'use strict';

const { __ } = wp.i18n;

/**
 * File type configurations for form uploads
 */
const HTFORM_FILE_TYPES = {
    image: ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'],
    audio: ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/oga', 'audio/wma', 'audio/mka', 'audio/m4a', 'audio/ra', 'audio/mid', 'audio/midi'],
    video: ['video/mp4', 'video/mpeg', 'video/ogg', 'video/avi', 'video/divx', 'video/flv', 'video/mov', 'video/ogv', 'video/mkv', 'video/m4v', 'video/divx', 'video/mpg', 'video/mpeg', 'video/mpe'],
    pdf: ['application/pdf'],
    doc: ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/plain'],
    zip: ['application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed', 'application/rar', 'application/x-7z-compressed', 'application/7z', 'application/gzip', 'application/x-gzip'],
    exe: ['application/exe', 'application/x-exe'],
    csv: ['text/csv']
};

/**
 * Configuration constants
 */
const HTFORM_CONFIG = {
    FORM_SELECTOR: '.ht-form',
    FIELD_SELECTOR: 'input, select, textarea',
    ERROR_CLASS: 'error',
    MESSAGE_DISPLAY_TIME: 5000,
    SCROLL_BEHAVIOR: { behavior: 'smooth', block: 'center' }
};

/**
 * Utility functions module
 */
const HTFormUtils = {
    /**
     * Remove URL parameters and update browser history
     * @param {string[]} params - Parameters to remove
     */
    removeUrlParams(params) {
        if (!window.location.search) return;
        
        const urlParams = new URLSearchParams(window.location.search);
        let hasChanges = false;
        
        params.forEach(param => {
            if (urlParams.has(param)) {
                urlParams.delete(param);
                hasChanges = true;
            }
        });
        
        if (hasChanges) {
            const newUrl = urlParams.toString() ? 
                `${window.location.pathname}?${urlParams.toString()}` : 
                window.location.pathname;
            window.history.replaceState({}, '', decodeURIComponent(newUrl));
        }
    },

    /**
     * Scroll element into view with smooth behavior
     * @param {HTMLElement} element - Element to scroll to
     * @param {Object} options - Scroll options
     */
    scrollToElement(element, options = HTFORM_CONFIG.SCROLL_BEHAVIOR) {
        if (element) {
            element.scrollIntoView(options);
        }
    },

    /**
     * Debounce function calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

/**
 * Message handling module
 */
const HTFormMessageHandler = {
    /**
     * Show success message
     * @param {HTMLFormElement} form - Form element
     * @param {string} message - Success message
     */
    showSuccess(form, message) {
        this._showMessage(form, message, 'ht-form-success');
    },

    /**
     * Show error message
     * @param {HTMLFormElement} form - Form element
     * @param {string} message - Error message
     */
    showError(form, message) {
        this._showMessage(form, message, 'ht-form-error');
    },

    /**
     * Show message with specified type
     * @private
     * @param {HTMLFormElement} form - Form element
     * @param {string} message - Message text
     * @param {string} className - CSS class for message type
     */
    _showMessage(form, message, className) {
        let messageContainer = form.querySelector('.ht-form-message');
        
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'ht-form-message';
            form.prepend(messageContainer);
        }
        
        messageContainer.innerHTML = `<div class="${className}">${message}</div>`;
        messageContainer.style.display = 'block';
        
        HTFormUtils.scrollToElement(messageContainer, { behavior: 'smooth', block: 'start' });
        
        setTimeout(() => {
            HTFormUtils.removeUrlParams(['form_success', 'form_error']);
            messageContainer.style.display = 'none';
        }, HTFORM_CONFIG.MESSAGE_DISPLAY_TIME);
    },

    /**
     * Auto-hide existing messages
     * @param {HTMLFormElement} form - Form element
     */
    autoHideMessages(form) {
        const messages = form.querySelectorAll('.ht-form-message');
        messages.forEach(message => {
            setTimeout(() => {
                HTFormUtils.removeUrlParams(['form_success', 'form_error']);
                message.style.display = 'none';
            }, HTFORM_CONFIG.MESSAGE_DISPLAY_TIME);
        });
    }
};

