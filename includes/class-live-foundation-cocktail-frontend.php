<?php
/**
 * Cocktail Frontend
 *
 * Handles the frontend display of cocktails
 *
 * @package LiveFoundation
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Live_Foundation_Cocktail_Frontend class
 */
class Live_Foundation_Cocktail_Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('cocktail_list', array($this, 'cocktail_list_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add AJAX handlers for filtering
        add_action('wp_ajax_filter_cocktails', array($this, 'ajax_filter_cocktails'));
        add_action('wp_ajax_nopriv_filter_cocktails', array($this, 'ajax_filter_cocktails'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'live-foundation-cocktails', 
            plugin_dir_url(__FILE__) . '../assets/css/cocktails.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'live-foundation-cocktails',
            plugin_dir_url(__FILE__) . '../assets/js/cocktails.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script(
            'live-foundation-cocktails',
            'liveFoundationData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('live-foundation/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );
    }

    /**
     * Cocktail list shortcode
     * 
     * Usage: [cocktail_list categories="vodka,gin" limit="12" sort="title"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function cocktail_list_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'categories' => '',
                'limit' => 12,
                'sort' => 'title',
            ),
            $atts,
            'cocktail_list'
        );
        
        // Get categories for filter
        $categories = get_terms(array(
            'taxonomy' => 'cocktail_category',
            'hide_empty' => true,
        ));
        
        // Start output buffering
        ob_start();
        
        // Get difficulty levels for filter
        $difficulty_levels = array(
            'easy' => __('Easy', 'live-foundation'),
            'medium' => __('Medium', 'live-foundation'),
            'hard' => __('Hard', 'live-foundation'),
        );
        
        ?>
        <div class="live-foundation-cocktails">
            <div class="cocktail-filters">
                <div class="filter-group">
                    <label for="category-filter"><?php _e('Category:', 'live-foundation'); ?></label>
                    <select id="category-filter">
                        <option value=""><?php _e('All Categories', 'live-foundation'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>"><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="difficulty-filter"><?php _e('Difficulty:', 'live-foundation'); ?></label>
                    <select id="difficulty-filter">
                        <option value=""><?php _e('All Difficulties', 'live-foundation'); ?></option>
                        <?php foreach ($difficulty_levels as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort-filter"><?php _e('Sort By:', 'live-foundation'); ?></label>
                    <select id="sort-filter">
                        <option value="title"><?php _e('Name (A-Z)', 'live-foundation'); ?></option>
                        <option value="title-desc"><?php _e('Name (Z-A)', 'live-foundation'); ?></option>
                        <option value="date"><?php _e('Newest First', 'live-foundation'); ?></option>
                        <option value="date-asc"><?php _e('Oldest First', 'live-foundation'); ?></option>
                    </select>
                </div>
                
                <button id="apply-filters" class="button"><?php _e('Apply Filters', 'live-foundation'); ?></button>
            </div>
            
            <div id="cocktail-grid" class="cocktails__list" data-total="0" data-page="1" data-limit="<?php echo esc_attr($atts['limit']); ?>">
                <div class="cocktail-loading">
                    <div class="spinner"></div>
                    <p><?php _e('Loading cocktails...', 'live-foundation'); ?></p>
                </div>
                <div class="cocktail-load-more">
                 <button id="load-more-cocktails" class="button" style="display: none;"><?php _e('Load More', 'live-foundation'); ?></button>
                 </div>
            </div>
            <div id="cocktail-modal" class="cocktail__info">
                <div class="cocktail-modal-content">
                    <span class="close-modal">&times;</span>
                    <div id="cocktail-modal-body"></div>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for filtering cocktails
     */
    public function ajax_filter_cocktails() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $difficulty = isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'title';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;
        
        // Get cocktails based on filters
        $cocktails = $this->get_filtered_cocktails($category, $difficulty, $sort, $page, $limit);
        
        // Format cocktails for output
        $formatted_cocktails = array();
        
        foreach ($cocktails['items'] as $cocktail) {
            $formatted_cocktails[] = $this->format_cocktail_for_display($cocktail);
        }
        
        // Send response
        wp_send_json_success(array(
            'cocktails' => $formatted_cocktails,
            'total' => $cocktails['total'],
            'pages' => ceil($cocktails['total'] / $limit),
        ));
    }
    
    /**
     * Get filtered cocktails from the database
     * 
     * @param string $category Category slug
     * @param string $difficulty Difficulty level
     * @param string $sort Sort order
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Cocktails and total count
     */
    private function get_filtered_cocktails($category, $difficulty, $sort, $page, $limit) {
        $args = array(
            'post_type' => 'cocktail',
            'posts_per_page' => $limit,
            'paged' => $page,
            'post_status' => 'publish',
        );
        
        // Add category filter
        if (!empty($category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'cocktail_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        // Add difficulty filter
        if (!empty($difficulty)) {
            $args['meta_query'] = array(
                array(
                    'key' => '_cocktail_difficulty',
                    'value' => $difficulty,
                    'compare' => '=',
                ),
            );
        }
        
        // Add sorting
        switch ($sort) {
            case 'title-desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            case 'date-asc':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'title':
            default:
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
        }
        
        // Get posts
        $query = new WP_Query($args);
        $cocktails = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $cocktails[] = $this->get_cocktail_data(get_the_ID());
            }
            
            wp_reset_postdata();
        }
        
        return array(
            'items' => $cocktails,
            'total' => $query->found_posts,
        );
    }
    
    /**
     * Get cocktail data for a single cocktail
     * 
     * @param int $cocktail_id Post ID
     * @return array Cocktail data
     */
    private function get_cocktail_data($cocktail_id) {
        $cocktail = get_post($cocktail_id);
        
        if (!$cocktail) {
            return null;
        }
        
        // Get meta values
        $difficulty = get_post_meta($cocktail_id, '_cocktail_difficulty', true);
        $prep_time = get_post_meta($cocktail_id, '_cocktail_prep_time', true);
        $glass_type = get_post_meta($cocktail_id, '_cocktail_glass_type', true);
        $ingredients = get_post_meta($cocktail_id, '_cocktail_ingredients', true);
        
        // Get featured image
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($cocktail_id), 'medium');
        $image_url = $image ? $image[0] : '';
        
        // Get categories
        $categories = wp_get_post_terms($cocktail_id, 'cocktail_category', array('fields' => 'all'));
        $category_data = array();
        
        foreach ($categories as $category) {
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
            );
        }
        
        // Format ingredients with data
        $formatted_ingredients = array();
        
        if (is_array($ingredients)) {
            foreach ($ingredients as $ingredient) {
                $ingredient_post = get_post($ingredient['id']);
                
                if ($ingredient_post) {
                    $formatted_ingredients[] = array(
                        'id' => $ingredient['id'],
                        'name' => $ingredient_post->post_title,
                        'amount' => $ingredient['amount'],
                        'unit' => $ingredient['unit'],
                    );
                }
            }
        }
        
        return array(
            'id' => $cocktail_id,
            'title' => $cocktail->post_title,
            'content' => $cocktail->post_content,
            'excerpt' => has_excerpt($cocktail_id) ? get_the_excerpt($cocktail_id) : wp_trim_words($cocktail->post_content, 20),
            'date' => $cocktail->post_date,
            'image' => $image_url,
            'meta' => array(
                'difficulty' => $difficulty,
                'prep_time' => $prep_time,
                'glass_type' => $glass_type,
            ),
            'categories' => $category_data,
            'ingredients' => $formatted_ingredients,
        );
    }
    
    /**
     * Format cocktail data for display
     * 
     * @param array $cocktail Cocktail data
     * @return string HTML output
     */
    private function format_cocktail_for_display($cocktail) {
        if (!$cocktail) {
            return '';
        }
        
        // Get difficulty label
        $difficulty_labels = array(
            'easy' => __('Easy', 'live-foundation'),
            'medium' => __('Medium', 'live-foundation'),
            'hard' => __('Hard', 'live-foundation'),
        );
        
        $difficulty = isset($cocktail['meta']['difficulty']) ? $cocktail['meta']['difficulty'] : 'easy';
        $difficulty_label = isset($difficulty_labels[$difficulty]) ? $difficulty_labels[$difficulty] : $difficulty;
        
        // Start output buffering
        ob_start();
        
        ?>
        <div class="cocktail__card" data-id="<?php echo esc_attr($cocktail['id']); ?>">
            <div class="cocktail__card-image">
                <?php if (!empty($cocktail['image'])) : ?>
                    <img src="<?php echo esc_url($cocktail['image']); ?>" alt="<?php echo esc_attr($cocktail['title']); ?>">
                <?php else : ?>
                    <div class="no-image"></div>
                <?php endif; ?>
            </div>
            <div class="cocktail__card-details">
                <h3 class="cocktail-title"><?php echo esc_html($cocktail['title']); ?></h3>
                <div class="cocktail-meta">
                    <?php if (!empty($cocktail['meta']['prep_time'])) : ?>
                        <span class="prep-time"><i class="dashicons dashicons-clock"></i> <?php echo esc_html($cocktail['meta']['prep_time']); ?> <?php _e('min', 'live-foundation'); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($difficulty)) : ?>
                        <span class="difficulty difficulty-<?php echo esc_attr($difficulty); ?>">
                            <i class="dashicons dashicons-chart-bar"></i> <?php echo esc_html($difficulty_label); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($cocktail['meta']['glass_type'])) : ?>
                        <span class="glass-type"><i class="dashicons dashicons-beer"></i> <?php echo esc_html($cocktail['meta']['glass_type']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="cocktail-excerpt">
                    <?php echo wp_kses_post($cocktail['excerpt']); ?>
                </div>
                <div class="cocktail-view">
                    <button class="view-cocktail button" data-id="<?php echo esc_attr($cocktail['id']); ?>">
                        <?php _e('View Recipe', 'live-foundation'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get cocktail modal content
     * 
     * @param int $cocktail_id Cocktail ID
     * @return array Cocktail modal data
     */
    public function get_cocktail_modal_content($cocktail_id) {
        $cocktail = $this->get_cocktail_data($cocktail_id);
        
        if (!$cocktail) {
            return array(
                'success' => false,
                'message' => __('Cocktail not found', 'live-foundation'),
            );
        }
        
        // Start output buffering for HTML content
        ob_start();
        
        ?>
        <div class="cocktail-modal-header">
            <h2><?php echo esc_html($cocktail['title']); ?></h2>
        </div>
        
        <div class="cocktail-modal-body">
            <div class="cocktail-modal-image-wrapper">
                <?php if (!empty($cocktail['image'])) : ?>
                    <img src="<?php echo esc_url($cocktail['image']); ?>" alt="<?php echo esc_attr($cocktail['title']); ?>" class="cocktail-modal-image">
                <?php endif; ?>
            </div>
            
            <div class="cocktail-modal-details">
                <div class="cocktail-modal-meta">
                    <?php if (!empty($cocktail['meta']['difficulty'])) : ?>
                        <div class="cocktail-meta-item">
                            <span class="meta-label"><?php _e('Difficulty:', 'live-foundation'); ?></span>
                            <span class="meta-value"><?php echo esc_html(ucfirst($cocktail['meta']['difficulty'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cocktail['meta']['prep_time'])) : ?>
                        <div class="cocktail-meta-item">
                            <span class="meta-label"><?php _e('Preparation Time:', 'live-foundation'); ?></span>
                            <span class="meta-value"><?php echo esc_html($cocktail['meta']['prep_time']); ?> <?php _e('minutes', 'live-foundation'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cocktail['meta']['glass_type'])) : ?>
                        <div class="cocktail-meta-item">
                            <span class="meta-label"><?php _e('Glass Type:', 'live-foundation'); ?></span>
                            <span class="meta-value"><?php echo esc_html($cocktail['meta']['glass_type']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($cocktail['ingredients'])) : ?>
                    <div class="cocktail-ingredients">
                        <h3><?php _e('Ingredients', 'live-foundation'); ?></h3>
                        <ul>
                            <?php foreach ($cocktail['ingredients'] as $ingredient) : ?>
                                <li>
                                    <span class="ingredient-amount"><?php echo esc_html($ingredient['amount']); ?> <?php echo esc_html($ingredient['unit']); ?></span>
                                    <span class="ingredient-name"><?php echo esc_html($ingredient['name']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="cocktail-description">
                    <h3><?php _e('Instructions', 'live-foundation'); ?></h3>
                    <?php echo wp_kses_post($cocktail['content']); ?>
                </div>
                
                <?php if (!empty($cocktail['categories'])) : ?>
                    <div class="cocktail-categories">
                        <p class="category-label"><?php _e('Categories:', 'live-foundation'); ?></p>
                        <div class="category-tags">
                            <?php foreach ($cocktail['categories'] as $category) : ?>
                                <span class="category-tag"><?php echo esc_html($category['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        $content = ob_get_clean();
        
        return array(
            'success' => true,
            'content' => $content,
        );
    }
}

// Initialize class
new Live_Foundation_Cocktail_Frontend();