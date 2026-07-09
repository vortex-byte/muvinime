<?php

namespace Muvinime;

use Muvinime\Controllers\MovieController;
use Muvinime\Controllers\SeriesController;
use Muvinime\Rest\CronController;
use Muvinime\Rest\HealthController;
use Muvinime\Rest\PostController;
use Muvinime\Rest\QueueController;
use Muvinime\Services\EpisodeService;
use Muvinime\Services\MovieService;
use Muvinime\Services\SeriesService;

class Routes
{
    public function register(): void
    {
        $queueController = new QueueController();
        $cronController = new CronController();
        $postController = new PostController();
        $healthController = new HealthController();

        $movieService = new MovieService();
        $seriesService = new SeriesService();
        $episodeService = new EpisodeService($seriesService);
        $movieController = new MovieController($movieService);
        $seriesController = new SeriesController($seriesService, $episodeService);

        $this->registerQueueRoutes($queueController);
        $this->registerPostRoutes($postController);
        $this->registerCronRoutes($cronController);
        $this->registerDeleteRoutes($movieController, $seriesController);
        $this->registerLogRoutes($healthController);
        $this->registerHealthRoute($healthController);
    }

    private function registerQueueRoutes(QueueController $controller): void
    {
        register_rest_route('muvinime/v1', 'queue', [
            'methods'  => 'GET',
            'callback' => [$controller, 'getQueue'],
        ]);

        register_rest_route('muvinime/v1', 'addqueue/(?P<api_key>.+)', [
            'methods'  => 'POST',
            'callback' => [$controller, 'addQueue'],
        ]);
    }

    private function registerPostRoutes(PostController $controller): void
    {
        register_rest_route('muvinime/v1', 'post/(?P<post_id>.+)', [
            'methods'  => 'GET',
            'callback' => [$controller, 'getPostInfo'],
        ]);

        register_rest_route('muvinime/v1', 'posts', [
            'methods'  => 'GET',
            'callback' => [$controller, 'getPostsList'],
        ]);

        register_rest_route('muvinime/v1', 'search', [
            'methods'  => 'GET',
            'callback' => [$controller, 'search'],
        ]);
    }

    private function registerCronRoutes(CronController $controller): void
    {
        register_rest_route('muvinime/v1', 'cron/refresh', [
            'methods'  => 'GET',
            'callback' => [$controller, 'refreshCron'],
        ]);

        register_rest_route('muvinime/v1', 'cron/disable', [
            'methods'  => 'GET',
            'callback' => [$controller, 'disableCron'],
        ]);
    }

    private function registerDeleteRoutes(MovieController $movieController, SeriesController $seriesController): void
    {
        register_rest_route('muvinime/v1', 'delete/tv/(?P<slug>.+)', [
            'methods'  => 'GET',
            'callback' => [$seriesController, 'deleteSeries'],
        ]);

        register_rest_route('muvinime/v1', 'delete/movie/(?P<slug>.+)', [
            'methods'  => 'GET',
            'callback' => [$movieController, 'deleteMovie'],
        ]);
    }

    private function registerLogRoutes(HealthController $controller): void
    {
        register_rest_route('muvinime/v1', 'log', [
            'methods'  => 'GET',
            'callback' => [$controller, 'getLog'],
        ]);

        register_rest_route('muvinime/v1', 'clear-log', [
            'methods'  => 'GET',
            'callback' => [$controller, 'clearLog'],
        ]);
    }

    private function registerHealthRoute(HealthController $controller): void
    {
        register_rest_route('muvinime/v1', 'tes', [
            'methods'  => 'GET',
            'callback' => [$controller, 'alive'],
        ]);
    }
}
