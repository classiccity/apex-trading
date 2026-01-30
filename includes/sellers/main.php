<?php
/**
 * Sellers content model, state routing, and data sync.
 *
 * The sync replaces the former Python script:
 * - Fetches state-level and single-seller JSON feeds.
 * - Normalizes/merges sellers.
 * - Saves them as posts with a state taxonomy for URLs like
 *   /wholesale-cannabis-sellers/colorado/14er-boulder.
 *
 * Configure the remote sources with constants in wp-config.php:
 *  - APEX_SELLER_SOURCE_STATES e.g. https://.../sellers?state=
 *  - APEX_SELLER_SOURCE_SINGLE e.g. https://.../single?slug=
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

// Base identifiers.
if (! defined('APEX_SELLER_POST_TYPE')) {
    define('APEX_SELLER_POST_TYPE', 'apex_seller');
}
if (! defined('APEX_SELLER_TAXONOMY')) {
    define('APEX_SELLER_TAXONOMY', 'apex_seller_state');
}
if (! defined('APEX_SELLER_BASE_SLUG')) {
    define('APEX_SELLER_BASE_SLUG', 'wholesale-cannabis-sellers');
}
if (! defined('APEX_SELLER_CRON_HOOK')) {
    define('APEX_SELLER_CRON_HOOK', 'apex_trading_sync_sellers');
}
if (! defined('APEX_SELLER_STATE_CRON_HOOK')) {
    define('APEX_SELLER_STATE_CRON_HOOK', 'apex_trading_sync_seller_state');
}

/**
 * Conditional debug logger for the seller sync.
 *
 * Enable by defining APEX_SELLER_SYNC_DEBUG as true in wp-config.php.
 */
function apex_trading_seller_log(string $message, array $context = []): void
{
    if (! defined('APEX_SELLER_SYNC_DEBUG') || ! APEX_SELLER_SYNC_DEBUG) {
        return;
    }

    $line = 'apex-seller debug: ' . $message;

    if (! empty($context)) {
        $line .= ' ' . wp_json_encode($context);
    }

    error_log($line);
}

add_action('init', 'apex_trading_register_sellers');
add_filter('post_type_link', 'apex_trading_filter_seller_permalink', 10, 2);
add_action('after_switch_theme', 'apex_trading_flush_seller_rewrite');

// Cron wiring.
add_action('init', 'apex_trading_schedule_seller_sync');
add_action(APEX_SELLER_CRON_HOOK, 'apex_trading_run_seller_sync');
add_action(APEX_SELLER_STATE_CRON_HOOK, 'apex_trading_run_seller_state_sync', 10, 1);

/**
 * Register the seller post type and state taxonomy.
 */
function apex_trading_register_sellers(): void
{
    $labels = [
        'name'               => __('Sellers', 'ccc-primary-theme'),
        'singular_name'      => __('Seller', 'ccc-primary-theme'),
        'add_new_item'       => __('Add New Seller', 'ccc-primary-theme'),
        'edit_item'          => __('Edit Seller', 'ccc-primary-theme'),
        'view_item'          => __('View Seller', 'ccc-primary-theme'),
        'search_items'       => __('Search Sellers', 'ccc-primary-theme'),
        'not_found'          => __('No sellers found.', 'ccc-primary-theme'),
        'menu_name'          => __('Sellers', 'ccc-primary-theme'),
    ];

    register_post_type(
        APEX_SELLER_POST_TYPE,
        [
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => false,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-store',
            'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
            'rewrite'      => [
                'slug'       => APEX_SELLER_BASE_SLUG . '/%' . APEX_SELLER_TAXONOMY . '%',
                'with_front' => false,
                'feeds'      => false,
            ],
        ]
    );

    register_taxonomy(
        APEX_SELLER_TAXONOMY,
        [APEX_SELLER_POST_TYPE],
        [
            'label'        => __('States', 'ccc-primary-theme'),
            'rewrite'      => [
                'slug'       => APEX_SELLER_BASE_SLUG,
                'with_front' => false,
            ],
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ]
    );
}

/**
 * Replace the taxonomy placeholder with the state slug in seller permalinks.
 */
