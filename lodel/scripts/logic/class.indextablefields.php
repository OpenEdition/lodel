<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des champs d'indexs
 */
class IndexTableFieldsLogic extends TableFieldsLogic {

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct();
	}

	/**
		* edit/add an object Action
		*/
	public function editAction(&$context,&$error, $clean=false)
	{
		$context['condition']="*";
		return parent::editAction($context,$error);
	}

	/**
		*
		*/
	public function makeSelect(&$context,$var)
	{
		global $db;
		switch($var) {
		case "name" :
			$type = (isset($context['type']) && $context['type']=='entries') ? "entrytypes" : "persontypes";
			$dao=DAO::getDAO($type);
			$vos=$dao->findMany("status>0","rank,title","type,title");
			$arr = array();
			foreach($vos as $vo) {
				$arr[$vo->type]=$vo->title;
			}

			$ext = $db->getArray(lq('SELECT site, id2 FROM #_TP_relations_ext WHERE nature="ET" ORDER BY site'));
			if($ext)
			{
				foreach($ext as $entrytype)
				{
					if($db->database != DATABASE.'_'.$entrytype['site'])
					{
						$db->SelectDB(DATABASE.'_'.$entrytype['site']);
					}
					$type = $db->GetRow(lq('SELECT title FROM #_TP_entrytypes WHERE id='.$entrytype['id2']));
					if($type)
					{
						$arr[$entrytype['site'].'.'.$entrytype['id2']] = $entrytype['site'].' - '.$type['title'];
					}
				}
				usecurrentdb();
			}

			renderOptions($arr,isset($context['name']) ? $context['name'] : '');
			break;
		case "edition" :
			$arr=array(
			"editable"=>getlodeltextcontents("edit_in_the_interface","admin"),
			"importable"=>getlodeltextcontents("no_edit_but_import","admin"),
			"none"=>getlodeltextcontents("no_change","admin"),
			"display"=>getlodeltextcontents("display_no_edit","admin"),
			);
			renderOptions($arr,isset($context['edition']) ? $context['edition'] : '');
			break;
		default:
			parent::makeSelect($context,$var);
			break;
		}
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

	/**
	* Préparation de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilisée
	* @param array &$context le context passé par référence
	*/
	protected function _prepareEdit($dao,&$context)
	{
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
	}

	/**
	* Appelé avant l'action delete
	*
	* Cette méthode est appelée avant l'action delete pour effectuer des vérifications
	* préliminaires à une suppression.
	*
	* @param object $dao la DAO utilisée
	* @param array &$context le contexte passé par référénce
	*/
	protected function _prepareDelete($dao,&$context)
	{     
	}

	protected function _deleteRelatedTables($id)
	{
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('name' => array('select', '+'),
									'class' => array('class', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'edition' => array('select', ''),
									'idgroup' => array('select', '+'),
									'type' => array('class', '+'),
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
		return array(array('name', 'class'), );
	}
	// end{uniquefields} automatic generation  //


} // class 