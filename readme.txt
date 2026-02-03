=== BeanST Image Optimizer ===
Contributors: techlandlab
Tags: images, webp, avif, optimization, converter
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.1.2
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart local AVIF & WebP converter for WordPress. Zero limits, local processing, helps improve Core Web Vitals. Includes PDF compression and Directory Scanning.

== Description ==

**BeanST Image Optimizer** is a powerful, locally-hosted image optimization suite designed for performance-obsessed WordPress users. Unlike other plugins, BeanST processes everything on **your** server—no API limits, no monthly credits, and no third-party cloud dependencies.

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

= 1.1.2 =
* Public Release Candidate.
* Compliance: Full Internationalization (I18n) support.
* Security: Added index.php silencers to all directories.
* Cleanup: Removed legacy files.

= 1.0.1 =
* Initial public release structure.

