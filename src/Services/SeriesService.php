<?php

namespace Muvinime\Services;

use Muvinime\Utils\Logger;

class SeriesService extends BasePostService
{
    const POST_TYPE = 'tv';

    public function getSeriesByTitle(string $title): ?object
    {
        return $this->findByTitle($title, self::POST_TYPE);
    }

    public function getSeriesBySlug(string $slug): ?object
    {
        return $this->findBySlug($slug, self::POST_TYPE);
    }

    public function upload(array $data): array
    {
        try {
            $existing = $this->getSeriesByTitle($data['title']);
            if ($existing) {
                return [
                    'id'    => $existing->ID,
                    'title' => $existing->post_title,
                    'tmdb'  => $existing->IDMUVICORE_tmdbID,
                ];
            }

            $postId = $this->createPost($data, self::POST_TYPE, null);

            $tmdb = $this->generateUniqueTmdbId((int)($data['tmdb'] ?? 0));

            $this->setMeta($postId, [
                'IDMUVICORE_Title'        => $data['title'] ?? '',
                'IDMUVICORE_tmdbVotes'    => $data['voters'] ?? '',
                'IDMUVICORE_tmdbRating'   => $data['score'] ?? '',
                'IDMUVICORE_tmdbID'       => $tmdb,
                'IDMUVICORE_Released'     => $data['released'] ?? '',
                'IDMUVICORE_Runtime'      => $data['duration'] ?? '',
                'IDMUVICORE_Year'         => $data['year'] ?? '',
                'IDMUVICORE_Numbepisode'  => $data['epsno'] ?? '',
            ]);

            $this->setTerms($postId, [
                'post_tag' => $data['tags'] ?? '',
                'muviyear' => $data['year'] ?? '',
            ]);

            $this->setCategories($postId, $data['genres'] ?? null);
            $this->setPoster($postId, $data['poster'] ?? null);

            return [
                'id'    => $postId,
                'title' => $data["title"],
                'tmdb'  => $tmdb,
            ];
        } catch (\Throwable $e) {
            Logger::error("Series upload failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function update(string $slug, array $data): array
    {
        try {
            $post = $this->getSeriesBySlug($slug);
            if (!$post) {
                return ["cancel" => "Series $slug not found"];
            }

            $modified_date = get_the_modified_date('Y-m-d', $post->ID);
            $modified_timestamp = strtotime($modified_date);
            $threshold = strtotime('-3 days');

            if ($modified_timestamp >= $threshold) {
                return [
                    'id'    => $post->ID,
                    'title' => $data["title"],
                    'tmdb'  => $post->IDMUVICORE_tmdbID,
                ];
            }

            $updateId = $this->updatePost($post->ID, $data);

            $this->setMeta($updateId, [
                'IDMUVICORE_Title'       => $data['title'] ?? '',
                'IDMUVICORE_tmdbVotes'   => $data['voters'] ?? '',
                'IDMUVICORE_tmdbRating'  => $data['score'] ?? '',
                'IDMUVICORE_Released'    => $data['released'] ?? '',
                'IDMUVICORE_Runtime'     => $data['duration'] ?? '',
                'IDMUVICORE_Year'        => $data['year'] ?? '',
                'IDMUVICORE_Numbepisode' => $data['epsno'] ?? '',
            ]);

            $this->setCategories($updateId, $data['genres'] ?? null);

            if ($data['poster'] ?? null) {
                $currPoster = get_post_meta($updateId, '_knawatfibu_url', true);
                $head = wp_remote_get($currPoster, ['timeout' => 5]);
                $headCode = wp_remote_retrieve_response_code($head);

                if (!$headCode || $headCode >= 400) {
                    delete_post_thumbnail($updateId);
                    delete_post_meta($updateId, "_knawatfibu_url");
                    delete_post_meta($updateId, "IDMUVICORE_Poster");
                    $this->setPoster($updateId, $data['poster']);
                }
            }

            $this->publishPost($updateId);

            return [
                'id'       => $post->ID,
                'title'    => $data["title"],
                'tmdb'     => $post->IDMUVICORE_tmdbID,
                'last_eps' => $data['epsno'] ?? 0,
            ];
        } catch (\Throwable $e) {
            Logger::error("Series update failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function deleteSeries(EpisodeService $episodeService, string $slug): array
    {
        try {
            $post = $this->getSeriesBySlug($slug);
            if (!$post) {
                throw new \RuntimeException("Series slug $slug not found");
            }

            $title = $post->post_title;
            $tmdb_id = $post->IDMUVICORE_tmdbID ?? null;

            if ($tmdb_id) {
                $episodeService->deleteEpisode($tmdb_id);
            }

            $delete = wp_delete_post($post->ID, true);
            if (is_wp_error($delete)) {
                throw new \RuntimeException("Failed delete $title. Error: {$delete->get_error_message()}");
            }

            return ['title' => $title, 'message' => "Success delete series $title"];
        } catch (\Throwable $e) {
            Logger::error("Series delete failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
