<?php
/**
 * State archive showing all sellers for that state.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

$term = get_queried_object();

get_header();

// Get featured image for hero (fallback to static image)
$featured_img = get_the_post_thumbnail_url(get_queried_object_id(), 'full');
$hero_bg = $featured_img ?: '/images/sellers/feature/slack.jpg';
?>

<!-- Hero Section -->
<section class="section" style="background: url(<?php echo esc_url($hero_bg); ?>); background-size: cover; background-position: center;">
  <div class="container mt-7">
    <div class="banner-text">
      <div class="row justify-content-between">
        <div class="col-md-12 align-self-center">
          <h1 class="text-center mb-2 display-3">
            Purchasing Wholesale Cannabis in "<?php echo esc_html($term->name); ?>" is Easy with Apex Trading
          </h1>
          <!-- <p class="text-center mb-5">Optional subtitle here</p> -->
          <br>
        </div>
      </div>
    </div>
  </div>
</section>

<main id="primary" class="site-main">

    <?php if (have_posts()) : ?>
        <div class="ccc-linked-icon-pods has-columns-3">
            <div class="ccc-linked-icon-pods__grid seller-list">
                <?php
                while (have_posts()) :
                    the_post();

                    $city    = get_post_meta(get_the_ID(), 'apex_seller_city', true);
                    $website = get_post_meta(get_the_ID(), 'apex_seller_website', true);
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
</main>

<?php
get_footer();
