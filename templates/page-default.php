<?php
/**
 * JMVC Default Page Template
 *
 * This template is used when a JPageController doesn't specify a custom template.
 * It provides a basic wrapper with theme header and footer.
 *
 * Available variables:
 *   - Content is available via JBag::get('jmvc_page_content')
 *   - Title is available via JBag::get('jmvc_page_title')
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="jmvc-main" class="jmvc-page-wrapper">
    <div class="jmvc-page-container">
        <?php
        $content = JBag::get('jmvc_page_content');
        if ($content) {
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content escaped in controller
        }
        ?>
    </div>
</main>

<?php
get_footer();
