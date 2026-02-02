=== BeanST Image Optimizer ===
Contributors: techlandlab
Tags: images, webp, avif, optimization, converter
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 2.0.7
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart local AVIF & WebP converter for WordPress. Zero limits, local processing, helps improve Core Web Vitals. Includes PDF compression and Directory Scanning.

== Description ==

**BeanST Image Optimizer** is a powerful, locally-hosted image optimization suite designed for performance-obsessed WordPress users. Unlike other plugins, BeanST processes everything on **your** server—no API limits, no monthly credits, and no third-party cloud dependencies.

= 🚀 V2.0 Major Update: UX Revolution =
We've completely rebuilt the interface to be simpler, faster, and more intuitive for everyone.
*   **New Dashboard**: See your optimization status at a glance with the new Hero Section.
*   **Quick Actions**: Auto-optimize, cleanup, and settings accessible via cards.
*   **Modern Design**: simplified layout inspired by WordPress core aesthetics.
*   **Better Feedback**: Clear progress bars, helpful tooltips, and no technical jargon.

= Key Features =
*   **Visual Proof (Comparison Slider)**: Compare original vs optimized images side-by-side.
*   **PDF Optimization**: Compress large PDF documents locally.
*   **Directory Janitor**: Optimize images in theme folders or custom uploads.
*   **Smart HEIC Support**: Automatic conversion of iPhone (HEIC) uploads to JPEG.
*   **Unlimited Conversion**: No API limits, 100% local processing.
*   **Memory Guard**: Intelligent processing to prevent server crashes.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/beanst-image-optimizer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings -> BeanST Optimizer to configure.

== Frequently Asked Questions ==

= Does this require an API key? =
No. All processing is done locally on your server using PHP extensions (Imagick/GD) or system tools (Ghostscript).

= What are the server requirements? =
We recommend PHP 8.1+ and the Imagick extension with WebP/AVIF support.

== Changelog ==

= 2.0.7 =
*   **Security**: Hardened Admin JS against XSS (replaced string concatenation with standard DOM API).
*   **Security**: Added `rel="noopener noreferrer"` to all external links.

= 2.0.6 =
*   **Fix**: Update support links to point to WordPress.org forums.
*   **Cleanup**: Removed unused legacy view files.

= 2.0.5 =
*   **Compliance**: Refined plugin description to accurately reflect local algorithmic processing (removed "AI" terminology).
*   **Tweak**: Updated guidelines compliance for repository submission.

= 2.0.4 =
*   **Security**: Applied strict escaping output (esc_html, esc_url) to all admin views.
*   **Audit**: Verified enqueue isolation to load scripts only on plugin pages.

= 2.0.3 =
*   **Fix**: Removed external branding links from settings page footer to comply with repository guidelines.

= 2.0.2 =
*   **Fix**: Replaced filesystem operations with standard WordPress file system API.
*   **Fix**: Removed potential unsafe file deletion in uninstall process.
*   **Fix**: Reduced tag count for repository compliance.
*   **Tweak**: Updated descriptions to align with WordPress.org guidelines.

= 2.0.1 =
*   **UX Fix**: Improved text contrast in the Hero Section for better readability.
*   **Security**: Enhanced input sanitization for bulk actions.

= 2.0.0 =
*   **NEW UX**: Complete redesign of the settings page with a modern, simplified dashboard.
*   **Feature**: Added "Hero Section" with visual progress tracking.
*   **Feature**: Added "Quick Action Cards" for common tasks.
*   **Improvement**: Implemented AJAX-based toggle switches for instant settings updates.
*   **Improvement**: Replaced tabs with a single, scrollable page layout.
*   **Dev**: PHPCS security fixes and code modernization.

= 1.0.1 =
*   Added Visual Proof comparison slider.
*   Added PDF Optimization support.
*   Added Directory Janitor.

= 1.0.0 =
*   Initial release.
