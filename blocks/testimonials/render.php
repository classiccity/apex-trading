<?php
/**
 * View template for the Testimonials block.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

$block      = $context['block'] ?? [];
$posts      = $context['posts'] ?? [];
$columns    = $context['columns'] ?? 3;
$is_preview = ! empty($context['is_preview']);

$block_id = ! empty($block['anchor']) ? $block['anchor'] : 'ccc-testimonials-' . ($block['id'] ?? uniqid());

$classes = [
    'ccc-testimonials',
    'has-columns-' . $columns,
];

if (! empty($block['className'])) {
    $classes[] = $block['className'];
}

if (! empty($block['align'])) {
    $classes[] = 'align' . $block['align'];
}

if (empty($posts) && $is_preview) {
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <p><?php esc_html_e('Select testimonials to display.', 'ccc-primary-theme'); ?></p>
    </div>
    <?php
    return;
}

if (empty($posts)) {
    return;
}
?>

<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <div class="ccc-testimonials__grid">
        <?php foreach ($posts as $post_obj) :
            $post_id   = $post_obj instanceof WP_Post ? $post_obj->ID : (int) $post_obj;
            $name      = get_field('name', $post_id) ?: get_the_title($post_id);
            $company   = get_field('company', $post_id);
            $content   = apply_filters('the_content', get_post_field('post_content', $post_id));
            $image     = get_the_post_thumbnail($post_id, 'medium_large', ['loading' => 'lazy']);
            $states    = wp_get_post_terms($post_id, 'testimonial_state', ['fields' => 'names']);
            $state_str = $states && ! is_wp_error($states) ? implode(', ', $states) : '';
            ?>
            <article class="ccc-icon-pod ccc-testimonials__item">
                <div class="ccc-testimonials__content">
                    <?php echo $content; ?>
                </div>
                <div class="ccc-testimonials__meta">
                    <?php if ($image) : ?>
                        <figure class="ccc-testimonials__image">
                            <?php echo $image; ?>
                        </figure>
                    <?php endif; ?>
                    <div class="ccc-testimonials__details">
                        <?php if ($name) : ?>
                            <p class="ccc-testimonials__name"><strong><?php echo esc_html($name); ?></strong></p>
                        <?php endif; ?>
                        <p class="ccc-testimonials__company">
                            <?php echo esc_html($company); ?>
                            <?php if ($state_str) : ?>
                                <span class="ccc-testimonials__state"><?php echo esc_html($state_str); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
