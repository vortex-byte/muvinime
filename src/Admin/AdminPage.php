<?php

namespace Muvinime\Admin;

class AdminPage
{
    public function register(): void
    {
        add_menu_page(
            'Anime Uploader',
            'Anime Uploader',
            'publish_posts',
            'anime-uploader',
            [$this, 'renderSettingsPage'],
            'dashicons-superhero',
            7
        );
    }

    public function renderSettingsPage(): void
    {
        $key = get_option('muvi_apikey');
        $userid = get_option('muvi_userid');
        $enable_log = get_option('muvi_enable_log');
        $proxy_url = get_option('muvi_proxy_url', 'http://paorsnok-rotate:3gelpkdyzj63@p.webshare.io:80');
        $grab_api_url = get_option('muvi_grab_api_url', 'https://grabapi.xyz');
        $player_base_url = get_option('muvi_player_base_url', 'https://playsobat.xyz');
        $log_path = defined('MVNIME_URL') ? MVNIME_URL . 'app.log' : '';
        $log_level = get_option('muvi_log_level', 'INFO');

        require WP_PLUGIN_DIR . '/muvinime/templates/settings.php';
    }

    public function enqueueScripts($hook): void
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'muvigrabber_script',
            (\defined('MVNIME_URL') ? MVNIME_URL : plugin_dir_url(dirname(__DIR__, 2) . '/muvinime.php')) . 'assets/script.js',
            ['jquery']
        );

        $jsVar = [
            'ajax_url'   => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce("mvnime_ajax_nonce175"),
        ];

        wp_localize_script('muvigrabber_script', 'muvigrabber', $jsVar);
    }
}
