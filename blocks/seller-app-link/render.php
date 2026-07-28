<?php
/**
 * View template for the Seller App Link block.
 *
 * Intended for use on Single post templates only — pulls the current
 * post's title and slug directly from the query.
 */

if (! defined('ABSPATH')) {
    exit;
}

$post_title = get_the_title();
$post_slug  = get_post_field('post_name');
?>

<div class="ccc-seller-app-link">
    <div class="ccc-seller-app-link__content">
        <p>Take a look at all of <?php echo esc_html($post_title); ?>'s products!</p>
        <div class="wp-block-button is-style-outline is-style-outline--3">
            <a class="wp-block-button__link wp-element-button" href="{{INSERT_APP_LINK_HERE}}/<?php echo esc_attr($post_slug); ?>">Buy Now</a>
        </div>
    </div>
</div>
