<?php

if (!defined('ABSPATH')) {
    exit;
}

class MATCHBALL_Source_Repository {
    private const OPTION_KEY = 'matchball_sources';
    private const API_KEY_PREFIX = 'matchballenc:v1';

    public function get_all() {
        $sources = get_option(self::OPTION_KEY, array());
        if (!is_array($sources)) {
            return array();
        }

        $sources = $this->migrate_encrypted_sources($sources);

        $normalized = array();
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }

            $item = $this->normalize_source($source, false);
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
            $item = $this->normalize_source($source, true);
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

    private function normalize_source($source, $for_storage = false) {
        $id = isset($source['id']) ? sanitize_key($source['id']) : '';
        $name = isset($source['name']) ? sanitize_text_field($source['name']) : '';
        $base_url = isset($source['base_url']) ? esc_url_raw($source['base_url']) : '';
        $stored_api_key = isset($source['api_key']) ? trim((string) $source['api_key']) : '';
        $db_api_key = $for_storage
            ? $this->encrypt_api_key($stored_api_key)
            : $this->decrypt_api_key($stored_api_key);
        $association_uuid = isset($source['association_uuid']) ? sanitize_text_field($source['association_uuid']) : '';
        $is_active = !empty($source['is_active']);
        $is_default = !empty($source['is_default']);

        if ($id === '' && $name !== '') {
            $id = sanitize_key($name);
        }

        $api_key = $for_storage ? $db_api_key : $this->resolve_api_key($id, $db_api_key);

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

    private function migrate_encrypted_sources($sources) {
        $changed = false;
        $migrated = array();

        foreach ($sources as $source) {
            if (!is_array($source)) {
                $migrated[] = $source;
                continue;
            }

            $item = $source;
            $stored_api_key = isset($item['api_key']) ? trim((string) $item['api_key']) : '';
            if ($stored_api_key !== '' && !$this->is_api_key_encrypted($stored_api_key)) {
                $encrypted = $this->encrypt_api_key($stored_api_key);
                if ($encrypted !== $stored_api_key) {
                    $item['api_key'] = $encrypted;
                    $changed = true;
                }
            }

            $migrated[] = $item;
        }

        if ($changed) {
            update_option(self::OPTION_KEY, $migrated, false);
        }

        return $migrated;
    }

    private function is_api_key_encrypted($value) {
        return strpos((string) $value, self::API_KEY_PREFIX . ':') === 0;
    }

    private function encrypt_api_key($plaintext) {
        $plaintext = sanitize_text_field(trim((string) $plaintext));
        if ($plaintext === '' || $this->is_api_key_encrypted($plaintext)) {
            return $plaintext;
        }

        $key = $this->crypto_key();
        if ($key === '') {
            return $plaintext;
        }

        if (function_exists('sodium_crypto_secretbox') && defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES')) {
            try {
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            } catch (Exception $e) {
                return $plaintext;
            }
            $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);
            return self::API_KEY_PREFIX . ':sb:' . $this->b64url_encode($nonce) . ':' . $this->b64url_encode($ciphertext);
        }

        if (function_exists('openssl_encrypt')) {
            try {
                $iv = random_bytes(12);
            } catch (Exception $e) {
                return $plaintext;
            }
            $tag = '';
            $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
            if ($ciphertext !== false && $tag !== '') {
                return self::API_KEY_PREFIX . ':og:' . $this->b64url_encode($iv) . ':' . $this->b64url_encode($tag) . ':' . $this->b64url_encode($ciphertext);
            }
        }

        return $plaintext;
    }

