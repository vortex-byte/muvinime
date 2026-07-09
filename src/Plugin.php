<?php

namespace Muvinime;

use Muvinime\Admin\Activation;
use Muvinime\Admin\AdminPage;
use Muvinime\Admin\Settings;
use Muvinime\Cron\Handlers;
use Muvinime\Cron\Scheduler;
use Muvinime\Update\PluginUpdater;

class Plugin
{
    private AdminPage $adminPage;
    private Activation $activation;
    private Settings $settings;
    private Handlers $cronHandlers;
    private Scheduler $scheduler;

    public function boot(): void
    {
        $this->defineConstants();
        $this->setPhpIni();
        $this->initDependencies();
        $this->registerHooks();
        date_default_timezone_set('Asia/Jakarta');
    }

    private function defineConstants(): void
    {
        if (!\defined('MVNIME_USER_ID')) {
            define('MVNIME_USER_ID', get_option('muvi_userid'));
        }
        if (!\defined('MVNIME_PATH')) {
            define('MVNIME_PATH', plugin_dir_path(MVNIME_BASEFILE));
        }
        if (!\defined('MVNIME_URL')) {
            define('MVNIME_URL', plugin_dir_url(MVNIME_BASEFILE));
        }
        if (!\defined('MVNIME_BASEDIR')) {
            define('MVNIME_BASEDIR', plugin_basename(dirname(MVNIME_BASEFILE)));
        }
        if (!\defined('MVNIME_BASEFILE')) {
            define('MVNIME_BASEFILE', WP_PLUGIN_DIR . '/muvinime/muvinime.php');
        }
        if (!\defined('MVNIME_ENABLE_LOG')) {
            define('MVNIME_ENABLE_LOG', get_option('muvi_enable_log'));
        }
    }

    private function setPhpIni(): void
    {
        @ini_set('upload_max_size', '64M');
        @ini_set('post_max_size', '64M');
        @ini_set('max_execution_time', '300');
    }

    private function initDependencies(): void
    {
        $this->activation = new Activation();
        $this->adminPage = new AdminPage();
        $this->settings = new Settings();
        $this->cronHandlers = new Handlers();
        $this->scheduler = new Scheduler();
    }

    private function registerHooks(): void
    {
        $this->registerActivationHooks();
        $this->registerAdminHooks();
        $this->registerCronHooks();
        $this->registerRestRoutes();
        $this->registerHttpFilters();
        $this->registerPostTypeSupport();
        $this->registerUpdateChecker();
    }

    private function registerActivationHooks(): void
    {
        $pluginFile = constant('MVNIME_BASEFILE');
        register_activation_hook($pluginFile, [$this->activation, 'activate']);
        register_deactivation_hook($pluginFile, [$this->activation, 'deactivate']);
        register_uninstall_hook($pluginFile, [Activation::class, 'uninstall']);
    }

    private function registerAdminHooks(): void
    {
        add_action('admin_menu', [$this->adminPage, 'register']);
        add_action('admin_enqueue_scripts', [$this->adminPage, 'enqueueScripts']);
        add_action('admin_post_muvinime_save_settings', [$this->settings, 'save']);
    }

    private function registerCronHooks(): void
    {
        add_filter('cron_schedules', [$this->scheduler, 'registerSchedules']);
        add_action('mvn_post_cron_hook', [$this->cronHandlers, 'postCronHandler']);
        add_action('mvn_series_update_cron_hook', [$this->cronHandlers, 'updateSeriesCronHandler']);
        add_action('save_post', [$this->cronHandlers, 'clearCustomQueryCache']);
        add_action('deleted_post', [$this->cronHandlers, 'clearCustomQueryCache']);
        add_action('trashed_post', [$this->cronHandlers, 'clearCustomQueryCache']);
    }

    private function registerRestRoutes(): void
    {
        add_action('rest_api_init', [new Routes(), 'register']);
    }

    private function registerHttpFilters(): void
    {
        add_filter('http_request_args', [Hooks\HttpFilters::class, 'increaseTimeout'], 100, 1);
        add_action('http_api_curl', [Hooks\HttpFilters::class, 'increaseCurlTimeout'], 100, 1);
    }

    private function registerPostTypeSupport(): void
    {
        add_post_type_support('tv', 'thumbnail');
    }

    private function registerUpdateChecker(): void
    {
        $updater = new PluginUpdater();
        $updater->register();
    }
}
