<?php
/**
 * Kohana Cache Tagging Interface
 * 
 * @package    Kohana/Cache
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
interface Cache_Tagging {

	/**
	 * Set a value based on an id. Optionally add tags.
	 * 
	 * Note : Some caching engines do not support
	 * tagging
	 *
	 * @param   string   id 
	 * @param   mixed    data 
	 * @param   integer  lifetime [Optional]
	 * @param   array    tags [Optional]
	 * @return  boolean
	 */
	public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL);

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param   string   tag 
	 * @param   integer  timeout [Optional]
	 */
	public function delete_tag($tag);

	/**
	 * Find cache entries based on a tag
	 *
	 * @param   string   tag 
	 * @return  array
	 */
	public function find($tag);
}
