<?php
/**
 * Advanced Custom Fields Mock Functions for Testing
 *
 * Provides mock implementations of ACF functions
 * to enable unit testing without ACF installed.
 *
 * @package JMVC\Tests\Mocks
 */

namespace {
    /**
     * Mock ACF data storage
     */
    class ACF_Mock_Data
    {
        public static $fields = [];
        public static $field_groups = [];
        public static $field_definitions = [];

        public static function reset()
        {
            self::$fields = [];
            self::$field_groups = [];
            self::$field_definitions = [];
        }

        public static function setField($post_id, $field, $value)
        {
            if (!isset(self::$fields[$post_id])) {
                self::$fields[$post_id] = [];
            }
            self::$fields[$post_id][$field] = $value;
        }

        public static function addFieldGroup($id, $data)
        {
            self::$field_groups[$id] = $data;
        }

        public static function addFieldDefinition($name, $definition)
        {
            self::$field_definitions[$name] = $definition;
        }
    }

    // ACF field functions
    if (!function_exists('get_field')) {
        function get_field($selector, $post_id = false, $format_value = true)
        {
            if ($post_id === false) {
                $post_id = 0;
            }

            if (isset(ACF_Mock_Data::$fields[$post_id][$selector])) {
                return ACF_Mock_Data::$fields[$post_id][$selector];
            }

            return null;
        }
    }

    if (!function_exists('update_field')) {
        function update_field($selector, $value, $post_id = false)
        {
            if ($post_id === false) {
                $post_id = 0;
            }

            ACF_Mock_Data::setField($post_id, $selector, $value);
            return true;
        }
    }

    if (!function_exists('get_fields')) {
        function get_fields($post_id = false, $format_value = true)
        {
            if ($post_id === false) {
                $post_id = 0;
            }

            return ACF_Mock_Data::$fields[$post_id] ?? [];
        }
    }

    if (!function_exists('have_rows')) {
        function have_rows($selector, $post_id = false)
        {
            static $current_row = 0;
            static $current_selector = null;

            if ($selector !== $current_selector) {
                $current_selector = $selector;
                $current_row = 0;
            }

            $field = get_field($selector, $post_id);
            if (!is_array($field)) {
                return false;
            }

            if ($current_row < count($field)) {
                return true;
            }

            $current_selector = null;
            $current_row = 0;
            return false;
        }
    }

    if (!function_exists('the_row')) {
        function the_row()
        {
            return true;
        }
    }

    if (!function_exists('get_sub_field')) {
        function get_sub_field($selector, $format_value = true)
        {
            return null;
        }
    }

    // ACF field group functions
    if (!function_exists('acf_get_field_groups')) {
        function acf_get_field_groups($filter = [])
        {
            $groups = ACF_Mock_Data::$field_groups;

            if (isset($filter['post_type'])) {
                $groups = array_filter($groups, function ($group) use ($filter) {
                    if (!isset($group['location'])) {
                        return false;
                    }
                    foreach ($group['location'] as $location_group) {
                        foreach ($location_group as $rule) {
                            if ($rule['param'] === 'post_type' && $rule['value'] === $filter['post_type']) {
                                return true;
                            }
                        }
                    }
                    return false;
                });
            }

            return array_values($groups);
        }
    }

    if (!function_exists('acf_get_fields')) {
        function acf_get_fields($parent)
        {
            if (is_numeric($parent) && isset(ACF_Mock_Data::$field_groups[$parent]['fields'])) {
                return ACF_Mock_Data::$field_groups[$parent]['fields'];
            }
            return [];
        }
    }

    if (!function_exists('acf_get_fields_by_id')) {
        function acf_get_fields_by_id($id)
        {
            return acf_get_fields($id);
        }
    }

    if (!function_exists('get_field_object')) {
        function get_field_object($selector, $post_id = false, $format_value = true, $load_value = true)
        {
            if (isset(ACF_Mock_Data::$field_definitions[$selector])) {
                return ACF_Mock_Data::$field_definitions[$selector];
            }

            return [
                'key' => 'field_' . md5($selector),
                'name' => $selector,
                'type' => 'text',
                'label' => ucfirst(str_replace('_', ' ', $selector)),
            ];
        }
    }

    if (!function_exists('acf_get_field')) {
        function acf_get_field($selector, $post_id = false)
        {
            return get_field_object($selector, $post_id);
        }
    }

    // ACF utility functions
    if (!function_exists('acf_decode_post_id')) {
        function acf_decode_post_id($post_id)
        {
            return [
                'type' => 'post',
                'id' => $post_id,
            ];
        }
    }

    if (!function_exists('acf_format_value')) {
        function acf_format_value($value, $post_id, $field)
        {
            return $value;
        }
    }

    if (!function_exists('acf')) {
        function acf()
        {
            return new class {
                public $version = '6.0.0';
            };
        }
    }
}
