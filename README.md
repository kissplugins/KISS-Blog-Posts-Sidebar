# KISS Blog Posts Sidebar
**Contributors:** KISS Plugins
**Tags:** sidebar, widget, posts, recent posts, blog, simple, elegant, customizer, rest api
**Requires at least:** 5.0
**Tested up to:** 6.5
**Stable tag:** 1.0.5
**License:** GPL v2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html
A simple and elegant recent blog posts widget for your sidebar with customizable rounded corners and drop shadows.
## Description
The **KISS Blog Posts Sidebar** plugin provides a beautiful, modern, and highly customizable widget to display your recent blog posts. Using the WordPress REST API for fast, asynchronous loading, it presents posts in a clean, tile-based layout with featured images, titles, excerpts, and dates.
Customize everything from the corner radius and drop shadows to tile spacing and padding directly from the plugin's settings page to perfectly match your site's design.
## Features
- **Elegant Tile Layout:** Displays recent posts in a clean, grid-based tile format.
- **Fast, Modern Loading:** Uses the WordPress REST API to load posts without slowing down your page.
- **Highly Customizable:** Fine-tune the appearance with settings for:
	- Border Radius
	- Drop Shadow (Blur, Spread, Color, Opacity)
	- Vertical Spacing
	- Content Padding
- **Simple Widget:** Easy to add to any sidebar or widget-ready area.
- **Developer Friendly:** Includes an optional debug mode for easy troubleshooting.
- **Lightweight & Efficient:** Built with performance in mind.
## Installation
1. Upload the `kiss-blog-posts-sidebar` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Appearance > Widgets**.
4. Drag the **KISS Blog Posts Sidebar** widget to your desired sidebar.
5. Configure the widget title and the number of posts to display.
## Configuration
### Widget Settings
Once you add the widget to a sidebar, you can configure two options directly:
- **Title:** The title that appears above the post tiles (e.g., "Recent Posts").
- **Number of posts:** How many recent posts to display (from 1 to 20).
### Plugin Settings
For detailed styling options, navigate to **Settings > KISS Blog Posts** in your WordPress dashboard. Here you can customize the visual appearance of the tiles.
- **Styling Options:** Control the roundness of corners, the size and color of the drop shadow, and the spacing between and within the tiles.
- **Debugging:** A dedicated section for troubleshooting.
	- **Enable Debug Mode:** If you are having issues (e.g., images not appearing), you can enable this setting. It will display the raw data being sent to the widget, which is invaluable for diagnosing problems.
## Frequently Asked Questions (FAQ)
**Where does the plugin get its thumbnail images from?**
The plugin is designed to be efficient and flexible. It retrieves the featured image for each post by checking for available image sizes in a specific order:
1. It first looks for the `medium` size image. This is the preferred size as it typically offers the best balance of quality and file size for a sidebar.
2. If a `medium` version is not available, it falls back to the `thumbnail` size.
3. If neither of those is found, it will use the `full` size (the original, unmodified image you uploaded).
To prevent blurry images, you should ensure your **Medium** image size is large enough for the widget area. You can adjust this in your WordPress dashboard under **Settings > Media**.
## Troubleshooting
### Featured Images Are Blurry
Blurry thumbnails are usually caused by WordPress generating images that are too small for the space they need to fill.
1. **Check Media Settings:** Go to **Settings > Media** and ensure your `Medium` size settings are large enough for the widget area. Consult your theme's documentation for recommended sizes.
2. **Regenerate Thumbnails:** After correcting your media settings, you must regenerate the thumbnails for your existing images. The easiest way is to use a plugin like [Regenerate Thumbnails](https://www.google.com/search?q=https://wordpress.org/plugins/regenerate-thumbnails/). Install it, go to **Tools > Regenerate Thumbnails**, and run the process.

If the first plugin does not work, we recommend trying this one:
https://wordpress.org/plugins/force-regenerate-thumbnails/

### Featured Images Are Not Showing Up
1. **Verify the Post Has a Featured Image:** First, edit the post in question and confirm that a "Featured Image" has been set in the WordPress editor.
2. **Enable Debug Mode:** Go to **Settings > KISS Blog Posts > Debugging** and check the "Enable Debug Mode" box.
3. **Check the Debug Output:** Refresh your site's front end. The widget will now display the raw data for each post. Look at the `featured_image` field.
	- If the field is an empty string (`""`), it means WordPress could not find a featured image for that post.
	- If the field contains a URL, but the image still doesn't show, there may be a caching issue or a problem with your theme's CSS interfering with the plugin.
## Changelog
### 1.0.5 (2025-08-09)
- **Add:** Added a convenient link to WordPress’s Media Settings page in the widget configuration and on the main plugins page.
### 1.0.4 (2025-08-09)
- **Add:** Implemented an optional debug mode, available via a new switch on the settings page.
### 1.0.3 (2025-08-09)
- **Fix:** Resolved an issue where featured images would not display due to HTML parsing conflicts with quotes in inline styles.
- **Remove:** Removed temporary on-screen debugging code.
### 1.0.2 (2025-08-09)
- **Add:** Implemented on-screen debugging and cache-busting to diagnose persistent featured image issue.
### 1.0.1 (2025-08-09)
- **Fix:** Modified the REST API callback to more reliably fetch featured image URLs.
### 1.0.0
- Initial release.
