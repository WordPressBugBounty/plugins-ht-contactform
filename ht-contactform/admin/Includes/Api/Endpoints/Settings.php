<?php

namespace HTContactFormAdmin\Includes\Api\Endpoints;

use HTContactFormAdmin\Includes\Config\Form as FormConfig;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Settings API Handler Class
 * 
 * Handles all REST API endpoints for global settings management
 */
class Settings {
    //-------------------------------------------------------------------------
    // PROPERTIES
    //-------------------------------------------------------------------------
    
    /** @var string REST API namespace */
    private $namespace = 'ht-form/v1';

    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var array Global settings */
    private $global_settings;
    /**
     * Option name for storing global settings
     */
    public const OPTION_NAME = 'ht_form_global_settings';

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
        $this->global_settings = FormConfig::get_instance()->form_global_settings();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register routes
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, 
            '/settings', 
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_settings'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
                [
                    'methods'             => 'PUT',
                    'callback'            => [$this, 'update_settings'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => [
                        'settings' => [
                            'required' => true,
                            'type'     => 'object',
                        ],
                    ],
                ]
            ]
        );
    }

    /**
     * Check if user has permission
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, WP_Error otherwise
     */
    public function permissions_check($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to manage settings.', 'ht-contactform'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * Get global settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_settings($request) {
        $default = array_reduce($this->global_settings, function($carry, $section): mixed {
            $carry[$section['id']] = array_reduce($section['settings'], function($carry, $field): mixed {
                $carry[$field['id']] = $field['value'];
                return $carry;
            }, []);
            return $carry;
        }, []);

        $settings = get_option(self::OPTION_NAME, $default);
        return new WP_REST_Response($settings, 200);
    }

    /**
     * Update global settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function update_settings($request) {
        $settings = $request->get_param('settings');
        if (!$settings) {
            // Try to get from JSON body for backward compatibility
            $settings = $request->get_json_params();
        }
        
        $sanitize_data = [];
        foreach ($this->global_settings as $key => $section) {
            $sanitize_data[$key] = array_reduce($section['settings'], function($carry, $field) use ($settings, $key) {
                if (isset($settings[$key]) && isset($settings[$key][$field['id']])) {
                    if($field['type'] === 'input' || $field['type'] === 'password') {
                        $carry[$field['id']] = sanitize_text_field($settings[$key][$field['id']]);
                    } else if($field['type'] === 'switch') {
                        $carry[$field['id']] = (bool) $settings[$key][$field['id']];
                    } else if($field['type'] === 'textarea') {
                        $carry[$field['id']] = sanitize_textarea_field($settings[$key][$field['id']]);
                    } else if($field['type'] === 'select') {
                        $carry[$field['id']] = sanitize_text_field($settings[$key][$field['id']]);
                    } else if($field['type'] === 'radio' || $field['type'] === 'radio_card') {
                        $carry[$field['id']] = sanitize_text_field($settings[$key][$field['id']]);
                    }
                }
                return $carry;
            }, []);
        }
            
        // Validate settings
        if (!is_array($settings)) {
            return new WP_Error(
                'invalid_settings',
                esc_html__('Settings must be an object.', 'ht-contactform'),
                ['status' => 400]
            );
        }
        
        // Update settings
        update_option(self::OPTION_NAME, $sanitize_data);
        
        // Clear any caches
        wp_cache_delete('ht_form_global_settings', 'options');
        
        return new WP_REST_Response($sanitize_data, 200);
    }
}