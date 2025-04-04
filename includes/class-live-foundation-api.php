<?php
/**
 * Live Foundation API
 *
 * Handles API functionality and endpoints
 *
 * @package LiveFoundation
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Live_Foundation_API class
 */
class Live_Foundation_API {

    /**
     * Constructor
     */
    public function __construct() {
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Register AJAX handlers
        add_action('wp_ajax_live_foundation_data', array($this, 'ajax_get_data'));
        add_action('wp_ajax_nopriv_live_foundation_data', array($this, 'ajax_get_data'));
        
        add_action('wp_ajax_live_foundation_submit', array($this, 'ajax_submit_data'));
        add_action('wp_ajax_nopriv_live_foundation_submit', array($this, 'ajax_submit_data'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('live-foundation/v1', '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'permissions_check'),
        ));
        
        register_rest_route('live-foundation/v1', '/data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_data'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('live-foundation/v1', '/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_data'),
            'permission_callback' => '__return_true',
            'args' => array(
                'name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_email($param);
                    },
                ),
                'message' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));

        // Endpoints for Cocktails
        register_rest_route('live-foundation/v1', '/cocktails', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_cocktails'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_cocktail'],
                'permission_callback' => [$this, 'permissions_check'],
            ]
        ]);

        register_rest_route('live-foundation/v1', '/cocktails/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_cocktail'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update_cocktail'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_cocktail'],
                'permission_callback' => [$this, 'permissions_check'],
            ]
        ]);

        // Route for getting available endpoints
        register_rest_route('live-foundation/v1', '/routes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_routes'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Check if user has permission to access settings
     *
     * @param WP_REST_Request $request The request object
     * @return bool|WP_Error True if user can access, WP_Error otherwise
     */
    public function permissions_check($request) {
        // Check if user can manage options
        if (current_user_can('manage_options')) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __('Sorry, you are not allowed to access these settings.', 'live-foundation'),
            array('status' => rest_authorization_required_code())
        );
    }

    /**
     * Get all cocktails
     */
    public function get_cocktails($request) {
        $args = [
            'post_type' => 'cocktail', // исправлено с 'cocktails'
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $posts = get_posts($args);
        $data = [];

        foreach ($posts as $post) {
            $data[] = $this->prepare_cocktail_data($post);
        }

        return rest_ensure_response($data);
    }

    /**
     * Get single cocktail
     */
    public function get_cocktail($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'cocktail') { // исправлено с 'cocktails'
            return new WP_Error(
                'not_found',
                'Коктейль не найден',
                ['status' => 404]
            );
        }

        return rest_ensure_response($this->prepare_cocktail_data($post));
    }

    /**
     * Create new cocktail
     */
    public function create_cocktail($request) {
        $params = $request->get_params();

        $post_data = [
            'post_type' => 'cocktail', // исправлено с 'cocktails'
            'post_title' => sanitize_text_field($params['title']),
            'post_status' => 'publish',
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Update meta fields
        if (!empty($params['meta'])) {
            foreach ($params['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return rest_ensure_response($this->prepare_cocktail_data(get_post($post_id)));
    }

    /**
     * Update existing cocktail
     */
    public function update_cocktail($request) {
        $post = get_post($request['id']);
        
        if (!$post || $post->post_type !== 'cocktail') { // исправлено с 'cocktails'
            return new WP_Error(
                'not_found',
                'Коктейль не найден',
                ['status' => 404]
            );
        }

        $params = $request->get_params();

        if (isset($params['title'])) {
            wp_update_post([
                'ID' => $post->ID,
                'post_title' => sanitize_text_field($params['title'])
            ]);
        }

        // Update meta fields
        if (!empty($params['meta'])) {
            foreach ($params['meta'] as $key => $value) {
                update_post_meta($post->ID, $key, $value);
            }
        }

        return rest_ensure_response($this->prepare_cocktail_data(get_post($post->ID)));
    }

    /**
     * Delete cocktail
     */
    public function delete_cocktail($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'cocktail') { // исправлено с 'cocktails'
            return new WP_Error(
                'not_found',
                'Коктейль не найден',
                ['status' => 404]
            );
        }

        $result = wp_delete_post($request['id'], true);

        if (!$result) {
            return new WP_Error(
                'delete_failed',
                'Не удалось удалить коктейль',
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'deleted' => true,
            'id' => $post->ID
        ]);
    }

    /**
     * Prepare cocktail data for response
     */
    private function prepare_cocktail_data($post) {
        // Get all meta fields
        $meta = get_post_meta($post->ID);
        $cleaned_meta = [];

        // Clean up meta values from arrays
        foreach ($meta as $key => $value) {
            $cleaned_meta[$key] = maybe_unserialize($value[0]);
        }

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'meta' => $cleaned_meta
        ];
    }

    /**
     * Get available routes
     */
    public function get_routes($request) {
        $routes = array(
            'cocktails' => array(
                'endpoints' => array(
                    'GET /wp-json/live-foundation/v1/cocktails' => 'Get all cocktails',
                    'POST /wp-json/live-foundation/v1/cocktails' => 'Create new cocktail',
                    'GET /wp-json/live-foundation/v1/cocktails/{id}' => 'Get single cocktail',
                    'PATCH /wp-json/live-foundation/v1/cocktails/{id}' => 'Update cocktail',
                    'DELETE /wp-json/live-foundation/v1/cocktails/{id}' => 'Delete cocktail'
                )
            )
        );

        return rest_ensure_response($routes);
    }
}

new live_foundation_api();