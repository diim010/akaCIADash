<?php
/**
 * Cocktail Post Type
 *
 * @package LiveFoundation
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Cocktail_Post_Type Class
 */
class Cocktail_Post_Type {

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
     * Register Cocktail post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Cocktails', 'post type general name', 'live-foundation'),
            'singular_name'      => _x('Cocktail', 'post type singular name', 'live-foundation'),
            'menu_name'          => _x('Cocktails', 'admin menu', 'live-foundation'),
            'name_admin_bar'     => _x('Cocktail', 'add new on admin bar', 'live-foundation'),
            'add_new'            => _x('Add New', 'cocktail', 'live-foundation'),
            'add_new_item'       => __('Add New Cocktail', 'live-foundation'),
            'new_item'           => __('New Cocktail', 'live-foundation'),
            'edit_item'          => __('Edit Cocktail', 'live-foundation'),
            'view_item'          => __('View Cocktail', 'live-foundation'),
            'all_items'          => __('All Cocktails', 'live-foundation'),
            'search_items'       => __('Search Cocktails', 'live-foundation'),
            'parent_item_colon'  => __('Parent Cocktails:', 'live-foundation'),
            'not_found'          => __('No cocktails found.', 'live-foundation'),
            'not_found_in_trash' => __('No cocktails found in Trash.', 'live-foundation')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Cocktail recipes for bar staff', 'live-foundation'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'cocktail'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-beer',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt')
        );

        register_post_type('cocktail', $args);
        
        // Register taxonomy for cocktail categories
        register_taxonomy('cocktail_category', 'cocktail', array(
            'label' => __('Categories', 'live-foundation'),
            'hierarchical' => true,
            'show_admin_column' => true,
        ));
    }

    /**
     * Add meta boxes for cocktail post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cocktail_details',
            __('Cocktail Details', 'live-foundation'),
            array($this, 'render_details_meta_box'),
            'cocktail',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cocktail_ingredients',
            __('Ingredients', 'live-foundation'),
            array($this, 'render_ingredients_meta_box'),
            'cocktail',
            'normal',
            'high'
        );
    }

    /**
     * Render the details meta box
     */
    public function render_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('cocktail_details_nonce', 'cocktail_details_nonce');
        
        // Get saved values
        $difficulty = get_post_meta($post->ID, '_cocktail_difficulty', true);
        $prep_time = get_post_meta($post->ID, '_cocktail_prep_time', true);
        $glass_type = get_post_meta($post->ID, '_cocktail_glass_type', true);
        
        ?>
        <div class="cocktail-meta-box">
            <p>
                <label for="cocktail_difficulty"><?php _e('Difficulty', 'live-foundation'); ?></label>
                <select name="cocktail_difficulty" id="cocktail_difficulty">
                    <option value="easy" <?php selected($difficulty, 'easy'); ?>><?php _e('Easy', 'live-foundation'); ?></option>
                    <option value="medium" <?php selected($difficulty, 'medium'); ?>><?php _e('Medium', 'live-foundation'); ?></option>
                    <option value="hard" <?php selected($difficulty, 'hard'); ?>><?php _e('Hard', 'live-foundation'); ?></option>
                </select>
            </p>
            <p>
                <label for="cocktail_prep_time"><?php _e('Preparation Time (minutes)', 'live-foundation'); ?></label>
                <input type="number" id="cocktail_prep_time" name="cocktail_prep_time" value="<?php echo esc_attr($prep_time); ?>" min="1">
            </p>
            <p>
                <label for="cocktail_glass_type"><?php _e('Glass Type', 'live-foundation'); ?></label>
                <input type="text" id="cocktail_glass_type" name="cocktail_glass_type" value="<?php echo esc_attr($glass_type); ?>">
            </p>
        </div>
        <?php
    }

    /**
     * Render the ingredients meta box
     */
    public function render_ingredients_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('cocktail_ingredients_nonce', 'cocktail_ingredients_nonce');
        
        // Get saved ingredients
        $ingredients = get_post_meta($post->ID, '_cocktail_ingredients', true);
        
        if (!is_array($ingredients)) {
            $ingredients = array(array('id' => '', 'amount' => '', 'unit' => ''));
        }
        
        // Get all ingredient posts for dropdown
        $ingredient_posts = get_posts(array(
            'post_type' => 'ingredient',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <div id="cocktail-ingredients-container">
            <?php foreach ($ingredients as $index => $ingredient) : ?>
            <div class="ingredient-row">
                <select name="cocktail_ingredients[<?php echo $index; ?>][id]">
                    <option value=""><?php _e('Select an ingredient', 'live-foundation'); ?></option>
                    <?php foreach ($ingredient_posts as $ingredient_post) : ?>
                    <option value="<?php echo $ingredient_post->ID; ?>" <?php selected($ingredient['id'], $ingredient_post->ID); ?>><?php echo $ingredient_post->post_title; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="cocktail_ingredients[<?php echo $index; ?>][amount]" placeholder="<?php _e('Amount', 'live-foundation'); ?>" value="<?php echo esc_attr($ingredient['amount']); ?>">
                <select name="cocktail_ingredients[<?php echo $index; ?>][unit]">
                    <option value="ml" <?php selected($ingredient['unit'], 'ml'); ?>><?php _e('ml', 'live-foundation'); ?></option>
                    <option value="cl" <?php selected($ingredient['unit'], 'cl'); ?>><?php _e('cl', 'live-foundation'); ?></option>
                    <option value="oz" <?php selected($ingredient['unit'], 'oz'); ?>><?php _e('oz', 'live-foundation'); ?></option>
                    <option value="dash" <?php selected($ingredient['unit'], 'dash'); ?>><?php _e('dash', 'live-foundation'); ?></option>
                    <option value="piece" <?php selected($ingredient['unit'], 'piece'); ?>><?php _e('piece', 'live-foundation'); ?></option>
                </select>
                <button type="button" class="remove-ingredient button"><?php _e('Remove', 'live-foundation'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-ingredient" class="button"><?php _e('Add Ingredient', 'live-foundation'); ?></button>
        
        <script>
        jQuery(document).ready(function($) {
            let ingredientIndex = <?php echo count($ingredients); ?>;
            
            // Add new ingredient row
            $('#add-ingredient').on('click', function() {
                const ingredientTemplate = `
                <div class="ingredient-row">
                    <select name="cocktail_ingredients[${ingredientIndex}][id]">
                        <option value=""><?php _e('Select an ingredient', 'live-foundation'); ?></option>
                        <?php foreach ($ingredient_posts as $ingredient_post) : ?>
                        <option value="<?php echo $ingredient_post->ID; ?>"><?php echo $ingredient_post->post_title; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="cocktail_ingredients[${ingredientIndex}][amount]" placeholder="<?php _e('Amount', 'live-foundation'); ?>">
                    <select name="cocktail_ingredients[${ingredientIndex}][unit]">
                        <option value="ml"><?php _e('ml', 'live-foundation'); ?></option>
                        <option value="cl"><?php _e('cl', 'live-foundation'); ?></option>
                        <option value="oz"><?php _e('oz', 'live-foundation'); ?></option>
                        <option value="dash"><?php _e('dash', 'live-foundation'); ?></option>
                        <option value="piece"><?php _e('piece', 'live-foundation'); ?></option>
                    </select>
                    <button type="button" class="remove-ingredient button"><?php _e('Remove', 'live-foundation'); ?></button>
                </div>`;
                
                $('#cocktail-ingredients-container').append(ingredientTemplate);
                ingredientIndex++;
            });
            
            // Remove ingredient row
            $(document).on('click', '.remove-ingredient', function() {
                $(this).closest('.ingredient-row').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id, $post) {
        // Check if our nonce is set
        if (!isset($_POST['cocktail_details_nonce']) || !isset($_POST['cocktail_ingredients_nonce'])) {
            return $post_id;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['cocktail_details_nonce'], 'cocktail_details_nonce') ||
            !wp_verify_nonce($_POST['cocktail_ingredients_nonce'], 'cocktail_ingredients_nonce')) {
            return $post_id;
        }

        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check the user's permissions
        if ('cocktail' !== $post->post_type || !current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Save cocktail details
        $fields = array(
            'cocktail_difficulty' => 'text',
            'cocktail_prep_time' => 'int',
            'cocktail_glass_type' => 'text',
        );

        foreach ($fields as $field => $type) {
            if (isset($_POST[$field])) {
                $value = $type === 'int' ? intval($_POST[$field]) : sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }

        // Save ingredients
        if (isset($_POST['cocktail_ingredients']) && is_array($_POST['cocktail_ingredients'])) {
            $ingredients = array();
            
            foreach ($_POST['cocktail_ingredients'] as $ingredient) {
                if (!empty($ingredient['id'])) {
                    $ingredients[] = array(
                        'id' => intval($ingredient['id']),
                        'amount' => sanitize_text_field($ingredient['amount']),
                        'unit' => sanitize_text_field($ingredient['unit'])
                    );
                }
            }
            
            update_post_meta($post_id, '_cocktail_ingredients', $ingredients);
        } else {
            delete_post_meta($post_id, '_cocktail_ingredients');
        }
    }
}

// Initialize class
new Cocktail_Post_Type();