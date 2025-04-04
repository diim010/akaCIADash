<?php
/**
 * Ingredient Post Type
 *
 * @package LiveFoundation
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Ingredient_Post_Type Class
 */
class Ingredient_Post_Type {

    /**
     * Constructor
     */
    public function __construct() {
        // Register the post type
        add_action('init', array($this, 'register_post_type'));
        
        // Add custom meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post meta
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
    }

    /**
     * Register Ingredient post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Ingredients', 'post type general name', 'live-foundation'),
            'singular_name'      => _x('Ingredient', 'post type singular name', 'live-foundation'),
            'menu_name'          => _x('Ingredients', 'admin menu', 'live-foundation'),
            'name_admin_bar'     => _x('Ingredient', 'add new on admin bar', 'live-foundation'),
            'add_new'            => _x('Add New', 'ingredient', 'live-foundation'),
            'add_new_item'       => __('Add New Ingredient', 'live-foundation'),
            'new_item'           => __('New Ingredient', 'live-foundation'),
            'edit_item'          => __('Edit Ingredient', 'live-foundation'),
            'view_item'          => __('View Ingredient', 'live-foundation'),
            'all_items'          => __('All Ingredients', 'live-foundation'),
            'search_items'       => __('Search Ingredients', 'live-foundation'),
            'parent_item_colon'  => __('Parent Ingredients:', 'live-foundation'),
            'not_found'          => __('No ingredients found.', 'live-foundation'),
            'not_found_in_trash' => __('No ingredients found in Trash.', 'live-foundation')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Cocktail ingredients for bar staff', 'live-foundation'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'ingredient'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-carrot',
            'supports'           => array('title', 'editor', 'thumbnail')
        );

        register_post_type('ingredient', $args);
        
        // Register taxonomy for ingredient types
        register_taxonomy('ingredient_type', 'ingredient', array(
            'label' => __('Ingredient Types', 'live-foundation'),
            'hierarchical' => true,
            'show_admin_column' => true,
        ));
    }

    /**
     * Add meta boxes for ingredient post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'ingredient_details',
            __('Ingredient Details', 'live-foundation'),
            array($this, 'render_details_meta_box'),
            'ingredient',
            'normal',
            'high'
        );
    }

    /**
     * Render the details meta box
     */
    public function render_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('ingredient_details_nonce', 'ingredient_details_nonce');
        
        // Get saved values
        $alcohol_content = get_post_meta($post->ID, '_ingredient_alcohol_content', true);
        $price = get_post_meta($post->ID, '_ingredient_price', true);
        $stock = get_post_meta($post->ID, '_ingredient_stock', true);
        
        ?>
        <div class="ingredient-meta-box">
            <p>
                <label for="ingredient_alcohol_content"><?php _e('Alcohol Content (%)', 'live-foundation'); ?></label>
                <input type="number" id="ingredient_alcohol_content" name="ingredient_alcohol_content" value="<?php echo esc_attr($alcohol_content); ?>" min="0" max="100" step="0.1">
            </p>
            <p>
                <label for="ingredient_price"><?php _e('Price', 'live-foundation'); ?></label>
                <input type="text" id="ingredient_price" name="ingredient_price" value="<?php echo esc_attr($price); ?>">
            </p>
            <p>
                <label for="ingredient_stock"><?php _e('Stock Level', 'live-foundation'); ?></label>
                <input type="number" id="ingredient_stock" name="ingredient_stock" value="<?php echo esc_attr($stock); ?>" min="0">
            </p>
        </div>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id, $post) {
        // Check if our nonce is set
        if (!isset($_POST['ingredient_details_nonce'])) {
            return $post_id;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['ingredient_details_nonce'], 'ingredient_details_nonce')) {
            return $post_id;
        }

        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check the user's permissions
        if ('ingredient' !== $post->post_type || !current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Save ingredient details
        $fields = array(
            'ingredient_alcohol_content' => 'float',
            'ingredient_price' => 'text',
            'ingredient_stock' => 'int',
        );

        foreach ($fields as $field => $type) {
            if (isset($_POST[$field])) {
                $value = '';
                
                switch ($type) {
                    case 'int':
                        $value = intval($_POST[$field]);
                        break;
                    case 'float':
                        $value = floatval($_POST[$field]);
                        break;
                    default:
                        $value = sanitize_text_field($_POST[$field]);
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
}

// Initialize class
new Ingredient_Post_Type();