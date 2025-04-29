<?php

namespace HTContactFormAdmin\Includes\Models;

use HTContactFormAdmin\Includes\Config\Form as FormConfig;
use HTContactFormAdmin\Includes\Models\Entries;
use WP_Post;
use WP_Error;

/**
 * Form Model Class
 * 
 * Handles all form data operations including CRUD operations,
 * form data manipulation, and data sanitization.
 * 
 * @package HTContactFormAdmin\Includes\Models
 * @since 1.0.0
 */
class Form {
    //-------------------------------------------------------------------------
    // PROPERTIES
    //-------------------------------------------------------------------------
    
    /** @var string Custom post type name for forms */
    private $post_type = 'ht_form';
    private $entries = null;

    /** @var self|null Singleton instance */
    private static $instance = null;

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
     */
    public function __construct() {
        // Initialize the model
        $this->entries = Entries::get_instance();
    }

    //-------------------------------------------------------------------------
    // MAIN CRUD OPERATIONS
    //-------------------------------------------------------------------------
    
    /**
     * Get all forms
     * 
     * @param string $search_query Optional search query
     * @return array Array of form data
     */
    public function get_all($search_query = '') {
        $args = [
            'post_type'      => $this->post_type,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish'
        ];

        if ($search_query) {
            $args['s'] = $search_query;
        }

        $posts = get_posts($args);
        $forms = [];

        foreach ($posts as $post) {
            $form_data = $this->get_form_data($post->ID);
            $entries_count_unread = $this->entries->count_by_form($post->ID, 'unread'); // Placeholder for entries count
            $entries_count_read = $this->entries->count_by_form($post->ID, 'read'); // Placeholder for entries count
            $entries_count_total = $this->entries->count_by_form($post->ID, 'all'); // Placeholder for entries count

            $forms[] = [
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'fields'     => $form_data['fields'],
                'settings'   => $form_data['settings'],
                'shortcode'  => "[ht_form id=\"{$post->ID}\"]",
                'entries'    => [
                    'unread' => $entries_count_unread,
                    'read'   => $entries_count_read,
                    'total'  => $entries_count_total
                ],
                'created_at' => $post->post_date,
                'updated_at' => $post->post_modified
            ];
        }

        return $forms;
    }

    /**
     * Get single form
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error Form data or error
     */
    public function get($form_id) {
        $post = $this->get_form_post($form_id);
        if (is_wp_error($post)) {
            return $post;
        }

        $form_data = $this->get_form_data($post->ID);

        return [
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'fields'     => $form_data['fields'],
            'settings'   => $form_data['settings'],
            'shortcode'  => "[ht_form id=\"{$post->ID}\"]",
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified
        ];
    }

