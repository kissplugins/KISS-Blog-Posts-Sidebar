# KISS Blog Posts Sidebar - Changelog

## Version 1.0.10 (2025-08-12) - Public Access Fix

### 🚨 CRITICAL ACCESS FIX
- **Fixed**: "Access denied. Please refresh the page." error for non-logged-in users
- **Issue**: Overly restrictive permission check was blocking public access to published posts
- **Solution**: Removed `current_user_can('read')` check since published posts should be publicly accessible
- **Impact**: Widget now works correctly for all visitors, not just logged-in users

### Technical Details
- **File**: kiss-blog-posts-sidebar.php
- **Change**: Removed permission check in `get_posts_rest()` method
- **Reasoning**: Published posts are public content and the REST API endpoint already has `permission_callback => '__return_true'`

---

## Version 1.0.9 (2025-08-12) - HTML Entity Decoding Fix

### 🔧 CONTENT DISPLAY FIX
- **Fixed**: HTML entities in post titles and excerpts not being decoded properly
- **Examples Fixed**:
  - `&#8217;` (right single quotation mark) → `'`
  - `&#8220;` and `&#8221;` (left/right double quotation marks) → `"` and `"`
  - `&#8211;` (en dash) → `–`
  - `&#038;` (ampersand) → `&`

### Technical Changes
- **PHP**: Added `html_entity_decode()` with UTF-8 encoding to REST API response
- **JavaScript**: Added `decodeHtmlEntities()` function for client-side decoding
- **Impact**: Post titles and excerpts now display with proper punctuation and special characters

---

## Version 1.0.8 (2025-08-12) - Critical Bug Fix

### 🚨 CRITICAL BUG FIX
- **Fixed**: JavaScript syntax error causing "SyntaxError: Unexpected keyword 'function'. Expected ')' to end a compound expression"
- **Fixed**: Widget stuck on "Loading posts..." due to extra closing brace in JavaScript
- **Impact**: This was preventing the widget from functioning at all in v1.0.7

### Technical Details
- **File**: assets/js/kiss-blog-posts.js
- **Issue**: Extra closing brace on line 164 causing syntax error
- **Resolution**: Removed duplicate closing brace

---

## Version 1.0.7 (2025-08-12) - Phase 1: Frontend Rendering Reliability

### 🚨 CRITICAL IMPROVEMENTS - Frontend Rendering Reliability

#### Enhanced Error Handling & User Feedback
- **Added**: Detailed error messages with specific failure reasons
- **Added**: User-friendly error states with retry buttons
- **Added**: Fallback content when API is unavailable
- **Added**: Loading state improvements with timeout handling
- **Added**: Progressive error recovery with retry mechanisms

#### Script Dependencies & Initialization
- **Added**: Dependency availability checks before execution
- **Added**: Graceful degradation if jQuery unavailable
- **Added**: DOM ready state verification
- **Added**: Script loading timeout detection
- **Added**: Alternative initialization methods

#### Data Validation & Safe Rendering
- **Added**: Comprehensive API response validation
- **Added**: Safe HTML generation with proper escaping
- **Added**: Fallback values for missing/invalid data
- **Added**: Image URL validation before rendering
- **Added**: Content sanitization and length limits

### Technical Changes

#### JavaScript Enhancements (assets/js/kiss-blog-posts.js)
- **Rewritten**: Complete JavaScript architecture with dependency checking
- **Added**: Comprehensive error handling with specific error messages
- **Added**: Automatic retry logic with exponential backoff (up to 3 retries)
- **Added**: 10-second timeout for AJAX requests
- **Added**: User-friendly retry buttons in error states
- **Added**: XSS protection with HTML escaping for all dynamic content
- **Added**: API response validation before rendering
- **Added**: Image URL validation before display
- **Added**: Content length limits (titles: 200 chars, excerpts: 300 chars)
- **Added**: Graceful degradation when jQuery is unavailable
- **Added**: Enhanced debug logging with detailed error information

#### CSS Improvements (assets/css/kiss-blog-posts.css)
- **Enhanced**: Error message styling with better visual hierarchy
- **Added**: Retry button styling with hover and disabled states
- **Enhanced**: Loading state styling with borders and backgrounds
- **Added**: Better visual feedback for different error types

#### PHP Backend Enhancements (kiss-blog-posts-sidebar.php)
- **Enhanced**: REST API endpoint with comprehensive error handling
- **Added**: Parameter validation with specific error messages
- **Added**: Exception handling with proper error logging
- **Added**: Enhanced featured image retrieval with multiple size fallbacks
- **Added**: Data sanitization for all output fields
- **Added**: Permission checks for post access
- **Added**: Debug logging for troubleshooting (when WP_DEBUG enabled)
- **Added**: Safe featured image URL validation

### Bug Fixes
- **Fixed**: Generic "Error loading posts" now shows specific, actionable error messages
- **Fixed**: Widget completely breaking when jQuery or dependencies are missing
- **Fixed**: Potential XSS vulnerabilities in dynamic HTML generation
- **Fixed**: Silent failures when API responses are malformed or missing required fields
- **Fixed**: Widget hanging indefinitely on network timeouts (now 10s timeout)
- **Fixed**: Crashes when post data is incomplete or corrupted
- **Fixed**: Featured images not loading due to invalid URLs
- **Fixed**: No feedback when posts query fails on server side

---

## Previous Versions

### Version 1.0.6 (2025-08-09)
- **Added**: Console log "ping" when debug mode is active to help diagnose script loading issues

### Version 1.0.5 (2025-08-09)
- **Added**: Convenient link to WordPress's Media Settings page in widget configuration

### Version 1.0.4 (2025-08-09)
- **Added**: Optional debug mode, available via switch on settings page

### Version 1.0.3 (2025-08-09)
- **Fixed**: Featured images not displaying due to HTML parsing conflicts with quotes
- **Removed**: Temporary on-screen debugging code

### Version 1.0.2 (2025-08-09)
- **Added**: On-screen debugging and cache-busting for featured image issues

### Version 1.0.1 (2025-08-09)
- **Fixed**: Modified REST API callback to more reliably fetch featured image URLs

### Version 1.0.0 (2025-08-09)
- **Initial**: Plugin release with basic functionality
