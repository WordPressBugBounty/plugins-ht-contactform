<?php

namespace HTContactFormAdmin\Includes;

/**
 * Mailer Class
 * 
 * Handles mailer functions for the plugin.
 * 
 * @package HTContactFormAdmin\Includes
 * @since 1.0.0
 */
class Mailer {
    //-------------------------------------------------------------------------
    // PROPERTIES
    //-------------------------------------------------------------------------

    /** @var array|null Form data */
    private $form = null;
    /** @var array|null Form fields */
    private $fields = [];
    /** @var object|null Form settings */
    private $settings = null;
    /** @var object|null Notification settings */
    private $notification = null;
    /** @var object|null Global settings */
    private $global = null;

    /** @var array|null Form data */
    private $form_data = null; 

    /** @var array|null Entry metadata */
    private $meta = null;

    /** @var array|null Form Body Tags */
    private $body_tags = null;

    /** @var self|null Singleton instance */
    private static $instance = null;

    //-------------------------------------------------------------------------
    // INITIALIZATION
    //-------------------------------------------------------------------------
    
    /**
     * Get singleton instance
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return self
     */
    public static function get_instance($form = null, $form_data = null, $meta = null) {
        if (!isset(self::$instance)) {
            self::$instance = new self($form, $form_data, $meta);
        }
        return self::$instance;
    }

    /**
     * Constructor
     * 
     * Initializes the class and sets up WordPress hooks
     */
    public function __construct($form = null, $form_data = null, $meta = null) {
        if(!empty($form)) {
            $this->form = $form;
            $this->fields = !empty($form['fields']) ? $form['fields'] : [];
            $this->settings = !empty($form['settings']) ? $form['settings'] : (object)[];
            $this->notification = !empty($form['settings']->notification['settings']) ? (object) $form['settings']->notification['settings'] : (object)[];
            $this->global = get_option('ht_form_global_settings', []);
        }
        if(!empty($form_data)) {
            $this->form_data = $form_data;
        }
        if(!empty($meta)) {
            $this->meta = $meta;
        }
        $this->body_tags = $this->get_body_tags();
    }

    /**
     * Get body tags
     * 
     * @return array Body tags
     */
    public function get_body_tags() {
        return [
            [
                'id' => 'admin_email',
                'label' => __('Admin Email', 'ht-contactform'),
                'value' => '{admin_email}',
                'group' => 'Others',
                'callback' => 'parse_wp'
            ],
            [
                'id' => 'form_id',
                'label' => __('Form ID', 'ht-contactform'),
                'value' => '{form_id}',
                'group' => 'Others',
                'callback' => 'parse_form'
            ],
            [
                'id' => 'form_title',
                'label' => __('Form Title', 'ht-contactform'),
                'value' => '{form_title}',
                'group' => 'Others',
                'callback' => 'parse_form'
            ],
            [
                'id' => 'post_title',
                'label' => __('Embedded Post/Page Title', 'ht-contactform'),
                'value' => '{post_title}',
                'group' => 'Others',
                'callback' => 'parse_post'
            ],
            [
                'id' => 'post_url',
                'label' => __('Embedded Post/Page URL', 'ht-contactform'),
                'value' => '{post_url}',
                'group' => 'Others',
                'callback' => 'parse_post'
            ],
            [
                'id' => 'post_id',
                'label' => __('Embedded Post/Page ID', 'ht-contactform'),
                'value' => '{post_id}',
                'group' => 'Others',
                'callback' => 'parse_post'
            ],
            [
                'id' => 'date',
                'label' => __('Date', 'ht-contactform'),
                'value' => '{date format="m/d/Y"}',
                'group' => 'Others',
                'callback' => 'parse_date'
            ],
            [
                'id' => 'query_var',
                'label' => __('Query String Variable', 'ht-contactform'),
                'value' => '{query_var key=""}',
                'group' => 'Others',
                'callback' => 'parse_query_var'
            ],
            [
                'id' => 'user_id',
                'label' => __('User ID', 'ht-contactform'),
                'value' => '{user_id}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'user_display',
                'label' => __('User Display Name', 'ht-contactform'),
                'value' => '{user_display}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'user_full_name',
                'label' => __('User Full Name', 'ht-contactform'),
                'value' => '{user_full_name}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'user_first_name',
                'label' => __('User First Name', 'ht-contactform'),
                'value' => '{user_first_name}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'user_last_name',
                'label' => __('User Last Name', 'ht-contactform'),
                'value' => '{user_last_name}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'user_email',
                'label' => __('User Email', 'ht-contactform'),
                'value' => '{user_email}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'user_meta',
                'label' => __('User Meta', 'ht-contactform'),
                'value' => '{user_meta key=""}',
                'group' => 'Others',
                'callback' => 'parse_user'
            ],
            [
                'id' => 'author_id',
                'label' => __('Author ID', 'ht-contactform'),
                'value' => '{author_id}',
                'group' => 'Others',
                'callback' => 'parse_author'
            ],
            [
                'id' => 'author_display',
                'label' => __('Author Name', 'ht-contactform'),
                'value' => '{author_display}',
                'group' => 'Others',
                'callback' => 'parse_author'
            ],
            [
                'id' => 'author_email',
                'label' => __('Author Email', 'ht-contactform'),
                'value' => '{author_email}',
                'group' => 'Others',
                'callback' => 'parse_author'
            ],
            [
                'id' => 'url_referer',
                'label' => __('Referrer URL', 'ht-contactform'),
                'value' => '{url_referer}',
                'group' => 'Others',
                'callback' => 'parse_url'
            ],
            [
                'id' => 'url_login',
                'label' => __('Login URL', 'ht-contactform'),
                'value' => '{url_login}',
                'group' => 'Others',
                'callback' => 'parse_url'
            ],
            [
                'id' => 'url_logout',
                'label' => __('Logout URL', 'ht-contactform'),
                'value' => '{url_logout}',
                'group' => 'Others',
                'callback' => 'parse_url'
            ],
            [
                'id' => 'url_register',
                'label' => __('Register URL', 'ht-contactform'),
                'value' => '{url_register}',
                'group' => 'Others',
                'callback' => 'parse_url'
            ],
            [
                'id' => 'url_lost_password',
                'label' => __('Lost Password URL', 'ht-contactform'),
                'value' => '{url_lost_password}',
                'group' => 'Others',
                'callback' => 'parse_url'
            ],
            [
                'id' => 'site_title',
                'label' => __('Site Title', 'ht-contactform'),
                'value' => '{site_title}',
                'group' => 'Others',
                'callback' => 'parse_wp'
            ],
            [
                'id' => 'site_url',
                'label' => __('Site URL', 'ht-contactform'),
                'value' => '{site_url}',
                'group' => 'Others',
                'callback' => 'parse_wp'
            ]
        ];
    }

