<?php
/**
 * JBag Unit Tests
 *
 * @package JMVC\Tests\Unit\Service
 */

namespace JMVC\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use JBag;

class JBagTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear the static storage before each test
        $reflection = new \ReflectionClass(JBag::class);
        $property = $reflection->getProperty('things');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testSetStoresValue(): void
    {
        JBag::set('test_key', 'test_value');

        $this->assertEquals('test_value', JBag::get('test_key'));
    }

    public function testGetReturnsStoredValue(): void
    {
        JBag::set('my_key', 'my_value');

        $this->assertEquals('my_value', JBag::get('my_key'));
    }

    public function testGetReturnsNullForNonExistentKey(): void
    {
        $this->assertNull(JBag::get('nonexistent_key'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        JBag::set('exists', 'value');

        $this->assertTrue(JBag::has('exists'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $this->assertFalse(JBag::has('does_not_exist'));
    }

    public function testHasReturnsTrueForNullValue(): void
    {
        JBag::set('null_value', null);

        $this->assertTrue(JBag::has('null_value'));
    }

    public function testRemoveDeletesKey(): void
    {
        JBag::set('to_remove', 'value');
        $this->assertTrue(JBag::has('to_remove'));

        JBag::remove('to_remove');

        $this->assertFalse(JBag::has('to_remove'));
        $this->assertNull(JBag::get('to_remove'));
    }

    public function testOverwriteReplacesValue(): void
    {
        JBag::set('overwrite', 'original');
        $this->assertEquals('original', JBag::get('overwrite'));

        JBag::set('overwrite', 'new_value');

        $this->assertEquals('new_value', JBag::get('overwrite'));
    }

    public function testCanStoreArrays(): void
    {
        $array = ['foo' => 'bar', 'baz' => 123];
        JBag::set('array_key', $array);

        $this->assertEquals($array, JBag::get('array_key'));
    }

    public function testCanStoreObjects(): void
    {
        $object = new \stdClass();
        $object->property = 'value';
        JBag::set('object_key', $object);

        $retrieved = JBag::get('object_key');
        $this->assertSame($object, $retrieved);
        $this->assertEquals('value', $retrieved->property);
    }

    public function testCanStoreCallables(): void
    {
        $callable = function () {
            return 'result';
        };
        JBag::set('callable_key', $callable);

        $retrieved = JBag::get('callable_key');
        $this->assertEquals('result', $retrieved());
    }

    public function testModulePropertyExists(): void
    {
        $this->assertNull(JBag::$module);

        JBag::$module = 'test_module';
        $this->assertEquals('test_module', JBag::$module);

        JBag::$module = null;
    }

    public function testMultipleKeysIndependent(): void
    {
        JBag::set('key1', 'value1');
        JBag::set('key2', 'value2');
        JBag::set('key3', 'value3');

        $this->assertEquals('value1', JBag::get('key1'));
        $this->assertEquals('value2', JBag::get('key2'));
        $this->assertEquals('value3', JBag::get('key3'));

        JBag::remove('key2');

        $this->assertEquals('value1', JBag::get('key1'));
        $this->assertNull(JBag::get('key2'));
        $this->assertEquals('value3', JBag::get('key3'));
    }
}
