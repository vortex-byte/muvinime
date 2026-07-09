<?php

namespace Muvinime\Utils;

use Muvinime\Hooks\HttpFilters;

class Common
{
    public static function slugify(string $title): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\s]/', '', $title);
        $slug = trim($slug);
        $slug = str_replace(' ', '-', $slug);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/', '-', $slug);

        return $slug;
    }

    public static function setIframe(string $link, string $type): string
    {
        if ($type == 'DIRECT') {
            $playerBase = get_option('muvi_player_base_url', 'https://playsobat.xyz');
            $link = "$playerBase/player/index.html?file=$link";
        }

        return "<iframe src='$link' frameborder=0 marginwidth=0 marginheight=0 scrolling=NO width=640 height=360 allowfullscreen></iframe>";
    }

    public static function setThumbnail(string $url, int $postId): void
    {
        $m = date('m');
        $y = date('Y');

        add_filter('upload_dir', [self::class, 'customUploadDir']);
        $upload_dir = wp_upload_dir();
        wp_mkdir_p($upload_dir['basedir'] . "/$y/$m");

        add_filter('http_request_args', [HttpFilters::class, 'bypassImageDownloadNekopoi'], 10, 2);
        $image_id = media_sideload_image($url, $postId, null, 'id');
        remove_filter('http_request_args', [HttpFilters::class, 'bypassImageDownloadNekopoi']);

        if (!is_wp_error($image_id)) {
            $url = wp_get_attachment_url($image_id);
            set_post_thumbnail($postId, $image_id);
            PostUtils::createOrUpdateMeta($postId, 'add_post_meta', 'IDMUVICORE_Poster', $url);
        } else {
            Logger::error("Failed to set thumbnail $url: " . $image_id->get_error_message());
        }

        remove_filter('upload_dir', [self::class, 'customUploadDir']);
        PostUtils::createOrUpdateMeta($postId, 'add_post_meta', '_knawatfibu_url', $url);
    }

    public static function customUploadDir(array $dirs): array
    {
        $m = date('m');
        $y = date('Y');
        $dirs['subdir'] = "/$y/$m";
        $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
        $dirs['url']  = $dirs['baseurl'] . $dirs['subdir'];
        return $dirs;
    }
}