/**
 * Form validation module
 */
const HTFormValidator = {
    messages: window?.ht_form?.i18n || {},

    /**
     * Validate entire form
     * @param {HTMLFormElement} form - Form to validate
     * @returns {boolean} True if form is valid
     */
    validateForm(form) {
        let isValid = true;
        
        this.clearAllErrors(form);
        
        form.querySelectorAll(HTFORM_CONFIG.FIELD_SELECTOR).forEach(field => {
            if (!this.validateField(field, form)) {
                isValid = false;
            }
        });
        
        this._resetValidationFlags(form);
        return isValid;
    },

    /**
     * Validate single field
     * @param {HTMLElement} field - Field to validate
     * @param {HTMLFormElement} form - Parent form
     * @returns {boolean} True if field is valid
     */
    validateField(field, form) {
        const fieldContainer = field.closest('.ht-form-elem');
        if (!fieldContainer) return true;
        
        const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
        if (!errorElement) return true;
        
        // Handle checkbox/radio groups
        if ((field.type === 'checkbox' || field.type === 'radio' || field.type === 'ratings') && field.hasAttribute('required')) {
            return this._validateCheckboxRadioGroup(field, form, fieldContainer, errorElement);
        }
        
        // Required field validation
        if (field.hasAttribute('required') && !field.value.trim()) {
            this._showFieldError(field, fieldContainer, errorElement, 'required');
            return false;
        }

        // Input mask validation
        if (field.getAttribute('data-mask')) {
            const maskFormat = field.getAttribute('data-mask');
            const value = field.value.trim();
            
            if (value && !this._validateMaskedInput(field, maskFormat)) {
                this._showFieldError(field, fieldContainer, errorElement, 'format');
                return false;
            }
        }
        
        // Phone validation
        if (field.type === 'tel' && field.getAttribute('data-validation')) {
            const iti = window.intlTelInput?.getInstance(field);
            if (iti && !iti.isValidNumber()) {
                this._showFieldError(field, fieldContainer, errorElement, 'phone');
                return false;
            }
        }
        
        // Email validation
        if (field.type === 'email' && field.getAttribute('data-email-validation') && field.value.trim()) {
            if (!this._validateEmail(field.value.trim())) {
                this._showFieldError(field, fieldContainer, errorElement, 'email');
                return false;
            }
        }

        // Number min/max validation
        if (field.type === 'number') {
            const value = parseInt(field.value);
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            
            if (min && value < parseInt(min)) {
                this._showFieldError(field, fieldContainer, errorElement, 'min');
                return false;
            }
            
            if (max && value > parseInt(max)) {
                this._showFieldError(field, fieldContainer, errorElement, 'max');
                return false;
            }
        }
        
        return true;
    },

    /**
     * Clear field error state
     * @param {HTMLElement} field - Field to clear
     * @param {HTMLFormElement} form - Parent form
     */
    clearFieldError(field, form) {
        const fieldContainer = field.closest('.ht-form-elem');
        if (!fieldContainer) return;
        
        field.classList.remove(HTFORM_CONFIG.ERROR_CLASS);
        
        // Clear Choices.js error state
        const choicesContainer = fieldContainer.querySelector('.choices');
        if (choicesContainer) {
            choicesContainer.classList.remove(HTFORM_CONFIG.ERROR_CLASS);
        }
        
        const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    },

    /**
     * Clear all form errors
     * @param {HTMLFormElement} form - Form to clear
     */
    clearAllErrors(form) {
        form.querySelectorAll('.ht-form-elem-error').forEach(errorEl => {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        });
        
        form.querySelectorAll(`.${HTFORM_CONFIG.ERROR_CLASS}`).forEach(el => {
            el.classList.remove(HTFORM_CONFIG.ERROR_CLASS);
        });
    },

    /**
     * Scroll to first error in form
     * @param {HTMLFormElement} form - Form element
     */
    scrollToFirstError(form) {
        const firstError = form.querySelector(`.${HTFORM_CONFIG.ERROR_CLASS}`);
        if (firstError) {
            const fieldContainer = firstError.closest('.ht-form-elem');
            HTFormUtils.scrollToElement(fieldContainer);
        }
    },

    // Private methods
    _validateCheckboxRadioGroup(field, form, fieldContainer, errorElement) {
        const name = field.getAttribute('name');
        
        if (field.dataset.validationProcessed === 'true') return true;
        field.dataset.validationProcessed = 'true';
        
        const groupInputs = form.querySelectorAll(`input[name="${name}"]`);
        const isAnyChecked = Array.from(groupInputs).some(input => input.checked);
        
        if (!isAnyChecked) {
            groupInputs.forEach(input => input.classList.add(HTFORM_CONFIG.ERROR_CLASS));
            
            const fieldMessage = field.getAttribute('data-required-message') || 
                               fieldContainer.getAttribute('data-required-message');
            errorElement.textContent = fieldMessage || this.messages.required;
            errorElement.style.display = 'block';
            
            return false;
        }
        
        return true;
    },

    _showFieldError(field, fieldContainer, errorElement, errorType) {
        field.classList.add(HTFORM_CONFIG.ERROR_CLASS);
        
        if (field.tagName.toLowerCase() === 'select') {
            const choicesContainer = fieldContainer.querySelector('.choices');
            if (choicesContainer) {
                choicesContainer.classList.add(HTFORM_CONFIG.ERROR_CLASS);
            }
        }
        
        const errorMessages = {
            required: field.getAttribute('data-required-message') || this.messages.required,
            email: field.getAttribute('data-email-validation-message') || this.messages.email,
            format: field.getAttribute('data-format-message') || this.messages.input_mask?.replace('{format}', field.getAttribute('data-mask')),
            min: this.messages.minimum_number?.replace('{min}', field.getAttribute('min')),
            max: this.messages.maximum_number?.replace('{max}', field.getAttribute('max')),
            phone: field.getAttribute('data-validation-message') || this.messages.phone
        };
        
        errorElement.textContent = errorMessages[errorType] || 'Validation error';
        errorElement.style.display = 'block';
    },

    _validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    _validateMaskedInput(field, maskFormat) {
        const value = field.value.trim();
        if (!value) return true;
        
        if (field.inputmask) {
            return field.inputmask.isComplete();
        }
        
        const patterns = {
            'MM/DD/YYYY': /^(0[1-9]|1[0-2])\/([0-2][0-9]|3[0-1])\/\d{4}$/,
            'HH:MM': /^([0-1][0-9]|2[0-3]):([0-5][0-9])$/,
            '9999 9999 9999 9999': /^\d{4}\s\d{4}\s\d{4}\s\d{4}$/,
            '$999.99': /^\$\d+\.\d{2}$/,
            '(999) 999-9999': /^\(\d{3}\)\s\d{3}-\d{4}$/,
            '999-99-9999': /^\d{3}-\d{2}-\d{4}$/,
            '99999-9999': /^\d{5}-\d{4}$/
        };
        
        return patterns[maskFormat] ? patterns[maskFormat].test(value) : true;
    },

    _resetValidationFlags(form) {
        form.querySelectorAll('input[data-validation-processed]').forEach(field => {
            delete field.dataset.validationProcessed;
        });
    }
};