    /**
     * Filter content for predefined variable like {admin_email}, {site_title}, {site_url}, {current_date}, {current_time}
     * 
     * @param string $data Content
     * @return string Filtered content
     */
    private function filter_vars($data) {
        // Process {input.field_name} patterns
        if(str_contains($data, '{input.')) {
            preg_match_all('/{input\.([^}]+)}/', $data, $matches, PREG_SET_ORDER);
            foreach($matches as $match) {
                $placeholder = $match[0]; // Full match like {input.name}
                $field_name = $match[1]; // Captured group like "name"
                
                if(isset($this->form_data[$field_name])) {
                    $replacement = $this->form_data[$field_name];
                    if(is_array($replacement)) {
                        $replacement = implode(', ', array_map('sanitize_text_field', $replacement));
                    } else {
                        $replacement = sanitize_text_field($replacement);
                    }
                    $data = str_replace($placeholder, $replacement, $data);
                }
            }
        }
        
        // Process other tags
        foreach($this->body_tags as $tag) {
            if(strpos($data, $tag['value']) !== false) {
                // Extract the tag pattern from the data string
                $pattern = $tag['value'];
                
                // Get the replacement value using the callback
                $replacement = call_user_func([$this, $tag['callback']], $pattern);
                
                // Replace only the specific tag pattern in the data string
                $data = str_replace($pattern, $replacement, $data);
            }
        }
        
        return $data;
    }

    //-------------------------------------------------------------------------
    // MAIN OPERATIONS
    //-------------------------------------------------------------------------
    

