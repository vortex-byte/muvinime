<?php

namespace Muvinime\Services;

use Muvinime\Utils\Common;
use Muvinime\Utils\Logger;
use Muvinime\Utils\PostUtils;

abstract class BasePostService
{
    protected function findByTitle(string $title, string $postType): ?object
    {
        return PostUtils::getPostByTitle($title, $postType);
    }

    protected function findBySlug(string $slug, string $postType): ?object
    {
        return PostUtils::getPostBySlug($slug, $postType);
    }

    protected function createPost(array $data, string $postType, ?string $postTitle): int
    {
        $postTitle = $postTitle ?? $data['title'];
        $postSlug = $data['slug'] ?? Common::slugify($postTitle);

        $args = [
            "post_title"   => $postTitle,
            "post_type"    => $postType,
            "post_name"    => $postSlug,
            "post_content" => $data["synopsis"] ?? '',
            "post_status"  => "draft",
            "post_author"  => \defined('MVNIME_USER_ID') ? MVNIME_USER_ID : 1,
        ];

        $attempts = 1;
        while (true) {
            $post = wp_insert_post($args);

            if (!is_wp_error($post)) {
                break;
            }

            $attempts++;
            if ($attempts >= 3) {
                throw new \RuntimeException(
                    "Failed to create post: " . $post->get_error_message()
                );
            }
            sleep(30);
        }

        return $post;
    }

    protected function updatePost(int $postId, array $data): int
    {
        $args = [
            "ID"           => $postId,
            "post_title"   => $data["title"],
            "post_content" => $data["synopsis"] ?? '',
            "post_status"  => "draft",
        ];

        $attempts = 1;
        while (true) {
            $update = wp_update_post($args);

            if (!is_wp_error($update)) {
                break;
            }

            $attempts++;
            if ($attempts >= 3) {
                throw new \RuntimeException(
                    "Failed to update post: " . $update->get_error_message()
                );
            }
            sleep(30);
        }

        return $update;
    }

    protected function deletePost(string $slug, string $postType): array
    {
        $post = $this->findBySlug($slug, $postType);
        if (!$post) {
            throw new \RuntimeException("Post slug $slug not found");
        }

        $title = $post->post_title;
        $delete = wp_delete_post($post->ID, true);

        if (is_wp_error($delete)) {
            throw new \RuntimeException("Failed delete $title. Error: {$delete->get_error_message()}");
        }

        return ['title' => $title, 'message' => "$title deleted"];
    }

    protected function generateUniqueTmdbId(int $tmdb): int
    {
        while (true) {
            $find = PostUtils::getPostByTMDB($tmdb);
            if (!$find) {
                break;
            }
            $tmdb += substr(time(), -4, 4) + rand(10, 100);
        }
        return $tmdb;
    }

    protected function setMeta(int $postId, array $metaPairs): void
    {
        foreach ($metaPairs as $key => $value) {
            if ($value !== null && $value !== '') {
                PostUtils::createOrUpdateMeta($postId, 'add_post_meta', $key, $value);
            }
        }
    }

    protected function setTerms(int $postId, array $termPairs): void
    {
        foreach ($termPairs as $taxonomy => $value) {
            if ($value !== null && $value !== '') {
                PostUtils::createOrUpdateMeta($postId, 'wp_set_post_terms', $taxonomy, $value);
            }
        }
    }

    protected function setCategories(int $postId, ?array $genres): void
    {
        if (!$genres || empty($genres)) return;

        $categories_id = [];
        foreach ($genres as $category) {
            $existing = term_exists($category, 'category');
            $category_id = \intval(\is_array($existing) ? $existing['term_id'] : 0);
            if (!$category_id) {
                $category_id = wp_create_category($category);
            }
            $categories_id[] = $category_id;
        }
        wp_set_post_categories($postId, $categories_id);
    }

    protected function setPoster(int $postId, ?string $posterUrl): void
    {
        if ($posterUrl) {
            Common::setThumbnail($posterUrl, $postId);
        }
    }

    protected function publishPost(int $postId): void
    {
        wp_update_post(['ID' => $postId, 'post_status' => 'publish']);
    }

    protected function deleteExistingPost(string $title, string $type): void
    {
        try {
            $existing = $this->findByTitle($title, $type);
            if (!$existing) return;

            wp_delete_post($existing->ID);
            sleep(1);
            wp_delete_post($existing->ID, true);
            $encodedTitle = urlencode($existing->post_title);
            $grabApiUrl = get_option('muvi_grab_api_url', 'https://grabapi.xyz');
            wp_remote_get(
                "$grabApiUrl/wp-json/muvibot/v1/delete-title/$type/$encodedTitle",
                ['timeout' => 30, 'sslverify' => false]
            );
        } catch (\Throwable $e) {
            Logger::warning("deleteExistingPost: " . $e->getMessage());
        }
    }
}
