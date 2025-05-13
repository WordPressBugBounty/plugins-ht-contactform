<?php
namespace HTContactFormAdmin\Includes\Api;

use HTContactFormAdmin\Includes\Api\Endpoints\Form;
use HTContactFormAdmin\Includes\Api\Endpoints\Entry;
use HTContactFormAdmin\Includes\Api\Endpoints\Submission;
use HTContactFormAdmin\Includes\Api\Endpoints\Settings;
use HTContactFormAdmin\Includes\Api\Endpoints\Integrations;

class ApiRegistry {
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
        Integrations::get_instance();
    }
}