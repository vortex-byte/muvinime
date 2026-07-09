<?php

namespace Muvinime\Hooks;

class HttpFilters
{
    public static function increaseTimeout(array $args): array
    {
        $args['timeout'] = 60;
        return $args;
    }

    public static function increaseCurlTimeout($handle): void
    {
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    }

    public static function bypassImageDownloadNekopoi(array $args, string $url): array
    {
        if (strpos($url, 'nekopoi') !== false) {
            $args['headers']['Referer'] = 'https://nekopoi.care/';
            $args['headers']['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

            $proxyUrl = get_option('muvi_proxy_url');
            if ($proxyUrl && !empty($proxyUrl)) {
                $args['proxy'] = $proxyUrl;
            }
        }

        return $args;
    }
}
