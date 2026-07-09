<?php

namespace Muvinime\Rest;

use WP_REST_Response;

class CronController
{
    public function refreshCron(): WP_REST_Response
    {
        $hooks = ['mvn_post_cron_hook', 'mvn_series_update_cron_hook'];

        try {
            foreach ($hooks as $hook) {
                wp_clear_scheduled_hook($hook);

                $timestamp = wp_next_scheduled($hook);
                if ($timestamp) {
                    wp_unschedule_event($timestamp, $hook);
                }

                sleep(1);

                if (!wp_next_scheduled($hook)) {
                    $time = $hook == 'mvn_post_cron_hook' ? '10min' : 'hourly';
                    wp_schedule_event(time(), $time, $hook);
                }

                sleep(1);
            }

            update_option('muvinime_cron_lock', false);

            return new WP_REST_Response('Success refresh cron', 200);
        } catch (\Exception $e) {
            return new WP_REST_Response('Failed refresh cron', 500);
        }
    }

    public function disableCron(): void
    {
        $timestamp = wp_next_scheduled('mvn_post_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'mvn_post_cron_hook');
        }
    }

    public function monitorCron(): WP_REST_Response
    {
        $postTime = wp_next_scheduled('mvn_post_cron_hook');
        $seriesTime = wp_next_scheduled('mvn_series_update_cron_hook');
        $postNext = $postTime ? date('Y-m-d H:i:s', $postTime) : null;
        $seriesNext = $seriesTime ? date('Y-m-d H:i:s', $seriesTime) : null;

        return new WP_REST_Response([
            'next post'          => $postNext,
            'next publish series' => $seriesNext,
        ]);
    }
}
