# KISS Blog Posts Sidebar - Developer API

## Overview
The KISS Blog Posts Sidebar plugin provides multiple ways for developers to integrate and reuse its functionality in other plugins or themes. This document covers all available integration methods.

## Integration Methods

### 1. Shortcode Usage (Easiest)

#### Basic Shortcode
```php
// Display 8 posts in automatic grid layout
echo do_shortcode('[kiss_blog_posts]');
```

#### Advanced Shortcode with Parameters
```php
// Display 6 posts in 3-column grid with title
echo do_shortcode('[kiss_blog_posts count="6" columns="3" title="Latest News" title_url="/blog"]');
```

#### Shortcode Parameters
- **count** (1-20): Number of posts to display (default: 8)
- **columns** (auto|1|2|3|4|5|6): Grid columns (default: auto)
- **title**: Optional title above the grid
- **title_url**: Optional URL to make title clickable
- **class**: Additional CSS class for container
- **style**: Inline CSS styles for container

### 2. Public API Methods (Recommended for Developers)

#### Render Posts Grid
```php
// Get plugin instance
$kiss_blog_posts = KISSBlogPostsSidebar::get_instance();

// Render 6 posts in 3-column grid
$html = $kiss_blog_posts->render_posts_grid(
    6,        // count
    '3',      // columns
    'Latest News',  // title
    '/blog',  // title_url
    'my-custom-class',  // class
    'margin-bottom: 20px;'  // style
);

echo $html;
```

#### Method Signature
```php
public function render_posts_grid(
    $count = 8,           // int: Number of posts (1-20)
    $columns = 'auto',    // string: Grid columns (auto|1|2|3|4|5|6)
    $title = '',          // string: Optional title
    $title_url = '',      // string: Optional title URL
    $class = '',          // string: Additional CSS class
    $style = ''           // string: Inline CSS styles
)
```

### 3. Direct REST API Access

#### Endpoint
```
GET /wp-json/kiss-blog-posts/v1/posts?per_page=6
```

#### Response Format
```json
[
    {
        "id": 123,
        "title": "Post Title",
        "link": "https://example.com/post-title",
        "featured_image": "https://example.com/image.jpg",
        "excerpt": "Post excerpt...",
        "date": "January 15, 2025"
    }
]
```

#### JavaScript Usage
```javascript
// Using the plugin's cached AJAX system
jQuery('.my-custom-container').each(function() {
    var container = jQuery(this);
    var postsCount = 6;
    
    // This will use the plugin's caching and error handling
    loadPosts(container, postsCount, jQuery);
});
```

### 4. Theme Integration Examples

#### In Template Files
```php
// In your theme's template file (e.g., front-page.php)
<div class="homepage-blog-section">
    <h2>Latest from Our Blog</h2>
    <?php echo do_shortcode('[kiss_blog_posts count="6" columns="3"]'); ?>
</div>
```

#### In Functions.php
```php
// Add to your theme's functions.php
function my_theme_blog_posts_section() {
    if (function_exists('KISSBlogPostsSidebar')) {
        $plugin = KISSBlogPostsSidebar::get_instance();
        return $plugin->render_posts_grid(9, '3', 'Recent Articles', '/blog');
    }
    return '<p>Blog posts plugin not available.</p>';
}

// Use in templates
echo my_theme_blog_posts_section();
```

#### Custom Page Template
```php
// In a custom page template
get_header(); ?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <?php the_content(); ?>
        </div>
        <div class="col-md-4">
            <h3>Related Posts</h3>
            <?php echo do_shortcode('[kiss_blog_posts count="4" columns="1"]'); ?>
        </div>
    </div>
</div>

<?php get_footer();
```

### 5. Plugin Integration Examples

#### Check Plugin Availability
```php
// Always check if the plugin is active
if (class_exists('KISSBlogPostsSidebar')) {
    $kiss_blog_posts = KISSBlogPostsSidebar::get_instance();
    // Use the plugin
} else {
    // Fallback or error message
    echo '<p>KISS Blog Posts plugin is required.</p>';
}
```

