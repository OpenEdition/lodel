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
  * VO of table entities_persons
  */

class entities_personsVO {
	public $idperson;
	public $identity;
	public $idtype;
	public $rank;
	public $prefix;
	public $description;
	public $function;
	public $affiliation;
	public $email;
}

/**
	* DAO of table entities_persons
	*/

class entities_personsDAO extends DAO 
{
	function __construct() 
	{
		parent::__construct("entities_persons", false);
		$this->rights = array();
	}

}
