<?php

namespace HTContactFormAdmin\Includes\Api;

use HTContactFormAdmin\Includes\Models\Form as FormModel;
use HTContactFormAdmin\Includes\Models\Entries;
use HTContactFormAdmin\Includes\Helper;
use HTContactFormAdmin\Includes\Mailer;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Post;

/**
 * Form Submission API Handler Class
 * 
 * Handles all REST API endpoints for form submission management including CRUD operations.
 * 
 * @package HTContactFormAdmin\Includes\Api
 * @since 1.0.0
 */
class Submission {
    //-------------------------------------------------------------------------
    // PROPERTIES
    //-------------------------------------------------------------------------
    
    /** @var string REST API namespace */
    private $namespace = 'ht-form/v1';

    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var FormModel Form model instance */
    private $form;
    /** @var Entries Form Entries instance */
    private $entries;

    //-------------------------------------------------------------------------
    // INITIALIZATION
    //-------------------------------------------------------------------------
    
    /**
     * Get singleton instance
     * 
     * @return self
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     * 
     * Initializes the class and sets up WordPress hooks
     */
    public function __construct() {
        $this->form = FormModel::get_instance();
        $this->entries = Entries::get_instance();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     * 
     * Sets up all the REST API endpoints for form management
     */
    public function register_routes() {
        $routes = [
            // Form submission - public endpoint
            [
                'endpoint' => '/submission',
                'methods'  => 'POST',
                'callback' => 'submit_form',
                'permission_callback' => [$this, 'check_permission']
            ],
        ];

        foreach ($routes as $route) {
            register_rest_route($this->namespace, $route['endpoint'], [
                'methods'             => $route['methods'],
                'callback'            => [$this, $route['callback']],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => $route['args'] ?? []
            ]);
        }
    }

    //-------------------------------------------------------------------------
    // VALIDATION & PERMISSIONS
    //-------------------------------------------------------------------------
    
    /**
     * Validate that the provided ID is numeric
     * 
     * @param mixed $param Parameter to validate
     * @return bool Whether the parameter is numeric
     */
    public function validate_numeric_id($param) {
        return is_numeric($param);
    }

    /**
     * Check if current user has permission to access endpoints
     * 
     * @return bool Whether user has manage_options capability
     */
    public function check_permission() {
        // Submissions are allowed for everyone, but we may add spam protection here
        return true;
    }

    //-------------------------------------------------------------------------
    // MAIN OPERATIONS
    //-------------------------------------------------------------------------
    
    /**
     * Submit form
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response with submitted form data or error
     */
    public function submit_form($request) {
        // Process the submission
        try {
            $form_data = $request->get_params();
            $form_id = absint($form_data['ht_form_id']);

            // Refresh user agent from current request
            Helper::refresh_user_agent($request);

            // Get Form Data
            // Return the form if not found
            $form = $this->form->get($form_id);
            if (is_wp_error($form)) {
                return $form;
            }

            // Check honeypot field - if it's filled, it's probably a bot
            if (isset($form_data['ht_form_hp_email']) && !empty($form_data['ht_form_hp_email'])) {
                return new WP_Error(
                    'honeypot_filled',
                    __('Spam detected. Please try again.', 'ht-contactform'),
                    ['status' => 400]
                );
            }

            // Remove honeypot field from the submission data
            unset($form_data['ht_form_hp_email'], $form_data['ht_form_timestamp']);

            // Check for minimum submission time if enabled
            $settings = $form['settings'] ?? [];
            $spam_settings = $settings->spam_protection['settings'] ?? [];
            
            if (!empty($spam_settings['enable_minimum_time_to_submit']) && 
                !empty($spam_settings['minimum_time_to_submit']) && 
                !empty($form_data['ht_form_timestamp'])) {
                
                $min_seconds = (int) $spam_settings['minimum_time_to_submit'];
                $submit_timestamp = (int) $form_data['ht_form_timestamp'];
                $current_time = time();
                $elapsed_time = $current_time - $submit_timestamp;
                
                // If submission is too quick, treat as spam
                if ($elapsed_time < $min_seconds) {
                    return new WP_Error(
                        'submission_too_quick',
                        __('Submission was too quick. Please wait a moment and try again.', 'ht-contactform'),
                        ['status' => 400]
                    );
                }
            }

            // Verify reCAPTCHA if enabled in global settings
            if(isset($form_data['g-recaptcha-response'])) {
                $recaptcha_result = Helper::validate_recaptcha($form_data['g-recaptcha-response']);
                if ($recaptcha_result !== true) {
                    return new WP_Error(
                        $recaptcha_result['code'],
                        $recaptcha_result['message'],
                        ['status' => $recaptcha_result['status']]
                    );
                }
            }

            // Remove reCAPTCHA response from the submission data
            unset($form_data['g-recaptcha-response']);

            // Sanitize Form Data
            $form_data = $this->sanitize_data($form_id, $form_data, $form['fields']);

            // Validate form data
            if (empty($form_data) || empty($form_data['form_id'])) {
                return new WP_Error(
                    'invalid_form_data',
                    __('Form data or form ID is missing', 'ht-contactform'),
                    ['status' => 400]
                );
            }

            $form_id = absint($form_data['form_id']);
            
            // Check for unique email
            // $this->check_unique_email($form, $form_data);
            
            if (is_wp_error($form)) {
                return $form;
            }
            // Basic validation
            $errors = $this->validate_data($form_data, $form);
            
            if (!empty($errors)) {
                return new WP_Error(
                    'validation_failed',
                    __('Form validation failed', 'ht-contactform'),
                    [
                        'status' => 400,
                        'errors' => $errors
                    ]
                );
            }
            
            // Process form actions (email, storage, etc.)
            $this->process_form_actions($form_data, $form);

            $form_confirmation = (object) $form['settings']->confirmation['settings'];
            $confirmation = [
                'type'=> $form_confirmation->confirmation_type,
                'message'=> $form_confirmation->confirmation_message,
                'page' => get_permalink(absint($form_confirmation->confirmation_page)),
                'redirect'=> $form_confirmation->confirmation_redirect,
                'newTab'=> $form_confirmation->confirmation_new_tab,
            ];
            
            // Return success response
            return new WP_REST_Response([
                'success' => true,
                'confirmation' => $confirmation
            ], 200);
            
        } catch (\Exception $e) {
            return new WP_Error(
                'submission_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    
    /**
     * Sanitize form submission data
     *
     * @param array $form_data Raw form data submitted by the user
     * @return array Sanitized form data
     */
    private function sanitize_data($form_id, $form_data, $fields) {
        // Sanitize Form Data
        $sanitized_data = [];

        // Preserve form_id and reference fields
        $sanitized_data['form_id'] = $form_id;
        $sanitized_data['_wp_http_referer'] = !empty($form_data['_wp_http_referer']) ? 
            sanitize_text_field($form_data['_wp_http_referer']) : '';

        if (!empty($fields)) {
            // Loop through form fields and sanitize based on field type
            foreach ($fields as $field) {
                $field_name = $field['settings']['name_attribute'] ?? '';
                $field_type = $field['type'];
                // Skip if field doesn't exist in submission
                if (empty($form_data[$field_name])) {
                    continue;
                }
                
                // Sanitize based on field type
                switch ($field_type) {
                    case 'email':
                        $sanitized_data[$field_name] = sanitize_email($form_data[$field_name]);
                        break;
                        
                    case 'textarea':
                        $sanitized_data[$field_name] = sanitize_textarea_field($form_data[$field_name]);
                        break;
                        
                    case 'number':
                        $sanitized_data[$field_name] = is_numeric($form_data[$field_name]) ? 
                            floatval($form_data[$field_name]) : '';
                        break;
                        
                    case 'tel':
                        // Basic phone sanitization (keeping only digits, plus, dashes, and parentheses)
                        $sanitized_data[$field_name] = preg_replace('/[^0-9\+\-\(\) ]/', '', $form_data[$field_name]);
                        break;
                        
                    case 'url':
                        $sanitized_data[$field_name] = esc_url_raw($form_data[$field_name]);
                        break;
                        
                    case 'multiple_choices':
                    case 'checkboxes':
                    case 'radio':
                        // For multi-value fields like checkboxes
                        if (is_array($form_data[$field_name])) {
                            $sanitized_data[$field_name] = array_map('sanitize_text_field', $form_data[$field_name]);
                        } else {
                            $sanitized_data[$field_name] = sanitize_text_field($form_data[$field_name]);
                        }
                        break;
                        
                    case 'date':
                        // Simple date validation (basic format check)
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $form_data[$field_name])) {
                            $sanitized_data[$field_name] = $form_data[$field_name];
                        } else {
                            $sanitized_data[$field_name] = '';
                        }
                        break;
                        
                    case 'file':
                        // Files should be handled separately via $_FILES
                        $sanitized_data[$field_name] = '';
                        break;
                        
                    case 'name':
                        // For name fields with multiple components
                        if (is_array($form_data[$field_name])) {
                            $sanitized_data[$field_name] = [];
                            foreach ($form_data[$field_name] as $name_key => $name_value) {
                                $sanitized_data[$field_name][$name_key] = sanitize_text_field($name_value);
                            }
                        } else {
                            $sanitized_data[$field_name] = sanitize_text_field($form_data[$field_name]);
                        }
                        break;
                    
                    // Default sanitization for text and other field types    
                    default:
                        $sanitized_data[$field_name] = sanitize_text_field($form_data[$field_name]);
                        break;
                }
            }
        } else {
            // If we can't get the form structure, do basic sanitization on all fields
            foreach ($form_data as $key => $value) {
                if ($key === 'ht_form_id') {
                    $sanitized_data[$key] = absint($value);
                } else if (is_array($value)) {
                    $sanitized_data[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $sanitized_data[$key] = sanitize_text_field($value);
                }
            }
        }
        return $sanitized_data;
    }

    private function get_field_setting_value($settings, $type) {
        return array_reduce($settings, function($carry, $setting) use ($type) {
            return $setting['type'] === $type ? $setting['value'] : $carry;
        }, '');
    }

    /**
     * Validate form submission data against form configuration
     * 
     * @param array $form_data Submitted form data
     * @param array $form Form configuration
     * @return array Array of validation errors (field_name => error_message)
     */
    private function validate_data($form_data, $form) {
        $errors = [];
        $fields = $form['fields'] ?? [];
        
        foreach ($fields as $field) {
            $field_name = $field['settings']['name_attribute'] ?? '';
            $field_label = $field['settings']['label'] ?? $field_name;
            $is_required = !empty($field['settings']['required']);
            
            // Skip if field is not required
            if (!$is_required) {
                continue;
            }
            
            // Check required fields
            if ($is_required && (!isset($form_data[$field_name]) || $form_data[$field_name] === '')) {
                $errors[$field_name] = sprintf(
                    /* translators: %s: field label */
                    __('%s is required.', 'ht-contactform'),
                    $field_label
                );
                continue;
            }
            
            // Validate email format
            if ($field['type'] === 'email' && !empty($form_data[$field_name])) {
                if (!is_email($form_data[$field_name])) {
                    $errors[$field_name] = sprintf(
                        /* translators: %s: field label */
                        __('%s must be a valid email address.', 'ht-contactform'),
                        $field_label
                    );
                }
            }
        }
        
        return $errors;
    }

    /**
     * Process form actions (send email, store submission, etc.)
     * 
     * @param array $form_data Submitted form data
     * @param array $form Form configuration
     */
    private function process_form_actions($form_data, $form) {
        $settings = $form['settings'] ?? [];
        $global = get_option('ht_form_global_settings', []);
        $meta = [
            'user_id'     => get_current_user_id(),
            'ip_address'  => !empty($global['miscellaneous']['disable_ip_logging']) ? '' : Helper::get_ip(),
            'browser'     => Helper::get_browser(),
            'device'      => Helper::get_device(),
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql')
        ];
        
        // Send email notification if enabled
        if (!empty($settings->notification['settings']['enable_notification'])) {
            $mailer = Mailer::get_instance($form, $form_data, $meta);
            $mailer->send();
        }
        
        // Store submission in database if enabled
        if (!empty($settings->general['settings']['store_submissions'])) {
            $result = $this->store_submission($form_data, $meta);
            if (is_wp_error($result)) {
                return $result;
            }
        }
        
        // Run action hook for third-party integrations
        do_action('ht_form/after_submission', $form_data, $form);
    }

    /**
     * Store form submission in database
     * 
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return int|WP_Error New entry ID or error
     */
    private function store_submission($form_data, $meta) {
        $result = $this->entries->create($form_data, $meta);
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result;
    }
}