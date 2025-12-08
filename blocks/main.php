<?php
/**
 * Loader for ACF blocks within the CCC Primary Theme.
 *
 * @package CCCPrimaryTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

// Require additional block definitions here as they are created.
require_once __DIR__ . '/linked-icon-pods/main.php';
require_once __DIR__ . '/testimonials/main.php';
require_once __DIR__ . '/events-list/main.php';
require_once __DIR__ . '/post-grid/main.php';
require_once __DIR__ . '/logo-garden/main.php';