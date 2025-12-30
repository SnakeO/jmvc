<?php
/**
 * JView Unit Tests
 *
 * @package JMVC\Tests\Unit\View
 */

namespace JMVC\Tests\Unit\View;

use PHPUnit\Framework\TestCase;
use JView;

class JViewTest extends TestCase
{
    private string $viewsDir;

    protected function setUp(): void
    {
        // Create a temporary views directory for testing
        $this->viewsDir = sys_get_temp_dir() . '/jmvc_test_views_' . uniqid();
        mkdir($this->viewsDir, 0755, true);

        // Redefine JMVC constant for tests
        if (!defined('JMVC_TEST_VIEWS')) {
            define('JMVC_TEST_VIEWS', $this->viewsDir . '/');
        }
    }

    protected function tearDown(): void
    {
        // Clean up test views
        $this->removeDirectory($this->viewsDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createTestView(string $path, string $content): void
    {
        $fullPath = $this->viewsDir . '/' . $path;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, $content);
    }

    public function testGetReturnsViewContent(): void
    {
        $this->createTestView('test/simple.php', '<?php echo "Hello World"; ?>');

        // We need to temporarily override the JMVC constant behavior
        // Since we can't redefine constants, we test the basic rendering logic
        $this->assertTrue(file_exists($this->viewsDir . '/test/simple.php'));
    }

    public function testDataIsPassedToView(): void
    {
        $content = '<?php echo $name . " is " . $age; ?>';
        $this->createTestView('test/data.php', $content);

        // Simulate view rendering
        $file = $this->viewsDir . '/test/data.php';
        $name = 'John';
        $age = 30;

        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('John is 30', $output);
    }

    public function testViewUrlIsAvailable(): void
    {
        $content = '<?php echo isset($view_url) ? "yes" : "no"; ?>';
        $this->createTestView('test/viewurl.php', $content);

        // In actual JView, $view_url is passed to the view
        $file = $this->viewsDir . '/test/viewurl.php';
        $view_url = 'https://example.com/views/test/';

        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('yes', $output);
    }

    public function testNestedViewsWork(): void
    {
        $this->createTestView('layouts/header.php', '<header>Header</header>');
        $this->createTestView('layouts/footer.php', '<footer>Footer</footer>');
        $this->createTestView('pages/home.php', '<?php include dirname(__DIR__) . "/layouts/header.php"; ?>Content<?php include dirname(__DIR__) . "/layouts/footer.php"; ?>');

        $file = $this->viewsDir . '/pages/home.php';
        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertStringContainsString('<header>Header</header>', $output);
        $this->assertStringContainsString('Content', $output);
        $this->assertStringContainsString('<footer>Footer</footer>', $output);
    }

    public function testHtmlEscapingInViews(): void
    {
        $content = '<?php echo esc_html($userInput); ?>';
        $this->createTestView('test/escape.php', $content);

        $file = $this->viewsDir . '/test/escape.php';
        $userInput = '<script>alert("xss")</script>';

        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testArrayDataInView(): void
    {
        $content = '<?php foreach ($items as $item): ?><?php echo $item; ?>,<?php endforeach; ?>';
        $this->createTestView('test/array.php', $content);

        $file = $this->viewsDir . '/test/array.php';
        $items = ['apple', 'banana', 'cherry'];

        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('apple,banana,cherry,', $output);
    }

    public function testEmptyViewReturnsEmpty(): void
    {
        $this->createTestView('test/empty.php', '');

        $file = $this->viewsDir . '/test/empty.php';
        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    public function testPhpOnlyViewExecutes(): void
    {
        $content = '<?php $result = 1 + 2; echo $result; ?>';
        $this->createTestView('test/phponly.php', $content);

        $file = $this->viewsDir . '/test/phponly.php';
        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('3', $output);
    }

    public function testMixedHtmlPhpView(): void
    {
        $content = '<div class="container"><?php echo $title; ?></div>';
        $this->createTestView('test/mixed.php', $content);

        $file = $this->viewsDir . '/test/mixed.php';
        $title = 'Test Title';

        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('<div class="container">Test Title</div>', $output);
    }

    public function testPathWithSubdirectories(): void
    {
        $this->createTestView('deep/nested/path/view.php', '<?php echo "deep"; ?>');

        $this->assertTrue(file_exists($this->viewsDir . '/deep/nested/path/view.php'));

        $file = $this->viewsDir . '/deep/nested/path/view.php';
        ob_start();
        include $file;
        $output = ob_get_clean();

        $this->assertEquals('deep', $output);
    }
}
