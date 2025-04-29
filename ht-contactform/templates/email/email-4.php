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
<body style="margin: 0; padding: 0; background-color: #f2f2f2; font-family: Arial, sans-serif; color: #333333; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 30px 0;">
                <!-- Email Container -->
                <table role="presentation" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <!-- Header with Brand Color and Logo -->
                    <tr>
                        <td style="background-color: #2C3E50; padding: 25px 30px; text-align: center;">
                            <!-- Replace with your logo -->
                            <div style="color: #ffffff; font-size: 28px; font-weight: bold; letter-spacing: 1px;">
                                <?php echo esc_html(get_bloginfo('name')); ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Title Bar -->
                    <tr>
                        <td style="background-color: #34495E; padding: 15px 30px; text-align: center; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 20px; font-weight: normal;"><?php echo esc_html($form['title']); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin-top: 0; margin-bottom: 20px; font-size: 16px;">
                                <?php echo esc_html__('You\'ve received a new message from your website contact form.', 'ht-contactform'); ?>
                            </p>
                            
                            <!-- Form Data -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 0; border: 1px solid #e1e1e1; border-radius: 4px; overflow: hidden;">
                            <?php
                            foreach ($data as $key => $value) {
                                if(is_numeric($key) || empty($key)) {
                                    // If key is numeric or empty, display value without a label (full width)
                                    ?>
                                    <tr>
                                        <td style="padding: 12px 15px; border-bottom: 1px solid #e1e1e1; vertical-align: top; color: #333333; font-size: 14px;" colspan="2">
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
                                            $field_admin_label = $field['settings']['admin_label'];
                                            break;
                                        }
                                    }
                                    
                                    // Process array values
                                    if(is_array($value) && !empty($value)) {
                                        if($field_type === 'name') {
                                            $value = implode(' ', $value);
                                        } else {
                                            $value = sprintf('<ul style="margin: 0; padding: 0 0 0 20px;"><li>%s</li></ul>', implode('</li><li>', $value));
                                        }
                                    }
                                    
                                    // Format based on field type
                                    if($field_type === 'textarea') {
                                        $value = nl2br(esc_html($value));
                                    } else {
                                        $value = wp_kses_post($value);
                                    }
                                    ?>
                                    <tr>
                                        <th style="text-align: left; padding: 12px 15px; border-bottom: 1px solid #e1e1e1; width: 140px; vertical-align: top; background-color: #f9f9f9; color: #555555; font-weight: 600; font-size: 14px;">
                                            <?php echo esc_html($field_admin_label); ?>:
                                        </th>
                                        <td style="padding: 12px 15px; border-bottom: 1px solid #e1e1e1; vertical-align: top; color: #333333; font-size: 14px;">
                                            <?php echo wp_kses_post($value); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            </table>
                            
                            <!-- Metadata -->
                            <div style="margin-top: 0; padding-top: 20px; border-top: 1px solid #e1e1e1; font-size: 13px; color: #777777;">
                                <?php
                                    if(!empty($meta['created_at'])) {
                                        printf('<p style="margin: 0 0 5px;">%s: %s</p>', 
                                            esc_html__('Submitted on', 'ht-contactform'),
                                            esc_html($meta['created_at'])
                                        );
                                    }
                                    if(!empty($meta['ip_address'])) {
                                        printf('<p style="margin: 0;">%s: %s</p>', 
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
                        <td style="background-color: #34495E; padding: 20px; text-align: center; color: #ffffff; font-size: 13px;">
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
                                } ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>