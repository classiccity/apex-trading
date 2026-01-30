<?php
/**
 * Term Hero ACF block registration.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ccc_primary_register_term_hero_block');

function ccc_primary_register_term_hero_block(): void
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type([
        'name'            => 'ccc-term-hero',
        'title'           => __('Term Hero', 'ccc-primary-theme'),
        'description'     => __('Hero section with background image and dynamic term title.', 'ccc-primary-theme'),
        'render_callback' => 'ccc_primary_render_term_hero_block',
        'category'        => 'layout',
        'icon'            => 'cover-image',
        'keywords'        => ['hero', 'term', 'archive'],
        'supports'        => [
            'align'  => ['full'],
            'anchor' => true,
            'mode'   => false,
        ],
    ]);

    ccc_primary_register_term_hero_fields();
}

function ccc_primary_register_term_hero_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_ccc_term_hero',
        'title'  => __('Term Hero', 'ccc-primary-theme'),
        'fields' => [
            [
                'key'   => 'field_ccc_th_background_image',
                'label' => __('Background Image', 'ccc-primary-theme'),
                'name'  => 'background_image',
                'type'  => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'wrapper'       => ['width' => 50],
            ],
            [
                'key'   => 'field_ccc_th_custom_title',
                'label' => __('Custom Title', 'ccc-primary-theme'),
                'name'  => 'custom_title',
                'type'  => 'text',
                'instructions' => __('Optional: overrides default hero title for this term.', 'ccc-primary-theme'),
                'wrapper'       => ['width' => 50],
            ],
            [
                'key'   => 'field_ccc_th_padding_top',
                'label' => __('Padding Top (px)', 'ccc-primary-theme'),
                'name'  => 'padding_top',
                'type'  => 'number',
                'default_value' => 80,
                'wrapper'       => ['width' => 25],
            ],
            [
                'key'   => 'field_ccc_th_padding_bottom',
                'label' => __('Padding Bottom (px)', 'ccc-primary-theme'),
                'name'  => 'padding_bottom',
                'type'  => 'number',
                'default_value' => 80,
                'wrapper'       => ['width' => 25],
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'block',
                    'operator' => '==',
                    'value'    => 'acf/ccc-term-hero',
                ],
            ],
        ],
        'active' => true,
        'show_in_rest' => 1,
    ]);
}

function ccc_primary_render_term_hero_block($block, $content = '', $is_preview = false)
{
    $block_dir = __DIR__;
    if (file_exists($block_dir . '/render.php')) {
        include $block_dir . '/render.php';
    }
}
