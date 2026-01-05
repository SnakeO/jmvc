<?php
/**
 * JMVC Admin Dashboard View
 *
 * @package JMVC\Admin
 */

declare(strict_types=1);

namespace JMVC\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$isInitialized = Admin::isInitialized();
$hasVendor = Admin::hasVendor();
$themeName = Admin::getThemeName();
$themePath = Admin::getThemeJmvcPath();

// Get component counts if initialized
$counts = $isInitialized ? Browser::getCounts() : ['controllers' => 0, 'models' => 0, 'views' => 0];
?>

<div class="wrap jmvc-admin">
    <h1><?php esc_html_e('JMVC Dashboard', 'jmvc'); ?></h1>

    <div class="jmvc-dashboard">
        <!-- Status Cards -->
        <div class="jmvc-status-cards">
            <!-- Theme Info -->
            <div class="jmvc-card">
                <h3><?php esc_html_e('Active Theme', 'jmvc'); ?></h3>
                <p class="jmvc-theme-name"><?php echo esc_html($themeName); ?></p>
                <p class="jmvc-theme-path"><code><?php echo esc_html($themePath); ?></code></p>
            </div>

            <!-- JMVC Status -->
            <div class="jmvc-card <?php echo $isInitialized ? 'status-ok' : 'status-warning'; ?>">
                <h3><?php esc_html_e('JMVC Status', 'jmvc'); ?></h3>
                <div class="jmvc-status-indicator">
                    <span class="dashicons <?php echo $isInitialized ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <span><?php echo $isInitialized ? esc_html__('Initialized', 'jmvc') : esc_html__('Not Initialized', 'jmvc'); ?></span>
                </div>
                <?php if (!$isInitialized): ?>
                    <button type="button" class="button button-primary jmvc-initialize-btn">
                        <?php esc_html_e('Initialize JMVC', 'jmvc'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Dependencies Status -->
            <div class="jmvc-card <?php echo $hasVendor ? 'status-ok' : ($isInitialized ? 'status-warning' : 'status-disabled'); ?>">
                <h3><?php esc_html_e('Dependencies', 'jmvc'); ?></h3>
                <div class="jmvc-status-indicator">
                    <?php if ($hasVendor): ?>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span><?php esc_html_e('Installed', 'jmvc'); ?></span>
                    <?php elseif ($isInitialized): ?>
                        <span class="dashicons dashicons-warning"></span>
                        <span><?php esc_html_e('Not Installed', 'jmvc'); ?></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-minus"></span>
                        <span><?php esc_html_e('Initialize first', 'jmvc'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($isInitialized && !$hasVendor): ?>
                    <button type="button" class="button button-primary jmvc-install-deps-btn">
                        <?php esc_html_e('Install Dependencies', 'jmvc'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Rewrite Rules Status -->
            <div class="jmvc-card" id="jmvc-rewrite-card">
                <h3><?php esc_html_e('Rewrite Rules', 'jmvc'); ?></h3>
                <div class="jmvc-status-indicator jmvc-rewrite-status">
                    <span class="dashicons dashicons-update jmvc-spinner"></span>
                    <span><?php esc_html_e('Testing...', 'jmvc'); ?></span>
                </div>
                <button type="button" class="button jmvc-test-rewrite-btn" style="display: none;">
                    <?php esc_html_e('Test Again', 'jmvc'); ?>
                </button>
            </div>
        </div>

        <!-- Component Stats -->
        <?php if ($isInitialized): ?>
        <div class="jmvc-stats">
            <h2><?php esc_html_e('Components', 'jmvc'); ?></h2>
            <div class="jmvc-stats-grid">
                <div class="jmvc-stat">
                    <span class="jmvc-stat-number"><?php echo esc_html((string)$counts['controllers']); ?></span>
                    <span class="jmvc-stat-label"><?php esc_html_e('Controllers', 'jmvc'); ?></span>
                </div>
                <div class="jmvc-stat">
                    <span class="jmvc-stat-number"><?php echo esc_html((string)$counts['models']); ?></span>
                    <span class="jmvc-stat-label"><?php esc_html_e('Models', 'jmvc'); ?></span>
                </div>
                <div class="jmvc-stat">
                    <span class="jmvc-stat-number"><?php echo esc_html((string)$counts['views']); ?></span>
                    <span class="jmvc-stat-label"><?php esc_html_e('Views', 'jmvc'); ?></span>
                </div>
            </div>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=jmvc-components')); ?>" class="button">
                    <?php esc_html_e('View Components', 'jmvc'); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>

        <!-- Instructions Panel (for rewrite rules) -->
        <div class="jmvc-instructions" id="jmvc-rewrite-instructions" style="display: none;">
            <h2><?php esc_html_e('Rewrite Rules Setup', 'jmvc'); ?></h2>
            <div class="jmvc-instructions-content"></div>
        </div>

        <!-- Manual Composer Instructions -->
        <div class="jmvc-instructions" id="jmvc-composer-instructions" style="display: none;">
            <h2><?php esc_html_e('Manual Installation Required', 'jmvc'); ?></h2>
            <div class="jmvc-instructions-content"></div>
        </div>
    </div>

    <!-- Notices -->
    <div class="jmvc-notices" id="jmvc-notices"></div>
</div>
