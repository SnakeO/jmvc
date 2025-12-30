<?php
/**
 * JMVC ACF Model Trait
 *
 * Provides Advanced Custom Fields integration for models.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait for models that use Advanced Custom Fields
 */
trait ACFModelTrait
{
    /**
     * Cached field key map
     *
     * @var array|null
     */
    public $acf_field_key_map;

    /**
     * Get the ACF field key map for this model
     *
     * Returns an array whose keys are the human-readable ACF field names,
     * and the values are the field keys.
     *
     * @return array Field name to key mapping
     */
    public function getFieldKeyMap()
    {
        $post_id = $this->id;

        // Return cached if available
        if ($this->acf_field_key_map !== null) {
            return $this->acf_field_key_map;
        }

        // Get field groups based on context
        $filter = array(
            'post_type' => static::$post_type,
        );

        if (strpos($post_id, 'user_') !== false) {
            $user_id = str_replace('user_', '', $post_id);
            $filter = array(
                'ef_user' => absint($user_id),
            );
        } elseif (strpos($post_id, 'taxonomy_') !== false) {
            $taxonomy_id = str_replace('taxonomy_', '', $post_id);
            $filter = array(
                'ef_taxonomy' => absint($taxonomy_id),
            );
        }

        $field_groups = acf_get_field_groups($filter);
        $map = array();

        foreach ($field_groups as $field_group) {
            $fields = acf_get_fields_by_id($field_group['ID']);
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    $map[$field['name']] = $field['key'];
                }
            }
        }

        $this->acf_field_key_map = $map;
        return $map;
    }

    /**
     * Add a new post with ACF fields
     *
     * @return int|false The new post ID or false on failure
     */
    public function add()
    {
        // Capability check
        if (!current_user_can('edit_posts')) {
            DevAlert::slack('ACFModelTrait::add() permission denied', array(
                'user_id' => get_current_user_id(),
            ));
            return false;
        }

        $post_data = array(
            'post_type'   => static::$post_type,
            'post_title'  => $this->makePostTitle(),
            'post_content' => '',
            'post_status' => 'publish',
        );

        $post_id = wp_insert_post($post_data, true);

        // Fixed: was checking $post instead of $post_id
        if (is_wp_error($post_id)) {
            DevAlert::slack('ACFModelTrait::add() error inserting post', array(
                'post_data' => $post_data,
                'error'     => $post_id->get_error_message(),
            ));
            return false;
        }

        $this->id = $post_id;
        $this->update();

        return $this->id;
    }

    /**
     * Update ACF fields for this model
     *
     * @return bool True on success
     */
    public function update()
    {
        // Capability check
        if (!current_user_can('edit_post', $this->id)) {
            DevAlert::slack('ACFModelTrait::update() permission denied', array(
                'user_id' => get_current_user_id(),
                'post_id' => $this->id,
            ));
            return false;
        }

        $field_map = $this->getFieldKeyMap();

        foreach ($this->data as $field => $value) {
            if ($this->hasAcfField($field)) {
                // Convert string booleans
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }

                update_field($field_map[$field], $value, $this->id);
            }
        }

        return true;
    }

    /**
     * Check if a field is an ACF field
     *
     * @param string $field Field name
     * @return bool True if ACF field exists
     */
    public function hasAcfField($field)
    {
        return array_key_exists($field, $this->getFieldKeyMap());
    }

    /**
     * Magic getter for ACF fields
     *
     * @param string $key Field name
     * @return mixed Field value
     */
    public function __get($key)
    {
        // Check if already set in data array
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        // Check if it's an ACF field
        if ($this->hasAcfField($key)) {
            return get_field($key, $this->id);
        }

        return parent::__get($key);
    }
}
