<?php
/**	
 * Logique des tâches
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


/**
 * Classe de logique des tâches
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
class TasksLogic extends Logic {

	/**
	* generic equivalent assoc array
	*/
	var $g_name;


	/** Constructor
	*/
	function TasksLogic() {
		$this->Logic("tasks");
	}





	/**
		* view an object Action
		*/
	function viewAction(&$context,&$error) 
	{ die("TasksLogic::viewAction"); }


	/**
		* Change rank action
		*/
	function changeRankAction(&$context,&$error)
	{ die("TasksLogic::changeRankAction"); }

	/**
		* add/edit Action
		*/

	function editAction(&$context,&$error)
	{ die("TasksLogic::editAction"); }

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/


	/**
	*  Indique si un objet est protégé en suppression
	*
	* Cette méthode indique si un objet, identifié par son identifiant numérique et
	* éventuellement son status, ne peut pas être supprimé. Dans le cas où un objet ne serait
	* pas supprimable un message est retourné indiquant la cause. Sinon la méthode renvoit le
	* booleen false.
	*
	* @param integer $id identifiant de l'objet
	* @param integer $status status de l'objet
	* @return false si l'objet n'est pas protégé en suppression, un message sinon
	*/
	function isdeletelocked($id,$status=0)

	{
		global $lodeluser;

		// basic check. Should be more advanced because of the potential conflict between 
		// adminlodel adn othe rusers
		$dao=$this->_getMainTableDAO();
		$vo=$dao->find("id='".$id."' AND user='".$lodeluser['id']."'","id");
		return $vo->id ? false : true ;
	}


	// begin{publicfields} automatic generation  //
	/**
	* Retourne la liste des champs publics
	* @access private
	*/
	function _publicfields() 
	{
		return array();
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //


} // class 



/*-----------------------------------*/
/* loops                             */



?>
