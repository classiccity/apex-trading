<?php
/**
 * Testimonials custom post type and taxonomy.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', 'ccc_primary_register_testimonials');
add_action('acf/init', 'ccc_primary_register_testimonial_fields');

/**
 * Registers the Testimonials CPT and State taxonomy.
 */
function ccc_primary_register_testimonials(): void
{
    $labels = [
        'name'               => __('Testimonials', 'ccc-primary-theme'),
        'singular_name'      => __('Testimonial', 'ccc-primary-theme'),
        'add_new'            => __('Add New', 'ccc-primary-theme'),
        'add_new_item'       => __('Add New Testimonial', 'ccc-primary-theme'),
        'edit_item'          => __('Edit Testimonial', 'ccc-primary-theme'),
        'new_item'           => __('New Testimonial', 'ccc-primary-theme'),
        'view_item'          => __('View Testimonial', 'ccc-primary-theme'),
        'search_items'       => __('Search Testimonials', 'ccc-primary-theme'),
        'not_found'          => __('No testimonials found.', 'ccc-primary-theme'),
        'not_found_in_trash' => __('No testimonials found in Trash.', 'ccc-primary-theme'),
        'menu_name'          => __('Testimonials', 'ccc-primary-theme'),
    ];

    register_post_type(
        'testimonials',
        [
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-format-quote',
            'supports'     => ['title', 'editor', 'thumbnail'],
            'rewrite'      => [
                'slug' => 'testimonials',
            ],
        ]
    );

    register_taxonomy(
        'testimonial_state',
        ['testimonials'],
        [
            'label'        => __('States', 'ccc-primary-theme'),
            'rewrite'      => ['slug' => 'testimonial-state'],
            'hierarchical' => false,
            'show_in_rest' => true,
        ]
    );

    ccc_primary_seed_testimonial_states();
}

/**
 * Seeds the testimonial_state taxonomy with U.S. states (including DC).
 */
function ccc_primary_seed_testimonial_states(): void
{
    $states = [
        'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware',
        'District of Columbia', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa',
        'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
        'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey',
        'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon',
        'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah',
        'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming',
    ];

    foreach ($states as $state) {
        if (! term_exists($state, 'testimonial_state')) {
            wp_insert_term($state, 'testimonial_state', ['slug' => sanitize_title($state)]);
        }
    }
}

/**
 * Registers ACF fields for Testimonials posts.
 */
function ccc_primary_register_testimonial_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        [
            'key'      => 'group_ccc_testimonial_fields',
            'title'    => __('Testimonial Details', 'ccc-primary-theme'),
            'fields'   => [
                [
                    'key'   => 'field_ccc_testimonial_name',
                    'label' => __('Name', 'ccc-primary-theme'),
                    'name'  => 'name',
                    'type'  => 'text',
                    'wrapper' => ['width' => 100],
                ],
                [
                    'key'   => 'field_ccc_testimonial_company',
                    'label' => __('Company', 'ccc-primary-theme'),
                    'name'  => 'company',
                    'type'  => 'text',
                    'wrapper' => ['width' => 100],
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'testimonials',
                    ],
                ],
            ],
            'position' => 'normal',
            'style'    => 'default',
            'active'   => true,
            'show_in_rest' => 1,
        ]
    );
}
