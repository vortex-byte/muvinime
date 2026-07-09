<?php

namespace Muvinime\Services;

use Muvinime\Utils\Common;
use Muvinime\Utils\Logger;

class EpisodeService extends BasePostService
{
    const POST_TYPE = 'episode';

    private SeriesService $seriesService;

    public function __construct(SeriesService $seriesService)
    {
        $this->seriesService = $seriesService;
    }

    public function getEpisodeByTitle(string $title): ?object
    {
        return $this->findByTitle($title, self::POST_TYPE);
    }

    public function getEpisodeBySlug(string $slug): ?object
    {
        return $this->findBySlug($slug, self::POST_TYPE);
    }

    public function upload(array $data): array
    {
        try {
            $title = "{$data['title']} Episode {$data['epsno']}";

            if (!isset($data['epsno'])) {
                return ["cancel" => "Missing Parameter 'epsno' for $title"];
            }

            if (!isset($data['stream'])) {
                return ["cancel" => "Missing Parameter 'stream' for $title"];
            }

            if (isset($data['update']) && $data['update']) {
                return $this->update($data);
            }

            $tmdb = $data['tmdb'];
            $series = $this->seriesService->getSeriesByTitle($data['title']);
            $createSeries = true;
            $lastEpisodeNo = 0;

            if ($series && isset($series->IDMUVICORE_tmdbID)) {
                $createSeries = false;
                $updatedSeries = $this->seriesService->update($series->post_name, $data);
                $tmdb = $updatedSeries['tmdb'];
                $seriesId = $updatedSeries['id'];
                $lastEpisodeNo = $updatedSeries['last_eps'];
            }

            if ($createSeries) {
                $uploadSeries = $this->seriesService->upload($data);

                if (!$uploadSeries || isset($uploadSeries['error'])) {
                    return ["cancel" => "Failed to post series {$data['title']}: " . ($uploadSeries['error'] ?? '')];
                }

                $seriesId = $uploadSeries['id'];
                $tmdb = $uploadSeries['tmdb'];
            }

            $this->deleteExistingPost($title, self::POST_TYPE);

            $postId = $this->createPost($data, self::POST_TYPE, $title);

            $this->setMeta($postId, [
                'IDMUVICORE_Title'              => $title,
                'IDMUVICORE_tmdbVotes'          => $data['voters'] ?? '',
                'IDMUVICORE_tmdbRating'         => $data['score'] ?? '',
                'IDMUVICORE_Episodenumber'      => $data['epsno'] ?? '',
                'IDMUVICORE_tmdbID'             => $tmdb,
                'IDMUVICORE_Title_Player1'      => 'MULTI SERVER',
                'IDMUVICORE_Player1'            => Common::setIframe($data['stream'], 'MULTI SERVER'),
                'IDMUVICORE_Title_Download1'    => 'DOWNLOAD',
                'IDMUVICORE_Download1'          => $data['download'] ?? '',
            ]);

            $this->setPoster($postId, $data['poster'] ?? null);

            if (\intval($lastEpisodeNo) < \intval($data['epsno'])) {
                wp_update_post([
                    "ID" => $seriesId,
                    "meta_input" => [
                        "IDMUVICORE_Numbepisode" => $data['epsno']
                    ]
                ]);
            }

            $this->publishPost($postId);

            return ['id' => $postId, 'title' => $title];
        } catch (\Throwable $e) {
            Logger::error("Episode upload failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function update(array $data): array
    {
        try {
            $title = "{$data['title']} Episode {$data['epsno']}";
            $updateUrl = $data['update'];
            $path = parse_url($updateUrl, PHP_URL_PATH);
            $split = explode('/', $path ?? '');

            if (!$path || !isset($split[1]) || $split[1] !== 'tv') {
                return ["cancel" => "Invalid URL post for update episode $title"];
            }

            $series = $this->seriesService->update($split[2], $data);

            if (!$series || isset($series['error'])) {
                return isset($series['error']) ? $series : ["error" => "Failed to update series {$data['title']}"];
            }

            $existingByTitle = $this->getEpisodeByTitle($title);
            if ($existingByTitle) {
                wp_delete_post($existingByTitle->ID);
                sleep(1);
                wp_delete_post($existingByTitle->ID, true);
            }

            $existingBySlug = $this->getEpisodeBySlug(Common::slugify($title));
            if ($existingBySlug) {
                wp_delete_post($existingBySlug->ID);
                sleep(1);
                wp_delete_post($existingBySlug->ID, true);
            }

            $postId = $this->createPost($data, self::POST_TYPE, $title);

            $this->setMeta($postId, [
                'IDMUVICORE_Title'              => $title,
                'IDMUVICORE_tmdbVotes'          => $data['voters'] ?? '',
                'IDMUVICORE_tmdbRating'         => $data['score'] ?? '',
                'IDMUVICORE_Episodenumber'      => $data['epsno'] ?? '',
                'IDMUVICORE_tmdbID'             => $series['tmdb'],
                'IDMUVICORE_Title_Player1'      => 'MULTI SERVER',
                'IDMUVICORE_Player1'            => Common::setIframe($data['stream'], 'MULTI SERVER'),
                'IDMUVICORE_Title_Download1'    => 'DOWNLOAD',
                'IDMUVICORE_Download1'          => $data['download'] ?? '',
            ]);

            $this->setPoster($postId, $data['poster'] ?? null);
            $this->publishPost($postId);

            return ['id' => $postId, 'title' => $title];
        } catch (\Throwable $e) {
            Logger::error("Episode update failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function deleteEpisode($tmdbId)
    {
        try {
            $posts = get_posts([
                'post_type'      => 'episode',
                'meta_key'       => 'IDMUVICORE_tmdbID',
                'meta_value'     => $tmdbId,
                'posts_per_page' => -1
            ]);

            if (!$posts || empty($posts)) return false;

            foreach ($posts as $post) {
                $delete = wp_delete_post($post->ID, true);
                if (is_wp_error($delete)) {
                    throw new \RuntimeException("Failed delete eps {$post->post_title}. Error: {$delete->get_error_message()}");
                }
            }

            return true;
        } catch (\Throwable $e) {
            Logger::error("Episode delete failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
