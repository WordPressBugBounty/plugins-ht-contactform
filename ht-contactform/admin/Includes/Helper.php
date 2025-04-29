<?php

namespace HTContactFormAdmin\Includes;

/**
 * Helper Class
 * 
 * Handles general helper functions for the plugin.
 * 
 * @package HTContactFormAdmin\Includes
 * @since 1.0.0
 */
class Helper {
    //-------------------------------------------------------------------------
    // PROPERTIES
    //-------------------------------------------------------------------------

    // Get user agent string
    private static $userAgent = null;

    /** @var self|null Singleton instance */
    private static $instance = null;

    //-------------------------------------------------------------------------
    // INITIALIZATION
    //-------------------------------------------------------------------------
    
    /**
     * Get singleton instance
     * 
     * @return self
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     * 
     * Initializes the class and sets up WordPress hooks
     */
    public function __construct() {
        self::$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
    }

    //-------------------------------------------------------------------------
    // GETTERS
    //-------------------------------------------------------------------------
    
    /**
     * Get user agent string
     * 
     * @return string User agent string
     */
    public static function get_user_agent() {
        return self::$userAgent;
    }

    /**
     * Refresh user agent from current request
     * 
     * @param \WP_REST_Request $request Optional REST request object
     * @return string Current user agent
     */
    public static function refresh_user_agent($request = null) {
        if ($request !== null) {
            self::$userAgent = $request->get_header('user-agent') ?? '';
        } else {
            self::$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        }
        return self::$userAgent;
    }

    /**
     * Get browser name
     * 
     * @return string Browser name
     */
    public static function get_browser() {
        // Ensure we have the user agent
        if (empty(self::$userAgent)) {
            self::refresh_user_agent();
        }
        
        $browser = "Unknown";
        $browsers = [
            '/msie|trident/i'      => 'Internet Explorer',
            '/firefox/i'           => 'Firefox',
            '/edg/i'               => 'Edge',  // Modern Edge (Chromium-based)
            '/edge/i'              => 'Edge',  // Legacy Edge
            '/chrome/i'            => 'Chrome',
            '/safari/i'            => 'Safari',
            '/opera|OPR/i'         => 'Opera',
            '/netscape/i'          => 'Netscape',
            '/maxthon/i'           => 'Maxthon',
            '/konqueror/i'         => 'Konqueror',
            '/ucbrowser/i'         => 'UC Browser',
            '/mobile/i'            => 'Mobile Browser'
        ];
    
        foreach ($browsers as $regex => $value) {
            if (preg_match($regex, self::$userAgent)) {
                $browser = $value;
                break;
            }
        }
    
        return $browser;
    }

    /**
     * Get device type
     * 
     * @return string Device type
     */
    public static function get_device() {
        // Ensure we have the user agent
        if (empty(self::$userAgent)) {
            self::refresh_user_agent();
        }
        
        $device = "Unknown";
        
        $devices = [
            '/iphone/i'            => 'iPhone',
            '/ipad/i'              => 'iPad',
            '/android.*mobile/i'   => 'Android Mobile',
            '/android/i'           => 'Android', 
            '/blackberry/i'        => 'BlackBerry',
            '/webos/i'             => 'Mobile',
            '/ipod/i'              => 'iPod',
            '/windows phone/i'     => 'Windows Phone',
            '/windows nt 10/i'     => 'Windows 10',
            '/windows nt 6.3/i'    => 'Windows 8.1',
            '/windows nt 6.2/i'    => 'Windows 8',
            '/windows nt 6.1/i'    => 'Windows 7',
            '/windows nt 6.0/i'    => 'Windows Vista',
            '/windows nt 5.2/i'    => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'    => 'Windows XP',
            '/windows xp/i'        => 'Windows XP',
            '/windows/i'           => 'Windows',
            '/macintosh|mac os x/i' => 'macOS',
            '/mac_powerpc/i'       => 'Mac OS 9',
            '/linux/i'             => 'Linux',
            '/ubuntu/i'            => 'Ubuntu',
            '/ios/i'               => 'iOS',
            '/tablet/i'            => 'Tablet',
            '/mobile/i'            => 'Mobile'
        ];
        
        foreach ($devices as $regex => $value) {
            if (preg_match($regex, self::$userAgent)) {
                $device = $value;
                break;
            }
        }
        
        return $device;
    }

