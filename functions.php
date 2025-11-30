<?php
/**
 * Theme setup and asset loading for CCC Primary Theme.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ccc_primary_theme_setup')) {
    /**
     * Register basic theme supports.
     */
    function ccc_primary_theme_setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support(
            'html5',
            [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            ]
        );

        register_nav_menus(
            [
                'primary' => __('Primary Menu', 'ccc-primary-theme'),
            ]
        );
    }
}
add_action('after_setup_theme', 'ccc_primary_theme_setup');

/**
 * Enqueue front-end assets.
 */
function ccc_primary_theme_enqueue_assets(): void
{
    $theme_version = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'ccc-primary-theme-style',
        get_stylesheet_uri(),
        [],
        $theme_version
    );

    $script_path = get_template_directory() . '/js/main.js';
    $script_version = file_exists($script_path) ? filemtime($script_path) : $theme_version;

    wp_enqueue_script(
        'ccc-primary-theme-main',
        get_template_directory_uri() . '/js/main.js',
        [],
        $script_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'ccc_primary_theme_enqueue_assets');

/**
 * Register widget areas.
 */
function ccc_primary_theme_widgets_init(): void
{
    register_sidebar(
        [
            'name'          => __('Primary Sidebar', 'ccc-primary-theme'),
            'id'            => 'sidebar-1',
            'description'   => __('Add widgets here to appear in your sidebar.', 'ccc-primary-theme'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );
}
add_action('widgets_init', 'ccc_primary_theme_widgets_init');

// Load theme block definitions.
require_once get_template_directory() . '/blocks/main.php';

/**
 * Enqueue theme styles inside the block editor.
 */
function ccc_primary_theme_enqueue_editor_assets(): void
{
    wp_enqueue_style(
        'ccc-primary-theme-editor-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
}
add_action('enqueue_block_editor_assets', 'ccc_primary_theme_enqueue_editor_assets');

// Load block style registrations.
require_once get_template_directory() . '/includes/block-styles/main.php';
// Load testimonials customizations.
require_once get_template_directory() . '/includes/testimonials/main.php';



/* 
Remove all the default block styles from WordPress
*/
function smartwp_remove_wp_block_library_css(){
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-blocks-style' ); // Remove WooCommerce block CSS
    wp_dequeue_style( 'wp-block-buttons' );
	wp_dequeue_style( 'wp-block-button' );
    wp_dequeue_style( 'wp-block-paragraph' );
} 
add_action( 'wp_enqueue_scripts', 'smartwp_remove_wp_block_library_css', 100 );


/**
 * Filter the default theme.json data.
 *
 * Removes default duotone, gradients and color palette values provided by WordPress core.
 * This allows the theme to define its own color settings without also loading core defaults as CSS variables.
 *
 * @param WP_Theme_JSON_Data $theme_json The default theme.json data.
 *
 * @return WP_Theme_JSON_Data The modified theme.json data.
 */

add_filter( 'wp_theme_json_data_default', function( $theme_json ) {
	// Get JSON data as an array.
	$data = $theme_json->get_data();

	// Remove duotone, gradients, and palette values.
	$data['settings']['color']['duotone']['default']   = [];
	$data['settings']['color']['gradients']['default'] = [];
	$data['settings']['color']['palette']['default']   = [];

	// Update the theme JSON data.
	return $theme_json->update_with( $data );
});
