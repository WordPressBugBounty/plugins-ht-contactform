<?php
namespace HTContactFormAdmin\Includes\Services;

/**
 * FileManager class
 * 
 * Provides functions for managing temporary files used by the form builder
 */
class FileManager {
    /**
     * Singleton instance
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Directory paths
     */
    private $dir;
    
    /**
     * Upload configuration
     * 
     * @var array
     */
    private $config = [
        'cleanup_age' => 86400, // 24 hours
    ];
    
    /**
     * Get instance of this class
     *
     * @return object
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set up upload directories
        $upload_dir = wp_upload_dir();
        $this->dir = $upload_dir['basedir'] . '/ht_form';

        // Schedule cleanup
        add_action('ht_form_temp_files_cleanup', [$this, 'cleanup_temp_files']);

        // Create directories on initialization
        $this->maybe_create_directories("{$this->dir}/temp");
    }
    
    /**
     * Temporary file upload handler
     * 
     * @param array $file The $_FILES array item
     * @return void
     */
    public function temp_file_upload($file) {
        $destination = "{$this->dir}/temp";
        $this->maybe_create_directories($destination);
        
        // Validate file
        $validation = $this->validate($file);
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
            return;
        }
        
        // Process the file
        $filename = $this->process_filename($file['name']);
        $file_path = "{$destination}/$filename";
        
        // File type validation check
        $validate = wp_check_filetype( $filename );
        if ($validate['type'] === false) {
            wp_send_json_error('Invalid file type.');
            return;
        }
        
        // Check if directory is writable
        if (!is_writable($destination)) {
            wp_send_json_error("Directory is not writable: {$destination}");
            return;
        }
        
        // Move file to temporary directory
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_success([
                'file_id' => $filename,
                'file_name' => $file['name'],
                'file_size' => $file['size']
            ]);
            return;
        } else {
            $upload_error = error_get_last();
            wp_send_json_error('Failed to save uploaded file. Error: ' . ($upload_error ? $upload_error['message'] : 'Unknown error'));
            return;
        }
    }

    /**
     * Delete temporary file
     * @return bool Return `true` if file deleted successfully else `false`
     */
    public function temp_file_delete($file_id) {
        $file = "{$this->dir}/temp/$file_id";
        if (file_exists($file) && is_writable($file)) {
            @unlink($file);
            return true;
        }
        return false;
    }

    /**
     * Create necessary directories for file uploads
    */
    private function maybe_create_directories($dir = 'temp') {
        if (!file_exists($dir)) {
            if (!wp_mkdir_p($dir)) {
                error_log("Failed to create directory: $dir");
                return;
            }
        }
        // Create an index.php file to prevent directory listing
        $index_file = trailingslashit($dir) . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * Validate uploaded file
     *
     * @param array $file The $_FILES array item
     * @return array Validation result with 'valid' boolean and 'message' string
     */
    private function validate($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($file['error']);
            return [
                'valid' => false,
                'message' => $error_message
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get human-readable upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }

    /**
     * Process filename to ensure it's safe and unique
     *
     * @param string $filename Original filename
     * @return string Processed filename
     */
    private function process_filename($filename) {
        $filename = sanitize_file_name($filename);
        return uniqid() . '-' . $filename;
    }

    /**
     * Schedule cleanup task for temporary files
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled('ht_form_temp_files_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ht_form_temp_files_cleanup');
        }
    }

    /**
     * Clean up old temporary files
     */
    public function cleanup_temp_files() {
        $files = glob("{$this->dir}/temp/*");
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if (basename($file) === 'index.php' || basename($file) === '.htaccess') {
                    continue;
                }
                if ($now - filemtime($file) > $this->config['cleanup_age']) {
                    @unlink($file);
                }
            }
        }
    }

}