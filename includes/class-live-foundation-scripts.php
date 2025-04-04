<?php
/**
 * Live Foundation Scripts
 *
 * Handles the loading of scripts and styles for the plugin
 *
 * @package LiveFoundation
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Live_Foundation_Scripts class
 */
class Live_Foundation_Scripts {

    /**
     * Constructor
     */
    public function __construct() {
        // Register and enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'), 10);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
        
        // Register script localization
        add_action('wp_enqueue_scripts', array($this, 'localize_scripts'), 30);
        
        // Add async/defer attributes to scripts
        add_filter('script_loader_tag', array($this, 'script_loader_tag'), 10, 3);
    }

    /**
     * Register scripts and styles
     */
    public function register_scripts() {
        // Get plugin options
        $options = get_option('live_foundation_settings');
        $debug_mode = isset($options['debug_mode']) ? $options['debug_mode'] : false;
        
        // Define script suffix based on debug mode
        $suffix = ($debug_mode) ? '' : '.min';
        
        // Register styles
        wp_register_style(
            'live-foundation-style',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/css/live-foundation' . $suffix . '.css',
            array(),
            LIVE_FOUNDATION_VERSION
        );
        wp_register_style(
            'live-foundation-cocktails',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/css/cocktails.css',
            array(),
            LIVE_FOUNDATION_VERSION
        );
        // Register main script
        wp_register_script(
            'live-foundation-script',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/js/live-foundation' . $suffix . '.js',
            array('jquery'),
            LIVE_FOUNDATION_VERSION,
            true
        );
        
        // Register additional scripts
        wp_register_script(
            'live-foundation-utils',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/js/utils' . $suffix . '.js',
            array('jquery'),
            LIVE_FOUNDATION_VERSION,
            true
        );
        wp_register_script(
            'live-foundation-cocktails',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/js/cocktails.js',
            array('jquery'),
            LIVE_FOUNDATION_VERSION,
            true
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Get plugin options
        $options = get_option('live_foundation_settings');
        $enable_feature = isset($options['enable_feature']) ? $options['enable_feature'] : false;
        
        // Enqueue main styles
        wp_enqueue_style('live-foundation-style');
        
        // Conditionally enqueue scripts based on settings
        if ($enable_feature) {
            wp_enqueue_script('live-foundation-script');
            wp_enqueue_script('live-foundation-utils');
        }
    }

    /**
     * Localize scripts
     */
    public function localize_scripts() {
        // Get plugin options
        $options = get_option('live_foundation_settings');
        
        // Localize the main script with necessary data
        wp_localize_script(
            'live-foundation-script',
            'LiveFoundationParams',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('live-foundation-nonce'),
                'debug_mode' => isset($options['debug_mode']) ? (bool) $options['debug_mode'] : false,
                'cache_duration' => isset($options['cache_duration']) ? absint($options['cache_duration']) : 3600,
                'i18n' => array(
                    'error_message' => __('An error occurred. Please try again.', 'live-foundation'),
                    'success_message' => __('Operation completed successfully.', 'live-foundation'),
                    'loading' => __('Loading...', 'live-foundation'),
                ),
            )
        );
    }

    /**
     * Add async/defer attributes to scripts
     *
     * @param string $tag Script HTML tag
     * @param string $handle Script handle
     * @param string $src Script source
     * @return string Modified script HTML tag
     */
    public function script_loader_tag($tag, $handle, $src) {
        // List of scripts to load async
        $async_scripts = array(
            'live-foundation-utils'
        );
        
        // List of scripts to load defer
        $defer_scripts = array();
        
        // Add async attribute
        if (in_array($handle, $async_scripts)) {
            $tag = str_replace(' src', ' async src', $tag);
        }
        
        // Add defer attribute
        if (in_array($handle, $defer_scripts)) {
            $tag = str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
}

// Initialize the scripts class
new Live_Foundation_Scripts();