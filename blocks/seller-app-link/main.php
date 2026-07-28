<?php
/**
 * Seller App Link ACF block registration.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ccc_primary_register_seller_app_link_block');

function ccc_primary_register_seller_app_link_block(): void
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type([
        'name'            => 'ccc-seller-app-link',
        'title'           => __('Seller App Link', 'ccc-primary-theme'),
        'description'     => __('A simple link to the seller application.', 'ccc-primary-theme'),
        'render_callback' => 'ccc_primary_render_seller_app_link_block',
        'category'        => 'layout',
        'icon'            => 'admin-links',
        'keywords'        => ['seller', 'link', 'application'],
        'supports'        => [
            'align'  => false,
            'anchor' => true,
            'mode'   => false,
        ],
    ]);
}

function ccc_primary_render_seller_app_link_block($block, $content = '', $is_preview = false)
{
    $block_dir = __DIR__;
    if (file_exists($block_dir . '/render.php')) {
        include $block_dir . '/render.php';
    }
}
