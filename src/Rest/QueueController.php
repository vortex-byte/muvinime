<?php

namespace Muvinime\Rest;

use Muvinime\Utils\Logger;
use Muvinime\Utils\QueueUtils;
use WP_REST_Request;
use WP_REST_Response;

class QueueController
{
    public function getQueue(): void
    {
        header('Content-Type:application/json');
        echo json_encode(QueueUtils::getQueue());
    }

    public function addQueue(WP_REST_Request $request): WP_REST_Response
    {
        $trueApiKey = get_option('muvi_apikey');
        $apiKey = $request->get_param('api_key');
        $json = $request->get_body();
        $data = json_decode($json, true);
        $data = $data['data'] ?? [];

        if ($apiKey !== $trueApiKey) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Invalid API Key',
                'code'    => 401
            ], 401);
        }

        if (!$data || empty($data)) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => 'Bad request',
                'code'    => 400
            ], 400);
        }

        Logger::info('New data added to queue');
        QueueUtils::addQueue(json_encode($data));

        return new WP_REST_Response([
            'success' => true,
            'code'    => 200
        ], 200);
    }

    public function countQueue(): int
    {
        return QueueUtils::countQueue();
    }
}
