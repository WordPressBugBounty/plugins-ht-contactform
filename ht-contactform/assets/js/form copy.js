'use strict';

const {__} = wp.i18n;

const fileAllowTypes = {
    image: [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/jpg',
    ],
    audio: [
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/oga',
        'audio/wma',
        'audio/mka',
        'audio/m4a',
        'audio/ra',
        'audio/mid',
        'audio/midi',
    ],
    video: [
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/avi',
        'video/divx',
        'video/flv',
        'video/mov',
        'video/ogv',
        'video/mkv',
        'video/m4v',
        'video/divx',
        'video/mpg',
        'video/mpeg',
        'video/mpe',
    ],
    pdf: [
        'application/pdf',
    ],
    doc: [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/plain',
    ],
    zip: [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/rar',
        'application/x-7z-compressed',
        'application/7z',
        'application/gzip',
        'application/x-gzip',
    ],
    exe: [
        'application/exe',
        'application/x-exe',
    ],
    csv: [
        'text/csv',
    ],
}

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
            this.checkConditionalLogic(form);
            form.querySelectorAll('.ht-form-message').forEach(message => {
                setTimeout(() => {
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.delete('form_success');
                    urlParams.delete('form_error');
                    window.history.replaceState({}, '', decodeURIComponent(`${window.location.pathname}?${urlParams.toString()}`));
                    message.style.display = 'none';
                }, 5000);
            });
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
                this.checkConditionalLogic(form);
            });
            
            // For select elements and other fields that might not trigger input events
            field.addEventListener('change', () => {
                this.clearErrorForField(field, form);
                this.checkConditionalLogic(form);
                
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

        if(field.type === 'checkbox' && field.checked) {
            field.closest('.ht-form-elem-checkbox').classList.add('checked');
        } else {
            field.closest('.ht-form-elem-checkbox').classList.remove('checked');
        }

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
                    message: __('reCAPTCHA is not properly configured', 'ht-contactform'),
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
                                message: __('reCAPTCHA v3 execution failed', 'ht-contactform'),
                            });
                        });
                    });
                } catch (error) {
                    reject({
                        message: __('reCAPTCHA v3 is not properly configured', 'ht-contactform'),
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
                        message: __('Please complete the reCAPTCHA verification', 'ht-contactform'),
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
                    setTimeout(() => {
                        const urlParams = new URLSearchParams(window.location.search);
                        urlParams.delete('form_success');
                        urlParams.delete('form_error');
                        window.history.replaceState({}, '', decodeURIComponent(`${window.location.pathname}?${urlParams.toString()}`));
                        messageContainer.style.display = 'none';
                    }, 5000);
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
            // Extract error message from axios error response
            let errorMessage = 'Form submission failed. Please try again.';
            
            if (error.response && error.response.data) {
                if (error.response.data.message) {
                    errorMessage = error.response.data.message;
                } else if (error.response.data.code === 'submission_too_quick') {
                    errorMessage = __('Please wait a moment before submitting the form.', 'ht-contactform');
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
                            errorElement.textContent = error?.message || __('reCAPTCHA verification failed', 'ht-contactform');
                            errorElement.style.display = 'block';
                            // Scroll to error
                            fieldContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            // Fallback if error element not found
                            alert(error?.message || __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'));
                        }
                    } else {
                        // Fallback if container not found
                        alert(error?.message || __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'));
                    }
                });
            } else {
                // Show loading state if available
                const submitButton = form.querySelector('[type="submit"]');
                let originalText = '';
                if(submitButton) {
                    originalText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.classList.add('loading');
                    submitButton.innerHTML = '<span class="ht-form-loader"></span>' + originalText;
                }
                // For non-AJAX forms, handle reCAPTCHA and then submit
                this.handleRecaptcha(form).then(() => {
                    // Standard form submission if AJAX is not enabled
                    const isValid = this.validateForm(form);
                    if(!isValid) {
                        this.scrollToFirstError(form);
                        return;
                    }
                    form.submit();
                }).catch(error => {
                    alert(error.message || __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'));
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
        
        // Phone validation
        if (field.type === 'tel' && field.getAttribute('data-validation')) {
            const iti = window.intlTelInput.getInstance(field);
            if(!iti.isValidNumber()) {
                this.showErrorForField(field, fieldContainer, errorElement, 'phone');
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
        } else if (errorType === 'phone') {
            fieldMessage = field.getAttribute('data-validation-message');
            errorElement.textContent = fieldMessage || this.messages.phone;
        }
        
        errorElement.style.display = 'block';
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
    },
    
    /**
     * Evaluate a single condition
     * @param {Object} condition - The condition to evaluate
     * @param {HTMLFormElement} form - The form element
     * @returns {boolean} - Whether the condition is met
     */
    evaluateCondition: function(condition, form) {
        const { field, operator, value } = condition;
        const fieldElement = form.querySelector(`[name="${field}"]`);
        
        if(!fieldElement) {
            return false;
        }
        
        const fieldValue = fieldElement.value;
        
        switch(operator) {
            case 'is':
            case '==':
                return fieldValue === value;
            case 'is_not':
            case '!=':
                return fieldValue !== value;
            case 'contains':
                return fieldValue.includes(value);
            case 'does_not_contain':
            case 'doNotContains':
                return !fieldValue.includes(value);
            case 'greater_than':
            case '>':
                return +fieldValue > +value;
            case 'less_than':
            case '<':
                return +fieldValue < +value;
            case '>=':
                return +fieldValue >= +value;
            case '<=':
                return +fieldValue <= +value;
            case 'startsWith':
                return fieldValue.startsWith(value);
            case 'endsWith':
                return fieldValue.endsWith(value);
            case 'test_regex':
                try {
                    const regex = new RegExp(value);
                    return regex.test(fieldValue);
                } catch (e) {
                    console.error('Invalid regex pattern:', value);
                    return false;
                }
            default:
                return false;
        }
    },
    
    /**
     * Check conditional logic
     * @param {HTMLFormElement} form - The form element
     */
    checkConditionalLogic: function(form) {
        form.querySelectorAll('.ht-form-elem').forEach((field) => {
            if(field.hasAttribute('data-condition-match') && field.hasAttribute('data-condition-logic')) {
                const match = field.getAttribute('data-condition-match');
                const logic = field.getAttribute('data-condition-logic');
                const conditions = JSON.parse(logic);
                let isValid = false;
                
                if(match === 'any') {
                    isValid = conditions?.some(condition => this.evaluateCondition(condition, form));
                } else if(match === 'all') {
                    isValid = conditions?.every(condition => this.evaluateCondition(condition, form));
                }
                
                field.style.display = isValid ? 'block' : 'none';
            }
        });
    },
    
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
    // Tel
    if(document.querySelectorAll('.ht-form-elem-input-tel')) {
        document.querySelectorAll('.ht-form-elem-input-tel').forEach((input) => {
            const initialCountry = input.getAttribute('data-initial-country');
            const excludeCountries = input.getAttribute('data-exclude-countries') ? input.getAttribute('data-exclude-countries').split(',') : [];
            const onlyCountries = input.getAttribute('data-only-countries') ? input.getAttribute('data-only-countries').split(',') : [];
            window.intlTelInput(input, {
                loadUtils: () => import(`${ht_form.plugin_url}assets/lib/intl-tel-input/utils.min.js`),
                initialCountry: initialCountry,
                excludeCountries: excludeCountries,
                onlyCountries: onlyCountries,
                customPlaceholder: (selectedCountryPlaceholder, selectedCountryData) => "e.g. " + selectedCountryPlaceholder,
            });
            input.closest('.ht-form-elem-content').querySelector('.iti').style.cssText = `
                --iti-path-flags-1x: url(${ht_form.plugin_url}assets/images/intl-tel-input/flags.webp);
                --iti-path-flags-2x: url(${ht_form.plugin_url}assets/images/intl-tel-input/flags@2x.webp);
                --iti-path-globe-1x: url(${ht_form.plugin_url}assets/images/intl-tel-input/globe.webp);
                --iti-path-globe-2x: url(${ht_form.plugin_url}assets/images/intl-tel-input/globe@2x.webp);
            `;
        });
    }
    // Country
    if(document.querySelectorAll('.ht-form-elem-input-country')) {
        document.querySelectorAll('.ht-form-elem-input-country').forEach((input) => {
            const initialCountry = input.getAttribute('data-initial-country');
            const excludeCountries = input.getAttribute('data-exclude-countries') ? input.getAttribute('data-exclude-countries').split(',') : [];
            const onlyCountries = input.getAttribute('data-only-countries') ? input.getAttribute('data-only-countries').split(',') : [];
            jQuery(input).countrySelect({
                defaultCountry: initialCountry,
                excludeCountries: excludeCountries,
                onlyCountries: onlyCountries,
                preferredCountries: [],
                responsiveDropdown: true,
            });
        });
    }
    // DateTime (Flatpicker)
    if(document.querySelectorAll('.ht-form-elem-datetime')) {
        document.querySelectorAll('.ht-form-elem-datetime').forEach((input) => {
            const format = input.getAttribute('data-format');
            const range = input.getAttribute('data-range') === '1';
            const multiple = input.getAttribute('data-multiple') === '1';
            const appendTo = input.closest('.ht-form-elem-content');

            const enableTime = format?.includes('H') || format?.includes('h');
            const noDate = !format?.includes('Y');

            let mode = 'single';
            if(!enableTime) {
                if(range) mode = 'range';
                if(multiple) mode = 'multiple';
            }
            
            flatpickr(input, {
                enableTime: enableTime,
                noCalendar: noDate,
                dateFormat: format,
                mode: mode,
                // appendTo: appendTo,
                time_24hr: enableTime && format?.includes('H'),
            });
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
    
    // File Upload
    if(document.querySelectorAll('.ht-form-elem-file-upload') || document.querySelectorAll('.ht-form-elem-image-upload')) {
        document.querySelectorAll('.ht-form-elem-file-upload, .ht-form-elem-image-upload').forEach((fileUpload) => {
            const input = fileUpload.querySelector('input[type="file"]');
            const maxFileCount = parseInt(input.getAttribute('data-max-files'), 10) || null;
            const maxFileCountMessage = input.getAttribute('data-max-files-message');
            const maxFileSize = parseInt(input.getAttribute('data-max-file-size'), 10) || 10;
            const maxFileSizeMessage = input.getAttribute('data-max-file-size-message');
            const allowTypesMessage = input.getAttribute('data-allow-types-message');
            const uploadLocation = input.getAttribute('data-upload-location');
            
            // Register the necessary plugins
            FilePond.registerPlugin(FilePondPluginImagePreview);
            FilePond.registerPlugin(FilePondPluginFileValidateSize);
            FilePond.registerPlugin(FilePondPluginFileValidateType);

            // Idle Label
            let idleLabel = `<span class="filepond--label-action">Browse</span> or drag and drop your files.`;
            if(maxFileCount && maxFileCount > 1 && maxFileSize) {
                idleLabel += `<br/> <span class="filepond-extra-info">Max files: ${maxFileCount} and Max size: ${maxFileSize}MB</span>`;
            }
            if(maxFileCount && maxFileCount > 1 && !maxFileSize) {
                idleLabel += `<br/> <span class="filepond-extra-info">Max files: ${maxFileCount}</span>`;
            }
            if((!maxFileCount || maxFileCount === 1) && maxFileSize) {
                idleLabel += `<br/> <span class="filepond-extra-info">Max size: ${maxFileSize}MB</span>`;
            }
            
            // Create a FilePond instance
            const pond = FilePond.create(input, {
                // Configure FilePond options
                allowMultiple: maxFileCount > 1,
                maxFiles: parseInt(maxFileCount, 10) || null,
                maxFileSize: maxFileSize * 1024 * 1024,
                fileValidateTypeLabelExpectedTypes: 'Expects {allTypes}',
                acceptedFileTypes: input.accept ? input.accept.split(',') : null,
                fileSizeBase: 1024,
                server: {
                    process: (fieldName, file, metadata, load, error, progress, abort) => {
                        const formData = new FormData();
                        formData.append('action', 'ht_form_temp_file_upload');
                        formData.append('_wpnonce', ht_form.nonce);
                        formData.append('ht_form_file', file);
                    
                        const source = axios.CancelToken.source();
                    
                        axios.post(ht_form.ajaxurl, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        cancelToken: source.token,
                        onUploadProgress: (e) => {
                            // Compute progress in percentage
                            progress(e.lengthComputable, e.loaded, e.total);
                        }
                        })
                        .then(response => {
                            const data = response.data;
                            if (data.success) {
                                load(data.data.file_id); // pass file ID to FilePond
                            } else {
                                error(data.data || 'Upload failed');
                            }
                        })
                        .catch(err => {
                            if (axios.isCancel(err)) {
                                error('Upload cancelled');
                            } else {
                                error('Upload failed: ' + (err.message || 'Unknown error'));
                            }
                        });
                    
                        // Setup abort method
                        return {
                            abort: () => {
                                source.cancel('Upload aborted by user');
                                abort();
                            }
                        };
                    },
                    
                    revert: (uniqueFileId, load, error) => {
                        const formData = new FormData();
                        formData.append('action', 'ht_form_temp_file_delete');
                        formData.append('_wpnonce', ht_form.nonce);
                        formData.append('ht_form_file_id', uniqueFileId);
                    
                        axios.post(ht_form.ajaxurl, formData)
                        .then(response => {
                            const data = response.data;
                            if (data.success) {
                                load();
                            } else {
                                error(data.data || 'Delete failed');
                            }
                        })
                        .catch(err => {
                            error('Delete failed: ' + (err.message || 'Unknown error'));
                        });
                    },
                    
                    load: null,
                    restore: null,
                    fetch: null
                },                  
                labelIdle: idleLabel,
                labelMaxFileSizeExceeded: maxFileSizeMessage ? maxFileSizeMessage.replace('%s', maxFileSize) : __('File is too large. Maximum size is %sMB.', 'ht-contactform').replace('%s', maxFileSize),
                labelMaxFileSize: __('Maximum file size is %sMB.', 'ht-contactform').replace('%s', maxFileSize),
                labelMaxTotalFileSizeExceeded: __('Maximum total size exceeded', 'ht-contactform'),
                labelMaxTotalFileSize: maxFileCount ? __('Maximum total size is %sMB', 'ht-contactform').replace('%s', maxFileSize * maxFileCount) : __('Maximum total size is %sMB', 'ht-contactform').replace('%s', maxFileSize),
                labelFileTypeNotAllowed: allowTypesMessage ? allowTypesMessage : __('File type not allowed', 'ht-contactform'),
                labelFileProcessing: __('Uploading', 'ht-contactform'),
                labelFileProcessingComplete: __('Upload complete', 'ht-contactform'),
                labelFileProcessingAborted: __('Upload cancelled', 'ht-contactform'),
                labelFileProcessingError: __('Error during upload', 'ht-contactform'),
                labelTapToCancel: __('tap to cancel', 'ht-contactform'),
                labelTapToRetry: __('tap to retry', 'ht-contactform'),
                labelTapToUndo: __('tap to undo', 'ht-contactform'),
                credits: false,
            });
            
            // Store FilePond instance in the DOM element for future reference
            fileUpload.filepond = pond;
        });
    }

    HTFormValidation.init();

})