function apex_trading_filter_seller_permalink(string $permalink, WP_Post $post): string
{
    if ($post->post_type !== APEX_SELLER_POST_TYPE) {
        return $permalink;
    }

    $terms = get_the_terms($post, APEX_SELLER_TAXONOMY);
    $state_slug = (! empty($terms) && ! is_wp_error($terms))
        ? $terms[0]->slug
        : 'state';

    return str_replace('%' . APEX_SELLER_TAXONOMY . '%', $state_slug, $permalink);
}

/**
 * Flush rewrites so the custom URLs work after the theme is activated.
 */
function apex_trading_flush_seller_rewrite(): void
{
    apex_trading_register_sellers();
    flush_rewrite_rules();
}

/**
 * Ensure the cron event exists (defaults to hourly).
 */
function apex_trading_schedule_seller_sync(): void
{
    if (! wp_next_scheduled(APEX_SELLER_CRON_HOOK)) {
        wp_schedule_event(time() + 300, 'weekly', APEX_SELLER_CRON_HOOK);
    }
}

/**
 * Fetch and persist sellers from the configured remote APIs.
 *
 * Flow:
 * - Queue one cron job per state to avoid long single runs.
 */
function apex_trading_run_seller_sync(): void
{
    // Hard log to ensure we see execution even if WP_DEBUG_LOG is redirected.
    error_log('apex-seller cron fired at ' . gmdate('c'));

    // Allow enough time to queue jobs.
    if (function_exists('set_time_limit')) {
        @set_time_limit(600);
    }

    $state_base = defined('APEX_SELLER_SOURCE_STATES') ? APEX_SELLER_SOURCE_STATES : '';

    if (empty($state_base)) {
        apex_trading_seller_log('sync aborted: state endpoint missing');
        return;
    }

    // Log existing state terms (slug => id) before we queue jobs.
    $existing_terms = [];
    $terms = get_terms(
        [
            'taxonomy'   => APEX_SELLER_TAXONOMY,
            'hide_empty' => false,
            'fields'     => 'all',
        ]
    );
    if (! is_wp_error($terms)) {
        foreach ($terms as $term) {
            $existing_terms[$term->slug] = $term->term_id;
        }
    }
    apex_trading_seller_log('existing state terms before queue', $existing_terms);

    // Clear legacy batch cursor if it exists.
    delete_option('apex_seller_sync_cursor');

    $states = array_keys(apex_trading_state_codes());
    $queued = 0;
    $offset = 0;

    foreach ($states as $state_code) {
        if (wp_next_scheduled(APEX_SELLER_STATE_CRON_HOOK, [$state_code])) {
            continue;
        }

        wp_schedule_single_event(time() + $offset, APEX_SELLER_STATE_CRON_HOOK, [$state_code]);
        $queued++;

        // Stagger calls to avoid hitting remote/host limits.
        $offset += 15;
    }

    apex_trading_seller_log('queued state syncs', ['queued' => $queued, 'total_states' => count($states)]);
}

/**
 * Manual trigger endpoint (authenticated) to run the sync via URL.
 *
 * Hit /wp-admin/admin-post.php?action=apex_seller_sync while logged in.
 */
function apex_trading_run_seller_sync_manual(): void
{
    $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';

    if (! empty($state)) {
        apex_trading_run_seller_state_sync($state);
        wp_die('apex seller state sync complete: ' . esc_html($state));
    }

    apex_trading_run_seller_sync();
    wp_die('apex seller sync queued');
}
add_action('admin_post_apex_seller_sync', 'apex_trading_run_seller_sync_manual');

/**
 * Manual purge endpoint to delete all sellers and state terms.
 *
 * Hit /wp-admin/admin-post.php?action=apex_seller_purge while logged in as an admin.
 */
function apex_trading_purge_sellers_manual(): void
{
    if (! current_user_can('manage_options')) {
        wp_die('Unauthorized', '', 403);
    }

    apex_trading_purge_sellers_and_states();
    wp_die('apex sellers and states purged');
}
add_action('admin_post_apex_seller_purge', 'apex_trading_purge_sellers_manual');

/**
 * Process a single state's sellers.
 *
 * @param string $state_code Two-letter state code.
 */