    private function decrypt_api_key($stored_value) {
        $stored_value = trim((string) $stored_value);
        if ($stored_value === '') {
            return '';
        }

        if (!$this->is_api_key_encrypted($stored_value)) {
            return sanitize_text_field($stored_value);
        }

        $parts = explode(':', $stored_value);
        if (count($parts) < 5) {
            return '';
        }

        $variant = $parts[2];
        $key = $this->crypto_key();
        if ($key === '') {
            return '';
        }

        if ($variant === 'sb' && count($parts) === 5 && function_exists('sodium_crypto_secretbox_open')) {
            $nonce = $this->b64url_decode($parts[3]);
            $ciphertext = $this->b64url_decode($parts[4]);
            if ($nonce === '' || $ciphertext === '') {
                return '';
            }

            $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
            if ($plaintext === false) {
                return '';
            }

            return sanitize_text_field($plaintext);
        }

        if ($variant === 'og' && count($parts) === 6 && function_exists('openssl_decrypt')) {
            $iv = $this->b64url_decode($parts[3]);
            $tag = $this->b64url_decode($parts[4]);
            $ciphertext = $this->b64url_decode($parts[5]);
            if ($iv === '' || $tag === '' || $ciphertext === '') {
                return '';
            }

            $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '');
            if ($plaintext === false) {
                return '';
            }

            return sanitize_text_field($plaintext);
        }

        return '';
    }

    private function crypto_key() {
        $material = '';

        $env_key = getenv('MATCHBALL_ENCRYPTION_KEY');
        if (is_string($env_key) && trim($env_key) !== '') {
            $material = $env_key;
        } elseif (defined('MATCHBALL_ENCRYPTION_KEY') && trim((string) constant('MATCHBALL_ENCRYPTION_KEY')) !== '') {
            $material = (string) constant('MATCHBALL_ENCRYPTION_KEY');
        } elseif (function_exists('wp_salt')) {
            $material = wp_salt('secure_auth') . '|' . wp_salt('auth');
        } elseif (defined('SECURE_AUTH_KEY') || defined('AUTH_KEY')) {
            $material = (defined('SECURE_AUTH_KEY') ? (string) constant('SECURE_AUTH_KEY') : '')
                . '|'
                . (defined('AUTH_KEY') ? (string) constant('AUTH_KEY') : '');
        }

        if (trim($material) === '') {
            return '';
        }

        return hash('sha256', $material, true);
    }

    private function b64url_encode($value) {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function b64url_decode($value) {
        $value = strtr((string) $value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($value, true);
        return $decoded === false ? '' : $decoded;
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

        if (defined('MATCHBALL_API_KEYS') && is_array(constant('MATCHBALL_API_KEYS'))) {
            $map = constant('MATCHBALL_API_KEYS');
            if (isset($map[$source_id]) && trim((string) $map[$source_id]) !== '') {
                return true;
            }
        }

        $env_global = getenv('MATCHBALL_API_KEY');
        if (is_string($env_global) && trim($env_global) !== '') {
            return true;
        }

        if (defined('MATCHBALL_API_KEY') && trim((string) constant('MATCHBALL_API_KEY')) !== '') {
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

            if (defined('MATCHBALL_API_KEYS') && is_array(constant('MATCHBALL_API_KEYS'))) {
                $map = constant('MATCHBALL_API_KEYS');
                if (isset($map[$source_id]) && trim((string) $map[$source_id]) !== '') {
                    return sanitize_text_field((string) $map[$source_id]);
                }
            }
        }

        $env_global = getenv('MATCHBALL_API_KEY');
        if (is_string($env_global) && trim($env_global) !== '') {
            return sanitize_text_field($env_global);
        }

        if (defined('MATCHBALL_API_KEY') && trim((string) constant('MATCHBALL_API_KEY')) !== '') {
            return sanitize_text_field((string) constant('MATCHBALL_API_KEY'));
        }

        return $db_api_key;
    }

    private function specific_env_name($source_id) {
        return 'MATCHBALL_API_KEY_' . strtoupper(str_replace('-', '_', $source_id));
    }

    private function specific_const_name($source_id) {
        return 'MATCHBALL_API_KEY_' . strtoupper(str_replace('-', '_', $source_id));
    }
}
