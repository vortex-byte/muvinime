<?php

namespace Muvinime\Rest;

use Muvinime\Utils\PostUtils;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

class PostController
{
    public function getPostInfo(WP_REST_Request $request): WP_REST_Response
    {
        $postId = $request->get_param('post_id');
        $post = PostUtils::getPostById($postId);

        return new WP_REST_Response($post, 200);
    }

    public function getPostsList(WP_REST_Request $request)
    {
        try {
            global $wpdb;

            $page     = max(1, \intval($request->get_param('page')));
            $per_page = max(1, \intval($request->get_param('per_page')));
            $search   = $request->get_param('search') ? rawurldecode($request->get_param('search')) : '';

            $tkArgs = [
                'per_page' => $per_page,
                'page'     => $page,
                'search'   => $search
            ];
            $transientKey = 'dashboard_custom_query_' . md5(serialize($tkArgs));

            if (($cached = get_transient($transientKey)) !== false) {
                return new WP_REST_Response($cached, 200);
            }

            $offset = ($page - 1) * $per_page;
            $where  = "p.post_type IN ('tv', 'post') AND p.post_status IN ('publish', 'draft')";
            $params = [];

            if ($search) {
                $searchLike = '%' . $wpdb->esc_like($search) . '%';
                $where .= " AND p.post_title LIKE %s";
                $params[] = $searchLike;
            }

            $query = "
                SELECT SQL_CALC_FOUND_ROWS 
                       p.ID, 
                       p.post_title, 
                       p.post_type, 
                       p.post_status,
                       p.post_modified,
                       pm1.meta_value AS knawat_url,
                       pm2.meta_value AS tmdbID,
                       pm3.meta_value AS player_title,
                       pm4.meta_value AS player_value,
                       pm5.meta_value AS tmdb_rating,
                       pm6.meta_value AS numb_episode
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_knawatfibu_url'
                LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = 'IDMUVICORE_tmdbID'
                LEFT JOIN {$wpdb->postmeta} pm3 ON pm3.post_id = p.ID AND pm3.meta_key = 'IDMUVICORE_Title_Player1'
                LEFT JOIN {$wpdb->postmeta} pm4 ON pm4.post_id = p.ID AND pm4.meta_key = 'IDMUVICORE_Player1'
                LEFT JOIN {$wpdb->postmeta} pm5 ON pm5.post_id = p.ID AND pm5.meta_key = 'IDMUVICORE_tmdbRating'
                LEFT JOIN {$wpdb->postmeta} pm6 ON pm6.post_id = p.ID AND pm6.meta_key = 'IDMUVICORE_Numbepisode'
                WHERE $where
                ORDER BY p.post_date DESC
                LIMIT %d OFFSET %d
            ";

            $params[] = $per_page;
            $params[] = $offset;

            $sql = $wpdb->prepare($query, ...$params);
            $posts = $wpdb->get_results($sql);
            $total_rows = $wpdb->get_var("SELECT FOUND_ROWS()");
            $total_pages = ceil($total_rows / $per_page);

            $results = [
                'total_rows'   => $total_rows,
                'total_pages'  => $total_pages,
                'current_page' => $page,
                'per_page'     => $per_page,
                'data'         => []
            ];

            foreach ($posts as $post) {
                $link = get_permalink($post->ID);
                $healthCacheKey = 'health_status_' . $post->ID;
                $healthCheck = get_transient($healthCacheKey);

                if ($healthCheck === false) {
                    $healthCheck = 1;

                    if (!empty($post->knawat_url)) {
                        $headers = @get_headers($post->knawat_url);
                        if ($headers) {
                            $status_code = \intval(substr($headers[0], 9, 3));
                            $healthCheck = ($status_code >= 400) ? 2 : 3;
                        }
                    } else {
                        $healthCheck = 3;
                    }

                    if ($post->post_type === 'tv') {
                        $episode = get_posts([
                            'post_type'      => 'episode',
                            'meta_key'       => 'IDMUVICORE_tmdbID',
                            'meta_value'     => $post->tmdbID,
                            'posts_per_page' => 1,
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                            'fields'         => 'ids'
                        ]);

                        $episodeId = !empty($episode) ? $episode[0] : null;
                        if ($episodeId) {
                            $meta = get_post_meta($episodeId);
                            $post->player_title = $meta['IDMUVICORE_Title_Player1'][0] ?? '';
                            $post->player_value = $meta['IDMUVICORE_Player1'][0] ?? '';
                        }
                    }

                    $playerBase = rtrim(get_option('muvi_player_base_url', 'https://playsobat.xyz'), '/');

                    if ($post->player_title !== 'MULTI SERVER') {
                        $healthCheck = 1;
                    } else {
                        $escapedBase = preg_quote($playerBase, '/');
                        preg_match('/src=(["\'])(' . $escapedBase . '\/[^"\']*)\1/i', $post->player_value ?? '', $matches);

                        if (isset($matches[2])) {
                            $healthCheck = 3;
                            $playerSlug = preg_replace("/.*\//", "", $matches[2]);
                            $getPlayer = wp_remote_get("$playerBase/api/get?key=kDcZqfeIoD&slug=$playerSlug", [
                                'timeout' => 5,
                                'headers' => ['Origin' => 'https://moflix.test']
                            ]);

                            if (is_wp_error($getPlayer)) {
                                $healthCheck = 2;
                            } else {
                                $json = wp_remote_retrieve_body($getPlayer);
                                $data = json_decode($json, true);
                                if (!isset($data['status']) || $data['status'] !== true) {
                                    $healthCheck = 2;
                                } else {
                                    $player = array_values($data['data']);
                                    if (\count($player) <= 1) {
                                        $healthCheck = 2;
                                    } elseif (\count($player) == 1 && strpos($player[0], 'player') !== false) {
                                        $healthCheck = 2;
                                    } elseif (strpos($player[0], 'playcrot') !== false) {
                                        $healthCheck = 1;
                                    }
                                }
                            }
                        } else {
                            $healthCheck = 1;
                        }
                    }

                    set_transient($healthCacheKey, $healthCheck, 6 * HOUR_IN_SECONDS);
                }

                $results['data'][] = array_merge((array) $post, [
                    'link'   => $link,
                    'health' => $healthCheck
                ]);
            }

            set_transient($transientKey, $results, DAY_IN_SECONDS);

            $optionKey = 'dashboard_custom_query_registry';
            $registry  = get_option($optionKey, []);
            $registry[] = $transientKey;
            $registry = array_unique($registry);
            update_option($optionKey, $registry);

            return new WP_REST_Response($results, 200);
        } catch (\Throwable $e) {
            return new WP_Error('error_get_posts_list', $e->getMessage());
        }
    }

