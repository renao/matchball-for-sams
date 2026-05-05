<?php

if (!defined('ABSPATH')) {
    exit;
}

class SIK_Source_Repository {
    private const OPTION_KEY = 'sik_sources';

    public function get_all() {
        $sources = get_option(self::OPTION_KEY, array());
        if (!is_array($sources)) {
            return array();
        }

        $normalized = array();
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }

            $item = $this->normalize_source($source);
            if ($item['id'] === '') {
                continue;
            }
            $normalized[] = $item;
        }

        return $normalized;
    }

    public function get_active() {
        return array_values(array_filter($this->get_all(), function ($source) {
            return !empty($source['is_active']);
        }));
    }

    public function get_by_id($id) {
        foreach ($this->get_all() as $source) {
            if ($source['id'] === $id) {
                return $source;
            }
        }
        return null;
    }

    public function get_default_id() {
        $active = $this->get_active();
        foreach ($active as $source) {
            if (!empty($source['is_default'])) {
                return $source['id'];
            }
        }
        if (!empty($active)) {
            return $active[0]['id'];
        }
        return '';
    }

    public function save_all($sources) {
        $clean = array();
        $default_set = false;

        if (!is_array($sources)) {
            $sources = array();
        }

        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            $item = $this->normalize_source($source);
            if ($item['id'] === '' || $item['name'] === '' || $item['base_url'] === '') {
                continue;
            }

            if (!empty($item['is_default']) && !$default_set) {
                $default_set = true;
            } else {
                $item['is_default'] = false;
            }

            $clean[] = $item;
        }

        if (!$default_set) {
            foreach ($clean as $index => $item) {
                if (!empty($item['is_active'])) {
                    $clean[$index]['is_default'] = true;
                    break;
                }
            }
        }

        update_option(self::OPTION_KEY, $clean, false);
    }

    private function normalize_source($source) {
        $id = isset($source['id']) ? sanitize_key($source['id']) : '';
        $name = isset($source['name']) ? sanitize_text_field($source['name']) : '';
        $base_url = isset($source['base_url']) ? esc_url_raw($source['base_url']) : '';
        $db_api_key = isset($source['api_key']) ? sanitize_text_field($source['api_key']) : '';
        $association_uuid = isset($source['association_uuid']) ? sanitize_text_field($source['association_uuid']) : '';
        $is_active = !empty($source['is_active']);
        $is_default = !empty($source['is_default']);

        if ($id === '' && $name !== '') {
            $id = sanitize_key($name);
        }

        $api_key = $this->resolve_api_key($id, $db_api_key);

        return array(
            'id' => $id,
            'name' => $name,
            'base_url' => untrailingslashit($base_url),
            'api_key' => $api_key,
            'association_uuid' => $association_uuid,
            'is_active' => $is_active,
            'is_default' => $is_default,
        );
    }

    public function uses_external_api_key($source_id) {
        $source_id = sanitize_key($source_id);
        if ($source_id === '') {
            return false;
        }

        $env_specific = getenv($this->specific_env_name($source_id));
        if (is_string($env_specific) && trim($env_specific) !== '') {
            return true;
        }

        $const_specific = $this->specific_const_name($source_id);
        if (defined($const_specific) && trim((string) constant($const_specific)) !== '') {
            return true;
        }

        if (defined('SIK_API_KEYS') && is_array(constant('SIK_API_KEYS'))) {
            $map = constant('SIK_API_KEYS');
            if (isset($map[$source_id]) && trim((string) $map[$source_id]) !== '') {
                return true;
            }
        }

        $env_global = getenv('SIK_API_KEY');
        if (is_string($env_global) && trim($env_global) !== '') {
            return true;
        }

        if (defined('SIK_API_KEY') && trim((string) constant('SIK_API_KEY')) !== '') {
            return true;
        }

        return false;
    }

    private function resolve_api_key($source_id, $db_api_key) {
        $source_id = sanitize_key($source_id);

        if ($source_id !== '') {
            $env_specific = getenv($this->specific_env_name($source_id));
            if (is_string($env_specific) && trim($env_specific) !== '') {
                return sanitize_text_field($env_specific);
            }

            $const_specific = $this->specific_const_name($source_id);
            if (defined($const_specific) && trim((string) constant($const_specific)) !== '') {
                return sanitize_text_field((string) constant($const_specific));
            }

            if (defined('SIK_API_KEYS') && is_array(constant('SIK_API_KEYS'))) {
                $map = constant('SIK_API_KEYS');
                if (isset($map[$source_id]) && trim((string) $map[$source_id]) !== '') {
                    return sanitize_text_field((string) $map[$source_id]);
                }
            }
        }

        $env_global = getenv('SIK_API_KEY');
        if (is_string($env_global) && trim($env_global) !== '') {
            return sanitize_text_field($env_global);
        }

        if (defined('SIK_API_KEY') && trim((string) constant('SIK_API_KEY')) !== '') {
            return sanitize_text_field((string) constant('SIK_API_KEY'));
        }

        return $db_api_key;
    }

    private function specific_env_name($source_id) {
        return 'SIK_API_KEY_' . strtoupper(str_replace('-', '_', $source_id));
    }

    private function specific_const_name($source_id) {
        return 'SIK_API_KEY_' . strtoupper(str_replace('-', '_', $source_id));
    }
}
