# 🚀 BeanST Image Optimizer

**Smart local AVIF & WebP converter for WordPress. Zero limits, local processing.**

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)

---

## 📖 Overview

**BeanST Image Optimizer** is a powerful, locally-hosted image optimization suite designed for performance-obsessed WordPress users. Unlike other plugins, BeanST processes everything on **your** server—no API limits, no monthly credits, and no third-party cloud dependencies.

### Why BeanST?

- **🔒 Privacy First**: All processing happens on your server. Zero external API calls.
- **💰 Zero Costs**: No subscription fees, no usage limits, no hidden charges.
- **⚡ Performance**: Generate AVIF & WebP images automatically to boost Core Web Vitals.
- **🎯 SEO Ready**: Built-in filename and alt-text optimizer for better search rankings.
- **🧹 Smart Cleanup**: Automatic detection and removal of orphaned optimization files.

---

## ✨ Key Features

### Image Optimization
- **AVIF & WebP Conversion**: Automatically generate next-gen image formats on upload
- **Visual Comparison Slider**: See the difference between original and optimized images
- **Bulk Optimizer**: Process your entire Media Library with one click
- **Memory Guard**: Intelligent processing prevents server crashes during batch operations
- **Auto-Backup**: Optional original image preservation before optimization

### Advanced Features
- **HEIC Support**: Automatic conversion of iPhone (HEIC) uploads to JPEG
- **Directory Janitor**: Scan and optimize images in theme folders or custom directories
- **SEO Optimizer**: AI-powered filename and alt-text suggestions
- **Orphan Cleanup**: Detect and remove unused WebP/AVIF files to free up space

### Developer Friendly
- **Full I18n Support**: Translation-ready with proper WordPress localization
- **Clean Codebase**: Follows WordPress Coding Standards
- **Extensible**: Well-documented hooks and filters
- **No Vendor Lock-in**: Works with standard WordPress image handling

---

## 📋 Requirements

### Server Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 8.1 or higher
- **Memory**: Minimum 128MB PHP memory limit (256MB+ recommended)

### Recommended PHP Extensions
- **Imagick** (preferred): With WebP and AVIF delegates enabled
- **GD** (fallback): WebP support only

### Checking Your Server
Run this in your WordPress admin or via WP-CLI:
```php
// Check Imagick AVIF support
$imagick = new Imagick();
$formats = $imagick->queryFormats();
echo in_array('AVIF', $formats) ? '✅ AVIF supported' : '❌ AVIF not supported';
echo in_array('WEBP', $formats) ? '✅ WebP supported' : '❌ WebP not supported';
```

---

## 🔧 Installation

