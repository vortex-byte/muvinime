<?php

namespace Muvinime\Services;

use Muvinime\Utils\Common;
use Muvinime\Utils\Logger;
use Muvinime\Utils\PostUtils;

class MovieService extends BasePostService
{
    const POST_TYPE = 'post';

    public function upload(array $data): array
    {
        try {
            $title = "{$data['title']} ({$data['year']})";

            if (!isset($data['stream'])) {
                return ["cancel" => "Missing Parameter 'stream' for $title"];
            }

            if (isset($data['update']) && $data['update']) {
                return $this->update($data);
            }

            $this->deleteExistingPost($title, self::POST_TYPE);

            $postId = $this->createPost($data, self::POST_TYPE, $title);

            $tmdb = $this->generateUniqueTmdbId((int)($data['tmdb'] ?? 0));

            $this->setMeta($postId, [
                'IDMUVICORE_tmdbID'          => $tmdb,
                'IDMUVICORE_Runtime'         => $data['duration'] ?? '',
                'IDMUVICORE_Year'            => $data['year'] ?? '',
                'IDMUVICORE_Rated'           => $data['score'] ?? '',
                'IDMUVICORE_Title'           => $data['title'] ?? '',
                'IDMUVICORE_tmdbVotes'       => $data['voters'] ?? '',
                'IDMUVICORE_tmdbRating'      => $data['score'] ?? '',
                'IDMUVICORE_Released'        => $data['released'] ?? '',
                'IDMUVICORE_Title_Player1'   => 'MULTI SERVER',
                'IDMUVICORE_Player1'         => Common::setIframe($data['stream'], 'MULTI SERVER'),
                'IDMUVICORE_Title_Download1' => 'DOWNLOAD',
                'IDMUVICORE_Download1'       => $data['download'] ?? '',
            ]);

            $this->setTerms($postId, [
                'post_tag' => $data['tags'] ?? '',
                'muviyear' => $data['year'] ?? '',
            ]);

            $this->setCategories($postId, $data['genres'] ?? null);
            $this->setPoster($postId, $data['poster'] ?? null);
            $this->publishPost($postId);

            return ['id' => $postId, 'title' => $data["title"]];
        } catch (\Throwable $e) {
            Logger::error("Movie upload failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function update(array $data): array
    {
        try {
            $title = "{$data['title']} ({$data['year']})";
            $updateUrl = $data['update'];
            $path = parse_url($updateUrl, PHP_URL_PATH);
            $split = explode('/', $path ?? '');

            if (!$path || !isset($split[1]) || $split[1] == 'tv') {
                return ["error" => "Invalid URL post for update movie $title"];
            }

            $post = $this->findBySlug($split[1], self::POST_TYPE);
            if (!$post) {
                return ["error" => "Movie {$split[1]} not found"];
            }

            $postId = $this->updatePost($post->ID, [
                'title'    => $title,
                'synopsis' => $data["synopsis"] ?? '',
            ]);

            $tmdbId = (int)($data['tmdb'] ?? 0);
            if ($tmdbId) {
                $existing = PostUtils::getPostByTmdb($tmdbId);
                if ($existing && \count($existing) > 0) {
                    $tmdbId += substr(time(), -4, 4) + rand(10, 100);
                }
            }

            $this->setMeta($postId, [
                'IDMUVICORE_tmdbID'          => $tmdbId,
                'IDMUVICORE_Runtime'         => $data['duration'] ?? '',
                'IDMUVICORE_Year'            => $data['year'] ?? '',
                'IDMUVICORE_Rated'           => $data['score'] ?? '',
                'IDMUVICORE_Title'           => $data['title'] ?? '',
                'IDMUVICORE_tmdbVotes'       => $data['voters'] ?? '',
                'IDMUVICORE_tmdbRating'      => $data['score'] ?? '',
                'IDMUVICORE_Released'        => $data['released'] ?? '',
                'IDMUVICORE_Title_Player1'   => 'MULTI SERVER',
                'IDMUVICORE_Player1'         => Common::setIframe($data['stream'], 'MULTI SERVER'),
                'IDMUVICORE_Title_Download1' => 'DOWNLOAD',
                'IDMUVICORE_Download1'       => $data['download'] ?? '',
            ]);

            $this->setTerms($postId, [
                'post_tag' => $data['tags'] ?? '',
                'muviyear' => $data['year'] ?? '',
            ]);

            $this->setCategories($postId, $data['genres'] ?? null);
            $this->setPoster($postId, $data['poster'] ?? null);
            $this->publishPost($postId);

            return ['id' => $postId, 'title' => $data["title"]];
        } catch (\Throwable $e) {
            Logger::error("Movie update failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function deleteMovie(string $slug): array
    {
        try {
            return $this->deletePost($slug, self::POST_TYPE);
        } catch (\Throwable $e) {
            Logger::error("Movie delete failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