function apex_trading_run_seller_state_sync(string $state_code): void
{
    $state_code = strtoupper(trim($state_code));

    if (empty($state_code)) {
        return;
    }

    if (function_exists('set_time_limit')) {
        @set_time_limit(900);
    }

    $state_base = defined('APEX_SELLER_SOURCE_STATES') ? APEX_SELLER_SOURCE_STATES : '';

    if (empty($state_base)) {
        apex_trading_seller_log('state sync aborted: state endpoint missing', ['state' => $state_code]);
        return;
    }

    $single_base = defined('APEX_SELLER_SOURCE_SINGLE') ? APEX_SELLER_SOURCE_SINGLE : '';
    $merged = [];

    apex_trading_seller_log('state job start', ['state' => $state_code]);

    try {
        $state_map = apex_trading_state_map();
        $job_state_name = $state_map[$state_code] ?? $state_code;
        $job_state_slug = sanitize_title($job_state_name);

        $rows = apex_trading_fetch_sellers_for_state($state_base, $state_code);
        apex_trading_seller_log('fetched state', ['state' => $state_code, 'count' => count($rows)]);

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (empty($row['state'])) {
                $row['state'] = $state_code;
            }
            if (empty($row['state_code'])) {
                $row['state_code'] = $state_code;
            }

            if (! empty($single_base)) {
                $slug = $row['slug'] ?? $row['id'] ?? '';

                if (! empty($slug)) {
                    $detail = apex_trading_fetch_single_seller($single_base, $slug);

                    if (! empty($detail) && is_array($detail)) {
                        $row = array_merge($row, $detail);
                    }
                }
            }

            $normalized = apex_trading_normalize_seller($row, $state_code);

            if (empty($normalized)) {
                apex_trading_seller_log('skipped record missing required fields', ['state' => $state_code]);
                continue;
            }

            $key = $normalized['slug'];

            if (! isset($merged[$key])) {
                $merged[$key] = $normalized;
                continue;
            }

            $merged[$key] = array_merge(
                $merged[$key],
                array_filter(
                    $normalized,
                    static fn($value) => $value !== null && $value !== ''
                )
            );

            $merged[$key]['raw'] = array_merge(
                $merged[$key]['raw'] ?? [],
                $normalized['raw'] ?? []
            );
        }
    } catch (Throwable $e) {
        error_log('apex-seller sync: state failure ' . $state_code . ' ' . $e->getMessage());
        apex_trading_seller_log('state exception', ['state' => $state_code, 'message' => $e->getMessage()]);
        return;
    }

    apex_trading_seller_log('state merge complete', ['state' => $state_code, 'total' => count($merged)]);

    foreach ($merged as $seller) {
        // Force consistent state values for term assignment.
        $seller['state'] = $state_code;
        $seller['state_code'] = $state_code;
        $seller['state_name'] = $job_state_name;
        $seller['state_slug'] = $job_state_slug;

        $post_id = apex_trading_upsert_seller_post($seller, $state_code, $job_state_name, $job_state_slug);
        apex_trading_seller_log('upserted seller', ['state' => $state_code, 'slug' => $seller['slug'], 'post_id' => $post_id]);
    }

    apex_trading_seller_log('state job finished', ['state' => $state_code, 'total' => count($merged)]);
}

/**
 * Delete all seller posts and state taxonomy terms.
 */
function apex_trading_purge_sellers_and_states(): void
{
    // Delete seller posts and their thumbnails.
    $posts = get_posts(
        [
            'post_type'      => APEX_SELLER_POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]
    );

    foreach ($posts as $post_id) {
        $thumb_id = get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            wp_delete_attachment($thumb_id, true);
        }
        wp_delete_post($post_id, true);
    }

    // Delete state terms.
    $terms = get_terms(
        [
            'taxonomy'   => APEX_SELLER_TAXONOMY,
            'hide_empty' => false,
        ]
    );

    if (! is_wp_error($terms)) {
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, APEX_SELLER_TAXONOMY);
        }
    }

    // Clear any legacy cursors.
    delete_option('apex_seller_sync_cursor');
}

/**
 * Get sellers for a specific state code from the state endpoint.
 *
 * @param string $base_url  Base URL with trailing query value placeholder.
 * @param string $state_code Two-letter code.
 *
 * @return array
 */
