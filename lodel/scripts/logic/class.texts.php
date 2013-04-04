<?php
/**	
 * Logique des textes lodel
 *
 * PHP versions 5
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

function_exists('mkeditlodeltext') || include("translationfunc.php");

/**
 * Classe de logique des textes lodel
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class TextsLogic extends Logic 
{

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct("texts");
	}

	/**
		* add/edit Action
		*/
	public function editAction(&$context, &$error, $clean = false)
	{
// 		$id = @$context['id'];
// 		if ($id) {
// 			// normal edit
// 			$ret = parent::editAction($context,$error);
//             		clearcache();
//             		return $ret;
// 		}
		$dao = $this->_getMainTableDAO();
		// Sauvegarde massive
		if (isset($context['contents']) && is_array($context['contents'])) {
			//if ($GLOBALS['lodeluser']['translationmode'] != 'site') {
			if(!isset($context['textgroup']) || $context['textgroup'] != 'site') {
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
				$status = (int) (isset($context['status'][$id]) ? $context['status'][$id] : 0);
				$this->_isAuthorizedStatus($status);
				$vo->status   = $status;
				if (!$vo->status) {
					$vo->status=-1;
				}
				$dao->save($vo);
			}
			//if ($GLOBALS['lodeluser']['translationmode']!="site") {
			if(!isset($context['textgroup']) || $context['textgroup'] != 'site') {
				#echo "mode interface";
				usecurrentdb();
			}
		}
		else
		{
			if (!$this->validateFields($context, $error)) {
				return '_error';
			}
			if(!isset($context['textgroup']) || $context['textgroup'] != 'site') {
				#echo "mode interface";
				usemaindb();
			}
			$vo = $dao->createObject();
			$vo->contents = preg_replace("/(\r\n\s*){2,}/", "<br />", $context['contents']);
			$status = (int) (isset($context['status']) ? $context['status'] : 0);
			$this->_isAuthorizedStatus($status);

			if(empty($context['lang']) || empty($context['name']))
				trigger_error('ERROR: missing lang or name in TextsLogic::editAction', E_USER_ERROR);

			$vo->status   = $status;
			$vo->lang = $context['lang'];
			$vo->name = strtolower($context['name']);
			$vo->textgroup = isset($context['textgroup']) ? $context['textgroup'] : '';
			$vo->id = isset($context['id']) ? $context['id'] : null;
			if (!$vo->status) {
				$vo->status=-1;
			}
			$context['id'] = $dao->save($vo);
			if(!isset($context['textgroup']) || $context['textgroup'] != 'site') {
				#echo "mode interface";
				usecurrentdb();
			}
		}
		clearcache(true);
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ? '_ajax' : '_back';
	}


	/**
		* Function to create the text entry for all the languages
		*/
	public function createTexts($name, $textgroup = '')
	{
		global $db;
		$name = addslashes($name);
		$textgroup = addslashes($textgroup);
		if ($textgroup == '' && is_numeric($name)) {
			$criteria = "#_TP_texts.id='". $name. "'";
		} else {
			$criteria = "name='". $name. "' AND textgroup='". $textgroup. "'";
		}
		if ($textgroup != 'site') {
			usemaindb();
		}

		$result = $db->execute(lq("SELECT #_TP_translations.lang FROM #_TP_translations LEFT JOIN #_TP_texts ON #_TP_translations.lang=#_TP_texts.lang AND ".$criteria." WHERE #_TP_texts.lang is NULL")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
	protected function _saveRelatedTables($vo, &$context)
	{
		if ($vo->id) {
			$this->createTexts($vo->id);
		} else {
			$this->createTexts($vo->name,$vo->textgroup);
		}
	}

	protected function _deleteRelatedTables($id) 
	{
		// reinitialise le cache surement.
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('contents' => array('longtext', ''),
									'lang' => array('lang', '+'),
									'textgroup' => array('text', '+'));
	}
	// end{publicfields} automatic generation  //


	protected function _uniqueFields() {  return array();  }

} // class