    /**
     * Create new form
     * 
     * @param array $form_data Form data
     * @return array|WP_Error New form data or error
     */
    public function create($form_data) {
        $title = $form_data['title'] ?? __('Untitled Form', 'ht-contactform');
        
        $sanitized_data = $this->sanitize_form_data($form_data);

        // Prepare initial content as JSON
        $content_data = wp_json_encode([
            'fields'   => $sanitized_data['fields'] ?? [],
            'settings' => $sanitized_data['settings'] ?? (object) []
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Use wp_slash to preserve newlines and special characters during post insertion
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_type'    => $this->post_type,
            'post_status'  => 'publish',
            'post_content' => wp_slash($content_data)
        ], true);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'creation_failed',
                'Failed to create form',
                ['status' => 500]
            );
        }

        return $this->get($post_id);
    }

    /**
     * Update existing form
     * 
     * @param int $form_id Form ID
     * @param array $form_data Form data
     * @return array|WP_Error Updated form data or error
     */
    public function update($form_id, $form_data) {
        $post = $this->get_form_post($form_id);
        if (is_wp_error($post)) {
            return $post;
        }

        if (empty($form_data)) {
            return new WP_Error(
                'invalid_data',
                'Form data is required',
                ['status' => 400]
            );
        }

        // Update post title
        $updated_post = wp_update_post([
            'ID'         => $post->ID,
            'post_title' => $form_data['title'],
            'post_type'  => $this->post_type
        ], true);

        if (is_wp_error($updated_post)) {
            return new WP_Error(
                'update_failed',
                'Failed to update form',
                ['status' => 500]
            );
        }

        // Save form data to post_content
        $save_result = $this->save_form_data($post->ID, $form_data);
        
        if (!$save_result) {
            return new WP_Error(
                'update_failed',
                'Failed to save form data',
                ['status' => 500]
            );
        }

        return $this->get($form_id);
    }

    /**
     * Delete form
     * 
     * @param int $form_id Form ID
     * @return bool|WP_Error Success status or error
     */
    public function delete($form_id) {
        $post = $this->get_form_post($form_id);
        if (is_wp_error($post)) {
            return $post;
        }

        $result = wp_delete_post($post->ID, true);
        if (!$result) {
            return new WP_Error(
                'delete_failed',
                'Failed to delete form',
                ['status' => 500]
            );
        }

        return true;
    }

    /**
     * Duplicate existing form
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error New form data or error
     */
    public function duplicate($form_id) {
        $original_form = $this->get_form_post($form_id);
        
        if (is_wp_error($original_form)) {
            return $original_form;
        }

        // Get original form parsed data
        $form_data = $this->parse_post_content($original_form);
        
        // Prepare content for new form
        $content_data = wp_json_encode([
            'fields'   => $form_data['fields'],
            'settings' => $form_data['settings']
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Create new form post
        $new_form_id = wp_insert_post([
            'post_title'   => "{$original_form->post_title} (Copy)",
            'post_type'    => $this->post_type,
            'post_status'  => 'publish',
            'post_content' => wp_slash($content_data)
        ], true);

        if (is_wp_error($new_form_id)) {
            return new WP_Error(
                'form_duplication_failed',
                'Failed to duplicate form',
                ['status' => 500]
            );
        }

        // Get the new form data
        return $this->get($new_form_id);
    }

    /**
     * Export form data
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error Form export data or error
     */
    public function export($form_id) {
        $form = $this->get_form_post($form_id);
        
        if (is_wp_error($form)) {
            return $form;
        }

        $form_data = $this->get_form_data($form_id);
        
        return [
            'title'         => $form->post_title,
            'fields'        => $form_data['fields'],
            'settings'      => $form_data['settings'],
            'date_exported' => current_time('mysql')
        ];
    }

    //-------------------------------------------------------------------------
    // HELPER METHODS
    //-------------------------------------------------------------------------
    
    /**
     * Get form post by ID
     * 
     * @param int $post_id Post ID
     * @return WP_Post|WP_Error Post object or error if not found
     */
    private function get_form_post($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== $this->post_type) {
            return new WP_Error(
                'form_not_found',
                "Form not found -> $post_id",
                ['status' => 404]
            );
        }

        return $post;
    }

    /**
     * Get form data with merged default values from post content
     * 
     * @param int $post_id Post ID
     * @return array{fields: array, settings: object} Form data
     */
    private function get_form_data($post_id) {
        $post = $this->get_form_post($post_id);
        if (is_wp_error($post)) {
            return [
                'fields'   => [],
                'settings' => (object) []
            ];
        }

        // Parse form data from post content
        $saved_data = $this->parse_post_content($post);

        // Get default data
        $form_config = FormConfig::get_instance();
        $default_fields = $form_config->fields();
        $default_settings = $form_config->form_settings();

        // Merge fields and settings with defaults
        // $merged_fields = $this->merge_fields_with_defaults($saved_data['fields'], $default_fields);
        // $merged_settings = $this->merge_settings_with_defaults($saved_data['settings'], $default_settings);
        return $saved_data;
    }

    /**
     * Parse form data from post content
     * 
     * @param WP_Post $post Post object
     * @return array{fields: array, settings: object} Parsed data
     */
    private function parse_post_content($post) {
        $saved_data = [];
        if (!empty($post->post_content)) {
            $saved_data = json_decode($post->post_content, true, 512, JSON_UNESCAPED_UNICODE);
        }

        $saved_fields = isset($saved_data['fields']) && is_array($saved_data['fields']) 
            ? $saved_data['fields'] 
            : [];
            
        $saved_settings = isset($saved_data['settings']) 
            ? $saved_data['settings'] 
            : (object) [];
        
        if (is_array($saved_settings)) {
            $saved_settings = (object) $saved_settings;
        }

        return [
            'fields'   => $saved_fields,
            'settings' => $saved_settings
        ];
    }

    /**
     * Merge saved fields with default fields
     * 
     * @param array $saved_fields Saved fields from post content
     * @param array $default_fields Default fields from settings
     * @return array Merged fields
     */
    private function merge_fields_with_defaults($saved_fields, $default_fields) {
        $merged_fields = [];
        
        foreach ($saved_fields as $saved_field) {
            $default_field = $this->find_default_field($saved_field['type'], $default_fields);
            if (!$default_field) {
                continue;
            }
            
            // Merge settings
            $merged_settings = [];
            foreach ($default_field['settings'] as $default_setting) {
                $saved_setting = $this->find_setting_by_id(
                    $saved_field['settings'], 
                    $default_setting['id']
                );
                $merged_settings[] = array_merge($default_setting, $saved_setting ?: []);
            }
            
            // Create merged field
            $merged_fields[] = [
                'id'       => $saved_field['id'],
                'type'     => $default_field['type'],
                'label'    => $default_field['label'],
                'options'  => $default_field['options'] ?? [],
                'settings' => $merged_settings
            ];
        }
        
        return $merged_fields;
    }

    /**
     * Merge saved settings with default settings
     * 
     * @param object $saved_settings Saved settings from post content
     * @param array $default_settings Default settings from settings class
     * @return array Merged settings
     */
    private function merge_settings_with_defaults($saved_settings, $default_settings) {
        $merged_settings = [];
        
        foreach ($default_settings as $section_key => $default_section) {
            $saved_section = isset($saved_settings->{$section_key}) 
                ? (array) $saved_settings->{$section_key} 
                : [];
            
            // Merge settings within section
            $section_settings = [];
            foreach ($default_section['settings'] as $default_setting) {
                $saved_setting = $this->find_setting_by_id(
                    $saved_section['settings'] ?? [], 
                    $default_setting['id']
                );
                $section_settings[] = array_merge($default_setting, $saved_setting ?: []);
            }

            $merged_settings[$section_key] = [
                'id'       => $default_section['id'],
                'label'    => $default_section['label'],
                'settings' => $section_settings
            ];
        }
        
        return $merged_settings;
    }

    /**
     * Find a field in default fields by type
     * 
     * @param string $type Field type to find
     * @param array $default_fields Array of default fields
     * @return array|null Found field or null
     */
    private function find_default_field($type, $default_fields) {
        foreach ($default_fields as $field) {
            if ($field['type'] === $type) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Find a setting by its ID
     * 
     * @param array|null $settings Array of settings
     * @param string $id Setting ID to find
     * @return array|null Found setting or null
     */
    private function find_setting_by_id($settings, $id) {
        if (!is_array($settings)) {
            return null;
        }
        
        foreach ($settings as $setting) {
            if ($setting['id'] === $id) {
                return $setting;
            }
        }
        
        return null;
    }

    /**
     * Save form data to post content
     * 
     * @param int $post_id Post ID
     * @param array $form_data Form data to save
     * @return bool Success status
     */
    private function save_form_data($post_id, $form_data) {
        $sanitized_data = $this->sanitize_form_data($form_data);
        
        // Convert the form data to JSON for storage
        $content_data = wp_json_encode([
            'fields'   => $sanitized_data['fields'],
            'settings' => $sanitized_data['settings']
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Update the post with new content
        $updated = wp_update_post([
            'ID'           => $post_id,
            'post_content' => wp_slash($content_data)
        ], true);
        
        return !is_wp_error($updated);
    }

    //-------------------------------------------------------------------------
    // SANITIZATION METHODS
    //-------------------------------------------------------------------------
    
    /**
     * Sanitize form data
     * 
     * @param array $form_data Raw form data
     * @return array{fields: array, settings: object} Sanitized form data
     */
    private function sanitize_form_data($form_data) {
        if (!is_array($form_data)) {
            return [
                'fields'   => [],
                'settings' => (object) []
            ];
        }

        $sanitized_fields = $this->sanitize_fields($form_data['fields'] ?? []);
        $sanitized_settings = $this->sanitize_settings($form_data['settings'] ?? []);

        return [
            'fields'   => $sanitized_fields,
            'settings' => (object) $sanitized_settings
        ];
    }

    /**
     * Sanitize fields
     * 
     * @param array $fields Fields to sanitize
     * @return array Sanitized fields
     */
    private function sanitize_fields($fields) {
        if (!is_array($fields)) {
            return [];
        }
        
        $sanitized_fields = [];
        
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $sanitized_field = [
                'id'   => sanitize_key($field['id'] ?? ''),
                'type' => sanitize_key($field['type'] ?? ''),
            ];

            // Sanitize field settings
            if (!empty($field['settings']) && is_array($field['settings'])) {
                $sanitized_field['settings'] = $this->sanitize_field_settings($field['settings']);
            }

            $sanitized_fields[] = $sanitized_field;
        }
        
        return $sanitized_fields;
    }

    /**
     * Sanitize field settings
     * 
     * @param array $settings Settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_field_settings($settings) {
        $sanitized_settings = [];
        
        foreach ($settings as $setting) {
            if (!is_array($setting)) {
                continue;
            }
            
            $key = sanitize_key($setting['id'] ?? '');
            $value = $this->sanitize_setting_value($setting['value'] ?? '', $setting['type'] ?? '');

            // if (!empty($key)) {
                $sanitized_settings[$key] = $value;
            // }
        }

        return $sanitized_settings;
    }

    /**
     * Sanitize form settings
     * 
     * @param array|object $settings Settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_settings($settings) {
        if (is_object($settings)) {
            $settings = (array) $settings;
        }
        
        if (!is_array($settings)) {
            return [];
        }
        
        $sanitized_settings = [];
        
        foreach ($settings as $section_key => $section) {
            if (!is_array($section)) {
                continue;
            }

            $sanitized_section = [
                'id' => sanitize_key($section['id'] ?? ''),
            ];

            if (!empty($section['settings']) && is_array($section['settings'])) {
                $sanitized_section['settings'] = $this->sanitize_field_settings($section['settings']);
            }

            $sanitized_settings[$section_key] = $sanitized_section;
        }
        
        return $sanitized_settings;
    }

    /**
     * Sanitize setting value based on type
     * 
     * @param mixed $value Value to sanitize
     * @param string $type Setting type
     * @return mixed Sanitized value
     */
    private function sanitize_setting_value($value, $type) {
        switch ($type) {
            case 'switch':
                return (bool) $value;
                
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'repeater':
                return $this->sanitize_recursive($value);
                
            case 'select':
                if (is_array($value)) {
                    // For arrays, map sanitize_key
                    return array_map('sanitize_key', $value);
                }
                // For mask format options with special characters, preserve them
                if (strpos($value, '/') !== false || strpos($value, ':') !== false || 
                    strpos($value, '.') !== false || strpos($value, ' ') !== false) {
                    return $value;
                }
                return sanitize_key($value);
                
            case 'names':
                return $this->sanitize_names_field($value);
                
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Sanitize names field type
     * 
     * @param mixed $value Value to sanitize
     * @return array Sanitized names field
     */
    private function sanitize_names_field($value) {
        if (!is_array($value)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($value as $key => $name_field) {
            if (is_array($name_field)) {
                $sanitized[$key] = [
                    'label'       => sanitize_text_field($name_field['label'] ?? ''),
                    'placeholder' => sanitize_text_field($name_field['placeholder'] ?? ''),
                    'value'       => sanitize_text_field($name_field['value'] ?? ''),
                    'message'     => sanitize_text_field($name_field['message'] ?? ''),
                    'required'    => (bool)($name_field['required'] ?? false),
                    'required_message' => sanitize_text_field($name_field['required_message'] ?? ''),
                ];
            }
        }
        
        return $sanitized;
    }

    /**
     * Recursively sanitize array values
     * 
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    private function sanitize_recursive($value) {
        if (!is_array($value)) {
            return sanitize_text_field($value);
        }

        return array_map(function($item) {
            return $this->sanitize_recursive($item);
        }, $value);
    }
}