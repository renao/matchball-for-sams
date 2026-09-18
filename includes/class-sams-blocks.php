<?php

if (!defined('ABSPATH')) {
    exit;
}

class ESAMS_Blocks {
    /** @var ESAMS_Source_Repository */
    private $sources;

    /** @var ESAMS_Api_Client */
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
        register_block_type('esams/standings', array(
            'api_version' => 2,
            'render_callback' => array($this, 'render_standings'),
            'attributes' => $this->base_attributes(),
        ));

        register_block_type('esams/results', array(
            'api_version' => 2,
            'render_callback' => array($this, 'render_results'),
            'attributes' => array_merge($this->base_attributes(), array(
                'includeFuture' => array('type' => 'boolean', 'default' => false),
            )),
        ));

    }

    public function enqueue_editor_assets() {
        $script_path = ESAMS_PLUGIN_DIR . 'assets/js/blocks.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : ESAMS_PLUGIN_VERSION;

        // Enqueue editor styles for SAMS blocks
        $style_path = ESAMS_PLUGIN_DIR . 'assets/css/editor.css';
        $style_version = file_exists($style_path) ? filemtime($style_path) : ESAMS_PLUGIN_VERSION;
        wp_enqueue_style('esams-editor', ESAMS_PLUGIN_URL . 'assets/css/editor.css', array(), $style_version);

        wp_enqueue_script(
            'esams-blocks',
            ESAMS_PLUGIN_URL . 'assets/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor', 'wp-api-fetch', 'wp-i18n'),
            $script_version,
            true
        );

        wp_set_script_translations('esams-blocks', 'easy-sams');

        wp_localize_script('esams-blocks', 'esamsConfig', array(
            'root' => esc_url_raw(rest_url('esams/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function enqueue_frontend_assets() {
        $style_path = ESAMS_PLUGIN_DIR . 'assets/css/frontend.css';
        $style_version = file_exists($style_path) ? filemtime($style_path) : ESAMS_PLUGIN_VERSION;

        wp_enqueue_style(
            'esams-frontend',
            ESAMS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $style_version
        );
    }

    private function base_attributes() {
        return array(
            'sourceId' => array('type' => 'string', 'default' => ''),
            'seasonUuid' => array('type' => 'string', 'default' => ''),
            'associationUuid' => array('type' => 'string', 'default' => ''),
            'leagueUuid' => array('type' => 'string', 'default' => ''),
            'teamUuid' => array('type' => 'string', 'default' => ''),
        );
    }

    public function render_standings($attributes) {
        $source_id = $this->resolve_source_id($attributes);
        $league_uuid = isset($attributes['leagueUuid']) ? sanitize_text_field($attributes['leagueUuid']) : '';
        $team_uuid = isset($attributes['teamUuid']) ? sanitize_text_field($attributes['teamUuid']) : '';

        if ($source_id === '' || $league_uuid === '') {
            return $this->notice(__('Please configure source, season, and league.', 'easy-sams'));
        }

        $result = $this->api->get_rankings($source_id, $league_uuid);
        if (empty($result['ok'])) {
            return $this->notice(__('Standings unavailable.', 'easy-sams'));
        }

        $rows = isset($result['data']['content']) && is_array($result['data']['content']) ? $result['data']['content'] : array();
        if (empty($rows)) {
            return $this->notice(__('No standings data available for this selection.', 'easy-sams'));
        }

        ob_start();
        echo '<div class="esams-block esams-standings">';
        echo '<div class="esams-table-scroll">';
        echo '<table class="esams-table esams-standings__table">';
        echo '<caption class="screen-reader-text">' . esc_html__('Standings', 'easy-sams') . '</caption>';
        echo '<thead><tr><th scope="col">' . esc_html__('Rank', 'easy-sams') . '</th><th scope="col">' . esc_html__('Team', 'easy-sams') . '</th><th scope="col">' . esc_html__('Played', 'easy-sams') . '</th><th scope="col">' . esc_html__('Balls', 'easy-sams') . '</th><th scope="col">' . esc_html__('Points', 'easy-sams') . '</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $rank = isset($row['rank']) ? $row['rank'] : '';
            $team_name = isset($row['teamName']) ? $row['teamName'] : '';
            $matches_played = isset($row['matchesPlayed']) ? $row['matchesPlayed'] : '';
            $points = isset($row['points']) ? $row['points'] : '';
            $ball_wins = isset($row['ballWins']) ? $row['ballWins'] : '';
            $ball_losses = isset($row['ballLosses']) ? $row['ballLosses'] : '';
            $ball_difference = isset($row['ballDifference']) ? $row['ballDifference'] : '';
            $balls_display = '';
            if ($ball_wins !== '' || $ball_losses !== '' || $ball_difference !== '') {
                $balls_display = $ball_wins . ':' . $ball_losses;
                if ($ball_difference !== '') {
                    $ball_difference_text = (is_numeric($ball_difference) && (int) $ball_difference > 0)
                        ? '+' . $ball_difference
                        : (string) $ball_difference;
                    $balls_display .= ' (' . $ball_difference_text . ')';
                }
            }
            $row_team_uuid = isset($row['uuid']) ? (string) $row['uuid'] : '';
            $is_selected_team = $team_uuid !== '' && $row_team_uuid === $team_uuid;

            echo '<tr' . ($is_selected_team ? ' class="is-selected-team"' : '') . '>';
            echo '<td class="esams-standings__rank">' . esc_html($rank) . '</td>';
            echo '<td class="esams-standings__team">' . esc_html($team_name);
            if ($is_selected_team) {
                echo ' <span class="screen-reader-text">' . esc_html__('(selected team)', 'easy-sams') . '</span>';
            }
            echo '</td>';
            echo '<td class="esams-standings__played">' . esc_html($matches_played) . '</td>';
            echo '<td class="esams-standings__balls">' . esc_html($balls_display) . '</td>';
            echo '<td class="esams-standings__points">' . esc_html($points) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo $this->updated_hint($result);
        echo '</div>';
        return ob_get_clean();
    }

    public function render_results($attributes) {
        $source_id = $this->resolve_source_id($attributes);
        $season_uuid = isset($attributes['seasonUuid']) ? sanitize_text_field($attributes['seasonUuid']) : '';
        $league_uuid = isset($attributes['leagueUuid']) ? sanitize_text_field($attributes['leagueUuid']) : '';
        $team_uuid = isset($attributes['teamUuid']) ? sanitize_text_field($attributes['teamUuid']) : '';

        if ($source_id === '' || $season_uuid === '' || $league_uuid === '') {
            return $this->notice(__('Please configure source, season, and league.', 'easy-sams'));
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
            return $this->notice(__('Results unavailable.', 'easy-sams'));
        }

        $rows = isset($result['data']['content']) && is_array($result['data']['content']) ? $result['data']['content'] : array();
        if (empty($rows)) {
            return $this->notice(__('No results available for this selection.', 'easy-sams'));
        }

        $now = current_time('timestamp', true);
        $include_future = !empty($attributes['includeFuture']);

        $filtered = array_values(array_filter($rows, function ($row) use ($include_future, $now) {
            $timestamp = isset($row['date']) ? strtotime($row['date']) : false;
            if ($timestamp === false) {
                return false;
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

        if (!$include_future) {
            $filtered = array_reverse($filtered);
        }

        ob_start();
        echo '<div class="esams-block esams-results">';
        echo '<ul class="esams-match-list">';
        foreach ($filtered as $row) {
            $timestamp = isset($row['date']) ? strtotime($row['date']) : false;
            $date_label = $timestamp ? esc_html(wp_date(get_option('date_format'), $timestamp)) : '';
            $time_suffix = ' ' . esc_html__('Uhr', 'easy-sams');
            $time_label = isset($row['time']) && $row['time'] !== '' ? esc_html($row['time']) . $time_suffix : ($timestamp ? esc_html(wp_date(get_option('time_format'), $timestamp)) . $time_suffix : '');
            $home_raw = isset($row['team1Description']) ? trim((string) $row['team1Description']) : '';
            $away_raw = isset($row['team2Description']) ? trim((string) $row['team2Description']) : '';
            $home = $home_raw !== '' ? esc_html($home_raw) : esc_html__('Unknown', 'easy-sams');
            $away = $away_raw !== '' ? esc_html($away_raw) : esc_html__('Unknown', 'easy-sams');

            $set_points  = isset($row['results']['setPoints']) && $row['results']['setPoints'] !== '' ? esc_html($row['results']['setPoints']) : '';
            $has_result  = $set_points !== '';
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

            echo '<li class="esams-match-card">';
            echo '<div class="esams-match-card__matchup">';
            echo '<span class="esams-match-card__team esams-match-card__team--home">' . $home . '</span>';
            if ($has_result) {
                echo '<span class="esams-match-card__score">' . $set_points . '</span>';
            } else {
                echo '<span class="esams-match-card__vs">' . esc_html__('vs', 'easy-sams') . '</span>';
            }
            echo '<span class="esams-match-card__team esams-match-card__team--away">' . $away . '</span>';
            echo '</div>';
            if (!empty($meta_parts)) {
                echo '<div class="esams-match-card__meta">' . implode('<span class="esams-match-card__sep" aria-hidden="true"></span>', $meta_parts) . '</div>';
            }
            echo '</li>';
        }
        echo '</ul>';
        echo $this->updated_hint($result);
        echo '</div>';
        return ob_get_clean();
    }

    private function updated_hint($result) {
        if (!is_array($result) || empty($result['ok'])) {
            return '';
        }

        $age = 0;
        if (isset($result['meta']['age']) && is_numeric($result['meta']['age'])) {
            $age = max(0, (int) $result['meta']['age']);
        }

        $last_updated_utc = time() - $age;
        $formatted = wp_date('j. F Y, H:i', $last_updated_utc) . ' ' . __('Uhr', 'easy-sams');

        return '<p class="esams-updated-hint">' . sprintf(esc_html__('Last updated: %s', 'easy-sams'), esc_html($formatted)) . '</p>';
    }

    private function resolve_source_id($attributes) {
        $source_id = isset($attributes['sourceId']) ? sanitize_key($attributes['sourceId']) : '';
        if ($source_id !== '') {
            return $source_id;
        }
        return $this->sources->get_default_id();
    }

    private function notice($message) {
        return '<div class="esams-notice">' . esc_html($message) . '</div>';
    }
}
