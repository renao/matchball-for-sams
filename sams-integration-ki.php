<?php
/**
 * Plugin Name: SAMS Integration KI
 * Description: Gutenberg blocks for standings, results, and schedules via SAMS API.
 * Version: 0.1.0
 * Author: SAMS Integration Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SIK_PLUGIN_VERSION', '0.1.0');
define('SIK_PLUGIN_FILE', __FILE__);
define('SIK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIK_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SIK_PLUGIN_DIR . 'includes/class-sams-source-repository.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-api-client.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-admin.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-rest.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-blocks.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-plugin.php';

function sik_bootstrap() {
    $plugin = new SIK_Plugin();
    $plugin->init();
}

add_action('plugins_loaded', 'sik_bootstrap');
