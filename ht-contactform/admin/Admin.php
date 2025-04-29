<?php
namespace HTContactFormAdmin;
use HTContactFormAdmin\Includes\API;
use HTContactFormAdmin\Includes\PostType;
use HTContactFormAdmin\Includes\Config\Form as FormConfig;
use HTContactFormAdmin\Includes\ShortCode;
use HTContactFormAdmin\Includes\Assets;
class Admin {
    private static $instance = null;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }   
    public function __construct() {
        API::get_instance();
        PostType::get_instance();
        Assets::get_instance();
        ShortCode::get_instance();
        add_action('in_admin_header', [$this, 'remove_admin_notice']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('init', [$this, 'update_global_settings']);
    }
    
    /**
     * Remove all Notices on admin pages.
     * @return void
     */
    public function remove_admin_notice(){
        $current_screen = get_current_screen();
        $hide_screen = ['toplevel_page_htcontact-form', 'ht-contact-form_page_ht-contactform_extensions', 'update'];
        if(  in_array( $current_screen->id, $hide_screen) ){
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }

    public function add_admin_menu() {
        global $submenu;

        $slug        = 'htcontact-form';
        $capability  = 'manage_options';

        add_menu_page(
            esc_html__( 'HT Contact Form', 'ht-contactform' ),
            esc_html__( 'HT Contact Form', 'ht-contactform' ), 
            $capability,
            $slug,
            [$this, 'render_admin_page'],
            'dashicons-email-alt',
            30
        );
        if ( current_user_can( $capability ) ) {
            $submenu[ $slug ][] = [
                esc_html__('Welcome', 'ht-contactform'),
                $capability,
                "admin.php?page=$slug"
            ];
            $submenu[ $slug ][] = [
                esc_html__('Forms', 'ht-contactform'),
                $capability,
                "admin.php?page=$slug&path=forms"
            ];
            $submenu[ $slug ][] = [
                esc_html__('New Form', 'ht-contactform'),
                $capability,
                "admin.php?page=$slug&path=editor"
            ];
            $submenu[ $slug ][] = [
                esc_html__('Global Settings', 'ht-contactform'),
                $capability,
                "admin.php?page=$slug&path=settings"
            ];
        }
    }

    public function render_admin_page() {
        echo '<div id="ht-contactform-admin"></div>';
    }

    public function update_global_settings() {
        if(!get_option('ht_form_global_settings')) {
            $global_settings = FormConfig::get_instance()->form_global_settings();
            $default = array_reduce($global_settings, function($carry, $section): mixed {
                $carry[$section['id']] = array_reduce($section['settings'], function($carry, $field): mixed {
                    $carry[$field['id']] = $field['value'];
                    return $carry;
                }, []);
                return $carry;
            }, []);
            update_option('ht_form_global_settings', $default);
        }
    }
}