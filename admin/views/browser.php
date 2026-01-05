<?php
/**
 * JMVC Admin Components Browser View
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
    echo '<div class="wrap"><h1>' . esc_html__('JMVC Components', 'jmvc') . '</h1>';
    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('Please initialize JMVC first from the Dashboard.', 'jmvc');
    echo ' <a href="' . esc_url(admin_url('admin.php?page=jmvc')) . '">' . esc_html__('Go to Dashboard', 'jmvc') . '</a>';
    echo '</p></div></div>';
    return;
}

$controllers = Browser::getControllers();
$models = Browser::getModels();
$views = Browser::getViews();

$activeTab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'controllers';
?>

<div class="wrap jmvc-admin">
    <h1><?php esc_html_e('JMVC Components', 'jmvc'); ?></h1>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(add_query_arg('tab', 'controllers')); ?>"
           class="nav-tab <?php echo $activeTab === 'controllers' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Controllers', 'jmvc'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'models')); ?>"
           class="nav-tab <?php echo $activeTab === 'models' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Models', 'jmvc'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'views')); ?>"
           class="nav-tab <?php echo $activeTab === 'views' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Views', 'jmvc'); ?>
        </a>
    </nav>

    <div class="jmvc-browser-content">
        <?php if ($activeTab === 'controllers'): ?>
            <!-- Controllers Tab -->
            <div class="jmvc-tab-content">
                <?php foreach (['pub' => __('Public', 'jmvc'), 'admin' => __('Admin', 'jmvc'), 'resource' => __('Resource', 'jmvc')] as $env => $label): ?>
                    <div class="jmvc-component-group">
                        <h3>
                            <?php echo esc_html($label); ?>
                            <span class="count">(<?php echo count($controllers[$env]); ?>)</span>
                        </h3>
                        <?php if (empty($controllers[$env])): ?>
                            <p class="jmvc-empty">
                                <?php esc_html_e('No controllers found.', 'jmvc'); ?>
                            </p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Controller', 'jmvc'); ?></th>
                                        <th><?php esc_html_e('File', 'jmvc'); ?></th>
                                        <th><?php esc_html_e('Last Modified', 'jmvc'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($controllers[$env] as $filename => $info): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($info['name']); ?></strong></td>
                                            <td><code><?php echo esc_html($filename); ?></code></td>
                                            <td><?php echo esc_html($info['modified']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($activeTab === 'models'): ?>
            <!-- Models Tab -->
            <div class="jmvc-tab-content">
                <div class="jmvc-component-group">
                    <h3>
                        <?php esc_html_e('Models', 'jmvc'); ?>
                        <span class="count">(<?php echo count($models); ?>)</span>
                    </h3>
                    <?php if (empty($models)): ?>
                        <p class="jmvc-empty">
                            <?php esc_html_e('No models found.', 'jmvc'); ?>
                        </p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Model', 'jmvc'); ?></th>
                                    <th><?php esc_html_e('File', 'jmvc'); ?></th>
                                    <th><?php esc_html_e('Last Modified', 'jmvc'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($models as $filename => $info): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($info['name']); ?></strong></td>
                                        <td><code><?php echo esc_html($filename); ?></code></td>
                                        <td><?php echo esc_html($info['modified']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'views'): ?>
            <!-- Views Tab -->
            <div class="jmvc-tab-content">
                <div class="jmvc-component-group">
                    <h3>
                        <?php esc_html_e('Views', 'jmvc'); ?>
                    </h3>
                    <?php if (empty($views)): ?>
                        <p class="jmvc-empty">
                            <?php esc_html_e('No views found.', 'jmvc'); ?>
                        </p>
                    <?php else: ?>
                        <div class="jmvc-view-tree">
                            <?php echo renderViewTree($views); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- File Path Info -->
    <div class="jmvc-browser-info">
        <p>
            <strong><?php esc_html_e('Base Path:', 'jmvc'); ?></strong>
            <code><?php echo esc_html(Admin::getThemeJmvcPath()); ?></code>
        </p>
    </div>
</div>

<?php
/**
 * Render view tree recursively
 *
 * @param array<string, mixed> $items
 * @param int $depth
 * @return string
 */
function renderViewTree(array $items, int $depth = 0): string
{
    if (empty($items)) {
        return '';
    }

    $html = '<ul class="jmvc-tree' . ($depth === 0 ? ' jmvc-tree-root' : '') . '">';

    foreach ($items as $key => $item) {
        if (isset($item['isDir']) && $item['isDir']) {
            $html .= '<li class="jmvc-tree-folder">';
            $html .= '<span class="dashicons dashicons-category"></span> ';
            $html .= '<strong>' . esc_html($item['name']) . '</strong>';
            if (!empty($item['children'])) {
                $html .= renderViewTree($item['children'], $depth + 1);
            }
            $html .= '</li>';
        } else {
            $html .= '<li class="jmvc-tree-file">';
            $html .= '<span class="dashicons dashicons-media-code"></span> ';
            $html .= esc_html($item['name']) . '.php';
            $html .= ' <span class="jmvc-tree-date">' . esc_html($item['modified']) . '</span>';
            $html .= '</li>';
        }
    }

    $html .= '</ul>';

    return $html;
}
?>
