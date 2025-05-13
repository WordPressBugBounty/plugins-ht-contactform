<?php
/**
 * Frontend Form Fields Handler
 */

namespace HTContactFormAdmin\Includes\UI;

// If this file is accessed directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fields class
 * Handles field rendering functionality for displaying forms on the frontend
 */
class Fields {
    /**
     * Singleton instance
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Form icons
     *
     * @var array
     */
    protected $icons = [
        'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    ];

    /**
     * Global settings
     *
     * @var array
     */
    protected $global_settings;

    /**
     * Get instance of this class
     *
     * @return object
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->global_settings = get_option('ht_form_global_settings', []);
    }

    /**
     * Render individual field
     *
     * @param array $classes Field classes
     * @param string $field_type Field type
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function render_field($classes, $field_type, $field_id, $settings) {
        $help_message_pos = $this->global_settings['layout']['help_message_placement'] ?? 'below_input_element';
        if (!empty($settings['help_message_position'])) {
            $help_message_pos = sanitize_text_field($settings['help_message_position']);
        }
        $error_message_placement = $this->global_settings['layout']['error_message_placement'] ?? 'below_input_element';
        return sprintf(
            '<div data-id="%1$s" class="%2$s">
                <div class="ht-form-elem-inner">
                    <div class="ht-form-elem-head">
                        %3$s
                        %4$s
                        %5$s
                    </div>
                    <div class="ht-form-elem-content">
                        %6$s
                        %7$s
                        %8$s
                    </div>
                </div>
                %9$s
                %10$s
            </div>',
            esc_attr($field_id),
            implode(' ', array_filter($classes)),
            !empty($settings['label']) ? $this->field_label($field_id, $settings) : '',
            $help_message_pos === 'next_to_label' ? $this->field_message_tooltip($settings) : '',
            $error_message_placement === 'below_label'? '<span class="ht-form-elem-error"></span>' :'',
            $this->field_prefix($settings),
            method_exists($this, "field_$field_type") ? call_user_func([$this, "field_$field_type"], $field_id, $settings) : '',
            $this->field_suffix($settings),
            $help_message_pos === 'below_input_element' ? $this->field_message($settings) : '',
            $error_message_placement === 'below_input_element'? '<span class="ht-form-elem-error"></span>' :'',
        );
    }

    /**
     * Render field label
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_label($field_id, $settings) {
        $classes = ['ht-form-elem-label'];
        if(!empty($settings['required'])) {
            $classes[] = 'ht-form-elem-is-required';
        }
        if(!empty($settings['hide_label'])) {
            $classes[] = 'ht-form-elem-label-hidden';
        }
        return sprintf(
            '<label for="%1$s" class="' . implode(' ', $classes) . '" aria-label="%2$s">%3$s</label>',
            esc_attr($field_id),    
            esc_attr($settings['label']),
            esc_html($settings['label'])
        );
    }
    
    /**
     * Render field prefix
     *
     * @param array $settings Field settings
     * @return string
     */
    public function field_prefix($settings) {
        if(empty($settings['prefix_label'])) {
            return '';
        }
        $classes = ['ht-form-elem-prefix'];
        return sprintf(
            '<span class="' . implode(' ', $classes) . '">%s</span>',
            esc_html($settings['prefix_label'])
        );
    }
    
    /**
     * Render field suffix
     *
     * @param array $settings Field settings
     * @return string
     */
    public function field_suffix($settings) {
        if(empty($settings['suffix_label'])) {
            return '';
        }
        $classes = ['ht-form-elem-suffix'];
        return sprintf(
            '<span class="' . implode(' ', $classes) . '">%s</span>',
            esc_html($settings['suffix_label'])
        );
    }

    /**
     * Render field help message
     *
     * @param array $settings Field settings
     * @return string
     */
    public function field_message($settings) {
        if(empty($settings['help_message'])) {
            return '';
        }
        $classes = ['ht-form-elem-help'];
        return sprintf(
            '<p class="' . implode(' ', $classes) . '">%s</p>',
            esc_html($settings['help_message'])
        );
    }

    /**
     * Render field tooltip help message
     *
     * @param array $settings Field settings
     * @return string
     */
    public function field_message_tooltip($settings) {
        if(empty($settings['help_message'])) {
            return '';
        }
        $classes = ['ht-form-elem-help-tooltip'];
        return sprintf(
            '<span class="' . implode(' ', $classes) . '">%s<span class="ht-form-elem-help-tooltip-text">%s</span></span>',
            $this->icons['info'],
            esc_html($settings['help_message'])
        );
    }
    
    /**
     * Render name field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_name($field_id, $settings) {
        // Create field wrapper
        $wrapper_classes = [
            'ht-form-elem',
            'ht-form-elem-input-field',
            !empty($settings['field_size']) ? 'ht-form-elem-' . sanitize_html_class($settings['field_size']) : 'medium',
        ];

        if (!empty($settings['field_class'])) {
            $wrapper_classes[] = sanitize_html_class($settings['field_class']);
        }

        if(empty($settings['label_position']) && empty($this->global_settings['layout']['label_position'])) {
            $wrapper_classes[] = 'ht-form-elem-label-top';
        } else if(empty($settings['label_position']) && !empty($this->global_settings['layout']['label_position'])) {
            $wrapper_classes[] = 'ht-form-elem-label-' . sanitize_html_class($this->global_settings['layout']['label_position']);
        } else if(!empty($settings['label_position'])) {
            $wrapper_classes[] = 'ht-form-elem-label-' . sanitize_html_class($settings['label_position']);
        }

        $names = [];

        if($settings['name_format'] === 'simple') {
            $settings['label'] = $settings['names']['simple']['label'];
            $settings['placeholder'] = $settings['names']['simple']['placeholder'];
            $settings['default_value'] = $settings['names']['simple']['value'];
            $settings['help_message'] = $settings['names']['simple']['message'];
            $settings['required'] = $settings['names']['simple']['required'];
            $settings['required_message'] = $settings['names']['simple']['required_message'];
            $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_simple", $settings);
        }

        if($settings['name_format'] === 'first_last' || $settings['name_format'] === 'first_middle_last') {

            $first['label'] = $settings['names']['first_name']['label'];
            $first['placeholder'] = $settings['names']['first_name']['placeholder'];
            $first['default_value'] = $settings['names']['first_name']['value'];
            $first['help_message'] = $settings['names']['first_name']['message'];
            $first['required'] = $settings['names']['first_name']['required'];
            $first['required_message'] = $settings['names']['first_name']['required_message'];
            $first['name_attribute'] = $settings['name_attribute'] . '[first_name]';
            $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_first_name", array_merge($settings, $first));

            if($settings['name_format'] === 'first_middle_last') {
                $middle['label'] = $settings['names']['middle_name']['label'];
                $middle['placeholder'] = $settings['names']['middle_name']['placeholder'];
                $middle['default_value'] = $settings['names']['middle_name']['value'];
                $middle['help_message'] = $settings['names']['middle_name']['message'];
                $middle['required'] = $settings['names']['middle_name']['required'];
                $middle['required_message'] = $settings['names']['middle_name']['required_message'];
                $middle['name_attribute'] = $settings['name_attribute'] . '[middle_name]';
                $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_middle_name", array_merge($settings, $middle));
            }

            $last['label'] = $settings['names']['last_name']['label'];
            $last['placeholder'] = $settings['names']['last_name']['placeholder'];
            $last['default_value'] = $settings['names']['last_name']['value'];
            $last['help_message'] = $settings['names']['last_name']['message'];
            $last['required'] = $settings['names']['last_name']['required'];
            $last['required_message'] = $settings['names']['last_name']['required_message'];
            $last['name_attribute'] = $settings['name_attribute'] . '[last_name]';
            $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_last_name", array_merge($settings, $last));

        }

        return '<div data-id="' . esc_attr($field_id) . '" class="ht-form-elem-group ht-form-elem-name">' .
            implode('', $names)
        . '</div>';
    }   

    /**
     * Render email field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_email($field_id, $settings) {
        $attributes = [
            'type' => 'email',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'data-email-validation' => $settings['email_validation'],
            'data-email-validation-message' => !empty($settings['email_validation']) && !empty($settings['email_validation_message']) ? $settings['email_validation_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'data-email-unique' => !empty($settings['email_unique']) ? true : false,
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<input %s/>',
            $attributes_string
        );
    }

    /**
     * Render input field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_input($field_id, $settings) {
        $attributes = [
            'type' => 'text',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'maxlength' => !empty($settings['max_length']) ? $settings['max_length'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<input %s/>',
            $attributes_string
        );
    }

    /**
     * Render input mask field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_mask_input($field_id, $settings) {
        $attributes = [
            'type' => 'text',
            'id' => $field_id,
            'class' => 'ht-form-elem-input ht-form-elem-input-mask',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'data-mask' => !empty($settings['mask']) ? $settings['mask'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<input %s/>',
            $attributes_string
        );
    }

    /**
     * Render number field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_number($field_id, $settings) {
        $attributes = [
            'type' => 'number',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'min' => isset($settings['min']) ? (int) $settings['min'] : '',
            'max' => isset($settings['max']) ? (int) $settings['max'] : '',
            'step' => isset($settings['step']) ? (int) $settings['step'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value !== false && $value !== null && $value !== '') {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<input %s/>',
            $attributes_string
        );
    }

    /**
     * Render textarea field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_textarea($field_id, $settings) {
        $attributes = [
            'id' => $field_id,
            'class' => 'ht-form-elem-textarea',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'maxlength' => !empty($settings['max_length']) ? $settings['max_length'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<textarea %1$s>%2$s</textarea>',
            $attributes_string,
            !empty($settings['default_value']) ? $settings['default_value'] : '',
        );
    }

    /**
     * Render select/dropdown field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_dropdown($field_id, $settings) {
        $attributes = [
            'id' => $field_id,
            'class' => 'ht-form-elem-select',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'data-searchable' => !empty($settings['searchable']) ? $settings['searchable'] : false,
            'data-placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'data-ht-select' => true,
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        $options = '';
        if(!empty($settings['placeholder'])) {
            $options .= sprintf(
                '<option value="" disabled selected>%s</option>',
                esc_html($settings['placeholder'])
            );
        }
        if(!empty($settings['options'])) {
            foreach ($settings['options'] as $option) {
                $options .= sprintf(
                    '<option value="%s" %s>%s</option>', 
                    esc_attr($option['value']), 
                    $option['selected'] ? 'selected' : '', 
                    esc_html($option['label'])
                );
            }
        }
        return sprintf(
            '<select %s>%s</select>',
            $attributes_string,
            $options
        );
    }

    /**
     * Render select/dropdown field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_multiple_choices($field_id, $settings) {
        $attributes = [
            'id' => $field_id,
            'class' => 'ht-form-elem-select',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'data-searchable' => !empty($settings['searchable']) ? $settings['searchable'] : false,
            'data-placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'data-maxselect' => !empty($settings['max_selection']) ? $settings['max_selection'] : '',
            'multiple' => true,
            'data-ht-select' => true,
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        $options = '';
        if(!empty($settings['options'])) {
            foreach ($settings['options'] as $option) {
                $options .= sprintf(
                    '<option value="%s" %s>%s</option>', 
                    esc_attr($option['value']), 
                    $option['selected'] ? 'selected' : '', 
                    esc_html($option['label'])
                );
            }
        }
        return sprintf(
            '<select %s>%s</select>',
            $attributes_string,
            $options
        );
    }

    /**
     * Render checkboxes field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_checkboxes($field_id, $settings) {
        $attributes = [
            'class' => implode(' ', array_filter([
                'ht-form-elem-checkboxes',
                $settings['layout'] ? "ht-form-elem-checkboxes-" . esc_attr($settings['layout']) : false
            ])),
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        $options = '';
        if(!empty($settings['options'])) {
            foreach ($settings['options'] as $option) {
                $item_attributes = [
                    'type' => 'checkbox',
                    'id' => $field_id .'_'. $option['value'],
                    'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[]' : '',
                    'value' => $option['value'],
                    'checked' => $option['selected'] ? 'checked' : '',
                    'required' => $settings['required'] ? true : false,
                    'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
                ];
                // Build attribute string
                $item_attributes_string = '';
                foreach ($item_attributes as $key => $value) {
                    if($value) {
                        $item_attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
                    }
                }
                $options .= sprintf(
                    '<div class="ht-form-elem-checkbox"><input %s/><label for="%s">%s</label></div>' . PHP_EOL,
                    $item_attributes_string,
                    esc_attr($field_id .'_'. $option['value']),
                    esc_html($option['label'])
                );
            }
        }
        return sprintf(
            '<div %s>%s</div>',
            $attributes_string,
            $options
        );
    }

    /**
     * Render slider field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_slider($field_id, $settings) {
        $attributes = [
            'type' => 'range',
            'id' => $field_id,
            'class' => 'ht-form-elem-range',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : 0,
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'min' => !empty($settings['min']) ? $settings['min'] : 0,
            'max' => !empty($settings['max']) ? $settings['max'] : 100,
            'step' => !empty($settings['step']) ? $settings['step'] : 1,
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<div class="ht-form-elem-content-range"><input %1$s/>%2$s</div>',
            $attributes_string,
            !empty($settings['value_display']) ? '<p class="ht-form-elem-range-value">' . str_replace('{value}',    '<span class="ht-form-elem-range-amount">'.esc_attr($settings['default_value']).'</span>', esc_html($settings['value_display'])) . '</p>' : ''
        );
    }

    /**
     * Render GDPR field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_gdpr($field_id, $settings) {
        $desc = !empty($settings['description']) ? $settings['description'] : '';
        $label_attributes = [
            'for' => $field_id,
            'class' => 'ht-form-elem-gdpr',
            'aria-label' => $desc,
        ];
        // Build attribute string
        $label_attributes_string = '';
        foreach ($label_attributes as $key => $value) {
            if($value) {
                $label_attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        $attributes = [
            'type' => 'checkbox',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => 'yes',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'required' => true,
            'data-required-message' => !empty($settings['required_message']) ? $settings['required_message'] : '',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<label %s>
                <input %s/>
                <span class="ht-form-elem-gdpr-check"></span>
                <span class="ht-form-elem-gdpr-desc">%s</span>
            </label>',
            $label_attributes_string,
            $attributes_string,
            $desc
        );
    }

    /**
     * Render submit button
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_submit($field_id, $settings) {
        $button_style = !empty($settings['style']) ? sanitize_html_class($settings['style']) : '';
        $attributes = [
            'type' => 'submit',
            'id' => $field_id,
            'class' => implode(' ', array_filter([
                'ht-form-elem-button-submit',
                $button_style ? "ht-form-elem-button-submit-" . esc_attr($button_style) : false
            ])),
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        return sprintf(
            '<button %s>%s</button>',
            $attributes_string,
            !empty($settings['default_value']) ? $settings['default_value'] : __('Submit', 'ht-contactform')
        );
    }

    /**
     * Render reCAPTCHA field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_recaptcha($field_id, $settings) {
        // Get global settings for reCAPTCHA
        $recaptcha_version = isset($this->global_settings['captcha']['recaptcha_version']) ? 
                            $this->global_settings['captcha']['recaptcha_version'] : 'reCAPTCHAv2';
        $site_key = isset($this->global_settings['captcha']['recaptcha_site_key']) ? 
                    $this->global_settings['captcha']['recaptcha_site_key'] : '';
        
        if (empty($site_key)) {
            return '<div class="ht-form-recaptcha-error">' . esc_html__('reCAPTCHA site key is not configured.', 'ht-contactform') . '</div>';
        }

        $attributes = [
            'type' => 'hidden',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : 'g-recaptcha-response',
            'id' => $field_id,
            'value' => '',
            'required' => true,
        ];
        
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        
        $output = '<input ' . $attributes_string . '/>';
        
        if ($recaptcha_version === 'reCAPTCHAv2') {
            $output .= sprintf(
                '<div class="g-recaptcha" data-sitekey="%s"></div>',
                esc_attr($site_key)
            );
        }
        
        return $output;
    }
}