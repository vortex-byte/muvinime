<?php

namespace Muvinime\Cron;

use Muvinime\Controllers\EpisodeController;
use Muvinime\Controllers\MovieController;
use Muvinime\Services\EpisodeService;
use Muvinime\Services\SeriesService;
use Muvinime\Utils\Logger;
use Muvinime\Utils\QueueUtils;
use WP_Query;

class Handlers
{
    private MovieController $movieController;
    private EpisodeController $episodeController;

    public function __construct()
    {
        $seriesService = new SeriesService();
        $episodeService = new EpisodeService($seriesService);
        $this->movieController = new MovieController(new \Muvinime\Services\MovieService());
        $this->episodeController = new EpisodeController($episodeService);
    }

    public function postCronHandler(): void
    {
        try {
            if (get_option('muvinime_cron_lock')) return;
            if (QueueUtils::countQueue() < 1) return;

            for ($i = 1; $i <= 5; $i++) {
                if (QueueUtils::countQueue() < 1) break;
                $queue = QueueUtils::getQueue();

                if ($i == 1) {
                    update_option('muvinime_cron_lock', true);
                    Logger::info('CRON INITIATED');
                }

                $queue = array_values($queue);
                $data = $queue[0];
                $result = null;

                if ($data['type'] == 'episode') {
                    $result = $this->episodeController->post($data);
                } else {
                    $result = $this->movieController->post($data);
                }

                if (!$result || isset($result['error'])) {
                    Logger::error("E: " . ($result['title'] ?? $data['title'] ?? 'Unknown') . " " . ($result['error'] ?? 'Unknown error'));
                } elseif (isset($result['cancel'])) {
                    unset($queue[0]);
                    QueueUtils::saveQueue(array_values($queue));
                    Logger::warning($result['cancel']);
                } else {
                    unset($queue[0]);
                    QueueUtils::saveQueue(array_values($queue));
                    Logger::info("{$result['title']} => ID: {$result['id']}");
                }

                sleep(5);
            }
        } catch (\Exception $e) {
            Logger::error("Cron failed to finish job: " . $e->getMessage());
        } finally {
            update_option('muvinime_cron_lock', false);
        }
    }

    public function updateSeriesCronHandler(): void
    {
        $args = [
            'post_type'   => 'tv',
            'post_status' => 'draft',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'ASC'
        ];

        $last_draft = new WP_Query($args);

        if ($last_draft->have_posts()) {
            while ($last_draft->have_posts()) {
                $last_draft->the_post();
                $ID = get_the_ID();

                wp_update_post([
                    'ID'          => $ID,
                    'post_status' => 'publish'
                ]);
            }
            wp_reset_postdata();
            Logger::info("Publish Series " . get_the_title());
        }
    }

    public function clearCustomQueryCache(int $post_id): void
    {
        if (\defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $post_type = get_post_type($post_id);
        $allowed_post_types = ['post', 'tv'];

        if (!\in_array($post_type, $allowed_post_types, true)) {
            return;
        }

        if (wp_using_ext_object_cache()) {
            $registry = get_option('dashboard_custom_query_registry', []);
            if (!empty($registry)) {
                foreach ($registry as $key) {
                    delete_transient(str_replace('_transient_', '', $key));
                }
                update_option('dashboard_custom_query_registry', []);
            }
        } else {
            global $wpdb;
            $wpdb->query("
                DELETE FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_dashboard_custom_query_%'
                OR option_name LIKE '_transient_timeout_dashboard_custom_query_%'
            ");
        }
    }
}
