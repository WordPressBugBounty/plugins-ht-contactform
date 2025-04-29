<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Extract variables
$subject = $args['subject'] ?? '';
$form = $args['form'] ?? [];
$data = $args['data'] ?? []; // Changed from form_data to data for consistency
$meta = $args['meta'] ?? [];
$footer_text = $args['footer_text'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html($subject); ?></title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 25px 0;">
                <!-- Email Container -->
                <table role="presentation" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 5px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="text-align: center; padding: 25px 30px; background: linear-gradient(to right, #007bff, #0056b3); color: #ffffff;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 500;"><?php echo esc_html($form['title']); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <!-- Intro Text -->
                            <p style="margin-top: 0; margin-bottom: 20px; font-size: 16px; color: #555555;">
                                <?php echo esc_html__('A new form submission has been received with the following details:', 'ht-contactform'); ?>
                            </p>
                            
                            <!-- Form Data -->
                            <table role="presentation" style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 5px; overflow: hidden;">
                            <?php
                            $i = 0;
                            foreach ($data as $key => $value) {
                                // Skip empty values
                                if ($value === '') {
                                    continue;
                                }
                                
                                // Alternate row colors
                                $bg_color = $i % 2 == 0 ? '#ffffff' : '#f8f9fa';
                                $i++;
                                
                                if(is_numeric($key) || empty($key)) {
                                    // If key is numeric or empty, display only the value without a label
                                    ?>
                                    <tr style="background-color: <?php echo esc_attr($bg_color); ?>;">
                                        <td style="padding: 12px 15px; vertical-align: top; border-bottom: 1px solid #dee2e6; color: #212529; font-size: 14px;" colspan="2">
                                            <?php echo esc_html($value); ?>
                                        </td>
                                    </tr>
                                    <?php
                                } else {
                                    // For non-numeric keys, find the field information
                                    $field_type = '';
                                    $field_admin_label = '';
                                    foreach ($form['fields'] as $field) {
                                        if($field['type'] !== 'submit' && $field['settings']['name_attribute'] === $key) {
                                            $field_type = $field['type'];
                                            $field_admin_label = $field['settings']['admin_label'] ?? $field['settings']['label'] ?? ucfirst($key);
                                            break;
                                        }
                                    }
                                    
                                    // Process array values
                                    if(is_array($value) && !empty($value)) {
                                        if($field_type === 'name') {
                                            $value = implode(' ', $value);
                                        } else {
                                            $value = sprintf('<ul style="margin: 0; padding: 0;"><li>%s</li></ul>', implode('</li><li>', $value));
                                        }
                                    }
                                    
                                    // Format based on field type
                                    if($field_type === 'textarea') {
                                        $value = nl2br(esc_html($value));
                                    } else {
                                        $value = wp_kses_post($value);
                                    }
                                    ?>
                                    <tr style="background-color: <?php echo esc_attr($bg_color); ?>;">
                                        <th style="text-align: left; padding: 12px 15px; width: 140px; vertical-align: top; border-bottom: 1px solid #dee2e6; color: #495057; font-weight: 600; font-size: 14px;">
                                            <?php echo esc_html($field_admin_label); ?>
                                        </th>
                                        <td style="padding: 12px 15px; vertical-align: top; border-bottom: 1px solid #dee2e6; color: #212529; font-size: 14px;">
                                            <?php echo wp_kses_post($value); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            </table>
                            
                            <!-- Metadata -->
                            <div style="margin-top: 25px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;">
                                <?php
                                    if(!empty($meta['created_at']) || !empty($meta['ip_address'])) {
                                        printf('<p style="margin: 0 0 5px;"><strong>%s:</strong></p>',
                                            esc_html__('Submission Details', 'ht-contactform')
                                        );
                                    }
                                    if(!empty($meta['created_at'])) {
                                        printf('<p style="margin: 5px 0;">%s: %s</p>', 
                                            esc_html__('Date', 'ht-contactform'),
                                            esc_html($meta['created_at'])
                                        );
                                    }
                                    if(!empty($meta['ip_address'])) {
                                        printf('<p style="margin: 5px 0;">%s: %s</p>', 
                                            esc_html__('IP Address', 'ht-contactform'),
                                            esc_html($meta['ip_address'])
                                        );
                                    }
                                ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; text-align: center; font-size: 12px; color: #6c757d; background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                            <p style="margin: 0;">
                                <?php if(!empty($footer_text)) {
                                    echo esc_html($footer_text);
                                } else {
                                    echo sprintf(
                                        /* translators: %1$s: Year, %2$s: Site Name, %3$s: Plugin Name */
                                        esc_html__('&copy; %1$s %2$s. All rights reserved. Powered by %3$s', 'ht-contactform'),
                                        esc_html(gmdate('Y')),
                                        esc_html(get_bloginfo('name')),
                                        esc_html('HT Contact Form')
                                    );
                                }?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>