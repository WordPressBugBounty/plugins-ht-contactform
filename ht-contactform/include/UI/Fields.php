<?php
/**
 * Frontend Form Fields Handler
 */

namespace HTContactForm\UI;

use HTContactFormAdmin\Includes\Services\Helper;

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
    protected $helper;

    protected $file_types = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/jpg',
        ],
        'audio' => [
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
        'video' => [
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
            'video/mpg',
            'video/mpeg',
            'video/mpe',
        ],
        'pdf' => [
            'application/pdf',
        ],
        'doc' => [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/plain',
        ],
        'zip' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/rar',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-gzip',
        ],
        'exe' => [
            'application/exe',
            'application/x-exe',
        ],
        'csv' => [
            'text/csv',
        ],
    ];

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
        $this->helper = Helper::get_instance();
    }

    /**
     * Render individual field
     *
     * @param array $classes Field classes
     * @param string $field_type Field type
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @param bool $allow_condition Allow condition attribute
     * @return string
     */
    public function render_field($classes, $field_type, $field_id, $settings, $allow_condition = true) {
        $help_message_pos = $this->global_settings['layout']['help_message_placement'] ?? 'below_input_element';
        if (!empty($settings['help_message_position'])) {
            $help_message_pos = sanitize_text_field($settings['help_message_position']);
        }
        $error_message_placement = $this->global_settings['layout']['error_message_placement'] ?? 'below_input_element';

        $allow_conditional_match = null;
        $conditional_logic = null;
        if(!empty($settings['enable_condition']) && $allow_condition) {
            $conditional_match = $settings['conditional_match'];
            $conditional_logic = json_encode($settings['conditional_logic']);
        }
        return sprintf(
            '<div data-id="%1$s" class="%2$s" %3$s %4$s>
                <div class="ht-form-elem-inner">
                    <div class="ht-form-elem-head">
                        %5$s
                        %6$s
                        %7$s
                    </div>
                    <div class="ht-form-elem-content">
                        %8$s
                        %9$s
                        %10$s
                    </div>
                </div>
                %11$s
                %12$s
            </div>',
            esc_attr($field_id),
            implode(' ', array_filter($classes)),
            !empty($settings['enable_condition']) && $allow_condition ? "data-condition-match=" . esc_attr($conditional_match) : '',
            !empty($settings['enable_condition']) && $allow_condition ? "data-condition-logic=" . esc_attr($conditional_logic) : '',
            !empty($settings['label']) ? $this->field_label($field_id, $settings) : '',
            $help_message_pos === 'next_to_label' ? $this->field_message_tooltip($settings) : '',
            $error_message_placement === 'below_label'? '<span class="ht-form-elem-error"></span>' :'',
            $this->field_prefix($settings),
            method_exists($this, "field_$field_type") ? call_user_func([$this, "field_$field_type"], $field_id, $settings) : '',
            $this->field_suffix($settings),
            $help_message_pos === 'below_input_element' ? $this->field_message($settings) : '',
            $error_message_placement === 'below_input_element' ? '<span class="ht-form-elem-error"></span>' :'',
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
            return '';
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
            $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_simple", $settings, false);
        }

        if($settings['name_format'] === 'first_last' || $settings['name_format'] === 'first_middle_last') {

            $first['label'] = $settings['names']['first_name']['label'];
            $first['placeholder'] = $settings['names']['first_name']['placeholder'];
            $first['default_value'] = $settings['names']['first_name']['value'];
            $first['help_message'] = $settings['names']['first_name']['message'];
            $first['required'] = $settings['names']['first_name']['required'];
            $first['required_message'] = $settings['names']['first_name']['required_message'];
            $first['name_attribute'] = $settings['name_attribute'] . '[first_name]';
            $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_first_name", array_merge($settings, $first), false);

            if($settings['name_format'] === 'first_middle_last') {
                $middle['label'] = $settings['names']['middle_name']['label'];
                $middle['placeholder'] = $settings['names']['middle_name']['placeholder'];
                $middle['default_value'] = $settings['names']['middle_name']['value'];
                $middle['help_message'] = $settings['names']['middle_name']['message'];
                $middle['required'] = $settings['names']['middle_name']['required'];
                $middle['required_message'] = $settings['names']['middle_name']['required_message'];
                $middle['name_attribute'] = $settings['name_attribute'] . '[middle_name]';
                $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_middle_name", array_merge($settings, $middle), false);
            }

            $last['label'] = $settings['names']['last_name']['label'];
            $last['placeholder'] = $settings['names']['last_name']['placeholder'];
            $last['default_value'] = $settings['names']['last_name']['value'];
            $last['help_message'] = $settings['names']['last_name']['message'];
            $last['required'] = $settings['names']['last_name']['required'];
            $last['required_message'] = $settings['names']['last_name']['required_message'];
            $last['name_attribute'] = $settings['name_attribute'] . '[last_name]';
            $names[] = $this->render_field($wrapper_classes, 'input', "{$field_id}_last_name", array_merge($settings, $last), false);

        }

        $conditional_match = null;
        $conditional_logic = null;
        if(!empty($settings['enable_condition'])) {
            $conditional_match = $settings['conditional_match'];
            $conditional_logic = json_encode($settings['conditional_logic']);
        }

        return sprintf('<div data-id="%1$s" class="ht-form-elem ht-form-elem-group ht-form-elem-name" %2$s %3$s>%4$s</div>',
            esc_attr($field_id),
            !empty($settings['enable_condition']) ? "data-condition-match=" . esc_attr($conditional_match) : '',
            !empty($settings['enable_condition']) ? "data-condition-logic=" . esc_attr($conditional_logic) : '',
            implode('', $names)
        );
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
     * Render website url field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_url($field_id, $settings) {
        $attributes = [
            'type' => 'url',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'data-validate' => !empty($settings['validate_url']) ? true : false,
            'data-validation-message' =>  !empty($settings['validate_url']) && !empty($settings['validate_url_message']) ? $settings['validate_url_message'] : '',
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
     * Render hidden field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_hidden($field_id, $settings) {
        $attributes = [
            'type' => 'hidden',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'readonly' => true,
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
     * Render Custom HTML field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_custom_html($field_id, $settings) {
        $content = !empty($settings['html']) ? $settings['html'] : '';
        $content = $this->helper->filter_vars($content);
        if(str_contains($content, '{input.')) {
            // Match all {input.something} patterns
            preg_match_all('/{input\.[^}]+}/', $content, $matches);
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    // Extract the field name from the tag (e.g., {input.email} -> email)
                    $field_name = str_replace(['{input.', '}'], '', $match);
                    // Convert dot notation to array notation (e.g., name.first_name.nested -> name[first_name][nested])
                    if (strpos($field_name, '.') !== false) {
                        $parts = explode('.', $field_name);
                        $base = array_shift($parts);
                        $field_name = $base;
                        foreach ($parts as $part) {
                            $field_name .= "[$part]";
                        }
                    }
                    
                    // Create a span with data attribute to reference this field
                    $span = '<span class="ht-form-elem-dynamic" data-ref="' . esc_attr($field_name) . '"></span>';
                    
                    // Replace the tag with the span
                    $content = str_replace($match, $span, $content);
                }
            }
        }
        return wp_kses_post("<div class=\"ht-form-elem-custom-html\">$content</div>");
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
     * Render password field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_password($field_id, $settings) {
        $attributes = [
            'type' => 'password',
            'id' => $field_id,
            'class' => 'ht-form-elem-input',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
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
     * Render date time field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_date_time($field_id, $settings) {
        $attributes = [
            'type' => 'text',
            'id' => $field_id,
            'class' => 'ht-form-elem-input ht-form-elem-datetime',
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
            'data-format' => !empty($settings['format']) ? $settings['format'] : '',
            'data-range' => !empty($settings['range']) ? true : false,
            'data-multiple' => !empty($settings['multiple']) ? true : false,
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
     * Render phone/tel field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_phone($field_id, $settings) {
        $validate = !empty($settings['validate']) ? true : false;
        $validate_message = !empty($settings['validate_message']) ? $settings['validate_message'] : '';
        $classes = [
            'ht-form-elem-input',
            $validate ? 'ht-form-elem-input-tel' : '',
        ];
        $initial_country = $validate && !empty($settings['default_country']) ? $settings['default_country'] : '';
        $exclude_countries = $validate && !empty($settings['country_list_type']) && $settings['country_list_type'] === 'exclude' ? implode(',', $settings['country_list']) : '';
        $only_countries = $validate && !empty($settings['country_list_type']) && $settings['country_list_type'] === 'include' ? implode(',', $settings['country_list']) : '';

        if(!empty($settings['auto_country_select'])) {
            $initial_country = $this->helper->get_geolocation_data($this->helper->get_ip())['country'];
        }
        $attributes = [
            'type' => 'tel',
            'id' => $field_id,
            'class' => implode(' ', array_filter($classes)),
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) && !$validate ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'data-validation' => $validate,
            'data-validation-message' => $validate_message,
            'data-initial-country' => $initial_country,
            'data-exclude-countries' => $exclude_countries,
            'data-only-countries' => $only_countries,
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
     * Render country field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_country($field_id, $settings) {
        $classes = [
            'ht-form-elem-input',
            'ht-form-elem-input-country',
        ];
        $initial_country = !empty($settings['default_country']) ? $settings['default_country'] : '';
        $exclude_countries = !empty($settings['country_list_type']) && $settings['country_list_type'] === 'exclude' ? implode(',', $settings['country_list']) : '';
        $only_countries = !empty($settings['country_list_type']) && $settings['country_list_type'] === 'include' ? implode(',', $settings['country_list']) : '';

        if(!empty($settings['auto_country_select'])) {
            $initial_country = $this->helper->get_geolocation_data($this->helper->get_ip())['country'];
        }
        $attributes = [
            'type' => 'text',
            'id' => $field_id,
            'class' => implode(' ', array_filter($classes)),
            'value' => !empty($settings['default_value']) ? $settings['default_value'] : '',
            'placeholder' => !empty($settings['placeholder']) ? $settings['placeholder'] : '',
            'required' => !empty($settings['required']) ? true : false,
            'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
            'data-initial-country' => strtolower($initial_country),
            'data-exclude-countries' => $exclude_countries,
            'data-only-countries' => $only_countries,
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
     * Render address field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_address($field_id, $settings) {
        $geo_data = $this->helper->get_geolocation_data($this->helper->get_ip());
        $attributes = [
            'id' => $field_id,
            'class' => 'ht-form-elem-group ht-form-elem-address',
        ];
        // Build attribute string
        $attributes_string = '';
        foreach ($attributes as $key => $value) {
            if($value) {
                $attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }
        
        // Create field wrapper
        $wrapper_classes = [
            'ht-form-elem',
            !empty($settings['field_size']) ? 'ht-form-elem-' . sanitize_html_class($settings['field_size']) : 'medium',
        ];

        $fields = [];

        if(!empty($settings['enable_address_line_1'])) {
            $wrapper_classes[] = 'ht-form-elem-input-field';
            $fields[] = $this->render_field(
                $wrapper_classes,
                'input',
                "{$field_id}_address_line_1",
                array_merge($settings, [
                    'label' => !empty($settings['address_line_1']['label']) ? $settings['address_line_1']['label'] : '',
                    'default_value' => !empty($settings['address_line_1']['value']) ? $settings['address_line_1']['value'] : '',
                    'placeholder' => !empty($settings['address_line_1']['placeholder']) ? $settings['address_line_1']['placeholder'] : '',
                    'required' => !empty($settings['address_line_1']['required']) ? true : false,
                    'data-required-message' => !empty($settings['address_line_1']['required_message']) ? $settings['address_line_1']['required_message'] : '',
                    'name_attribute' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[address_line_1]' : '',
                ]),
                false
            );
        }
        if(!empty($settings['enable_address_line_2'])) {
            $wrapper_classes[] = 'ht-form-elem-input-field';
            $fields[] = $this->render_field(
                $wrapper_classes,
                'input',
                "{$field_id}_address_line_2",
                array_merge($settings, [
                    'label' => !empty($settings['address_line_2']['label']) ? $settings['address_line_2']['label'] : '',
                    'default_value' => !empty($settings['address_line_2']['value']) ? $settings['address_line_2']['value'] : '',
                    'placeholder' => !empty($settings['address_line_2']['placeholder']) ? $settings['address_line_2']['placeholder'] : '',
                    'required' => !empty($settings['address_line_2']['required']) ? true : false,
                    'data-required-message' => !empty($settings['address_line_2']['required_message']) ? $settings['address_line_2']['required_message'] : '',
                    'name_attribute' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[address_line_2]' : '',
                ]),
                false
            );
        }
        if(!empty($settings['enable_city'])) {
            $wrapper_classes[] = 'ht-form-elem-input-field';
            $value = !empty($settings['city']['value']) ? $settings['city']['value'] : '';
            if(!empty($settings['city']['auto_fill'])) {
                $value = $geo_data['city'];
            }
            $fields[] = $this->render_field(
                $wrapper_classes,
                'input',
                "{$field_id}_city",
                array_merge($settings, [
                    'label' => !empty($settings['city']['label']) ? $settings['city']['label'] : '',
                    'default_value' => $value,
                    'placeholder' => !empty($settings['city']['placeholder']) ? $settings['city']['placeholder'] : '',
                    'required' => !empty($settings['city']['required']) ? true : false,
                    'data-required-message' => !empty($settings['city']['required_message']) ? $settings['city']['required_message'] : '',
                    'name_attribute' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[address_city]' : '',
                ]),
                false
            );
        }
        if(!empty($settings['enable_state'])) {
            $wrapper_classes[] = 'ht-form-elem-input-field';
            $value = !empty($settings['state']['value']) ? $settings['state']['value'] : '';
            if(!empty($settings['state']['auto_fill'])) {
                $value = $geo_data['region'];
            }
            $fields[] = $this->render_field(
                $wrapper_classes,
                'input',
                "{$field_id}_state",
                array_merge($settings, [
                    'label' => !empty($settings['state']['label']) ? $settings['state']['label'] : '',
                    'default_value' => $value,
                    'placeholder' => !empty($settings['state']['placeholder']) ? $settings['state']['placeholder'] : '',
                    'required' => !empty($settings['state']['required']) ? true : false,
                    'data-required-message' => !empty($settings['state']['required_message']) ? $settings['state']['required_message'] : '',
                    'name_attribute' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[address_state]' : '',
                ]),
                false
            );
        }
        if(!empty($settings['enable_zip'])) {
            $wrapper_classes[] = 'ht-form-elem-input-field';
            $value = !empty($settings['zip']['value']) ? $settings['zip']['value'] : '';
            if(!empty($settings['zip']['auto_fill'])) {
                $value = $geo_data['postal'];
            }
            $fields[] = $this->render_field(
                $wrapper_classes,
                'input',
                "{$field_id}_zip",
                array_merge($settings, [
                    'label' => !empty($settings['zip']['label']) ? $settings['zip']['label'] : '',
                    'default_value' => $value,
                    'placeholder' => !empty($settings['zip']['placeholder']) ? $settings['zip']['placeholder'] : '',
                    'required' => !empty($settings['zip']['required']) ? true : false,
                    'data-required-message' => !empty($settings['zip']['required_message']) ? $settings['zip']['required_message'] : '',
                    'name_attribute' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[address_zip]' : '',
                ]),
                false
            );
        }
        
        if(!empty($settings['enable_country'])) {
            $wrapper_classes[] = 'ht-form-elem-country-field';
            $value = !empty($settings['country']['value']) ? $settings['country']['value'] : '';

            $fields[] = $this->render_field(
                $wrapper_classes,
                'country',
                "{$field_id}_country",
                array_merge($settings, [
                    'label' => !empty($settings['country']['label']) ? $settings['country']['label'] : '',
                    'default_value' => $value,
                    'default_country' => !empty($settings['country']['default_country']) ? $settings['country']['default_country'] : '',
                    'auto_country_select' => !empty($settings['country']['auto_fill']) ? true : false,
                    'country_list_type' => !empty($settings['country']['country_list_type']) ? $settings['country']['country_list_type'] : '',
                    'country_list' => !empty($settings['country']['country_list']) ? $settings['country']['country_list'] : [],
                    'placeholder' => !empty($settings['country']['placeholder']) ? $settings['country']['placeholder'] : '',
                    'required' => !empty($settings['country']['required']) ? true : false,
                    'data-required-message' => !empty($settings['country']['required_message']) ? $settings['country']['required_message'] : '',
                    'name_attribute' => !empty($settings['name_attribute']) ? $settings['name_attribute'] . '[address_country]' : '',
                ]),
                false
            );
        }

        return sprintf(
            '<div %s>
                %s
            </div>',
            $attributes_string,
            implode('', $fields)
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
                    '<div class="ht-form-elem-checkbox">
                        <div class="ht-form-elem-checkbox-inner">
                            <input %s/>
                            <svg viewBox="0 0 10 7" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="ht-form-elem-checkbox-icon"><path d="M4 4.586L1.707 2.293A1 1 0 1 0 .293 3.707l3 3a.997.997 0 0 0 1.414 0l5-5A1 1 0 1 0 8.293.293L4 4.586z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                        </div>
                        <label for="%s">%s</label>
                    </div>' . PHP_EOL,
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
     * Render radio field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_radio($field_id, $settings) {
        $attributes = [
            'class' => implode(' ', array_filter([
                'ht-form-elem-radios',
                $settings['layout'] ? "ht-form-elem-radios-" . esc_attr($settings['layout']) : false
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
                    'type' => 'radio',
                    'id' => $field_id .'_'. $option['value'],
                    'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
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
                    '<div class="ht-form-elem-radio-item">
                        <div class="ht-form-elem-radio-inner">
                            <input %s/>
                            <span class="ht-form-elem-radio-icon"></span>
                        </div>
                        <label for="%s">%s</label>
                    </div>' . PHP_EOL,
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
     * Render file upload field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_file_upload($field_id, $settings) {
        $required = !empty($settings['required']) ? true : false;
        $required_message = !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '';
        $button_text = !empty($settings['button_text']) ? $settings['button_text'] : '';
        $interface = !empty($settings['upload_interface']) ? $settings['upload_interface'] : 'button';
        $max_file_size = !empty($settings['max_file_size']) ? $settings['max_file_size'] : '';
        $max_file_size_message = !empty($settings['max_file_size_message']) ? $settings['max_file_size_message'] : '';
        $max_file_count = !empty($settings['max_files']) ? $settings['max_files'] : '';
        $max_file_count_message = !empty($settings['max_files_message']) ? $settings['max_files_message'] : '';
        $allow_types = !empty($settings['allow_types']) ? $settings['allow_types'] : [];
        $allow_types_message = !empty($settings['allow_types_message']) ? $settings['allow_types_message'] : '';
        $upload_location = !empty($settings['upload_location']) ? $settings['upload_location'] : 'default';
        $name_attribute = !empty($settings['name_attribute']) ? $settings['name_attribute'] : '';

        $file_allows = array_map(function($type){
            return $this->file_types[$type];
        }, $allow_types);

        $file_allows = implode(',', array_merge(...$file_allows));

        return sprintf(
            '<label for="%1$s" class="ht-form-elem-file-upload ht-form-elem-file-upload-%2$s">
                <input
                    type="file"
                    id="%1$s"
                    name="%3$s[]"
                    multiple
                    accept="%4$s"
                    %5$s
                    data-required-message="%6$s"
                    data-max-file-size="%7$sMB"
                    data-max-file-size-message="%8$s"
                    data-max-files="%9$s"
                    data-max-files-message="%10$s"
                    data-allow-message="%11$s"
                    data-upload-location="%12$s"
                />
            </label>',
            esc_attr($field_id),
            esc_html($interface),
            esc_attr($name_attribute),
            esc_attr($file_allows),
            $required ? 'required' : '',
            esc_attr($required_message),
            esc_attr($max_file_size),
            esc_attr($max_file_size_message),
            esc_attr($max_file_count),
            esc_attr($max_file_count_message),
            esc_attr($allow_types_message),
            esc_attr($upload_location)
        );
    }

    /**
     * Render image upload field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_image_upload($field_id, $settings) {
        $required = !empty($settings['required']) ? true : false;
        $required_message = !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '';
        $button_text = !empty($settings['button_text']) ? $settings['button_text'] : '';
        $interface = !empty($settings['upload_interface']) ? $settings['upload_interface'] : 'button';
        $max_file_size = !empty($settings['max_file_size']) ? $settings['max_file_size'] : '';
        $max_file_size_message = !empty($settings['max_file_size_message']) ? $settings['max_file_size_message'] : '';
        $max_file_count = !empty($settings['max_files']) ? $settings['max_files'] : '';
        $max_file_count_message = !empty($settings['max_files_message']) ? $settings['max_files_message'] : '';
        $allow_types = !empty($settings['allow_types']) ? $settings['allow_types'] : [];
        $allow_types_message = !empty($settings['allow_types_message']) ? $settings['allow_types_message'] : '';
        $upload_location = !empty($settings['upload_location']) ? $settings['upload_location'] : 'default';
        $name_attribute = !empty($settings['name_attribute']) ? $settings['name_attribute'] : '';

        $file_allows = implode(',', $allow_types);

        return sprintf(
            '<label class="ht-form-elem-image-upload ht-form-elem-image-upload-%2$s">
                <input
                    type="file"
                    id="%1$s"
                    name="%3$s[]"
                    multiple
                    accept="%4$s"
                    %5$s
                    data-required-message="%6$s"
                    data-max-file-size="%7$sMB"
                    data-max-file-size-message="%8$s"
                    data-max-files="%9$s"
                    data-max-files-message="%10$s"
                    data-allow-message="%11$s"
                    data-upload-location="%12$s"
                />
            </label>',
            esc_attr($field_id),
            esc_html($interface),
            esc_attr($name_attribute),
            esc_attr($file_allows),
            $required ? 'required' : '',
            esc_attr($required_message),
            esc_attr($max_file_size),
            esc_attr($max_file_size_message),
            esc_attr($max_file_count),
            esc_attr($max_file_count_message),
            esc_attr($allow_types_message),
            esc_attr($upload_location)
        );
    }

    /**
     * Render shortcode field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_shortcode($field_id, $settings) {
        $shortcode = !empty($settings['shortcode']) ? $settings['shortcode'] : '';
        $shortcode = do_shortcode($shortcode);
        return $shortcode;
    }

    /**
     * Render ratings field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_ratings($field_id, $settings) {
        $attributes = [
            'class' => implode(' ', array_filter([
                'ht-form-elem-ratings',
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
            foreach ($settings['options'] as $index => $option) {
                $input_attributes = [
                    'type' => 'radio',
                    'id' => $field_id .'_'. $option['value'],
                    'name' => !empty($settings['name_attribute']) ? $settings['name_attribute'] : '',
                    'value' => $option['value'],
                    'checked' => $option['selected'] ? 'checked' : '',
                    'required' => $settings['required'] ? true : false,
                    'data-required-message' => !empty($settings['required']) && !empty($settings['required_message']) ? $settings['required_message'] : '',
                ];
                // Build attribute string
                $input_attributes_string = '';
                foreach ($input_attributes as $key => $value) {
                    if($value) {
                        $input_attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
                    }
                }
                
                // Find the selected option index
                $selected_index = -1;
                foreach ($settings['options'] as $i => $opt) {
                    if ($opt['selected']) {
                        $selected_index = $i;
                        break;
                    }
                }
                
                $label_attributes = [
                    'for' => $field_id .'_'. $option['value'],
                ];
                if($settings['show_text_on_hover']) {
                    $label_attributes['data-tooltip'] = $option['label'];
                }
                
                // Add active class to current and all previous options if any option is selected
                if ($selected_index >= 0 && $index <= $selected_index) {
                    $label_attributes['class'] = 'active';
                }
                
                // Build attribute string
                $label_attributes_string = '';
                foreach ($label_attributes as $key => $value) {
                    if($value) {
                        $label_attributes_string .= sprintf(' %s="%s"', $key, esc_attr($value));
                    }
                }
                $options .= sprintf(
                    '<label %s>
                        <input %s/>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-star"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8.243 7.34l-6.38 .925l-.113 .023a1 1 0 0 0 -.44 1.684l4.622 4.499l-1.09 6.355l-.013 .11a1 1 0 0 0 1.464 .944l5.706 -3l5.693 3l.1 .046a1 1 0 0 0 1.352 -1.1l-1.091 -6.355l4.624 -4.5l.078 -.085a1 1 0 0 0 -.633 -1.62l-6.38 -.926l-2.852 -5.78a1 1 0 0 0 -1.794 0l-2.853 5.78z" /></svg>
                    </label>' . PHP_EOL,
                    $label_attributes_string,
                    $input_attributes_string
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
                <div class="ht-form-elem-gdpr-inner">
                    <input %s/>
                    <svg viewBox="0 0 10 7" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="ht-form-elem-checkbox-icon"><path d="M4 4.586L1.707 2.293A1 1 0 1 0 .293 3.707l3 3a.997.997 0 0 0 1.414 0l5-5A1 1 0 1 0 8.293.293L4 4.586z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </div>
                <span class="ht-form-elem-gdpr-desc">%s</span>
            </label>',
            $label_attributes_string,
            $attributes_string,
            $desc
        );
    }

    /**
     * Render Terms and Conditions field
     *
     * @param string $field_id Field ID
     * @param array $settings Field settings
     * @return string
     */
    public function field_terms_conditions($field_id, $settings) {
        $content = !empty($settings['content']) ? $settings['content'] : '';
        $label_attributes = [
            'for' => $field_id,
            'class' => 'ht-form-elem-terms-conditions',
            'aria-label' => $content,
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
                <div class="ht-form-elem-terms-conditions-inner">
                    <input %s/>
                    <svg viewBox="0 0 10 7" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="ht-form-elem-checkbox-icon"><path d="M4 4.586L1.707 2.293A1 1 0 1 0 .293 3.707l3 3a.997.997 0 0 0 1.414 0l5-5A1 1 0 1 0 8.293.293L4 4.586z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </div>
                <span class="ht-form-elem-terms-conditions-desc">%s</span>
            </label>',
            $label_attributes_string,
            $attributes_string,
            wp_kses_post($content)
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