/**
 * Event handler module
 */
const HTFormEventHandlers = {
    /**
     * Setup field event listeners
     * @param {HTMLFormElement} form - Form element
     */
    setupFieldListeners(form) {
        form.querySelectorAll(HTFORM_CONFIG.FIELD_SELECTOR).forEach(field => {
            field.addEventListener('input', () => {
                HTFormValidator.clearFieldError(field, form);
                HTFormConditionalLogic.check(form);
            });
            
            field.addEventListener('change', () => {
                HTFormValidator.clearFieldError(field, form);
                HTFormConditionalLogic.check(form);
                
                if (field.type === 'checkbox' || field.type === 'radio') {
                    this._handleCheckboxRadioChange(field, form);
                }
            });
        });

        document.querySelectorAll('.ht-form-elem-ratings').forEach(ratingsContainer => {
            ratingsContainer.querySelectorAll('label').forEach(label => {
                label.addEventListener('mouseover', () => {
                    this._handleRatingsHover(label, form);
                });
            });
            
            // Use mouseleave instead of mouseout to only trigger when leaving the entire container
            ratingsContainer.addEventListener('mouseleave', () => {
                this._resetRatingsToCheckedState(ratingsContainer);
            });
        });
    },

    /**
     * Setup form submit listener
     * @param {HTMLFormElement} form - Form element
     */
    setupSubmitListener(form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            HTFormUtils.removeUrlParams(['form_success', 'form_error']);

            if (form.getAttribute('data-ajax-enabled') === 'true') {
                this._handleAjaxSubmission(form);
            } else {
                this._handleStandardSubmission(form);
            }
        });
    },

    // Private methods
    _handleCheckboxRadioChange(field, form) {

        if(field.closest('.ht-form-elem-ratings')) {
            const field_id = field.id;
            const ratingsContainer = field.closest('.ht-form-elem-ratings');
            const labels = Array.from(ratingsContainer.querySelectorAll('label'));
            
            // Find the index of the clicked label
            const clickedIndex = labels.findIndex(label => label.htmlFor === field_id);
            
            // Add 'active' class to current and all previous labels
            labels.forEach((label, index) => {
                if (index <= clickedIndex) {
                    label.classList.add('active');
                } else {
                    label.classList.remove('active');
                }
            });
        }

        if (field.type === 'checkbox') {
            const container = field.closest('.ht-form-elem-checkbox');
            if (container) {
                container.classList.toggle('checked', field.checked);
            }
        }

        const name = field.getAttribute('name');
        if (!name) return;
        
        const fieldContainer = field.closest('.ht-form-elem');
        if (!fieldContainer) return;
        
        const groupInputs = form.querySelectorAll(`input[name="${name}"]`);
        const isAnyChecked = Array.from(groupInputs).some(input => input.checked);
        
        if (isAnyChecked) {
            groupInputs.forEach(input => input.classList.remove(HTFORM_CONFIG.ERROR_CLASS));
            
            const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        }
    },

    _handleRatingsHover(label, form) {
        const field_id = label.htmlFor;
        const ratingsContainer = label.closest('.ht-form-elem-ratings');
        const labels = Array.from(ratingsContainer.querySelectorAll('label'));
        
        // Find the index of the hovered label
        const hoveredIndex = labels.findIndex(label => label.htmlFor === field_id);
        
        // Add 'active' class to current and all previous labels
        labels.forEach((label, index) => {
            if (index <= hoveredIndex) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
        });
    },
    
    _resetRatingsToCheckedState(ratingsContainer) {
        const labels = Array.from(ratingsContainer.querySelectorAll('label'));
        const inputs = Array.from(ratingsContainer.querySelectorAll('input[type="radio"]'));
        
        // Find the checked input
        const checkedInput = inputs.find(input => input.checked);
        
        // Reset all labels to inactive first
        labels.forEach(label => {
            label.classList.remove('active');
        });
        
        if (checkedInput) {
            // Find the index of the checked input
            const checkedIndex = labels.findIndex(label => label.htmlFor === checkedInput.id);
            
            // Add 'active' class to checked and all previous labels
            labels.forEach((label, index) => {
                if (index <= checkedIndex) {
                    label.classList.add('active');
                }
            });
        }
    },

    _handleAjaxSubmission(form) {
        HTFormRecaptchaHandler.handle(form)
            .then(token => HTFormAjaxSubmitter.submit(form, token))
            .catch(error => HTFormRecaptchaHandler.showError(form, error));
    },

    _handleStandardSubmission(form) {
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton?.innerHTML || '';
        
        if (submitButton) {
            HTFormButtonState.setLoading(submitButton, originalText);
        }
        
        HTFormRecaptchaHandler.handle(form)
            .then(() => {
                if (HTFormValidator.validateForm(form)) {
                    form.submit();
                } else {
                    HTFormValidator.scrollToFirstError(form);
                    if (submitButton) {
                        HTFormButtonState.restore(submitButton, originalText);
                    }
                }
            })
            .catch(error => {
                alert(error.message || __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'));
                if (submitButton) {
                    HTFormButtonState.restore(submitButton, originalText);
                }
            });
    }
};

