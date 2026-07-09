<?php

namespace Muvinime\Admin;

class Settings
{
    public function save(): void
    {
        if (!$this->hasValidNonce() || !current_user_can('manage_options')) {
            wp_redirect($_SERVER['HTTP_REFERER'] ?? admin_url());
            exit;
        }

        $this->saveField('apikey', 'muvi_apikey');
        $this->saveField('userid', 'muvi_userid');
        $this->saveIntField('enable_log', 'muvi_enable_log');
        $this->saveField('proxy_url', 'muvi_proxy_url');
        $this->saveField('grab_api_url', 'muvi_grab_api_url');
        $this->saveField('player_base_url', 'muvi_player_base_url');
        $this->saveField('log_level', 'muvi_log_level');

        wp_redirect($_SERVER['HTTP_REFERER'] ?? admin_url());
        exit;
    }

    private function hasValidNonce(): bool
    {
        if (!isset($_POST['mvnime_nonce'])) return false;

        $field = wp_unslash($_POST['mvnime_nonce']);
        return wp_verify_nonce($field, 'mvnime_ajax_nonce175');
    }

    private function saveField(string $postKey, string $optionKey): void
    {
        if (isset($_POST[$postKey])) {
            $value = sanitize_text_field(wp_unslash($_POST[$postKey]));

            if (!empty($value)) {
                if (get_option($optionKey) !== false) {
                    update_option($optionKey, $value);
                } else {
                    add_option($optionKey, $value);
                }
            }
        }
    }

    private function saveIntField(string $postKey, string $optionKey): void
    {
        if (isset($_POST[$postKey])) {
            $value = (int)sanitize_text_field(wp_unslash($_POST[$postKey]));

            if (get_option($optionKey) !== false) {
                update_option($optionKey, $value);
            } else {
                add_option($optionKey, $value);
            }
        }
    }
}
