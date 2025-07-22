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
<body style="margin: 0; padding: 0; background-color: #f7f7f7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333333; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 30px 0;">
                <!-- Email Container -->
                <table role="presentation" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);">
                    <!-- Header Bar -->
                    <tr>
                        <td style="background-color: #4F46E5; height: 6px; line-height: 0; font-size: 0;">&nbsp;</td>
                    </tr>
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px 30px 20px; text-align: center;">
                            <h1 style="margin: 0; color: #1F2937; font-size: 24px; font-weight: 600;"><?php echo esc_html($form['title']); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 0 30px 30px;">
                            <!-- Form Data Table -->
                            <table role="presentation" style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; border-radius: 6px; overflow: hidden; border: 1px solid #E5E7EB;">
                                <tbody>
                                <?php
                                foreach ($data as $key => $value) {
                                    if(is_numeric($key) || empty($key)) {
                                        // If key is numeric or empty, display the value without a label
                                        ?>
                                        <tr>
                                            <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; vertical-align: top; color: #1F2937; font-size: 14px;" colspan="2">
                                                <?php echo esc_html($value); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    } else {
                                        // Get current field using key
                                        $current_field = null;
                                        foreach ($form['fields'] as $field) {
                                            if($field['type'] !== 'submit' && $field['settings']['name_attribute'] === $key) {
                                                $current_field = $field;
                                                break;
                                            }
                                        }
                                        
                                        // Use current field variable for operations
                                        $field_type = $current_field ? $current_field['type'] : '';
                                        $field_admin_label = $current_field ? $current_field['settings']['admin_label'] : '';
                                        $display_value = $value;
                                        
                                        if(is_array($value) && !empty($value)) {
                                            if($field_type === 'name' || $field_type === 'address') {
                                                $display_value = implode(' ', $value);
                                            } else {
                                                $display_value = sprintf('<ul style="margin: 0; padding: 0;"><li>%s</li></ul>', implode('</li><li>', $value));
                                            }
                                        }
                                        
                                        if($field_type === 'textarea') {
                                            $display_value = nl2br(esc_html($value));
                                        }
    
                                        if($field_type === 'ratings') {
                                            $options = $current_field['settings']['options'] ?? [];
                                            $rating_label = '';
                                            foreach ($options as $option) {
                                                if($option['value'] === $value) {
                                                    $rating_label = $option['label'];
                                                    break;
                                                }
                                            }
                                            $display_value = implode(' ', [$rating_label, "({$value})"]) ;
                                        }
                                        ?>
                                        <tr>
                                            <th style="text-align: left; padding: 12px 16px; border-bottom: 1px solid #E5E7EB; width: 140px; vertical-align: top; color: #374151; font-weight: 600; font-size: 14px; background-color: #F9FAFB;">
                                                <?php echo esc_html($field_admin_label); ?>
                                            </th>
                                            <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; vertical-align: top; color: #1F2937; font-size: 14px;">
                                                <?php echo wp_kses_post($display_value); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                            
                            <!-- Metadata -->
                            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #E5E7EB; font-size: 12px; color: #6B7280;">
                                <?php
                                    if(!empty($meta['created_at'])) {
                                        printf('<p style="margin: 5px 0;">%s: %s</p>', 
                                            esc_html__('Submitted on', 'ht-contactform'),
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
                        <td style="background-color: #F9FAFB; padding: 20px 30px; text-align: center; border-top: 1px solid #E5E7EB;">
                            <p style="margin: 0; font-size: 13px; color: #6B7280;">
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