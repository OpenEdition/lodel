<?php
/**	
 * Logique des textes lodel
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



require_once("translationfunc.php");

/**
 * Classe de logique des textes lodel
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
class TextsLogic extends Logic 
{

	/** Constructor
	*/
	function TextsLogic() 
	{
		$this->Logic("texts");
	}

	/**
		* add/edit Action
		*/
	function editAction(&$context, &$error, $clean = false)
	{
		if ($context['id']) {
			// normal edit
			return Logic::editAction($context,$error);
		}
		// Sauvegarde massive
		if (is_array($context['contents'])) {
			$dao = $this->_getMainTableDAO();
			//if ($GLOBALS['lodeluser']['translationmode'] != 'site') {
			if($context['textgroup'] != 'site') {
				#echo "mode interface";
				usemaindb();
			}
			foreach ($context['contents'] as $id=>$contents) {
				if (!is_numeric($id)) {
					continue;
				}
				$dao->instantiateObject($vo);
				$vo->contents = preg_replace("/(\r\n\s*){2,}/", "<br />", $contents);
				$vo->id       = $id;
				$status = intval($context['status'][$id]);
				$this->_isAuthorizedStatus($status);
				$vo->status   = $status;
				if (!$vo->status) {
					$vo->status=-1;
				}
				$dao->save($vo);
			}
			//if ($GLOBALS['lodeluser']['translationmode']!="site") {
			if($context['textgroup'] != 'site') {
				#echo "mode interface";
				usecurrentdb();
			}
			update();
		}
		require_once 'cachefunc.php';
		clearcache();
		return '_back';
	}


	/**
		* Function to create the text entry for all the languages
		*/
	function createTexts($name, $textgroup = '')
	{
		global $db;

		if ($textgroup == '' && is_numeric($name)) {
			$criteria = "#_TP_texts.id='". $name. "'";
		} else {
			$criteria = "name='". $name. "' AND textgroup='". $textgroup. "'";
		}
		if ($textgroup != 'site') {
			usemaindb();
		}

		$result = $db->execute(lq("SELECT #_TP_translations.lang FROM #_TP_translations LEFT JOIN #_TP_texts ON #_TP_translations.lang=#_TP_texts.lang AND ".$criteria." WHERE #_TP_texts.lang is NULL")) or dberror();
		$dao = $this->_getMainTableDAO();

		while (!$result->EOF) {
			$dao->instantiateObject($vo);
			$vo->name      = $name;
			$vo->textgroup = $textgroup;
			$vo->status    = 1;
			$vo->lang      = $result->fields['lang'];
			$dao->save($vo);
			$result->MoveNext();
		}
		if ($textgroup != 'site') {
			usecurrentdb();
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
	function _saveRelatedTables($vo, $context)
	{
		if ($vo->id) {
			$this->createTexts($vo->id);
		} else {
			$this->createTexts($vo->name,$vo->textgroup);
		}
	}

	function _deleteRelatedTables($id) 
	{
		// reinitialise le cache surement.
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('contents' => array('longtext', ''),
									'lang' => array('lang', '+'),
									'textgroup' => array('text', '+'));
	}
	// end{publicfields} automatic generation  //


		function _uniqueFields() {  return array();  }


} // class
?>