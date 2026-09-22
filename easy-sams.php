<?php
/**
 * Plugin Name: Easy SAMS
 * Plugin URI: https://github.com/renao/easy-sams
 * Description: Gutenberg blocks for standings and results via SAMS API.
 * Version: 3.0.0
 * Author: Easy SAMS Team
 * Author URI: https://github.com/renao
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-sams
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ESAMS_PLUGIN_VERSION', '3.0.0');
define('ESAMS_PLUGIN_FILE', __FILE__);
define('ESAMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ESAMS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-source-repository.php';
require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-api-client.php';
require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-admin.php';
require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-rest.php';
require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-blocks.php';
require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-cron.php';
require_once ESAMS_PLUGIN_DIR . 'includes/class-sams-plugin.php';

function esams_bootstrap() {
    $plugin = new ESAMS_Plugin();
    $plugin->init();
}

function esams_activate() {
    ESAMS_Cron::activate();
}

function esams_deactivate() {
    ESAMS_Cron::deactivate();
}

register_activation_hook(ESAMS_PLUGIN_FILE, 'esams_activate');
register_deactivation_hook(ESAMS_PLUGIN_FILE, 'esams_deactivate');

add_action('plugins_loaded', 'esams_bootstrap', 20);
