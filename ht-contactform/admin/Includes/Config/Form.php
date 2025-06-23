<?php
namespace HTContactFormAdmin\Includes\Config;

use HTContactFormAdmin\Includes\Config\Field;
use HTContactFormAdmin\Includes\Config\Styler;
use HTContactFormAdmin\Includes\Api\Endpoints\Integrations\Mailchimp;
use HTContactFormAdmin\Includes\Api\Endpoints\Integrations\ActiveCampaign;
use HTContactFormAdmin\Includes\Api\Endpoints\Integrations\MailerLite;

class Form {

    private $global_settings = [];
    private $integrations = [];
    private $mailchimp = null;
    private $activeCampaign = null;
    private $mailerlite = null;

    private static $instance = null;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static $field = null;
    public static $countries = null;
    public static $styler = null;

    public function __construct() {
        self::$field = Field::get_instance();
        self::$countries = Countries::get_instance();
        self::$styler = Styler::get_instance();
        $this->global_settings = get_option('ht_form_global_settings', []);
        $this->integrations = get_option('ht_form_integrations', []);
        $this->mailchimp = Mailchimp::get_instance();
        $this->activeCampaign = ActiveCampaign::get_instance();
        $this->mailerlite = MailerLite::get_instance();
    }

    /**
     * Get available form fields
     * 
     * @return array Array of form fields
     */
    public function fields(): array {
        return apply_filters('ht_form_fields', [
            [
                'id' => 'input',
                'type' => 'input',
                'label' => __('Simple Text', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(),
                    self::$field->label(),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->prefix_label(),
                    self::$field->suffix_label(),
                    self::$field->name_attribute(),
                    self::$field->max_length(),
                ],
            ],
            [
                'id' => 'name',
                'type' => 'name',
                'label' => __('Name', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Name', 'ht-contactform')]),
                    self::$field->name_format(),
                    self::$field->names(),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'name']),
                ],
            ],
            [
                'id' => 'textarea',
                'type' => 'textarea',
                'label' => __('Textarea', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Message', 'ht-contactform')]),
                    self::$field->label(['value' => __('Message', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'message']),
                    self::$field->max_length(),
                ],
            ],
            [
                'id' => 'mask_input',
                'type' => 'mask_input',
                'label' => __('Mask Input', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Mask', 'ht-contactform')]),
                    self::$field->label(['value' => __('Mask Input', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(),
                    self::$field->mask(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->prefix_label(),
                    self::$field->suffix_label(),
                    self::$field->name_attribute(['value' => 'input_mask']),
                ],
            ],
            [
                'id' => 'dropdown',
                'type' => 'dropdown',
                'label' => __('Dropdown', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Dropdown', 'ht-contactform')]),
                    self::$field->label(['value' => __('Dropdown', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(['value' => 'Select an option']),
                    self::$field->options(),
                    self::$field->searchable(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'dropdown']),
                ],
            ],
            [
                'id' => 'multiple_choices',
                'type' => 'multiple_choices',
                'label' => __('Multiple Choices', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Multiple Choices', 'ht-contactform')]),
                    self::$field->label(['value' => __('Multiple Choices', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(),
                    self::$field->options([
                        'option_type' => 'checkbox',
                    ]),
                    self::$field->searchable(),
                    self::$field->max_selection(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'multi_select']),
                ],
            ],
            [
                'id' => 'checkboxes',
                'type' => 'checkboxes',
                'label' => __('Checkboxes', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Checkboxes', 'ht-contactform')]),
                    self::$field->label(['value' => __('Checkboxes', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->options([
                        'option_type' => 'checkbox',
                    ]),
                    self::$field->layout(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'checkboxes']),
                ],
            ],
            [
                'id' => 'phone',
                'type' => 'phone',
                'label' => __('Phone', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Phone', 'ht-contactform')]),
                    self::$field->label(['value' => __('Phone', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(['value' => __('Mobile Number', 'ht-contactform')]),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->create([
                        'id' => 'validate',
                        'label' => __('Validate Phone Number', 'ht-contactform'),
                        'type' => 'switch',
                        'info' => __('Toggle to enable phone number validation.', 'ht-contactform'),
                        'value' => false,
                    ]),
                    self::$field->create([
                        'id' => 'validate_message',
                        'label' => __('Validation Error Message', 'ht-contactform'),
                        'info' => __('Message will be shown if validation fails for phone number. Leave empty to use global message. Configure global message from: Global Settings -> Validation Messages.', 'ht-contactform'),
                        'value' => 'Invalid phone number.',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'validate',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'auto_country_select',
                        'label' => __('Enable Auto Country Select', 'ht-contactform'),
                        'type' => 'switch',
                        'info' => __('If enable auto country select, it will automatically select the country based on the user IP address.', 'ht-contactform'),
                        'value' => false,
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'validate',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'default_country',
                        'label' => __('Default Country', 'ht-contactform'),
                        'type' => 'select',
                        'searchable' => true,
                        'value' => 'us',
                        'options' => self::$countries->get_all(),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'validate',
                                    'value' => true,
                                    'compare' => '==',
                                ],
                                [
                                    'id' => 'auto_country_select',
                                    'value' => false,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'country_list_type',
                        'label' => __('Country List', 'ht-contactform'),
                        'type' => 'radio_button',
                        'value' => 'all',
                        'options' => [
                            [
                                'value' => 'all',
                                'label' => __('Show All', 'ht-contactform'),
                            ],
                            [
                                'value' => 'include',
                                'label' => __('Show Selected', 'ht-contactform'),
                            ],
                            [
                                'value' => 'exclude',
                                'label' => __('Hide Selected', 'ht-contactform'),
                            ],
                        ],
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'validate',
                                    'value' => true,
                                    'compare' => '==',
                                ],
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'country_list',
                        'type' => 'select',
                        'multiple' => true,
                        'searchable' => true,
                        'value' => [],
                        'options' => self::$countries->get_all(),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'validate',
                                    'value' => true,
                                    'compare' => '==',
                                ],
                                [
                                    'id' => 'country_list_type',
                                    'value' => 'all',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->prefix_label(),
                    self::$field->suffix_label(),
                    self::$field->name_attribute(['value' => 'phone']),
                ],
            ],
            [
                'id' => 'number',
                'type' => 'number',
                'label' => __('Number', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Number', 'ht-contactform')]),
                    self::$field->label(['value' => __('Number', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->min(),
                    self::$field->max(),
                    self::$field->step(),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->prefix_label(),
                    self::$field->suffix_label(),
                    self::$field->name_attribute(['value' => 'number']),
                ],
            ],
            [
                'id' => 'gdpr',
                'type' => 'gdpr',
                'label' => __('GDPR Agreement', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('GDPR Agreement', 'ht-contactform')]),
                    self::$field->create([
                        'id' => 'required_message',
                        'label' => __('Required Error Message', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for Required. Leave empty to use global message. Configure Global Message from: Global settings > Validation Messages', 'ht-contactform'),
                        'value' => __('This field is required', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'description',
                        'label' => __('Description', 'ht-contactform'),
                        'type' => 'textarea',
                        'info' => __('This message will be shown with GDPR agreement checkbox.', 'ht-contactform'),
                        'value' => __('I agree to allow this website to store my submitted information in order to respond to my inquiry.', 'ht-contactform'),
                    ]),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->name_attribute(['value' => 'gdpr_agreement']),
                ],
            ],
            [
                'id' => 'recaptcha',
                'type' => 'recaptcha',
                'label' => __('reCAPTCHA', 'ht-contactform'),
                'disabled' => empty($this->global_settings['captcha']['recaptcha_secret_key']) || empty($this->global_settings['captcha']['recaptcha_site_key']),
                'disabled_data' => [
                    'title' => __('Configuration Required', 'ht-contactform'),
                    'message' => __('reCAPTCHA is not configured, please configure it in Global Settings > Captcha', 'ht-contactform'),
                ],
                'settings' => [
                    self::$field->name_attribute(['value' => 'g-recaptcha-response', 'disabled' => true]),
                ],
            ],
            [
                'id' => 'email',
                'type' => 'email',
                'label' => __('Email', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Email', 'ht-contactform')]),
                    self::$field->label(['value' => __('Email', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->email_validation(),
                    self::$field->create([
                        'id' => 'email_validation_message',
                        'label' => __('Email Validation Error Message', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for Email. Leave empty to use global message. Configure Global Message from: Global settings > Validation Messages', 'ht-contactform'),
                        'value' => __('This field must contain a valid email', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'email_validation',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                    // self::$field->email_unique(),
                    // self::$field->create([
                    //     'id' => 'email_unique_message',
                    //     'label' => __('Validation Message for Duplicate Email', 'ht-contactform'),
                    //     'info' => __('This message will be shown if validation fails for Email. Leave empty to use global message. Configure Global Message from: Global settings > Validation Messages', 'ht-contactform'),
                    //     'value' => __('Email address need to be unique.', 'ht-contactform'),
                    //     'dependency' => [
                    //         'relation' => 'AND',
                    //         'rules' => [
                    //             [
                    //                 'id' => 'email_unique',
                    //                 'value' => true,
                    //                 'compare' => '==',
                    //             ]
                    //         ]
                    //     ],
                    // ]),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->prefix_label(),
                    self::$field->suffix_label(),
                    self::$field->name_attribute(['value' => 'email']),
                ],
            ],
            [
                'id' => 'password',
                'type' => 'password',
                'label' => __('Password', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Password', 'ht-contactform')]),
                    self::$field->label(['value' => __('Password', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(['value' => __('Password', 'ht-contactform')]),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'password']),
                ],
            ],
            [
                'id' => 'date_time',
                'type' => 'date_time',
                'label' => __('Date & Time', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Date & Time', 'ht-contactform')]),
                    self::$field->label(['value' => __('Date & Time', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(['value' => __('Select Date & Time', 'ht-contactform')]),
                    self::$field->create([
                        'id' => 'format',
                        'label' => __('Format', 'ht-contactform'),
                        'value' => 'Y-m-d H:i',
                        'info' => __('Select the format for the date and time.', 'ht-contactform'),
                        'type' => 'select',
                        'options' => [
                            [
                                'value' => 'Y-m-d',
                                'label' => 'Y-m-d (2025-05-18, ISO standard format)',
                            ],
                            [
                                'value' => 'd-m-Y',
                                'label' => 'd-m-Y (18-05-2025, European format)',
                            ],
                            [
                                'value' => 'm-d-Y',
                                'label' => 'm-d-Y (05-18-2025, US format)',
                            ],
                            [
                                'value' => 'Y/m/d',
                                'label' => 'Y/m/d (2025/05/18, ISO format with slashes)',
                            ],
                            [
                                'value' => 'd/m/Y',
                                'label' => 'd/m/Y (18/05/2025, European format with slashes)',
                            ],
                            [
                                'value' => 'm/d/Y',
                                'label' => 'm/d/Y (05/18/2025, US format with slashes)',
                            ],
                            [
                                'value' => 'F j, Y',
                                'label' => 'F j, Y (May 18, 2025, Full month with day)',
                            ],
                            [
                                'value' => 'j F Y',
                                'label' => 'j F Y (18 May 2025, Day with full month)',
                            ],
                            [
                                'value' => 'M j, Y',
                                'label' => 'M j, Y (May 18, 2025, Abbreviated month)',
                            ],
                            [
                                'value' => 'Y-m-d H:i',
                                'label' => 'Y-m-d H:i (2025-05-18 12:42, ISO with time)',
                            ],
                            [
                                'value' => 'd-m-Y H:i',
                                'label' => 'd-m-Y H:i (18-05-2025 12:42, European with time)',
                            ],
                            [
                                'value' => 'm-d-Y H:i',
                                'label' => 'm-d-Y H:i (05-18-2025 12:42, US with time)',
                            ],
                            [
                                'value' => 'F j, Y H:i',
                                'label' => 'F j, Y H:i (May 18, 2025 12:42, Full date with time)',
                            ],
                            [
                                'value' => 'Y-m-d H:i:s',
                                'label' => 'Y-m-d H:i:s (2025-05-18 12:42:31, ISO with seconds)',
                            ],
                            [
                                'value' => 'd-m-Y H:i:s',
                                'label' => 'd-m-Y H:i:s (18-05-2025 12:42:31, European with seconds)',
                            ],
                            [
                                'value' => 'm-d-Y H:i:s',
                                'label' => 'm-d-Y H:i:s (05-18-2025 12:42:31, US with seconds)',
                            ],
                            [
                                'value' => 'H:i',
                                'label' => 'H:i (12:42, 24-hour format)',
                            ],
                            [
                                'value' => 'h:i K',
                                'label' => 'h:i K (12:42 PM, 12-hour format with AM/PM)',
                            ],
                            [
                                'value' => 'H:i:s',
                                'label' => 'H:i:s (12:42:31, 24-hour format with seconds)',
                            ],
                            [
                                'value' => 'h:i:s K',
                                'label' => 'h:i:s K (12:42:31 PM, 12-hour format with seconds)',
                            ],
                            [
                                'value' => 'l, F j, Y',
                                'label' => 'l, F j, Y (Sunday, May 18, 2025, Full day and month)',
                            ],
                            [
                                'value' => 'D, M j, Y',
                                'label' => 'D, M j, Y (Sun, May 18, 2025, Abbreviated day and month)',
                            ],
                            [
                                'value' => 'j. F Y',
                                'label' => 'j. F Y (18. May 2025, European style with dot)',
                            ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'multiple',
                        'label' => __('Enable Multiple Selection', 'ht-contactform'),
                        'info' => __('Enabling multiple selection will disable the time picker and allow users to select multiple dates.', 'ht-contactform'),
                        'value' => false,
                        'type' => 'switch',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'format',
                                    'value' => 'H:i',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'format',
                                    'value' => 'h:i K',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'format',
                                    'value' => 'H:i:s',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'format',
                                    'value' => 'h:i:s A',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'range',
                                    'value' => true,
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'range',
                        'label' => __('Enable Range Selection', 'ht-contactform'),
                        'info' => __('Enabling range selection will disable the time picker and allow users to select a range of dates.', 'ht-contactform'),
                        'value' => false,
                        'type' => 'switch',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'format',
                                    'value' => 'H:i',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'format',
                                    'value' => 'h:i A',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'format',
                                    'value' => 'H:i:s',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'format',
                                    'value' => 'h:i:s A',
                                    'compare' => 'not_contains',
                                ],
                                [
                                    'id' => 'multiple',
                                    'value' => true,
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'date_time']),
                ],
            ],
            [
                'id' => 'country',
                'type' => 'country',
                'label' => __('Country List', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Country', 'ht-contactform')]),
                    self::$field->label(['value' => __('Country', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->placeholder(['value' => __('Select Country', 'ht-contactform')]),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->create([
                        'id' => 'auto_country_select',
                        'label' => __('Enable Auto Country Select', 'ht-contactform'),
                        'type' => 'switch',
                        'info' => __('If enable auto country select, it will automatically select the country based on the user IP address.', 'ht-contactform'),
                        'value' => false,
                    ]),
                    self::$field->create([
                        'id' => 'default_country',
                        'label' => __('Default Country', 'ht-contactform'),
                        'type' => 'select',
                        'searchable' => true,
                        'value' => 'us',
                        'options' => self::$countries->get_all(),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'auto_country_select',
                                    'value' => false,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'country_list_type',
                        'label' => __('Country List', 'ht-contactform'),
                        'type' => 'radio_button',
                        'value' => 'all',
                        'options' => [
                            [
                                'value' => 'all',
                                'label' => __('Show All', 'ht-contactform'),
                            ],
                            [
                                'value' => 'include',
                                'label' => __('Show Selected', 'ht-contactform'),
                            ],
                            [
                                'value' => 'exclude',
                                'label' => __('Hide Selected', 'ht-contactform'),
                            ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'country_list',
                        'type' => 'select',
                        'multiple' => true,
                        'searchable' => true,
                        'value' => [],
                        'options' => self::$countries->get_all(),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'country_list_type',
                                    'value' => 'all',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->value(),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'country']),
                ],
            ],
            [
                'id' => 'address',
                'type' => 'address',
                'label' => __('Address', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Address', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->create([
                        'id' => 'enable_address_line_1',
                        'type' => 'switch',
                        'label' => __('Enable Address Line 1', 'ht-contactform'),
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'address_line_1',
                        'type' => 'collapse',
                        'label' => __('Address Line 1', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_address_line_1',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                        'value' => [
                                'label' => 'Address Line 1',
                                'placeholder' => 'Address Line 1',
                                'value' => '',
                                'required' => false,
                                'required_message' => '',
                        ],
                        'fields' => [
                            self::$field->create([
                                'id' => 'label',
                                'label' => __('Label', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'placeholder',
                                'label' => __('Placeholder', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'help_message',
                                'label' => __('Help Message', 'ht-contactform'),
                                'type' => 'textarea',
                            ]),
                            self::$field->create([
                                'id' => 'default_value',
                                'label' => __('Default Value', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'required',
                                'label' => __('Required', 'ht-contactform'),
                                'type' => 'switch',
                            ]),
                            self::$field->create([
                                'id' => 'required_message',
                                'label' => __('Required Message', 'ht-contactform'),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'required',
                                            'value' => true,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'enable_address_line_2',
                        'type' => 'switch',
                        'label' => __('Enable Address Line 2', 'ht-contactform'),
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'address_line_2',
                        'type' => 'collapse',
                        'label' => __('Address Line 2', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_address_line_2',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                        'value' => [
                                'label' => 'Address Line 2',
                                'placeholder' => 'Address Line 2',
                                'value' => '',
                                'required' => false,
                                'required_message' => '',
                        ],
                        'fields' => [
                            self::$field->create([
                                'id' => 'label',
                                'label' => __('Label', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'placeholder',
                                'label' => __('Placeholder', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'help_message',
                                'label' => __('Help Message', 'ht-contactform'),
                                'type' => 'textarea',
                            ]),
                            self::$field->create([
                                'id' => 'default_value',
                                'label' => __('Default Value', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'required',
                                'label' => __('Required', 'ht-contactform'),
                                'type' => 'switch',
                            ]),
                            self::$field->create([
                                'id' => 'required_message',
                                'label' => __('Required Message', 'ht-contactform'),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'required',
                                            'value' => true,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'enable_city',
                        'type' => 'switch',
                        'label' => __('Enable City', 'ht-contactform'),
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'city',
                        'type' => 'collapse',
                        'label' => __('City', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_city',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                        'value' => [
                                'label' => 'City',
                                'placeholder' => 'City',
                                'value' => '',
                                'required' => false,
                                'required_message' => '',
                        ],
                        'fields' => [
                            self::$field->create([
                                'id' => 'label',
                                'label' => __('Label', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'placeholder',
                                'label' => __('Placeholder', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'help_message',
                                'label' => __('Help Message', 'ht-contactform'),
                                'type' => 'textarea',
                            ]),
                            self::$field->create([
                                'id' => 'default_value',
                                'label' => __('Default Value', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'auto_fill',
                                'label' => __('Auto Fill', 'ht-contactform'),
                                'type' => 'switch',
                                'info' => __('If enable, it will automatically fill the city based on the user IP address.', 'ht-contactform'),
                                'value' => false,
                            ]),
                            self::$field->create([
                                'id' => 'required',
                                'label' => __('Required', 'ht-contactform'),
                                'type' => 'switch',
                            ]),
                            self::$field->create([
                                'id' => 'required_message',
                                'label' => __('Required Message', 'ht-contactform'),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'required',
                                            'value' => true,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'enable_state',
                        'type' => 'switch',
                        'label' => __('Enable State', 'ht-contactform'),
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'state',
                        'type' => 'collapse',
                        'label' => __('State', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_state',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                        'value' => [
                                'label' => 'State',
                                'placeholder' => 'State',
                                'value' => '',
                                'required' => false,
                                'required_message' => '',
                        ],
                        'fields' => [
                            self::$field->create([
                                'id' => 'label',
                                'label' => __('Label', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'placeholder',
                                'label' => __('Placeholder', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'help_message',
                                'label' => __('Help Message', 'ht-contactform'),
                                'type' => 'textarea',
                            ]),
                            self::$field->create([
                                'id' => 'default_value',
                                'label' => __('Default Value', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'auto_fill',
                                'label' => __('Auto Fill', 'ht-contactform'),
                                'type' => 'switch',
                                'info' => __('If enable, it will automatically fill the state based on the user IP address.', 'ht-contactform'),
                                'value' => false,
                            ]),
                            self::$field->create([
                                'id' => 'required',
                                'label' => __('Required', 'ht-contactform'),
                                'type' => 'switch',
                            ]),
                            self::$field->create([
                                'id' => 'required_message',
                                'label' => __('Required Message', 'ht-contactform'),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'required',
                                            'value' => true,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'enable_zip',
                        'type' => 'switch',
                        'label' => __('Enable Zip Code', 'ht-contactform'),
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'zip',
                        'type' => 'collapse',
                        'label' => __('Zip Code', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_zip',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                        'value' => [
                                'label' => 'Zip Code',
                                'placeholder' => 'Zip Code',
                                'value' => '',
                                'required' => false,
                                'required_message' => '',
                        ],
                        'fields' => [
                            self::$field->create([
                                'id' => 'label',
                                'label' => __('Label', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'placeholder',
                                'label' => __('Placeholder', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'help_message',
                                'label' => __('Help Message', 'ht-contactform'),
                                'type' => 'textarea',
                            ]),
                            self::$field->create([
                                'id' => 'default_value',
                                'label' => __('Default Value', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'auto_fill',
                                'label' => __('Auto Fill', 'ht-contactform'),
                                'type' => 'switch',
                                'info' => __('If enable, it will automatically fill the zip code based on the user IP address.', 'ht-contactform'),
                                'value' => false,
                            ]),
                            self::$field->create([
                                'id' => 'required',
                                'label' => __('Required', 'ht-contactform'),
                                'type' => 'switch',
                            ]),
                            self::$field->create([
                                'id' => 'required_message',
                                'label' => __('Required Message', 'ht-contactform'),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'required',
                                            'value' => true,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'enable_country',
                        'type' => 'switch',
                        'label' => __('Enable Country', 'ht-contactform'),
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'country',
                        'type' => 'collapse',
                        'label' => __('Country', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_country',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ],
                        'value' => [
                                'label' => 'Country',
                                'placeholder' => 'Country',
                                'value' => '',
                                'required' => false,
                                'required_message' => '',
                        ],
                        'fields' => [
                            self::$field->create([
                                'id' => 'label',
                                'label' => __('Label', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'placeholder',
                                'label' => __('Placeholder', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'help_message',
                                'label' => __('Help Message', 'ht-contactform'),
                                'type' => 'textarea',
                            ]),
                            self::$field->create([
                                'id' => 'default_value',
                                'label' => __('Default Value', 'ht-contactform'),
                            ]),
                            self::$field->create([
                                'id' => 'auto_fill',
                                'label' => __('Auto Fill', 'ht-contactform'),
                                'type' => 'switch',
                                'info' => __('If enable, it will automatically fill the country based on the user IP address.', 'ht-contactform'),
                                'value' => false,
                            ]),
                            self::$field->create([
                                'id' => 'default_country',
                                'label' => __('Default Country', 'ht-contactform'),
                                'type' => 'select',
                                'searchable' => true,
                                'allowDeselect' => true,
                                'value' => 'us',
                                'options' => self::$countries->get_all(),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'auto_fill',
                                            'value' => false,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                            self::$field->create([
                                'id' => 'country_list_type',
                                'label' => __('Country List', 'ht-contactform'),
                                'type' => 'radio_button',
                                'value' => 'all',
                                'options' => [
                                    [
                                        'value' => 'all',
                                        'label' => __('Show All', 'ht-contactform'),
                                    ],
                                    [
                                        'value' => 'include',
                                        'label' => __('Show Selected', 'ht-contactform'),
                                    ],
                                    [
                                        'value' => 'exclude',
                                        'label' => __('Hide Selected', 'ht-contactform'),
                                    ],
                                ],
                            ]),
                            self::$field->create([
                                'id' => 'country_list',
                                'type' => 'select',
                                'multiple' => true,
                                'searchable' => true,
                                'value' => [],
                                'options' => self::$countries->get_all(),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'country_list_type',
                                            'value' => 'all',
                                            'compare' => '!=',
                                        ]
                                    ]
                                ],
                            ]),
                            self::$field->create([
                                'id' => 'required',
                                'label' => __('Required', 'ht-contactform'),
                                'type' => 'switch',
                            ]),
                            self::$field->create([
                                'id' => 'required_message',
                                'label' => __('Required Message', 'ht-contactform'),
                                'dependency' => [
                                    'relation' => 'AND',
                                    'rules' => [
                                        [
                                            'id' => 'required',
                                            'value' => true,
                                            'compare' => '==',
                                        ]
                                    ]
                                ],
                            ]),
                        ],
                    ]),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'address']),
                ],
            ],
            [
                'id' => 'file_upload',
                'type' => 'file_upload',
                'label' => __('File Upload', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('File Upload', 'ht-contactform')]),
                    self::$field->label(['value' => __('File Upload', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->required(),
                    self::$field->required_message(),
                    self::$field->create([
                        'id' => 'max_file_size',
                        'label' => __('Max File Size', 'ht-contactform'),
                        'info' => __('Max file size upload limit by user.', 'ht-contactform'),
                        'type' => 'number',
                        'value' => 2,
                        'min' => 1,
                        'suffix' => 'MB',
                    ]),
                    self::$field->create([
                        'id' => 'max_file_size_message',
                        'label' => __('Max File Size Error Message', 'ht-contactform'),
                        'value' => __('Max file size is 2MB', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'max_files',
                        'label' => __('Max Files Count', 'ht-contactform'),
                        'info' => __('Max files count upload limit by user.', 'ht-contactform'),
                        'type' => 'number',
                        'value' => 1,
                        'min' => 1,
                    ]),
                    self::$field->create([
                        'id' => 'allow_types',
                        'label' => __('Allow File Types', 'ht-contactform'),
                        'type' => 'checkbox',
                        'value' => ['image', 'pdf', 'doc'],
                        'options' => [
                            [
                                'value' => 'image',
                                'label' => __('Image (jpg, png, jpeg, gif)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'audio',
                                'label' => __('Audio (mp3, wav, ogg, oga, wma, mka, m4a, ra, mid, midi)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'video',
                                'label' => __('Video (avi, divx, flv, mov, ogv, mkv, mp4, m4v, divx, mpg, mpeg, mpe)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'pdf',
                                'label' => __('PDF (pdf)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'doc',
                                'label' => __('Documents (doc, docx, ppt, pptx, xls, xlsx, txt)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'zip',
                                'label' => __('Zip Archives (zip, rar, 7z, gz)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'exe',
                                'label' => __('Executable (exe)', 'ht-contactform'),
                            ],
                            [
                                'value' => 'csv',
                                'label' => __('CSV (csv)', 'ht-contactform'),
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'allow_types_message',
                        'label' => __('Allow Types Error Message', 'ht-contactform'),
                        'value' => __('Validation fails for allow file types', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'upload_location',
                        'label' => __('Upload Location', 'ht-contactform'),
                        'info' => __('Upload location.', 'ht-contactform'),
                        'type' => 'select',
                        'value' => 'ht_form_default',
                        'options' => [
                            [
                                'value' => 'ht_form_default',
                                'label' => __('HT Form Default', 'ht-contactform'),
                            ],
                            [
                                'value' => 'media_library',
                                'label' => __('Media Library', 'ht-contactform'),
                            ]
                        ],
                    ]),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'file-upload']),
                ],
            ],
            [
                'id' => 'slider',
                'type' => 'slider',
                'label' => __('Slider', 'ht-contactform'),
                'settings' => [
                    self::$field->admin_label(['value' => __('Slider', 'ht-contactform')]),
                    self::$field->label(['value' => __('Slider', 'ht-contactform')]),
                    self::$field->label_position(),
                    self::$field->label_hide(),
                    self::$field->min([
                        'value' => 0,
                    ]),
                    self::$field->max([
                        'value' => 100,
                    ]),
                    self::$field->step([
                        'value' => 1,
                    ]),
                    self::$field->slider_display_value(),
                    self::$field->value([
                        'type' => 'number',
                        'value' => 25,
                    ]),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->message(),
                    self::$field->message_position(),
                    self::$field->name_attribute(['value' => 'slider']),
                ],
            ],
            [
                'id' => 'submit',
                'type' => 'submit',
                'label' => __('Submit Button', 'ht-contactform'),
                'settings' => [
                    self::$field->value([
                        'label' => __('Button Text', 'ht-contactform'),
                        'value' => __('Submit', 'ht-contactform'),
                    ]),
                    self::$field->size(),
                    self::$field->class(),
                    self::$field->create([
                        'id' => 'style',
                        'label' => __('Button Style', 'ht-contactform'),
                        'info' => __('Select a button style from the dropdown', 'ht-contactform'),
                        'type' => 'select',
                        'value' => 'default',
                        'options' => [
                            ['value' => 'default', 'label' => __('Default', 'ht-contactform')],
                            ['value' => 'red', 'label' => __('Red', 'ht-contactform')],
                            ['value' => 'green', 'label' => __('Green', 'ht-contactform')],
                            ['value' => 'orange', 'label' => __('Orange', 'ht-contactform')],
                            ['value' => 'gray', 'label' => __('Gray', 'ht-contactform')],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'align',
                        'label' => __('Button Alignment', 'ht-contactform'),
                        'type' => 'radio',
                        'value' => 'left',
                        'options' => [
                            ['value' => 'left', 'label' => __('Left', 'ht-contactform')],
                            ['value' => 'center', 'label' => __('Center', 'ht-contactform')],
                            ['value' => 'right', 'label' => __('Right', 'ht-contactform')],
                        ],
                    ]),
                ],
            ],
        ]);
    }

    /**
     * Get available form settings
     * 
     * @return array Array of form settings
     */
    public function form_settings(): array {
        return apply_filters('ht_form_settings', [
            'general' => [
                'id' => 'general',
                'label' => __('General', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'enable_ajax',
                        'label' => __('Enable AJAX', 'ht-contactform'),
                        'info' => __('Enable AJAX submission for the contact form.', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => false,
                    ]),
                    self::$field->create([
                        'id' => 'store_submissions',
                        'label' => __('Store Submissions', 'ht-contactform'),
                        'info' => __('Save all form submission data to the database for later reference.', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'class',
                        'label' => __('Form Class', 'ht-contactform'),
                        'info' => __('Add a class to the contact form.', 'ht-contactform'),
                    ]),
                ]
            ],
            'spam_protection' => [
                'id' => 'spam_protection',
                'label' => __('Spam Protection', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'enable_spam_protection',
                        'label' => __('Enable Anti Spam Protection', 'ht-contactform'),
                        'info' => __('Enable anti spam protection for the contact form.', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => false,
                    ]),
                    self::$field->create([
                        'id' => 'enable_minimum_time_to_submit',
                        'label' => __('Enable minimum time to submit', 'ht-contactform'),
                        'info' => __('Set a minimum amount of time a user must spend on a form before submitting.', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => false,
                    ]),
                    self::$field->create([
                        'id' => 'minimum_time_to_submit',
                        'label' => __('Minimum Time to Submit (Seconds)', 'ht-contactform'),
                        'info' => __('Set a minimum amount of time a user must spend on a form before submitting.', 'ht-contactform'),
                        'type' => 'number',
                        'value' => 2,
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_minimum_time_to_submit',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                ]
            ],
            'notification' => [
                'id' => 'notification',
                'label' => __('Notification', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'enable_notification',
                        'label' => __('Enable Notification', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                    ]),
                    self::$field->create([
                        'id' => 'form_send_to_email',
                        'label' => __('Send To Email', 'ht-contactform'),
                        'info' => __('Enter the email address to receive form entry notifications. For multiple notifications, separate email addresses with a comma and space.', 'ht-contactform'),
                        'value' => '{admin_email}',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'form_subject',
                        'label' => __('Email Subject', 'ht-contactform'),
                        'value' => __('New Form Entry - {form_title}', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'form_name',
                        'label' => __('Form Name', 'ht-contactform'),
                        'value' => get_bloginfo('name'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'form_email',
                        'label' => __('Form Email', 'ht-contactform'),
                        'info' => __('Notifications can only use 1 From Email. Please do not enter multiple addresses.', 'ht-contactform'),
                        'value' => '{admin_email}',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'form_reply_to',
                        'label' => __('Reply To', 'ht-contactform'),
                        'info' => __('Enter the email address you would like to be used as the reply to address for the notification email.', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'form_email_body',
                        'label' => __('Email Body', 'ht-contactform'),
                        'type' => 'textarea',
                        'value' => __('{all_fields}', 'ht-contactform'),
                        'info'=> __('For every tag use new line.', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'template',
                        'label' => __('Template', 'ht-contactform'),
                        'type' => 'select',
                        'value' => '',
                        'options' => [
                            ["value" => "", "label" => __('Default', 'ht-contactform')],
                            ["value" => "1", "label" => __('Template 1', 'ht-contactform')],
                            ["value" => "2", "label" => __('Template 2', 'ht-contactform')],
                            ["value" => "3", "label" => __('Template 3', 'ht-contactform')],
                            ["value" => "4", "label" => __('Template 4', 'ht-contactform')],
                            ["value" => "5", "label" => __('Template 5', 'ht-contactform')],
                        ],
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'enable_notification',
                                    'value' => true,
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                ]
            ],
            'confirmation' => [
                'id' => 'confirmation',
                'label' => __('Confirmation', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'confirmation_type',
                        'label' => __('Confirmation Type', 'ht-contactform'),
                        'type' => 'select',
                        'value' => 'message',
                        'options' => [
                            [
                                'value' => 'message',
                                'label' => __('Message', 'ht-contactform'),
                            ],
                            [
                                'value' => 'redirect',
                                'label' => __('Redirect', 'ht-contactform'),
                            ],
                            [
                                'value' => 'page',
                                'label' => __('Show Page', 'ht-contactform'),
                            ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'confirmation_message',
                        'label' => __('Confirmation Message', 'ht-contactform'),
                        'type' => 'textarea',
                        'value' => __('Thanks for contacting us! We will be in touch with you shortly.', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'confirmation_type',
                                    'value' => 'message',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'confirmation_page',
                        'label' => __('Confirmation Page', 'ht-contactform'),
                        'type' => 'select',
                        'options' => $this->get_pages(),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'confirmation_type',
                                    'value' => 'page',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'confirmation_redirect',
                        'label' => __('Confirmation Redirect', 'ht-contactform'),
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'confirmation_type',
                                    'value' => 'redirect',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'confirmation_new_tab',
                        'label' => __('Open confirmation in new tab', 'ht-contactform'),
                        'type' => 'switch',
                        'dependency' => [
                            'relation' => 'OR',
                            'rules' => [
                                [
                                    'id' => 'confirmation_type',
                                    'value' => 'page',
                                    'compare' => '==',
                                ],
                                [
                                    'id' => 'confirmation_type',
                                    'value' => 'redirect',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                ]
            ],
            'styler' => [
                'id' => 'styler',
                'label' => __('Form Styler', 'ht-contactform'),
                'settings' => self::$styler->get_settings(),
            ],
        ]);
    }

    public function form_editor_integrations(): array {
        return apply_filters('ht_form_editor_integrations', [
            'webhook' => [
                'id' => 'webhook',
                'label' => __('Webhook', 'ht-contactform'),
                'value' => [
                    'enabled' => false,
                    'name' => '',
                    'url' => '',
                    'method' => 'POST',
                    'header_type' => 'no_headers',
                    'header' => '',
                    'body_type' => 'json',
                    'body' => '',
                ],
                'fields' => [
                    self::$field->create([
                        'id' => 'enabled',
                        'label' => __('Enable Webhook', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                        'callback' => 'rest_sanitize_boolean',
                    ]),
                    self::$field->create([
                        'id' => 'name',
                        'label' => __('Name', 'ht-contactform'),
                        'info' => __('Enter a unique name for the webhook to identify it.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'url',
                        'label' => __('Webhook URL', 'ht-contactform'),
                        'info' => __('Enter the webhook request URL.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        ]),
                    self::$field->create([
                        'id' => 'method',
                        'label' => __('Method', 'ht-contactform'),
                        'info' => __('Select the HTTP method for the webhook request.', 'ht-contactform'),
                        'type' => 'select',
                        'value' => 'POST',
                        'callback' => 'sanitize_text_field',
                        'options' => [
                            [
                                'value' => 'POST',
                                'label' => __('POST', 'ht-contactform'),
                            ],
                            // [
                            //     'value' => 'GET',
                            //     'label' => __('GET', 'ht-contactform'),
                            // ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'header_type',
                        'label' => __('Request Header', 'ht-contactform'),
                        'info' => __('Select the header for the webhook request.', 'ht-contactform'),
                        'type' => 'radio',
                        'value' => 'no_headers',
                        'callback' => 'sanitize_text_field',
                        'options' => [
                            [
                                'value' => 'no_headers',
                                'label' => __('No Headers', 'ht-contactform'),
                            ],
                            [
                                'value' => 'with_headers',
                                'label' => __('With Headers', 'ht-contactform'),
                            ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'headers',
                        'label' => __('Headers', 'ht-contactform'),
                        'info' => __('Setup headers to send with webhook request.', 'ht-contactform'),
                        'type' => 'custom_repeater',
                        'required' => true,
                        'value' => [],
                        'fields' => [
                            self::$field->create([
                                'id' => 'key',
                                'placeholder' => __('Key', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                            ]),
                            self::$field->create([
                                'id' => 'value',
                                'placeholder' => __('Value', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                            ]),
                        ],
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'header_type',
                                    'value' => 'with_headers',
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'body_fields',
                        'label' => __('Request Body', 'ht-contactform'),
                        'info' => __('Select if all fields or selected fields to send with webhook request.', 'ht-contactform'),
                        'type' => 'radio',
                        'required' => true,
                        'value' => 'all_fields',
                        'callback' => 'sanitize_text_field',
                        'options' => [
                            [
                                'value' => 'all_fields',
                                'label' => __('All Fields', 'ht-contactform'),
                            ],
                            [
                                'value' => 'selected_fields',
                                'label' => __('Selected Fields', 'ht-contactform'),
                            ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'fields',
                        'label' => __('Fields', 'ht-contactform'),
                        'info' => __('Select the fields to send with webhook request.', 'ht-contactform'),
                        'type' => 'custom_repeater',
                        'required' => true,
                        'value' => [],
                        'fields' => [
                            self::$field->create([
                                'id' => 'key',
                                'placeholder' => __('Field Name', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                            ]),
                            self::$field->create([
                                'id' => 'value',
                                'type' => 'select',
                                'placeholder' => __('Select Value', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                                'options' => 'tags',
                                'searchable' => true,
                            ]),
                        ],
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'body_fields',
                                    'value' => 'selected_fields',
                                    'compare' => '==',
                                ]
                            ]
                        ],
                    ]),
                ]
            ],
            'mailchimp' => [
                'id' => 'mailchimp',
                'label' => __('Mailchimp', 'ht-contactform'),
                'value' => [
                    'enabled' => false,
                    'name' => '',
                    'list_id' => '',
                    'merge_fields' => [],
                    'tags' => [],
                    'double_opt_in' => false,
                    'vip' => false,
                    'resubscribe' => false,
                    'note' => '',
                ],
                'fields' => [
                    self::$field->create([
                        'id' => 'enabled',
                        'label' => __('Enable Mailchimp', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                        'callback' => 'rest_sanitize_boolean',
                    ]),
                    self::$field->create([
                        'id' => 'name',
                        'label' => __('Name', 'ht-contactform'),
                        'info' => __('Enter a unique name for the mailchimp to identify it.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'list_id',
                        'label' => __('Mailchimp List', 'ht-contactform'),
                        'info' => __('Select the mailchimp list to which the form data will be sent.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        "type" => "select",
                        "options" => [],
                    ]),
                    self::$field->create([
                        'id' => 'merge_fields',
                        'label' => __('Map Fields', 'ht-contactform'),
                        'info' => __('Select the fields to map with mailchimp list.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        "type" => "custom",
                        "value" => [],
                        "fields" => [
                            self::$field->create([
                                'id' => 'value',
                                'type' => 'select',
                                'placeholder' => __('Select Value', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                                'options' => 'tags',
                                'searchable' => true,
                            ]),
                        ],
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'list_id',
                                    'value' => '',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'tags',
                        'label' => __('Tags', 'ht-contactform'),
                        'info' => __('Select the tags to associate with your Mailchimp contacts.', 'ht-contactform'),
                        "type" => "select",
                        "multiple" => true,
                        "value" => [],
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'list_id',
                                    'value' => '',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'resubscribe',
                        'label' => __('Resubscribe', 'ht-contactform'),
                        'info' => __('Enable this option to automatically resubscribe inactive or previously unsubscribed contacts. Use with caution.', 'ht-contactform'),
                        "type" => "switch",
                        "value" => false,
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'list_id',
                                    'value' => '',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'double_opt_in',
                        'label' => __('Double Opt-in', 'ht-contactform'),
                        'info' => __('When enabled, Mailchimp will send a confirmation email to the user and will only add them to your list after they confirm their subscription.', 'ht-contactform'),
                        "type" => "switch",
                        "value" => false,
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'list_id',
                                    'value' => '',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'vip',
                        'label' => __('VIP', 'ht-contactform'),
                        'info' => __('When enabled, This contact will be marked as VIP.', 'ht-contactform'),
                        "type" => "switch",
                        "value" => false,
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'list_id',
                                    'value' => '',
                                    'compare' => '!=',
                                ]
                            ]
                        ],
                    ]),
                ]
            ],
            'slack' => [
                'id' => 'slack',
                'label' => __('Slack', 'ht-contactform'),
                'value' => [
                    'enabled' => false,
                    'name' => '',
                    'url' => '',
                ],
                'fields' => [
                    self::$field->create([
                        'id' => 'enabled',
                        'label' => __('Enable Slack', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                        'callback' => 'rest_sanitize_boolean',
                    ]),
                    self::$field->create([
                        'id' => 'name',
                        'label' => __('Name', 'ht-contactform'),
                        'info' => __('Enter a unique name for the slack to identify it.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'url',
                        'label' => __('Webhook URL', 'ht-contactform'),
                        'info' => __('Enter the slack incoming webhook URL.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'fields',
                        'label' => __('Fields', 'ht-contactform'),
                        'info' => __('Select the fields to send with discord request.', 'ht-contactform'),
                        'type' => 'field_checkbox',
                        'required' => true,
                        'value' => [],
                        'options' => [],
                    ]),
                    self::$field->create([
                        'id' => 'footer',
                        'label' => __('Slack Footer message', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                    ]),
                ]
            ],
            'discord' => [
                'id' => 'discord',
                'label' => __('Discord', 'ht-contactform'),
                'value' => [
                    'enabled' => false,
                    'name' => '',
                    'url' => '',
                ],
                'fields' => [
                    self::$field->create([
                        'id' => 'enabled',
                        'label' => __('Enable Discord', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                        'callback' => 'rest_sanitize_boolean',
                    ]),
                    self::$field->create([
                        'id' => 'name',
                        'label' => __('Name', 'ht-contactform'),
                        'info' => __('Enter a unique name for the discord to identify it.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'url',
                        'label' => __('Webhook URL', 'ht-contactform'),
                        'info' => __('Enter the discord incoming webhook URL.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'fields',
                        'label' => __('Fields', 'ht-contactform'),
                        'info' => __('Select the fields to send with discord request.', 'ht-contactform'),
                        'type' => 'field_checkbox',
                        'required' => true,
                        'value' => [],
                        'options' => [],
                    ]),
                    self::$field->create([
                        'id' => 'footer',
                        'label' => __('Discord Footer message', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                    ]),
                ]
            ],
            'activecampaign' => [
                'id' => 'activecampaign',
                'label' => __('ActiveCampaign', 'ht-contactform'),
                'value' => [
                    'enabled' => false,
                    'name' => '',
                    'list_id' => '',
                    'merge_fields' => [],
                    'tags' => [],
                    'double_opt_in' => '',
                    'note' => '',
                ],
                'fields' => [
                    self::$field->create([
                        'id' => 'enabled',
                        'label' => __('Enable ActiveCampaign', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                        'callback' => 'rest_sanitize_boolean',
                    ]),
                    self::$field->create([
                        'id' => 'name',
                        'label' => __('Name', 'ht-contactform'),
                        'info' => __('Enter a unique name for the activecampaign to identify it.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'list_id',
                        'label' => __('ActiveCampaign List', 'ht-contactform'),
                        'info' => __('Select the activecampaign list to which the form data will be sent.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        "type" => "select",
                        "options" => [],
                    ]),
                    self::$field->create([
                        'id' => 'merge_fields',
                        'label' => __('Map Fields', 'ht-contactform'),
                        'info' => __('Select the fields to map with activecampaign list.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        "type" => "custom",
                        "value" => [],
                        "options" => [
                            [
                                'name' => __('Email Address', 'ht-contactform'),
                                'tag' => 'email',
                                'required' => true,
                            ],
                            [
                                'name' => __('First Name', 'ht-contactform'),
                                'tag' => 'firstName',
                            ],
                            [
                                'name' => __('Last Name', 'ht-contactform'),
                                'tag' => 'lastName',
                            ],
                            [
                                'name' => __('Phone Number', 'ht-contactform'),
                                'tag' => 'phone',
                            ],
                        ],
                        "fields" => [
                            self::$field->create([
                                'id' => 'value',
                                'type' => 'select',
                                'placeholder' => __('Select Value', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                                'options' => 'tags',
                                'searchable' => true,
                            ]),
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'tags',
                        'label' => __('Tags', 'ht-contactform'),
                        'info' => __('Select the tags to associate with your ActiveCampaign contacts.', 'ht-contactform'),
                        "type" => "select",
                        "multiple" => true,
                        "value" => [],
                        "options" => [],
                    ]),
                    self::$field->create([
                        'id' => 'note',
                        'label' => __('Note', 'ht-contactform'),
                        'info' => __('Enter any additional notes or instructions for the integration.', 'ht-contactform'),
                        'callback' => 'sanitize_textarea_field',
                        "type" => "textarea",
                    ]),
                ]
            ],
            'mailerlite' => [
                'id' => 'mailerlite',
                'label' => __('MailerLite', 'ht-contactform'),
                'value' => [
                    'enabled' => false,
                    'name' => '',
                    'group' => '',
                    'merge_fields' => [],
                ],
                'fields' => [
                    self::$field->create([
                        'id' => 'enabled',
                        'label' => __('Enable MailerLite', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => true,
                        'callback' => 'rest_sanitize_boolean',
                    ]),
                    self::$field->create([
                        'id' => 'name',
                        'label' => __('Name', 'ht-contactform'),
                        'info' => __('Enter a unique name for the mailerlite to identify it.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                    ]),
                    self::$field->create([
                        'id' => 'group',
                        'label' => __('MailerLite Group', 'ht-contactform'),
                        'info' => __('Select the mailerlite group to which the form data will be sent.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        "type" => "select",
                        "options" => [],
                    ]),
                    self::$field->create([
                        'id' => 'merge_fields',
                        'label' => __('Map Fields', 'ht-contactform'),
                        'info' => __('Select the fields to map with mailerlite group.', 'ht-contactform'),
                        'callback' => 'sanitize_text_field',
                        'required' => true,
                        "type" => "custom",
                        "value" => [],
                        "options" => [
                            [
                                'name' => 'Email',
                                'key' => 'email',
                            ],
                        ],
                        "fields" => [
                            self::$field->create([
                                'id' => 'value',
                                'type' => 'select',
                                'placeholder' => __('Select Value', 'ht-contactform'),
                                'callback' => 'sanitize_text_field',
                                'options' => 'tags',
                                'searchable' => true,
                            ]),
                        ],
                    ]),
                ]
            ],
        ]);
    }

    /**
     * Get available form Global settings
     * 
     * @return array Array of form settings
     */
    public function form_global_settings(): array {
        return apply_filters('ht_form_global_settings', [
            // 'general' => [
            //     'id' => 'general',
            //     'label' => __('General', 'ht-contactform'),
            //     'settings' => [
            //         self::$field->create([
            //             'id' => 'load_assets_globally',
            //             'label' => __('Load Assets Globally', 'ht-contactform'),
            //             'info' => __('Load assets globally for all forms.', 'ht-contactform'),
            //             'type' => 'switch',
            //             'value' => false,
            //         ]),
            //     ]
            // ],
            'layout' => [
                'id' => 'layout',
                'label' => __('Layout', 'ht-contactform'),
                'settings' => [
                    self::$field->label_position([
                        'value' => 'top',
                    ]),
                    self::$field->create([
                        'id' => 'help_message_placement',
                        'label' => __('Help Message Placement', 'ht-contactform'),
                        'info' => __('Set the placement of help messages for form fields.', 'ht-contactform'),
                        'type' => 'select',
                        'value' => 'next_to_label',
                        'options' => [
                            [
                                'value' => 'next_to_label',
                                'label' => __('Next to Label as Tooltip', 'ht-contactform'),
                            ],
                            [
                                'value' => 'below_input_element',
                                'label' => __('Below Input Element', 'ht-contactform'),
                            ],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'error_message_placement',
                        'label' => __('Error Message Placement', 'ht-contactform'),
                        'info' => __('Set the placement of error messages for form fields.', 'ht-contactform'),
                        'type' => 'select',
                        'value' => 'below_input_element',
                        'options' => [
                            [
                                'value' => 'below_label',
                                'label' => __('Below Label', 'ht-contactform'),
                            ],
                            [
                                'value' => 'below_input_element',
                                'label' => __('Below Input Element', 'ht-contactform'),
                            ],
                        ],
                    ]),
                ]
            ],
            'email' => [
                'id' => 'email',
                'label' => __('Email', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'template',
                        'label' => __('Select Template', 'ht-contactform'),
                        'type' => 'select',
                        'value' => '1',
                        'options' => [
                            ["value" => "1", "label" => __('Template 1', 'ht-contactform')],
                            ["value" => "2", "label" => __('Template 2', 'ht-contactform')],
                            ["value" => "3", "label" => __('Template 3', 'ht-contactform')],
                            ["value" => "4", "label" => __('Template 4', 'ht-contactform')],
                            ["value" => "5", "label" => __('Template 5', 'ht-contactform')],
                        ],
                    ]),
                    self::$field->create([
                        'id' => 'footer_text',
                        'label' => __('Email Footer Text', 'ht-contactform'),
                        'info' => __('This text will be added at the end of the email content.', 'ht-contactform'),
                        'type' => 'textarea',
                        'value' => '',
                    ]),
                ]
            ],
            'captcha' => [
                'id' => 'captcha',
                'label' => __('Captcha', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'captcha_type',
                        // 'label' => __('Captcha Type', 'ht-contactform'),
                        'type' => 'radio_card',
                        'options' => [
                            [
                                'value' => 'reCAPTCHA',
                                'label' => __('reCAPTCHA', 'ht-contactform')
                            ],
                        ],
                        'value' => 'reCAPTCHA',
                    ]),
                    self::$field->create([
                        'id' => 'recaptcha_version',
                        'type' => 'radio',
                        'options' => [
                            [
                                'value' => 'reCAPTCHAv2',
                                'label' => __('Google reCAPTCHA v2', 'ht-contactform')
                            ],
                            [
                                'value' => 'reCAPTCHAv3',
                                'label' => __('Google reCAPTCHA v3', 'ht-contactform')
                            ]
                        ],
                        'value' => 'reCAPTCHAv2',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'captcha_type',
                                    'value' => 'reCAPTCHA',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'recaptcha_site_key',
                        'label' => __('Site Key', 'ht-contactform'),
                        'value' => '',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'captcha_type',
                                    'value' => 'reCAPTCHA',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                    self::$field->create([
                        'id' => 'recaptcha_secret_key',
                        'label' => __('Secret Key', 'ht-contactform'),
                        'type' => 'password',
                        'value' => '',
                        'dependency' => [
                            'relation' => 'AND',
                            'rules' => [
                                [
                                    'id' => 'captcha_type',
                                    'value' => 'reCAPTCHA',
                                    'compare' => '==',
                                ]
                            ]
                        ]
                    ]),
                ]
            ],
            'validation_messages' => [
                'id' => 'validation_messages',
                'label' => __('Validation Messages', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'required',
                        'label' => __('Required', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for required field.', 'ht-contactform'),
                        'value' => __('This field is required.', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'email',
                        'label' => __('Email', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for email field.', 'ht-contactform'),
                        'value' => __('Please enter a valid email address.', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'number',
                        'label' => __('Number', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for number field.', 'ht-contactform'),
                        'value' => __('Please enter a valid number.', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'input_mask',
                        'label' => __('Input Mask Incomplete', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for input mask field. {format} will be replaced with the actual format.', 'ht-contactform'),
                        'value' => __('Please enter a valid {format} format', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'minimum_number',
                        'label' => __('Minimum Number', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for number field minimum value.', 'ht-contactform'),
                        'value' => __('You have exceeded the number of allowed {min}. {min} will be replaced with the field minimum value.', 'ht-contactform'),
                    ]),
                    self::$field->create([
                        'id' => 'maximum_number',
                        'label' => __('Maximum Number', 'ht-contactform'),
                        'info' => __('This message will be shown if validation fails for number field maximum value.', 'ht-contactform'),
                        'value' => __('You have exceeded the number of allowed {max}. {max} will be replaced with the field maximum value.', 'ht-contactform'),
                    ]),
                ]
            ],
            'miscellaneous' => [
                'id' => 'miscellaneous',
                'label' => __('Miscellaneous', 'ht-contactform'),
                'settings' => [
                    self::$field->create([
                        'id' => 'disable_ip_logging',
                        'label' => __('Disable IP Logging', 'ht-contactform'),
                        'info' => __('If this option is turned on, the user\'s IP address will not be saved with the form data.', 'ht-contactform'),
                        'type' => 'switch',
                        'value' => false,
                    ]),
                ]
            ],
        ]);
    }


    /**
     * Get available form Integrations
     * 
     * @return array Array of form settings
     */
    public function form_integrations(): array {
        return apply_filters('ht_form_integrations', [
            'id' => 'integration',
            'label' => __('Integrations', 'ht-contactform'),
            'settings' => [
                self::$field->create([
                    'id' => 'webhook',
                    'icon' => 'webhook',
                    'label' => __('Webhook', 'ht-contactform'),
                    'info' => __('This option allows you to integrate with external services using webhooks.', 'ht-contactform'),
                    'type' => 'integration',
                    'value' => false,
                    'callback' => 'switch',
                ]),
                self::$field->create([
                    'id' => 'mailchimp',
                    'icon' => 'mailchimp',
                    'label' => __('Mailchimp', 'ht-contactform'),
                    'info' => __('This option allows you to integrate with Mailchimp.', 'ht-contactform'),
                    'type' => 'integration',
                    'value' => false,
                    'callback' => 'switch',
                    'options' => [
                        self::$field->create([
                            'id' => 'api_key',
                            'label' => __('API Key', 'ht-contactform'),
                            'info' => __('Enter your Mailchimp API Key. If you don\'t have one, please log in to your Mailchimp account and go to Account > Extras > API keys.', 'ht-contactform'),
                            'callback' => 'api_key',
                            'required' => true,
                        ]),
                    ]
                ]),
                self::$field->create([
                    'id' => 'slack',
                    'icon' => 'slack',
                    'label' => __('Slack', 'ht-contactform'),
                    'info' => __('Get instant notifications in your Slack channel whenever a new submission is received.', 'ht-contactform'),
                    'type' => 'integration',
                    'value' => false,
                    'callback' => 'switch',
                ]),
                self::$field->create([
                    'id' => 'discord',
                    'icon' => 'discord',
                    'label' => __('Discord', 'ht-contactform'),
                    'info' => __('Get instant notifications in your Discord channel whenever a new submission is received.', 'ht-contactform'),
                    'type' => 'integration',
                    'value' => false,
                    'callback' => 'switch',
                ]),
                self::$field->create([
                    'id' => 'activecampaign',
                    'icon' => 'activecampaign',
                    'label' => __('ActiveCampaign', 'ht-contactform'),
                    'info' => __('This option allows you to integrate with ActiveCampaign.', 'ht-contactform'),
                    'type' => 'integration',
                    'value' => false,
                    'callback' => 'switch',
                    'options' => [
                        self::$field->create([
                            'id' => 'api_url',
                            'label' => __('API URL', 'ht-contactform'),
                            'info' => __('Enter your ActiveCampaign API URL.', 'ht-contactform'),
                            'callback' => 'api_url',
                            'required' => true,
                        ]),
                        self::$field->create([
                            'id' => 'api_key',
                            'label' => __('API Key', 'ht-contactform'),
                            'info' => __('Enter your ActiveCampaign API Key.', 'ht-contactform'),
                            'callback' => 'api_key',
                            'required' => true,
                        ]),
                    ]
                ]),
                self::$field->create([
                    'id' => 'mailerlite',
                    'icon' => 'mailerlite',
                    'label' => __('MailerLite', 'ht-contactform'),
                    'info' => __('This option allows you to integrate with MailerLite.', 'ht-contactform'),
                    'type' => 'integration',
                    'value' => false,
                    'callback' => 'switch',
                    'options' => [
                        self::$field->create([
                            'id' => 'api_key',
                            'label' => __('API Token', 'ht-contactform'),
                            'info' => __('Enter your MailerLite API Token.', 'ht-contactform'),
                            'callback' => 'api_key',
                            'required' => true,
                        ]),
                    ]
                ]),
            ]
        ]);
    }

    public function get_pages()
    {
        $pages = get_pages();
        $options = [];
        foreach ($pages as $page) {
            $item = [];
            $item['value'] = (string) $page->ID;
            $item['label'] = $page->post_title;
            $options[] = $item;
        }
        return $options;
    }
}