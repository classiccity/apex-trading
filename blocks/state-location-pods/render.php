<?php
// Allow editors to pick a state; fall back to the current archive term.
$selected_state = function_exists('get_field') ? get_field('state') : null;
$state_term = null;

if ($selected_state instanceof WP_Term) {
    $state_term = $selected_state;
} elseif (is_array($selected_state) && isset($selected_state['term_id'])) {
    $state_term = get_term((int) $selected_state['term_id'], APEX_SELLER_TAXONOMY);
} elseif (is_numeric($selected_state)) {
    $state_term = get_term((int) $selected_state, APEX_SELLER_TAXONOMY);
}

if (! $state_term || is_wp_error($state_term)) {
    $queried = get_queried_object();
    if ($queried instanceof WP_Term && $queried->taxonomy === APEX_SELLER_TAXONOMY) {
        $state_term = $queried;
    }
}

if (! $state_term || ! isset($state_term->term_id)) {
    echo '<p>' . esc_html__('No sellers found for this state yet.', 'ccc-primary-theme') . '</p>';
    return;
}

$args = [
    'post_type'      => APEX_SELLER_POST_TYPE,
    'posts_per_page' => -1,
    'tax_query'      => [
        [
            'taxonomy' => APEX_SELLER_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $state_term->term_id,
        ],
    ],
];

$query = new WP_Query($args);
?>

<?php if ($query->have_posts()) : ?>
    <div class="ccc-linked-icon-pods has-columns-3">
        <div class="ccc-linked-icon-pods__grid seller-list">
            <?php while ($query->have_posts()) : $query->the_post(); ?>

                <?php
                $city         = get_post_meta(get_the_ID(), 'apex_seller_city', true);
                $website      = get_post_meta(get_the_ID(), 'apex_seller_website', true);
                $featured_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
                ?>

                <a
                    href="<?php the_permalink(); ?>"
                    id="post-<?php the_ID(); ?>"
                    <?php post_class('ccc-icon-pod ccc-linked-icon-pods__item seller-card'); ?>
                >
                    <?php if ($featured_img) : ?>
                        <figure class="ccc-linked-icon-pods__icon">
                            <img src="<?php echo esc_url($featured_img); ?>" alt="<?php the_title_attribute(); ?>">
                        </figure>
                    <?php endif; ?>

                    <p class="ccc-linked-icon-pods__title">
                        <?php the_title(); ?>
                    </p>

                    <?php if ($city || $website) : ?>
                        <ul class="seller-meta">
                            <?php if ($city) : ?>
                                <li><?php echo esc_html($city); ?></li>
                            <?php endif; ?>
                            <?php if ($website) : ?>
                                <li><?php echo esc_html($website); ?></li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </a>

            <?php endwhile; ?>
        </div>
    </div>
<?php else : ?>
    <p><?php esc_html_e('No sellers found for this state yet.', 'ccc-primary-theme'); ?></p>
<?php endif; ?>

<?php wp_reset_postdata(); ?>