    /**
     * Send email notification
     * 
     * @return bool Whether the email was sent successfully
     */
    public function send() {
        // Set recipient
        $to = $this->get_recipient();

        // Set subject
        /* translators: %s: form title */
        $subject = $this->notification->form_subject ?? sprintf(__('New Form Entry - %s', 'ht-contactform'), $this->form['title']);
        
        $subject = $this->filter_vars($subject);
        $notification_body = $this->notification->form_email_body;
        $lines = explode("\n", $notification_body); // Split into lines
        $data = [];
        foreach($lines as $line) {
            if(str_contains($line, '{all_fields}')) {
                $data = array_merge($data, $this->form_data);
            } else {
                $data[] = $this->filter_vars($line);
            }
        }
        
        $data = array_filter($data, function($key) {
            return $key !== 'form_id' && $key !== '_wp_http_referer' && $key !== 'ht_form_timestamp';
        }, ARRAY_FILTER_USE_KEY);


        // Filter out empty arrays
        $data = array_filter($data, function($value) {
            if (is_array($value)) {
                // Filter out empty values from the array
                $filtered = array_filter($value);
                // Only keep arrays that have values after filtering
                return !empty($filtered);
            }
            return true; // Keep non-array values
        });

        $template = 1;
        if(!empty($this->notification->template)) {
            $template = $this->notification->template;
        } else if(!empty($this->global['email']['template'])) {
            $template = $this->global['email']['template'];
        }
        
        // Load the email template using the load_email_template method
        $content = $this->load_template("email-{$template}", [
            'subject' => $subject,
            'form' => $this->form,
            'data' => $data,
            'meta' => $this->meta,
            'footer_text' => $this->global['email']['footer_text']
        ]);
        
        // Set up email headers
        $headers = $this->get_headers();
        
        // Trigger before email send action
        do_action('ht_form/before_email_send', $this->form, $this->form_data);

        // Send the email
        $send = wp_mail($to, $subject, $content, $headers);

        // Trigger after email send action
        do_action('ht_form/email_sent', $send, $this->form, $this->form_data);

        return $send;
    }

