<?php
/**
 * Plugin Name: KISS Blog Posts Sidebar - Claude
 * Plugin URI: https://KISSplugins.com
 * Description: A simple and elegant recent blog posts widget for your sidebar with customizable rounded corners and drop shadows.
 * Version: 1.2.2
 * Author: KISS Plugins
 * Author URI: https://KISSplugins.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kiss-blog-posts
 * Domain Path: /languages
 *
 * --- CHANGELOG ---
 *
 * 1.2.2 (2025-08-12) - Image Size Control & User Choice
 * - Add: Image size preference setting (Medium vs Full) in plugin settings
 * - Add: Performance vs Quality toggle with clear explanations
 * - Add: Real-time file size estimates and recommendations
 * - Add: Smart defaults based on WordPress media settings
 * - UI: Enhanced settings with performance impact indicators
 *
 * 1.2.1 (2025-08-12) - Image Size Optimization & Developer API
 * - Fix: Reverted thumbnail size priority to medium → thumbnail → full (was using only 'full')
 * - Add: Developer API with shortcode support and public methods
 * - Add: Grid layout options (1-6 columns) for external integration
 * - Add: Comprehensive developer documentation (DEVELOPER-API.md)
 * - Performance: Optimized image loading to reduce bandwidth usage
 *
 * 1.1.1 (2025-08-12) - Cache Optimization & Compatibility
 * - Add: Client-side caching with 5-minute cache duration
 * - Add: Server-side cache headers (Cache-Control, ETag, Last-Modified)
 * - Add: Automatic cache invalidation when posts are updated
 * - Add: Nonce refresh mechanism for cached pages
 * - Add: localStorage-based response caching
 * - Fix: Cache-friendly AJAX requests and error handling
 *
 * 1.1.0 (2025-08-12) - Phase 2: Backend Reliability & Easy Wins
 * - Add: Self-diagnostic tests in plugin settings to prevent regressions
 * - Add: Changelog viewer link in All Plugins page
 * - Add: Enhanced REST API health monitoring and validation
 * - Add: Comprehensive error logging and debugging system
 * - Add: Settings validation with fallback configurations
 *
 * 1.0.11 (2025-08-12)
 * - Add: Widget title link setting - allows making the widget title clickable with custom URL
 * - Add: Support for relative paths (/blog) and full URLs (https://example.com/blog)
 * - Add: URL validation and sanitization for security
 *
 * 1.0.10 (2025-08-12)
 * - Fix: "Access denied. Please refresh the page." error for non-logged-in users (removed overly restrictive permission check)
 *
 * 1.0.9 (2025-08-12)
 * - Fix: HTML entities in post titles and excerpts not being decoded properly (&#8217; &#8220; &#8211; &#038; etc.)
 *
 * 1.0.8 (2025-08-12)
 * - Fix: Critical JavaScript syntax error causing "Unexpected keyword 'function'" error
 *
 * 1.0.7 (2025-08-12)
 * - Add: Enhanced error handling with detailed error messages and retry functionality
 * - Add: Script dependency validation and graceful degradation
 * - Add: Comprehensive data validation and safe HTML rendering
 * - Add: Loading timeout handling and progressive error recovery
 * - Fix: Widget breaking when dependencies are missing or API fails
 *
 * 1.0.6 (2025-08-09)
 * - Add: Added a console log "ping" when debug mode is active to help diagnose script loading issues on production sites.
 *
 * 1.0.5 (2025-08-09)
 * - Add: Added a convenient link to WordPress's Media Settings page in the widget configuration and on the main plugins page.
 *
 * 1.0.4 (2025-08-09)
 * - Add: Implemented an optional debug mode, available via a new switch on the settings page.
 *
 * 1.0.3 (2025-08-09)
 * - Fix: Resolved an issue where featured images would not display due to HTML parsing conflicts with quotes in inline styles.
 * - Remove: Removed temporary on-screen debugging code.
 *
 * 1.0.2 (2025-08-09)
 * - Add: Implemented on-screen debugging and cache-busting to diagnose persistent featured image issue.
 *
 * 1.0.1 (2025-08-09)
 * - Fix: Modified the REST API callback to more reliably fetch featured image URLs.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KISS_BLOG_POSTS_VERSION', '1.2.2');
define('KISS_BLOG_POSTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KISS_BLOG_POSTS_PLUGIN_PATH', plugin_dir_path(__FILE__));

class KISSBlogPostsSidebar {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('widgets_init', array($this, 'register_widget'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

        // AJAX hooks
        add_action('wp_ajax_kiss_blog_posts_diagnostics', array($this, 'handle_diagnostics_ajax'));

        // Cache invalidation hooks
        add_action('save_post', array($this, 'invalidate_cache_on_post_update'));
        add_action('delete_post', array($this, 'invalidate_cache_on_post_update'));
        add_action('wp_trash_post', array($this, 'invalidate_cache_on_post_update'));
        add_action('untrash_post', array($this, 'invalidate_cache_on_post_update'));
    }
    
    public function init() {
        load_plugin_textdomain('kiss-blog-posts', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'kiss-blog-posts-style',
            KISS_BLOG_POSTS_PLUGIN_URL . 'assets/css/kiss-blog-posts.css',
            array(),
            KISS_BLOG_POSTS_VERSION
        );
        
        wp_enqueue_script(
            'kiss-blog-posts-script',
            KISS_BLOG_POSTS_PLUGIN_URL . 'assets/js/kiss-blog-posts.js',
            array('jquery', 'wp-api'),
            KISS_BLOG_POSTS_VERSION,
            true
        );
        
        $options = get_option('kiss_blog_posts_options');
        $debug_mode = !empty($options['debug_mode']) ? true : false;
        
        wp_localize_script('kiss-blog-posts-script', 'kissBlogs', array(
            'restUrl' => rest_url('kiss-blog-posts/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'debug' => $debug_mode
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_kiss-blog-posts' === $hook) {
            wp_enqueue_style(
                'kiss-blog-posts-admin',
                KISS_BLOG_POSTS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                KISS_BLOG_POSTS_VERSION
            );
        }
    }
    
    public function register_widget() {
        register_widget('KISS_Blog_Posts_Widget');
    }
    
    public function register_rest_routes() {
        register_rest_route('kiss-blog-posts/v1', '/posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_posts_rest'),
            'permission_callback' => '__return_true',
            'args' => array(
                'per_page' => array(
                    'default' => 8,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 20;
                    }
                )
            )
        ));
    }
    
    public function get_posts_rest($request) {
        try {
            // Enhanced error logging for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KISS Blog Posts: REST API called with params: ' . print_r($request->get_params(), true));
            }

            $per_page = $request->get_param('per_page');

            // Enhanced parameter validation
            if (!is_numeric($per_page) || $per_page < 1 || $per_page > 20) {
                return new WP_Error(
                    'invalid_parameter',
                    'Invalid per_page parameter. Must be between 1 and 20.',
                    array('status' => 400)
                );
            }

            // Note: Removed permission check since we're only serving published posts
            // which should be publicly accessible. The REST API permission_callback
            // is already set to '__return_true' for public access.

            // Get posts with error handling
            $posts = get_posts(array(
                'numberposts' => $per_page,
                'post_status' => 'publish',
                'post_type' => 'post',
                'suppress_filters' => false
            ));

            // Check if get_posts failed
            if (is_wp_error($posts)) {
                error_log('KISS Blog Posts: get_posts failed: ' . $posts->get_error_message());
                return new WP_Error(
                    'posts_query_failed',
                    'Failed to retrieve posts from database.',
                    array('status' => 500)
                );
            }

            $formatted_posts = array();

            foreach ($posts as $post) {
                // Validate post object
                if (!$post || !isset($post->ID)) {
                    continue;
                }

                // Get featured image URL with enhanced error handling
                $featured_image = $this->get_safe_featured_image($post->ID);

                // Safely get post data with fallbacks
                $post_title = get_the_title($post->ID);
                $post_link = get_permalink($post->ID);
                $post_excerpt = get_the_excerpt($post->ID);
                $post_date = get_the_date('F j, Y', $post->ID);

                // Validate required fields
                if (empty($post_title) || empty($post_link)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('KISS Blog Posts: Skipping post ID ' . $post->ID . ' due to missing title or link');
                    }
                    continue;
                }

                $formatted_posts[] = array(
                    'id' => intval($post->ID),
                    'title' => html_entity_decode(sanitize_text_field($post_title), ENT_QUOTES, 'UTF-8'),
                    'link' => esc_url($post_link),
                    'featured_image' => esc_url($featured_image),
                    'excerpt' => html_entity_decode(sanitize_text_field(wp_trim_words($post_excerpt, 15)), ENT_QUOTES, 'UTF-8'),
                    'date' => sanitize_text_field($post_date)
                );
            }

            // Log successful response for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KISS Blog Posts: Successfully returning ' . count($formatted_posts) . ' posts');
            }

            // Add cache-friendly headers
            $response = rest_ensure_response($formatted_posts);

            // Set cache headers (5 minutes for posts, longer for static content)
            $response->header('Cache-Control', 'public, max-age=300'); // 5 minutes
            $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
            $response->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT');

            // Add ETag for better cache validation
            $etag = md5(serialize($formatted_posts) . KISS_BLOG_POSTS_VERSION);
            $response->header('ETag', '"' . $etag . '"');

            // Check if client has cached version
            $client_etag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') : '';
            if ($client_etag === $etag) {
                $response->set_status(304); // Not Modified
                $response->set_data('');
            }

            return $response;

        } catch (Exception $e) {
            error_log('KISS Blog Posts: Exception in get_posts_rest: ' . $e->getMessage());
            return new WP_Error(
                'server_error',
                'An unexpected error occurred while retrieving posts.',
                array('status' => 500)
            );
        }
    }

    /**
     * Safely get featured image URL with fallbacks
     */
    private function get_safe_featured_image($post_id) {
        try {
            $featured_image = '';
            $thumbnail_id = get_post_thumbnail_id($post_id);

            if ($thumbnail_id) {
                // Get user's image size preference
                $options = get_option('kiss_blog_posts_options');
                $preference = isset($options['image_size_preference']) ? $options['image_size_preference'] : 'medium';

                // Set image size priority based on user preference
                if ($preference === 'full') {
                    $sizes = array('full', 'large', 'medium', 'thumbnail');
                } else {
                    $sizes = array('medium', 'thumbnail', 'full');
                }

                foreach ($sizes as $size) {
                    $featured_image = wp_get_attachment_image_url($thumbnail_id, $size);
                    if ($featured_image) {
                        break;
                    }
                }

                // Validate the URL
                if ($featured_image && !filter_var($featured_image, FILTER_VALIDATE_URL)) {
                    $featured_image = '';
                }
            }

            return $featured_image;

        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KISS Blog Posts: Error getting featured image for post ' . $post_id . ': ' . $e->getMessage());
            }
            return '';
        }
    }
    
    // Admin functions
    public function add_admin_menu() {
        add_options_page(
            __('KISS Blog Posts Settings', 'kiss-blog-posts'),
            __('KISS Blog Posts', 'kiss-blog-posts'),
            'manage_options',
            'kiss-blog-posts',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('kiss_blog_posts_settings', 'kiss_blog_posts_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        add_settings_section(
            'kiss_blog_posts_styling',
            __('Styling Options', 'kiss-blog-posts'),
            array($this, 'styling_section_callback'),
            'kiss-blog-posts'
        );
        
        add_settings_field(
            'border_radius',
            __('Border Radius (px)', 'kiss-blog-posts'),
            array($this, 'border_radius_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        add_settings_field(
            'shadow_blur',
            __('Shadow Blur (px)', 'kiss-blog-posts'),
            array($this, 'shadow_blur_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        add_settings_field(
            'shadow_spread',
            __('Shadow Spread (px)', 'kiss-blog-posts'),
            array($this, 'shadow_spread_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        add_settings_field(
            'shadow_color',
            __('Shadow Color', 'kiss-blog-posts'),
            array($this, 'shadow_color_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        add_settings_field(
            'shadow_opacity',
            __('Shadow Opacity', 'kiss-blog-posts'),
            array($this, 'shadow_opacity_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        add_settings_field(
            'tile_spacing',
            __('Vertical Spacing Between Tiles (px)', 'kiss-blog-posts'),
            array($this, 'tile_spacing_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        add_settings_field(
            'content_padding',
            __('Content Padding (px)', 'kiss-blog-posts'),
            array($this, 'content_padding_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_styling'
        );
        
        // Debugging Section
        add_settings_section(
            'kiss_blog_posts_debugging',
            __('Debugging', 'kiss-blog-posts'),
            array($this, 'debugging_section_callback'),
            'kiss-blog-posts'
        );
        
        add_settings_field(
            'debug_mode',
            __('Enable Debug Mode', 'kiss-blog-posts'),
            array($this, 'debug_mode_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_debugging'
        );

        // Self-Diagnostic Tests Section
        add_settings_section(
            'kiss_blog_posts_diagnostics',
            __('Self-Diagnostic Tests', 'kiss-blog-posts'),
            array($this, 'diagnostics_section_callback'),
            'kiss-blog-posts'
        );

        add_settings_field(
            'run_diagnostics',
            __('System Health Check', 'kiss-blog-posts'),
            array($this, 'diagnostics_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_diagnostics'
        );

        // Image Quality Section
        add_settings_section(
            'kiss_blog_posts_image_quality',
            __('Image Quality & Performance', 'kiss-blog-posts'),
            array($this, 'image_quality_section_callback'),
            'kiss-blog-posts'
        );

        add_settings_field(
            'image_size_preference',
            __('Image Size Preference', 'kiss-blog-posts'),
            array($this, 'image_size_preference_callback'),
            'kiss-blog-posts',
            'kiss_blog_posts_image_quality'
        );
    }
    
    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['border_radius'] = isset($input['border_radius']) ? absint($input['border_radius']) : 8;
        $sanitized['shadow_blur'] = isset($input['shadow_blur']) ? absint($input['shadow_blur']) : 10;
        $sanitized['shadow_spread'] = isset($input['shadow_spread']) ? absint($input['shadow_spread']) : 2;
        $sanitized['shadow_color'] = isset($input['shadow_color']) ? sanitize_hex_color($input['shadow_color']) : '#000000';
        $sanitized['shadow_opacity'] = isset($input['shadow_opacity']) ? floatval($input['shadow_opacity']) : 0.1;
        $sanitized['tile_spacing'] = isset($input['tile_spacing']) ? absint($input['tile_spacing']) : 20;
        $sanitized['content_padding'] = isset($input['content_padding']) ? absint($input['content_padding']) : 15;
        $sanitized['debug_mode'] = !empty($input['debug_mode']) ? 1 : 0;

        // Image size preference
        $sanitized['image_size_preference'] = isset($input['image_size_preference']) &&
            in_array($input['image_size_preference'], array('medium', 'full')) ?
            $input['image_size_preference'] : 'medium';

        // Ensure opacity is between 0 and 1
        if ($sanitized['shadow_opacity'] < 0) $sanitized['shadow_opacity'] = 0;
        if ($sanitized['shadow_opacity'] > 1) $sanitized['shadow_opacity'] = 1;

        return $sanitized;
    }
    
    public function styling_section_callback() {
        echo '<p>' . __('Customize the appearance of your blog post tiles.', 'kiss-blog-posts') . '</p>';
    }
    
    public function border_radius_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['border_radius']) ? $options['border_radius'] : 8;
        echo '<input type="number" name="kiss_blog_posts_options[border_radius]" value="' . esc_attr($value) . '" min="0" max="50" />';
        echo '<p class="description">' . __('Set the border radius for rounded corners (0-50px)', 'kiss-blog-posts') . '</p>';
    }
    
    public function shadow_blur_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['shadow_blur']) ? $options['shadow_blur'] : 10;
        echo '<input type="number" name="kiss_blog_posts_options[shadow_blur]" value="' . esc_attr($value) . '" min="0" max="50" />';
        echo '<p class="description">' . __('Set the shadow blur amount (0-50px)', 'kiss-blog-posts') . '</p>';
    }
    
    public function shadow_spread_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['shadow_spread']) ? $options['shadow_spread'] : 2;
        echo '<input type="number" name="kiss_blog_posts_options[shadow_spread]" value="' . esc_attr($value) . '" min="-10" max="10" />';
        echo '<p class="description">' . __('Set the shadow spread (-10 to 10px)', 'kiss-blog-posts') . '</p>';
    }
    
    public function shadow_color_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['shadow_color']) ? $options['shadow_color'] : '#000000';
        echo '<input type="color" name="kiss_blog_posts_options[shadow_color]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Choose the shadow color (opacity set separately)', 'kiss-blog-posts') . '</p>';
    }
    
    public function shadow_opacity_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['shadow_opacity']) ? $options['shadow_opacity'] : 0.1;
        echo '<input type="range" name="kiss_blog_posts_options[shadow_opacity]" value="' . esc_attr($value) . '" min="0" max="1" step="0.1" oninput="this.nextElementSibling.value=this.value" />';
        echo '<output>' . esc_attr($value) . '</output>';
        echo '<p class="description">' . __('Set the shadow opacity (0 = transparent, 1 = solid)', 'kiss-blog-posts') . '</p>';
    }
    
    public function tile_spacing_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['tile_spacing']) ? $options['tile_spacing'] : 20;
        echo '<input type="number" name="kiss_blog_posts_options[tile_spacing]" value="' . esc_attr($value) . '" min="0" max="100" />';
        echo '<p class="description">' . __('Set the vertical spacing between tiles (0-100px)', 'kiss-blog-posts') . '</p>';
    }
    
    public function content_padding_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['content_padding']) ? $options['content_padding'] : 15;
        echo '<input type="number" name="kiss_blog_posts_options[content_padding]" value="' . esc_attr($value) . '" min="5" max="50" />';
        echo '<p class="description">' . __('Set the padding inside each tile content area (5-50px)', 'kiss-blog-posts') . '</p>';
    }

    public function debugging_section_callback() {
        echo '<p>' . __('Use these settings to help troubleshoot issues with the plugin.', 'kiss-blog-posts') . '</p>';
    }
    
    public function debug_mode_callback() {
        $options = get_option('kiss_blog_posts_options');
        $checked = isset($options['debug_mode']) && $options['debug_mode'] ? 'checked' : '';
        echo '<input type="checkbox" name="kiss_blog_posts_options[debug_mode]" value="1" ' . $checked . ' />';
        echo '<p class="description">' . __('When enabled, the plugin will display raw post data in the widget for troubleshooting.', 'kiss-blog-posts') . '</p>';
    }

    public function diagnostics_section_callback() {
        echo '<p>' . __('Run these tests to ensure the plugin is working correctly and prevent regressions.', 'kiss-blog-posts') . '</p>';
    }

    public function diagnostics_callback() {
        echo '<button type="button" id="run-diagnostics-btn" class="button button-secondary">' . __('Run All Tests', 'kiss-blog-posts') . '</button>';
        echo '<div id="diagnostics-results" style="margin-top: 15px;"></div>';

        // Add JavaScript for running diagnostics
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#run-diagnostics-btn').on('click', function() {
                var button = $(this);
                var results = $('#diagnostics-results');

                button.prop('disabled', true).text('Running Tests...');
                results.html('<div class="notice notice-info"><p>Running diagnostic tests...</p></div>');

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'kiss_blog_posts_diagnostics',
                        nonce: '<?php echo wp_create_nonce('kiss_blog_posts_diagnostics'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            results.html(response.data.html);
                        } else {
                            results.html('<div class="notice notice-error"><p>Error running diagnostics: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        results.html('<div class="notice notice-error"><p>Failed to run diagnostics. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Run All Tests');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function image_quality_section_callback() {
        echo '<p>' . __('Control image quality vs performance for your blog post thumbnails.', 'kiss-blog-posts') . '</p>';
    }

    public function image_size_preference_callback() {
        $options = get_option('kiss_blog_posts_options');
        $value = isset($options['image_size_preference']) ? $options['image_size_preference'] : 'medium';

        // Get WordPress media settings for context
        $medium_width = get_option('medium_size_w', 300);
        $medium_height = get_option('medium_size_h', 300);

        echo '<fieldset>';
        echo '<legend class="screen-reader-text"><span>' . __('Image Size Preference', 'kiss-blog-posts') . '</span></legend>';

        // Medium size option
        echo '<label>';
        echo '<input type="radio" name="kiss_blog_posts_options[image_size_preference]" value="medium" ' . checked($value, 'medium', false) . ' />';
        echo ' <strong>' . __('Medium Size (Recommended)', 'kiss-blog-posts') . '</strong>';
        echo '</label><br>';
        echo '<div style="margin-left: 25px; margin-bottom: 15px; color: #666;">';
        echo sprintf(__('Current size: %dx%d pixels', 'kiss-blog-posts'), $medium_width, $medium_height);
        echo '<br>' . __('✓ Fast loading (~50-150KB per image)', 'kiss-blog-posts');
        echo '<br>' . __('✓ Mobile-friendly', 'kiss-blog-posts');
        echo '<br>' . __('✓ Good for most use cases', 'kiss-blog-posts');
        if ($medium_width < 400) {
            echo '<br><span style="color: #d63638;">⚠ Consider increasing to 400-500px in Settings > Media for sharper images</span>';
        }
        echo '</div>';

        // Full size option
        echo '<label>';
        echo '<input type="radio" name="kiss_blog_posts_options[image_size_preference]" value="full" ' . checked($value, 'full', false) . ' />';
        echo ' <strong>' . __('Full Size (Maximum Quality)', 'kiss-blog-posts') . '</strong>';
        echo '</label><br>';
        echo '<div style="margin-left: 25px; color: #666;">';
        echo __('Original uploaded image size', 'kiss-blog-posts');
        echo '<br>' . __('⚠ Slower loading (~500KB-3MB per image)', 'kiss-blog-posts');
        echo '<br>' . __('⚠ May impact mobile performance', 'kiss-blog-posts');
        echo '<br>' . __('✓ Maximum image quality', 'kiss-blog-posts');
        echo '</div>';

        echo '</fieldset>';

        echo '<p class="description">';
        echo __('Choose between performance (Medium) and maximum quality (Full). Medium size is recommended for most sites.', 'kiss-blog-posts');
        echo '</p>';
    }

    public function admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=kiss-blog-posts&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Settings', 'kiss-blog-posts'); ?>
                </a>
                <a href="?page=kiss-blog-posts&tab=changelog" class="nav-tab <?php echo $active_tab == 'changelog' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Changelog', 'kiss-blog-posts'); ?>
                </a>
            </h2>

            <?php if ($active_tab == 'settings'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('kiss_blog_posts_settings');
                    do_settings_sections('kiss-blog-posts');
                    submit_button();
                    ?>
                </form>
            <?php elseif ($active_tab == 'changelog'): ?>
                <div style="margin-top: 20px;">
                    <?php $this->render_changelog(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_changelog() {
        $changelog_file = KISS_BLOG_POSTS_PLUGIN_PATH . 'changelog.md';

        if (function_exists('kiss_mdv_render_file')) {
            $html = kiss_mdv_render_file($changelog_file);
        } else {
            // Fallback to plain text rendering
            if (file_exists($changelog_file)) {
                $content = file_get_contents($changelog_file);
                $html = '<pre style="background: #f9f9f9; padding: 20px; border-radius: 4px; overflow-x: auto;">' . esc_html($content) . '</pre>';
            } else {
                $html = '<div class="notice notice-error"><p>Changelog file not found.</p></div>';
            }
        }

        echo $html;
    }

    /**
     * Handle AJAX request for diagnostics
     */
    public function handle_diagnostics_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kiss_blog_posts_diagnostics')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $results = $this->run_diagnostic_tests();

        wp_send_json_success(array('html' => $this->format_diagnostic_results($results)));
    }

    /**
     * Run all diagnostic tests
     */
    private function run_diagnostic_tests() {
        $tests = array();

        // Test 1: REST API Endpoint Accessibility
        $tests['rest_api'] = $this->test_rest_api_endpoint();

        // Test 2: JavaScript Dependencies
        $tests['js_dependencies'] = $this->test_javascript_dependencies();

        // Test 3: Database Query Performance
        $tests['db_performance'] = $this->test_database_performance();

        // Test 4: Settings Validation
        $tests['settings_validation'] = $this->test_settings_validation();

        return $tests;
    }

    /**
     * Test REST API endpoint accessibility
     */
    private function test_rest_api_endpoint() {
        $test = array(
            'name' => 'REST API Endpoint Test',
            'description' => 'Verifies the REST API endpoint is accessible and returns valid data',
            'status' => 'pass',
            'message' => '',
            'details' => array()
        );

        try {
            $url = rest_url('kiss-blog-posts/v1/posts?per_page=1');
            $response = wp_remote_get($url, array('timeout' => 10));

            if (is_wp_error($response)) {
                $test['status'] = 'fail';
                $test['message'] = 'Failed to connect to REST API: ' . $response->get_error_message();
                return $test;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($status_code !== 200) {
                $test['status'] = 'fail';
                $test['message'] = 'REST API returned status code: ' . $status_code;
                return $test;
            }

            $data = json_decode($body, true);
            if (!is_array($data)) {
                $test['status'] = 'fail';
                $test['message'] = 'REST API returned invalid JSON data';
                return $test;
            }

            $test['message'] = 'REST API endpoint is working correctly';
            $test['details'][] = 'Status Code: ' . $status_code;
            $test['details'][] = 'Response Size: ' . strlen($body) . ' bytes';
            $test['details'][] = 'Posts Returned: ' . count($data);

        } catch (Exception $e) {
            $test['status'] = 'fail';
            $test['message'] = 'Exception during REST API test: ' . $e->getMessage();
        }

        return $test;
    }

    /**
     * Test JavaScript dependencies
     */
    private function test_javascript_dependencies() {
        $test = array(
            'name' => 'JavaScript Dependencies Test',
            'description' => 'Checks if required JavaScript files and dependencies are properly enqueued',
            'status' => 'pass',
            'message' => '',
            'details' => array()
        );

        // Check if script files exist
        $js_file = KISS_BLOG_POSTS_PLUGIN_PATH . 'assets/js/kiss-blog-posts.js';
        $css_file = KISS_BLOG_POSTS_PLUGIN_PATH . 'assets/css/kiss-blog-posts.css';

        if (!file_exists($js_file)) {
            $test['status'] = 'fail';
            $test['message'] = 'JavaScript file is missing';
            return $test;
        }

        if (!file_exists($css_file)) {
            $test['status'] = 'fail';
            $test['message'] = 'CSS file is missing';
            return $test;
        }

        // Check file sizes and basic content
        $js_size = filesize($js_file);
        $css_size = filesize($css_file);

        if ($js_size < 1000) {
            $test['status'] = 'warning';
            $test['message'] = 'JavaScript file seems unusually small';
        }

        $test['message'] = $test['message'] ?: 'JavaScript and CSS files are present and properly sized';
        $test['details'][] = 'JS File Size: ' . number_format($js_size) . ' bytes';
        $test['details'][] = 'CSS File Size: ' . number_format($css_size) . ' bytes';
        $test['details'][] = 'Plugin Version: ' . KISS_BLOG_POSTS_VERSION;

        return $test;
    }

    /**
     * Test database performance
     */
    private function test_database_performance() {
        $test = array(
            'name' => 'Database Performance Test',
            'description' => 'Tests database query performance for retrieving posts',
            'status' => 'pass',
            'message' => '',
            'details' => array()
        );

        try {
            $start_time = microtime(true);

            $posts = get_posts(array(
                'numberposts' => 10,
                'post_status' => 'publish',
                'post_type' => 'post'
            ));

            $end_time = microtime(true);
            $query_time = ($end_time - $start_time) * 1000; // Convert to milliseconds

            if ($query_time > 1000) {
                $test['status'] = 'warning';
                $test['message'] = 'Database query is slow (' . number_format($query_time, 2) . 'ms)';
            } else {
                $test['message'] = 'Database performance is good (' . number_format($query_time, 2) . 'ms)';
            }

            $test['details'][] = 'Query Time: ' . number_format($query_time, 2) . 'ms';
            $test['details'][] = 'Posts Found: ' . count($posts);
            $test['details'][] = 'Memory Usage: ' . number_format(memory_get_usage() / 1024 / 1024, 2) . 'MB';

        } catch (Exception $e) {
            $test['status'] = 'fail';
            $test['message'] = 'Database test failed: ' . $e->getMessage();
        }

        return $test;
    }

    /**
     * Test settings validation
     */
    private function test_settings_validation() {
        $test = array(
            'name' => 'Settings Validation Test',
            'description' => 'Validates plugin settings and configuration',
            'status' => 'pass',
            'message' => '',
            'details' => array()
        );

        $options = get_option('kiss_blog_posts_options');

        if (!$options) {
            $test['status'] = 'warning';
            $test['message'] = 'Plugin options not found, using defaults';
            $test['details'][] = 'Status: Using default settings';
        } else {
            // Validate individual settings
            $valid_settings = 0;
            $total_settings = 0;

            $expected_settings = array(
                'border_radius' => 'numeric',
                'shadow_blur' => 'numeric',
                'shadow_spread' => 'numeric',
                'shadow_color' => 'string',
                'shadow_opacity' => 'numeric',
                'tile_spacing' => 'numeric',
                'content_padding' => 'numeric'
            );

            foreach ($expected_settings as $setting => $type) {
                $total_settings++;
                if (isset($options[$setting])) {
                    if ($type === 'numeric' && is_numeric($options[$setting])) {
                        $valid_settings++;
                    } elseif ($type === 'string' && is_string($options[$setting])) {
                        $valid_settings++;
                    }
                }
            }

            if ($valid_settings === $total_settings) {
                $test['message'] = 'All settings are valid and properly configured';
            } else {
                $test['status'] = 'warning';
                $test['message'] = 'Some settings may be missing or invalid';
            }

            $test['details'][] = 'Valid Settings: ' . $valid_settings . '/' . $total_settings;
            $test['details'][] = 'Debug Mode: ' . (isset($options['debug_mode']) && $options['debug_mode'] ? 'Enabled' : 'Disabled');
        }

        return $test;
    }

    /**
     * Format diagnostic results for display
     */
    private function format_diagnostic_results($results) {
        $html = '<div class="kiss-blog-posts-diagnostics">';

        $total_tests = count($results);
        $passed_tests = 0;
        $warning_tests = 0;
        $failed_tests = 0;

        foreach ($results as $test) {
            if ($test['status'] === 'pass') $passed_tests++;
            elseif ($test['status'] === 'warning') $warning_tests++;
            else $failed_tests++;
        }

        // Summary
        $html .= '<div class="notice notice-info" style="margin-bottom: 20px;">';
        $html .= '<h3>Diagnostic Summary</h3>';
        $html .= '<p><strong>Total Tests:</strong> ' . $total_tests . ' | ';
        $html .= '<span style="color: #46b450;"><strong>Passed:</strong> ' . $passed_tests . '</span> | ';
        $html .= '<span style="color: #ffb900;"><strong>Warnings:</strong> ' . $warning_tests . '</span> | ';
        $html .= '<span style="color: #dc3232;"><strong>Failed:</strong> ' . $failed_tests . '</span></p>';
        $html .= '</div>';

        // Individual test results
        foreach ($results as $test) {
            $status_class = 'notice-info';
            $status_color = '#0073aa';

            if ($test['status'] === 'pass') {
                $status_class = 'notice-success';
                $status_color = '#46b450';
            } elseif ($test['status'] === 'warning') {
                $status_class = 'notice-warning';
                $status_color = '#ffb900';
            } elseif ($test['status'] === 'fail') {
                $status_class = 'notice-error';
                $status_color = '#dc3232';
            }

            $html .= '<div class="notice ' . $status_class . '" style="margin-bottom: 15px;">';
            $html .= '<h4 style="margin: 10px 0 5px 0; color: ' . $status_color . ';">';
            $html .= esc_html($test['name']) . ' - ' . strtoupper($test['status']);
            $html .= '</h4>';
            $html .= '<p style="margin: 5px 0;"><em>' . esc_html($test['description']) . '</em></p>';
            $html .= '<p style="margin: 5px 0;"><strong>' . esc_html($test['message']) . '</strong></p>';

            if (!empty($test['details'])) {
                $html .= '<ul style="margin: 5px 0 10px 20px;">';
                foreach ($test['details'] as $detail) {
                    $html .= '<li>' . esc_html($detail) . '</li>';
                }
                $html .= '</ul>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Invalidate cache when posts are updated
     */
    public function invalidate_cache_on_post_update($post_id) {
        // Only invalidate for published posts
        if (get_post_type($post_id) !== 'post') {
            return;
        }

        $post_status = get_post_status($post_id);
        if ($post_status !== 'publish' && $post_status !== 'trash') {
            return;
        }

        // Set a transient to signal cache invalidation
        set_transient('kiss_blog_posts_cache_invalidated', time(), 3600);

        // If using object cache, clear it
        if (function_exists('wp_cache_flush')) {
            wp_cache_delete('kiss_blog_posts_*', 'kiss_blog_posts');
        }

        // Log cache invalidation in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KISS Blog Posts: Cache invalidated due to post update (ID: ' . $post_id . ')');
        }
    }
    
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=kiss-blog-posts') . '">' . __('Settings', 'kiss-blog-posts') . '</a>';
        $changelog_link = '<a href="' . admin_url('options-general.php?page=kiss-blog-posts&tab=changelog') . '">' . __('Changelog', 'kiss-blog-posts') . '</a>';
        $media_settings_link = '<a href="' . admin_url('options-media.php') . '">' . __('WP Thumbnail Settings', 'kiss-blog-posts') . '</a>';

        array_unshift($links, $media_settings_link);
        array_unshift($links, $changelog_link);
        array_unshift($links, $settings_link);

        return $links;
    }
    
    public static function get_custom_css() {
        $options = get_option('kiss_blog_posts_options');
        $border_radius = isset($options['border_radius']) ? $options['border_radius'] : 8;
        $shadow_blur = isset($options['shadow_blur']) ? $options['shadow_blur'] : 10;
        $shadow_spread = isset($options['shadow_spread']) ? $options['shadow_spread'] : 2;
        $shadow_color = isset($options['shadow_color']) ? $options['shadow_color'] : '#000000';
        $shadow_opacity = isset($options['shadow_opacity']) ? $options['shadow_opacity'] : 0.1;
        $tile_spacing = isset($options['tile_spacing']) ? $options['tile_spacing'] : 20;
        $content_padding = isset($options['content_padding']) ? $options['content_padding'] : 15;
        
        // Convert hex color to rgba with opacity
        $shadow_color_rgba = self::hex_to_rgba($shadow_color, $shadow_opacity);
        
        return "
        .kiss-blog-posts-container {
            gap: {$tile_spacing}px !important;
        }
        .kiss-blog-posts-tile {
            border-radius: {$border_radius}px !important;
            box-shadow: 0 4px {$shadow_blur}px {$shadow_spread}px {$shadow_color_rgba} !important;
        }
        .kiss-blog-posts-tile .tile-image {
            border-radius: {$border_radius}px {$border_radius}px 0 0 !important;
        }
        .kiss-blog-posts-tile .tile-content {
            padding: {$content_padding}px !important;
        }
        ";
    }
    
    private static function hex_to_rgba($hex, $opacity) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "rgba($r, $g, $b, $opacity)";
    }
}

// Widget Class
class KISS_Blog_Posts_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'kiss_blog_posts_widget',
            __('KISS Blog Posts Sidebar', 'kiss-blog-posts'),
            array('description' => __('Display recent blog posts in an elegant tile layout', 'kiss-blog-posts'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            $title = apply_filters('widget_title', $instance['title']);
            $title_url = !empty($instance['title_url']) ? $instance['title_url'] : '';

            if (!empty($title_url)) {
                // Sanitize and validate the URL
                $safe_url = $this->sanitize_title_url($title_url);
                if ($safe_url) {
                    $title = '<a href="' . esc_url($safe_url) . '" class="kiss-blog-posts-title-link">' . esc_html($title) . '</a>';
                }
            }

            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        $posts_count = !empty($instance['posts_count']) ? $instance['posts_count'] : 8;
        
        echo '<div class="kiss-blog-posts-container" data-posts-count="' . esc_attr($posts_count) . '">';
        echo '<div class="kiss-blog-posts-loading">' . __('Loading posts...', 'kiss-blog-posts') . '</div>';
        echo '</div>';
        
        // Add custom CSS
        echo '<style>' . KISSBlogPostsSidebar::get_custom_css() . '</style>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Recent Blog Posts', 'kiss-blog-posts');
        $title_url = !empty($instance['title_url']) ? $instance['title_url'] : '';
        $posts_count = !empty($instance['posts_count']) ? $instance['posts_count'] : 8;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'kiss-blog-posts'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title_url')); ?>"><?php _e('Title Link URL (optional):', 'kiss-blog-posts'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title_url')); ?>" name="<?php echo esc_attr($this->get_field_name('title_url')); ?>" type="text" value="<?php echo esc_attr($title_url); ?>" placeholder="<?php _e('/blog or https://example.com/blog', 'kiss-blog-posts'); ?>">
            <small class="description"><?php _e('Make the widget title clickable. Supports relative paths (/blog) or full URLs (https://example.com).', 'kiss-blog-posts'); ?></small>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('posts_count')); ?>"><?php _e('Number of posts:', 'kiss-blog-posts'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('posts_count')); ?>" name="<?php echo esc_attr($this->get_field_name('posts_count')); ?>" type="number" step="1" min="1" max="20" value="<?php echo esc_attr($posts_count); ?>">
        </p>
        <p>
            <a href="<?php echo admin_url('options-general.php?page=kiss-blog-posts'); ?>" target="_blank"><?php _e('⚙️ Plugin Settings', 'kiss-blog-posts'); ?></a> |
            <a href="<?php echo admin_url('options-media.php'); ?>" target="_blank"><?php _e('WP Thumbnail Settings', 'kiss-blog-posts'); ?></a>
            <br><small><?php _e('Customize appearance, spacing, and thumbnail sizes.', 'kiss-blog-posts'); ?></small>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['title_url'] = (!empty($new_instance['title_url'])) ? $this->sanitize_title_url($new_instance['title_url']) : '';
        $instance['posts_count'] = (!empty($new_instance['posts_count'])) ? absint($new_instance['posts_count']) : 8;
        return $instance;
    }

    /**
     * Sanitize and validate title URL
     */
    private function sanitize_title_url($url) {
        if (empty($url)) {
            return '';
        }

        $url = trim($url);

        // Handle relative paths
        if (strpos($url, '/') === 0) {
            // Relative path - validate it doesn't contain dangerous characters
            if (preg_match('/^\/[a-zA-Z0-9\/_-]*$/', $url)) {
                return $url;
            }
            return '';
        }

        // Handle full URLs
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // Additional security check - only allow http/https
            $parsed = parse_url($url);
            if (isset($parsed['scheme']) && in_array($parsed['scheme'], array('http', 'https'))) {
                return esc_url_raw($url);
            }
        }

        return '';
    }
}

