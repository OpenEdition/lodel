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
 * VO of table entitytypes_entrytypes
 */
class entitytypes_entrytypesVO 
{
	public $identitytype;
	public $identrytype;
	public $cond;
}

/**
 * DAO of table entitytypes_entrytypes
 */
class entitytypes_entrytypesDAO extends DAO 
{
	public function __construct()
	{
		parent::__construct("entitytypes_entrytypes", false);
		$this->rights = array();
	}
}