### Via WordPress Admin (Recommended)
1. Download `beanst-image-optimizer.zip` from [Releases](https://github.com/techlandlab/beanst-image-optimizer/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin
5. Navigate to **Settings → BeanST Optimizer**

### Via WP-CLI
```bash
wp plugin install beanst-image-optimizer.zip --activate
```

### Manual Installation
```bash
cd /path/to/wordpress/wp-content/plugins
unzip beanst-image-optimizer.zip
wp plugin activate beanst-image-optimizer
```

---

## 🎮 Usage Guide

### First-Time Setup

1. **Navigate to Settings**
   - Go to **WordPress Admin → Settings → BeanST Optimizer**

2. **Configure Output Formats**
   - Enable **WebP** (universal browser support)
   - Enable **AVIF** (if your server supports it - smaller files, Chrome/Firefox only)

3. **Set Quality Level**
   - Default: 80 (good balance)
   - Higher (85-95): Better quality, larger files
   - Lower (60-75): Smaller files, visible quality loss

4. **Enable Auto-Optimize**
   - Toggle **ON** to automatically convert new uploads
   - Toggle **OFF** to manually control which images to optimize

### Bulk Optimization

Optimize all existing images in your Media Library:

1. Click **Hero Section CTA** on the BeanST settings page
2. The optimizer will process images in batches (memory-safe)
3. Monitor progress in real-time
4. Pause/Resume anytime

### Directory Janitor

Optimize images outside the Media Library (e.g., theme files):

1. Go to **Settings → BeanST Optimizer → Directory Janitor**
2. Add paths relative to WordPress root:
   ```
   wp-content/themes/your-theme/images
   wp-content/uploads/custom-folder
   ```
3. Click **Scan** to discover images
4. Select images and click **Optimize Selected**

### SEO Optimization

Fix generic filenames and missing alt-text:

1. Go to **Media Library (List View)**
2. Look for the **SEO Review** column
3. Click **"Apply SEO"** on flagged images
4. BeanST will suggest:
   - Descriptive filename based on post title
   - Auto-generated alt-text from context

---

## 🖼️ How It Works

### Image Processing Flow

```
Original Upload (example.jpg)
        ↓
    [BeanST Converter]
        ↓
    ├─→ example.webp (WebP version)
    ├─→ example.avif (AVIF version, if enabled)
    └─→ example-backup.jpg (original backup, if enabled)
```

### WordPress Integration

BeanST hooks into WordPress's image processing pipeline:

1. **On Upload**: `wp_generate_attachment_metadata` filter
2. **Frontend Delivery**: WordPress automatically serves WebP/AVIF to compatible browsers via `<picture>` tags (if theme supports it) or direct replacement
3. **Cleanup**: Orphan detection runs when you delete images from Media Library

---

## 🛠️ Configuration Options

### Main Settings

| Option | Description | Default |
|--------|-------------|---------|
| **Auto-Convert on Upload** | Automatically optimize new images | `ON` |
| **Output Formats** | WebP, AVIF, or both | `WebP` |
| **Quality** | Compression quality (1-100) | `80` |
| **Max Width** | Resize originals larger than this | `2560px` |
| **Keep Backups** | Save original before resizing | `OFF` |

### Advanced Settings

| Option | Description | Default |
|--------|-------------|---------|
| **Directory Scan Paths** | Custom folders to scan | Empty |
| **Orphan Cleanup** | Auto-remove unused optimized files | Manual |

---

## ❓ FAQ

### Does this require an API key?
No. All processing is done locally on your server using PHP extensions (Imagick/GD). No external services, no API keys, no usage limits.

### Will this work on shared hosting?
Yes, if your host provides PHP 8.1+ and Imagick extension. BeanST includes a Memory Guard to prevent crashes on restricted environments. However, very large image libraries may require VPS/dedicated hosting.

### What happens to my original images?
By default, originals are kept untouched. BeanST creates WebP/AVIF versions alongside them. You can optionally enable "Resize Originals" to save server space (with backup).

### Does this replace images in my posts?
No. WordPress handles serving the optimized formats automatically via its responsive image system. Your post content remains unchanged.

### Can I bulk optimize existing images?
Yes! Use the **"Optimize Library"** button on the BeanST settings page. The process is batched and pausable.

### How do I know if AVIF is working?
Check your server's Imagick configuration:
```bash
identify -list format | grep AVIF
```
If you see `AVIF* rw+`, you're good to go.

### What browsers support AVIF?
- ✅ Chrome 85+
- ✅ Firefox 93+
- ✅ Edge 121+
- ❌ Safari (as of 2024)

WebP is universally supported, so it's always safe to enable both.

---

## 🐛 Troubleshooting

### "Memory limit" errors during bulk optimization
**Solution**: Increase PHP memory in `wp-config.php`:
```php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

### AVIF images not generating
**Solution**: Check Imagick AVIF support:
```bash
php -r "phpinfo();" | grep -i imagick
convert -list format | grep AVIF
```
If missing, install libavif delegate for Imagick.

### WebP images are larger than originals
**Solution**: Lower quality setting (try 75-80) or check if source images are already heavily compressed.

### Images not showing on frontend
**Cause**: Your theme may not support responsive images.  
**Solution**: Use a plugin like "WebP Express" for `.htaccess` rewrite rules, or switch to a modern theme.

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. **Fork** the repository
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Follow WordPress Coding Standards**: Use PHPCS with WordPress ruleset
4. **Write tests** (if applicable)
5. **Commit** with clear messages: `git commit -m 'Add: AVIF quality presets'`
6. **Push** to your branch: `git push origin feature/amazing-feature`
7. **Open a Pull Request**

### Development Setup

```bash
git clone https://github.com/techlandlab/beanst-image-optimizer.git
cd beanst-image-optimizer
composer install  # If using Composer for development tools
phpcs --standard=WordPress .  # Check coding standards
```

---

## 📜 License

**GPL-2.0-or-later**

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

See [LICENSE](LICENSE) for full details.

---

## 🙏 Credits

**Developed by:** [TechLandLab](https://techlandlab.com)  
**Contributors:** See [CONTRIBUTORS.md](CONTRIBUTORS.md)

### Special Thanks
- WordPress Core Team for the image processing APIs
- Imagick developers for AVIF/WebP delegates
- All users who provided feedback during beta testing

---

## 📞 Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/techlandlab/beanst-image-optimizer/issues)
- **WordPress.org Forums**: [Community support](https://wordpress.org/support/plugin/beanst-image-optimizer/)
- **Documentation**: You're reading it! 📖

---

## 🗺️ Roadmap

### v1.2.0 (Planned)
- [ ] WebP/AVIF quality presets (Low/Medium/High/Custom)
- [ ] Progress API for external tools
- [ ] WP-CLI commands for bulk operations

### v1.3.0 (Future)
- [ ] CDN integration helpers
- [ ] Advanced caching strategies
- [ ] Performance analytics dashboard

### v2.0.0 (Vision)
- [ ] Support for additional formats (JPEG XL, WebP2)
- [ ] AI-powered smart cropping
- [ ] Cloud backup integration (optional)

---

<p align="center">
  <strong>Made with ❤️ for the WordPress community</strong><br>
  Star ⭐ this repo if you find it helpful!
</p>
