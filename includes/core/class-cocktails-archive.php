<?php
/**
 * Cocktail Archive Template
 *
 * @package LiveFoundation
 */

// Create a class for handling the frontend archive functionality
class Cocktail_Archive {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode for cocktail archive
        add_shortcode('cocktail_archive', array($this, 'archive_shortcode'));
        
        // AJAX handlers for filtering
        add_action('wp_ajax_filter_cocktails', array($this, 'filter_cocktails'));
        add_action('wp_ajax_nopriv_filter_cocktails', array($this, 'filter_cocktails'));
        
        // Filter the main archive query
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('cocktail-archive', LIVE_FOUNDATION_PLUGIN_URL . 'assets/js/archive.js', array('jquery'), LIVE_FOUNDATION_VERSION, true);
        
        wp_localize_script('cocktail-archive', 'cocktail_archive', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cocktail_archive_nonce')
        ));
    }
    
    /**
     * Modify the main archive query
     */
    public function modify_archive_query($query) {
        if (!is_admin() && $query->is_main_query() && is_post_type_archive('cocktail')) {
            // Set posts per page
            $query->set('posts_per_page', 12);
            
            // Handle sorting
            if (isset($_GET['orderby'])) {
                switch ($_GET['orderby']) {
                    case 'title':
                        $query->set('orderby', 'title');
                        $query->set('order', 'ASC');
                        break;
                    case 'date':
                        $query->set('orderby', 'date');
                        $query->set('order', 'DESC');
                        break;
                    case 'rating':
                        $query->set('meta_key', '_cocktail_rating');
                        $query->set('orderby', 'meta_value_num');
                        $query->set('order', 'DESC');
                        break;
                    case 'difficulty':
                        $query->set('meta_key', '_cocktail_difficulty');
                        $query->set('orderby', 'meta_value');
                        $query->set('order', 'ASC');
                        break;
                }
            }
            
            // Handle filtering by category
            if (isset($_GET['cocktail_category']) && !empty($_GET['cocktail_category'])) {
                $query->set('tax_query', array(
                    array(
                        'taxonomy' => 'cocktail_category',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['cocktail_category'])
                    )
                ));
            }
            
            // Handle filtering by difficulty
            if (isset($_GET['difficulty']) && !empty($_GET['difficulty'])) {
                $query->set('meta_query', array(
                    array(
                        'key' => '_cocktail_difficulty',
                        'value' => sanitize_text_field($_GET['difficulty']),
                        'compare' => '='
                    )
                ));
            }
            
            // Handle search
            if (isset($_GET['cocktail_search']) && !empty($_GET['cocktail_search'])) {
                $query->set('s', sanitize_text_field($_GET['cocktail_search']));
            }
        }
        
        return $query;
    }
    
    /**
     * Archive page shortcode [cocktail_archive]
     */
    public function archive_shortcode($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 12,
        ), $atts);
        
        // Get all categories for filter
        $categories = get_terms(array(
            'taxonomy' => 'cocktail_category',
            'hide_empty' => true,
        ));
        
        // Get all ingredients for filter
        $ingredients = get_posts(array(
            'post_type' => 'ingredient',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ob_start();
        ?>
        <h1>Archive</h1>
        <div class="cocktail-archive-container">
            <div class="cocktail-filters">
                <form id="cocktail-filter-form" method="get" action="<?php echo esc_url(get_post_type_archive_link('cocktail')); ?>">
                    <div class="filter-section">
                        <h3><?php _e('Search', 'live-foundation'); ?></h3>
                        <input type="text" name="cocktail_search" placeholder="<?php _e('Search cocktails...', 'live-foundation'); ?>" value="<?php echo isset($_GET['cocktail_search']) ? esc_attr($_GET['cocktail_search']) : ''; ?>">
                    </div>
                    
                    <div class="filter-section">
                        <h3><?php _e('Categories', 'live-foundation'); ?></h3>
                        <select name="cocktail_category">
                            <option value=""><?php _e('All Categories', 'live-foundation'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo $category->slug; ?>" <?php selected(isset($_GET['cocktail_category']) ? $_GET['cocktail_category'] : '', $category->slug); ?>><?php echo $category->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-section">
                        <h3><?php _e('Difficulty', 'live-foundation'); ?></h3>
                        <select name="difficulty">
                            <option value=""><?php _e('Any Difficulty', 'live-foundation'); ?></option>
                            <option value="easy" <?php selected(isset($_GET['difficulty']) ? $_GET['difficulty'] : '', 'easy'); ?>><?php _e('Easy', 'live-foundation'); ?></option>
                            <option value="medium" <?php selected(isset($_GET['difficulty']) ? $_GET['difficulty'] : '', 'medium'); ?>><?php _e('Medium', 'live-foundation'); ?></option>
                            <option value="hard" <?php selected(isset($_GET['difficulty']) ? $_GET['difficulty'] : '', 'hard'); ?>><?php _e('Hard', 'live-foundation'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-section">
                        <h3><?php _e('Ingredients', 'live-foundation'); ?></h3>
                        <div class="ingredient-filter">
                            <p><?php _e('Filter by available ingredients:', 'live-foundation'); ?></p>
                            <div class="ingredient-list">
                                <?php foreach ($ingredients as $ingredient) : ?>
                                <label>
                                    <input type="checkbox" name="available_ingredients[]" value="<?php echo $ingredient->ID; ?>" <?php checked(isset($_GET['available_ingredients']) && in_array($ingredient->ID, $_GET['available_ingredients']), true); ?>>
                                    <?php echo $ingredient->post_title; ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3><?php _e('Sort By', 'live-foundation'); ?></h3>
                        <select name="orderby">
                            <option value="title" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'title'); ?>><?php _e('Name', 'live-foundation'); ?></option>
                            <option value="date" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'date'); ?>><?php _e('Newest', 'live-foundation'); ?></option>
                            <option value="rating" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'rating'); ?>><?php _e('Highest Rated', 'live-foundation'); ?></option>
                            <option value="difficulty" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'difficulty'); ?>><?php _e('Difficulty', 'live-foundation'); ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="button filter-button"><?php _e('Apply Filters', 'live-foundation'); ?></button>
                    <a href="<?php echo esc_url(get_post_type_archive_link('cocktail')); ?>" class="button reset-button"><?php _e('Reset Filters', 'live-foundation'); ?></a>
                </form>
            </div>
            
            <div class="cocktail-results">
                <?php
                // Set up the query
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $args = array(
                    'post_type' => 'cocktail',
                    'posts_per_page' => $atts['per_page'],
                    'paged' => $paged
                );
                
                // Check if we need to filter by available ingredients
                if (isset($_GET['available_ingredients']) && !empty($_GET['available_ingredients'])) {
                    // Only show cocktails that use these ingredients
                    $args['meta_query'] = array('relation' => 'AND');
                    
                    // Helper function to check if all cocktail ingredients are available
                    $cocktails_with_available_ingredients = $this->get_cocktails_with_available_ingredients($_GET['available_ingredients']);
                    
                    if (!empty($cocktails_with_available_ingredients)) {
                        $args['post__in'] = $cocktails_with_available_ingredients;
                    } else {
                        $args['post__in'] = array(0); // No results if no cocktails match
                    }
                }
                
                $cocktails_query = new WP_Query($args);
                
                if ($cocktails_query->have_posts()) :
                ?>
                <div class="cocktail-grid">
                    <?php while ($cocktails_query->have_posts()) : $cocktails_query->the_post(); ?>
                    <div class="cocktail-card">
                        <a href="<?php the_permalink(); ?>" class="cocktail-thumbnail">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('cocktail-thumbnail'); ?>
                            <?php else : ?>
                                <div class="no-thumbnail"><?php _e('No Image', 'live-foundation'); ?></div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="cocktail-details">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            
                            <?php
                            // Display difficulty
                            $difficulty = get_post_meta(get_the_ID(), '_cocktail_difficulty', true);
                            if ($difficulty) {
                                echo '<span class="difficulty ' . esc_attr($difficulty) . '">' . ucfirst($difficulty) . '</span>';
                            }
                            
                            // Display rating
                            $rating = get_post_meta(get_the_ID(), '_cocktail_rating', true);
                            if ($rating) {
                                echo '<div class="star-rating">';
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<span class="star ' . ($i <= $rating ? 'filled' : 'empty') . '">★</span>';
                                }
                                echo '</div>';
                            }
                            ?>
                            
                            <div class="cocktail-meta">
                                <?php
                                // Display prep time
                                $prep_time = get_post_meta(get_the_ID(), '_cocktail_prep_time', true);
                                if ($prep_time) {
                                    echo '<span class="prep-time">' . sprintf(__('%d min', 'live-foundation'), $prep_time) . '</span>';
                                }
                                
                                // Display glass type
                                $glass_type = get_post_meta(get_the_ID(), '_cocktail_glass_type', true);
                                if ($glass_type) {
                                    echo '<span class="glass-type">' . esc_html($glass_type) . '</span>';
                                }
                                ?>
                            </div>
                            
                            <?php
                            // Display favorite button if user is logged in
                            if (is_user_logged_in()) {
                                $user_id = get_current_user_id();
                                $favorites = get_user_meta($user_id, '_cocktail_favorites', true);
                                $is_favorite = is_array($favorites) && in_array(get_the_ID(), $favorites);
                                ?>
                                <button class="favorite-button <?php echo $is_favorite ? 'is-favorite' : ''; ?>" data-cocktail="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('favorite_nonce_' . get_the_ID()); ?>">
                                    <span class="dashicons <?php echo $is_favorite ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></span>
                                    <?php echo $is_favorite ? __('Favorited', 'live-foundation') : __('Add to Favorites', 'live-foundation'); ?>
                                </button>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="cocktail-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $cocktails_query->max_num_pages,
                        'prev_text' => __('← Previous', 'live-foundation'),
                        'next_text' => __('Next →', 'live-foundation')
                    ));
                    ?>
                </div>
                
                <?php else : ?>
                <div class="no-results">
                    <p><?php _e('No cocktails found matching your criteria.', 'live-foundation'); ?></p>
                </div>
                <?php endif; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get cocktails that can be made with available ingredients
     */
    public function get_cocktails_with_available_ingredients($available_ingredients) {
        $available_cocktails = array();
        
        // Get all cocktails
        $cocktails = get_posts(array(
            'post_type' => 'cocktail',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($cocktails as $cocktail_id) {
            $cocktail_ingredients = get_post_meta($cocktail_id, '_cocktail_ingredients', true);
            
            if (!is_array($cocktail_ingredients) || empty($cocktail_ingredients)) {
                continue;
            }
            
            $can_make = true;
            
            // Check if all ingredients in the cocktail are in our available ingredients
            foreach ($cocktail_ingredients as $ingredient) {
                if (!in_array($ingredient['id'], $available_ingredients)) {
                    $can_make = false;
                    break;
                }
            }
            
            if ($can_make) {
                $available_cocktails[] = $cocktail_id;
            }
        }
        
        return $available_cocktails;
    }
    
    /**
     * AJAX handler for filtering cocktails
     */
    public function filter_cocktails() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cocktail_archive_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'live-foundation')));
        }
        
        // Build query args based on filter values
        $args = array(
            'post_type' => 'cocktail',
            'posts_per_page' => 12,
        );
        
        // Handle filtering here (similar to modify_archive_query function)
        
        $query = new WP_Query($args);
        $html = '';
        
        if ($query->have_posts()) {
            ob_start();
            
            echo '<div class="cocktail-grid">';
            
            while ($query->have_posts()) {
                $query->the_post();
                
                // Output cocktail HTML (similar to the HTML in the shortcode function)
            }
            
            echo '</div>';
            
            $html = ob_get_clean();
        } else {
            $html = '<div class="no-results"><p>' . __('No cocktails found matching your criteria.', 'live-foundation') . '</p></div>';
        }
        
        wp_reset_postdata();
        wp_send_json_success(array('html' => $html));
    }
}

// Initialize class
new Cocktail_Archive();