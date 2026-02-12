<?php
$events = get_field('events') ?: [];
?>

<?php if ($events): ?>
    <div class="post-grid-custom events-grid">
        <div class="inner-grid-container">
            <?php foreach ($events as $event): 
                $title = $event['title'] ?? '';
                $start = $event['start_date'] ?? '';
                $end   = $event['end_date'] ?? '';
                $location = $event['location'] ?? ''; // 🔽 NEW
                $desc  = $event['description'] ?? '';
                $link_text = $event['link_text'] ?? '';
                $url = $event['link'] ?? '';
                $featured_image = $event['featured_image'] ?? null;
            ?>
                <div class="post-grid-item event-item">
                    <?php if ($featured_image): ?>
                        <div class="post-grid-image">
                            <a href="<?php echo esc_url($url ?: '#'); ?>">
                                <img src="<?php echo esc_url($featured_image['url']); ?>" alt="<?php echo esc_attr($featured_image['alt'] ?? ''); ?>">
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="content-container">
                        <?php if ($title): ?>
                            <h3 class="post-grid-title">
                                <a href="<?php echo esc_url($url ?: '#'); ?>" target="_blank"><?php echo esc_html($title); ?></a>
                            </h3>
                        <?php endif; ?>

                        <?php if ($start || $end): ?>
                            <p class="post-grid-excerpt event-date">
                                <?php 
                                echo esc_html($start ? date_i18n('F j, Y', strtotime($start)) : '');
                                if ($end) echo ' – ' . esc_html(date_i18n('F j, Y', strtotime($end)));
                                ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($location): ?> <!-- 🔽 NEW OUTPUT -->
                            <p class="event-location">
                                <?php echo esc_html($location); ?>
                            </p>
                        <?php endif; ?> <!-- 🔼 NEW OUTPUT -->

                        <?php if ($desc): ?>
                            <p class="post-grid-excerpt event-description">
                                <?php echo esc_html(wp_trim_words($desc, 55, '...')); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($link_text && $url): ?>
                            <a class="post-grid-readmore wp-block-button__link" href="<?php echo esc_url($url); ?>">
                                <?php echo esc_html($link_text); ?> <i class="link__icon fa fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <p>No events added yet.</p>
<?php endif; ?>
