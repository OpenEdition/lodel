<?php
/**	
 * Logique des entrées et des personnes
 *
 * PHP version 5
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
 * @author Sophie Malafosse
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


/**
 * Classe de logique des entrées
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
class EntriesLogic extends GenericLogic
{

	/**
	 * Constructeur
	 */
	public function __construct($logicname = 'entries')
	{
		parent::__construct($logicname);
		$this->daoname = 'entrytypes';
		$this->idtype = 'identry';
	}

	/**
	 * Affichage d'un objet (index ET persons)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction (&$context, &$error) 
	{
		if(!function_exists('loop_entities_select'))
		{
			function loop_entities_select($context, $funcname)
			{
				global $db;
				$varname = @$context['varname'];
				if (!$varname) {
					if (function_exists("code_alter_$funcname")) {
						call_user_func("code_alter_$funcname",$context);
					}
					return;
				}
				$values = null;
				if(isset($context['entities'][$varname]))
				{
					$values = $context['entities'][$varname];
				}
				elseif(is_numeric($nb = substr($varname, -1)))
				{
					$varname = substr($varname, 0, -1);
					if(isset($context['entities'][$varname][$nb])) $values = $context['entities'][$varname][$nb];
				}
				$ids = array();
				if($values)
				{
					if(!is_array($values))
					{
						$ids = preg_split("/,/", $values, -1, PREG_SPLIT_NO_EMPTY);
					}
					else $ids = $values;
	
					if($ids)
					{
						$result = $db->execute(lq("
						SELECT #_TP_entities.*, #_TP_types.type, #_TP_types.tpledition 
							FROM #_TP_entities JOIN #_TP_types ON (#_TP_entities.idtype=#_TP_types.id) 
							WHERE #_TP_entities.status>-64 AND #_TP_entities.id ". sql_in_array($ids))) 
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						while (!$result->EOF) {
							$localcontext = array_merge($context, $result->fields);
							call_user_func("code_do_$funcname", $localcontext);
							$result->MoveNext();
						}
					}
				}
				if (function_exists("code_after_$funcname")) {
					$localcontext = $context;
					$localcontext['all'] = join(',', $ids);
					call_user_func("code_after_$funcname",$localcontext);
				}
			}
		}
		if (empty($context['id'])) $context['status']=32; //why ? dont't know !
		$context['classtype']=$this->maintable;
		return parent::viewAction ($context, $error); //call the parent method
	}


	/**
	 * Publication d'une entrée (index ET persons)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function publishAction(&$context, &$error)
	{
		global $db;
		$dao = $this->_getMainTableDAO();
		$id = (int)@$context['id'];
		$vo  = $dao->find('id=' . $id, 'status,id');
		if (!$vo) {
			trigger_error("ERROR: interface error in EntriesLogic::publishAction ", E_USER_ERROR);
		}
		
		if ($vo->status <= 0) {
			$vo->status = abs($vo->status);
		} else {
			$vo->status = -abs($vo->status);
		}
		
		$dao->save($vo);
		update();
		return '_back';
	}


	/**
	*  Indique si un objet est protégé en suppression (index ET persons)
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
	public function isdeletelocked ($id, $status = 0)
	{
		global $db;

		// if this entry has child
		// OR is published AND permanent (status=32)
		$count = $db->getOne(lq("SELECT count(*) FROM #_TP_" . $this->maintable . " WHERE idparent ".sql_in_array($id)." AND status >-64"));
		$count += $db->getOne(lq("SELECT count(*) FROM #_TP_" . $this->maintable . " WHERE id ".sql_in_array($id)." AND status=32"));
		if ($db->errorno())  trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasentrieschild","admin"),$count);
		}
	}

	/**
	 * Appel la liste des objet de cette logic : ici les entrées (index ET persons)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function listAction (&$context, &$error)
	{
		$daotype = DAO::getDAO ($this->daoname);
		$context['idtype'] = @$context['idtype'];
		$votype = $daotype->getById($context['idtype']);
		if (!$votype) {
			trigger_error("ERROR: idtype must me known in GenericLogic::viewAction", E_USER_ERROR);
		}
		$this->_populateContext ($votype, $context['type']);
		return '_ok';
	}

	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant (index seulement)
	 *
	 * Ajout d'une nouvelle entrée
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function editAction (&$context, &$error, $clean=false) 
	{
		$id = isset($context['id']) ? $context['id'] : '';
		$idtype=@$context['idtype'];
		if (!$idtype) {
			trigger_error("ERROR: internal error in EntriesLogic::editAction", E_USER_ERROR);
		}
		$status = isset($context['status']) ? $context['status'] : null;
		// get the class 
		$daotype = DAO::getDAO ("entrytypes");
		$votype = $daotype->getById ($idtype, "class,newbyimportallowed,flat");
		$class = $context['class']=$votype->class;
		if (!$clean) {
			if (!$this->validateFields($context,$error)) {
				// error.
				// if the entity is imported and will be checked
				// that's fine, let's continue, if not return an error
				if ($status>-64) {
					return "_error";
				}
			}
		}
		$g_index_key = $this->getGenericEquivalent($class,'index key');
		if (!$g_index_key) {
			trigger_error("ERROR: The generic field 'index key' is required. Please edit your editorial model.", E_USER_ERROR);
		}
		
		$vo = null;

		// get the dao for working with the object
		$dao = $this->_getMainTableDAO ();
		if (isset ($context['g_name'])) {
			if (!$context['g_name']) return '_error'; // empty entry!
			// search if the entries exists
			$tmpgname = $context['g_name'];
			myaddslashes($tmpgname);
			$vo = $dao->find ("BINARY g_name='". $tmpgname. "' AND idtype='". $idtype."' AND status>-64","id,status");
			//$vo = $dao->find ("g_name='". $context['g_name']. "' AND idtype='". $idtype."' AND status>-64","id,status");
			if ($vo && $vo->id) {
				$context['id']=$vo->id;
				return; // nothing to do.
			} else {
				$context['data'][$g_index_key]=$context['g_name'];
			}
		}
		$context['data'][$g_index_key] = @$context['data'][$g_index_key];
		$index_key = &$context['data'][$g_index_key];
		$index_key = str_replace(',',' ',$index_key); // remove the , because it is a separator
		if (isset($context['lo']) && $context['lo'] == 'entries') {  // check it does not exist
			$tmpindex_key = $index_key;
			myaddslashes($tmpindex_key);
			$vo=$dao->find("BINARY g_name='". $tmpindex_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
			//$vo=$dao->find("g_name='". $index_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
			if ($vo && $vo->id) {
				$error[$g_index_key] = "1";
				return '_error';
			}
		}

		if (!$vo) {
			if ($id) { // create or edit the entity
				$new=false;
				$dao->instantiateObject ($vo);
				$vo->id=$id;
			} else {
				if (!$votype->newbyimportallowed && (!isset($context['lo']) || $context['lo']!="entries")) { return "_error"; }
				$new=true;
				$vo=$dao->createObject();
				$vo->status=$status ? $status : -1;
			}
		}
		if (isset($dao->rights['protect'])) $vo->protect=!empty($context['protected']) ? 1 : 0;
		if ($votype->flat) {
			$vo->idparent=0; // force the entry to be at root
		} else {
			$vo->idparent= isset($context['idparent']) ? $context['idparent'] : 0;
			$vo->idparent = (int)$vo->idparent;
		}
		// populate the entry table
		if ($idtype) $vo->idtype=$idtype;
		$vo->g_name=$index_key;
		$vo->sortkey=makeSortKey($vo->g_name);
		$id=$context['id']=$dao->save($vo);
		// save the class table
		$gdao=DAO::getGenericDAO($class,"identry");
		$gdao->instantiateObject($gvo);
		$context['data']['id']=$context['id'];
		$this->_populateObject($gvo,$context['data']);
		$gvo->identry=$id;

		$this->_moveFiles($id,$this->files_to_move,$gvo);
		$gdao->save($gvo,$new);  // save the related table

		update();
		return "_back";
	}


	/**
	 * Changement du rang d'un objet (index seulement)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		return parent::changeRankAction($context, $error, 'idparent', '');
	}

	/**
	 * Construction des balises select HTML pour cet objet (index seulement)
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 * @param string $edittype le type d'édition
	 */
	public function makeSelect (&$context, $var) 
	{
		global $db;
		switch($var) {
			case 'idparent':
			$arr=array ();
			$rank=array ();
			$parent=array ();
			$ids=array (0);
			$l=1;
			$context['type']['sort'] = @$context['type']['sort'];
			$context['id'] = @$context['id'];
			$context['idtype'] = @$context['idtype'];
			do {
				$result=$db->execute (lq ("
				SELECT * 
					FROM #_TP_entries 
					WHERE idtype='".$context['idtype']."' AND id!='".$context['id']."' AND idparent ".sql_in_array ($ids). " 
					AND ABS(status) = 32 ORDER BY ". $context['type']['sort'])) 
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				$ids=array();
				$i=0;
				while (!$result->EOF) {
					$id=$result->fields['id'];
					$ids[]=$id;	 
					$fullname=$result->fields['g_name'];
					$idparent=$result->fields['idparent'];
					if ($idparent) $fullname=$parent[$idparent]." / ".$fullname;
					do {
						$i++;
						$d=$rank[$id]=(isset($rank[$idparent]) ? $rank[$idparent]+($i*1.0)/$l : ($i*1.0)/$l);
					} while(isset($arr["p$d"]));
					$arr["p$d"]=array($id,$fullname);
					$parent[$id]=$fullname;
					$i++;
					$result->MoveNext();
				} //end while
				$l*=100;
			} while ($ids); // end do while
			ksort ($arr);
			$arr2=array ("0"=>"--"); // reorganize the array $arr
			foreach ($arr as $row) {
				$arr2[$row[0]]=$row[1];
			}
			renderOptions ($arr2, isset($context[$var]) ? $context[$var] : '');
			break;
		}
	} //end of function

	/**
	 * Appelé avant l'action delete (index ET persons)
	 *
	 * Cette méthode est appelée avant l'action delete pour effectuer des vérifications
	 * préliminaires à une suppression.
	 *
	 * @param object $dao la DAO utilisée
	 * @param array &$context le contexte passé par référénce
	 * @access private
	 */
	protected function _prepareDelete($dao, &$context) {
		global $db;
		// get the classes
		$this->classes = array ();

		// $this->daoname = persontypes OU entrytypes
		// $this->maintable = persons OU entries
		$result = $db->execute (lq ("SELECT DISTINCT class FROM #_TP_". $this->daoname . " INNER JOIN #_TP_" . $this->maintable . " ON idtype=#_TP_" . $this->daoname . ".id WHERE #_TP_" .$this->maintable. ".id ".sql_in_array ($context['id']))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		while (!$result->EOF) {
			$this->classes[] = $result->fields['class'];
			$result->MoveNext();
		}

		if (isset($context['idrelation'])) {
			$this->idrelation=$context['idrelation'];
		} else {
			$vos=DAO::getDAO ('relations')->findMany ("id2 ".sql_in_array ($context['id']));
			$this->idrelation=array ();
			foreach ($vos as $vo) {
				$this->idrelation[]=$vo->idrelation;
			}
		}
	}


	/**
	 * Used in deleteAction to do extra operation after the object has been deleted (index ET persons)
	 */
	protected function _deleteRelatedTables($id) 
	{
		global $db;
		foreach ($this->classes as $class) {
			$gdao=DAO::getGenericDAO ($class, $this->idtype);
			$gdao->deleteObject ($id);
		
			if($this->maintable == 'persons') {
				if ($this->idrelation) {
					$gdao=DAO::getGenericDAO("entities_".$class,"idrelation");
					$gdao->deleteObject($this->idrelation);
				}
			}
		}

		if ($this->idrelation) {
			$dao=DAO::getDAO ('relations');
			$dao->delete ('idrelation '. sql_in_array ($this->idrelation));
		}
	}
} // class 
?>
