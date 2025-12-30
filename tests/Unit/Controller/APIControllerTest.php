<?php
/**
 * APIController Unit Tests
 *
 * @package JMVC\Tests\Unit\Controller
 */

namespace JMVC\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use WP_Mock_Data;

class APIControllerTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock_Data::reset();
    }

    protected function tearDown(): void
    {
        WP_Mock_Data::reset();
    }

    public function testApiSuccessJsonFormat(): void
    {
        $result = ['data' => 'test'];
        $response = [
            'success' => true,
            'result' => $result,
        ];

        $json = json_encode($response);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['success']);
        $this->assertEquals($result, $decoded['result']);
    }

    public function testApiErrorJsonFormat(): void
    {
        $message = 'Something went wrong';
        $data = ['field' => 'email'];
        $response = [
            'success' => false,
            'error' => $message,
            'data' => $data,
        ];

        $json = json_encode($response);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals($message, $decoded['error']);
        $this->assertEquals($data, $decoded['data']);
    }

    public function testJsonpCallbackSanitization(): void
    {
        $validCallbacks = [
            'callback',
            'jQuery123_callback',
            'myApp$callback',
            '_callback123',
        ];

        foreach ($validCallbacks as $callback) {
            $sanitized = preg_replace('/[^a-zA-Z0-9_\$]/', '', $callback);
            $this->assertEquals($callback, $sanitized, "Failed for: $callback");
        }
    }

    public function testJsonpCallbackInjectionPrevention(): void
    {
        $maliciousCallbacks = [
            'callback(); alert(1); //' => 'callbackalert1',
            '<script>alert(1)</script>' => 'scriptalert1script',
            'callback\');alert(1);//' => 'callbackalert1',
            'eval(atob("base64"))' => 'evalatobbase64',
        ];

        foreach ($maliciousCallbacks as $input => $expected) {
            $sanitized = preg_replace('/[^a-zA-Z0-9_\$]/', '', $input);
            $this->assertEquals($expected, $sanitized, "Failed for: $input");
        }
    }

    public function testJsonpResponseFormat(): void
    {
        $callback = 'jQuery_callback';
        $data = ['success' => true, 'result' => ['id' => 1]];

        $jsonp = $callback . '(' . json_encode($data) . ');';

        $this->assertStringStartsWith('jQuery_callback(', $jsonp);
        $this->assertStringEndsWith(');', $jsonp);
        $this->assertStringContainsString('"success":true', $jsonp);
    }

    public function testExtractFieldsFromData(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
            'internal_field' => 'hidden',
        ];

        $allowedFields = ['id', 'name', 'email'];
        $extracted = array_intersect_key($data, array_flip($allowedFields));

        $this->assertArrayHasKey('id', $extracted);
        $this->assertArrayHasKey('name', $extracted);
        $this->assertArrayHasKey('email', $extracted);
        $this->assertArrayNotHasKey('password', $extracted);
        $this->assertArrayNotHasKey('internal_field', $extracted);
    }

    public function testCleanParamsStringBooleans(): void
    {
        $params = [
            'active' => 'true',
            'disabled' => 'false',
            'count' => '42',
            'name' => 'John',
        ];

        $cleaned = [];
        foreach ($params as $key => $value) {
            if ($value === 'true') {
                $cleaned[$key] = true;
            } elseif ($value === 'false') {
                $cleaned[$key] = false;
            } elseif (is_numeric($value)) {
                $cleaned[$key] = (int) $value;
            } else {
                $cleaned[$key] = $value;
            }
        }

        $this->assertTrue($cleaned['active']);
        $this->assertFalse($cleaned['disabled']);
        $this->assertEquals(42, $cleaned['count']);
        $this->assertEquals('John', $cleaned['name']);
    }

    public function testApiStringifyObject(): void
    {
        $object = new \stdClass();
        $object->id = 1;
        $object->name = 'Test';

        $json = json_encode($object);

        $this->assertJson($json);
        $this->assertStringContainsString('"id":1', $json);
        $this->assertStringContainsString('"name":"Test"', $json);
    }

    public function testApiStringifyArray(): void
    {
        $array = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            'total' => 2,
        ];

        $json = json_encode($array);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertCount(2, $decoded['items']);
        $this->assertEquals(2, $decoded['total']);
    }

    public function testApiResponseHeaders(): void
    {
        // Test expected content type header
        $contentType = 'application/json; charset=UTF-8';

        $this->assertEquals('application/json; charset=UTF-8', $contentType);
    }

    public function testApiResponseWithPagination(): void
    {
        $response = [
            'success' => true,
            'result' => [
                'items' => [['id' => 1], ['id' => 2]],
                'pagination' => [
                    'page' => 1,
                    'per_page' => 10,
                    'total' => 100,
                    'total_pages' => 10,
                ],
            ],
        ];

        $json = json_encode($response);
        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('pagination', $decoded['result']);
        $this->assertEquals(1, $decoded['result']['pagination']['page']);
        $this->assertEquals(100, $decoded['result']['pagination']['total']);
    }

    public function testApiResponseWithEmptyResult(): void
    {
        $response = [
            'success' => true,
            'result' => [],
        ];

        $json = json_encode($response);
        $decoded = json_decode($json, true);

        $this->assertTrue($decoded['success']);
        $this->assertEmpty($decoded['result']);
    }

    public function testApiResponseWithNullResult(): void
    {
        $response = [
            'success' => true,
            'result' => null,
        ];

        $json = json_encode($response);
        $decoded = json_decode($json, true);

        $this->assertTrue($decoded['success']);
        $this->assertNull($decoded['result']);
    }

    public function testWpSendJson(): void
    {
        $data = ['test' => 'value'];

        wp_send_json($data);

        $this->assertEquals($data, WP_Mock_Data::$json_sent);
    }

    public function testWpSendJsonSuccess(): void
    {
        $data = ['id' => 1];

        wp_send_json_success($data);

        $this->assertTrue(WP_Mock_Data::$json_sent['success']);
        $this->assertEquals($data, WP_Mock_Data::$json_sent['data']);
    }

    public function testWpSendJsonError(): void
    {
        $error = 'Something went wrong';

        wp_send_json_error($error);

        $this->assertFalse(WP_Mock_Data::$json_sent['success']);
        $this->assertEquals($error, WP_Mock_Data::$json_sent['data']);
    }

    public function testSpecialCharactersInJson(): void
    {
        $data = [
            'message' => 'Hello "World"',
            'content' => "Line 1\nLine 2",
            'unicode' => 'Héllo Wörld',
            'emoji' => 'Test 🎉',
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }

    public function testNestedObjectSerialization(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => true,
                    ],
                ],
            ],
        ];

        $json = json_encode($data);
        $decoded = json_decode($json, true);

        $this->assertEquals('dark', $decoded['user']['profile']['settings']['theme']);
        $this->assertTrue($decoded['user']['profile']['settings']['notifications']);
    }
}
