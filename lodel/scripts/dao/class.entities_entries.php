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
* VO of table entities_entries
*/
class entities_entriesVO 
{
	public $identry;
	public $identity;
}

/**
* DAO of table entities_entries
*/

class entities_entriesDAO extends DAO 
{
	public function __construct()
	{
		parent::__construct("entities_entries", false);
		$this->rights = array();
	}
}