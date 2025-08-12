/* KISS Blog Posts Sidebar JavaScript - v1.0.7 */
(function() {
    'use strict';

    // Dependency and environment checks
    function checkDependencies() {
        var issues = [];

        if (typeof jQuery === 'undefined') {
            issues.push('jQuery is not loaded');
        }

        if (typeof kissBlogs === 'undefined') {
            issues.push('kissBlogs configuration object is missing');
        } else {
            if (!kissBlogs.restUrl) {
                issues.push('REST API URL is not configured');
            }
            if (!kissBlogs.nonce) {
                issues.push('Security nonce is missing');
            }
        }

        return issues;
    }

    // Safe initialization with dependency checks
    function initializePlugin() {
        var dependencyIssues = checkDependencies();

        if (dependencyIssues.length > 0) {
            console.error('KISS Blog Posts: Dependency issues detected:', dependencyIssues);
            showFallbackContent(dependencyIssues);
            return;
        }

        // Debug ping
        if (kissBlogs.debug) {
            console.log('KISS Blog Posts: Debug mode is ON. Script is loading successfully.');
        }

        // Initialize widgets
        jQuery(document).ready(function($) {
            initializeWidgets($);
        });
    }

    // Fallback content for when dependencies fail
    function showFallbackContent(issues) {
        // Use vanilla JavaScript since jQuery might not be available
        var containers = document.querySelectorAll('.kiss-blog-posts-container');

        containers.forEach(function(container) {
            var errorHtml = '<div class="kiss-blog-posts-error">' +
                '<strong>Widget temporarily unavailable</strong><br>' +
                '<small>Please refresh the page or contact support if this persists.</small>' +
                '</div>';

            container.innerHTML = errorHtml;
        });
    }

    // Main widget initialization
    function initializeWidgets($) {
        $(".kiss-blog-posts-container").each(function() {
            var container = $(this);
            var postsCount = container.data("posts-count") || 8;

            // Validate posts count
            if (isNaN(postsCount) || postsCount < 1 || postsCount > 20) {
                postsCount = 8; // Default fallback
            }

            // Load posts with enhanced error handling
            loadPosts(container, postsCount, $);
        });
    }

    // Enhanced post loading with retry logic
    function loadPosts(container, postsCount, $, retryCount) {
        retryCount = retryCount || 0;
        var maxRetries = 3;
        var retryDelay = Math.pow(2, retryCount) * 1000; // Exponential backoff

        var ajaxData = {
            per_page: postsCount
        };

        // Add cache-buster in debug mode
        if (kissBlogs.debug) {
            ajaxData._cache_buster = new Date().getTime();
        }

        // Show loading state
        if (retryCount === 0) {
            container.html('<div class="kiss-blog-posts-loading">Loading posts...</div>');
        }

        // AJAX request with enhanced error handling
        $.ajax({
            url: kissBlogs.restUrl + "posts",
            method: "GET",
            data: ajaxData,
            timeout: 10000, // 10 second timeout
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-WP-Nonce", kissBlogs.nonce);
            },
            success: function(posts, textStatus, xhr) {
                // Validate response
                if (!validateApiResponse(posts)) {
                    handleError(container, 'Invalid response format', null, $, postsCount, retryCount);
                    return;
                }

                if (posts && posts.length > 0) {
                    renderPosts(container, posts, $);
                } else {
                    showNoPostsMessage(container);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                handleError(container, textStatus, xhr, $, postsCount, retryCount);
            }
        });
    }

    // Comprehensive error handling
    function handleError(container, textStatus, xhr, $, postsCount, retryCount) {
        var maxRetries = 3;
        var errorDetails = getErrorDetails(textStatus, xhr);

        // Log detailed error for debugging
        if (kissBlogs.debug) {
            console.error('KISS Blog Posts Error:', {
                textStatus: textStatus,
                status: xhr ? xhr.status : 'unknown',
                statusText: xhr ? xhr.statusText : 'unknown',
                responseText: xhr ? xhr.responseText : 'unknown',
                retryCount: retryCount
            });
        }

        // Retry logic
        if (retryCount < maxRetries && shouldRetry(textStatus, xhr)) {
            var retryDelay = Math.pow(2, retryCount) * 1000;

            container.html(
                '<div class="kiss-blog-posts-loading">' +
                'Connection issue detected. Retrying in ' + (retryDelay / 1000) + ' seconds... ' +
                '<small>(Attempt ' + (retryCount + 2) + ' of ' + (maxRetries + 1) + ')</small>' +
                '</div>'
            );

            setTimeout(function() {
                loadPosts(container, postsCount, $, retryCount + 1);
            }, retryDelay);

            return;
        }

        // Show user-friendly error with retry button
        showErrorWithRetry(container, errorDetails, $, postsCount);
    }

    // Helper functions for error handling
    function getErrorDetails(textStatus, xhr) {
        var details = {
            message: 'Unable to load posts',
            technical: textStatus || 'Unknown error',
            userFriendly: 'There was a problem loading the blog posts.'
        };

        if (xhr) {
            switch (xhr.status) {
                case 0:
                    details.userFriendly = 'Network connection problem. Please check your internet connection.';
                    break;
                case 404:
                    details.userFriendly = 'Blog posts service not found. Please contact support.';
                    break;
                case 500:
                    details.userFriendly = 'Server error. Please try again in a few minutes.';
                    break;
                case 403:
                    details.userFriendly = 'Access denied. Please refresh the page.';
                    break;
                default:
                    if (xhr.status >= 400) {
                        details.userFriendly = 'Server error (' + xhr.status + '). Please try again.';
                    }
            }
        } else if (textStatus === 'timeout') {
            details.userFriendly = 'Request timed out. Please check your connection and try again.';
        }

        return details;
    }

    function shouldRetry(textStatus, xhr) {
        // Don't retry on client errors (4xx) except 408 (timeout)
        if (xhr && xhr.status >= 400 && xhr.status < 500 && xhr.status !== 408) {
            return false;
        }

        // Retry on network errors, timeouts, and server errors
        return textStatus === 'timeout' || textStatus === 'error' ||
               (xhr && (xhr.status === 0 || xhr.status >= 500));
    }

    function showErrorWithRetry(container, errorDetails, $, postsCount) {
        var retryId = 'retry-' + Math.random().toString(36).substr(2, 9);

        var errorHtml =
            '<div class="kiss-blog-posts-error">' +
                '<strong>' + escapeHtml(errorDetails.userFriendly) + '</strong><br>' +
                '<button id="' + retryId + '" class="kiss-blog-posts-retry-btn" style="margin-top: 10px; padding: 8px 16px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">' +
                    'Try Again' +
                '</button>' +
                (kissBlogs.debug ? '<br><small style="color: #666; margin-top: 5px; display: block;">Debug: ' + escapeHtml(errorDetails.technical) + '</small>' : '') +
            '</div>';

        container.html(errorHtml);

        // Add retry functionality
        $('#' + retryId).on('click', function() {
            $(this).prop('disabled', true).text('Retrying...');
            loadPosts(container, postsCount, $, 0);
        });
    }

    function showNoPostsMessage(container) {
        container.html(
            '<div class="kiss-blog-posts-error" style="background: #f9f9f9; color: #666;">' +
                '<strong>No blog posts found</strong><br>' +
                '<small>There are currently no published posts to display.</small>' +
            '</div>'
        );
    }

    // Validate API response structure
    function validateApiResponse(posts) {
        if (!Array.isArray(posts)) {
            return false;
        }

        // Check if each post has required fields
        for (var i = 0; i < posts.length; i++) {
            var post = posts[i];
            if (!post || typeof post !== 'object' ||
                !post.hasOwnProperty('id') ||
                !post.hasOwnProperty('title') ||
                !post.hasOwnProperty('link')) {
                return false;
            }
        }

        return true;
    }

    // Safe HTML rendering with XSS protection
    function renderPosts(container, posts, $) {
        var html = "";

        posts.forEach(function(post) {
            // Validate and sanitize post data
            var safePost = sanitizePostData(post);

            // Construct safe image style
            var imageStyle = '';
            if (safePost.featured_image && isValidImageUrl(safePost.featured_image)) {
                imageStyle = 'background-image: url(' + escapeHtml(safePost.featured_image) + ');';
            } else {
                imageStyle = 'background-color: #f0f0f0;';
            }

            html += '<div class="kiss-blog-posts-tile" onclick="window.location.href=\'' + escapeHtml(safePost.link) + '\'">';
            html += '  <div class="tile-image" style="' + imageStyle + '"></div>';
            html += '  <div class="tile-content">';

            // Debug output if enabled
            if (kissBlogs.debug) {
                html += '<pre style="font-size: 10px; line-height: 1.2; word-wrap: break-word; white-space: pre-wrap; background: #fff; color: #000; padding: 10px; border: 1px dashed red; margin-bottom: 10px;">' +
                        escapeHtml(JSON.stringify(post, null, 2)) + '</pre>';
            }

            html += '    <h3 class="tile-title"><a href="' + escapeHtml(safePost.link) + '">' + escapeHtml(safePost.title) + '</a></h3>';

            if (safePost.excerpt) {
                html += '    <p class="tile-excerpt">' + escapeHtml(safePost.excerpt) + '</p>';
            }

            html += '    <p class="tile-date">' + escapeHtml(safePost.date) + '</p>';
            html += '  </div>';
            html += '</div>';
        });

        container.html(html);
    }

    // Data sanitization functions
    function sanitizePostData(post) {
        return {
            id: parseInt(post.id) || 0,
            title: decodeHtmlEntities((post.title || 'Untitled Post')).substring(0, 200), // Decode and limit title length
            link: post.link || '#',
            featured_image: post.featured_image || '',
            excerpt: decodeHtmlEntities((post.excerpt || '')).substring(0, 300), // Decode and limit excerpt length
            date: post.date || 'No date'
        };
    }

    function isValidImageUrl(url) {
        if (!url || typeof url !== 'string') {
            return false;
        }

        // Basic URL validation
        try {
            new URL(url);
            return /\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i.test(url);
        } catch (e) {
            return false;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';

        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function decodeHtmlEntities(text) {
        if (!text) return '';

        var div = document.createElement('div');
        div.innerHTML = text;
        return div.textContent || div.innerText || '';
    }

    // Initialize the plugin
    initializePlugin();

})();