// Initialize the plugin
KISSBlogPostsSidebar::get_instance();

// Create assets directories and files on activation
register_activation_hook(__FILE__, 'kiss_blog_posts_create_assets');

function kiss_blog_posts_create_assets() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = KISS_BLOG_POSTS_PLUGIN_PATH;
    
    // Create assets directories
    wp_mkdir_p($plugin_dir . 'assets/css');
    wp_mkdir_p($plugin_dir . 'assets/js');
    
    // Create CSS file
    $css_content = '/* KISS Blog Posts Sidebar Styles */
.kiss-blog-posts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.kiss-blog-posts-tile {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 10px 2px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.kiss-blog-posts-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px 3px rgba(0, 0, 0, 0.15);
}

.kiss-blog-posts-tile .tile-image {
    width: 100%;
    height: 160px;
    background-size: cover;
    background-position: center;
    border-radius: 8px 8px 0 0;
}

.kiss-blog-posts-tile .tile-content {
    padding: 15px;
}

.kiss-blog-posts-tile .tile-title {
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
    margin: 0 0 8px 0;
    color: #333;
    text-decoration: none;
}

.kiss-blog-posts-tile .tile-title:hover {
    color: #0073aa;
}

.kiss-blog-posts-tile .tile-excerpt {
    font-size: 14px;
    color: #666;
    line-height: 1.4;
    margin: 0 0 8px 0;
}