function apex_trading_fetch_sellers_for_state(string $base_url, string $state_code): array
{
    $url = $base_url . rawurlencode($state_code);

    return apex_trading_fetch_json($url);
}

/**
 * Fetch extra seller details by slug from the single endpoint.
 *
 * @param string $base_url Base URL with trailing slug placeholder.
 * @param string $slug     Seller slug.
 *
 * @return array
 */
function apex_trading_fetch_single_seller(string $base_url, string $slug): array
{
    $url = $base_url . rawurlencode($slug);

    $data = apex_trading_fetch_json($url);

    if (isset($data[0]) && is_array($data[0])) {
        return $data[0];
    }

    return $data;
}

/**
 * Download and decode JSON from a URL.
 *
 * @param string $url Remote URL.
 *
 * @return array
 */
function apex_trading_fetch_json(string $url): array
{
    $args = [
        // Allow slow endpoints; WP_HTTP max varies by host, so keep generous.
        'timeout' => 300,
    ];

    $attempts = 3;
    $delay = 1;

    for ($i = 1; $i <= $attempts; $i++) {
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            apex_trading_seller_log('request failed', ['url' => $url, 'error' => $response->get_error_message(), 'attempt' => $i]);
            if ($i === $attempts) {
                error_log('apex-seller sync: ' . $response->get_error_message());
                return [];
            }
        } else {
            $status = wp_remote_retrieve_response_code($response);
            if ($status >= 400) {
                apex_trading_seller_log('http error', ['url' => $url, 'status' => $status, 'attempt' => $i]);
                if ($i === $attempts) {
                    error_log('apex-seller sync: HTTP ' . $status . ' for ' . $url);
                    return [];
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (! is_array($data)) {
                    apex_trading_seller_log('invalid json', ['url' => $url, 'attempt' => $i]);
                    if ($i === $attempts) {
                        error_log('apex-seller sync: invalid JSON from ' . $url);
                        return [];
                    }
                } else {
                    if (isset($data['data']) && is_array($data['data'])) {
                        $data = $data['data'];
                    }

                    return $data;
                }
            }
        }

        sleep($delay);
        $delay *= 2;
    }

    return [];
}

/**
 * Normalize a single seller record to a consistent shape.
 *
 * @param array       $record        Raw record from a source.
 * @param string|null $default_state State fallback if absent.
 *
 * @return array|null
 */
function apex_trading_normalize_seller(array $record, ?string $default_state = null): ?array
{
    $name = $record['name'];
    // Force state from the job context; ignore upstream values entirely.
    $state = strtoupper((string) $default_state);
    $state_map = apex_trading_state_map();
    $state_name = $state_map[$state] ?? $state;

    $slug = $record['slug'] ?? sanitize_title($name);
    $city = $record['city'] ?? ($record['address']['city'] ?? null);
    $website = $record['website'] ?? $record['url'] ?? $record['domain'] ?? '';
    $profile_url = $record['profileUrl'] ?? $record['profile_url'] ?? '';
    $description = $record['description'] ?? ($record['Desc'] ?? ($record['desc'] ?? ($record['about'] ?? '')));
    $logo_url = $record['logo'] ?? $record['logo_file'] ?? $record['logoUrl'] ?? '';

    return apply_filters(
        'apex_trading_normalize_seller',
        [
            'name'        => (string) $name,
            'state'       => $state,
            'state_code'  => $state,
            'state_name'  => $state_name,
            'slug'        => $slug,
            'city'        => $city,
            'website'     => $website,
            'profile_url' => $profile_url,
            'logo_url'    => $logo_url,
            'description' => $description,
            'raw'         => $record,
        ],
        $record
    );
}

/**
 * Upsert a seller post and assign its state term.
 *
 * @param array  $seller         Normalized seller data.
 * @param string $job_state      Two-letter state code from the job context.
 * @param string $job_state_name State name from the job context.
 * @param string $job_state_slug State slug from the job context.
 *
 * @return int Post ID.
 */
