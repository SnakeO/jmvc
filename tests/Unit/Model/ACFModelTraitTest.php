<?php
/**
 * ACFModelTrait Unit Tests
 *
 * @package JMVC\Tests\Unit\Model
 */

namespace JMVC\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use WP_Mock_Data;
use ACF_Mock_Data;

/**
 * Test model class that uses ACFModelTrait
 */
class ACFTestModel extends \JModelBase
{
    use \ACFModelTrait;

    public static ?string $post_type = 'acf_test';
}

class ACFModelTraitTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock_Data::reset();
        ACF_Mock_Data::reset();
    }

    protected function tearDown(): void
    {
        WP_Mock_Data::reset();
        ACF_Mock_Data::reset();
    }

    public function testGetField(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'test_field', 'test_value');

        $value = get_field('test_field', $postId);

        $this->assertEquals('test_value', $value);
    }

    public function testUpdateField(): void
    {
        $postId = 100;

        update_field('new_field', 'new_value', $postId);

        $value = get_field('new_field', $postId);
        $this->assertEquals('new_value', $value);
    }

    public function testGetFieldReturnsNullForNonExistent(): void
    {
        $value = get_field('nonexistent_field', 100);

        $this->assertNull($value);
    }

    public function testGetFields(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'field1', 'value1');
        ACF_Mock_Data::setField($postId, 'field2', 'value2');

        $fields = get_fields($postId);

        $this->assertIsArray($fields);
        $this->assertEquals('value1', $fields['field1']);
        $this->assertEquals('value2', $fields['field2']);
    }

    public function testBooleanFieldConversion(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'is_active', true);
        ACF_Mock_Data::setField($postId, 'is_disabled', false);
        ACF_Mock_Data::setField($postId, 'string_true', '1');
        ACF_Mock_Data::setField($postId, 'string_false', '0');

        $this->assertTrue(get_field('is_active', $postId));
        $this->assertFalse(get_field('is_disabled', $postId));
        $this->assertEquals('1', get_field('string_true', $postId));
        $this->assertEquals('0', get_field('string_false', $postId));
    }

    public function testArrayFieldValue(): void
    {
        $postId = 100;
        $arrayValue = ['option1', 'option2', 'option3'];
        ACF_Mock_Data::setField($postId, 'multi_select', $arrayValue);

        $value = get_field('multi_select', $postId);

        $this->assertEquals($arrayValue, $value);
        $this->assertCount(3, $value);
    }

    public function testNumericFieldValue(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'price', 99.99);
        ACF_Mock_Data::setField($postId, 'quantity', 42);

        $this->assertEquals(99.99, get_field('price', $postId));
        $this->assertEquals(42, get_field('quantity', $postId));
    }

    public function testFieldGroups(): void
    {
        ACF_Mock_Data::addFieldGroup(1, [
            'ID' => 1,
            'title' => 'Test Group',
            'location' => [
                [
                    ['param' => 'post_type', 'operator' => '==', 'value' => 'acf_test'],
                ],
            ],
            'fields' => [
                ['key' => 'field_abc', 'name' => 'test_field', 'type' => 'text'],
            ],
        ]);

        $groups = acf_get_field_groups(['post_type' => 'acf_test']);

        $this->assertCount(1, $groups);
        $this->assertEquals('Test Group', $groups[0]['title']);
    }

    public function testFieldGroupFiltering(): void
    {
        ACF_Mock_Data::addFieldGroup(1, [
            'ID' => 1,
            'title' => 'Post Group',
            'location' => [[['param' => 'post_type', 'value' => 'post']]],
        ]);

        ACF_Mock_Data::addFieldGroup(2, [
            'ID' => 2,
            'title' => 'Page Group',
            'location' => [[['param' => 'post_type', 'value' => 'page']]],
        ]);

        $postGroups = acf_get_field_groups(['post_type' => 'post']);
        $this->assertCount(1, $postGroups);
        $this->assertEquals('Post Group', $postGroups[0]['title']);

        $pageGroups = acf_get_field_groups(['post_type' => 'page']);
        $this->assertCount(1, $pageGroups);
        $this->assertEquals('Page Group', $pageGroups[0]['title']);
    }

    public function testGetFieldsByGroupId(): void
    {
        ACF_Mock_Data::addFieldGroup(1, [
            'ID' => 1,
            'title' => 'Product Fields',
            'fields' => [
                ['key' => 'field_price', 'name' => 'price', 'type' => 'number'],
                ['key' => 'field_sku', 'name' => 'sku', 'type' => 'text'],
            ],
        ]);

        $fields = acf_get_fields_by_id(1);

        $this->assertCount(2, $fields);
        $this->assertEquals('price', $fields[0]['name']);
        $this->assertEquals('sku', $fields[1]['name']);
    }

    public function testAddWithPermission(): void
    {
        WP_Mock_Data::setLoggedIn(1, ['edit_posts']);

        $this->assertTrue(current_user_can('edit_posts'));
    }

    public function testAddWithoutPermission(): void
    {
        WP_Mock_Data::setLoggedOut();

        $this->assertFalse(current_user_can('edit_posts'));
    }

    public function testUpdateWithPermission(): void
    {
        WP_Mock_Data::setLoggedIn(1, ['edit_posts']);
        WP_Mock_Data::addPost(100, ['post_type' => 'acf_test']);

        $this->assertTrue(current_user_can('edit_posts'));

        update_field('updated_field', 'updated_value', 100);
        $this->assertEquals('updated_value', get_field('updated_field', 100));
    }

    public function testFieldObject(): void
    {
        $fieldObject = get_field_object('test_selector', 100);

        $this->assertIsArray($fieldObject);
        $this->assertArrayHasKey('key', $fieldObject);
        $this->assertArrayHasKey('name', $fieldObject);
        $this->assertArrayHasKey('type', $fieldObject);
    }

    public function testDateFieldValue(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'event_date', '2024-12-25');

        $value = get_field('event_date', $postId);

        $this->assertEquals('2024-12-25', $value);
    }

    public function testRelationshipField(): void
    {
        $postId = 100;
        $relatedPosts = [10, 20, 30];
        ACF_Mock_Data::setField($postId, 'related_posts', $relatedPosts);

        $value = get_field('related_posts', $postId);

        $this->assertEquals($relatedPosts, $value);
        $this->assertContains(20, $value);
    }

    public function testImageField(): void
    {
        $postId = 100;
        $imageData = [
            'ID' => 500,
            'url' => 'https://example.com/image.jpg',
            'alt' => 'Test Image',
        ];
        ACF_Mock_Data::setField($postId, 'featured_image', $imageData);

        $value = get_field('featured_image', $postId);

        $this->assertEquals($imageData, $value);
        $this->assertEquals('https://example.com/image.jpg', $value['url']);
    }

    public function testEmptyFieldValue(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'empty_string', '');
        ACF_Mock_Data::setField($postId, 'empty_array', []);

        $this->assertEquals('', get_field('empty_string', $postId));
        $this->assertEquals([], get_field('empty_array', $postId));
    }

    public function testFieldOverwrite(): void
    {
        $postId = 100;
        ACF_Mock_Data::setField($postId, 'changeable', 'original');
        $this->assertEquals('original', get_field('changeable', $postId));

        update_field('changeable', 'modified', $postId);
        $this->assertEquals('modified', get_field('changeable', $postId));
    }
}
