<?php

if (!defined('ABSPATH')) {
    exit;
}

class MATCHBALL_Plugin {
    /** @var MATCHBALL_Source_Repository */
    private $sources;

    /** @var MATCHBALL_Api_Client */
    private $api;

    /** @var MATCHBALL_Admin */
    private $admin;

    /** @var MATCHBALL_Rest */
    private $rest;

    /** @var MATCHBALL_Blocks */
    private $blocks;

    /** @var MATCHBALL_Cron */
    private $cron;

    public function __construct() {
        $this->sources = new MATCHBALL_Source_Repository();
        $this->api = new MATCHBALL_Api_Client($this->sources);
        $this->admin = new MATCHBALL_Admin($this->sources);
        $this->rest = new MATCHBALL_Rest($this->sources, $this->api);
        $this->blocks = new MATCHBALL_Blocks($this->sources, $this->api);
        $this->cron = new MATCHBALL_Cron($this->sources, $this->api);
    }

    public function init() {
        $this->admin->register();
        $this->rest->register();
        $this->blocks->register();
        $this->cron->register();
    }
}