    /**
     * Get visitor's real IP address
     * 
     * Checks various proxy headers to determine the actual client IP address
     * 
     * @return string The visitor's IP address
     */
    public static function get_ip() {
        // Check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        }
        
        // Check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can contain multiple IPs separated by comma
            // The first one is the original client IP
            $ip_list = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            foreach ($ip_list as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // Check for CloudFlare IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        }
        
        // Check for other common proxy headers
        $proxy_headers = [
            'HTTP_X_REAL_IP',
            'HTTP_X_CLUSTER_CLIENT_IP', 
            'HTTP_FORWARDED',
            'HTTP_X_FORWARDED'
        ];
        
        foreach ($proxy_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // If no proxy detected, return remote address or empty string
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    }

    /**
     * Validate reCAPTCHA response
     * 
     * @param string $recaptcha_response The reCAPTCHA response
     * @return array|bool {code: string, message: string, status: int}
     */
    public static function validate_recaptcha($recaptcha_response) {
        $global_settings = get_option('ht_form_global_settings', []);
        if (
            !empty($global_settings['captcha']['captcha_type']) && 
            $global_settings['captcha']['captcha_type'] === 'reCAPTCHA' &&
            !empty($global_settings['captcha']['recaptcha_secret_key'])
        ) {
            
            $recaptcha_version = $global_settings['captcha']['recaptcha_version'] ?? 'reCAPTCHAv2';
            $secret_key = $global_settings['captcha']['recaptcha_secret_key'] ?? '';
            
            if (!empty($recaptcha_response)) {
                $recaptcha_response = sanitize_text_field($recaptcha_response);
                
                // If response is empty, return error
                if (empty($recaptcha_response)) {
                    return [
                        'code' => 'recaptcha_required',
                        'message' => __('Please complete the reCAPTCHA challenge.', 'ht-contactform'),
                        'status' => 400
                    ];
                }
                
                // Verify with Google's API
                $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
                $response = wp_remote_post($verify_url, [
                    'body' => [
                        'secret' => $secret_key,
                        'response' => $recaptcha_response,
                        'remoteip' => !empty($global_settings['miscellaneous']['disable_ip_logging']) ? '' : Helper::get_ip(),
                    ],
                ]);
                
                // Check for errors in the request
                if (is_wp_error($response)) {
                    return [
                        'code' => 'recaptcha_connection_failed',
                        'message' => __('Failed to connect to reCAPTCHA server.', 'ht-contactform'),
                        'status' => 500
                    ];
                }
                
                // Parse the response
                $body = wp_remote_retrieve_body($response);
                $result = json_decode($body, true);
                
                // For v3, check the score
                if ($recaptcha_version === 'reCAPTCHAv3' && 
                    (!isset($result['success']) || !$result['success'] || 
                    (isset($result['score']) && $result['score'] < 0.5))) {
                    return [
                        'code' => 'recaptcha_failed',
                        'message' => __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'),
                        'status' => 400
                    ];
                }
                // For v2, just check success
                else if ($recaptcha_version === 'reCAPTCHAv2' && 
                    (!isset($result['success']) || !$result['success'])) {
                    return [
                        'code' => 'recaptcha_failed',
                        'message' => __('reCAPTCHA verification failed. Please try again.', 'ht-contactform'),
                        'status' => 400
                    ];
                }
                return true;
            }
        }
        return [
            'code' => 'recaptcha_not_configured',
            'message' => __('reCAPTCHA is not configured.', 'ht-contactform'),
            'status' => 400
        ];
    }

}