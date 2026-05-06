<?php
/**
 * Plugin Name: SAMS Integration
 * Plugin URI: https://codeberg.org/renao/sams-integration
 * Description: Gutenberg blocks for standings and results via SAMS API.
 * Version: 3.0.0
 * Author: SAMS Integration Team
 * Author URI: https://codeberg.org/renao
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sams-integration
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SIK_PLUGIN_VERSION', '3.0.0');
define('SIK_PLUGIN_FILE', __FILE__);
define('SIK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIK_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SIK_PLUGIN_DIR . 'includes/class-sams-source-repository.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-api-client.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-admin.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-rest.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-blocks.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-cron.php';
require_once SIK_PLUGIN_DIR . 'includes/class-sams-plugin.php';

function sik_bootstrap() {
    $plugin = new SIK_Plugin();
    $plugin->init();
}

function sik_load_textdomain() {
    load_plugin_textdomain('sams-integration', false, dirname(plugin_basename(SIK_PLUGIN_FILE)) . '/languages');
}

function sik_activate() {
    SIK_Cron::activate();
}

function sik_deactivate() {
    SIK_Cron::deactivate();
}

register_activation_hook(SIK_PLUGIN_FILE, 'sik_activate');
register_deactivation_hook(SIK_PLUGIN_FILE, 'sik_deactivate');

add_action('plugins_loaded', 'sik_load_textdomain');
add_action('plugins_loaded', 'sik_bootstrap', 20);
