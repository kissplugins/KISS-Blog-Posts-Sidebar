/* KISS Blog Posts Sidebar JavaScript */
jQuery(document).ready(function($) {
    $(".kiss-blog-posts-container").each(function() {
        var container = $(this);
        var postsCount = container.data("posts-count") || 8;
        
        var ajaxData = {
            per_page: postsCount
        };

        // If debug mode is enabled, add a cache-buster.
        if (kissBlogs.debug) {
            ajaxData._cache_buster = new Date().getTime();
        }
        
        // Fetch posts via REST API
        $.ajax({
            url: kissBlogs.restUrl + "posts",
            method: "GET",
            data: ajaxData,
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
            // Construct the inline style attribute.
            // We use single quotes for the HTML attribute and no quotes inside url()
            // to create the most robust and parseable HTML string.
            var styleAttr = "";
            if (post.featured_image) {
                styleAttr = "style='background-image: url(" + post.featured_image + ")'";
            } else {
                styleAttr = "style='background-color: #f0f0f0'";
            }
            
            html += "<div class=\"kiss-blog-posts-tile\" onclick=\"window.location.href='" + post.link + "'\">";
            html += "  <div class=\"tile-image\" " + styleAttr + "></div>";
            html += "  <div class=\"tile-content\">";

            // If debug mode is enabled, print the raw post object.
            if (kissBlogs.debug) {
                html += "<pre style='font-size: 10px; line-height: 1.2; word-wrap: break-word; white-space: pre-wrap; background: #fff; color: #000; padding: 10px; border: 1px dashed red; margin-bottom: 10px;'>" + JSON.stringify(post, null, 2) + "</pre>";
            }

            html += "    <h3 class=\"tile-title\"><a href=\"" + post.link + "\">" + post.title + "</a></h3>";
            if (post.excerpt) {
                html += "    <p class=\"tile-excerpt\">" + post.excerpt + "</p>";
            }
            html += "    <p class=\"tile-date\">" + post.date + "</p>";
            html += "  </div>";
            html += "</div>";
        });
        
        container.html(html);
    }
});