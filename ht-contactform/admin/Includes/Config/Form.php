<?php
namespace HTContactFormAdmin\Includes\Config;

use HTContactFormAdmin\Includes\Config\Field;

class Form {

    private $global_settings = [];

    private static $instance = null;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static $field = null;

    public function __construct() {
        self::$field = Field::get_instance();
        $this->global_settings = get_option('ht_form_global_settings', []);
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
                        'value' => '',
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