<?php

class JModelBase
{
	public $id;
	public static $post_type;	// override this in subclasses

	protected $data = array();	// pod data used for saving/creating pods. values for Related fields are just integers pointing to the post_id

	public function __construct($id=null)
	{
		if( !static::$post_type ) {
			throw new Exception("No post_type specified for JModel: " . get_called_class());
		}

		if( $id ) {
			$this->id = $id;
		}
		else {
			// DevAlert::slack('No id passed in for model');
		}
	}

	// find Models with the same filters as get_posts (but we pre-fill the post_type)
	public static function find($filters=array())
	{
		$filters = array(
			'post_type' 		=> static::$post_type,
			'posts_per_page'	=> -1
		) + $filters;

		$classname = get_called_class();
		return array_map(function($post) use ($classname)
		{
			return new $classname($post->ID);
		}, get_posts($filters));
	}

	// ourselves as a wordpress post
	public function post($as=OBJECT)
	{
		return get_post($this->id, $as);
	}

	// returns the post title
	public function makePostTitle()
	{
		throw new Exception("JModelBase:: override makePostTitle()");
	}

	// get an attribute from our corresponding wordpress post
	public function getPostAttr($key)
	{
		$post = $this->post();
		return @$post->$key;
	}

	public function save()
	{
		if( !$this->id ) 
		{
			$this->add();
			return $this->id;
		}
		
		$this->update();
		return $this->id;
	}

	public function add()
	{
		// you'll find most of this implementaiton inside ACFModelTrait
	}

	public function update()
	{
		// you'll find most of this implementaiton inside ACFModelTrait
	}

	// set pod data for saving/creating
	public function __set($k, $v)
	{
		$this->data[$k] = $v;
	}

	public function __get($field)
	{
		// already set earlier in the lifetime of this script?
		if( in_array($field, array_keys($this->data)) ) {
			return $this->data[$field];
		}

		// is it in our post field?
		if( $val = $this->getPostAttr($field) ) {
			return $val;
		}

		DevAlert::slack("JModelBase: __get() not found: $field");
	}
}