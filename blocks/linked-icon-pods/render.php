<?php
/**
 * View template for the Linked Icon Pods block.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

$block_id = ! empty($context['block']['anchor'])
    ? $context['block']['anchor']
    : 'ccc-linked-icon-pods-' . ($context['block']['id'] ?? uniqid());

$classes = [
    'ccc-linked-icon-pods',
    'has-columns-' . $context['columns'],
];

if (! empty($context['block']['className'])) {
    $classes[] = $context['block']['className'];
}

if (! empty($context['block']['align'])) {
    $classes[] = 'align' . $context['block']['align'];
}

$pods = $context['pods'] ?? [];

if (empty($pods)) {
    if (! empty($context['is_preview'])) {
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
            <p><?php esc_html_e('Add at least one pod to see content.', 'ccc-primary-theme'); ?></p>
        </div>
        <?php
    }
    return;
}
?>

<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <div class="ccc-linked-icon-pods__grid">
        <?php foreach ($pods as $pod) :
            $icon  = $pod['icon'] ?? null;
            $title = $pod['title'] ?? '';
            $link  = $pod['link'] ?? '';
            $new_tab = ! empty($pod['open_in_new_tab']);
            $tag   = $link ? 'a' : 'div';
            $href  = $link ? esc_url($link) : '';
            $target = $href && $new_tab ? '_blank' : '';
            $rel    = $href && $new_tab ? 'noopener noreferrer' : '';
            ?>
            <<?php echo esc_html($tag); ?>
                <?php if ($href) : ?>
                    href="<?php echo $href; ?>"
                    <?php if ($target) : ?>
                        target="<?php echo esc_attr($target); ?>"
                        rel="<?php echo esc_attr($rel); ?>"
                    <?php endif; ?>
                <?php endif; ?>
                class="ccc-icon-pod ccc-linked-icon-pods__item"
            >
                <?php if (! empty($icon['url'])) :
                    $alt_text = $icon['alt'] ?? $title;
                    ?>
                    <figure class="ccc-linked-icon-pods__icon">
                        <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($alt_text); ?>">
                    </figure>
                <?php endif; ?>

                <?php if ($title) : ?>
                    <p class="ccc-linked-icon-pods__title"><?php echo esc_html($title); ?></p>
                <?php endif; ?>
            </<?php echo esc_html($tag); ?>>
        <?php endforeach; ?>
    </div>
</div>
