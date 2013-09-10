<?php
/**	
 * Logique des groupes d'options
 *
 * PHP versions 4 et 5
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */



/**
 * Classe de logique des groupes d'options
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class OptiongroupsLogic extends Logic {

	/**
	 * Constructor
	 */
	function OptiongroupsLogic() 
	{
		$this->Logic("optiongroups");
	}

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	function makeSelect(&$context, $var)
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
				$result=$db->execute(lq("SELECT * FROM #_TP_optiongroups WHERE idparent ".sql_in_array($ids)." ORDER BY rank")) or dberror();
				$ids=array();
				$i=1;
				while(!$result->EOF) {
					$id=$result->fields['id'];
					if ($id!=$context['id']) {
						$ids[]=$id;	 
						$fullname=$result->fields['title'];
						$idparent=$result->fields['idparent'];
						if ($idparent) $fullname=$parent[$idparent]." / ".$fullname;	   
						$d=$rank[$id]=$rank[$idparent]+($i*1.0)/$l;
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
			renderOptions($arr2, $context[$var]);
			break;
		}
	}
	
	
	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function changeRankAction(&$context, &$error)
	{
		return Logic::changeRankAction($context, $error, 'idparent', '');
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
	function isdeletelocked($id, $status = 0)
	{
		global $db;
		$count = $db->getOne(lq("SELECT count(*) FROM #_TP_options WHERE idgroup='$id' AND status>-64"));
		$countgroups = $db->getOne(lq("SELECT count(*) FROM #_TP_optiongroups WHERE idparent='$id' AND status>-64"));
		$count = $count + $countgroups;
		if ($db->errorno())  dberror();
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
	function _prepareEdit($dao,&$context)
	{
		// gather information for the following
		if ($context['id']) //it is an edition
		{
			$this->oldvo=$dao->getById($context['id']);
			if (!$this->oldvo)
				die("ERROR: internal error in OptionGroups::_prepareEdit");
			if($context['idparent'] != $this->oldvo->idparent) //can't change the parent of an optiongroup !
				die("ERROR : Changing the parent of a group is forbidden");
			
		}
		else //it is an add
		{
			//if it is an add - the optiongroup inherit the exportpolicy
			if($context['idparent'])
			{
					$voparent = $dao->getById($context['idparent'],"id,exportpolicy");
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
	function editAction(&$context, &$error, $clean = false)
	{
		$ret = Logic::editAction($context, $error);
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
	function _saveRelatedTables($vo,&$context) 
	{
		global $db;
		//if the exportpolicy has been changed update the optiongroups children	
		$dao=$this->_getMainTableDAO();
		$newpolicy = $vo->exportpolicy;
		$oldpolicy = $context['exportpolicy'] == 'on' ? 1 : 0;
		if($newpolicy != $oldpolicy)	
		{
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
	function deleteAction(&$context,&$error)

	{
		$ret=Logic::deleteAction($context,$error);
		if (!$error) $this->clearCache();
		return $ret;

	}
	/**
	 * Effacement du cache
	 */
	function clearCache()
	{
		@unlink(SITEROOT. "CACHE/options_cache.php");
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
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
	function _uniqueFields() 
	{ 
		return array(array('name'), );
	}
	// end{uniquefields} automatic generation  //
		

} // class 

?>
