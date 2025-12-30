<?php
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
     *
     * @var int|null
     */
    public $id;

    /**
     * Post type - override in subclasses
     *
     * @var string
     */
    public static $post_type;

    /**
     * Model data used for saving/creating
     *
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     *
     * @param int|null $id The post ID
     * @throws Exception If no post_type is specified
     */
    public function __construct($id = null)
    {
        if (empty(static::$post_type)) {
            throw new Exception('No post_type specified for JModel: ' . get_called_class());
        }

        if ($id) {
            $this->id = absint($id);
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
    public static function find($filters = array())
    {
        $filters = array_merge(array(
            'post_type'      => static::$post_type,
            'posts_per_page' => -1,
        ), $filters);

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
    public function post($as = OBJECT)
    {
        return get_post($this->id, $as);
    }

    /**
     * Generate post title - override in subclasses
     *
     * @return string The post title
     * @throws Exception Must be overridden
     */
    public function makePostTitle()
    {
        throw new Exception('JModelBase: override makePostTitle()');
    }

    /**
     * Get an attribute from the WordPress post
     *
     * @param string $key The attribute key
     * @return mixed The attribute value or null
     */
    public function getPostAttr($key)
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
    public function save()
    {
        if (!$this->id) {
            $this->add();
            return $this->id;
        }

        $this->update();
        return $this->id;
    }

    /**
     * Add a new post - override in subclass or use trait
     *
     * @return int|false The new post ID or false
     */
    public function add()
    {
        // Implementation in ACFModelTrait
        return false;
    }

    /**
     * Update the post - override in subclass or use trait
     *
     * @return bool Success status
     */
    public function update()
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
    public function __set($k, $v)
    {
        $this->data[$k] = $v;
    }

    /**
     * Get model data or post attribute
     *
     * @param string $field Field name
     * @return mixed The field value or null
     */
    public function __get($field)
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
    public function __isset($field)
    {
        return array_key_exists($field, $this->data) || $this->getPostAttr($field) !== null;
    }
}
