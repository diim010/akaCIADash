<?php
/**
 * Plugin Name: Live Foundation
 * Plugin URI: https://yourwebsite.com/live-foundation
 * Description: A powerful foundation plugin for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: live-foundation
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LIVE_FOUNDATION_VERSION', '1.0.0');
define('LIVE_FOUNDATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LIVE_FOUNDATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LIVE_FOUNDATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Live_Foundation {
    /**
     * Instance of this class
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class
     *
     * @return object A single instance of this class
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    private function __construct() {
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'live-foundation',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables or options
        update_option('live_foundation_version', LIVE_FOUNDATION_VERSION);
        
        // Add any other activation tasks here
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up tasks
        flush_rewrite_rules();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Admin
        if (is_admin()) {
            require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/admin/class-live-foundation-admin.php';
        }
        
 
        // Core includes
        // require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/class-live-foundation-cpt.php';

        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/class-live-foundation-shortcodes.php';
        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/class-live-foundation-scripts.php';
        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/class-live-foundation-api.php';
        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/class-live-foundation-cocktail-frontend.php';


        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/post-types/class-ingredient-post-type.php';
        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/post-types/class-cocktail-post-type.php';


        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/core/class-cocktails-archive.php';

        // Functions
        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/live-foundation-template-functions.php';
        require_once LIVE_FOUNDATION_PLUGIN_DIR . 'includes/live-foundation-helper-functions.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        
        // Add settings link on plugin page
        add_filter('plugin_action_links_' . LIVE_FOUNDATION_PLUGIN_BASENAME, array($this, 'add_plugin_links'));
        
        // Add any other initialization hooks here
    }

    /**
     * Register scripts and styles
     */
    public function register_scripts() {
        // Register styles
        wp_register_style(
            'live-foundation-style',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/css/live-foundation.css',
            array(),
            LIVE_FOUNDATION_VERSION
        );
        
        // Register scripts
        wp_register_script(
            'live-foundation-script',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/js/live-foundation.js',
            array('jquery'),
            LIVE_FOUNDATION_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'live-foundation-script',
            'LiveFoundation',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('live-foundation-nonce')
            )
        );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Array of plugin action links
     * @return array Modified array of plugin action links
     */
    public function add_plugin_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=live-foundation-settings') . '">' . __('Settings', 'live-foundation') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }
}

// Initialize the plugin
function Live_Foundation() {
    return Live_Foundation::get_instance();
}

// Start the plugin
Live_Foundation();

