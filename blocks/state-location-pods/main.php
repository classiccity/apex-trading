<?php
/**
 * Seller Pods ACF block registration.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ccc_primary_register_seller_pods_block');

function ccc_primary_register_seller_pods_block(): void
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type([
        'name'            => 'ccc-seller-pods',
        'title'           => __('Seller Pods', 'ccc-primary-theme'),
        'description'     => __('Displays seller pods for the current state.', 'ccc-primary-theme'),
        'render_callback' => 'ccc_primary_render_seller_pods_block',
        'category'        => 'layout',
        'icon'            => 'store',
        'keywords'        => ['seller', 'pods', 'grid'],
        'supports'        => [
            'align'  => ['wide', 'full'],
            'anchor' => true,
            'mode'   => false,
        ],
    ]);
}

/**
 * Render callback for Seller Pods block.
 */
function ccc_primary_render_seller_pods_block($block, $content = '', $is_preview = false): void
{
    $block_dir = __DIR__;
    if (file_exists($block_dir . '/render.php')) {
        include $block_dir . '/render.php';
    }
}
