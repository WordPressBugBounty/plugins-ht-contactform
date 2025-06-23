<?php
namespace HTContactFormAdmin\Includes\Integrations;

use HTContactFormAdmin\Includes\Services\Helper;
use MailchimpMarketing\ApiClient as MailchimpApiClient;
use WP_Error;
use WP_REST_Response;

class Mailchimp {
    private $api_key = null;
    private $form = null;
    private $form_data = null;
    private $meta = null;
    private $helper = null;

    private static $instance = null;

    /**
     * Get instance
     * 
     * @param string $api_key
     * @param array $form
     * @param array $form_data
     * @param array $meta
     * @return self
     */
    public static function get_instance($api_key = null, $form = null, $form_data = null, $meta = null) {
        if (!isset(self::$instance) || $api_key !== null) {
            self::$instance = new self($api_key, $form, $form_data, $meta);
        }
        return self::$instance;
    }

    /**
     * Constructor
     * 
     * @param string $api_key
     * @param array $form
     * @param array $form_data
     * @param array $meta
     */
    public function __construct($api_key = null, $form = null, $form_data = null, $meta = null) {
        $this->api_key = is_string($api_key) ? trim($api_key) : '';
        $this->form = $form;
        $this->form_data = $form_data;
        $this->meta = $meta;
        $this->helper = Helper::get_instance();
    
        add_action('wp_ajax_ht_form_mailchimp_field_tags', [$this, 'get_field_tags']);
        add_action('wp_ajax_nopriv_ht_form_mailchimp_field_tags', [$this, 'get_field_tags']);
    }

