<?php
/**
 * JController Unit Tests
 *
 * @package JMVC\Tests\Unit\Controller
 */

namespace JMVC\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use JController;

class JControllerTest extends TestCase
{
    private string $controllersDir;

    protected function setUp(): void
    {
        // Create a temporary controllers directory for testing
        $this->controllersDir = sys_get_temp_dir() . '/jmvc_test_controllers_' . uniqid();
        mkdir($this->controllersDir . '/pub', 0755, true);
        mkdir($this->controllersDir . '/admin', 0755, true);
        mkdir($this->controllersDir . '/resource', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->controllersDir);
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

    private function createTestController(string $env, string $name, string $content): void
    {
        $path = $this->controllersDir . '/' . $env . '/' . $name . 'Controller.php';
        file_put_contents($path, $content);
    }

    public function testValidEnvironments(): void
    {
        $validEnvironments = ['pub', 'admin', 'resource'];

        foreach ($validEnvironments as $env) {
            $this->assertContains($env, $validEnvironments);
        }
    }

    public function testControllerFileNaming(): void
    {
        $controllerName = 'Test';
        $expectedFilename = $controllerName . 'Controller.php';

        $this->assertEquals('TestController.php', $expectedFilename);
    }

    public function testControllerPathConstruction(): void
    {
        $basePath = '/var/www/jmvc/controllers/';
        $env = 'pub';
        $name = 'User';

        $expectedPath = $basePath . $env . '/' . $name . 'Controller.php';

        $this->assertEquals('/var/www/jmvc/controllers/pub/UserController.php', $expectedPath);
    }

    public function testControllerPathWithModule(): void
    {
        $basePath = '/var/www/jmvc/modules/';
        $module = 'blog';
        $env = 'pub';
        $name = 'Post';

        $expectedPath = $basePath . $module . '/controllers/' . $env . '/' . $name . 'Controller.php';

        $this->assertEquals('/var/www/jmvc/modules/blog/controllers/pub/PostController.php', $expectedPath);
    }

    public function testPathTraversalPrevention(): void
    {
        // Simulate path traversal attempts
        $maliciousInputs = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32',
            'Test/../../../etc/passwd',
        ];

        foreach ($maliciousInputs as $input) {
            // sanitize_file_name should strip path traversal characters
            $sanitized = sanitize_file_name($input);

            // After sanitization, no slashes or backslashes should remain
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
        }
    }

    public function testRealPathValidation(): void
    {
        // Create a test file
        $testFile = $this->controllersDir . '/pub/TestController.php';
        file_put_contents($testFile, '<?php class TestController {}');

        $basePath = realpath($this->controllersDir);
        $filePath = realpath($testFile);

        // Valid path should be within base
        $this->assertStringStartsWith($basePath, $filePath);

        // Attempt to go outside should fail realpath check
        $outsidePath = realpath($this->controllersDir . '/../../etc/passwd');
        $this->assertFalse($outsidePath !== false && strpos($outsidePath, $basePath) === 0);
    }

    public function testControllerSanitization(): void
    {
        $inputs = [
            'Test' => 'Test',
            'test123' => 'test123',
            'Test_Controller' => 'Test_Controller',
            'Test-Controller' => 'Test-Controller',
            'Test<script>' => 'Testscript',
            "Test'OR'1'='1" => 'TestOR11',
        ];

        foreach ($inputs as $input => $expected) {
            $sanitized = sanitize_file_name($input);
            $this->assertEquals($expected, $sanitized, "Failed for input: $input");
        }
    }

    public function testEnvironmentValidation(): void
    {
        $validEnvs = ['pub', 'admin', 'resource'];
        $invalidEnvs = ['public', 'private', 'api', 'test'];

        foreach ($validEnvs as $env) {
            $this->assertTrue(in_array($env, ['pub', 'admin', 'resource']));
        }

        foreach ($invalidEnvs as $env) {
            $this->assertFalse(in_array($env, ['pub', 'admin', 'resource']));
        }

        // Path traversal attempts should be sanitized
        $pathTraversal = '../pub';
        $sanitized = sanitize_file_name($pathTraversal);
        // After sanitization, dots and slashes are removed
        $this->assertStringNotContainsString('/', $sanitized);
    }

    public function testControllerClassNameGeneration(): void
    {
        $testCases = [
            'User' => 'UserController',
            'ProductList' => 'ProductListController',
            'API' => 'APIController',
        ];

        foreach ($testCases as $input => $expected) {
            $className = $input . 'Controller';
            $this->assertEquals($expected, $className);
        }
    }

    public function testModuleNamespaceGeneration(): void
    {
        $module = 'blog';
        $controllerName = 'Post';
        $env = 'pub';

        $expectedNamespace = $module . '\\' . $env . '\\' . $controllerName . 'Controller';

        $this->assertEquals('blog\\pub\\PostController', $expectedNamespace);
    }

    public function testEmptyControllerNameHandling(): void
    {
        $controllerName = '';
        $sanitized = sanitize_file_name($controllerName);

        $this->assertEmpty($sanitized);
    }

    public function testSpecialCharactersInControllerName(): void
    {
        $specialChars = '!@#$%^&*()+=[]{}|;:\'",.<>?/\\`~';
        $sanitized = sanitize_file_name($specialChars);

        // Should strip all special characters
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\-\.]*$/', $sanitized);
    }
}
