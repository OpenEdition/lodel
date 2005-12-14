<?php
/**	
 * Logique des options
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */



function humanfieldtype($text)

{
  return $GLOBALS['fieldtypes'][$text];
}

/***/



/**
 * Classe de logique des options
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class OptionsLogic extends Logic {

	/** Constructor
	*/
	function OptionsLogic() {
		$this->Logic("options");
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function changeRankAction(&$context, &$error)

	{
		return Logic::changeRankAction(&$context, &$error, 'idgroup');
	}


	/**
		* add/edit Action
		*/

	function editAction(&$context,&$error,$clean=false)
	{ 
		if (!$context['title']) $context['title']=$context['name'];
		$ret=Logic::editAction($context,$error);
		if (!$error) $this->clearCache();
		return $ret;
	}

	function deleteAction(&$context,&$error)

	{
		$ret=Logic::deleteAction($context,$error);
		if (!$error) $this->clearCache();
		return $ret;

	}

	function clearCache()
	{
		@unlink(SITEROOT."CACHE/options_cache.php");
	}


	/**
		*
		*/

	function makeSelect(&$context,$var)

	{

		switch($var) {
		case "userrights":
			require_once("commonselect.php");
			makeSelectUserRights($context['userrights']);
			break;
		case "type" :
			require_once("commonselect.php");
			makeSelectFieldTypes($context['type']);
			break;
		case "edition" :
			require_once("commonselect.php");
			makeSelectEdition($context['edition']);
			break;
		}
	}
		

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

	/**
	* Sauve des données dans des tables liées éventuellement
	*
	* Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a été créé
	* @param array $context le contexte
	*/
	function _saveRelatedTables($vo,$context) 

	{
		// reinitialise le cache surement.
	}



	function _deleteRelatedTables($id) {
		// reinitialise le cache surement.
	}



	// begin{publicfields} automatic generation  //
	/**
	* Retourne la liste des champs publics
	* @access private
	*/
	function _publicfields() 
	{
		return array('name' => array('text', '+'),
									'title' => array('text', '+'),
									'idgroup' => array('int', '+'),
									'type' => array('select', ''),
									'edition' => array('select', ''),
									'editionparams' => array('text', ''),
									'userrights' => array('select', '+'),
									'defaultvalue' => array('text', ''),
									'comment' => array('longtext', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	* Retourne la liste des champs uniques
	* @access private
	*/
	function _uniqueFields() 
	{ 
		return array(array('name', 'idgroup'), );
	}
	// end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */





?>