/**
 * Button state management
 */
const HTFormButtonState = {
    /**
     * Set button to loading state
     * @param {HTMLButtonElement} button - Button element
     * @param {string} originalText - Original button text
     */
    setLoading(button, originalText) {
        button.disabled = true;
        button.classList.add('loading');
        button.innerHTML = '<span class="ht-form-loader"></span>' + originalText;
    },

    /**
     * Restore button to normal state
     * @param {HTMLButtonElement} button - Button element
     * @param {string} originalText - Original button text
     */
    restore(button, originalText) {
        button.disabled = false;
        button.classList.remove('loading');
        button.innerHTML = originalText;
    }
};

/**
 * reCAPTCHA handling module
 */
const HTFormRecaptchaHandler = {
    /**
     * Handle reCAPTCHA verification
     * @param {HTMLFormElement} form - Form element
     * @returns {Promise<string|null>} reCAPTCHA token or null
     */
    handle(form) {
        return new Promise((resolve, reject) => {
            const recaptchaField = form.querySelector('input[name="g-recaptcha-response"]');
            if (!recaptchaField) {
                resolve(null);
                return;
            }

            if (!this._isRecaptchaLoaded()) {
                reject({ message: __('reCAPTCHA is not properly configured', 'ht-contactform') });
                return;
            }

            const version = ht_form?.captcha?.recaptcha_version;
            
            if (version === 'reCAPTCHAv3') {
                this._handleV3(recaptchaField, resolve, reject);
            } else if (version === 'reCAPTCHAv2') {
                this._handleV2(recaptchaField, resolve, reject);
            }
        });
    },

    /**
     * Show reCAPTCHA error
     * @param {HTMLFormElement} form - Form element
     * @param {Object} error - Error object
     */
    showError(form, error) {
        const fieldContainer = form.querySelector('.ht-form-elem-recaptcha-field');
        if (fieldContainer) {
            const errorElement = fieldContainer.querySelector('.ht-form-elem-error');
            if (errorElement) {
                errorElement.textContent = error?.message || __('reCAPTCHA verification failed', 'ht-contactform');
                errorElement.style.display = 'block';
                HTFormUtils.scrollToElement(fieldContainer, { behavior: 'smooth', block: 'start' });
                return;
            }
        }
        
        alert(error?.message || __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'));
    },

    // Private methods
    _isRecaptchaLoaded() {
        return typeof grecaptcha !== 'undefined' && 
               typeof grecaptcha.execute === 'function' && 
               typeof grecaptcha.getResponse === 'function';
    },

    _handleV3(recaptchaField, resolve, reject) {
        try {
            grecaptcha.ready(() => {
                grecaptcha.execute(ht_form?.captcha?.recaptcha_site_key, { action: 'submit' })
                    .then(token => {
                        recaptchaField.value = token;
                        resolve(token);
                    })
                    .catch(() => {
                        reject({ message: __('reCAPTCHA v3 execution failed', 'ht-contactform') });
                    });
            });
        } catch (error) {
            reject({ message: __('reCAPTCHA v3 is not properly configured', 'ht-contactform') });
        }
    },

    _handleV2(recaptchaField, resolve, reject) {
        const token = grecaptcha.getResponse();
        if (token) {
            recaptchaField.value = token;
            resolve(token);
        } else {
            reject({ message: __('Please complete the reCAPTCHA verification', 'ht-contactform') });
        }
    }
};

/**
 * AJAX form submission module
 */
const HTFormAjaxSubmitter = {
    /**
     * Submit form via AJAX
     * @param {HTMLFormElement} form - Form element
     * @param {string|null} recaptchaToken - reCAPTCHA token
     */
    submit(form, recaptchaToken) {
        if (!HTFormValidator.validateForm(form)) {
            HTFormValidator.scrollToFirstError(form);
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton?.innerHTML || '';
        
        if (submitButton) {
            HTFormButtonState.setLoading(submitButton, originalText);
        }

        const formData = this._buildFormData(form);
        
        if (form.id) {
            formData.append('form_id', form.id);
        }

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
        .then(response => this._handleSuccess(form, response.data))
        .catch(error => this._handleError(form, error))
        .finally(() => {
            if (submitButton) {
                HTFormButtonState.restore(submitButton, originalText);
            }
        });
    },

    // Private methods
    _buildFormData(form) {
        const formData = new FormData();
        const inputs = form.querySelectorAll('[name]');

        inputs.forEach(input => {
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    for (let i = 0; i < input.files.length; i++) {
                        formData.append(input.name, input.files[i]);
                    }
                }
            } else if (input.type === 'checkbox' || input.type === 'radio') {
                if (input.checked) {
                    formData.append(input.name, input.value);
                }
            } else if (input.tagName === 'SELECT' && input.multiple) {
                Array.from(input.selectedOptions).forEach(option => {
                    formData.append(`${input.name}[]`, option.value);
                });
            } else {
                formData.append(input.name, input.value);
            }
        });

        return formData;
    },

    _handleSuccess(form, data) {
        const confirmation = data.confirmation;
        if (!confirmation) return;

        switch (confirmation.type) {
            case 'message':
                HTFormMessageHandler.showSuccess(form, confirmation.message);
                break;
            case 'redirect':
            case 'page':
                const url = confirmation.redirect || confirmation.page;
                if (confirmation.newTab) {
                    window.open(url, '_blank');
                } else {
                    window.location.href = url;
                }
                break;
        }
    },

    _handleError(form, error) {
        let errorMessage = 'Form submission failed. Please try again.';
        
        if (error.response?.data) {
            if (error.response.data.message) {
                errorMessage = error.response.data.message;
            } else if (error.response.data.code === 'submission_too_quick') {
                errorMessage = __('Please wait a moment before submitting the form.', 'ht-contactform');
            }
        }
        
        HTFormMessageHandler.showError(form, errorMessage);
    }
};

