<?php
/**
 * View template for the Term Hero block.
 */

if (! defined('ABSPATH')) {
    exit;
}

$term = get_queried_object();
if (! $term || empty($term->term_id)) {
    return;
}

// Get term featured image (ACF image field on taxonomy)
$background_image = get_field('featured_image', $term);

$padding_top    = get_field('padding_top') ?: 80;
$padding_bottom = get_field('padding_bottom') ?: 80;

$style = '';

if ($background_image) {
    $style .= "background-image:url('" . esc_url($background_image) . "');";
    $style .= "background-size:cover;";
    $style .= "background-position:center;";
}

$style .= "padding-top:{$padding_top}px;";
$style .= "padding-bottom:{$padding_bottom}px;";
?>

<div class="wp-block-group alignfull ccc-hero ccc-hero--large-image is-style-background-color-overlay has-background background-no-image"
     style="<?php echo esc_attr($style); ?>">

    <h1 class="wp-block-heading has-text-align-center header-contained">
        Purchasing Wholesale Cannabis in <?php echo esc_html($term->name); ?> is Easy with Apex Trading
    </h1>

</div>
