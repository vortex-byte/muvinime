<?php

namespace Muvinime\Rest;

use Muvinime\Utils\Logger;
use WP_REST_Response;

class HealthController
{
    public function alive(): void
    {
        echo 'Rest API is working';
    }

    public function getLog(): void
    {
        header('Content-Type:text/plain');
        $logPath = \defined('MVNIME_PATH') ? MVNIME_PATH . 'app.log' : WP_PLUGIN_DIR . '/muvinime/app.log';
        if (!file_exists($logPath)) {
            file_put_contents($logPath, '');
        }
        echo file_get_contents($logPath);
    }

    public function clearLog(): WP_REST_Response
    {
        $logPath = \defined('MVNIME_PATH') ? MVNIME_PATH . 'app.log' : WP_PLUGIN_DIR . '/muvinime/app.log';
        file_put_contents($logPath, '');
        return new WP_REST_Response('Success clear log');
    }
}
