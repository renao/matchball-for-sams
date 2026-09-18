<?php

if (!defined('ABSPATH')) {
    exit;
}

class ESAMS_Admin {
    /** @var ESAMS_Source_Repository */
    private $sources;

    public function __construct($sources) {
        $this->sources = $sources;
    }

    public function register() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_esams_save_sources', array($this, 'handle_save'));
    }

    public function add_menu() {
        add_options_page(
            __('SAMS Sources', 'easy-sams'),
            __('SAMS Sources', 'easy-sams'),
            'manage_options',
            'esams-sources',
            array($this, 'render_page')
        );
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'easy-sams'));
        }

        check_admin_referer('esams_save_sources');

        $sources = isset($_POST['sources']) ? wp_unslash($_POST['sources']) : array();
        $default = isset($_POST['default_source']) ? sanitize_key(wp_unslash($_POST['default_source'])) : '';

        $prepared = array();
        $existing = array();
        foreach ($this->sources->get_all() as $source_item) {
            $existing[$source_item['id']] = $source_item;
        }

        if (is_array($sources)) {
            foreach ($sources as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = isset($row['id']) ? sanitize_key($row['id']) : '';
                if ($id === '') {
                    continue;
                }

                $posted_api_key = isset($row['api_key']) ? sanitize_text_field($row['api_key']) : '';
                if ($this->sources->uses_external_api_key($id)) {
                    $posted_api_key = '';
                } elseif ($posted_api_key === '' && isset($existing[$id]['api_key'])) {
                    $posted_api_key = $existing[$id]['api_key'];
                }

                $prepared[] = array(
                    'id' => $id,
                    'name' => isset($row['name']) ? sanitize_text_field($row['name']) : '',
                    'base_url' => isset($row['base_url']) ? esc_url_raw($row['base_url']) : '',
                    'api_key' => $posted_api_key,
                    'association_uuid' => isset($row['association_uuid']) ? sanitize_text_field($row['association_uuid']) : '',
                    'is_active' => !empty($row['is_active']),
                    'is_default' => $id === $default,
                );
            }
        }

        $this->sources->save_all($prepared);

        wp_safe_redirect(add_query_arg(array('page' => 'esams-sources', 'updated' => '1'), admin_url('options-general.php')));
        exit;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'easy-sams'));
        }

        $sources = $this->sources->get_all();
        if (empty($sources)) {
            $sources = array(
                array(
                    'id' => 'sams-v2',
                    'name' => 'SAMS v2.1',
                    'base_url' => 'https://wvv.sams-server.de/api/v2',
                    'api_key' => '',
                    'association_uuid' => '',
                    'is_active' => true,
                    'is_default' => true,
                ),
            );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('SAMS Sources', 'easy-sams') . '</h1>';

        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>' . esc_html__('Security:', 'easy-sams') . '</strong> ' . esc_html__('API keys are encrypted automatically and stored securely in the database. They are never shown in the admin UI.', 'easy-sams') . '</p>';
        echo '</div>';

        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Sources saved.', 'easy-sams') . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('esams_save_sources');
        echo '<input type="hidden" name="action" value="esams_save_sources" />';

        $sources[] = array(
            'id' => '',
            'name' => '',
            'base_url' => '',
            'api_key' => '',
            'association_uuid' => '',
            'is_active' => false,
            'is_default' => false,
        );

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
    echo '<th>' . esc_html__('ID', 'easy-sams') . '</th><th>' . esc_html__('Name', 'easy-sams') . '</th><th>' . esc_html__('Base URL', 'easy-sams') . '</th><th>' . esc_html__('API Key', 'easy-sams') . '</th>';
    echo '<th>' . esc_html__('Top-level association UUID', 'easy-sams') . '<br><small style="font-weight:normal">' . esc_html__('Scopes this source: limits cron pre-fetch, match results filter, and the association picker in blocks', 'easy-sams') . '</small></th>';
    echo '<th>' . esc_html__('Active', 'easy-sams') . '</th><th>' . esc_html__('Default', 'easy-sams') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($sources as $index => $source) {
            $prefix = 'sources[' . $index . ']';
            echo '<tr>';
            echo '<td><input type="text" name="' . esc_attr($prefix . '[id]') . '" value="' . esc_attr($source['id']) . '" /></td>';
            echo '<td><input type="text" name="' . esc_attr($prefix . '[name]') . '" value="' . esc_attr($source['name']) . '" /></td>';
            echo '<td><input type="url" size="32" name="' . esc_attr($prefix . '[base_url]') . '" value="' . esc_attr($source['base_url']) . '" /></td>';
            echo '<td>';
            echo '<input type="password" autocomplete="new-password" placeholder="' . esc_attr__('Leave empty to keep existing key', 'easy-sams') . '" name="' . esc_attr($prefix . '[api_key]') . '" value="" />';
            if (!empty($source['id']) && $this->sources->uses_external_api_key($source['id'])) {
                echo '<p><small>' . esc_html__('Using server-side key override.', 'easy-sams') . '</small></p>';
            }
            echo '</td>';
            echo '<td>';
            echo '<input type="text" size="24" name="' . esc_attr($prefix . '[association_uuid]') . '" value="' . esc_attr($source['association_uuid']) . '" aria-describedby="assoc-help-' . esc_attr((string) $index) . '" />';
            echo '<p class="description" id="assoc-help-' . esc_attr((string) $index) . '" style="margin-top:4px">' . esc_html__('UUID of the parent/top-level association from SAMS. Leave empty if the API key already limits the scope. This is not the same as the per-block association filter used by editors.', 'easy-sams') . '</p>';
            echo '</td>';
            echo '<td><input type="checkbox" name="' . esc_attr($prefix . '[is_active]') . '" value="1" ' . checked(!empty($source['is_active']), true, false) . ' /></td>';
            echo '<td><input type="radio" name="default_source" value="' . esc_attr($source['id']) . '" ' . checked(!empty($source['is_default']), true, false) . ' /></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        submit_button(__('Save Sources', 'easy-sams'));
        echo '</form>';
        echo '</div>';
    }
}
