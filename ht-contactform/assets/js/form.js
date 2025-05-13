'use strict';

/**
 * HT Form Validation Module
 */
const HTFormValidation = {
    // Default validation messages
    messages: window?.ht_form?.i18n,
    
    /**
     * Initialize form validation
     */
    init: function() {
        const forms = document.querySelectorAll('.ht-form');
        forms.forEach(form => {
            this.setupFieldEventListeners(form);
            this.setupFormSubmitListener(form);
        });
    },
    
    /**
     * Set up event listeners for field validation
     * @param {HTMLFormElement} form - The form element
     */
    setupFieldEventListeners: function(form) {
        // Add input/change event listeners to all form fields
        form.querySelectorAll('input, select, textarea').forEach(field => {
            // For regular inputs and textareas
            field.addEventListener('input', () => {
                this.clearErrorForField(field, form);
            });
            
            // For select elements and other fields that might not trigger input events
            field.addEventListener('change', () => {
                this.clearErrorForField(field, form);
                
                // Handle checkbox/radio groups
                if (field.type === 'checkbox' || field.type === 'radio') {
                    this.handleCheckboxRadioGroupChange(field, form);
                }
            });
        });
    },
    
    /**
     * Handle changes to checkbox or radio button groups
     * @param {HTMLInputElement} field - The changed field
     * @param {HTMLFormElement} form - The form element
     */
    handleCheckboxRadioGroupChange: function(field, form) {
        const name = field.getAttribute('name');
        if (!name) return;
        
        // Find the parent container that holds the error message
        const fieldContainer = field.closest('.ht-form-elem');
        if (!fieldContainer) return;
        
        // Check if any in the group is checked
        const groupInputs = form.querySelectorAll(`input[name="${name}"]`);
        const isAnyChecked = Array.from(groupInputs).some(input => input.checked);
        
        if (isAnyChecked) {
            // Clear errors for the entire group
            groupInputs.forEach(input => {
                input.classList.remove('error');
            });
            
            // Clear the error message
            const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        }
    },
    
    /**
     * Clear error state for a field
     * @param {HTMLElement} field - The field to clear errors for
     * @param {HTMLFormElement} form - The form element
     */
    clearErrorForField: function(field, form) {
        const fieldContainer = field.closest('.ht-form-elem');
        if (!fieldContainer) return;
        
        // Remove error class from the field
        field.classList.remove('error');
        
        // For select fields with Choices.js
        if (field.tagName.toLowerCase() === 'select') {
            const choicesContainer = fieldContainer.querySelector('.choices');
            if (choicesContainer) {
                choicesContainer.classList.remove('error');
            }
        }
        
        // Clear error message
        const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    },
    
    /**
     * Handle reCAPTCHA verification
     * @param {HTMLFormElement} form - The form element
     * @returns {Promise<string|null>} - Promise that resolves with the reCAPTCHA token or null if not used
     */
    handleRecaptcha: function(form) {
        return new Promise((resolve, reject) => {
            // Check if form has reCAPTCHA
            const recaptchaField = form.querySelector('input[name="g-recaptcha-response"]');
            if (!recaptchaField) {
                resolve(null);
                return;
            }

            // Check if reCAPTCHA is properly loaded
            if(
                typeof grecaptcha === 'undefined' ||
                typeof grecaptcha.execute !== 'function' ||
                typeof grecaptcha.getResponse !== 'function'
            ) {
                reject({
                    message: 'reCAPTCHA is not properly configured'
                });
                return;
            }

            // Check if we're using reCAPTCHA v3
            if (ht_form?.captcha?.recaptcha_version === 'reCAPTCHAv3') {
                try {
                    grecaptcha.ready(function() {
                        grecaptcha.execute(ht_form?.captcha?.recaptcha_site_key, {action: 'submit'})
                        .then(function(token) {
                            recaptchaField.value = token;
                            resolve(token);
                        })
                        .catch(function() {
                            reject({
                                message: 'reCAPTCHA v3 execution failed'
                            });
                        });
                    });
                } catch (error) {
                    reject({
                        message: 'reCAPTCHA v3 is not properly configured'
                    });
                }
            }

            // Check if we're using reCAPTCHA v2
            if (ht_form?.captcha?.recaptcha_version === 'reCAPTCHAv2') {
                const token = grecaptcha.getResponse();
                if (token) {
                    recaptchaField.value = token;
                    resolve(token);
                } else {
                    reject({
                        message: 'Please complete the reCAPTCHA verification'
                    });
                }
            }
        });
    },
    
    /**
     * Submit form with AJAX
     * @param {HTMLFormElement} form - The form element
     * @param {string|null} recaptchaToken - The reCAPTCHA token if available
     */
    submitFormWithAjax: function(form, recaptchaToken) {
        const isValid = this.validateForm(form);
        if(!isValid) {
            this.scrollToFirstError(form);
            return;
        }
        // Show loading state if available
        const submitButton = form.querySelector('[type="submit"]');
        let originalText = '';
        if(submitButton) {
            originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.classList.add('loading');
            submitButton.innerHTML = '<span class="ht-form-loader"></span>' + originalText;
        }
        
        // Create FormData object
        const formData = new FormData();

        const inputs = form.querySelectorAll('[name]');
        inputs.forEach(input => {
            
            // Handle different input types appropriately
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    for (let i = 0; i < input.files.length; i++) {
                        formData.append(input.name, input.files[i]);
                    }
                }
            } else if ((input.type === 'checkbox' || input.type === 'radio')) {
                // Only include checked checkboxes/radios
                if (input.checked) {
                    formData.append(input.name, input.value);
                } 
            } else if (input.tagName === 'SELECT' && input.multiple) {
                // Handle multiple select
                Array.from(input.selectedOptions).forEach(option => {
                    formData.append(`${input.name}[]`, option.value);
                });
            } else if (input.type === 'text' && input.name.match(/\[(.*?)\]/)) {
                // Handle array input fields (e.g. names[first_name])
                const name = input.name.slice(0, input.name.indexOf('['));
                const nameParts = input.name.match(/\[(.*?)\]/);
                const arrayName = nameParts[1];
                const arrayValues = formData.getAll(arrayName);
                formData.append(`${name}[${arrayName}]`, arrayValues.concat([input.value]));
            } else {
                // All other input types
                formData.append(input.name, input.value);
            }
        });
        
        // Add form ID if available
        if (form.id) {
            formData.append('form_id', form.id);
        }
        
        // Send AJAX request with axios
        axios({
            method: 'post',
            url: `${ht_form.rest_url}ht-form/v1/submission`,
            data: formData,
            headers: { 
                'Content-Type': 'multipart/form-data',
                'X-WP-Nonce': ht_form.rest_nonce || '' 
            },
            withCredentials: true
        })
        .then(response => {
            // Axios automatically parses JSON response data
            const confirmation = response.data.confirmation;
            
            // Show success message if provided in response
            if(confirmation) {
                if(confirmation?.type === 'message') {
                    const messageContainer = form.querySelector('.ht-form-message') || 
                                        document.createElement('div');
                    
                    if(!form.querySelector('.ht-form-message')) {
                        messageContainer.className = 'ht-form-message';
                        form.prepend(messageContainer);
                    }
                    
                    messageContainer.innerHTML = `<div class="ht-form-success">${confirmation.message}</div>`;
                    messageContainer.style.display = 'block';
                    
                    // Scroll to message
                    messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                if(confirmation?.type === 'redirect') {
                    if(confirmation?.newTab) {
                        window.open(confirmation.redirect, '_blank');
                    } else {
                        window.location.href = confirmation.redirect;
                    }
                }
                if(confirmation?.type === 'page') {
                    if(confirmation?.newTab) {
                        window.open(confirmation.page, '_blank');
                    } else {
                        window.location.href = confirmation.page;
                    }
                }
            }
        })
        .catch(error => {
            console.log('Error submitting form:', error);
            
            // Extract error message from axios error response
            let errorMessage = 'Form submission failed. Please try again.';
            
            if (error.response && error.response.data) {
                if (error.response.data.message) {
                    errorMessage = error.response.data.message;
                } else if (error.response.data.code === 'submission_too_quick') {
                    errorMessage = 'Please wait a moment before submitting the form.';
                }
            }
            
            // Show error message
            const messageContainer = form.querySelector('.ht-form-message') ||  document.createElement('div');
            
            if(!form.querySelector('.ht-form-message')) {
                messageContainer.className = 'ht-form-message';
                form.prepend(messageContainer);
            }
            
            messageContainer.innerHTML = `<div class="ht-form-error">${errorMessage}</div>`;
            messageContainer.style.display = 'block';
            
            // Scroll to message
            messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .finally(() => {
            // Restore button state
            const submitButton = form.querySelector('[type="submit"]');
            if(submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                submitButton.classList.remove('loading');
            }
        });
    },
    
    /**
     * Set up form submission event listener
     * @param {HTMLFormElement} form - The form element
     */
    setupFormSubmitListener: function(form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            
            // Remove only form_success and form_error parameters from URL
            if (window.location.search) {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('form_success') || urlParams.has('form_error')) {
                    urlParams.delete('form_success');
                    urlParams.delete('form_error');
                    const newUrl = urlParams.toString() ? 
                        `${window.location.pathname}?${urlParams.toString()}` : 
                        window.location.pathname;
                    window.history.replaceState(null, null, newUrl);
                }
            }

            // Check if AJAX submission is enabled for this form
            if(form.getAttribute('data-ajax-enabled') === 'true') {
                // Handle reCAPTCHA if present
                this.handleRecaptcha(form).then(recaptchaToken => {
                    this.submitFormWithAjax(form, recaptchaToken);
                }).catch(error => {
                    const fieldContainer = form.querySelector('.ht-form-elem-recaptcha-field');
                    if (fieldContainer) {
                        const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
                        if (errorElement) {
                            errorElement.textContent = error?.message || 'reCAPTCHA verification failed';
                            errorElement.style.display = 'block';
                            // Scroll to error
                            fieldContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            // Fallback if error element not found
                            console.error('reCAPTCHA error:', error);
                            alert(error?.message || 'reCAPTCHA verification failed. Please try again.');
                        }
                    } else {
                        // Fallback if container not found
                        console.error('reCAPTCHA error:', error);
                        alert(error?.message || 'reCAPTCHA verification failed. Please try again.');
                    }
                });
            } else {
                // For non-AJAX forms, handle reCAPTCHA and then submit
                this.handleRecaptcha(form).then(() => {
                    // Standard form submission if AJAX is not enabled
                    form.submit();
                }).catch(error => {
                    alert(error.message || 'reCAPTCHA verification failed. Please try again.');
                });
            }
        });
    },
    
    /**
     * Validate the entire form
     * @param {HTMLFormElement} form - The form element
     * @returns {boolean} - Whether the form is valid
     */
    validateForm: function(form) {
        let isValid = true;
        
        // Clear all previous errors first
        this.clearAllErrors(form);
        
        // Validate all fields
        form.querySelectorAll('input, select, textarea').forEach(field => {
            if (!this.validateField(field, form)) {
                isValid = false;
            }
        });
        
        // Reset validation processed flag for next validation
        form.querySelectorAll('input[data-validation-processed]').forEach(field => {
            delete field.dataset.validationProcessed;
        });
        
        return isValid;
    },
    
    /**
     * Clear all errors in the form
     * @param {HTMLFormElement} form - The form element
     */
    clearAllErrors: function(form) {
        form.querySelectorAll('.ht-form-elem-error').forEach(errorEl => {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        });
        
        form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
        });
    },
    
    /**
     * Validate a single field
     * @param {HTMLElement} field - The field to validate
     * @param {HTMLFormElement} form - The form element
     * @returns {boolean} - Whether the field is valid
     */
    validateField: function(field, form) {
        // Find the parent element with error message container
        const fieldContainer = field.closest('.ht-form-elem');
        if (!fieldContainer) return true; // Skip if no container found
        
        const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
        if (!errorElement) return true; // Skip if no error element found
        
        // Handle checkboxes and radio buttons differently
        if ((field.type === 'checkbox' || field.type === 'radio') && field.hasAttribute('required')) {
            return this.validateCheckboxRadioGroup(field, form, fieldContainer, errorElement);
        }
        
        // Required field validation for regular inputs
        if (field.hasAttribute('required') && !field.value.trim()) {
            this.showErrorForField(field, fieldContainer, errorElement, 'required');
            return false;
        }

        // Input Mask Validation
        if(field?.getAttribute('data-mask')) {
            const maskFormat = field.getAttribute('data-mask');
            const value = field.value.trim();
            
            if (value && !this.validateMaskedInput(field, maskFormat)) {
                this.showErrorForField(field, fieldContainer, errorElement, 'format');
                return false;
            }
        }
        
        // Email validation
        if (field.type === 'email' && field.getAttribute('data-email-validation') && field.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value.trim())) {
                this.showErrorForField(field, fieldContainer, errorElement, 'email');
                return false;
            }
        }

        // Number Minimum/Maximum Validation
        if(field?.type === 'number') {
            const value = parseInt(field.value);
            if(field.getAttribute('min')) {
                const minValue = parseInt(field.getAttribute('min'));
                if (value < minValue) {
                    this.showErrorForField(field, fieldContainer, errorElement, 'min');
                    return false;
                }
            }
            if(field.getAttribute('max')) {
                const maxValue = parseInt(field.getAttribute('max'));
                if (value > maxValue) {
                    this.showErrorForField(field, fieldContainer, errorElement, 'max');
                    return false;
                }
            }
        }
        
        return true;
    },
    
    /**
     * Validate a checkbox or radio button group
     * @param {HTMLInputElement} field - The field to validate
     * @param {HTMLFormElement} form - The form element
     * @param {HTMLElement} fieldContainer - The field container
     * @param {HTMLElement} errorElement - The error element
     * @returns {boolean} - Whether the group is valid
     */
    validateCheckboxRadioGroup: function(field, form, fieldContainer, errorElement) {
        // Get the name of the checkbox/radio group
        const name = field.getAttribute('name');
        
        // If we've already processed this group, skip it
        if (field.dataset.validationProcessed === 'true') return true;
        
        // Mark field as processed to avoid duplicate validation for the same group
        field.dataset.validationProcessed = 'true';
        
        // Find all inputs in this group
        const groupInputs = form.querySelectorAll(`input[name="${name}"]`);
        
        // Check if any option is selected
        const isAnyChecked = Array.from(groupInputs).some(input => input.checked);
        
        if (!isAnyChecked) {
            // Mark the container as having an error
            groupInputs.forEach(input => {
                input.classList.add('error');
            });
            
            // Show error message
            const fieldMessage = field.getAttribute('data-required-message') || 
                                fieldContainer.getAttribute('data-required-message');
            errorElement.textContent = fieldMessage || this.messages.required;
            errorElement.style.display = 'block';
            
            // Debug
            console.log('Checkbox/Radio group with error:', name, 'Message:', errorElement.textContent);
            
            return false;
        }
        
        return true;
    },
    
    /**
     * Show error message for a field
     * @param {HTMLElement} field - The field with error
     * @param {HTMLElement} fieldContainer - The field container
     * @param {HTMLElement} errorElement - The error element
     * @param {string} errorType - The type of error (required, email, etc.)
     */
    showErrorForField: function(field, fieldContainer, errorElement, errorType) {
        // Mark field as error
        field.classList.add('error');
        
        // For select fields with Choices.js
        if (field.tagName.toLowerCase() === 'select') {
            const choicesContainer = fieldContainer.querySelector('.choices');
            if (choicesContainer) {
                choicesContainer.classList.add('error');
            }
        }
        
        // Show error message
        let fieldMessage = '';
        if (errorType === 'required') {
            fieldMessage = field.getAttribute('data-required-message');
            errorElement.textContent = fieldMessage || this.messages.required;
        } else if (errorType === 'email') {
            fieldMessage = field.getAttribute('data-email-validation-message');
            errorElement.textContent = fieldMessage || this.messages.email;
        } else if (errorType === 'format') {
            fieldMessage = field.getAttribute('data-format-message');
            const maskType = field.getAttribute('data-mask');
            errorElement.textContent = fieldMessage || this.messages.input_mask.replace('{format}', maskType);
        } else if (errorType === 'min') {
            errorElement.textContent = this.messages.minimum_number.replace('{min}', field.getAttribute('min'));
        } else if (errorType === 'max') {
            errorElement.textContent = this.messages.maximum_number.replace('{max}', field.getAttribute('max'));
        }
        
        errorElement.style.display = 'block';
        
        // Debug
        console.log('Field with error:', field.id, 'Message:', errorElement.textContent);
    },
    
    /**
     * Validate a masked input field
     * @param {HTMLElement} field - The field to validate
     * @param {string} maskFormat - The mask format
     * @returns {boolean} - Whether the input is valid
     */
    validateMaskedInput: function(field, maskFormat) {
        const value = field.value.trim();
        
        // If no value, consider it valid (required check is handled separately)
        if (!value) return true;
        
        // Check if the input has the Inputmask instance
        if (field.inputmask) {
            // Use Inputmask's built-in validation
            return field.inputmask.isComplete();
        }
        
        // Fallback validation for specific formats if Inputmask API is not available
        if (maskFormat === 'MM/DD/YYYY') {
            return /^(0[1-9]|1[0-2])\/([0-2][0-9]|3[0-1])\/\d{4}$/.test(value);
        } else if (maskFormat === 'HH:MM') {
            return /^([0-1][0-9]|2[0-3]):([0-5][0-9])$/.test(value);
        } else if (maskFormat === '9999 9999 9999 9999') {
            return /^\d{4}\s\d{4}\s\d{4}\s\d{4}$/.test(value);
        } else if (maskFormat === '$999.99') {
            return /^\$\d+\.\d{2}$/.test(value);
        } else if (maskFormat === '(999) 999-9999') {
            return /^\(\d{3}\)\s\d{3}-\d{4}$/.test(value);
        } else if (maskFormat === '999-99-9999') {
            return /^\d{3}-\d{2}-\d{4}$/.test(value);
        } else if (maskFormat === '99999-9999') {
            return /^\d{5}-\d{4}$/.test(value);
        }
        
        // For custom or unrecognized formats, consider it valid
        return true;
    },
    
    /**
     * Scroll to the first error in the form
     * @param {HTMLFormElement} form - The form element
     */
    scrollToFirstError: function(form) {
        const firstError = form.querySelector('.error');
        if (firstError) {
            const fieldContainer = firstError.closest('.ht-form-elem');
            if (fieldContainer) {
                fieldContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {

    // Handle Range Slider Value Update
    if(document.querySelectorAll('.ht-form-elem-range')) {
        document.querySelectorAll('.ht-form-elem-range').forEach((range) => {
            range.addEventListener('input', () => {
                range.nextElementSibling.querySelector('.ht-form-elem-range-amount').textContent = range.value;
            });
        });
    }

    // Input Mask
    if(document.querySelectorAll('.ht-form-elem-input-mask[data-mask]')) {
        document.querySelectorAll('.ht-form-elem-input-mask[data-mask]').forEach((input) => {
            const maskFormat = input.getAttribute('data-mask');
            
            if (maskFormat) {
                let maskOptions = {};
                
                // Configure specific formats
                if (maskFormat === 'MM/DD/YYYY') {
                    // Date mask with M/D/Y format
                    maskOptions = {
                        alias: 'datetime',
                        inputFormat: 'MM/DD/YYYY',
                    };
                } else if (maskFormat === 'HH:MM') {
                    // Time mask
                    maskOptions = {
                        alias: 'datetime',
                        inputFormat: 'HH:mm',
                        placeholder: 'HH:MM'
                    };
                } else if (maskFormat === '9999 9999 9999 9999') {
                    // Credit card mask
                    maskOptions = {
                        mask: '9999 9999 9999 9999'
                    };
                } else if (maskFormat === '$999.99') {
                    // Currency mask
                    maskOptions = {
                        alias: 'numeric',
                        groupSeparator: '',
                        digits: 2,
                        digitsOptional: false,
                        prefix: '$',
                        rightAlign: false,
                        allowMinus: false,
                    };
                } else if (maskFormat === '(999) 999-9999') {
                    // Phone mask
                    maskOptions = {
                        mask: '(999) 999-9999'
                    };
                } else if (maskFormat === '999-99-9999') {
                    // SSN mask
                    maskOptions = {
                        mask: '999-99-9999'
                    };
                } else if (maskFormat === '99999-9999') {
                    // Zip code mask
                    maskOptions = {
                        mask: '99999-9999'
                    };
                } else {
                    // Default - use the format as is
                    maskOptions = {
                        mask: maskFormat
                    };
                }
                
                // Apply the mask and store reference for validation
                const im = new Inputmask(maskOptions);
                im.mask(input);
                
                // Add blur event for immediate validation feedback
                // input.addEventListener('blur', function() {
                //     if (this.value.trim() && !this.inputmask.isComplete()) {
                //         // Find the form and validate this field
                //         const form = this.closest('form');
                //         if (form && HTFormValidation.validateField) {
                //             HTFormValidation.validateField(this, form);
                //         }
                //     }
                // });
            }
        });
    }
    
    // Custom Select using Choices JS
    if(document.querySelectorAll('[data-ht-select]')) {
        document.querySelectorAll('[data-ht-select]').forEach((select) => {
            const searchable = select.getAttribute('data-searchable') === '1';
            const maxselect = select.getAttribute('data-maxselect') ? parseInt(select.getAttribute('data-maxselect')) : -1;
            
            new Choices(select, {
                searchEnabled: searchable,
                itemSelectText: '',
                maxItemCount: maxselect,
                removeItemButton: true,
                placeholder: true,
                placeholderValue: '',
                shouldSort: false,
            });
        });
    }

    HTFormValidation.init();

})