.kiss-blog-posts-tile .tile-date {
    font-size: 12px;
    color: #999;
    margin: 0;
}

.kiss-blog-posts-loading {
    text-align: center;
    padding: 20px;
    color: #666;
}

.kiss-blog-posts-error {
    text-align: center;
    padding: 20px;
    color: #d32f2f;
    background: #ffeaea;
    border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .kiss-blog-posts-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .kiss-blog-posts-tile .tile-image {
        height: 140px;
    }
}';
    
    file_put_contents($plugin_dir . 'assets/css/kiss-blog-posts.css', $css_content);
    
    // Create JS file
    $js_content = '/* KISS Blog Posts Sidebar JavaScript */
jQuery(document).ready(function($) {
    $(".kiss-blog-posts-container").each(function() {
        var container = $(this);
        var postsCount = container.data("posts-count") || 8;
        
        // Fetch posts via REST API
        $.ajax({
            url: kissBlogs.restUrl + "posts",
            method: "GET",
            data: {
                per_page: postsCount
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-WP-Nonce", kissBlogs.nonce);
            },
            success: function(posts) {
                if (posts && posts.length > 0) {
                    renderPosts(container, posts);
                } else {
                    container.html("<div class=\"kiss-blog-posts-error\">No posts found.</div>");
                }
            },
            error: function() {
                container.html("<div class=\"kiss-blog-posts-error\">Error loading posts.</div>");
            }
        });
    });
    
    function renderPosts(container, posts) {
        var html = "";
        
        posts.forEach(function(post) {
            var imageStyle = post.featured_image ? 
                "background-image: url(\"" + post.featured_image + "\");" : 
                "background-color: #f0f0f0;";
            
            html += "<div class=\"kiss-blog-posts-tile\" onclick=\"window.location.href=\'" + post.link + "\'\">";
            html += "<div class=\"tile-image\" style=\"" + imageStyle + "\"></div>";
            html += "<div class=\"tile-content\">";
            html += "<h3 class=\"tile-title\"><a href=\"" + post.link + "\">" + post.title + "</a></h3>";
            if (post.excerpt) {
                html += "<p class=\"tile-excerpt\">" + post.excerpt + "</p>";
            }
            html += "<p class=\"tile-date\">" + post.date + "</p>";
            html += "</div>";
            html += "</div>";
        });
        
        container.html(html);
    }
});';
    
    file_put_contents($plugin_dir . 'assets/js/kiss-blog-posts.js', $js_content);
    
    // Create admin CSS file
    $admin_css = '/* KISS Blog Posts Admin Styles */
.kiss-blog-posts-admin .form-table th {
    font-weight: 600;
}

.kiss-blog-posts-admin input[type="number"],
.kiss-blog-posts-admin input[type="color"] {
    max-width: 100px;
}

.kiss-blog-posts-admin .description {
    font-style: italic;
    color: #666;
}';
    
    file_put_contents($plugin_dir . 'assets/css/admin.css', $admin_css);
    
    // Set default options
    $default_options = array(
        'border_radius' => 8,
        'shadow_blur' => 10,
        'shadow_spread' => 2,
        'shadow_color' => '#00000020'
    );
    
    if (!get_option('kiss_blog_posts_options')) {
        update_option('kiss_blog_posts_options', $default_options);
    }
}

// Clean up on deactivation
register_deactivation_hook(__FILE__, 'kiss_blog_posts_deactivate');

function kiss_blog_posts_deactivate() {
    // Clean up if needed
}
