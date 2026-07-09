<?php

namespace Muvinime\Admin;

class Activation
{
    public function activate(): void
    {
        if (!wp_next_scheduled('mvn_post_cron_hook')) {
            wp_schedule_event(time(), '10min', 'mvn_post_cron_hook');
        }

        if (!wp_next_scheduled('mvn_series_update_cron_hook')) {
            wp_schedule_event(time(), 'hourly', 'mvn_series_update_cron_hook');
        }

        add_option('muvinime_cron_lock', false);
    }

    public function deactivate(): void
    {
        $this->clearScheduledHook('mvn_post_cron_hook');
        $this->clearScheduledHook('mvn_series_update_cron_hook');
        delete_option('muvinime_cron_lock');
    }

    public function uninstall(): void
    {
        delete_option('muvi_apikey');
        delete_option('muvi_userid');
        delete_option('muvi_enable_log');

        $self = new self();
        $self->clearScheduledHook('mvn_post_cron_hook');
        $self->clearScheduledHook('mvn_series_update_cron_hook');
    }

    private function clearScheduledHook(string $hook): void
    {
        wp_clear_scheduled_hook($hook);

        $timestamp = wp_next_scheduled($hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
        }
    }
}
