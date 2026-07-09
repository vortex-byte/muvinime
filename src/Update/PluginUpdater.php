<?php

namespace Muvinime\Update;

class PluginUpdater
{
    private string $updateUrl;
    private string $pluginSlug;
    private string $pluginFile;

    public function __construct()
    {
        $this->updateUrl = 'https://vortex-byte.github.io/muvinime/update.json';
        $this->pluginSlug = 'muvinime';
        $this->pluginFile = MVNIME_BASEFILE;
    }

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
    }

    public function checkUpdate($transient): mixed
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->fetchRemote();

        if (!$remote || version_compare($this->getLocalVersion(), $remote->version, '>=')) {
            return $transient;
        }

        $pluginBasename = plugin_basename($this->pluginFile);

        $transient->response[$pluginBasename] = (object) [
            'slug'        => $this->pluginSlug,
            'plugin'      => $pluginBasename,
            'new_version' => $remote->version,
            'url'         => $remote->homepage ?? '',
            'package'     => $remote->download_url,
            'tested'      => $remote->tested ?? '',
            'requires_php' => $remote->requires_php ?? '',
        ];

        return $transient;
    }

    public function pluginInfo($result, string $action, object $args): mixed
    {
        if ($action !== 'plugin_information' || $args->slug !== $this->pluginSlug) {
            return $result;
        }

        $remote = $this->fetchRemote();
        if (!$remote) {
            return $result;
        }

        return (object) [
            'name'           => $remote->name ?? 'MuviNime',
            'slug'           => $this->pluginSlug,
            'version'        => $remote->version,
            'author'         => $remote->author ?? '',
            'homepage'       => $remote->homepage ?? '',
            'requires'       => $remote->requires ?? '',
            'tested'         => $remote->tested ?? '',
            'requires_php'   => $remote->requires_php ?? '',
            'downloaded'     => $remote->downloaded ?? 0,
            'last_updated'   => $remote->last_updated ?? '',
            'sections'       => [
                'description' => $remote->sections->description ?? '',
                'changelog'   => $remote->sections->changelog ?? '',
            ],
            'download_link'  => $remote->download_url,
        ];
    }

    private function fetchRemote(): ?object
    {
        $response = wp_remote_get($this->updateUrl, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        return is_object($data) ? $data : null;
    }

    private function getLocalVersion(): string
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $pluginData = get_plugin_data($this->pluginFile, false);
        return $pluginData['Version'] ?? '0.0.0';
    }
}