function apex_trading_upsert_seller_post(array $seller, string $job_state, string $job_state_name, string $job_state_slug): int
{
    // Use state values derived from the job context only.
    $state_code = strtoupper($job_state);
    $state_name = $job_state_name;
    $state_slug = $job_state_slug;

    $post_id = apex_trading_find_seller_post_id($seller['slug']);

    $postarr = [
        'post_title'   => wp_strip_all_tags($seller['name']),
        'post_name'    => sanitize_title($seller['slug'] ?: $seller['name']),
        'post_type'    => APEX_SELLER_POST_TYPE,
        'post_status'  => 'publish',
        'post_excerpt' => ! empty($seller['description'])
            ? wp_trim_words(wp_strip_all_tags($seller['description']), 40)
            : '',
        'post_content' => ! empty($seller['description'])
            ? wp_kses_post($seller['description'])
            : '',
    ];

    if ($post_id) {
        $postarr['ID'] = $post_id;
        $post_id = wp_update_post($postarr);
    } else {
        $post_id = wp_insert_post($postarr);
    }

    if (is_wp_error($post_id)) {
        error_log('apex-seller sync: ' . $post_id->get_error_message());
        return 0;
    }

    // Look up by slug to ensure we reuse an existing term even if names were altered.
    apex_trading_seller_log('state term lookup', [
        'post_id'    => $post_id,
        'seller_slug'=> $seller['slug'],
        'state'      => $state_name,
        'state_slug' => $state_slug,
    ]);
    $term = term_exists($state_slug, APEX_SELLER_TAXONOMY);

    if (! $term) {
        $term = wp_insert_term(
            $state_name,
            APEX_SELLER_TAXONOMY,
            ['slug' => $state_slug]
        );
        apex_trading_seller_log('state added', ['state slug' => $state_slug, 'state name' => $state_name, 'taxonomy' => APEX_SELLER_TAXONOMY]);
    }

    if (! is_wp_error($term)) {
        $term_id = is_array($term) ? ($term['term_id'] ?? $term['term_taxonomy_id'] ?? 0) : $term;
        $term_id = (int) $term_id;
        $term_tax_id = is_array($term) ? (int) ($term['term_taxonomy_id'] ?? 0) : 0;

        if ($term_id) {
            wp_set_post_terms($post_id, [$term_id], APEX_SELLER_TAXONOMY, false);
            apex_trading_seller_log('state term attached', [
                'post_id'          => $post_id,
                'slug'             => $seller['slug'],
                'state'            => $state_name,
                'state_slug'       => $state_slug,
                'term_id'          => $term_id,
                'term_taxonomy_id' => $term_tax_id,
                'taxonomy'         => APEX_SELLER_TAXONOMY,
            ]);
        } else {
            apex_trading_seller_log('state term missing id', [
                'post_id'    => $post_id,
                'slug'       => $seller['slug'],
                'state'      => $state_name,
                'state_slug' => $state_slug,
                'term_raw'   => $term,
            ]);
        }
    } else {
        apex_trading_seller_log('state term error', [
            'post_id'    => $post_id,
            'slug'       => $seller['slug'],
            'state'      => $state_name,
            'state_slug' => $state_slug,
            'error'      => $term->get_error_message(),
        ]);
    }

    update_post_meta($post_id, 'apex_seller_state', $state_name);

    if (! empty($seller['city'])) {
        update_post_meta($post_id, 'apex_seller_city', $seller['city']);
    } else {
        delete_post_meta($post_id, 'apex_seller_city');
    }

    if (! empty($seller['website'])) {
        update_post_meta($post_id, 'apex_seller_website', esc_url_raw($seller['website']));
    } else {
        delete_post_meta($post_id, 'apex_seller_website');
    }

    if (! empty($seller['profile_url'])) {
        update_post_meta($post_id, 'apex_seller_profile_url', esc_url_raw($seller['profile_url']));
    } else {
        delete_post_meta($post_id, 'apex_seller_profile_url');
    }

    if (! empty($seller['raw'])) {
        update_post_meta($post_id, 'apex_seller_raw', $seller['raw']);
    }

    if (! empty($seller['logo_url'])) {
        apex_trading_set_seller_logo($post_id, $seller['logo_url']);
    }

    return (int) $post_id;
}

