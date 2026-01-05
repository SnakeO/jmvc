<?php
/**
 * JModelBase Unit Tests
 *
 * @package JMVC\Tests\Unit\Model
 */

namespace JMVC\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use WP_Mock_Data;

/**
 * Test model class for JModelBase tests
 */
class TestModel extends \JModelBase
{
    public static ?string $post_type = 'test_post';
}

class JModelBaseTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock_Data::reset();
    }

    protected function tearDown(): void
    {
        WP_Mock_Data::reset();
    }

    public function testConstructorWithId(): void
    {
        // Add a test post
        WP_Mock_Data::addPost(123, [
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_type' => 'test_post',
        ]);

        $model = new TestModel(123);

        $this->assertEquals(123, $model->ID);
    }

    public function testConstructorWithoutId(): void
    {
        $model = new TestModel();

        $this->assertNull($model->ID);
    }

    public function testPostTypeIsSet(): void
    {
        $this->assertEquals('test_post', TestModel::$post_type);
    }

    public function testFind(): void
    {
        // Add test posts
        WP_Mock_Data::addPost(1, ['post_type' => 'test_post', 'post_title' => 'Post 1']);
        WP_Mock_Data::addPost(2, ['post_type' => 'test_post', 'post_title' => 'Post 2']);
        WP_Mock_Data::addPost(3, ['post_type' => 'other_type', 'post_title' => 'Post 3']);

        $posts = get_posts(['post_type' => 'test_post']);

        $this->assertCount(2, $posts);
    }

    public function testFindWithFilters(): void
    {
        WP_Mock_Data::addPost(1, ['post_type' => 'test_post', 'post_title' => 'First']);
        WP_Mock_Data::addPost(2, ['post_type' => 'test_post', 'post_title' => 'Second']);
        WP_Mock_Data::addPost(3, ['post_type' => 'test_post', 'post_title' => 'Third']);

        $posts = get_posts(['post_type' => 'test_post', 'posts_per_page' => 2]);

        $this->assertCount(2, $posts);
    }

    public function testGetPost(): void
    {
        WP_Mock_Data::addPost(100, [
            'post_title' => 'Test Title',
            'post_content' => 'Test Content',
            'post_type' => 'test_post',
        ]);

        $post = get_post(100);

        $this->assertNotNull($post);
        $this->assertEquals('Test Title', $post->post_title);
        $this->assertEquals('Test Content', $post->post_content);
    }

    public function testGetPostAttr(): void
    {
        WP_Mock_Data::addPost(100, [
            'post_title' => 'My Title',
            'post_content' => 'My Content',
            'post_status' => 'publish',
            'post_type' => 'test_post',
        ]);

        $post = get_post(100);

        $this->assertEquals('My Title', $post->post_title);
        $this->assertEquals('My Content', $post->post_content);
        $this->assertEquals('publish', $post->post_status);
    }

    public function testSaveNew(): void
    {
        $data = [
            'post_title' => 'New Post',
            'post_content' => 'New Content',
            'post_type' => 'test_post',
            'post_status' => 'publish',
        ];

        $id = wp_insert_post($data);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $post = get_post($id);
        $this->assertEquals('New Post', $post->post_title);
    }

    public function testSaveExisting(): void
    {
        // Create initial post
        $id = wp_insert_post([
            'post_title' => 'Original Title',
            'post_type' => 'test_post',
        ]);

        // Update the post
        wp_update_post([
            'ID' => $id,
            'post_title' => 'Updated Title',
        ]);

        $post = get_post($id);
        $this->assertEquals('Updated Title', $post->post_title);
    }

    public function testDeletePost(): void
    {
        $id = wp_insert_post([
            'post_title' => 'To Be Deleted',
            'post_type' => 'test_post',
        ]);

        $this->assertNotNull(get_post($id));

        wp_delete_post($id, true);

        $this->assertNull(get_post($id));
    }

    public function testPostMeta(): void
    {
        $id = wp_insert_post([
            'post_title' => 'Post with Meta',
            'post_type' => 'test_post',
        ]);

        update_post_meta($id, 'custom_field', 'custom_value');

        $value = get_post_meta($id, 'custom_field', true);
        $this->assertEquals('custom_value', $value);
    }

    public function testPostMetaArray(): void
    {
        $id = wp_insert_post([
            'post_title' => 'Post with Array Meta',
            'post_type' => 'test_post',
        ]);

        $arrayValue = ['item1', 'item2', 'item3'];
        update_post_meta($id, 'array_field', $arrayValue);

        $value = get_post_meta($id, 'array_field', true);
        $this->assertEquals($arrayValue, $value);
    }

    public function testDeletePostMeta(): void
    {
        $id = wp_insert_post([
            'post_title' => 'Post with Meta to Delete',
            'post_type' => 'test_post',
        ]);

        update_post_meta($id, 'temp_field', 'temp_value');
        $this->assertEquals('temp_value', get_post_meta($id, 'temp_field', true));

        delete_post_meta($id, 'temp_field');
        $this->assertEquals('', get_post_meta($id, 'temp_field', true));
    }

    public function testNonExistentPost(): void
    {
        $post = get_post(99999);

        $this->assertNull($post);
    }

    public function testPostTypeFiltering(): void
    {
        WP_Mock_Data::addPost(1, ['post_type' => 'post', 'post_title' => 'Blog Post']);
        WP_Mock_Data::addPost(2, ['post_type' => 'page', 'post_title' => 'Page']);
        WP_Mock_Data::addPost(3, ['post_type' => 'test_post', 'post_title' => 'Test']);

        $posts = get_posts(['post_type' => 'test_post']);
        $this->assertCount(1, $posts);
        $this->assertEquals('Test', $posts[0]->post_title);

        $pages = get_posts(['post_type' => 'page']);
        $this->assertCount(1, $pages);
        $this->assertEquals('Page', $pages[0]->post_title);
    }

    public function testIsWpError(): void
    {
        $error = new \WP_Error('test_error', 'Test error message');

        $this->assertTrue(is_wp_error($error));
        $this->assertEquals('Test error message', $error->get_error_message());
        $this->assertEquals('test_error', $error->get_error_code());
    }

    public function testNotWpError(): void
    {
        $post = get_post(1);
        $this->assertFalse(is_wp_error($post));

        $array = ['data' => 'value'];
        $this->assertFalse(is_wp_error($array));

        $string = 'just a string';
        $this->assertFalse(is_wp_error($string));
    }
}
