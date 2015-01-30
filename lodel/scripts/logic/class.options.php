<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des options
 *
 */
class OptionsLogic extends Logic {

	/** Constructor
	*/
	public function __construct() {
		parent::__construct("options");
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		return parent::changeRankAction($context, $error, 'idgroup');
	}


	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Ajout d'une option
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function editAction(&$context, &$error, $clean = false)
	{ 
		if (empty($context['title'])) {
			$context['title'] = !empty($context['name']) ? $context['name'] : '';
		}
		$ret = parent::editAction($context,$error);
		if (!$error) {
			$this->clearCache();
		}
		return $ret;
	}

	/**
	 * Suppression d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function deleteAction(&$context,&$error)
	{
		$ret=parent::deleteAction($context,$error);
		if (!$error) $this->clearCache();
		return $ret;

	}
	/**
	 * Effacement du cache
	 */
	public function clearCache()
	{
		clearcache();
	}


	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	public function makeSelect(&$context,$var)
	{
		switch($var) {
		case "userrights":
			function_exists('makeSelectUserRights') || include("commonselect.php");
			$lodeladmin = ((!C::get('site', 'cfg') || SINGLESITE) && defined('backoffice-lodeladmin')) ? TRUE : FALSE;
			makeSelectUserRights(isset($context['userrights']) ? $context['userrights'] : '', $lodeladmin);
			break;
		case "type" :
			function_exists('makeSelectFieldTypes') || include("commonselect.php");
			makeSelectFieldTypes(isset($context['type']) ? $context['type'] : '');
			break;
		case "edition" :
			function_exists('makeSelectEdition') || include("commonselect.php");
			makeSelectEdition(isset($context['edition']) ? $context['edition'] : '');
			break;
		}
	}

	/**
	* Sauve des données dans des tables liées éventuellement
	*
	* Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a été créé
	* @param array $context le contexte
	*/
	protected function _saveRelatedTables($vo,&$context)
	{
		// reinitialise le cache surement.
	}


	/**
	 * Suppression dans les tables liées
	 *
	 * @param integer $id identifiant numérique de l'objet supprimé
	 */
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
		return array('name' => array('text', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
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
	protected function _uniqueFields() 
	{ 
		return array(array('name', 'idgroup'), );
	}
	// end{uniquefields} automatic generation  //
} // class 


/*-----------------------------------*/
/* loops                             */
/*-----------------------------------*/
if(!function_exists('humanfieldtype'))
{
	function humanfieldtype($text)
	{
		return (isset($GLOBALS['fieldtypes'][$text]) ? $GLOBALS['fieldtypes'][$text] : '');
	}
}