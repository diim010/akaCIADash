<?php
/**
 * Live Foundation Admin
 *
 * Handles the admin functionality of the plugin
 *
 * @package LiveFoundation
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Live_Foundation_Admin class
 */
class Live_Foundation_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // Save meta box data
        add_action('save_post', array($this, 'save_meta_box_data'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main menu item
        add_menu_page(
            __('Live Foundation', 'live-foundation'),
            __('Live Foundation', 'live-foundation'),
            'manage_options',
            'live-foundation',
            array($this, 'admin_page_display'),
            'dashicons-admin-plugins',
            30
        );

        // Add submenu items
        add_submenu_page(
            'live-foundation',
            __('Settings', 'live-foundation'),
            __('Settings', 'live-foundation'),
            'manage_options',
            'live-foundation-settings',
            array($this, 'settings_page_display')
        );

        add_submenu_page(
            'live-foundation',
            __('Tools', 'live-foundation'),
            __('Tools', 'live-foundation'),
            'manage_options',
            'live-foundation-tools',
            array($this, 'tools_page_display')
        );
    }

    /**
     * Main admin page display
     */
    public function admin_page_display() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Live Foundation Dashboard', 'live-foundation'); ?></h1>
            <div class="welcome-panel">
                <div class="welcome-panel-content">
                    <h2><?php echo esc_html__('Live Foundation!', 'live-foundation'); ?></h2>
                    <p class="about-description"><?php echo esc_html__('A powerful foundation plugin for WordPress.', 'live-foundation'); ?></p>
                    <div class="welcome-panel-column-container">
                        <div class="welcome-panel-column">
                            <h3><?php echo esc_html__('Get Started', 'live-foundation'); ?></h3>
                            <a class="button button-primary button-hero" href="<?php echo admin_url('admin.php?page=live-foundation-settings'); ?>"><?php echo esc_html__('Configure Settings', 'live-foundation'); ?></a>
                        </div>
                        <div class="welcome-panel-column">
                            <h3><?php echo esc_html__('Documentation', 'live-foundation'); ?></h3>
                            <ul>
                                <li><a href="#" class="welcome-icon welcome-learn-more"><?php echo esc_html__('User Guide', 'live-foundation'); ?></a></li>
                                <li><a href="#" class="welcome-icon welcome-learn-more"><?php echo esc_html__('Developer Documentation', 'live-foundation'); ?></a></li>
                            </ul>
                        </div>
                        <div class="welcome-panel-column welcome-panel-last">
                            <h3><?php echo esc_html__('Support', 'live-foundation'); ?></h3>
                            <ul>
                                <li><a href="#" class="welcome-icon welcome-support-forum"><?php echo esc_html__('Support Forum', 'live-foundation'); ?></a></li>
                                <li><a href="#" class="welcome-icon welcome-support-contact"><?php echo esc_html__('Contact Us', 'live-foundation'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page display
     */
    public function settings_page_display() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Live Foundation Settings', 'live-foundation'); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Output security fields
                settings_fields('live_foundation_settings');
                
                // Output setting sections
                do_settings_sections('live_foundation_settings');
                
                // Submit button
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Tools page display
     */
    public function tools_page_display() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Live Foundation Tools', 'live-foundation'); ?></h1>
            <div class="card">
                <h2><?php echo esc_html__('Import/Export Settings', 'live-foundation'); ?></h2>
                <p><?php echo esc_html__('Import or export your Live Foundation settings.', 'live-foundation'); ?></p>
                <p>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=live-foundation-tools&action=export'), 'live-foundation-export')); ?>" class="button"><?php echo esc_html__('Export Settings', 'live-foundation'); ?></a>
                    <button type="button" class="button" id="live-foundation-import-button"><?php echo esc_html__('Import Settings', 'live-foundation'); ?></button>
                </p>
            </div>
            <div class="card">
                <h2><?php echo esc_html__('System Information', 'live-foundation'); ?></h2>
                <p><?php echo esc_html__('View your system information for debugging purposes.', 'live-foundation'); ?></p>
                <p>
                    <button type="button" class="button" id="live-foundation-system-info-button"><?php echo esc_html__('View System Info', 'live-foundation'); ?></button>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'live-foundation') === false) {
            return;
        }

        // Admin styles
        wp_enqueue_style(
            'live-foundation-admin-style',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/css/live-foundation-admin.css',
            array(),
            LIVE_FOUNDATION_VERSION
        );

        // Admin scripts
        wp_enqueue_script(
            'live-foundation-admin-script',
            LIVE_FOUNDATION_PLUGIN_URL . 'assets/js/live-foundation-admin.js',
            array('jquery'),
            LIVE_FOUNDATION_VERSION,
            true
        );

        // Localize admin script
        wp_localize_script(
            'live-foundation-admin-script',
            'LiveFoundationAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('live-foundation-admin-nonce'),
                'import_title' => __('Import Settings', 'live-foundation'),
                'import_button' => __('Import', 'live-foundation'),
                'system_info_title' => __('System Information', 'live-foundation')
            )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting(
            'live_foundation_settings',
            'live_foundation_settings',
            array($this, 'sanitize_settings')
        );

        // Add sections
        add_settings_section(
            'live_foundation_general_section',
            __('General Settings', 'live-foundation'),
            array($this, 'general_section_callback'),
            'live_foundation_settings'
        );

        add_settings_section(
            'live_foundation_advanced_section',
            __('Advanced Settings', 'live-foundation'),
            array($this, 'advanced_section_callback'),
            'live_foundation_settings'
        );

        // Add fields to general section
        add_settings_field(
            'enable_feature',
            __('Enable Feature', 'live-foundation'),
            array($this, 'enable_feature_callback'),
            'live_foundation_settings',
            'live_foundation_general_section'
        );

        add_settings_field(
            'api_key',
            __('API Key', 'live-foundation'),
            array($this, 'api_key_callback'),
            'live_foundation_settings',
            'live_foundation_general_section'
        );

        // Add fields to advanced section
        add_settings_field(
            'cache_duration',
            __('Cache Duration', 'live-foundation'),
            array($this, 'cache_duration_callback'),
            'live_foundation_settings',
            'live_foundation_advanced_section'
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'live-foundation'),
            array($this, 'debug_mode_callback'),
            'live_foundation_settings',
            'live_foundation_advanced_section'
        );
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure the general settings for Live Foundation.', 'live-foundation') . '</p>';
    }

    /**
     * Advanced section callback
     */
    public function advanced_section_callback() {
        echo '<p>' . esc_html__('Configure advanced settings for Live Foundation.', 'live-foundation') . '</p>';
    }

    /**
     * Enable feature callback
     */
    public function enable_feature_callback() {
        $options = get_option('live_foundation_settings');
        $value = isset($options['enable_feature']) ? $options['enable_feature'] : 0;
        ?>
        <input type="checkbox" id="enable_feature" name="live_foundation_settings[enable_feature]" value="1" <?php checked(1, $value); ?> />
        <label for="enable_feature"><?php echo esc_html__('Enable this feature', 'live-foundation'); ?></label>
        <?php
    }

    /**
     * API key callback
     */
    public function api_key_callback() {
        $options = get_option('live_foundation_settings');
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        ?>
        <input type="text" id="api_key" name="live_foundation_settings[api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php echo esc_html__('Enter your API key for external integrations.', 'live-foundation'); ?></p>
        <?php
    }

    /**
     * Cache duration callback
     */
    public function cache_duration_callback() {
        $options = get_option('live_foundation_settings');
        $value = isset($options['cache_duration']) ? $options['cache_duration'] : 3600;
        ?>
        <input type="number" id="cache_duration" name="live_foundation_settings[cache_duration]" value="<?php echo esc_attr($value); ?>" class="small-text" />
        <p class="description"><?php echo esc_html__('Duration in seconds to cache data. Default is 3600 (1 hour).', 'live-foundation'); ?></p>
        <?php
    }

    /**
     * Debug mode callback
     */
    public function debug_mode_callback() {
        $options = get_option('live_foundation_settings');
        $value = isset($options['debug_mode']) ? $options['debug_mode'] : 0;
        ?>
        <input type="checkbox" id="debug_mode" name="live_foundation_settings[debug_mode]" value="1" <?php checked(1, $value); ?> />
        <label for="debug_mode"><?php echo esc_html__('Enable debug mode', 'live-foundation'); ?></label>
        <p class="description"><?php echo esc_html__('Enable this for additional debugging information. Not recommended for production sites.', 'live-foundation'); ?></p>
        <?php
    }

    /**
     * Sanitize settings
     *
     * @param array $input The input to sanitize
     * @return array Sanitized input
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();

        // Sanitize checkboxes
        $sanitized_input['enable_feature'] = isset($input['enable_feature']) ? 1 : 0;
        $sanitized_input['debug_mode'] = isset($input['debug_mode']) ? 1 : 0;

        // Sanitize text fields
        if (isset($input['api_key'])) {
            $sanitized_input['api_key'] = sanitize_text_field($input['api_key']);
        }

        // Sanitize numbers
        if (isset($input['cache_duration'])) {
            $sanitized_input['cache_duration'] = absint($input['cache_duration']);
        }

        return $sanitized_input;
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'live_foundation_meta_box',
            __('Live Foundation Options', 'live-foundation'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'default'
        );
    }

    /**
     * Render meta box
     *
     * @param WP_Post $post The post object
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('live_foundation_meta_box', 'live_foundation_meta_box_nonce');

        // Get saved value
        $value = get_post_meta($post->ID, '_live_foundation_option', true);

        // Output field
        ?>
        <p>
            <label for="live_foundation_option"><?php echo esc_html__('Option:', 'live-foundation'); ?></label>
            <select name="live_foundation_option" id="live_foundation_option">
                <option value=""><?php echo esc_html__('Default', 'live-foundation'); ?></option>
                <option value="option1" <?php selected($value, 'option1'); ?>><?php echo esc_html__('Option 1', 'live-foundation'); ?></option>
                <option value="option2" <?php selected($value, 'option2'); ?>><?php echo esc_html__('Option 2', 'live-foundation'); ?></option>
                <option value="option3" <?php selected($value, 'option3'); ?>><?php echo esc_html__('Option 3', 'live-foundation'); ?></option>
            </select>
        </p>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id The ID of the post being saved
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['live_foundation_meta_box_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['live_foundation_meta_box_nonce'], 'live_foundation_meta_box')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if we're doing an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check if the field is set and save it
        if (isset($_POST['live_foundation_option'])) {
            update_post_meta(
                $post_id,
                '_live_foundation_option',
                sanitize_text_field($_POST['live_foundation_option'])
            );
        }
    }
}

// Initialize the admin class
new Live_Foundation_Admin();