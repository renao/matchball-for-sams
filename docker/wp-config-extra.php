<?php

if (!defined('ABSPATH')) {
    return;
}

$matchball_debug = getenv('WP_DEBUG');
if ($matchball_debug === '1' || strtolower((string) $matchball_debug) === 'true') {
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

$matchball_global_api_key = getenv('MATCHBALL_API_KEY');
if (!defined('MATCHBALL_API_KEY') && is_string($matchball_global_api_key) && trim($matchball_global_api_key) !== '') {
    define('MATCHBALL_API_KEY', trim($matchball_global_api_key));
}

$matchball_source_id = getenv('MATCHBALL_SOURCE_ID');
$matchball_source_specific_key = '';
if (is_string($matchball_source_id) && trim($matchball_source_id) !== '') {
    $matchball_sanitized_source_id = strtoupper(str_replace('-', '_', preg_replace('/[^a-zA-Z0-9_-]/', '', trim($matchball_source_id))));
    $matchball_env_name = 'MATCHBALL_API_KEY_' . $matchball_sanitized_source_id;
    $matchball_source_specific_key = getenv($matchball_env_name);

    if (is_string($matchball_source_specific_key) && trim($matchball_source_specific_key) !== '') {
        $matchball_const_name = 'MATCHBALL_API_KEY_' . $matchball_sanitized_source_id;
        if (!defined($matchball_const_name)) {
            define($matchball_const_name, trim($matchball_source_specific_key));
        }
    }
}
