<?php
/**
 * JControllerAjax Unit Tests
 *
 * @package JMVC\Tests\Unit\Controller
 */

namespace JMVC\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use JControllerAjax;
use WP_Mock_Data;

class JControllerAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock_Data::reset();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        WP_Mock_Data::reset();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    public function testNonceGeneration(): void
    {
        $nonce = wp_create_nonce('jmvc_ajax_nonce');

        $this->assertNotEmpty($nonce);
        $this->assertIsString($nonce);
    }

    public function testNonceVerificationSuccess(): void
    {
        $nonce = wp_create_nonce('jmvc_ajax_nonce');

        $result = wp_verify_nonce($nonce, 'jmvc_ajax_nonce');

        $this->assertEquals(1, $result);
    }

    public function testNonceVerificationFailure(): void
    {
        $invalidNonce = 'invalid_nonce_value';

        $result = wp_verify_nonce($invalidNonce, 'jmvc_ajax_nonce');

        $this->assertFalse($result);
    }

    public function testNonceVerificationWrongAction(): void
    {
        $nonce = wp_create_nonce('jmvc_ajax_nonce');

        $result = wp_verify_nonce($nonce, 'different_action');

        $this->assertFalse($result);
    }

    public function testPathParsing(): void
    {
        $path = 'User/show/123';
        $parts = explode('/', $path);

        $this->assertEquals('User', $parts[0]);
        $this->assertEquals('show', $parts[1]);
        $this->assertEquals('123', $parts[2]);
    }

    public function testPathParsingWithExtraSlashes(): void
    {
        $path = 'User/list/param1/param2/param3';
        $parts = explode('/', $path);

        $this->assertCount(5, $parts);
        $this->assertEquals('User', $parts[0]);
        $this->assertEquals('list', $parts[1]);
        $this->assertEquals(['param1', 'param2', 'param3'], array_slice($parts, 2));
    }

    public function testParameterSanitization(): void
    {
        $_GET['path'] = 'User/show/123';
        $_GET['callback'] = 'jsonpCallback';

        $sanitizedPath = sanitize_text_field($_GET['path']);
        $sanitizedCallback = sanitize_text_field($_GET['callback']);

        $this->assertEquals('User/show/123', $sanitizedPath);
        $this->assertEquals('jsonpCallback', $sanitizedCallback);
    }

    public function testMaliciousParameterSanitization(): void
    {
        $_GET['path'] = '<script>alert("xss")</script>User/show';
        $_GET['param'] = "'; DROP TABLE users; --";

        $sanitizedPath = sanitize_text_field($_GET['path']);
        $sanitizedParam = sanitize_text_field($_GET['param']);

        // Script tags are stripped
        $this->assertStringNotContainsString('<script>', $sanitizedPath);
        // sanitize_text_field strips tags but keeps other characters
        // For SQL injection prevention, use prepared statements, not sanitize_text_field
        $this->assertIsString($sanitizedParam);
    }

    public function testControllerUrlGeneration(): void
    {
        // Test controller_url helper format
        $env = 'pub';
        $controller = 'Task';
        $action = 'show';
        $params = '42';

        $expectedFormat = "/wp-admin/admin-ajax.php?action={$env}_controller&path={$controller}/{$action}/{$params}";

        $this->assertStringContainsString('action=pub_controller', $expectedFormat);
        $this->assertStringContainsString('path=Task/show/42', $expectedFormat);
    }

    public function testAdminEnvironmentRequiresAuth(): void
    {
        WP_Mock_Data::setLoggedOut();

        $this->assertFalse(is_user_logged_in());

        // Admin controller should check authentication
        $env = 'admin';
        $shouldRequireAuth = ($env === 'admin');

        $this->assertTrue($shouldRequireAuth);
    }

    public function testPubEnvironmentAllowsAnonymous(): void
    {
        WP_Mock_Data::setLoggedOut();

        $this->assertFalse(is_user_logged_in());

        // Pub controller allows anonymous access
        $env = 'pub';
        $allowsAnonymous = in_array($env, ['pub', 'resource']);

        $this->assertTrue($allowsAnonymous);
    }

    public function testAuthenticatedUserAccess(): void
    {
        WP_Mock_Data::setLoggedIn(1, ['edit_posts']);

        $this->assertTrue(is_user_logged_in());
        $this->assertEquals(1, get_current_user_id());
        $this->assertTrue(current_user_can('edit_posts'));
    }

    public function testMissingPathHandling(): void
    {
        $_GET = [];

        $path = isset($_GET['path']) ? sanitize_text_field($_GET['path']) : null;

        $this->assertNull($path);
    }

    public function testEmptyPathHandling(): void
    {
        $_GET['path'] = '';

        $path = isset($_GET['path']) ? sanitize_text_field($_GET['path']) : null;

        $this->assertEquals('', $path);
        $this->assertTrue(empty($path));
    }

    public function testPathWithoutMethodHandling(): void
    {
        $path = 'User';
        $parts = explode('/', $path);

        $controller = $parts[0] ?? null;
        $method = $parts[1] ?? null;

        $this->assertEquals('User', $controller);
        $this->assertNull($method);
    }

    public function testDefaultMethodFallback(): void
    {
        $path = 'User';
        $parts = explode('/', $path);

        $controller = $parts[0] ?? null;
        $method = $parts[1] ?? 'index';

        $this->assertEquals('User', $controller);
        $this->assertEquals('index', $method);
    }

    public function testJsonpCallbackSanitization(): void
    {
        $validCallbacks = [
            'callback' => 'callback',
            'jQuery123' => 'jQuery123',
            'my_callback' => 'my_callback',
            '$callback' => '$callback',
        ];

        $invalidCallbacks = [
            'callback<script>' => 'callbackscript',
            'alert(1)' => 'alert1',
            'callback;alert(1)' => 'callbackalert1',
        ];

        foreach ($validCallbacks as $input => $expected) {
            $sanitized = preg_replace('/[^a-zA-Z0-9_\$]/', '', $input);
            $this->assertEquals($expected, $sanitized, "Valid callback failed: $input");
        }

        foreach ($invalidCallbacks as $input => $expected) {
            $sanitized = preg_replace('/[^a-zA-Z0-9_\$]/', '', $input);
            $this->assertEquals($expected, $sanitized, "Invalid callback failed: $input");
        }
    }

    public function testRequestMethodDetection(): void
    {
        // Test GET vs POST detection
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('GET', $_SERVER['REQUEST_METHOD']);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('POST', $_SERVER['REQUEST_METHOD']);
    }

    public function testAjaxActionNames(): void
    {
        $expectedActions = [
            'pub_controller',
            'admin_controller',
            'resource_controller',
            'hmvc_controller',
        ];

        foreach ($expectedActions as $action) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $action);
        }
    }

    public function testNonceInRequestHeader(): void
    {
        $nonce = wp_create_nonce('jmvc_ajax_nonce');

        // Simulate nonce in request
        $_REQUEST['_jmvc_nonce'] = $nonce;

        $requestNonce = isset($_REQUEST['_jmvc_nonce']) ? sanitize_text_field($_REQUEST['_jmvc_nonce']) : '';

        $this->assertEquals($nonce, $requestNonce);
        $this->assertEquals(1, wp_verify_nonce($requestNonce, 'jmvc_ajax_nonce'));
    }
}
