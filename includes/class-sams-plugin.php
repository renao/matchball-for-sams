<?php

if (!defined('ABSPATH')) {
    exit;
}

class ESAMS_Plugin {
    /** @var ESAMS_Source_Repository */
    private $sources;

    /** @var ESAMS_Api_Client */
    private $api;

    /** @var ESAMS_Admin */
    private $admin;

    /** @var ESAMS_Rest */
    private $rest;

    /** @var ESAMS_Blocks */
    private $blocks;

    /** @var ESAMS_Cron */
    private $cron;

    public function __construct() {
        $this->sources = new ESAMS_Source_Repository();
        $this->api = new ESAMS_Api_Client($this->sources);
        $this->admin = new ESAMS_Admin($this->sources);
        $this->rest = new ESAMS_Rest($this->sources, $this->api);
        $this->blocks = new ESAMS_Blocks($this->sources, $this->api);
        $this->cron = new ESAMS_Cron($this->sources, $this->api);
    }

    public function init() {
        $this->admin->register();
        $this->rest->register();
        $this->blocks->register();
        $this->cron->register();
    }
}