    /**
     * Get email headers
     * 
     * @return array Email headers
     */
    private function get_headers() {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_form_name() . ' <' . $this->get_form_email() . '>',
            'Reply-To: ' . $this->get_form_name() . ' <' . $this->get_form_reply_to() . '>'
        ];
        $headers = apply_filters('ht_form/email_headers', $headers, $this->form, $this->form_data);
        return $headers;
    }

    /**
     * Get email recipient
     * 
     * @return string Email recipient
     */
    private function get_recipient() {
        $emails = [];
        if(!empty($this->notification->form_send_to_email) && str_contains($this->notification->form_send_to_email, ',')) {
            $emails = explode(', ', $this->notification->form_send_to_email);
            $emails = array_map(function($email) {
                if(is_email($email)) {
                    return sanitize_email($email);
                }
                return sanitize_email(get_option('admin_email'));
            }, $emails);
            return implode(',', array_unique($emails));
        }
        if(isset($this->notification->form_send_to_email) && is_email($this->notification->form_send_to_email)) {
            return sanitize_email($this->notification->form_send_to_email);
        }
        return sanitize_email(get_option('admin_email')); 
    }

    /**
     * Get email sender name
     * 
     * @return string Email sender name
     */
    private function get_form_name() {
        if(!isset($this->notification->form_name)) {
            return sanitize_text_field(get_bloginfo('name'));
        }
        // $fields = $this->fields;
        // if(str_contains($this->notification->form_name, '{') && str_contains($this->notification->form_name, '}')) {
        //     foreach($fields as $field) {
        //         if(isset($field['name_attribute']) && !empty($field['name_attribute']) && str_contains($this->notification->form_name, $field['name_attribute'])) {
        //             return sanitize_text_field($this->form_data[$field['name_attribute']]);
        //         }
        //     }
        // }
        return sanitize_text_field($this->filter_vars($this->notification->form_name));
    }

    /**
     * Get email sender email
     * 
     * @return string Email sender email
     */
    private function get_form_email() {
        if(isset($this->notification->form_email) && is_email($this->notification->form_email)) {
            return sanitize_email($this->notification->form_email);
        }
        if($this->notification->form_email !== '{admin_email}' && !empty($this->notification->form_email)) {
            foreach($this->fields as $field) {
                if($field['type'] === 'email' && isset($field['name_attribute']) && !empty($field['name_attribute']) && 
                   str_contains($this->notification->form_email, $field['name_attribute']) && 
                   isset($this->form_data[$field['name_attribute']])) {
                    return sanitize_email($this->form_data[$field['name_attribute']]);
                }
            }
        }
        return sanitize_email(get_option('admin_email'));
    }

    /**
     * Get email reply-to address
     * 
     * @return string Email reply-to address
     */
    private function get_form_reply_to() {
        if(isset($this->notification->form_reply_to) && is_email($this->notification->form_reply_to)) {
            return sanitize_email($this->notification->form_reply_to);
        }
        if($this->notification->form_reply_to !== '{admin_email}' && !empty($this->notification->form_reply_to)) {
            foreach($this->fields as $field) {
                if($field['type'] === 'email' && isset($field['name_attribute']) && !empty($field['name_attribute']) && 
                   str_contains($this->notification->form_reply_to, $field['name_attribute']) && 
                   isset($this->form_data[$field['name_attribute']])) {
                    return sanitize_email($this->form_data[$field['name_attribute']]);
                }
            }
        }
        return $this->get_form_email();
    }

    /**
     * Load an email template from the plugin's templates directory
     * 
     * @param string $template_name Template name without file extension
     * @param array $args Arguments to pass to the template
     * @return string The template content or empty string if template not found
     */
    private function load_template($template_name, $args = []) {
        // Get the template file path
        $template_path = HTCONTACTFORM_PL_PATH . 'templates/email/' . $template_name . '.php';
        
        // Check if the template file exists
        if (!file_exists($template_path)) {
            return '';
        }
        
        // Start output buffering
        ob_start();
        
        // Include the template file with the provided arguments
        include $template_path;
        
        // Get and return the buffered content
        return ob_get_clean();
    }

    /**
     * Parse WordPress variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_wp($value) {
        if($value === '{admin_email}') {
            return sanitize_email(get_option('admin_email'));
        }
        if($value === '{site_title}') {
            return sanitize_text_field(get_bloginfo('name'));
        }
        if($value === '{site_url}') {
            return sanitize_text_field(get_bloginfo('url'));
        }
        return $value;
    }

    /**
     * Parse URL variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_url($value) {
        if($value === '{url_referer}' && isset($_SERVER['HTTP_REFERER'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER']));
        }
        if($value === '{url_login}') {
            return sanitize_text_field(wp_login_url());
        }
        if($value === '{url_logout}') {
            return sanitize_text_field(wp_logout_url());
        }
        if($value === '{url_register}') {
            return sanitize_text_field(wp_registration_url());
        }
        if($value === '{url_lost_password}') {
            return sanitize_text_field(wp_lostpassword_url());
        }
        return $value;
    }

    /**
     * Parse form variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_form($value) {
        if($value === '{form_title}') {
            return sanitize_text_field($this->form['title']);
        }
        if($value === '{form_id}') {
            return sanitize_text_field($this->form['id']);
        }
        return $value;
    }

    /**
     * Parse post variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_post($value) {
        if($value === '{post_title}') {
            return sanitize_text_field(get_the_title());
        }
        if($value === '{post_url}') {
            return sanitize_text_field(get_permalink());
        }
        if($value === '{post_id}') {
            return sanitize_text_field(get_the_ID());
        }
        return $value;
    }

    /**
     * Parse user variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_user($value) {
        $current_user = wp_get_current_user();
        
        if($value === '{user_id}') {
            return sanitize_text_field($current_user->ID);
        }
        if($value === '{user_email}') {
            return sanitize_email($current_user->user_email);
        }
        if($value === '{user_display}') {
            return sanitize_text_field($current_user->display_name);
        }
        if($value === '{user_first_name}') {
            return sanitize_text_field($current_user->first_name);
        }
        if($value === '{user_last_name}') {
            return sanitize_text_field($current_user->last_name);
        }
        if($value === '{user_full_name}') {
            return sanitize_text_field("{$current_user->first_name} {$current_user->last_name}");
        }
        if(str_contains($value,"user_meta")) {
            $meta_key = substr($value, 16, -2);
            return sanitize_text_field(get_user_meta($current_user->ID, $meta_key, true));
        }
        return $value;
    }

    /**
     * Parse author variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_author($value) {
        if($value === '{author_id}') {
            return sanitize_text_field(get_the_author_meta('ID'));
        }
        if($value === '{author_email}') {
            return sanitize_email(get_the_author_meta('user_email'));
        }
        if($value === '{author_display}') {
            return sanitize_text_field(get_the_author_meta('display_name'));
        }
        return $value;
    }

    /**
     * Parse date variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_date($value) {
        if(str_contains($value, '{date')) {
            $format = substr($value, 14, -2);
            return sanitize_text_field(gmdate($format));
        }
        return $value;
    }

    /**
     * Parse query variables
     * 
     * @param string $value Value to parse
     * @return string The parsed value
     */
    private function parse_query_var($value) {
        if(str_contains($value,'{query_var')) {
            $key = substr($value, 16, -2);
            return sanitize_text_field(get_query_var($key));
        }
        return $value;
    }
}