<?php

namespace HTContactFormAdmin\Includes\Api\Endpoints;

use HTContactFormAdmin\Includes\Config\Form as FormConfig;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Integrations API Handler Class
 * 
 * Handles all REST API endpoints for integrations management
 */
class Integrations {
    //-------------------------------------------------------------------------
    // PROPERTIES
    //-------------------------------------------------------------------------
    
    /** @var string REST API namespace */
    private $namespace = 'ht-form/v1';

    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var array Integrations settings */
    private $integrations_settings;
    /**
     * Option name for storing integrations settings
     */
    public const OPTION_NAME = 'ht_form_integrations';

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
        $this->integrations_settings = FormConfig::get_instance()->form_integrations();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register routes
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, 
            '/integrations', 
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_integrations'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
                [
                    'methods'             => 'PUT',
                    'callback'            => [$this, 'update_integrations'],
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
        register_rest_route(
            $this->namespace, 
            '/integrations/verify', 
            [
                [
                    'methods'             => 'POST',
                    'callback'            => [$this, 'verify_integration'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => [
                        'integration' => [
                            'required' => true,
                            'type'     => 'string',
                        ],
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
                esc_html__('You do not have permission to manage integrations.', 'ht-contactform'),
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * Get integration settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_integrations($request) {
        $default = $this->get_default_integrations();
        $settings = get_option(self::OPTION_NAME, $default);
        return new WP_REST_Response($settings, 200);
    }

    /**
     * Update integrations settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function update_integrations($request) {
        $settings = $request->get_param('settings');
        if (!$settings) {
            // Try to get from JSON body for backward compatibility
            $settings = $request->get_json_params();
        }
        
        $sanitize_data = $this->sanitize_integration_data($settings);
            
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
        wp_cache_delete('ht_form_integrations_settings', 'options');
        
        return new WP_REST_Response($sanitize_data, 200);
    }

    /**
     * Verify integration settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_Error|WP_REST_Response
     */
    public function verify_integration($request) {
        $integration = $request->get_param('integration');
        $settings = $request->get_param('settings');
        
        // Validate settings
        if (!is_array($settings)) {
            return new WP_Error(
                'invalid_settings',
                esc_html__('Settings must be an object.', 'ht-contactform'),
                ['status' => 400]
            );
        }

        // Check if integration type is supported
        $supported_integrations = ['mailchimp'];
        if (!in_array($integration, $supported_integrations)) {
            return new WP_Error(
                'unsupported_integration',
                sprintf(esc_html__('Integration type "%s" is not supported.', 'ht-contactform'), $integration),
                ['status' => 400]
            );
        }

        $result = false;

        // Verify based on integration type
        if ($integration === 'mailchimp') {
            if (empty($settings['api_key'])) {
                return new WP_Error(
                    'missing_api_key',
                    esc_html__('API key is required for Mailchimp integration.', 'ht-contactform'),
                    ['status' => 400]
                );
            }
            
            $api_key = $settings['api_key'];
            $result = $this->verify_mailchimp($api_key);
            
            if ($result === false) {
                return new WP_Error(
                    'invalid_api_key',
                    esc_html__('The Mailchimp API key is invalid or the service is unavailable.', 'ht-contactform'),
                    ['status' => 400]
                );
            }
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => esc_html__('Integration verified successfully.', 'ht-contactform'),
            'data' => $result
        ], 200);
    }

    /**
     * Get default integration settings
     *
     * @return array Default integration settings
     */
    private function get_default_integrations() {
        return array_reduce($this->integrations_settings['settings'], function($carry, $integration) {
            $default_options = [];
            if(!empty($integration['options'])) {
                $default_options = array_reduce($integration['options'], function($carry, $option) {
                    $carry[$option['id']] = $option['value'];
                    return $carry;
                }, []);
            }
            $carry[$integration['id']] = [
                'enabled' => $integration['value'],
                ...$default_options
            ];
            
            return $carry;
        }, []);
    }

    /**
     * Sanitize integration data
     *
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    private function sanitize_integration_data($data) {
        if (!is_array($data)) {
            return [];
        }

        $sanitized = [];
        
        foreach ($data as $key => $integration) {
            if (!is_array($integration)) {
                continue;
            }
            
            $sanitized[$key] = [];
            
            // Sanitize enabled status
            if (isset($integration['enabled'])) {
                $sanitized[$key]['enabled'] = (bool) $integration['enabled'];
            }

            $setting = current(array_filter($this->integrations_settings['settings'], fn($setting) => $setting['id'] === $key));

            if(empty($setting['options'])) {
                continue;
            }
            
            // Sanitize other options
            foreach ($integration as $option_id => $value) {
                if ($option_id === 'enabled') {
                    continue;
                }

                $option = current(array_filter($setting['options'], fn($option) => $option['id'] === $option_id));
                $callback = $option['callback'];

                if(is_callable($callback)) {
                    $sanitized[$key][$option_id] = call_user_func($callback, $value);
                }
                
                switch ($callback) {
                    case 'number':
                        $sanitized[$key][$option_id] = is_numeric($value) ? (float) $value : 0;
                        break;
                        
                    case 'switch':
                    case 'checkbox':
                        $sanitized[$key][$option_id] = (bool) $value;
                        break;
                        
                    default:
                        $sanitized[$key][$option_id] = $value;
                        break;
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Verify Mailchimp integration
     *
     * @param string $api_key Mailchimp API key
     * @return array Verification result
     */
    private function verify_mailchimp($api_key) {
        // Extract the data center from the API key
        $parts = explode('-', $api_key);
        if (count($parts) != 2) {
            return [
                'success' => false,
                'message' => esc_html__('Invalid API key format. Mailchimp API keys should be in the format "key-datacenter".', 'ht-contactform')
            ];
        }
        
        $data_center = $parts[1];
        $url = "https://{$data_center}.api.mailchimp.com/3.0/";
        
        $args = [
            'method' => 'GET',
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("anystring:{$api_key}")
            ]
        ];
        
        $response = wp_remote_request($url, $args);
        
        // Check if request was successful
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code()
            ];
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        switch ($http_code) {
            case 200:
                $account_info = [
                    'account_id' => isset($data['account_id']) ? $data['account_id'] : '',
                    'account_name' => isset($data['account_name']) ? $data['account_name'] : '',
                    'email' => isset($data['email']) ? $data['email'] : '',
                ];

                return [
                    'success' => true,
                    'message' => esc_html__('Mailchimp API connection successful.', 'ht-contactform'),
                    'account' => $account_info
                ];
            default:
                $error_message = isset($data['detail']) ? $data['detail'] : esc_html__('Unknown error occurred.', 'ht-contactform');

                return [
                    'success' => false,
                    'message' => $error_message,
                    'code' => $http_code
                ];
        }
    }
    
}