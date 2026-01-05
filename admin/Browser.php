<?php
/**
 * JMVC Browser - Component scanner and browser
 *
 * @package JMVC\Admin
 */

declare(strict_types=1);

namespace JMVC\Admin;

/**
 * Scans and displays models, views, and controllers
 */
class Browser
{
    /**
     * Get all controllers organized by environment
     *
     * @return array<string, array<string, array{name: string, path: string, modified: string}>>
     */
    public static function getControllers(): array
    {
        $basePath = Admin::getThemeJmvcPath() . 'controllers/';
        $environments = ['pub', 'admin', 'resource'];
        $controllers = [];

        foreach ($environments as $env) {
            $envPath = $basePath . $env . '/';
            $controllers[$env] = self::scanDirectory($envPath, 'Controller.php');
        }

        return $controllers;
    }

    /**
     * Get all models
     *
     * @return array<string, array{name: string, path: string, modified: string}>
     */
    public static function getModels(): array
    {
        $basePath = Admin::getThemeJmvcPath() . 'models/';
        return self::scanDirectory($basePath, '.php');
    }

    /**
     * Get all views organized by directory
     *
     * @return array<string, array{name: string, path: string, modified: string, isDir: bool}>
     */
    public static function getViews(): array
    {
        $basePath = Admin::getThemeJmvcPath() . 'views/';
        return self::scanViewsRecursive($basePath);
    }

    /**
     * Scan a directory for PHP files
     *
     * @param string $path
     * @param string $suffix
     * @return array<string, array{name: string, path: string, modified: string}>
     */
    private static function scanDirectory(string $path, string $suffix): array
    {
        $files = [];

        if (!is_dir($path)) {
            return $files;
        }

        $iterator = new \DirectoryIterator($path);

        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }

            $filename = $file->getFilename();

            // Skip hidden files and non-matching files
            if (strpos($filename, '.') === 0) {
                continue;
            }

            if (!str_ends_with($filename, $suffix) && $suffix !== '.php') {
                continue;
            }

            if ($suffix === '.php' && !str_ends_with($filename, '.php')) {
                continue;
            }

            $name = str_replace($suffix, '', $filename);
            if ($suffix === '.php') {
                $name = str_replace('.php', '', $filename);
            }

            $files[$filename] = [
                'name' => $name,
                'path' => $file->getPathname(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        ksort($files);

        return $files;
    }

    /**
     * Recursively scan views directory
     *
     * @param string $path
     * @param string $prefix
     * @return array<string, array{name: string, path: string, modified: string, isDir: bool}>
     */
    private static function scanViewsRecursive(string $path, string $prefix = ''): array
    {
        $items = [];

        if (!is_dir($path)) {
            return $items;
        }

        $iterator = new \DirectoryIterator($path);

        foreach ($iterator as $file) {
            if ($file->isDot()) {
                continue;
            }

            $filename = $file->getFilename();

            // Skip hidden files
            if (strpos($filename, '.') === 0) {
                continue;
            }

            $key = $prefix . $filename;

            if ($file->isDir()) {
                $items[$key] = [
                    'name' => $filename,
                    'path' => $file->getPathname(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'isDir' => true,
                    'children' => self::scanViewsRecursive($file->getPathname(), $key . '/'),
                ];
            } elseif (str_ends_with($filename, '.php')) {
                $items[$key] = [
                    'name' => str_replace('.php', '', $filename),
                    'path' => $file->getPathname(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'isDir' => false,
                ];
            }
        }

        ksort($items);

        return $items;
    }

    /**
     * Get file contents for viewing
     *
     * @param string $path
     * @return array{success: bool, content?: string, error?: string}
     */
    public static function getFileContents(string $path): array
    {
        // Security: Ensure path is within theme's jmvc directory
        $basePath = realpath(Admin::getThemeJmvcPath());
        $realPath = realpath($path);

        if ($basePath === false || $realPath === false) {
            return [
                'success' => false,
                'error' => __('Invalid path', 'jmvc'),
            ];
        }

        if (strpos($realPath, $basePath) !== 0) {
            return [
                'success' => false,
                'error' => __('Access denied', 'jmvc'),
            ];
        }

        if (!is_file($realPath)) {
            return [
                'success' => false,
                'error' => __('File not found', 'jmvc'),
            ];
        }

        $content = file_get_contents($realPath);

        if ($content === false) {
            return [
                'success' => false,
                'error' => __('Could not read file', 'jmvc'),
            ];
        }

        return [
            'success' => true,
            'content' => $content,
        ];
    }

    /**
     * Get component counts
     *
     * @return array{controllers: int, models: int, views: int}
     */
    public static function getCounts(): array
    {
        $controllers = self::getControllers();
        $controllerCount = 0;
        foreach ($controllers as $env => $files) {
            $controllerCount += count($files);
        }

        $models = self::getModels();
        $views = self::getViews();

        return [
            'controllers' => $controllerCount,
            'models' => count($models),
            'views' => self::countViews($views),
        ];
    }

    /**
     * Count views recursively
     *
     * @param array<string, mixed> $views
     * @return int
     */
    private static function countViews(array $views): int
    {
        $count = 0;

        foreach ($views as $item) {
            if (isset($item['isDir']) && $item['isDir'] && isset($item['children'])) {
                $count += self::countViews($item['children']);
            } else {
                $count++;
            }
        }

        return $count;
    }
}
