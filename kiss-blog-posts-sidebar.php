<?php
/**
 * Plugin Name: KISS Blog Posts Sidebar - Claude
 * Plugin URI: https://KISSplugins.com
 * Description: A simple and elegant recent blog posts widget for your sidebar with customizable rounded corners and drop shadows.
 * Version: 1.0.1
 * Author: KISS Plugins
 * Author URI: https://KISSplugins.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kiss-blog-posts
 * Domain Path: /languages
 *
 * --- CHANGELOG ---
 *
 * 1.0.1 (2025-08-09) - Gemini
 * - Fix: Modified the REST API callback to more reliably fetch featured image URLs.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KISS_BLOG_POSTS_VERSION', '1.0.1');
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
        
        wp_localize_script('kiss-blog-posts-script', 'kissBlogs', array(
            'restUrl' => rest_url('kiss-blog-posts/v1/'),
            'nonce' => wp_create_nonce('wp_rest')
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
        $per_page = $request->get_param('per_page');
        
        $posts = get_posts(array(
            'numberposts' => $per_page,
            'post_status' => 'publish',
            'post_type' => 'post'
        ));
        
        $formatted_posts = array();
        
        foreach ($posts as $post) {
            // Get featured image URL more reliably by checking multiple sizes.
            $featured_image = '';
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            if ($thumbnail_id) {
                $featured_image = wp_get_attachment_image_url($thumbnail_id, 'medium');
                if (!$featured_image) {
                    $featured_image = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                }
                if (!$featured_image) {
                    $featured_image = wp_get_attachment_image_url($thumbnail_id, 'full');
                }
            }
            
            $formatted_posts[] = array(
                'id' => $post->ID,
                'title' => get_the_title($post->ID),
                'link' => get_permalink($post->ID),
                'featured_image' => $featured_image ?: '',
                'excerpt' => wp_trim_words(get_the_excerpt($post->ID), 15),
                'date' => get_the_date('F j, Y', $post->ID)
            );
        }
        
        return rest_ensure_response($formatted_posts);
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
    }
    
    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['border_radius'] = absint($input['border_radius']);
        $sanitized['shadow_blur'] = absint($input['shadow_blur']);
        $sanitized['shadow_spread'] = absint($input['shadow_spread']);
        $sanitized['shadow_color'] = sanitize_hex_color($input['shadow_color']);
        $sanitized['shadow_opacity'] = floatval($input['shadow_opacity']);
        $sanitized['tile_spacing'] = absint($input['tile_spacing']);
        $sanitized['content_padding'] = absint($input['content_padding']);
        
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
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('kiss_blog_posts_settings');
                do_settings_sections('kiss-blog-posts');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=kiss-blog-posts') . '">' . __('Settings', 'kiss-blog-posts') . '</a>';
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
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
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
        $posts_count = !empty($instance['posts_count']) ? $instance['posts_count'] : 8;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'kiss-blog-posts'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('posts_count')); ?>"><?php _e('Number of posts:', 'kiss-blog-posts'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('posts_count')); ?>" name="<?php echo esc_attr($this->get_field_name('posts_count')); ?>" type="number" step="1" min="1" max="20" value="<?php echo esc_attr($posts_count); ?>">
        </p>
        <p>
            <a href="<?php echo admin_url('options-general.php?page=kiss-blog-posts'); ?>" target="_blank">
                <?php _e('⚙️ Plugin Settings', 'kiss-blog-posts'); ?>
            </a>
            <br><small><?php _e('Customize appearance, spacing, and shadow effects', 'kiss-blog-posts'); ?></small>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['posts_count'] = (!empty($new_instance['posts_count'])) ? absint($new_instance['posts_count']) : 8;
        return $instance;
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