/**
 * Download and set the seller logo as featured image, replacing existing.
 *
 * @param int    $post_id  Seller post ID.
 * @param string $logo_url Remote logo URL.
 */
function apex_trading_set_seller_logo(int $post_id, string $logo_url): void
{
    if (empty($logo_url)) {
        return;
    }

    if (! function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (! function_exists('media_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $tmp = download_url($logo_url, 60);

    if (is_wp_error($tmp)) {
        apex_trading_seller_log('logo download failed', ['post_id' => $post_id, 'url' => $logo_url, 'error' => $tmp->get_error_message()]);
        return;
    }

    $filename = basename(parse_url($logo_url, PHP_URL_PATH) ?? '');
    if (empty($filename)) {
        $filename = 'logo-' . $post_id . '.png';
    }

    $file_array = [
        'name'     => $filename,
        'tmp_name' => $tmp,
    ];

    $attach_id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        apex_trading_seller_log('logo sideload failed', ['post_id' => $post_id, 'url' => $logo_url, 'error' => $attach_id->get_error_message()]);
        return;
    }

    $old_thumbnail = get_post_thumbnail_id($post_id);

    set_post_thumbnail($post_id, $attach_id);

    if ($old_thumbnail && (int) $old_thumbnail !== (int) $attach_id) {
        wp_delete_attachment($old_thumbnail, true);
    }
}

/**
 * Find an existing seller by its source identifier.
 *
 * @param string $source_id Upstream identifier.
 *
 * @return int Post ID or 0.
 */
function apex_trading_find_seller_post_id(string $slug): int
{
    if (empty($slug)) {
        return 0;
    }

    // Primary lookup by slug/post_name.
    $posts = get_posts(
        [
            'post_type'      => APEX_SELLER_POST_TYPE,
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'name'           => sanitize_title($slug),
        ]
    );

    return ! empty($posts) ? (int) $posts[0] : 0;
}

/**
 * Normalize a state string into a display name and slug.
 *
 * @param string $state Raw state code/name.
 *
 * @return array{0:string,1:string}
 */
function apex_trading_normalize_state(string $state): array
{
    $map = apex_trading_state_map();
    $key = strtoupper(trim($state));
    $name = $map[$key] ?? ucwords(strtolower((string) $state));
    $slug = sanitize_title($name);

    return [
        $name,
        $slug,
    ];
}

/**
 * Return state map keyed by code.
 *
 * @return array<string,string>
 */
function apex_trading_state_map(): array
{
    return [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ];
}

/**
 * Two-letter state codes keyed to names.
 *
 * @return array<string,string>
 */
function apex_trading_state_codes(): array
{
    return apex_trading_state_map();
}

add_action('acf/init', 'apex_trading_register_state_featured_image_field');

add_action('acf/init', 'apex_trading_register_state_custom_title_field');

function apex_trading_register_state_custom_title_field(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_apex_seller_state_custom_title',
        'title'  => 'State Custom Title',
        'fields' => [
            [
                'key'   => 'field_apex_seller_state_custom_title',
                'label' => 'Custom Title',
                'name'  => 'custom_title',
                'type'  => 'text',
                'instructions' => 'Optional: overrides the default hero title for this state.',
                'wrapper' => ['width' => 100],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'taxonomy',
                    'operator' => '==',
                    'value'    => APEX_SELLER_TAXONOMY,
                ],
            ],
        ],
        'position'     => 'normal',
        'style'        => 'default',
        'active'       => true,
        'show_in_rest' => 1,
    ]);
}


function apex_trading_register_state_featured_image_field(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_apex_seller_state_featured_image',
        'title'  => 'State Featured Image',
        'fields' => [
            [
                'key'   => 'field_apex_seller_state_featured_image',
                'label' => 'Featured Image',
                'name'  => 'featured_image',
                'type'  => 'image',
                'return_format' => 'url', // IMPORTANT: matches your hero block
                'preview_size'  => 'medium',
                'library'       => 'all',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'taxonomy',
                    'operator' => '==',
                    'value'    => APEX_SELLER_TAXONOMY,
                ],
            ],
        ],
        'position'     => 'normal',
        'style'        => 'default',
        'active'       => true,
        'show_in_rest' => 1,
    ]);
}
