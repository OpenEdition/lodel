<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des textes lodel
 */

function_exists('mkeditlodeltext') || include("translationfunc.php");

/**
 * Classe de logique des textes lodel
 *
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
		clearcache();
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
