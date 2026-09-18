<?php

if (!defined('ABSPATH')) {
    exit;
}

class ESAMS_Cron {
    private const HOOK = 'esams_sync_cron';
    private const SCHEDULE = 'esams_every_five_minutes';

    /** @var ESAMS_Source_Repository */
    private $sources;

    /** @var ESAMS_Api_Client */
    private $api;

    public function __construct($sources, $api) {
        $this->sources = $sources;
        $this->api = $api;
    }

    public function register() {
        add_filter('cron_schedules', array($this, 'add_schedule'));
        add_action(self::HOOK, array($this, 'run_sync'));
        self::ensure_scheduled();
    }

    public static function activate() {
        self::ensure_scheduled();
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
    }

    public function add_schedule($schedules) {
        if (!isset($schedules[self::SCHEDULE])) {
            $schedules[self::SCHEDULE] = array(
                'interval' => 300,
                'display' => __('Every 5 Minutes', 'easy-sams'),
            );
        }
        return $schedules;
    }

    public function run_sync() {
        $active_sources = $this->sources->get_active();
        if (empty($active_sources)) {
            return;
        }

        foreach ($active_sources as $source) {
            if (empty($source['id'])) {
                continue;
            }

            $source_id = $source['id'];
            $association = !empty($source['association_uuid']) ? $source['association_uuid'] : '';

            $seasons_result = $this->api->get_seasons($source_id);
            if (empty($seasons_result['ok'])) {
                $this->log_sync_issue($source_id, 'seasons', $seasons_result);
                continue;
            }

            $season_uuid = $this->pick_primary_season($seasons_result);
            if ($season_uuid === '') {
                continue;
            }

            $leagues_result = $this->api->get_leagues($source_id, $season_uuid, $association);
            if (empty($leagues_result['ok'])) {
                $this->log_sync_issue($source_id, 'leagues', $leagues_result);
                continue;
            }

            $leagues = isset($leagues_result['data']['content']) && is_array($leagues_result['data']['content'])
                ? $leagues_result['data']['content']
                : array();

            foreach ($leagues as $league) {
                if (!isset($league['uuid']) || $league['uuid'] === '') {
                    continue;
                }

                $league_uuid = (string) $league['uuid'];
                $this->api->get_rankings($source_id, $league_uuid);
                $this->api->get_matches($source_id, array(
                    'for-season' => $season_uuid,
                    'for-league' => $league_uuid,
                    'association' => $association,
                ));
            }
        }
    }

    private static function ensure_scheduled() {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + 60, self::SCHEDULE, self::HOOK);
        }
    }

    private function pick_primary_season($seasons_result) {
        $items = isset($seasons_result['data']['content']) && is_array($seasons_result['data']['content'])
            ? $seasons_result['data']['content']
            : array();

        if (empty($items)) {
            return '';
        }

        foreach ($items as $item) {
            if (!empty($item['currentSeason']) && !empty($item['uuid'])) {
                return (string) $item['uuid'];
            }
        }

        return !empty($items[0]['uuid']) ? (string) $items[0]['uuid'] : '';
    }

    private function log_sync_issue($source_id, $stage, $result) {
        $code = 'unknown';
        if (is_array($result) && !empty($result['error']['code'])) {
            $code = (string) $result['error']['code'];
        }
        error_log(sprintf('[ESAMS] Cron sync issue source=%s stage=%s code=%s', $source_id, $stage, $code));
    }
}
