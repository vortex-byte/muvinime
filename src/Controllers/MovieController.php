<?php

namespace Muvinime\Controllers;

use Muvinime\Services\MovieService;
use WP_REST_Request;
use WP_REST_Response;

class MovieController
{
    private MovieService $movieService;

    public function __construct(MovieService $movieService)
    {
        $this->movieService = $movieService;
    }

    public function post(array $data): array
    {
        return $this->movieService->upload($data);
    }

    public function deleteMovie(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        if (!$slug) {
            return new WP_REST_Response("slug parameter is required", 400);
        }

        $delete = $this->movieService->deleteMovie($slug);
        if (isset($delete['error'])) {
            return new WP_REST_Response($delete, 400);
        }

        $delete['status'] = 'ok';
        return new WP_REST_Response($delete, 200);
    }
}
