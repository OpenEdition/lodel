<?php
/**
 * Kohana Cache Exception
 * 
 * @package    Kohana/Cache
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Cache_Exception extends Exception {

	public function getContent(){
		return $this->message;
	}

}
