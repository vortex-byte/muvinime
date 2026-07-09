<?php

namespace Muvinime\Controllers;

use Muvinime\Services\EpisodeService;
use Muvinime\Services\SeriesService;
use WP_REST_Request;
use WP_REST_Response;

class SeriesController
{
    private SeriesService $seriesService;
    private EpisodeService $episodeService;

    public function __construct(SeriesService $seriesService, EpisodeService $episodeService)
    {
        $this->seriesService = $seriesService;
        $this->episodeService = $episodeService;
    }

    public function deleteSeries(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        if (!$slug) {
            return new WP_REST_Response("slug parameter is required", 400);
        }

        $delete = $this->seriesService->deleteSeries($this->episodeService, $slug);
        if (isset($delete['error'])) {
            return new WP_REST_Response($delete, 400);
        }

        $delete['status'] = 'ok';
        return new WP_REST_Response($delete, 200);
    }
}
