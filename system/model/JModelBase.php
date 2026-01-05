<?php

declare(strict_types=1);

/**
 * JMVC Base Model Class
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JModelBase
{
    /**
     * Post ID
     */
    public ?int $id = null;

    /**
     * Post type - override in subclasses
     */
    public static ?string $post_type = null;

    /**
     * Model data used for saving/creating
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Constructor
     *
     * @param int|null $id The post ID
     * @throws Exception If no post_type is specified
     */
    public function __construct(?int $id = null)
    {
        if (empty(static::$post_type)) {
            throw new Exception('No post_type specified for JModel: ' . get_called_class());
        }

        if ($id) {
            $this->id = $id;
        }
    }

    /**
     * Find models matching filters
     *
     * Uses get_posts() with post_type pre-filled.
     *
     * @param array $filters Query filters
     * @return array Array of model instances
     */
    public static function find(array $filters = []): array
    {
        $filters = array_merge([
            'post_type'      => static::$post_type,
            'posts_per_page' => -1,
        ], $filters);

        $classname = get_called_class();

        return array_map(function ($post) use ($classname) {
            return new $classname($post->ID);
        }, get_posts($filters));
    }

    /**
     * Get the underlying WordPress post
     *
     * @param string $as Output type (OBJECT, ARRAY_A, or ARRAY_N)
     * @return WP_Post|array|null The post object or array
     */
    public function post(string $as = OBJECT): WP_Post|array|null
    {
        return get_post($this->id, $as);
    }

    /**
     * Generate post title - override in subclasses
     *
     * @return string The post title
     * @throws Exception Must be overridden
     */
    public function makePostTitle(): string
    {
        throw new Exception('JModelBase: override makePostTitle()');
    }

    /**
     * Get an attribute from the WordPress post
     *
     * @param string $key The attribute key
     * @return mixed The attribute value or null
     */
    public function getPostAttr(string $key): mixed
    {
        $post = $this->post();

        if (!$post) {
            return null;
        }

        return $post->$key ?? null;
    }

    /**
     * Save the model (add or update)
     *
     * @return int|false The post ID or false on failure
     */
    public function save(): int|false
    {
        if (!$this->id) {
            $this->add();
            return $this->id ?: false;
        }

        $this->update();
        return $this->id;
    }

    /**
     * Add a new post - override in subclass or use trait
     *
     * @return int|false The new post ID or false
     */
    public function add(): int|false
    {
        // Implementation in ACFModelTrait
        return false;
    }

    /**
     * Update the post - override in subclass or use trait
     *
     * @return bool Success status
     */
    public function update(): bool
    {
        // Implementation in ACFModelTrait
        return false;
    }

    /**
     * Set model data
     *
     * @param string $k Key
     * @param mixed $v Value
     */
    public function __set(string $k, mixed $v): void
    {
        $this->data[$k] = $v;
    }

    /**
     * Get model data or post attribute
     *
     * @param string $field Field name
     * @return mixed The field value or null
     */
    public function __get(string $field): mixed
    {
        // Check data array first
        if (array_key_exists($field, $this->data)) {
            return $this->data[$field];
        }

        // Check post attribute
        $val = $this->getPostAttr($field);
        if ($val !== null) {
            return $val;
        }

        // Field not found - return null instead of alerting for every access
        return null;
    }

    /**
     * Check if a field is set
     *
     * @param string $field Field name
     * @return bool True if field exists
     */
    public function __isset(string $field): bool
    {
        return array_key_exists($field, $this->data) || $this->getPostAttr($field) !== null;
    }
}
