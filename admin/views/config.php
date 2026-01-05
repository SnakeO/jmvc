<?php
/**
 * JMVC Admin Settings View
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

if (!$isInitialized) {
    echo '<div class="wrap"><h1>' . esc_html__('JMVC Settings', 'jmvc') . '</h1>';
    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('Please initialize JMVC first from the Dashboard.', 'jmvc');
    echo ' <a href="' . esc_url(admin_url('admin.php?page=jmvc')) . '">' . esc_html__('Go to Dashboard', 'jmvc') . '</a>';
    echo '</p></div></div>';
    return;
}

$config = ConfigWriter::readConfig();
?>

<div class="wrap jmvc-admin">
    <h1><?php esc_html_e('JMVC Settings', 'jmvc'); ?></h1>

    <form id="jmvc-config-form" class="jmvc-config-form">
        <?php wp_nonce_field('jmvc_admin', 'jmvc_nonce'); ?>

        <!-- Developer Alerts Section -->
        <div class="jmvc-config-section">
            <h2><?php esc_html_e('Developer Alerts', 'jmvc'); ?></h2>
            <p class="description">
                <?php esc_html_e('Configure how developer alerts are sent when errors occur.', 'jmvc'); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="devalert_email"><?php esc_html_e('Email Address', 'jmvc'); ?></label>
                    </th>
                    <td>
                        <input type="email"
                               id="devalert_email"
                               name="config[devalert][email]"
                               value="<?php echo esc_attr($config['devalert']['email']); ?>"
                               class="regular-text"
                               placeholder="dev@example.com">
                        <p class="description">
                            <?php esc_html_e('Email address to receive developer alerts.', 'jmvc'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="slack_endpoint"><?php esc_html_e('Slack Webhook URL', 'jmvc'); ?></label>
                    </th>
                    <td>
                        <input type="url"
                               id="slack_endpoint"
                               name="config[devalert][slack_endpoint]"
                               value="<?php echo esc_attr($config['devalert']['slack_endpoint']); ?>"
                               class="large-text"
                               placeholder="https://hooks.slack.com/services/...">
                        <p class="description">
                            <?php esc_html_e('Slack incoming webhook URL for sending alerts.', 'jmvc'); ?>
                            <a href="https://api.slack.com/messaging/webhooks" target="_blank">
                                <?php esc_html_e('Create webhook', 'jmvc'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="slack_channel"><?php esc_html_e('Slack Channel', 'jmvc'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="slack_channel"
                               name="config[devalert][slack_channel]"
                               value="<?php echo esc_attr($config['devalert']['slack_channel']); ?>"
                               class="regular-text"
                               placeholder="#devalerts">
                        <p class="description">
                            <?php esc_html_e('Slack channel to post alerts to.', 'jmvc'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="slack_username"><?php esc_html_e('Slack Bot Username', 'jmvc'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="slack_username"
                               name="config[devalert][slack_username]"
                               value="<?php echo esc_attr($config['devalert']['slack_username']); ?>"
                               class="regular-text"
                               placeholder="JMVC Alert">
                        <p class="description">
                            <?php esc_html_e('Name displayed for the Slack bot.', 'jmvc'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Key-Value Store Section -->
        <div class="jmvc-config-section">
            <h2><?php esc_html_e('Key-Value Store', 'jmvc'); ?></h2>
            <p class="description">
                <?php esc_html_e('Configure the backend storage for the key-value store.', 'jmvc'); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="kvstore_type"><?php esc_html_e('Storage Type', 'jmvc'); ?></label>
                    </th>
                    <td>
                        <select id="kvstore_type" name="config[kvstore][type]">
                            <option value="sqlite" <?php selected($config['kvstore']['type'], 'sqlite'); ?>>
                                <?php esc_html_e('SQLite (Default)', 'jmvc'); ?>
                            </option>
                            <option value="redis" <?php selected($config['kvstore']['type'], 'redis'); ?>>
                                <?php esc_html_e('Redis', 'jmvc'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('SQLite is file-based and works everywhere. Redis requires a Redis server.', 'jmvc'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary jmvc-save-config-btn">
                <?php esc_html_e('Save Settings', 'jmvc'); ?>
            </button>
            <span class="jmvc-save-status"></span>
        </p>
    </form>

    <!-- Config File Location -->
    <div class="jmvc-config-info">
        <h3><?php esc_html_e('Configuration Files', 'jmvc'); ?></h3>
        <p>
            <?php esc_html_e('Settings are saved to PHP files in your theme:', 'jmvc'); ?>
        </p>
        <ul>
            <li><code><?php echo esc_html(Admin::getThemeJmvcPath() . 'config/devalert.php'); ?></code></li>
            <li><code><?php echo esc_html(Admin::getThemeJmvcPath() . 'config/kvstore.php'); ?></code></li>
        </ul>
    </div>
</div>
