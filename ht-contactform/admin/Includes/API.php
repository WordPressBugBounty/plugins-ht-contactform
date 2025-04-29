<?php
namespace HTContactFormAdmin\Includes;

use HTContactFormAdmin\Includes\Api\Form;
use HTContactFormAdmin\Includes\Api\Entry;
use HTContactFormAdmin\Includes\Api\Submission;
use HTContactFormAdmin\Includes\Api\Settings;

class API {
    private static $instance = null;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    public function __construct() {
        Form::get_instance();
        Entry::get_instance();
        Submission::get_instance();
        Settings::get_instance();
    }
}