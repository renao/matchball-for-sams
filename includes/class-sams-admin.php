<?php

if (!defined('ABSPATH')) {
    exit;
}

class SIK_Admin {
    /** @var SIK_Source_Repository */
    private $sources;

    public function __construct($sources) {
        $this->sources = $sources;
    }

    public function register() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_sik_save_sources', array($this, 'handle_save'));
    }

    public function add_menu() {
        add_options_page(
            'SAMS Sources',
            'SAMS Sources',
            'manage_options',
            'sik-sources',
            array($this, 'render_page')
        );
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        check_admin_referer('sik_save_sources');

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

        wp_safe_redirect(add_query_arg(array('page' => 'sik-sources', 'updated' => '1'), admin_url('options-general.php')));
        exit;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
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
        echo '<h1>SAMS Sources</h1>';

        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Sicherheit:</strong> API-Keys werden automatisch verschlüsselt und sicher in der Datenbank gespeichert. Sie werden niemals im Backend angezeigt.</p>';
        echo '</div>';

        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success"><p>Sources saved.</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('sik_save_sources');
        echo '<input type="hidden" name="action" value="sik_save_sources" />';

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
        echo '<th>ID</th><th>Name</th><th>Base URL</th><th>API Key</th><th>Association UUID</th><th>Active</th><th>Default</th>';
        echo '</tr></thead><tbody>';

        foreach ($sources as $index => $source) {
            $prefix = 'sources[' . $index . ']';
            echo '<tr>';
            echo '<td><input type="text" name="' . esc_attr($prefix . '[id]') . '" value="' . esc_attr($source['id']) . '" /></td>';
            echo '<td><input type="text" name="' . esc_attr($prefix . '[name]') . '" value="' . esc_attr($source['name']) . '" /></td>';
            echo '<td><input type="url" size="32" name="' . esc_attr($prefix . '[base_url]') . '" value="' . esc_attr($source['base_url']) . '" /></td>';
            echo '<td>';
            echo '<input type="password" autocomplete="new-password" placeholder="leave empty to keep existing" name="' . esc_attr($prefix . '[api_key]') . '" value="" />';
            if (!empty($source['id']) && $this->sources->uses_external_api_key($source['id'])) {
                echo '<p><small>Using server-side key override.</small></p>';
            }
            echo '</td>';
            echo '<td><input type="text" size="24" name="' . esc_attr($prefix . '[association_uuid]') . '" value="' . esc_attr($source['association_uuid']) . '" /></td>';
            echo '<td><input type="checkbox" name="' . esc_attr($prefix . '[is_active]') . '" value="1" ' . checked(!empty($source['is_active']), true, false) . ' /></td>';
            echo '<td><input type="radio" name="default_source" value="' . esc_attr($source['id']) . '" ' . checked(!empty($source['is_default']), true, false) . ' /></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        submit_button('Save Sources');
        echo '</form>';
        echo '</div>';
    }
}
