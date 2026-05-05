<?php

if (!defined('ABSPATH')) {
    return;
}

$debug = getenv('WP_DEBUG');
if ($debug === '1' || strtolower((string) $debug) === 'true') {
    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }
    if (!defined('WP_DEBUG_LOG')) {
        define('WP_DEBUG_LOG', true);
    }
    if (!defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', false);
    }
}

$global_api_key = getenv('SIK_API_KEY');
if (!defined('SIK_API_KEY') && is_string($global_api_key) && trim($global_api_key) !== '') {
    define('SIK_API_KEY', trim($global_api_key));
}

$source_id = getenv('SIK_SOURCE_ID');
$source_specific_key = '';
if (is_string($source_id) && trim($source_id) !== '') {
    $sanitized_source_id = strtoupper(str_replace('-', '_', preg_replace('/[^a-zA-Z0-9_-]/', '', trim($source_id))));
    $env_name = 'SIK_API_KEY_' . $sanitized_source_id;
    $source_specific_key = getenv($env_name);

    if (is_string($source_specific_key) && trim($source_specific_key) !== '') {
        $const_name = 'SIK_API_KEY_' . $sanitized_source_id;
        if (!defined($const_name)) {
            define($const_name, trim($source_specific_key));
        }
    }
}
