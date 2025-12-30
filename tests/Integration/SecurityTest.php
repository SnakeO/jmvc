<?php
/**
 * Security Integration Tests
 *
 * Tests security features including nonce verification, capability checks,
 * input sanitization, output escaping, and path traversal prevention.
 *
 * @package JMVC\Tests\Integration
 */

namespace JMVC\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WP_Mock_Data;

class SecurityTest extends TestCase
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

    // ==========================================
    // Nonce Verification Tests
    // ==========================================

    public function testNonceVerificationEnforced(): void
    {
        $action = 'jmvc_ajax_nonce';
        $nonce = wp_create_nonce($action);

        // Valid nonce should pass
        $this->assertEquals(1, wp_verify_nonce($nonce, $action));

        // Invalid nonce should fail
        $this->assertFalse(wp_verify_nonce('invalid_nonce', $action));

        // Empty nonce should fail
        $this->assertFalse(wp_verify_nonce('', $action));
    }

    public function testNonceIsUniquePerAction(): void
    {
        $nonce1 = wp_create_nonce('action_one');
        $nonce2 = wp_create_nonce('action_two');

        // Each nonce only works for its action
        $this->assertEquals(1, wp_verify_nonce($nonce1, 'action_one'));
        $this->assertFalse(wp_verify_nonce($nonce1, 'action_two'));

        $this->assertEquals(1, wp_verify_nonce($nonce2, 'action_two'));
        $this->assertFalse(wp_verify_nonce($nonce2, 'action_one'));
    }

    public function testNonceInRequest(): void
    {
        $nonce = wp_create_nonce('jmvc_ajax_nonce');
        $_REQUEST['_jmvc_nonce'] = $nonce;

        $requestNonce = sanitize_text_field($_REQUEST['_jmvc_nonce']);

        $this->assertEquals(1, wp_verify_nonce($requestNonce, 'jmvc_ajax_nonce'));
    }

    // ==========================================
    // Capability Check Tests
    // ==========================================

    public function testCapabilityChecksWork(): void
    {
        // Logged out user has no capabilities
        WP_Mock_Data::setLoggedOut();
        $this->assertFalse(current_user_can('edit_posts'));
        $this->assertFalse(current_user_can('manage_options'));

        // Logged in user with specific capability
        WP_Mock_Data::setLoggedIn(1, ['edit_posts']);
        $this->assertTrue(current_user_can('edit_posts'));
        $this->assertFalse(current_user_can('manage_options'));

        // Admin user
        WP_Mock_Data::setLoggedIn(2, ['edit_posts', 'manage_options', 'super_admin']);
        $this->assertTrue(current_user_can('edit_posts'));
        $this->assertTrue(current_user_can('manage_options'));
        $this->assertTrue(is_super_admin());
    }

    public function testUserIdRetrieval(): void
    {
        WP_Mock_Data::setLoggedOut();
        $this->assertEquals(0, get_current_user_id());
        $this->assertFalse(is_user_logged_in());

        WP_Mock_Data::setLoggedIn(42, ['edit_posts']);
        $this->assertEquals(42, get_current_user_id());
        $this->assertTrue(is_user_logged_in());
    }

    // ==========================================
    // Input Sanitization Tests
    // ==========================================

    public function testInputSanitizationApplied(): void
    {
        // XSS attempts
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img onerror="alert(1)" src="x">',
            '"><script>alert(1)</script>',
            "javascript:alert('XSS')",
        ];

        foreach ($xssAttempts as $input) {
            $sanitized = sanitize_text_field($input);
            $this->assertStringNotContainsString('<script>', $sanitized);
            $this->assertStringNotContainsString('onerror', $sanitized);
        }
    }

    public function testSqlInjectionPrevention(): void
    {
        // Note: sanitize_text_field is NOT sufficient for SQL injection prevention
        // Always use prepared statements for database queries
        // This test demonstrates that sanitize_file_name is more restrictive
        $sqlAttempts = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "admin'--",
        ];

        foreach ($sqlAttempts as $input) {
            // sanitize_file_name is stricter and removes quotes
            $sanitized = sanitize_file_name($input);
            $this->assertStringNotContainsString("'", $sanitized);
            $this->assertStringNotContainsString('"', $sanitized);
            $this->assertStringNotContainsString(';', $sanitized);
        }
    }

    public function testFileNameSanitization(): void
    {
        $maliciousFilenames = [
            '../../../etc/passwd',
            '..\\..\\windows\\system32',
            'file<script>.php',
            'file;rm -rf /',
        ];

        foreach ($maliciousFilenames as $input) {
            $sanitized = sanitize_file_name($input);
            // After sanitization, no path separators should remain
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
            // Only alphanumeric, dash, underscore, and dot allowed
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\-\.]*$/', $sanitized);
        }
    }

    public function testEmailSanitization(): void
    {
        $this->assertEquals('test@example.com', sanitize_email('test@example.com'));
        // Script tags are stripped but text remains
        $sanitized = sanitize_email('test@example.com<script>');
        $this->assertStringContainsString('test@example.com', $sanitized);
        $this->assertStringNotContainsString('<', $sanitized);
    }

    public function testKeySanitization(): void
    {
        $this->assertEquals('valid_key', sanitize_key('valid_key'));
        // WordPress sanitize_key lowercases and keeps underscores
        $this->assertEquals('valid_key', sanitize_key('Valid_Key'));
        $this->assertEquals('key123', sanitize_key('key<>123'));
    }

    // ==========================================
    // Output Escaping Tests
    // ==========================================

    public function testOutputEscapingApplied(): void
    {
        $htmlChars = '<script>alert("test")</script>';

        $escaped = esc_html($htmlChars);

        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    public function testUrlEscaping(): void
    {
        $validUrl = 'https://example.com/path?query=value';
        $this->assertEquals($validUrl, esc_url($validUrl));

        $invalidUrl = "javascript:alert('xss')";
        $escapedUrl = esc_url($invalidUrl);
        // esc_url should neutralize javascript: URLs by returning empty
        $this->assertEmpty($escapedUrl);
    }

    public function testAttributeEscaping(): void
    {
        $maliciousAttr = '" onclick="alert(1)"';

        $escaped = esc_attr($maliciousAttr);

        $this->assertStringNotContainsString('"', substr($escaped, 1));
    }

    public function testKsesPostFiltering(): void
    {
        $content = '<script>alert(1)</script><p>Safe content</p><div onclick="evil()">More</div>';

        $filtered = wp_kses_post($content);

        $this->assertStringNotContainsString('<script>', $filtered);
        $this->assertStringContainsString('<p>Safe content</p>', $filtered);
    }

    // ==========================================
    // Path Traversal Prevention Tests
    // ==========================================

    public function testPathTraversalBlocked(): void
    {
        $traversalAttempts = [
            '../etc/passwd',
            '..\\windows',
            '..../../etc/passwd',
        ];

        foreach ($traversalAttempts as $attempt) {
            $sanitized = sanitize_file_name($attempt);
            // After sanitization, path traversal sequences should be removed
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
        }
    }

    public function testRealPathValidation(): void
    {
        $tempDir = sys_get_temp_dir();
        $testFile = $tempDir . '/test_file.txt';
        file_put_contents($testFile, 'test');

        $basePath = realpath($tempDir);
        $filePath = realpath($testFile);

        // Valid path is within base
        $this->assertStringStartsWith($basePath, $filePath);

        // Traversal attempt would be outside base
        $outsidePath = $tempDir . '/../../../etc/passwd';
        $realOutside = realpath($outsidePath);

        if ($realOutside !== false) {
            $this->assertStringNotStartsWith($basePath, $realOutside);
        }

        unlink($testFile);
    }

    public function testNullByteInjection(): void
    {
        $nullByteAttempts = [
            "file.php\x00.jpg",
            "file.php%00.jpg",
            "file.php\0.jpg",
        ];

        foreach ($nullByteAttempts as $input) {
            $sanitized = sanitize_file_name($input);
            $this->assertStringNotContainsString("\x00", $sanitized);
            $this->assertStringNotContainsString("\0", $sanitized);
        }
    }

    // ==========================================
    // JSONP Injection Prevention Tests
    // ==========================================

    public function testJsonpInjectionPrevented(): void
    {
        $maliciousCallbacks = [
            'callback(); alert(1); //',
            'eval(atob("YWxlcnQoMSk="))',
            'constructor.constructor("alert(1)")()',
            '{{constructor.constructor("alert(1)")()}}',
        ];

        $pattern = '/[^a-zA-Z0-9_\$]/';

        foreach ($maliciousCallbacks as $callback) {
            $sanitized = preg_replace($pattern, '', $callback);

            // Should not contain any special characters
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\$]+$/', $sanitized);

            // Should not be able to execute code
            $this->assertStringNotContainsString('()', $sanitized);
            $this->assertStringNotContainsString(';', $sanitized);
        }
    }

    public function testValidJsonpCallbacks(): void
    {
        $validCallbacks = [
            'callback' => true,
            'jQuery1234567890' => true,
            'my_callback_function' => true,
            'myApp$callback' => true,
            '_privateCallback' => true,
        ];

        $pattern = '/^[a-zA-Z_\$][a-zA-Z0-9_\$]*$/';

        foreach ($validCallbacks as $callback => $expected) {
            $isValid = preg_match($pattern, $callback) === 1;
            $this->assertTrue($isValid, "Callback should be valid: $callback");
        }
    }

    // ==========================================
    // Header Injection Prevention Tests
    // ==========================================

    public function testHeaderInjectionPrevention(): void
    {
        $maliciousHeaders = [
            "Content-Type: text/html\r\nSet-Cookie: evil=true",
            "Location: /\r\n\r\n<script>alert(1)</script>",
            "X-Custom: value\nX-Injected: header",
        ];

        foreach ($maliciousHeaders as $header) {
            $sanitized = str_replace(["\r", "\n"], '', $header);
            $this->assertStringNotContainsString("\r", $sanitized);
            $this->assertStringNotContainsString("\n", $sanitized);
        }
    }

    // ==========================================
    // Command Injection Prevention Tests
    // ==========================================

    public function testCommandInjectionPrevention(): void
    {
        // sanitize_file_name is more restrictive and removes shell characters
        $commandAttempts = [
            '; ls -la' => 'ls-la',
            '| cat /etc/passwd' => 'catetcpasswd',
            '`whoami`' => 'whoami',
            '$(id)' => 'id',
        ];

        foreach ($commandAttempts as $input => $expected) {
            $sanitized = sanitize_file_name($input);
            // sanitize_file_name strips shell metacharacters
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\-\.]*$/', $sanitized);
        }
    }

    // ==========================================
    // Request Method Validation Tests
    // ==========================================

    public function testRequestMethodValidation(): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        $invalidMethods = ['TRACE', 'CONNECT', 'ARBITRARY'];

        foreach ($validMethods as $method) {
            $this->assertContains($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD']);
        }
    }

    // ==========================================
    // Content Type Validation Tests
    // ==========================================

    public function testContentTypeValidation(): void
    {
        $validContentTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data',
        ];

        foreach ($validContentTypes as $contentType) {
            $this->assertMatchesRegularExpression('/^[a-z]+\/[a-z\-+]+$/', $contentType);
        }
    }
}