    public function getDraft(): WP_REST_Response
    {
        $args = [
            'post_type'   => 'tv',
            'post_status' => 'draft',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'ASC',
            'date_query'     => [
                [
                    'year'   => 2025,
                    'column' => 'post_modified',
                ]
            ]
        ];

        $draft = new WP_Query($args);
        return new WP_REST_Response($draft->posts);
    }

    public function search(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $keyword = $request->get_param('s');
        if (!$keyword) {
            return new WP_REST_Response('Query is required', 400);
        }

        $keyword = rawurldecode($keyword);

        $table_name = $wpdb->prefix . 'posts';
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_title LIKE %s AND post_status IN ('publish', 'draft') AND post_type IN ('tv', 'post')",
            '%' . $wpdb->esc_like($keyword) . '%'
        );

        $fetch = $wpdb->get_results($query);

        if (!$fetch || \count($fetch) === 0) {
            return new WP_REST_Response('NOT FOUND');
        }

        $hostname = get_site_url();
        $posts = [];
        foreach ($fetch as $post) {
            $meta = get_post_meta($post->ID);
            $posterUrl = $meta['IDMUVICORE_Poster'][0] ?? '';
            if ($posterUrl) {
                $headers = @get_headers($posterUrl);
                $status_code = $headers ? substr($headers[0], 9, 3) : '500';
                $type = $post->post_type == 'tv' ? 'tv/' : '';
                if (!$status_code || $status_code >= 400) {
                    $posts[] = [$post->post_title => "$hostname/{$type}{$post->post_name}"];
                }
            }
        }

        return new WP_REST_Response($posts);
    }
}
