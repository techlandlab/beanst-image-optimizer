<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('beanst_options');
delete_option('beanst_external_registry');
delete_option('beanst_optimizer_settings');
// Note: We deliberately do not delete the '_beanst_optimized' post meta.
// This preserves optimization status if the user reinstalls the plugin,
// preventing the need to re-process the entire library.

delete_option('beanst_optimizer_license');

// Delete transients
delete_transient('beanst_license_check');
