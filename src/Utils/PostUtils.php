<?php

namespace Muvinime\Utils;

class PostUtils
{
    public static function getPostByTitle(string $title, string $type): ?object
    {
        global $wpdb;

        $title = str_replace("&amp;", "&", $title);
        $title = str_replace("&", "&amp;", $title);

        $table_name = $wpdb->prefix . 'posts';
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_title = %s AND post_status IN ('publish', 'draft') AND post_type = %s LIMIT 1",
            $wpdb->esc_like($title),
            $type
        );

        $post = $wpdb->get_row($query, \ARRAY_A);
        if (!$post) return null;

        $terms = [];
        foreach (get_object_taxonomies($post) as $taxonomy) {
            $term = wp_get_post_terms($post['ID'], $taxonomy);
            $terms[$taxonomy] = $term;
        }

        $meta = get_post_meta($post['ID']);
        $merge = array_merge($post, $terms, (array) $meta);

        $result = [];
        foreach ($merge as $key => $value) {
            if (\is_array($value) && \count($value) == 1) {
                $result[$key] = current($value);
            } else {
                $result[$key] = $value;
            }
        }

        return (object) $result;
    }

    public static function getPostBySlug(string $slug, string $postType): ?object
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'posts';
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_name = %s AND post_status IN ('publish', 'draft') AND post_type = %s",
            $slug,
            $postType
        );

        $fetch = $wpdb->get_results($query);
        if (!@$fetch[0] || !@$fetch[0]->ID) return null;

        $post = $fetch[0];
        $terms = [];

        foreach (get_object_taxonomies($post) as $taxonomy) {
            $term = wp_get_post_terms($post->ID, $taxonomy);
            $terms[$taxonomy] = $term;
        }

        $meta = get_post_meta($post->ID);
        $merge = array_merge((array) $post, (array) $terms, (array) $meta);

        $result = [];
        foreach ($merge as $key => $value) {
            if (\is_array($value) && \count($value) == 1) {
                $result[$key] = current($value);
            } else {
                $result[$key] = $value;
            }
        }

        return (object) $result;
    }

    public static function getPostByTmdb($tmdbId): ?array
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
            'IDMUVICORE_tmdbID',
            $tmdbId
        );

        $posts = $wpdb->get_col($query);
        if (!$posts || \count($posts) === 0) return null;

        return $posts;
    }

    public static function getPostById($id): ?array
    {
        $post = get_post($id);
        if (!$post || empty($post)) return null;

        $terms = [];
        foreach (get_object_taxonomies($post) as $taxonomy) {
            $term = wp_get_post_terms($post->ID, $taxonomy);
            $terms[$taxonomy] = $term;
        }
        $meta = get_post_meta($id);
        $merge = array_merge((array) $post, (array) $terms, (array) $meta);

        return $merge;
    }

    public static function createOrUpdateMeta($post, string $callback, string $key, $value): void
    {
        if ($value && isset($value) && !empty($value)) {
            if ($callback === 'add_post_meta') {
                update_post_meta($post, $key, $value);
            } elseif ($callback === 'wp_set_post_terms') {
                wp_set_post_terms($post, $value, $key);
            }
        }
    }
}
