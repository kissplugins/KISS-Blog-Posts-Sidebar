/* KISS Blog Posts Sidebar JavaScript */
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
            
            html += "<div class=\"kiss-blog-posts-tile\" onclick=\"window.location.href='" + post.link + "'\">";
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
});