/**
 * Conditional logic module
 */
const HTFormConditionalLogic = {
    /**
     * Check conditional logic for all fields
     * @param {HTMLFormElement} form - Form element
     */
    check(form) {
        form.querySelectorAll('.ht-form-elem[data-condition-match][data-condition-logic]').forEach(field => {
            const match = field.getAttribute('data-condition-match');
            const logic = field.getAttribute('data-condition-logic');
            
            try {
                const conditions = JSON.parse(logic);
                let isValid = false;
                
                if (match === 'any') {
                    isValid = conditions?.some(condition => this._evaluateCondition(condition, form));
                } else if (match === 'all') {
                    isValid = conditions?.every(condition => this._evaluateCondition(condition, form));
                }
                
                field.style.display = isValid ? 'block' : 'none';
            } catch (error) {
                console.error('Error parsing conditional logic:', error);
            }
        });
    },

    // Private methods
    _evaluateCondition(condition, form) {
        const { field, operator, value } = condition;
        const fieldElement = form.querySelector(`[name="${field}"]`);
        
        if (!fieldElement) return false;
        
        const fieldValue = fieldElement.value;
        
        const operators = {
            'is': () => fieldValue === value,
            '==': () => fieldValue === value,
            'is_not': () => fieldValue !== value,
            '!=': () => fieldValue !== value,
            'contains': () => fieldValue.includes(value),
            'does_not_contain': () => !fieldValue.includes(value),
            'doNotContains': () => !fieldValue.includes(value),
            'greater_than': () => +fieldValue > +value,
            '>': () => +fieldValue > +value,
            'less_than': () => +fieldValue < +value,
            '<': () => +fieldValue < +value,
            '>=': () => +fieldValue >= +value,
            '<=': () => +fieldValue <= +value,
            'startsWith': () => fieldValue.startsWith(value),
            'endsWith': () => fieldValue.endsWith(value),
            'test_regex': () => {
                try {
                    return new RegExp(value).test(fieldValue);
                } catch (e) {
                    console.error('Invalid regex pattern:', value);
                    return false;
                }
            }
        };
        
        return operators[operator] ? operators[operator]() : false;
    }
};

