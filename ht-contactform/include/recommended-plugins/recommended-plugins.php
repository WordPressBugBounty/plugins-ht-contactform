<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Constructor Parameters
 *
 * @param string    $text_domain your plugin text domain.
 * @param string    $parent_menu_slug the menu slug name where the "Recommendations" submenu will appear.
 * @param string    $submenu_label To change the submenu name.
 * @param string    $submenu_page_name an unique page name for the submenu.
 * @param int       $priority Submenu priority adjust.
 * @param string    $hook_suffix use it to load this library assets only to the recommedded plugins page. Not into the whol admin area.
 *
 */

if( class_exists('Hasthemes\HTContact_Form\Recommended_Plugins') ){
    $recommendations = new Hasthemes\HTContact_Form\Recommended_Plugins(
        array( 
            'text_domain'       => 'ht-contactform',
            'parent_menu_slug'  => 'htcontact-form',
            'menu_type'         => 'submenu',
            'menu_icon'         => 'dashicons-email-alt',
            'menu_capability'   => 'manage_options',
            'menu_page_slug'    => '',
            'priority'          => 300,
            'assets_url'        => HTCONTACTFORM_PL_URL.'/assets',
            'hook_suffix'       => 'ht-contact-form_page_ht-contactform_extensions',
        )
    );

    // ShopLentor is a WooCommerce plugin — only worth featuring in the
    // primary "Recommended Plugins" tab when WooCommerce is actually
    // active; otherwise it stays discoverable under the "WooCommerce" tab.
    $woocommerce_active = class_exists( 'WooCommerce' );
    $shoplentor_entry   = array(
        'slug'      => 'woolentor-addons',
        'location'  => 'woolentor_addons_elementor.php',
        'name'      => esc_html__( 'ShopLentor – All-in-One WooCommerce Growth & Store Enhancement Plugin', 'ht-contactform' )
    );

    $recommendations->add_new_tab( array(

        'title' => esc_html__( 'Recommended Plugins', 'ht-contactform' ),
        'active' => true,
        'plugins' => array_merge(
            $woocommerce_active ? array( $shoplentor_entry ) : array(),
            array(
                array(
                    'slug'      => 'support-genix-lite',
                    'location'  => 'support-genix-lite.php',
                    'name'      => esc_html__( 'Support Genix – Helpdesk, AI Chatbot, Knowledge Base & Customer Support Ticketing System', 'ht-contactform' )
                ),

                array(
                    'slug'      => 'hashbar-wp-notification-bar',
                    'location'  => 'init.php',
                    'name'      => esc_html__( 'HashBar – Announcement, Notification Bar & Popup Campaign', 'ht-contactform' )
                ),

                array(
                    'slug'      => 'wp-plugin-manager',
                    'location'  => 'plugin-main.php',
                    'name'      => esc_html__( 'WP Plugin Manager – Deactivate plugins per page', 'ht-contactform' )
                ),

                array(
                    'slug'      => 'cookieray',
                    'location'  => 'cookieray.php',
                    'name'      => esc_html__( 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)', 'ht-contactform' )
                ),

                array(
                    'slug'      => 'kelune-crm',
                    'location'  => 'kelune-crm.php',
                    'name'      => esc_html__( 'Kelune CRM – Contact Management, Email Marketing, Newsletter & Marketing Automation', 'ht-contactform' )
                ),
            )
        )

    ) );

    $recommendations->add_new_tab( array(
        'title' => esc_html__( 'WooCommerce', 'ht-contactform' ),
        'plugins' => array_merge(
            $woocommerce_active ? array() : array( $shoplentor_entry ),
            array(
                array(
                    'slug'      => 'whols',
                    'location'  => 'whols.php',
                    'name'      => esc_html__( 'Whols – Wholesale Prices and B2B Store Solution for WooCommerce', 'ht-contactform' )
                ),
                array(
                    'slug'      => 'recurio',
                    'location'  => 'recurio.php',
                    'name'      => esc_html__( 'Recurio – Ultimate Subscription for WooCommerce', 'ht-contactform' )
                ),
            )
        )
    ) );

    $recommendations->add_new_tab(array(
        'title' => esc_html__( 'Popular', 'ht-contactform' ),
        'plugins' => array(
            array(
                'slug'      => 'ht-mega-for-elementor',
                'location'  => 'htmega_addons_elementor.php',
                'name'      => esc_html__( 'HT Mega Addons for Elementor – Elementor Widgets & Template Builder', 'ht-contactform' )
            ),
            array(
                'slug'      => 'wp-plugin-manager',
                'location'  => 'plugin-main.php',
                'name'      => esc_html__( 'WP Plugin Manager – Deactivate plugins per page', 'ht-contactform' )
            ),
            array(
                'slug'      => 'ht-easy-google-analytics',
                'location'  => 'ht-easy-google-analytics.php',
                'name'      => esc_html__( 'HT Easy GA4 – Google Analytics WordPress Plugin', 'ht-contactform' )
            ),
            array(
                'slug'      => 'cookieray',
                'location'  => 'cookieray.php',
                'name'      => esc_html__( 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)', 'ht-contactform' )
            ),
            array(
                'slug'      => 'insert-headers-and-footers-script',
                'location'  => 'init.php',
                'name'      => esc_html__( 'Insert Headers and Footers Code – HT Script', 'ht-contactform' )
            ),
            array(
                'slug'      => 'pixelavo',
                'location'  => 'pixelavo.php',
                'name'      => esc_html__( 'Pixelavo – Server Side Tracking & Pixel + AI Ads Tools', 'ht-contactform' )
            ),
            array(
                'slug'      => 'courseglade-lms',
                'location'  => 'courseglade-lms.php',
                'name'      => esc_html__( 'CourseGlade LMS – Online Course & eLearning Platform', 'ht-contactform' )
            ),
        )
    ));
}
