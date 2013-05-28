<?php
/**	
 * Logique des entrées et des personnes
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
 * @author Sophie Malafosse
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


require_once 'genericlogic.php';

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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class EntriesLogic extends GenericLogic
{

	/**
	 * Constructeur
	 */
	function EntriesLogic ($logicname = 'entries')
	{
		$this->GenericLogic ($logicname);
		$this->daoname = 'entrytypes';
		$this->idtype = 'identry';
	}

	/**
	 * Affichage d'un objet (index ET persons)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function viewAction (&$context, &$error) 
	{
		if (!$context['id']) $context['status']=32; //why ?
		$context['classtype']=$this->maintable;
		return GenericLogic::viewAction ($context, $error); //call the parent method
	}


	/**
	 * Publication d'une entrée (index ET persons)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function publishAction(&$context, &$error)
	{
		global $db;
		$dao = $this->_getMainTableDAO();
		$vo  = $dao->find('id=' . $context['id'], 'status,id');
		if (!$vo) {
			die("ERROR: interface error in EntriesLogic::publishAction ");
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
	function isdeletelocked ($id, $status = 0)
	{
		/*if ($this->maintable == 'persons') {
			die("ERROR in EntriesLogic:: function isdeletelocked is not valid for persons logic");
		}*/
		global $db;

		// if this entry has child
		// OR is published AND permanent (status=32)
		$count = $db->getOne(lq("SELECT count(*) FROM #_TP_" . $this->maintable . " WHERE idparent ".sql_in_array($id)." AND status >-64"));
		$count += $db->getOne(lq("SELECT count(*) FROM #_TP_" . $this->maintable . " WHERE id='".$id."' AND status=32"));
		if ($db->errorno())  dberror();
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
	function listAction (&$context, &$error, $clean = false)
	{
		$daotype = &getDAO ($this->daoname);
		$votype = $daotype->getById($context['idtype']);
		if (!$votype) {
			die ("ERROR: idtype must me known in GenericLogic::viewAction");
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
	function editAction (&$context, &$error, $clean=false) 
	{
	if ($this->maintable == 'persons') {
			die("ERROR in EntriesLogic:: function isdeletelocked is not valid for persons logic");
		}
		global $home;
		$id = $context['id'];
		$idtype=$context['idtype'];
		if (!$idtype) {
			die ("ERROR: internal error in EntriesLogic::editAction");
		}
		$status = $context['status'];
		// get the class 
		$daotype = &getDAO ("entrytypes");
		$votype = $daotype->getById ($idtype, "class,newbyimportallowed,flat");
		$class = $context['class']=$votype->class;
		if ($clean!=CLEAN) {
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
			die ("ERROR: The generic field 'index key' is required. Please edit your editorial model.");
		}
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO ();
		if (isset ($context['g_name'])) {
			if (!$context['g_name']) return '_error'; // empty entry!
			// search if the entries exists
			$tmpgname = $context['g_name'];
			myaddslashes($tmpgname);
			$vo = $dao->find ("BINARY g_name='". $tmpgname. "' AND idtype='". $idtype."' AND status>-64","id,status");
			//$vo = $dao->find ("g_name='". $context['g_name']. "' AND idtype='". $idtype."' AND status>-64","id,status");
			if ($vo->id) {
				$context['id']=$vo->id;
				return; // nothing to do.
			} else {
				$context['data'][$g_index_key]=$context['g_name'];
			}
		}

		$index_key = &$context['data'][$g_index_key];
		$index_key = str_replace(',',' ',$index_key); // remove the , because it is a separator
		if ($context['lo'] == 'entries') {  // check it does not exist
			$tmpindex_key = $index_key;
			myaddslashes($tmpindex_key);
			$vo=$dao->find("BINARY g_name='". $tmpindex_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
			//$vo=$dao->find("g_name='". $index_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
			if ($vo->id) {
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
				if (!$votype->newbyimportallowed && $context['lo']!="entries") { return "_error"; }
				$new=true;
				$vo=$dao->createObject();
				$vo->status=$status ? $status : -1;
			}
		}
		if ($dao->rights['protect']) $vo->protect=$context['protected'] ? 1 : 0;
		if ($votype->flat) {
			$vo->idparent=0; // force the entry to be at root
		} else {
			$vo->idparent=intval($context['idparent']);
		}
		// populate the entry table
		if ($idtype) $vo->idtype=$idtype;
		$vo->g_name=$index_key;
		$vo->sortkey=makeSortKey($vo->g_name);
		$id=$context['id']=$dao->save($vo);
		// save the class table
		$gdao=&getGenericDAO($class,"identry");
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
	function changeRankAction (&$context, &$error) 
	{
		if ($this->maintable == 'persons') {
			die("ERROR in EntriesLogic:: function changeRankAction is not valid for persons logic");
		}
		return Logic::changeRankAction($context, $error, 'idparent', '');
	}

	/**
	 * Construction des balises select HTML pour cet objet (index seulement)
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 * @param string $edittype le type d'édition
	 */
	function makeSelect (&$context, $var) 
	{
		if ($this->maintable == 'persons') {
			die("ERROR in EntriesLogic:: function makeSelect is not valid for persons logic");
		}
		global $db;
		switch($var) {
		case 'idparent':
			$arr=array ();
			$rank=array ();
			$parent=array ();
			$ids=array (0);
			$l=1;
			do {
				$result=$db->execute (lq ("SELECT * FROM #_TP_entries WHERE idtype='".$context['idtype']."' AND id!='".$context['id']."' AND idparent ".sql_in_array ($ids). " AND ABS(status) = 32 ORDER BY ". $context['type']['sort'])) or dberror();
				$ids=array();
				$i=1;
				while (!$result->EOF) {
					$id=$result->fields['id'];
					$ids[]=$id;	 
					$fullname=$result->fields['g_name'];
					$idparent=$result->fields['idparent'];
					if ($idparent) $fullname=$parent[$idparent]." / ".$fullname;
					$d=$rank[$id]=$rank[$idparent]+($i*1.0)/$l;
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
			renderOptions ($arr2, $context[$var]);
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
	function _prepareDelete($dao, &$context) {
		global $db;
		// get the classes
		$this->classes = array ();

		// $this->daoname = persontypes OU entrytypes
		// $this->maintable = persons OU entries
		$result = $db->execute (lq ("SELECT DISTINCT class FROM #_TP_". $this->daoname . " INNER JOIN #_TP_" . $this->maintable . " ON idtype=#_TP_" . $this->daoname . ".id WHERE #_TP_" .$this->maintable. ".id ".sql_in_array ($context['id']))) or dberror ();

		while (!$result->EOF) {
			$this->classes[] = $result->fields['class'];
			$result->MoveNext();
		}

		if (isset($context['idrelation'])) {
			$this->idrelation=$context['idrelation'];
		} else {
			$dao=&getDAO ('relations');
			$vos=$dao->findMany ("id2 ".sql_in_array ($context['id']));
			$this->idrelation=array ();
			foreach ($vos as $vo) {
				$this->idrelation[]=$vo->idrelation;
			}
		}
	}


	/**
	 * Used in deleteAction to do extra operation after the object has been deleted (index ET persons)
	 */
	function _deleteRelatedTables($id) 
	{
		global $db;
		foreach ($this->classes as $class) {
			$gdao=&getGenericDAO ($class, $this->idtype);
			$gdao->deleteObject ($id);
		
			if($this->maintable == 'persons') {
				if ($this->idrelation) {
					$gdao=&getGenericDAO("entities_".$class,"idrelation");
					$gdao->deleteObject($this->idrelation);
				}
			}
		}

		if ($this->idrelation) {
			$dao=&getDAO ('relations');
			$dao->delete ('idrelation '. sql_in_array ($this->idrelation));
		}
	}
} // class 
?>
