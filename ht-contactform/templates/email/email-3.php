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
<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #111111; line-height: 1.5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 30px 15px;">
                <!-- Email Container -->
                <table role="presentation" style="max-width: 540px; width: 100%; margin: 0 auto; background-color: #ffffff;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 0 0 25px; text-align: left;">
                            <h1 style="margin: 0; color: #000000; font-size: 22px; font-weight: 500; letter-spacing: -0.5px;"><?php echo esc_html($form['title']); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 0;">
                            <!-- Form Data -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                            <?php
                            foreach ($data as $key => $value) {
                                if(is_numeric($key) || empty($key)) {
                                    // If key is numeric or empty, display the value without a label
                                    ?>
                                    <tr>
                                        <td style="padding: 12px 0; border-top: 1px solid #EEEEEE; vertical-align: top; font-size: 14px; color: #111111;" colspan="2">
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
                                        if($field_type === 'name' || $field_type === 'address') {
                                            $value = implode(' ', $value);
                                        } else {
                                            $value = sprintf('<ul style="margin: 10px 0 0; padding: 0 0 0 15px;"><li>%s</li></ul>', implode('</li><li>', $value));
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
                                        <th style="text-align: left; padding: 12px 0; border-top: 1px solid #EEEEEE; vertical-align: top; color: #555555; font-weight: 600; font-size: 14px; width: 120px;">
                                            <?php echo esc_html($field_admin_label); ?>
                                        </th>
                                        <td style="padding: 12px 0; border-top: 1px solid #EEEEEE; vertical-align: top; color: #111111; font-size: 14px;">
                                            <?php echo wp_kses_post($value); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            </table>
                            
                            <!-- Metadata -->
                            <p style="font-size: 12px; color: #777777; margin: 0; border-top: 1px solid #EEEEEE; padding-top: 20px; padding-bottom: 20px;">
                                <?php
                                    if(!empty($meta['created_at'])) {
                                        printf('%s: %s', esc_html__('Submitted on', 'ht-contactform'), esc_html($meta['created_at']));
                                    }
                                    if(!empty($meta['ip_address'])) {
                                        printf('<br>%s: %s', esc_html__('IP Address', 'ht-contactform'), esc_html($meta['ip_address']));
                                    }
                                ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 25px 0 0; text-align: center; color: #777777; font-size: 12px; border-top: 1px solid #EEEEEE; margin-top: 20px;">
                            <p style="margin: 0;">
                                <?php if(!empty($footer_text)) { ?>
                                    <?php echo esc_html($footer_text); ?>
                                <?php } else {
                                    echo sprintf(
                                        /* translators: %1$s: Site Name, %2$s: Year */
                                        esc_html__('%1$s • %2$s', 'ht-contactform'),
                                        esc_html(get_bloginfo('name')),
                                        esc_html(gmdate('Y'))
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