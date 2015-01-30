<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des groupes d'options
 *
 */
class OptiongroupsLogic extends Logic {

	/**
	 * Constructor
	 */
	public function __construct() 
	{
		parent::__construct("optiongroups");
	}

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	public function makeSelect(&$context, $var)
	{
		global $db;

		switch($var) {
			case 'idparent':
				$arr=array();
				$rank=array();
				$parent=array();
				$ids=array(0);
				$l=1;
				do {
					$result=$db->execute(lq("SELECT * FROM #_TP_optiongroups WHERE idparent ".sql_in_array($ids)." ORDER BY rank")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					$ids=array();
					$i=1;
					while(!$result->EOF) {
						$id=$result->fields['id'];
						if (!isset($context['id']) || $id!=$context['id']) {
							$ids[]=$id;	 
							$fullname=$result->fields['title'];
							$idparent=$result->fields['idparent'];
							if ($idparent) $fullname=$parent[$idparent]." / ".$fullname;	   
							$d=$rank[$id]=@$rank[$idparent]+($i*1.0)/$l;
							//echo $d," ";
							$arr["p$d"]=array($id,$fullname);
							$parent[$id]=$fullname;
							$i++;
						}
						$result->MoveNext();
					}
					$l*=100;
				} while ($ids);
				ksort($arr);
				$arr2=array('0' => '--'); // reorganize the array $arr
				foreach($arr as $row) {
					$arr2[$row[0]] = $row[1];
				}
				renderOptions($arr2, isset($context[$var]) ? $context[$var] : '');
			break;
		}
	}
	
	
	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		return parent::changeRankAction($context, $error, 'idparent', '');
	}

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
	public function isdeletelocked($id, $status = 0)
	{
		global $db;
		$id = (int)$id;
		$count = $db->getOne(lq("SELECT count(*) FROM #_TP_options WHERE idgroup='$id' AND status>-64"));
		$countgroups = $db->getOne(lq("SELECT count(*) FROM #_TP_optiongroups WHERE idparent='$id' AND status>-64"));
		$count = $count + $countgroups;
		if ($db->errorno())  trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasoptions","admin"),$count);
		}
	}
	
	
	/**
	* Préparation de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilisée
	* @param array &$context le context passé par référence
	*/
	protected function _prepareEdit($dao,&$context)
	{
		if (!empty($context['id'])) //it is an edition
		{
			$id = (int) $context['id'];
			$this->oldvo=$dao->getById($id);
			if (!$this->oldvo)
				trigger_error("ERROR: internal error in OptionGroups::_prepareEdit", E_USER_ERROR);

			if(!isset($context['idparent']) || $context['idparent'] != $this->oldvo->idparent) //can't change the parent of an optiongroup !
				trigger_error("ERROR : Changing the parent of a group is forbidden", E_USER_ERROR);
			
		}
		else //it is an add
		{
			//if it is an add - the optiongroup inherit the exportpolicy
			if(!empty($context['idparent']))
			{
				$idparent = (int) $context['idparent'];
				$voparent = $dao->getById($idparent,"id,exportpolicy");
				$context['exportpolicy'] = $voparent->exportpolicy;
			}
		}
	}
	
	
	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Ajout d'un groupe d'option
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function editAction(&$context, &$error, $clean = false)
	{
		$ret = parent::editAction($context, $error);
		if (!$error) $this->clearCache();
		return $ret;
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
		global $db;
		//if the exportpolicy has been changed update the optiongroups children	
		$dao=$this->_getMainTableDAO();
		$newpolicy = $vo->exportpolicy;
		$oldpolicy = $context['exportpolicy'] == 'on' ? 1 : 0;
		if($newpolicy != $oldpolicy)	
		{
			$vo->id = (int)$vo->id;
			$ids = array($vo->id);
			do
			{
				$vos = $dao->findMany("exportpolicy <> '$newpolicy' AND idparent ".sql_in_array($ids),"rank DESC","id,idparent,exportpolicy");
				$sqlquery = lq("UPDATE #_TP_optiongroups SET exportpolicy='$newpolicy' WHERE exportpolicy <> '$newpolicy' AND idparent ".sql_in_array($ids));
				$ret = $db->execute($sqlquery);
				$ids = array();
				if($ret!==false) //
					foreach($vos as $vo)
						$ids[] = $vo->id;
			}
			while($ids);
		}
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
		cache_delete("options");
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('idparent' => array('select', '+'),
									'name' => array('text', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'logic' => array('text', ''),
									'exportpolicy' => array('boolean', '+'),
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
		return array(array('name'), );
	}
	// end{uniquefields} automatic generation  //
} // class 