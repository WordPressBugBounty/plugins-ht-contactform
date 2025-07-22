<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Extract variables
$subject = $args['subject'] ?? '';
$form = $args['form'] ?? [];
$data = $args['data'] ?? [];
$meta = $args['meta'] ?? [];
$footer_text = $args['footer_text'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html($subject); ?></title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif; color: #333333; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0;">
                <!-- Email Container -->
                <table role="presentation" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 6px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 20px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; color: #444444; font-size: 24px;"><?php echo esc_html($form['title']); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px;">
                            <!-- Form Data Table -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <?php
                            foreach ($data as $key => $value) {
                                if(is_numeric($key) || empty($key)) {
                                    ?>
                                        <tr>
                                            <td style="padding: 12px; border: 1px solid #dddddd; vertical-align: top;" colspan="2">
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
                                            <th style="text-align: left; padding: 12px; border: 1px solid #dddddd; background-color: #f9f9f9; min-width: 120px; width: 120px; vertical-align: top;">
                                                <?php echo esc_html($field_admin_label); ?>
                                            </th>
                                            <td style="padding: 12px; border: 1px solid #dddddd; vertical-align: top;">
                                                <?php echo wp_kses_post($display_value); ?>
                                            </td>
                                        </tr>
                                    <?php
                                }
                            }
                            ?>
                            </table>
                            
                            <!-- Metadata -->
                            <div style="font-size: 12px; color: #777777; border-top: 1px solid #eeeeee; padding-top: 15px; margin-top: 20px;">
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
                        <td style="padding: 20px; text-align: center; font-size: 12px; color: #777777; border-top: 1px solid #eeeeee;">
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