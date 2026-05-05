<?php

if (!defined('ABSPATH')) {
    exit;
}

class SIK_Blocks {
    /** @var SIK_Source_Repository */
    private $sources;

    /** @var SIK_Api_Client */
    private $api;

    public function __construct($sources, $api) {
        $this->sources = $sources;
        $this->api = $api;
    }

    public function register() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    public function register_blocks() {
        register_block_type('sik/standings', array(
            'api_version' => 2,
            'render_callback' => array($this, 'render_standings'),
            'attributes' => $this->base_attributes(),
        ));

        register_block_type('sik/results', array(
            'api_version' => 2,
            'render_callback' => array($this, 'render_results'),
            'attributes' => array_merge($this->base_attributes(), array(
                'includeFuture' => array('type' => 'boolean', 'default' => false),
            )),
        ));

        register_block_type('sik/schedules', array(
            'api_version' => 2,
            'render_callback' => array($this, 'render_schedules'),
            'attributes' => array_merge($this->base_attributes(), array(
                'includePast' => array('type' => 'boolean', 'default' => false),
            )),
        ));
    }

    public function enqueue_editor_assets() {
        $script_path = SIK_PLUGIN_DIR . 'assets/js/blocks.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : SIK_PLUGIN_VERSION;

        // Enqueue editor styles for SAMS blocks
        $style_path = SIK_PLUGIN_DIR . 'assets/css/editor.css';
        $style_version = file_exists($style_path) ? filemtime($style_path) : SIK_PLUGIN_VERSION;
        wp_enqueue_style('sik-editor', SIK_PLUGIN_URL . 'assets/css/editor.css', array(), $style_version);

        wp_enqueue_script(
            'sik-blocks',
            SIK_PLUGIN_URL . 'assets/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor', 'wp-api-fetch'),
            $script_version,
            true
        );

