<?php
$pods    = get_field('pods') ?: [];
$columns = (int) (get_field('number_of_columns') ?: 3);
$columns = max(1, min(5, $columns)); // Clamp between 1 and 5
?>

<?php if (!empty($pods)) : ?>
    <div class="logo-garden columns-<?php echo esc_attr($columns); ?>">
        <div class="inner-grid-container">
            <?php foreach ($pods as $pod) : 
                $icon = $pod['icon'] ?? '';
                $title = $pod['title'] ?? '';
                $link = $pod['link'] ?? '';
                $new_tab = !empty($pod['open_in_new_tab']);
            ?>
                <div class="logo-garden-item">
                    <?php if ($link) : ?>
                        <a href="<?php echo esc_url($link); ?>" <?php echo $new_tab ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php endif; ?>

                    <?php if ($icon) : ?>
                        <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt'] ?? $title); ?>" />
                    <?php endif; ?>

                    <?php if ($title) : ?>
                        <p class="logo-title"><?php echo esc_html($title); ?></p>
                    <?php endif; ?>

                    <?php if ($link) : ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
