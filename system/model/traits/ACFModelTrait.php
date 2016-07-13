<?php

// functionality for models that use Advanced Custom Fields
trait ACFModelTrait 
{
	public $acf_field_key_map;

	// return an array who's keys are the human-readable acf field names, 
	// and the values are the gibberish keys that they map to.
	public function getFieldKeyMap()
	{
		$post_id = $this->id;

		// cached?
		if( $this->acf_field_key_map != null ) {
			return $this->acf_field_key_map;
		}

		// get field groups
		$filter = array(
			'post_type' => static::$post_type
		);
		
		if( strpos($post_id, 'user_') !== false )
		{
			$user_id = str_replace('user_', '', $post_id);
			$filter = array(
				'ef_user' => $user_id
			);
		}
		elseif( strpos($post_id, 'taxonomy_') !== false )
		{
			$taxonomy_id = str_replace('taxonomy_', '', $post_id);
			$filter = array(
				'ef_taxonomy' => $taxonomy_id
			);
		}
		
		$field_groups = acf_get_field_groups( $filter );
		$map = array();

		foreach($field_groups as $field_group) 
		{
			$fields = acf_get_fields_by_id($field_group['ID']);
			foreach($fields as $field) {
				$map[$field['name']] = $field['key'];
			}
		}

		$this->acf_field_key_map = $map;
		return $map;
	}

	public function add()
	{
		$post_data = array(
			'post_type'		=> static::$post_type,
			'post_title'	=> $this->makePostTitle(),
			'post_content'	=> ''
		);

		$post_id = wp_insert_post($post_data, true);

		if( is_wp_error($post) ) {
			DevAlert::slack("ACFModelTrait::add() error inserting post", array(
				'post_data'	=> $post_data
			));
		}

		$this->id = $post_id;
		$this->update();

		return $this->id;
	}

	public function update()
	{
		$field_map = $this->getFieldKeyMap();
		
		foreach($this->data as $field => $value) 
		{
			if( $this->hasAcfField($field) ) 
			{
				if( $value === 'true' ) {
					$value = true;
				}
				else if( $value === 'false' ) {
					$value = false;
				}
				
				update_field($field_map[$field], $value, $this->id);
			}
		}
	}

	public function hasAcfField($field)
	{
		return in_array($field, array_keys($this->getFieldKeyMap()));
	}

	public function __get($key)
	{
		// already set earlier in the lifetime of this script?
		if( in_array($key, array_keys($this->data)) ) {
			return $this->data[$key];
		}

		// is it an ACF field?
		if( $this->hasAcfField($key) )  {
			return get_field($key, $this->id);
		}

		return parent::__get($key);
	}

	/*
	public function __set($key, $value)
	{
		$fkm = $this->getFieldKeyMap();

		// is it an ACF field?
		if( in_array($key, array_keys($fkm)) )  {
			return update_field($fkm[$key], $value, $this->id);	// use field_key here to be sure.
		}
	}
	*/
}