/**
 * Field components initialization
 */
const HTFormFieldComponents = {
    /**
     * Initialize all field components
     */
    initAll() {
        this.initRangeSliders();
        this.initInputMasks();
        this.initTelFields();
        this.initCountryFields();
        this.initDateTimeFields();
        this.initSelectFields();
        this.initFileUploads();
    },

    initRangeSliders() {
        document.querySelectorAll('.ht-form-elem-range').forEach(range => {
            range.addEventListener('input', () => {
                const amountEl = range.nextElementSibling?.querySelector('.ht-form-elem-range-amount');
                if (amountEl) {
                    amountEl.textContent = range.value;
                }
            });
        });
    },

    initInputMasks() {
        document.querySelectorAll('.ht-form-elem-input-mask[data-mask]').forEach(input => {
            const maskFormat = input.getAttribute('data-mask');
            if (!maskFormat) return;

            const maskOptions = this._getMaskOptions(maskFormat);
            const im = new Inputmask(maskOptions);
            im.mask(input);
        });
    },

    initTelFields() {
        document.querySelectorAll('.ht-form-elem-input-tel').forEach(input => {
            const config = this._getTelConfig(input);
            window.intlTelInput(input, config);
            this._setTelFlags(input);
        });
    },

    initCountryFields() {
        document.querySelectorAll('.ht-form-elem-input-country').forEach(input => {
            const config = this._getCountryConfig(input);
            jQuery(input).countrySelect(config);
        });
    },

    initDateTimeFields() {
        document.querySelectorAll('.ht-form-elem-datetime').forEach(input => {
            const config = this._getDateTimeConfig(input);
            flatpickr(input, config);
        });
    },

    initSelectFields() {
        document.querySelectorAll('[data-ht-select]').forEach(select => {
            const config = this._getSelectConfig(select);
            new Choices(select, config);
        });
    },

    initFileUploads() {
        document.querySelectorAll('.ht-form-elem-file-upload, .ht-form-elem-image-upload').forEach(fileUpload => {
            this._initFilePond(fileUpload);
        });
    },

    // Private helper methods
    _getMaskOptions(maskFormat) {
        const formats = {
            'MM/DD/YYYY': { alias: 'datetime', inputFormat: 'MM/DD/YYYY' },
            'HH:MM': { alias: 'datetime', inputFormat: 'HH:mm', placeholder: 'HH:MM' },
            '9999 9999 9999 9999': { mask: '9999 9999 9999 9999' },
            '$999.99': { alias: 'numeric', groupSeparator: '', digits: 2, digitsOptional: false, prefix: '$', rightAlign: false, allowMinus: false },
            '(999) 999-9999': { mask: '(999) 999-9999' },
            '999-99-9999': { mask: '999-99-9999' },
            '99999-9999': { mask: '99999-9999' }
        };
        
        return formats[maskFormat] || { mask: maskFormat };
    },

    _getTelConfig(input) {
        return {
            loadUtils: () => import(`${ht_form.plugin_url}assets/lib/intl-tel-input/utils.min.js`),
            initialCountry: input.getAttribute('data-initial-country'),
            excludeCountries: input.getAttribute('data-exclude-countries')?.split(',') || [],
            onlyCountries: input.getAttribute('data-only-countries')?.split(',') || [],
            customPlaceholder: (selectedCountryPlaceholder) => "e.g. " + selectedCountryPlaceholder
        };
    },

    _setTelFlags(input) {
        const iti = input.closest('.ht-form-elem-content')?.querySelector('.iti');
        if (iti) {
            iti.style.cssText = `
                --iti-path-flags-1x: url(${ht_form.plugin_url}assets/images/intl-tel-input/flags.webp);
                --iti-path-flags-2x: url(${ht_form.plugin_url}assets/images/intl-tel-input/flags@2x.webp);
                --iti-path-globe-1x: url(${ht_form.plugin_url}assets/images/intl-tel-input/globe.webp);
                --iti-path-globe-2x: url(${ht_form.plugin_url}assets/images/intl-tel-input/globe@2x.webp);
            `;
        }
    },

    _getCountryConfig(input) {
        return {
            defaultCountry: input.getAttribute('data-initial-country'),
            excludeCountries: input.getAttribute('data-exclude-countries')?.split(',') || [],
            onlyCountries: input.getAttribute('data-only-countries')?.split(',') || [],
            preferredCountries: [],
            responsiveDropdown: true
        };
    },

    _getDateTimeConfig(input) {
        const format = input.getAttribute('data-format');
        const range = input.getAttribute('data-range') === '1';
        const multiple = input.getAttribute('data-multiple') === '1';
        const enableTime = format?.includes('H') || format?.includes('h');
        const noDate = !format?.includes('Y');

        let mode = 'single';
        if (!enableTime) {
            if (range) mode = 'range';
            if (multiple) mode = 'multiple';
        }

        return {
            enableTime,
            noCalendar: noDate,
            dateFormat: format,
            mode,
            time_24hr: enableTime && format?.includes('H')
        };
    },

    _getSelectConfig(select) {
        return {
            searchEnabled: select.getAttribute('data-searchable') === '1',
            itemSelectText: '',
            maxItemCount: parseInt(select.getAttribute('data-maxselect')) || -1,
            removeItemButton: true,
            placeholder: true,
            placeholderValue: '',
            shouldSort: false
        };
    },

    _initFilePond(fileUpload) {
        const input = fileUpload.querySelector('input[type="file"]');
        const config = this._getFilePondConfig(input);
        
        FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateSize, FilePondPluginFileValidateType);
        
        const pond = FilePond.create(input, config);
        fileUpload.filepond = pond;
    },

    _getFilePondConfig(input) {
        const maxFileCount = parseInt(input.getAttribute('data-max-files'), 10) || null;
        const maxFileSize = parseInt(input.getAttribute('data-max-file-size'), 10) || 10;
        
        return {
            allowMultiple: maxFileCount > 1,
            maxFiles: maxFileCount,
            maxFileSize: maxFileSize * 1024 * 1024,
            fileValidateTypeLabelExpectedTypes: 'Expects {allTypes}',
            acceptedFileTypes: input.accept ? input.accept.split(',') : null,
            fileSizeBase: 1024,
            server: this._getFilePondServerConfig(),
            labelIdle: this._getFilePondLabel(maxFileCount, maxFileSize),
            credits: false
        };
    },

    _getFilePondServerConfig() {
        return {
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                const formData = new FormData();
                formData.append('action', 'ht_form_temp_file_upload');
                formData.append('_wpnonce', ht_form.nonce);
                formData.append('ht_form_file', file);

                const source = axios.CancelToken.source();

                axios.post(ht_form.ajaxurl, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    cancelToken: source.token,
                    onUploadProgress: (e) => progress(e.lengthComputable, e.loaded, e.total)
                })
                .then(response => {
                    const data = response.data;
                    if (data.success) {
                        load(data.data.file_id);
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
            }
        };
    },

    _getFilePondLabel(maxFileCount, maxFileSize) {
        let label = `<span class="filepond--label-action">Browse</span> or drag and drop your files.`;
        
        if (maxFileCount && maxFileCount > 1 && maxFileSize) {
            label += `<br/> <span class="filepond-extra-info">Max files: ${maxFileCount} and Max size: ${maxFileSize}MB</span>`;
        } else if (maxFileCount && maxFileCount > 1) {
            label += `<br/> <span class="filepond-extra-info">Max files: ${maxFileCount}</span>`;
        } else if (maxFileSize) {
            label += `<br/> <span class="filepond-extra-info">Max size: ${maxFileSize}MB</span>`;
        }
        
        return label;
    }
};

/**
 * Main form validation controller
 */
const HTForm = {
    /**
     * Initialize form validation system
     */
    init() {
        const forms = document.querySelectorAll(HTFORM_CONFIG.FORM_SELECTOR);
        
        forms.forEach(form => {
            HTFormEventHandlers.setupFieldListeners(form);
            HTFormEventHandlers.setupSubmitListener(form);
            HTFormConditionalLogic.check(form);
            HTFormMessageHandler.autoHideMessages(form);
        });
    }
};

/**
 * Initialize everything when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    HTFormFieldComponents.initAll();
    HTForm.init();
});