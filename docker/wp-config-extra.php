<?php

if (!defined('ABSPATH')) {
    return;
}

$esams_debug = getenv('WP_DEBUG');
if ($esams_debug === '1' || strtolower((string) $esams_debug) === 'true') {
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

$esams_global_api_key = getenv('ESAMS_API_KEY');
if (!defined('ESAMS_API_KEY') && is_string($esams_global_api_key) && trim($esams_global_api_key) !== '') {
    define('ESAMS_API_KEY', trim($esams_global_api_key));
}

$esams_source_id = getenv('ESAMS_SOURCE_ID');
$esams_source_specific_key = '';
if (is_string($esams_source_id) && trim($esams_source_id) !== '') {
    $esams_sanitized_source_id = strtoupper(str_replace('-', '_', preg_replace('/[^a-zA-Z0-9_-]/', '', trim($esams_source_id))));
    $esams_env_name = 'ESAMS_API_KEY_' . $esams_sanitized_source_id;
    $esams_source_specific_key = getenv($esams_env_name);

    if (is_string($esams_source_specific_key) && trim($esams_source_specific_key) !== '') {
        $esams_const_name = 'ESAMS_API_KEY_' . $esams_sanitized_source_id;
        if (!defined($esams_const_name)) {
            define($esams_const_name, trim($esams_source_specific_key));
        }
    }
}
