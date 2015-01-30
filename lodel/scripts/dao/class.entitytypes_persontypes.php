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
	* VO of table entitytypes_persontypes
	*/

class entitytypes_persontypesVO 
{
	public $identitytype;
	public $idpersontype;
	public $cond;
}

/**
	* DAO of table entitytypes_persontypes
	*/

class entitytypes_persontypesDAO extends DAO 
{
	public function __construct()
	{
		parent::__construct("entitytypes_persontypes", false);
		$this->rights=array();
	}
}