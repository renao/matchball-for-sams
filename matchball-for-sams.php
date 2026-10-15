<?php
/**
 * Plugin Name: Matchball - Standings & Tables for SAMS
 * Plugin URI: https://github.com/renao/matchball-for-sams
 * Description: Gutenberg blocks for standings and results via SAMS API.
 * Version: 3.0.0
 * Author: Matchball SAMS
 * Author URI: https://github.com/renao
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: matchball-for-sams
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MATCHBALL_PLUGIN_VERSION', '3.0.0');
define('MATCHBALL_PLUGIN_FILE', __FILE__);
define('MATCHBALL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MATCHBALL_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-source-repository.php';
require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-api-client.php';
require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-admin.php';
require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-rest.php';
require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-blocks.php';
require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-cron.php';
require_once MATCHBALL_PLUGIN_DIR . 'includes/class-matchball-plugin.php';

function matchball_bootstrap() {
    load_plugin_textdomain('matchball-for-sams', false, dirname(plugin_basename(MATCHBALL_PLUGIN_FILE)) . '/languages');
    $plugin = new MATCHBALL_Plugin();
    $plugin->init();
}

function matchball_activate() {
    MATCHBALL_Cron::activate();
}

function matchball_deactivate() {
    MATCHBALL_Cron::deactivate();
}

register_activation_hook(MATCHBALL_PLUGIN_FILE, 'matchball_activate');
register_deactivation_hook(MATCHBALL_PLUGIN_FILE, 'matchball_deactivate');

add_action('plugins_loaded', 'matchball_bootstrap', 20);
