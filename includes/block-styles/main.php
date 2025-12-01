<?php
/**
 * Register custom block styles for CCC Primary Theme.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', 'ccc_primary_theme_register_block_styles');

/**
 * Registers custom block styles.
 */
function ccc_primary_theme_register_block_styles(): void
{
    register_block_style(
        'core/columns',
        [
            'name'  => 'swap-on-mobile',
            'label' => __('Swap on Mobile', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/image',
        [
            'name'  => 'style-a',
            'label' => __('Style A', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/image',
        [
            'name'  => 'style-b',
            'label' => __('Style B', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'mobile-scroll',
            'label' => __('Mobile Scroll', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'mobile-two-column',
            'label' => __('Mobile 2-Column', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'mobile-one-column',
            'label' => __('Mobile 1-Column', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'background-half-gradient',
            'label' => __('BG Half Gradient', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'background-color-overlay',
            'label' => __('BG Overlay', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'background-cut-off-left',
            'label' => __('BG Cut Left', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/group',
        [
            'name'  => 'background-cut-off-right',
            'label' => __('BG Cut Right', 'ccc-primary-theme'),
        ]
    );

    register_block_style(
        'core/paragraph',
        [
            'name'  => 'eyebrow',
            'label' => __('Eyebrow', 'ccc-primary-theme'),
        ]
    );
}
