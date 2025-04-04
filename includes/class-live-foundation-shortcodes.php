<?php
/**
 * Live Foundation Shortcodes
 *
 * Registers all shortcodes for the plugin
 *
 * @package LiveFoundation
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Live_Foundation_Shortcodes class
 */
class Live_Foundation_Shortcodes {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('live_foundation', array($this, 'live_foundation_shortcode'));
        add_shortcode('live_foundation_button', array($this, 'button_shortcode'));
        add_shortcode('live_foundation_info', array($this, 'info_shortcode'));
        add_shortcode('cocktails_list', array($this, 'cocktails_list_shortcode')); // Добавляем новый шорткод
    }

    /**
     * Main shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered shortcode
     */
    public function live_foundation_shortcode($atts, $content = null) {
        // Extract and merge attributes
        $atts = shortcode_atts(
            array(
                'title' => '',
                'type' => 'standard',
                'class' => '',
                'id' => '',
            ),
            $atts,
            'live_foundation'
        );

        // Get options
        $options = get_option('live_foundation_settings');
        
        // Start output buffering
        ob_start();

        // Get template
        $template_file = 'live-foundation.php';
        $template_path = LIVE_FOUNDATION_PLUGIN_DIR . 'templates/' . $template_file;
        
        // Include template if it exists
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            ?>
            <div id="<?php echo esc_attr($atts['id']); ?>" class="live-foundation-container <?php echo esc_attr($atts['class']); ?> live-foundation-type-<?php echo esc_attr($atts['type']); ?>">
                <?php if (!empty($atts['title'])) : ?>
                    <h3 class="live-foundation-title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>
                
                <div class="live-foundation-content">
                    <?php echo wp_kses_post($content); ?>
                </div>
            </div>
            <?php
        }
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Button shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered shortcode
     */
    public function button_shortcode($atts, $content = null) {
        // Extract and merge attributes
        $atts = shortcode_atts(
            array(
                'url' => '#',
                'target' => '_self',
                'style' => 'default',
                'size' => 'medium',
                'class' => '',
                'id' => '',
            ),
            $atts,
            'live_foundation_button'
        );

        // Enqueue required styles
        wp_enqueue_style('live-foundation-style');

        // Build the button
        $button = '<a href="' . esc_url($atts['url']) . '" ';
        $button .= 'target="' . esc_attr($atts['target']) . '" ';
        
        if (!empty($atts['id'])) {
            $button .= 'id="' . esc_attr($atts['id']) . '" ';
        }
        
        $button .= 'class="live-foundation-button ';
        $button .= 'live-foundation-button-' . esc_attr($atts['style']) . ' ';
        $button .= 'live-foundation-button-' . esc_attr($atts['size']) . ' ';
        $button .= esc_attr($atts['class']) . '">';
        
        $button .= do_shortcode($content);
        
        $button .= '</a>';

        return $button;
    }

    /**
     * Info shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered shortcode
     */
    public function info_shortcode($atts, $content = null) {
        // Extract and merge attributes
        $atts = shortcode_atts(
            array(
                'type' => 'info', // info, success, warning, error
                'icon' => 'true',
                'class' => '',
                'id' => '',
            ),
            $atts,
            'live_foundation_info'
        );

        // Enqueue required styles
        wp_enqueue_style('live-foundation-style');

        // Start output buffering
        ob_start();
        
        ?>
        <div 
            <?php echo !empty($atts['id']) ? 'id="' . esc_attr($atts['id']) . '"' : ''; ?> 
            class="live-foundation-info live-foundation-info-<?php echo esc_attr($atts['type']); ?> <?php echo esc_attr($atts['class']); ?>"
        >
            <?php if ($atts['icon'] == 'true') : ?>
                <div class="live-foundation-info-icon">
                    <?php 
                    // Different icon based on type
                    switch ($atts['type']) {
                        case 'success':
                            echo '<span class="dashicons dashicons-yes"></span>';
                            break;
                        case 'warning':
                            echo '<span class="dashicons dashicons-warning"></span>';
                            break;
                        case 'error':
                            echo '<span class="dashicons dashicons-dismiss"></span>';
                            break;
                        case 'info':
                        default:
                            echo '<span class="dashicons dashicons-info"></span>';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="live-foundation-info-content">
                <?php echo wp_kses_post(do_shortcode($content)); ?>
            </div>
        </div>
        <?php

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Cocktails list shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode
     */
    public function cocktails_list_shortcode($atts) {
        // Extract and merge attributes
        $atts = shortcode_atts(
            array(
                'per_page' => 12,
                'layout' => 'grid', // grid или list
                'class' => '',
            ),
            $atts,
            'cocktails_list'
        );

        // Подключаем стили и скрипты
        wp_enqueue_style('live-foundation-cocktails');
        wp_enqueue_script('live-foundation-cocktails');

        // Передаем параметры в JavaScript
        wp_localize_script('live-foundation-cocktails', 'liveCocktails', array(
            'restUrl' => get_rest_url(null, 'live-foundation/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'perPage' => $atts['per_page'],
            'layout' => $atts['layout'],
            'i18n' => array(
                'loading' => __('Загрузка...', 'live-foundation'),
                'noResults' => __('Коктейли не найдены', 'live-foundation'),
                'error' => __('Ошибка загрузки данных', 'live-foundation'),
                'ingredients' => __('Ингредиенты:', 'live-foundation'),
                'instructions' => __('Инструкция:', 'live-foundation'),
            )
        ));

        ob_start();
        ?>
        <div class="cocktails-container <?php echo esc_attr($atts['class']); ?>" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            <div class="cocktails-filters">
                <input type="text" class="cocktails-search" placeholder="<?php esc_attr_e('Поиск коктейлей...', 'live-foundation'); ?>">
            </div>
            
            <div class="cocktails-grid"></div>
            
            <div class="cocktails-loader">
                <?php esc_html_e('Загрузка...', 'live-foundation'); ?>
            </div>

            <div class="cocktails-pagination"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the shortcodes class
new Live_Foundation_Shortcodes();