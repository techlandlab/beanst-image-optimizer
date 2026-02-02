<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('beanst_options');
delete_option('beanst_external_registry');
delete_option('beanst_optimizer_settings');
delete_option('beanst_optimizer_license');

// Delete transients
delete_transient('beanst_license_check');

// Optional: Delete converted images
// $uploads = wp_upload_dir();
// $files = glob($uploads['basedir'] . '/**/*.{webp,avif}', GLOB_BRACE);
// foreach ($files as $file) {
//     unlink($file);
// }
