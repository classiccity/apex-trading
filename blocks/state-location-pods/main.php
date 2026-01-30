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
        'description'     => __('Displays seller pods for a selected state.', 'ccc-primary-theme'),
        'render_callback' => 'ccc_primary_render_seller_pods_block',
        'category'        => 'layout',
        'icon'            => 'store',
        'keywords'        => ['seller', 'pods', 'grid', 'state'],
        'supports'        => [
            'align'  => ['wide', 'full'],
            'anchor' => true,
            'mode'   => false,
        ],
    ]);

    ccc_primary_register_seller_pods_fields();
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

/**
 * Registers ACF fields for the Seller Pods block.
 */
function ccc_primary_register_seller_pods_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_ccc_seller_pods',
        'title'  => __('Seller Pods', 'ccc-primary-theme'),
        'fields' => [
            [
                'key'           => 'field_ccc_seller_pods_state',
                'label'         => __('State', 'ccc-primary-theme'),
                'name'          => 'state',
                'type'          => 'taxonomy',
                'taxonomy'      => APEX_SELLER_TAXONOMY,
                'field_type'    => 'select',
                'return_format' => 'object',
                'add_term'      => 0,
                'load_save_terms' => 0,
                'allow_null'    => 1,
                'ui'            => 1,
                'instructions'  => __('Choose which state’s sellers to show. If empty, the current state archive is used.', 'ccc-primary-theme'),
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'block',
                    'operator' => '==',
                    'value'    => 'acf/ccc-seller-pods',
                ],
            ],
        ],
        'position'     => 'normal',
        'style'        => 'default',
        'active'       => true,
        'show_in_rest' => 1,
    ]);
}
