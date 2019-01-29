<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

//
// File generate automatically the 2005-01-11.
//

/**
 * VO of table entitytypes_entitytypes
 */

class entitytypes_entitytypesVO 
{
	public $identitytype;
	public $identitytype2;
	public $cond;
}


/**
 * DAO of table entitytypes_entitytypes
 */

class entitytypes_entitytypesDAO extends DAO 
{
	function __construct() 
	{
		parent::__construct("entitytypes_entitytypes", false);
		$this->rights = array();
	}
}
