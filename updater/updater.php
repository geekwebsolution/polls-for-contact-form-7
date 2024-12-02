<?php

if (!defined('ABSPATH')) exit;

/**
 * License manager module
 */
function cf7p_updater_utility() {
    $prefix = 'CF7P_';
    $settings = [
        'prefix' => $prefix,
        'get_base' => CF7P_PLUGIN_BASENAME,
        'get_slug' => CF7P_PLUGIN_DIR,
        'get_version' => CF7P_BUILD,
        'get_api' => 'https://download.geekcodelab.com/',
        'license_update_class' => $prefix . 'Update_Checker'
    ];

    return $settings;
}

function cf7p_updater_activate() {

    // Refresh transients
    delete_site_transient('update_plugins');
    delete_transient('cf7p_plugin_updates');
    delete_transient('cf7p_plugin_auto_updates');
}

require_once(CF7P_PLUGIN_DIR_PATH . 'updater/class-update-checker.php');
