<?php
/**
 * Testimonials ACF block registration and fields.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ccc_primary_register_testimonials_block');

/**
 * Registers the Testimonials block and its fields.
 */
function ccc_primary_register_testimonials_block(): void
{
    if (! function_exists('acf_register_block_type')) {
        return;
    }

    acf_register_block_type(
        [
            'name'            => 'ccc-testimonials',
            'title'           => __('Testimonials', 'ccc-primary-theme'),
            'description'     => __('Displays testimonials in a grid layout.', 'ccc-primary-theme'),
            'render_callback' => 'ccc_primary_render_testimonials_block',
            'category'        => 'layout',
            'icon'            => 'format-quote',
            'keywords'        => ['testimonials', 'quotes', 'grid'],
            'supports'        => [
                'align'  => false,
                'anchor' => true,
                'mode'   => false,
            ],
        ]
    );

    ccc_primary_register_testimonials_block_fields();
}

/**
 * Registers ACF fields for the Testimonials block.
 */
function ccc_primary_register_testimonials_block_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        [
            'key'      => 'group_ccc_testimonials_block',
            'title'    => __('Testimonials Block', 'ccc-primary-theme'),
            'fields'   => [
                [
                    'key'           => 'field_ccc_test_columns',
                    'label'         => __('Columns', 'ccc-primary-theme'),
                    'name'          => 'columns',
                    'type'          => 'select',
                    'choices'       => [
                        1 => '1',
                        2 => '2',
                        3 => '3',
                    ],
                    'default_value' => 3,
                    'wrapper'       => ['width' => 50],
                    'ui'            => 1,
                ],
                [
                    'key'           => 'field_ccc_test_display_option',
                    'label'         => __('Display Options', 'ccc-primary-theme'),
                    'name'          => 'display_option',
                    'type'          => 'select',
                    'choices'       => [
                        'manual' => __('Manual Selection', 'ccc-primary-theme'),
                        'state'  => __('By State', 'ccc-primary-theme'),
                    ],
                    'default_value' => 'manual',
                    'wrapper'       => ['width' => 50],
                    'ui'            => 1,
                ],
                [
                    'key'               => 'field_ccc_test_state_filter',
                    'label'             => __('State', 'ccc-primary-theme'),
                    'name'              => 'state_filter',
                    'type'              => 'taxonomy',
                    'taxonomy'          => 'testimonial_state',
                    'field_type'        => 'select',
                    'allow_null'        => 1,
                    'add_term'          => 0,
                    'save_terms'        => 0,
                    'load_terms'        => 0,
                    'return_format'     => 'id',
                    'wrapper'           => ['width' => 100],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_ccc_test_display_option',
                                'operator' => '==',
                                'value'    => 'state',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_ccc_test_state_quantity',
                    'label'             => __('Quantity of Posts', 'ccc-primary-theme'),
                    'name'              => 'state_quantity',
                    'type'              => 'number',
                    'min'               => 1,
                    'step'              => 1,
                    'placeholder'       => 3,
                    'wrapper'           => ['width' => 100],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_ccc_test_display_option',
                                'operator' => '==',
                                'value'    => 'state',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_ccc_test_manual_selection',
                    'label'             => __('Choose Your Testimonials', 'ccc-primary-theme'),
                    'name'              => 'manual_selection',
                    'type'              => 'relationship',
                    'post_type'         => ['testimonials'],
                    'filters'           => ['search'],
                    'return_format'     => 'object',
                    'wrapper'           => ['width' => 100],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_ccc_test_display_option',
                                'operator' => '==',
                                'value'    => 'manual',
                            ],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'acf/ccc-testimonials',
                    ],
                ],
            ],
            'style'    => 'default',
            'active'   => true,
            'show_in_rest' => 1,
        ]
    );
}

/**
 * Renders the Testimonials block.
 *
 * @param array  $block      Block settings and attributes.
 * @param string $content    Block inner HTML (unused).
 * @param bool   $is_preview Whether this is shown in the editor preview.
 */
function ccc_primary_render_testimonials_block(array $block, string $content = '', bool $is_preview = false): void
{
    $columns        = (int) (get_field('columns') ?: 3);
    $display_option = get_field('display_option') ?: 'manual';
    $state_filter   = get_field('state_filter');
    $state_quantity = (int) (get_field('state_quantity') ?: 0);
    $manual_posts   = get_field('manual_selection') ?: [];

    $columns = max(1, min(3, $columns));

    $posts = [];

    if ('state' === $display_option && $state_filter) {
        $query = new WP_Query(
            [
                'post_type'      => 'testimonials',
                'posts_per_page' => $state_quantity > 0 ? $state_quantity : -1,
                'tax_query'      => [
                    [
                        'taxonomy' => 'testimonial_state',
                        'field'    => 'term_id',
                        'terms'    => (array) $state_filter,
                    ],
                ],
            ]
        );

        if ($query->have_posts()) {
            $posts = $query->posts;
        }
        wp_reset_postdata();
    } elseif ('manual' === $display_option && ! empty($manual_posts)) {
        $posts = $manual_posts;
    }

    $context = [
        'block'      => $block,
        'posts'      => $posts,
        'columns'    => $columns,
        'is_preview' => $is_preview,
    ];

    include __DIR__ . '/render.php';
}
