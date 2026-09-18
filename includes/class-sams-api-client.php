<?php

if (!defined('ABSPATH')) {
    exit;
}

class ESAMS_Api_Client {
    private const FRESH_TTL = 300;       // 5 minutes target freshness for MVP
    private const STALE_TTL = 86400;     // 24 hours stale tolerance
    private const HTTP_TIMEOUT = 5;
    private const MAX_PAGES = 200;

    /** @var ESAMS_Source_Repository */
    private $sources;

    public function __construct($sources) {
        $this->sources = $sources;
    }

    public function get_seasons($source_id) {
        return $this->request($source_id, '/seasons');
    }

    public function get_associations($source_id, $parent_uuid = '') {
        $query = array('size' => 200);
        if ($parent_uuid !== '') {
            $query['association'] = $parent_uuid;
        }
        return $this->request($source_id, '/associations', $query);
    }

    public function get_leagues($source_id, $season_uuid, $association_uuid = '') {
        $query = array(
            'season' => $season_uuid,
            'size' => 200,
        );
        if ($association_uuid !== '') {
            $query['association'] = $association_uuid;
        }
        return $this->request($source_id, '/leagues', $query);
    }

    public function get_teams($source_id, $league_uuid) {
        return $this->request(
            $source_id,
            '/leagues/' . rawurlencode($league_uuid) . '/teams',
            array('size' => 300)
        );
    }

    public function get_rankings($source_id, $league_uuid) {
        return $this->request(
            $source_id,
            '/leagues/' . rawurlencode($league_uuid) . '/rankings',
            array('size' => 300)
        );
    }

    public function get_matches($source_id, $filters) {
        if (!is_array($filters)) {
            $filters = array();
        }
        if (!isset($filters['size'])) {
            $filters['size'] = 500;
        }
        return $this->request($source_id, '/league-matches', $filters);
    }

