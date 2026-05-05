<?php

if (!defined('ABSPATH')) {
    exit;
}

class SIK_Plugin {
    /** @var SIK_Source_Repository */
    private $sources;

    /** @var SIK_Api_Client */
    private $api;

    /** @var SIK_Admin */
    private $admin;

    /** @var SIK_Rest */
    private $rest;

    /** @var SIK_Blocks */
    private $blocks;

    public function __construct() {
        $this->sources = new SIK_Source_Repository();
        $this->api = new SIK_Api_Client($this->sources);
        $this->admin = new SIK_Admin($this->sources);
        $this->rest = new SIK_Rest($this->sources, $this->api);
        $this->blocks = new SIK_Blocks($this->sources, $this->api);
    }

    public function init() {
        $this->admin->register();
        $this->rest->register();
        $this->blocks->register();
    }
}
