<?php
/**
 * Linked Icon Pods ACF block registration and fields.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ccc_primary_register_logo_garden_block');

/**
 * Registers the Linked Icon Pods block and its fields.
 */
function ccc_primary_register_logo_garden_block(): void
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type(
        [
            'name'            => 'ccc-logo-garden',
            'title'           => __('Linked Icon Pods', 'ccc-primary-theme'),
            'description'     => __('Displays a grid of linked icon pods.', 'ccc-primary-theme'),
            'render_callback' => 'ccc_primary_render_logo_garden_block',
            'category'        => 'layout',
            'icon'            => 'screenoptions',
            'keywords'        => ['icons', 'links', 'grid', 'cards', 'logo', 'garden'],
            'supports'        => [
                'align'  => false,
                'anchor' => true,
                'mode'   => true,
            ],
        ]
    );

    ccc_primary_register_logo_garden_fields();
}

/**
 * Registers ACF fields for the Linked Icon Pods block.
 */
function ccc_primary_register_logo_garden_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        [
            'key'                   => 'group_ccc_logo_garden',
            'title'                 => __('Linked Icon Pods', 'ccc-primary-theme'),
            'fields'                => [
                [
                    'key'           => 'field_ccc_lip_number_of_columns',
                    'label'         => __('Number of Columns', 'ccc-primary-theme'),
                    'name'          => 'number_of_columns',
                    'type'          => 'select',
                    'choices'       => [
                        1 => '1',
                        2 => '2',
                        3 => '3',
                        4 => '4',
                    ],
                    'default_value' => 3,
                    'ui'            => 1,
                    'allow_null'    => 0,
                    'multiple'      => 0,
                    'wrapper'       => [
                        'width' => 25,
                    ],
                ],
                [
                    'key'          => 'field_ccc_lip_pods',
                    'label'        => __('Pods', 'ccc-primary-theme'),
                    'name'         => 'pods',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'button_label' => __('Add Pod', 'ccc-primary-theme'),
                    'sub_fields'   => [
                        [
                            'key'           => 'field_ccc_lip_icon',
                            'label'         => __('Icon', 'ccc-primary-theme'),
                            'name'          => 'icon',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'medium',
                            'wrapper'       => [
                                'width' => 100,
                            ],
                        ],
                        [
                            'key'     => 'field_ccc_lip_title',
                            'label'   => __('Title', 'ccc-primary-theme'),
                            'name'    => 'title',
                            'type'    => 'text',
                            'wrapper' => [
                                'width' => 40,
                            ],
                        ],
                        [
                            'key'           => 'field_ccc_lip_link',
                            'label'         => __('Link', 'ccc-primary-theme'),
                            'name'          => 'link',
                            'type'          => 'text',
                            'placeholder'   => 'https://example.com',
                            'wrapper'       => [
                                'width' => 30,
                            ],
                        ],
                        [
                            'key'     => 'field_ccc_lip_new_tab',
                            'label'   => __('Open in New Tab', 'ccc-primary-theme'),
                            'name'    => 'open_in_new_tab',
                            'type'    => 'true_false',
                            'ui'      => 1,
                            'wrapper' => [
                                'width' => 30,
                            ],
                        ],
                    ],
                ],
            ],
            'location'              => [
                [
                    [
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'acf/ccc-logo-garden',
                    ],
                ],
            ],
            'position'              => 'normal',
            'style'                 => 'default',
            'active'                => true,
            'show_in_rest'          => 1,
            'hide_on_screen'        => '',
        ]
    );
}

/**
 * Renders the Linked Icon Pods block markup.
 *
 * @param array  $block      Block settings and attributes.
 * @param string $content    Block inner HTML (not used).
 * @param bool   $is_preview Whether this is shown in the editor preview.
 */
function ccc_primary_render_logo_garden_block(array $block, string $content = '', bool $is_preview = true): void
{
    $context = [
        'block'      => $block,
        'pods'       => get_field('pods') ?: [],
        'columns'    => (int) (get_field('number_of_columns') ?: 3),
        'is_preview' => $is_preview,
    ];

    $context['columns'] = max(1, min(4, $context['columns']));

    include __DIR__ . '/render.php';
}