    public function request($source_id, $path, $query = array()) {
        $source = $this->sources->get_by_id($source_id);
        if (!$source || empty($source['is_active'])) {
            return $this->error('inactive_source', __('Selected source is not active.', 'easy-sams'));
        }

        if (empty($source['base_url']) || empty($source['api_key'])) {
            return $this->error('invalid_source_config', __('Source configuration is incomplete.', 'easy-sams'));
        }

        $path = '/' . ltrim($path, '/');
        $query = $this->sanitize_query($query);

        $cache_key = $this->cache_key($source_id, $path, $query);
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['fetched_at'], $cached['data'])) {
            $age = time() - (int) $cached['fetched_at'];
            if ($age <= self::FRESH_TTL) {
                return $this->ok($cached['data'], true, false, $age);
            }
        }

        $url = $source['base_url'] . $path;
        if (!empty($query)) {
            $url = add_query_arg($query, $url);
        }

        $response = $this->perform_get($url, $source['api_key']);

        if (is_wp_error($response)) {
            return $this->fallback_or_error($cache_key, 'network_error', $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status_code >= 200 && $status_code < 300 && is_array($decoded)) {
            $decoded = $this->fetch_all_pages($decoded, $url, $source['api_key']);

            $payload = array(
                'fetched_at' => time(),
                'data' => $decoded,
            );
            set_transient($cache_key, $payload, self::STALE_TTL);
            return $this->ok($decoded, false, false, 0);
        }

        $this->log_http_error($status_code, $path);
        return $this->fallback_or_error($cache_key, 'http_' . $status_code, __('Remote API error.', 'easy-sams'));
    }

    private function fallback_or_error($cache_key, $code, $message) {
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['fetched_at'], $cached['data'])) {
            $age = time() - (int) $cached['fetched_at'];
            if ($age <= self::STALE_TTL) {
                return $this->ok($cached['data'], true, true, $age, $code);
            }
        }

        return $this->error($code, $message);
    }

    private function sanitize_query($query) {
        $clean = array();
        if (!is_array($query)) {
            return $clean;
        }

        foreach ($query as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $clean[sanitize_key($key)] = sanitize_text_field((string) $value);
        }
        return $clean;
    }

    private function perform_get($url, $api_key) {
        return wp_remote_get($url, array(
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => array(
                'X-Api-Key' => $api_key,
                'Accept' => 'application/hal+json',
            ),
        ));
    }

    private function fetch_all_pages($first_page_data, $initial_url, $api_key) {
        $combined = $first_page_data;
        $current_url = $initial_url;
        $next_url = $this->next_page_url($combined, $current_url);
        $page_count = 1;

        while ($next_url !== '' && $page_count < self::MAX_PAGES) {
            $response = $this->perform_get($next_url, $api_key);
            if (is_wp_error($response)) {
                break;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                break;
            }

            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                break;
            }

            $combined = $this->merge_page_data($combined, $decoded);
            $current_url = $next_url;
            $next_url = $this->next_page_url($decoded, $current_url);
            $page_count++;
        }

        if (is_array($combined) && isset($combined['content']) && is_array($combined['content'])) {
            $combined['numberOfElements'] = count($combined['content']);
            if (isset($combined['_links']['next'])) {
                unset($combined['_links']['next']);
            }
            $combined['last'] = true;
        }

        return $combined;
    }

    private function next_page_url($data, $current_url) {
        $total_pages = 0;
        if (isset($data['totalPages'])) {
            $total_pages = (int) $data['totalPages'];
        } elseif (isset($data['page']['totalPages'])) {
            $total_pages = (int) $data['page']['totalPages'];
        }

        if ($total_pages <= 1) {
            return '';
        }

        $current_page = 0;
        if (isset($data['number'])) {
            $current_page = (int) $data['number'];
        } elseif (isset($data['page']['number'])) {
            $current_page = (int) $data['page']['number'];
        } else {
            $query = wp_parse_url($current_url, PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                if (isset($params['page'])) {
                    $current_page = (int) $params['page'];
                }
            }
        }

        $next_page = $current_page + 1;
        if ($next_page >= $total_pages) {
            if (isset($data['_links']['next']['href']) && is_string($data['_links']['next']['href']) && $data['_links']['next']['href'] !== '') {
                return $this->absolute_url($data['_links']['next']['href'], $current_url);
            }
            return '';
        }

        return add_query_arg('page', (string) $next_page, $current_url);
    }

    private function merge_page_data($base, $next) {
        if (!is_array($base) || !is_array($next)) {
            return $base;
        }

        if (isset($base['content']) && is_array($base['content']) && isset($next['content']) && is_array($next['content'])) {
            $base['content'] = array_merge($base['content'], $next['content']);
            if (isset($next['totalElements'])) {
                $base['totalElements'] = $next['totalElements'];
            }
            if (isset($next['totalPages'])) {
                $base['totalPages'] = $next['totalPages'];
            }
            if (isset($next['last'])) {
                $base['last'] = $next['last'];
            }
            return $base;
        }

        if (isset($base['_embedded']) && is_array($base['_embedded']) && isset($next['_embedded']) && is_array($next['_embedded'])) {
            foreach ($next['_embedded'] as $key => $value) {
                if (!isset($base['_embedded'][$key]) || !is_array($base['_embedded'][$key])) {
                    $base['_embedded'][$key] = $value;
                    continue;
                }
                if (is_array($value)) {
                    $base['_embedded'][$key] = array_merge($base['_embedded'][$key], $value);
                }
            }
            return $base;
        }

        if ($this->is_list_array($base) && $this->is_list_array($next)) {
            return array_merge($base, $next);
        }

        return $base;
    }

    private function is_list_array($value) {
        if (!is_array($value)) {
            return false;
        }
        if ($value === array()) {
            return true;
        }
        return array_keys($value) === range(0, count($value) - 1);
    }

    private function absolute_url($href, $base_url) {
        if (strpos($href, 'http://') === 0 || strpos($href, 'https://') === 0) {
            return $href;
        }

        if (strpos($href, '/') === 0) {
            $parts = wp_parse_url($base_url);
            if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
                return $href;
            }
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            return $parts['scheme'] . '://' . $parts['host'] . $port . $href;
        }

        return $href;
    }

    private function cache_key($source_id, $path, $query) {
        $material = wp_json_encode(array($source_id, $path, $query));
        return 'esams_' . md5($material);
    }

    private function ok($data, $from_cache, $stale, $age, $fallback_code = '') {
        return array(
            'ok' => true,
            'data' => $data,
            'meta' => array(
                'from_cache' => $from_cache,
                'stale' => $stale,
                'age' => $age,
                'fallback_code' => $fallback_code,
            ),
        );
    }

    private function error($code, $message) {
        return array(
            'ok' => false,
            'error' => array(
                'code' => $code,
                'message' => $message,
            ),
        );
    }

    private function log_http_error($status_code, $path) {
        error_log(sprintf('[ESAMS] API error status=%d path=%s', (int) $status_code, $path));
    }
}
