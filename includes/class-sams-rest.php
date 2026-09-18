<?php

if (!defined('ABSPATH')) {
    exit;
}

class ESAMS_Rest {
    /** @var ESAMS_Source_Repository */
    private $sources;

    /** @var ESAMS_Api_Client */
    private $api;

    public function __construct($sources, $api) {
        $this->sources = $sources;
        $this->api = $api;
    }

    public function register() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('esams/v1', '/sources', array(
            'methods' => 'GET',
            'permission_callback' => array($this, 'can_edit_posts'),
            'callback' => array($this, 'get_sources'),
        ));

        register_rest_route('esams/v1', '/seasons', array(
            'methods' => 'GET',
            'permission_callback' => array($this, 'can_edit_posts'),
            'callback' => array($this, 'get_seasons'),
            'args' => array('source' => array('required' => true)),
        ));

        register_rest_route('esams/v1', '/leagues', array(
            'methods' => 'GET',
            'permission_callback' => array($this, 'can_edit_posts'),
            'callback' => array($this, 'get_leagues'),
            'args' => array(
                'source' => array('required' => true),
                'season' => array('required' => true),
                'association' => array('required' => false),
            ),
        ));

        register_rest_route('esams/v1', '/associations', array(
            'methods' => 'GET',
            'permission_callback' => array($this, 'can_edit_posts'),
            'callback' => array($this, 'get_associations'),
            'args' => array('source' => array('required' => true)),
        ));

        register_rest_route('esams/v1', '/teams', array(
            'methods' => 'GET',
            'permission_callback' => array($this, 'can_edit_posts'),
            'callback' => array($this, 'get_teams'),
            'args' => array(
                'source' => array('required' => true),
                'league' => array('required' => true),
            ),
        ));
    }

    public function can_edit_posts() {
        return current_user_can('edit_posts');
    }

    public function get_sources() {
        $active = $this->sources->get_active();
        $default = $this->sources->get_default_id();

        $list = array_map(function ($source) {
            return array(
                'id' => $source['id'],
                'name' => $source['name'],
                'association_uuid' => $source['association_uuid'],
                'is_default' => !empty($source['is_default']),
            );
        }, $active);

        $response = rest_ensure_response(array(
            'default_source' => $default,
            'items' => array_values($list),
        ));
        $this->add_cache_headers($response, 3600);
        return $response;
    }

    public function get_seasons($request) {
        $source = sanitize_key($request['source']);
        $response = $this->normalize_collection($this->api->get_seasons($source), 'content', array('uuid', 'name', 'currentSeason'));
        $this->add_cache_headers($response, 86400);
        return $response;
    }

    public function get_leagues($request) {
        $source_id = sanitize_key($request['source']);
        $season = sanitize_text_field($request['season']);
        $source = $this->sources->get_by_id($source_id);

        if (!empty($request['association'])) {
            $association = sanitize_text_field($request['association']);
        } else {
            $association = $source && !empty($source['association_uuid']) ? $source['association_uuid'] : '';
        }

        $response = $this->normalize_collection(
            $this->api->get_leagues($source_id, $season, $association),
            'content',
            array('uuid', 'name', 'shortName', 'seasonUuid', 'associationUuid')
        );
        $this->add_cache_headers($response, 86400);
        return $response;
    }

    public function get_associations($request) {
        $source_id = sanitize_key($request['source']);
        $source = $this->sources->get_by_id($source_id);
        $parent = $source && !empty($source['association_uuid']) ? $source['association_uuid'] : '';

        $response = $this->normalize_collection(
            $this->api->get_associations($source_id, $parent),
            'content',
            array('uuid', 'name', 'shortname', 'level')
        );
        $this->add_cache_headers($response, 86400);
        return $response;
    }

    public function get_teams($request) {
        $source = sanitize_key($request['source']);
        $league = sanitize_text_field($request['league']);

        $response = $this->normalize_collection(
            $this->api->get_teams($source, $league),
            'content',
            array('uuid', 'name', 'shortName', 'teamNumber', 'associationUuid')
        );
        $this->add_cache_headers($response, 86400);
        return $response;
    }

    private function normalize_collection($result, $content_key, $fields) {
        if (empty($result['ok'])) {
            return new WP_REST_Response($result, 400);
        }

        $raw = $this->extract_collection($result['data'], $content_key);

        $items = array();
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $item = array();
            foreach ($fields as $field) {
                $item[$field] = isset($entry[$field]) ? $entry[$field] : '';
            }
            $items[] = $item;
        }

        return rest_ensure_response(array(
            'ok' => true,
            'meta' => $result['meta'],
            'items' => $items,
        ));
    }

    private function extract_collection($data, $content_key) {
        if (!is_array($data)) {
            return array();
        }

        if (isset($data[$content_key]) && is_array($data[$content_key])) {
            return $data[$content_key];
        }

        if (isset($data['_embedded']) && is_array($data['_embedded'])) {
            foreach ($data['_embedded'] as $embedded_list) {
                if ($this->is_list_of_arrays($embedded_list)) {
                    return $embedded_list;
                }
            }
        }

        if ($this->is_list_of_arrays($data)) {
            return $data;
        }

        return array();
    }

    private function is_list_of_arrays($value) {
        if (!is_array($value) || empty($value)) {
            return false;
        }

        $index = 0;
        foreach ($value as $key => $item) {
            if ($key !== $index || !is_array($item)) {
                return false;
            }
            $index++;
        }

        return true;
    }

    private function add_cache_headers($response, $ttl_seconds) {
        if (!is_a($response, 'WP_REST_Response')) {
            return;
        }
        
        // Set cache headers for browser and intermediary caches
        $response->header('Cache-Control', "public, max-age={$ttl_seconds}");
        $response->header('X-Cache-TTL', $ttl_seconds);
        
        // Set ETag for conditional requests
        $data_hash = md5(wp_json_encode($response->get_data()));
        $response->header('ETag', '"' . $data_hash . '"');
    }
}