        wp_localize_script('sik-blocks', 'sikConfig', array(
            'root' => esc_url_raw(rest_url('sik/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function enqueue_frontend_assets() {
        $style_path = SIK_PLUGIN_DIR . 'assets/css/frontend.css';
        $style_version = file_exists($style_path) ? filemtime($style_path) : SIK_PLUGIN_VERSION;

        wp_enqueue_style(
            'sik-frontend',
            SIK_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $style_version
        );
    }

    private function base_attributes() {
        return array(
            'sourceId' => array('type' => 'string', 'default' => ''),
            'seasonUuid' => array('type' => 'string', 'default' => ''),
            'leagueUuid' => array('type' => 'string', 'default' => ''),
            'teamUuid' => array('type' => 'string', 'default' => ''),
        );
    }

    public function render_standings($attributes) {
        $source_id = $this->resolve_source_id($attributes);
        $league_uuid = isset($attributes['leagueUuid']) ? sanitize_text_field($attributes['leagueUuid']) : '';
        $team_uuid = isset($attributes['teamUuid']) ? sanitize_text_field($attributes['teamUuid']) : '';

        if ($source_id === '' || $league_uuid === '') {
            return $this->notice('Please configure source, season, and league.');
        }

        $result = $this->api->get_rankings($source_id, $league_uuid);
        if (empty($result['ok'])) {
            return $this->notice('Standings unavailable.');
        }

        $rows = isset($result['data']['content']) && is_array($result['data']['content']) ? $result['data']['content'] : array();
        if (empty($rows)) {
            return $this->notice('No standings data available for this selection.');
        }

        ob_start();
        echo '<div class="sik-block sik-standings">';
        echo '<div class="sik-table-scroll">';
        echo '<table class="sik-table sik-standings__table">';
        echo '<caption class="screen-reader-text">Standings</caption>';
        echo '<thead><tr><th scope="col">Rank</th><th scope="col">Team</th><th scope="col">Played</th><th scope="col">Points</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $rank = isset($row['rank']) ? $row['rank'] : '';
            $team_name = isset($row['teamName']) ? $row['teamName'] : '';
            $matches_played = isset($row['matchesPlayed']) ? $row['matchesPlayed'] : '';
            $points = isset($row['points']) ? $row['points'] : '';
            $row_team_uuid = isset($row['uuid']) ? (string) $row['uuid'] : '';
            $is_selected_team = $team_uuid !== '' && $row_team_uuid === $team_uuid;

            echo '<tr' . ($is_selected_team ? ' class="is-selected-team"' : '') . '>';
            echo '<td class="sik-standings__rank">' . esc_html($rank) . '</td>';
            echo '<td class="sik-standings__team">' . esc_html($team_name);
            if ($is_selected_team) {
                echo ' <span class="screen-reader-text">(selected team)</span>';
            }
            echo '</td>';
            echo '<td class="sik-standings__played">' . esc_html($matches_played) . '</td>';
            echo '<td class="sik-standings__points">' . esc_html($points) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
        return ob_get_clean();
    }

    public function render_results($attributes) {
        return $this->render_matches($attributes, false);
    }

    public function render_schedules($attributes) {
        return $this->render_matches($attributes, true);
    }

    private function render_matches($attributes, $for_schedule) {
        $source_id = $this->resolve_source_id($attributes);
        $season_uuid = isset($attributes['seasonUuid']) ? sanitize_text_field($attributes['seasonUuid']) : '';
        $league_uuid = isset($attributes['leagueUuid']) ? sanitize_text_field($attributes['leagueUuid']) : '';
        $team_uuid = isset($attributes['teamUuid']) ? sanitize_text_field($attributes['teamUuid']) : '';

        if ($source_id === '' || $season_uuid === '' || $league_uuid === '') {
            return $this->notice('Please configure source, season, and league.');
        }

        $source = $this->sources->get_by_id($source_id);
        $filters = array(
            'for-season' => $season_uuid,
            'for-league' => $league_uuid,
        );
        if ($team_uuid !== '') {
            $filters['for-team'] = $team_uuid;
        }
        if ($source && !empty($source['association_uuid'])) {
            $filters['association'] = $source['association_uuid'];
        }

        $result = $this->api->get_matches($source_id, $filters);
        if (empty($result['ok'])) {
            return $this->notice($for_schedule ? 'Schedule unavailable.' : 'Results unavailable.');
        }

        $rows = isset($result['data']['content']) && is_array($result['data']['content']) ? $result['data']['content'] : array();
        if (empty($rows)) {
            return $this->notice($for_schedule
                ? 'No upcoming matches available for this selection.'
                : 'No results available for this selection.');
        }

        $now = current_time('timestamp', true);
        $include_future = !empty($attributes['includeFuture']);
        $include_past = !empty($attributes['includePast']);

        $filtered = array_values(array_filter($rows, function ($row) use ($for_schedule, $include_future, $include_past, $now) {
            $timestamp = isset($row['date']) ? strtotime($row['date']) : false;
            if ($timestamp === false) {
                return false;
            }
            if ($for_schedule) {
                if ($include_past) {
                    return true;
                }
                return $timestamp >= $now;
            }

            if ($include_future) {
                return true;
            }
            return $timestamp < $now;
        }));

        usort($filtered, function ($a, $b) {
            $ta = isset($a['date']) ? strtotime($a['date']) : 0;
            $tb = isset($b['date']) ? strtotime($b['date']) : 0;
            return $ta <=> $tb;
        });

        if (!$for_schedule && !$include_future) {
            $filtered = array_reverse($filtered);
        }

        $block_class = $for_schedule ? 'sik-block sik-schedules' : 'sik-block sik-results';

        ob_start();
        echo '<div class="' . esc_attr($block_class) . '">';
        echo '<ul class="sik-match-list">';
        foreach ($filtered as $row) {
            $timestamp = isset($row['date']) ? strtotime($row['date']) : false;
            $date_label = $timestamp ? esc_html(wp_date(get_option('date_format'), $timestamp)) : '';
            $time_label = isset($row['time']) && $row['time'] !== '' ? esc_html($row['time']) . ' Uhr' : ($timestamp ? esc_html(wp_date(get_option('time_format'), $timestamp)) . ' Uhr' : '');
            $home = isset($row['team1Description']) ? esc_html($row['team1Description']) : '';
            $away = isset($row['team2Description']) ? esc_html($row['team2Description']) : '';

            $set_points  = isset($row['results']['setPoints'])  && $row['results']['setPoints']  !== '' ? esc_html($row['results']['setPoints'])  : '';
            $ball_points = isset($row['results']['ballPoints']) && $row['results']['ballPoints'] !== '' ? esc_html($row['results']['ballPoints']) : '';
            $has_result  = $set_points !== '' || $ball_points !== '';
            $venue_parts = array();
            if (isset($row['location']['name']) && $row['location']['name'] !== '') {
                $venue_parts[] = esc_html($row['location']['name']);
            }
            $street = isset($row['location']['address']['street']) && $row['location']['address']['street'] !== '' ? esc_html($row['location']['address']['street']) : '';
            $city   = isset($row['location']['address']['city'])   && $row['location']['address']['city']   !== '' ? esc_html($row['location']['address']['city'])   : '';
            if ($street !== '' || $city !== '') {
                $venue_parts[] = implode(', ', array_filter(array($street, $city)));
            }
            $venue = implode(' · ', $venue_parts);

            $meta_parts = array_filter(array($date_label, $time_label, $venue));

            echo '<li class="sik-match-card' . ($for_schedule && !$has_result ? ' sik-match-card--upcoming' : '') . '">';
            echo '<div class="sik-match-card__matchup">';
            echo '<span class="sik-match-card__team sik-match-card__team--home">' . $home . '</span>';
            if ($has_result) {
                $score = $set_points !== '' ? $set_points : $ball_points;
                echo '<span class="sik-match-card__score">' . $score . '</span>';
            } else {
                echo '<span class="sik-match-card__vs">vs</span>';
            }
            echo '<span class="sik-match-card__team sik-match-card__team--away">' . $away . '</span>';
            echo '</div>';
            if (!empty($meta_parts)) {
                echo '<div class="sik-match-card__meta">' . implode('<span class="sik-match-card__sep" aria-hidden="true"></span>', $meta_parts) . '</div>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
        return ob_get_clean();
    }

    private function resolve_source_id($attributes) {
        $source_id = isset($attributes['sourceId']) ? sanitize_key($attributes['sourceId']) : '';
        if ($source_id !== '') {
            return $source_id;
        }
        return $this->sources->get_default_id();
    }

    private function notice($message) {
        return '<div class="sik-notice">' . esc_html($message) . '</div>';
    }
}