#### Custom Plugin Integration
```php
class MyCustomPlugin {
    
    public function display_featured_posts() {
        if (!class_exists('KISSBlogPostsSidebar')) {
            return $this->fallback_posts_display();
        }
        
        $kiss_blog_posts = KISSBlogPostsSidebar::get_instance();
        
        return $kiss_blog_posts->render_posts_grid(
            12,      // Show 12 posts
            '4',     // 4 columns
            'Featured Posts',
            '/featured',
            'featured-posts-grid',
            'background: #f9f9f9; padding: 20px;'
        );
    }
    
    private function fallback_posts_display() {
        // Your fallback implementation
        $posts = get_posts(array('numberposts' => 12));
        // ... render posts manually
    }
}
```

## Grid Layout Options

### Column Configurations
- **auto**: Responsive grid (default behavior)
- **1**: Single column (vertical list)
- **2**: Two columns
- **3**: Three columns (recommended for most layouts)
- **4**: Four columns (good for wide layouts)
- **5**: Five columns (for very wide layouts)
- **6**: Six columns (for extra wide layouts)

### Responsive Behavior
The grid automatically adapts to screen sizes:
- **Desktop (1200px+)**: Full column count
- **Tablet (900-1200px)**: Max 4 columns
- **Mobile (600-900px)**: Max 3 columns
- **Small Mobile (400-600px)**: Max 2 columns
- **Tiny Mobile (<400px)**: Single column

## Styling and Customization

### CSS Classes Available
- `.kiss-blog-posts-container`: Main container
- `.kiss-blog-posts-columns-{n}`: Column-specific classes
- `.kiss-blog-posts-tile`: Individual post tile
- `.tile-image`: Post featured image
- `.tile-content`: Post content area
- `.tile-title`: Post title
- `.tile-excerpt`: Post excerpt
- `.tile-date`: Post date

### Custom CSS Example
```css
/* Custom styling for 3-column layout */
.my-custom-class.kiss-blog-posts-columns-3 .kiss-blog-posts-tile {
    border: 2px solid #e0e0e0;
    transition: transform 0.3s ease;
}

.my-custom-class.kiss-blog-posts-columns-3 .kiss-blog-posts-tile:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
```

## Performance Considerations

### Caching
The plugin includes built-in caching:
- **Client-side**: 5-minute localStorage cache
- **Server-side**: HTTP cache headers
- **Automatic invalidation**: When posts are updated

### Best Practices
1. **Use appropriate post counts**: Don't request more posts than needed
2. **Leverage caching**: Multiple instances will share cached data
3. **Responsive design**: Use column settings that work on all devices
4. **CSS optimization**: Add custom styles efficiently

## Error Handling

### Graceful Degradation
```php
function safe_blog_posts_display() {
    try {
        if (class_exists('KISSBlogPostsSidebar')) {
            $plugin = KISSBlogPostsSidebar::get_instance();
            return $plugin->render_posts_grid(6, '3');
        }
    } catch (Exception $e) {
        error_log('KISS Blog Posts error: ' . $e->getMessage());
    }
    
    // Fallback to standard WordPress posts
    return get_posts_fallback();
}
```

## Hooks and Filters (Future Enhancement)

### Planned Developer Hooks
```php
// These hooks are planned for future versions
apply_filters('kiss_blog_posts_query_args', $args);
apply_filters('kiss_blog_posts_post_data', $post_data);
do_action('kiss_blog_posts_before_render', $posts);
do_action('kiss_blog_posts_after_render', $posts);
```

## Support and Compatibility

### Requirements
- WordPress 5.0+
- PHP 7.0+
- jQuery (automatically loaded)

### Cache Compatibility
Fully compatible with:
- WP Rocket, W3 Total Cache, WP Super Cache
- CloudFlare, MaxCDN, Amazon CloudFront
- Redis, Memcached object caching

### Testing Your Integration
Use the plugin's built-in diagnostic tests:
1. Go to **Settings > KISS Blog Posts > Settings**
2. Run **Self-Diagnostic Tests**
3. Verify all tests pass with your integration

## Examples Repository

For complete working examples, see the `/examples` directory in the plugin folder (coming soon) or visit our documentation site.

## Support

For developer support:
1. Use the plugin's self-diagnostic system
2. Enable debug mode for detailed logging
3. Check the changelog for recent updates
4. Review the complete API documentation in plugin settings