    /**
     * Get initialized Mailchimp API client
     *
     * @return MailchimpApiClient
     * @throws \Exception If API key is invalid
     */
    private function get_api_client() {
        if (empty($this->api_key)) {
            throw new \Exception('Mailchimp API key is required');
        }
        
        $api_parts = explode('-', $this->api_key);
        if (count($api_parts) <= 1 || empty($api_parts[1])) {
            throw new \Exception('Invalid Mailchimp API key format');
        }
        
        $client = new MailchimpApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $api_parts[1],
        ]);
        
        return $client;
    }

    /**
     * Get lists
     * @return array|WP_Error
     */
    public function get_lists() {
        if (empty($this->api_key)) {
            return new WP_Error('mailchimp_error', 'API key is required');
        }
        
        try {
            $client = $this->get_api_client();
            $response = $client->lists->getAllLists();
            return $response->lists;
        } catch (\Exception $e) {
            error_log('Mailchimp Lists Error: ' . $e->getMessage());
            return new WP_Error('mailchimp_error', $e->getMessage());
        }
    }

    /**
     * Get merge fields
     * 
     * @param string $list_id
     * @return array|WP_Error
     */
    public function get_merge_fields($list_id) {
        if (empty($this->api_key)) {
            return new WP_Error('mailchimp_error', 'API key is required');
        }
        
        if (empty($list_id)) {
            return new WP_Error('mailchimp_error', 'List ID is required');
        }
        
        try {
            $client = $this->get_api_client();
            $response = $client->lists->getListMergeFields($list_id);
            return $response->merge_fields;
        } catch (\Exception $e) {
            error_log('Mailchimp Merge Fields Error: ' . $e->getMessage());
            return new WP_Error('mailchimp_error', $e->getMessage());
        }
    }

    /**
     * Get search tags
     * 
     * @param string $list_id
     * @return array|WP_Error
     */
    public function get_search_tags($list_id) {
        if (empty($this->api_key)) {
            return new WP_Error('mailchimp_error', 'API key is required');
        }
        
        if (empty($list_id)) {
            return new WP_Error('mailchimp_error', 'List ID is required');
        }
        
        try {
            $client = $this->get_api_client();
            $response = $client->lists->tagSearch($list_id);
            return $response->tags;
        } catch (\Exception $e) {
            error_log('Mailchimp Tags Error: ' . $e->getMessage());
            return new WP_Error('mailchimp_error', $e->getMessage());
        }
    }

    /**
     * Get field tags for AJAX request
     * 
     * @return void
     */
    public function get_field_tags() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!isset($_GET['list_id']) || empty($_GET['list_id'])) {
            wp_send_json_error(['message' => 'List ID is required'], 400);
            return;
        }
        
        $list_id = sanitize_text_field($_GET['list_id']);
        $fields = $this->get_merge_fields($list_id);
        $tags = $this->get_search_tags($list_id);
        
        if (is_wp_error($fields)) {
            wp_send_json_error(['message' => $fields->get_error_message()], 400);
            return;
        }
        
        if (is_wp_error($tags)) {
            wp_send_json_error(['message' => $tags->get_error_message()], 400);
            return;
        }
        
        wp_send_json_success([
            'fields' => $fields,
            'tags' => $tags
        ]);
    }

    /**
     * Subscribe to Mailchimp
     * 
     * @param object $integration Integration settings
     * @param array $form Form data
     * @param array $form_data Form submission data
     * @param array $meta Meta data
     * @return WP_Error|WP_REST_Response Whether the Mailchimp request was sent successfully
     */
    public function subscribe($integration, $form, $form_data, $meta) {
        // Check if integration is enabled for this form
        if (empty($integration->enabled) || empty($integration->list_id)) {
            return new WP_Error('mailchimp_error', 'Integration is not enabled for this form');
        }

        // Prepare data to send to Mailchimp
        $data = [
            'status' => !empty($integration->double_opt_in) ? 'pending' : 'subscribed',
            'vip' => !empty($integration->vip),
            'tags' => [],
        ];
        
        // Process tags
        if (!empty($integration->tags) && is_array($integration->tags)) {
            $data['tags'] = $integration->tags;
        }
        
        // Convert tags to strings and filter empty values
        if (!empty($data['tags'])) {
            $data['tags'] = array_map('strval', array_filter($data['tags']));
        }

        // Process merge fields
        $merge_fields = [];
        if (!empty($integration->merge_fields) && is_array($integration->merge_fields)) {
            foreach ($integration->merge_fields as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                if (!str_contains($value, 'input.')) {
                    $merge_fields[$key] = $this->helper->filter_vars($value, $form_data, $form);
                } elseif (str_contains($value, 'input.')) {
                    preg_match_all('/{input\.([^}]+)}/', $value, $matches, PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        $field_name = $match[1];
                        if (isset($form_data[$field_name])) {
                            $merge_fields[$key] = $form_data[$field_name];
                        }
                    }
                } else {
                    $merge_fields[$key] = sanitize_text_field($value);
                }
            }
        }
        
        // Check for required email field
        if (empty($merge_fields['email_address'])) {
            error_log('Mailchimp Error: Email field is required');
            return new WP_Error('mailchimp_error', 'Email field is required');
        }

        // Set email address from merge fields if available
        if (!empty($merge_fields['email_address'])) {
            $email = sanitize_email($merge_fields['email_address']);
            if (is_email($email)) {
                $data['email_address'] = $email;
            } else {
                error_log('Mailchimp Error: Invalid email address');
                return new WP_Error('mailchimp_error', 'Invalid email address');
            }
        }
        
        $data['merge_fields'] = $this->format_merge_fields($merge_fields);

        // Initialize Mailchimp API client and process subscription
        try {
            $client = $this->get_api_client();
            
            // Get MD5 hash of lowercase email for member lookup
            $member_hash = md5(strtolower($data['email_address']));
            
            // Check if member exists
            try {
                $member = $client->lists->getListMember($integration->list_id, $member_hash);
                
                // Update existing member
                if (isset($member->status) && $member->status === 'unsubscribed' && !empty($integration->resubscribe)) {
                    $response = $client->lists->updateListMember($integration->list_id, $member_hash, $data);
                } elseif (isset($member->id)) {
                    $response = $client->lists->updateListMember($integration->list_id, $member_hash, $data);
                } else {
                    // Should not reach here, but just in case
                    $response = $client->lists->addListMember($integration->list_id, $data);
                }
            } catch (\Exception $e) {
                // Member doesn't exist, add new member
                if (strpos($e->getMessage(), 'Resource Not Found') !== false) {
                    $response = $client->lists->addListMember($integration->list_id, $data);
                } else {
                    // Other error
                    error_log('Mailchimp Error: ' . $e->getMessage());
                    do_action('ht_form/mailchimp_integration_result', $response, 'failed', $e->getMessage());
                    return new WP_Error('mailchimp_error', $e->getMessage());
                }
            }
            
            // Success case
            if (isset($response->status) && ($response->status === 'subscribed' || $response->status === 'pending')) {
                // Trigger hooks for external tracking
                do_action('ht_form/mailchimp_integration_result', $response, 'success', 'Mailchimp subscription successfully');
                return new WP_REST_Response([
                    'message' => 'Mailchimp subscription successfully',
                    'response' => $response,
                ], 200);
            }
            
            // Log unexpected response
            error_log('Mailchimp Unexpected Response: ' . json_encode($response));
            do_action('ht_form/mailchimp_integration_result', $response, 'failed', 'Unexpected response');
            return new WP_Error('mailchimp_error', 'Unexpected response');
            
        } catch (\Exception $e) {
            // Log the error
            error_log('Mailchimp Error: ' . $e->getMessage());
            do_action('ht_form/mailchimp_integration_result', $response, 'failed', $e->getMessage());
            return new WP_Error('mailchimp_error', $e->getMessage());
        }
    }

    /**
     * Format merge fields for Mailchimp API
     * 
     * Processes form data to match Mailchimp's expected merge field format:
     * - Handles special fields like names and addresses
     * - Sanitizes values based on their type
     * - Skips email_address as it's handled separately
     * 
     * @param array $data The raw merge field data
     * @return array Formatted merge fields ready for Mailchimp API
     */
    public function format_merge_fields($data) {
        if (!is_array($data)) {
            return [];
        }

        $merge_fields_data = [];
        
        foreach ($data as $key => $value) {
            // Skip email_address as it's handled separately in the main data array
            if ($key === 'email_address') {
                continue;
            }
            
            // Handle array values (complex fields)
            if (is_array($value)) {
                // Handle name fields (combine into a single string)
                if ($this->is_name_field($value)) {
                    $merge_fields_data[$key] = implode(' ', array_filter($value));
                } 
                // Handle address fields (format as Mailchimp expects)
                elseif ($this->is_address_field($value)) {
                    $address = $this->get_formatted_address($value);
                    if ($address) {
                        $merge_fields_data[$key] = $address;
                    }
                } 
                // Handle other array values
                else {
                    $merge_fields_data[$key] = array_map('sanitize_text_field', $value);
                }
            } 
            // Handle scalar values
            elseif ($value !== null && $value !== '') {
                if (is_email($value)) {
                    $merge_fields_data[$key] = sanitize_email($value);
                } elseif (is_numeric($value)) {
                    // Preserve float values when needed
                    $merge_fields_data[$key] = strpos($value, '.') !== false ? (float) $value : (int) $value;
                } else {
                    $merge_fields_data[$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $merge_fields_data;
    }
    
    /**
     * Check if an array contains address field components
     *
     * @param array $data The data to check
     * @return bool True if it contains address components
     */
    private function is_name_field($data) {
        $name_fields = [
            'first_name', 'last_name', 'middle_name'
        ];
        
        foreach ($name_fields as $field) {
            if (isset($data[$field])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if an array contains address field components
     *
     * @param array $data The data to check
     * @return bool True if it contains address components
     */
    private function is_address_field($data) {
        $address_fields = [
            'address_line_1', 'address_line_2', 'address_city', 
            'address_state', 'address_zip', 'address_country'
        ];
        
        foreach ($address_fields as $field) {
            if (isset($data[$field])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Format address data for Mailchimp API
     * 
     * @param array $data Address field data
     * @return array|false Formatted address data or false if required fields are missing
     */
    public function get_formatted_address($data) {
        if (!is_array($data)) {
            return false;
        }
        
        $address_data = [
            'addr1' => '',
            'addr2' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => ''
        ];
        
        // Map your data to Mailchimp format
        if (!empty($data['address_line_1'])) {
            $address_data['addr1'] = sanitize_text_field($data['address_line_1']);
        }
        if (!empty($data['address_line_2'])) {
            $address_data['addr2'] = sanitize_text_field($data['address_line_2']);
        }
        if (!empty($data['address_city'])) {
            $address_data['city'] = sanitize_text_field($data['address_city']);
        }
        if (!empty($data['address_state'])) {
            $address_data['state'] = sanitize_text_field($data['address_state']);
        }
        if (!empty($data['address_zip'])) {
            $address_data['zip'] = sanitize_text_field($data['address_zip']);
        }
        if (!empty($data['address_country'])) {
            $address_data['country'] = sanitize_text_field($data['address_country']);
        }
        
        // Only include if we have the minimum required fields
        if (!empty($address_data['addr1']) && !empty($address_data['city'])) {
            return $address_data;
        }
        return false;
    }
}
