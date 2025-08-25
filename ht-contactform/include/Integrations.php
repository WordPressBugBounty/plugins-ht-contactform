<?php

namespace HTContactForm;

use HTContactFormAdmin\Includes\Services\Webhook;
use HTContactForm\Integrations\Mailchimp;
use HTContactForm\Integrations\Slack;
use HTContactForm\Integrations\Discord;
use HTContactForm\Integrations\ActiveCampaign;
use HTContactForm\Integrations\MailerLite;
use HTContactForm\Integrations\Zapier;
use HTContactForm\Integrations\SupportGenix;
use HTContactForm\Integrations\ConstantContact;
use HTContactForm\Integrations\Brevo;
use HTContactForm\Integrations\Insightly;

/**
 * Integrations Class
 * 
 * Handles integrations functionality for sending form data to external services 
 * like webhook, Mailchimp, Slack, etc.
 */
class Integrations {

    /** @var self|null Singleton instance */
    private static $instance = null;

    private $integrations_settings = [];
    
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
        $this->integrations_settings = get_option('ht_form_integrations', []);
        add_action('ht_form/after_submission', [$this, 'process_integrations'], 10, 3);
    }

    /**
     * Process all enabled integrations for a form submission
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function process_integrations($form, $form_data, $meta) {
        // Process each integration type
        $this->webhook($form, $form_data, $meta);
        $this->mailchimp($form, $form_data, $meta);
        $this->slack($form, $form_data, $meta);
        $this->discord($form, $form_data, $meta);
        $this->activecampaign($form, $form_data, $meta);
        $this->mailerlite($form, $form_data, $meta);
        $this->zapier($form, $form_data, $meta);
        if(class_exists('Apbd_wps_ht_contact_form')) {
            $this->supportgenix($form, $form_data, $meta);
        }
        $this->constantcontact($form, $form_data, $meta);
        $this->brevo($form, $form_data, $meta);
        $this->insightly($form, $form_data, $meta);
        // Allow other integrations to be processed
        do_action('ht_form/process_custom_integrations', $form, $form_data, $meta);
    }

    /**
     * Send data to webhook
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function webhook($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if webhook integration is enabled globally
            if (empty($this->integrations_settings['webhook']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'webhook');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each webhook integration
            $webhook = Webhook::get_instance($form, $form_data, $meta);
            foreach ($integration_list as $integration) {
                $result = $webhook->send((object) $integration);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Webhook Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Webhook Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to Mailchimp
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function mailchimp($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if Mailchimp integration is enabled globally and API key exists
            if (empty($this->integrations_settings['mailchimp']['enabled']) || 
                empty($this->integrations_settings['mailchimp']['api_key'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'mailchimp');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each Mailchimp integration
            $mailchimp = Mailchimp::get_instance($this->integrations_settings['mailchimp']['api_key']);
            foreach ($integration_list as $integration) {
                $result = $mailchimp->subscribe((object) $integration, $form, $form_data, $meta);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Mailchimp Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Mailchimp Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to Slack
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function slack($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if Slack integration is enabled globally
            if (empty($this->integrations_settings['slack']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'slack');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each Slack integration
            $slack = Slack::get_instance($form, $form_data, $meta);
            foreach ($integration_list as $integration) {
                $result = $slack->send((object) $integration);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Slack Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Slack Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }


    /**
     * Send data to Discord
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function discord($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if Discord integration is enabled globally
            if (empty($this->integrations_settings['discord']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'discord');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each Discord integration
            $discord = Discord::get_instance($form, $form_data, $meta);
            foreach ($integration_list as $integration) {
                $result = $discord->send((object) $integration);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Discord Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Discord Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to ActiveCampaign
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function activecampaign($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if ActiveCampaign integration is enabled globally and API key exists
            if (empty($this->integrations_settings['activecampaign']['enabled']) || 
                empty($this->integrations_settings['activecampaign']['api_key'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'activecampaign');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each ActiveCampaign integration
            $activecampaign = ActiveCampaign::get_instance($this->integrations_settings['activecampaign']['api_key']);
            foreach ($integration_list as $integration) {
                $result = $activecampaign->subscribe((object) $integration, $form, $form_data, $meta);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form ActiveCampaign Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form ActiveCampaign Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to MailerLite
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function mailerlite($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if MailerLite integration is enabled globally and API key exists
            if (empty($this->integrations_settings['mailerlite']['enabled']) || 
                empty($this->integrations_settings['mailerlite']['api_key'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'mailerlite');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each MailerLite integration
            $mailerlite = MailerLite::get_instance($this->integrations_settings['mailerlite']['api_key']);
            foreach ($integration_list as $integration) {
                $result = $mailerlite->subscribe((object) $integration, $form, $form_data, $meta);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form MailerLite Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form MailerLite Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to Zapier
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function zapier($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if Zapier integration is enabled globally
            if (empty($this->integrations_settings['zapier']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'zapier');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each Zapier integration
            $zapier = Zapier::get_instance($form, $form_data, $meta);
            foreach ($integration_list as $integration) {
                $result = $zapier->send((object) $integration);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Zapier Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Zapier Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to SupportGenix
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function supportgenix($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if SupportGenix integration is enabled globally
            if (empty($this->integrations_settings['supportgenix']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'supportgenix');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each SupportGenix integration
            $supportgenix = new SupportGenix($form, $form_data, $meta);
            foreach ($integration_list as $integration) {
                $result = $supportgenix->send((object) $integration);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form SupportGenix Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form SupportGenix Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to ConstantContact
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function constantcontact($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if ConstantContact integration is enabled globally
            if (empty($this->integrations_settings['constantcontact']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'constantcontact');
            if (empty($integration_list)) {
                return;
            }
            
            // Process each ConstantContact integration
            $constantcontact = new ConstantContact($form, $form_data, $meta);
            foreach ($integration_list as $integration) {
                $result = $constantcontact->subscribe((object) $integration);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form ConstantContact Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form ConstantContact Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to Brevo
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function brevo($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if Brevo integration is enabled globally
            if (empty($this->integrations_settings['brevo']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'brevo');
            if (empty($integration_list)) {
                return;
            }
            // Process each Brevo integration
            $brevo = new Brevo();
            foreach ($integration_list as $integration) {
                $result = $brevo->subscribe((object) $integration, $form, $form_data, $meta);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Brevo Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Brevo Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Send data to Insightly
     * 
     * @param array $form Form configuration
     * @param array $form_data Submitted form data
     * @param array $meta Entry metadata
     * @return void
     */
    public function insightly($form, $form_data, $meta) {
        try {
            if (empty($this->integrations_settings) || empty($form['id'])) {
                return;
            }
            
            // Check if Insightly integration is enabled globally
            if (empty($this->integrations_settings['insightly']['enabled'])) {
                return;
            }
            
            // Get form-specific integrations
            $integration_list = $this->get_form_integrations($form['id'], 'insightly');
            if (empty($integration_list)) {
                return;
            }
            // Process each Insightly integration
            $insightly = new Insightly();
            foreach ($integration_list as $integration) {
                $result = $insightly->subscribe((object) $integration, $form, $form_data, $meta);
                if (is_wp_error($result)) {
                    error_log(sprintf(
                        'HT Contact Form Insightly Error (Form ID: %s): %s',
                        $form['id'],
                        $result->get_error_message()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'HT Contact Form Insightly Exception (Form ID: %s): %s',
                $form['id'] ?? 'unknown',
                $e->getMessage()
            ));
        }
    }

    /**
     * Get form-specific integrations of a specific type
     * 
     * @param int $form_id Form ID
     * @param string $integration_type Integration type (webhook, mailchimp, slack)
     * @return array Array of enabled integrations of the specified type
     */
    private function get_form_integrations($form_id, $integration_type) {
        $integration_list = get_post_meta($form_id, 'integrations', true);
        if (empty($integration_list)) {
            return [];
        }
        
        $decoded_list = json_decode($integration_list, true);
        if (!is_array($decoded_list)) {
            return [];
        }
        
        return array_filter($decoded_list, function($integration) use ($integration_type) {
            return $integration['type'] === $integration_type && $integration['enabled'];
        });
    }
}