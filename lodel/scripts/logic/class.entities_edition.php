<?php
/**	
 * Logique des entités - edition
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
 * Classe de logique des entités (gestion de l'édition)
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
class Entities_EditionLogic extends GenericLogic 
{

	/**
	 * Tableau des équivalents génériques
	 *
	 * @var array
	 */
	public $g_name;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct('entities');
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context, &$error)
	{
		if (isset($context['check']) && $error) {
			return '_error';
		}
		if (!rightonentity(!empty($context['id']) ? 'edit' : 'create', $context)) {
			trigger_error('ERROR: you don\'t have the right to perform this operation', E_USER_ERROR);
		}

		// define some loop functions
		if(!function_exists('loop_persons_in_entities'))
		{
			function loop_persons_in_entities($context, $funcname)
			{
				$varname = @$context['varname'];
				if (!$varname) return;
	
				$idtype = @$context['idtype'];
				if (!$idtype) {
					trigger_error("ERROR: internal error in Entities_EditionLogic::loop_persons_in_entities", E_USER_ERROR);
				}
				if (!is_numeric($idtype)) {
					return;
				}
			
				if (!isset($context['persons'][$idtype]) || !is_array($context['persons'][$idtype])) {
					call_user_func("code_alter_$funcname",$context);
					return;
				}
				$maxdegree = 0;
				foreach($context['persons'][$idtype] as $degree => $arr) {
					if (!is_numeric($degree)) {
						continue;
					}
					$localcontext              = array_merge($context,$arr);
					$localcontext['name']      = $varname;
					$localcontext['classtype'] = "persons";
					$localcontext['degree']    = $degree;
					if ($degree > $maxdegree) {
						$maxdegree = $degree;
					}
					call_user_func("code_do_$funcname", $localcontext);
				}
	
				if (function_exists("code_after_$funcname")) {
					$localcontext = $context;
					$localcontext['maxdegree'] = $maxdegree;
					call_user_func("code_after_$funcname", $localcontext);
				}
			}	  
		}
		if(!function_exists('loop_entries_in_entities'))
		{
			function loop_entries_in_entities($context, $funcname) 
			{
				$varname = @$context['varname'];
				if (!$varname) {
					return;
				}
	
				$idtype = @$context['idtype'];
				if (!is_numeric($idtype)) {
					return;
				}
				$votype = DAO::getDAO("entrytypes")->getById($idtype, "id,sort,flat");
				if (!$votype) {
					trigger_error("ERROR: internal error in loop_entries_in_entities", E_USER_ERROR);
				}
				if (isset($context['entries'][$idtype]) && is_array($context['entries'][$idtype])) {
					foreach ($context['entries'][$idtype] as $entry) {
						$checkarr[] = &$entry['g_name'];
					}
				}
				$context['id'] = 0; // start by the parents
				loop_entries_in_entities_rec ($context, $funcname, $votype, $checkarr);
			}
		}
		if(!function_exists('loop_entries_in_entities_rec'))
		{
			function loop_entries_in_entities_rec ($context, $funcname, &$votype, &$checkarr) 
			{
				global $db;
				$context['id'] = @$context['id'];
				// get the entries
				$result = $db->execute(lq("SELECT * FROM #_TP_entries WHERE idtype='". $votype->id. "' AND idparent='". $context['id']. "' AND status>-64 ORDER BY ". $votype->sort)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				
				while (!$result->EOF) {
					$localcontext = array_merge($context, $result->fields);
					$localcontext['root'] = '';
					$localcontext['selected'] = $checkarr && in_array($result->fields['g_name'],$checkarr) ? "selected=\"selected\"" : "";
					call_user_func("code_do_$funcname", $localcontext);
					if (!$votype->flat) {
						$localcontext['root'].= $localcontext['g_name']. "/";
					}
					loop_entries_in_entities_rec ($localcontext, $funcname, $votype, $checkarr);
					$result->MoveNext();
				}
			}
		}
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
		/////
		$ret = parent::viewAction($context, $error);

		//if ((!$context['id'] || (preg_match ("/servoo.*/", $context['creationmethod']) &&  $context['status'] == -64)) && !$error) { // add
		if (empty($context['id']) && !$error) { // add : récupération des valeurs par défaut
			$fields = DAO::getDAO("tablefields")->findMany("class='". $context['type']['class']. "' AND status>0 AND type!='passwd'", "",	"name,defaultvalue");
			foreach($fields as $field) {
				if (empty($context['data'][$field->name]))
					$context['data'][$field->name] = $field->defaultvalue;
			}
		}
	
		if (isset($context['check']) && !$error) {
			$context['status'] = -1;
			//il semble nécessaire de nettoyer request pour eviter les requetes pétées.
			C::clean($context);
			return $this->editAction($context, $error);
		}
		return $ret ? $ret : "_ok";
	}



	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Ajout d'une nouvelle entité. Dans un premier temps on vérifie si l'utilisateur
	 * possède les bons droits pour modifier ou ajouter cette entité. Ensuite les différents
	 * champs de l'entité sont validés. Si ceux-ci sont valide, alors l'objet est créé dans
	 * la base de données et les objets liés sont aussi créés : personnes, entrées d'index
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function editAction (&$context, &$error, $clean = false)
	{
		if (!rightonentity(!empty($context['id']) ? 'edit' : 'create', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}
		if (isset($context['cancel'])) {
			return '_back';
		}
		$id = @$context['id'];
		if ($id) {
			$select = array();
			if(empty($context['idparent'])) $select[] = 'idparent';
			if(empty($context['idtype'])) $select[] = 'idtype';
			if(empty($context['status'])) $select[] = 'status';
			if($select) {
				$vo  = $this->_getMainTableDAO()->getById($id, join(',', $select));
				if(!$vo) trigger_error('ERROR: can not find entity '.$id, E_USER_ERROR);
				foreach($select as $v)
					$context[$v] = $vo->$v;
			}
		}
		$idparent = @$context['idparent'];
		$idtype   = @$context['idtype'];
		$status = @$context['status'];
		$this->_isAuthorizedStatus($status);
		// iduser
		$context['iduser'] = !SINGLESITE && C::get('adminlodel', 'lodeluser') ? 0 : C::get('id', 'lodeluser');
		function_exists('checkTypesCompatibility') || include 'entitiesfunc.php';
		if (!checkTypesCompatibility($id, $idparent, $idtype)) {
			$error['idtype'] = 'types_compatibility';
			return '_error';
		}

		// get the class 
		$votype = DAO::getDAO("types")->getById($idtype, "class,creationstatus,search");
		if (!$votype) {
			trigger_error("ERROR: idtype is not valid in Entities_EditionLogic::editAction", E_USER_ERROR);
		}
		$class = $context['class']=$votype->class;
		$context['lo'] = @$context['lo'];
		// Récupération des valeurs par défaut pour les champs vides À L'IMPORT
		if ($context['lo'] == 'entities_import' && !empty($context['idtask']) && !$error) {
			$fields = DAO::getDAO("tablefields")->findMany("class='". $context['class']. "' AND status>0 AND type!='passwd'", "", "name,defaultvalue");
			foreach($fields as $field) {
				if (empty($context['data'][$field->name]))
					$context['data'][$field->name] = $field->defaultvalue;
			}
		}
        	$ret = '';
		if (!$this->validateFields($context, $error)) {
			// error.
			// if the entity is imported and will be checked
			// that's fine, let's continue, if not return an error
			if ($clean == 'FORCE') { 
				$status=-64;
				$ret="_error";
			}
			else
			{
				return "_error";
			}
		}

		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$now = date("Y-m-d H:i:s");
		if ($id) { //edit the entity
			$new = false;
			$vo = $dao->getById($id, "id,identifier,status, g_title");
			if ($vo->status >= 16) {
				trigger_error("ERROR: entity is locked. No operation is allowed", E_USER_ERROR);
			}
			// possibly document reloading
/*			if(isset($context['reload']) && $context['reload']) {
				// let's deal with document reloading problem : PDF file and entries disapeared :
				$daotablefields = DAO::getDAO("tablefields");
				$Filefields = $daotablefields->findMany("class='". $context['class']. "' AND status>0 AND (type='file' OR type='image')", "",	"name");
				foreach($Filefields as $ffield) {
					$gdaoaf = DAO::getGenericDAO ($class, "identity");
					$tmpfile = $gdaoaf->getById($id, $ffield->name);
					$fieldname = $ffield->name;
					if($context['data'][$ffield->name] == 'deleted') {
						$context['data'][$ffield->name] = '';
					} elseif(empty($context['data'][$ffield->name]) && !empty($tmpfile->$fieldname)) {
						$name = $ffield->name;
						$context['data'][$ffield->name] = $tmpfile->$name;
					}
				}
				// entries
			/*	$daorelations = DAO::getDAO("relations");
				$Entryfields = $daorelations->findMany("id1='{$id}' AND nature = 'E'", "", "id2");
				$daoentries = DAO::getDAO("entries");
				$daoentrytypes = DAO::getDAO("entrytypes");
				foreach($Entryfields as $ffield) {
					$reloaded = false;
					$entry = $daoentries->getById($ffield->id2, 'idtype, g_name');
					$entryclass = $daoentrytypes->getById($entry->idtype, 'class');
					if(is_array($context['entries'][$entry->idtype])) {
						foreach($context['entries'][$entry->idtype] as $entryfield) {
							if($entryfield['g_name'] == $entry->g_name) {
								$reloaded = true;
								break;
							}
						}
					}
					if($reloaded) continue;
					$pos = count($context['entries'][$entry->idtype]);
					$context['entries'][$entry->idtype][$pos]['g_name'] = $entry->g_name;
					$context['entries'][$entry->idtype][$pos]['class'] = $entryclass->class;
					$context['entries'][$entry->idtype][$pos]['idtype'] = $entry->idtype;
					$indexfields = $daotablefields->findMany("class='{$entryclass->class}'",'', 'name');
					foreach($indexfields as $indexfield)
						$context['entries'][$entry->idtype][$pos]['data'][$indexfield->name] = null;
				}
				unset($entry, $entryclass, $daoentries, $daoentrytypes, $Entryfields, $daorelations, $daotablefields, $Filefields); // save some memory
*/
//			}

			$Filefields = DAO::getDAO("tablefields")->findMany("class='". $context['class']. "' AND status>0 AND (type='file' OR type='image')", "",   "name");
                        foreach($Filefields as $ffield) {
                                $gdaoaf = DAO::getGenericDAO ($class, "identity");
                                $tmpfile = $gdaoaf->getById($id, $ffield->name);
                                $fieldname = $ffield->name;
                                if($context['data'][$ffield->name] == 'deleted') {
                                        $context['data'][$ffield->name] = '';
                                } elseif(empty($context['data'][$ffield->name]) && !empty($tmpfile->$fieldname)) {
                                        $name = $ffield->name;
                                        $context['data'][$ffield->name] = $tmpfile->$name;
                                }
                        }

			// change the usergroup of the entity ?
			if (C::get('admin', 'lodeluser') && C::get('usergroup', 'lodeluser')) {
				$vo->usergroup = (int)C::get('usergroup', 'lodeluser');
			}
			if ($vo->status<=-64) {  // like a creation
				$vo->status = $votype->creationstatus;
				$vo->creationdate = $now;
			}
			$vo->idparent = $idparent;
		} else { //create the entity
			$new = true;
			$vo = $dao->createObject ();
			$vo->idparent       = $idparent;
			$vo->usergroup      = $this->_getUserGroup ($context, $idparent);
			$vo->iduser         = C::get('id', 'lodeluser');
			$vo->status         = $status ? $status : $votype->creationstatus;
			$vo->creationdate   = $now;
		}
		$vo->modificationdate = $now;
		// populate the entity
		if ($idtype) {
			$vo->idtype=$idtype;
		}
		// permanent identifier management
		$dctitle = $this->getGenericEquivalent ($class, 'dc.title');
		if ($dctitle) {
			$vo->g_title = strip_tags ($context['data'][$dctitle], "<em><strong><span><sup><sub>");
		} else $vo->g_title = '';
		// If Identifier is not set, let's calcul it with the generic title
		$context['identifier'] = isset($context['identifier']) ? $context['identifier'] : '';
		if (!$vo->identifier && trim($context['identifier']) === '') {
			$vo->identifier = $this->_calculateIdentifier ($id, $vo->g_title);
		}	else { // else simply clean bad chars
			if(empty($context['identifier'])) {
				$context['identifier'] = $vo->identifier;
			}
			/*if (is_null ($context['identifier'])) {//identifier desactivated
				$vo->identifier = $this->_calculateIdentifier ($id, $vo->identifier);
			} else {// else that means that we have modified it*/
				$vo->identifier= $this->_calculateIdentifier ($id, $context['identifier']);
			/*}*/
		}
		//print_r($context);
		if (!empty($context['creationmethod'])) {
			$vo->creationmethod = $context['creationmethod'];
		}
		if (!empty($context['creationinfo'])) {
			$vo->creationinfo = $context['creationinfo'];
		}
	
		$id = $context['id'] = $dao->save($vo);
		// change the group recursively
		$gdao = DAO::getGenericDAO ($class, "identity");
		$gdao->instantiateObject ($gvo);
		$context['data']['id'] = $context['id'];
		$this->_moveImages ($context['data']);
		$this->_populateObject ($gvo, $context['data']);
		$gvo->identity = $id;
		$this->_moveFiles ($id, $this->files_to_move, $gvo);
		$gdao->save ($gvo, $new);  // save the related table
		if ($new) {
			$this->_createRelationWithParents ($id, $idparent);
		}
		$this->_saveRelatedTables ($vo, $context);
		// check if the entities have an history field defined
		$this->_processSpecialFields ('history', $context);
		
		if ($ret=="_error") {
			return "_error";
		}
		// pour indexer aller dans lodel/admin/ et cliquer sur réindexer
        	// ou activer l'option dans le siteconfig.php
		if (C::get('searchEngine', 'cfg') && $votype->search) {
			$lo_entities_index = new Entities_IndexLogic();
			$lo_entities_index->addIndexAction($context, $error);
		}
		update();

		// pour import de plusieurs entités à la suite
		if(isset($context['next_entity'])) {
			if ((string)$context['next_entity'] === 'yes') { return $ret = "_next"; }
			else { return $ret = "_back"; }
		}

		if (isset($context['visualiserdocument'])) {
			return "_location: ". SITEROOT. makeurlwithid($id);
		}
		return $ret ? $ret : "_back";
		//return $ret ? $ret : "_location" . SITEROOT. makeurlwithid($idparent);
	} //end of editAction

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 * @param string $edittype le type d'édition
	 */
	public function makeSelect(&$context, $var, $edittype)
	{
		switch($var) {
		case 'creationinfo':
			if (isset($context['creationmethod']) && $context['creationmethod']!='form') {
				trigger_error("ERROR: error creationmethod must be 'form' to choose the creationinfo", E_USER_ERROR);
			}
			$arr = array("xhtml"=>"XHTML", "wiki"=>"Wiki", "bb"=>"BB");
			renderOptions($arr, isset($context['creationinfo']) ? $context['creationinfo'] : '');
			break;
		}
		switch($edittype) {
		case 'lang':
			function_exists('makeselectlangs') || include 'lang.php';
			makeselectlangs(isset($context[$var]) ? $context[$var] : '');
			break;
		}
	}

	/**
	 * Déplace les liens images quand un nouvel identifiant est connu
	 *
	 * Cette méthode n'est pas définie ici.
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @access private
	 */
	protected function _moveImages(&$context) {}

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
		if (!isset($vo->status)) {
			$vo  = $this->_getMainTableDAO()->getById($vo->id, 'status,id');
		}
		$status = null;
		if ($vo->status>-64 && $vo->status<=-1) {
			$status = -1;
		}
		if ($vo->status >= 1) {
			$status = 1;
		}
		//Mise à jour des softs relations
		Logic::getLogic('entities')->_publishSoftRelation(array($vo->id), $vo->status);

		// Entries and Persons
		foreach (array ('entries' => 'E', 'persons' => 'G') as $table => $nature) {
			// put the id's from entrees and autresentrees into idtypes
			if (empty($context[$table]) || !is_array($context[$table])) {
				continue;
			}
            		$idtypes = array_keys ($context[$table]);
			$logic = Logic::getLogic($table);
			$ids         = array();
			$idrelations = array();
			foreach ($idtypes as $idtype) {
				if (empty($context[$table][$idtype])) {
					continue;
				}
                		$itemscontext = $context[$table][$idtype];
				if(!is_array($itemscontext)) trigger_error('ERROR: invalid items', E_USER_ERROR);
				$degree = 1;
				foreach ($itemscontext as $k => $itemcontext) {
					if (!is_numeric($k)) {
						continue;
					}
					$itemcontext['identity'] = $vo->id;
					$itemcontext['idtype']   = $idtype;
					$itemcontext['status']   = $status;
					$itemcontext['degree']   = $degree++;
					//$ret = $logic->editAction($itemcontext, $error, 'CLEAN');
					$ret = $logic->editAction($itemcontext, $error);
					if ($ret!="_error" && $itemcontext['id']) {
						if(!isset($ids[$idtype])) $ids[$idtype] = array();
						$ids[$idtype][] = $itemcontext['id'];
						if (isset($itemcontext['idrelation'])) {
							$idrelations[] = $itemcontext['idrelation'];
						}
					}
				}
			}
			// delete relation not used
			if ($ids && !$idrelations) { // new item but relation has not been created
				if (!$vo->id) {
					trigger_error("ERROR: internal error in Entities_EditionLogic ::_saveRelatedTables");
				}
				$values      = array ();
				$idrelations = array ();
				foreach ($ids as $ids2) {
					$degree = 1;
					foreach ($ids2 as $id) {
						$db->execute (lq ("REPLACE INTO #_TP_relations (id2,id1,nature,degree) VALUES ('". $id. "','". $vo->id."','". $nature."','". ($degree++). "')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$idrelations[] = $db->insert_id();
					}
				}
			}
			$criteria = $idrelations ? "AND idrelation NOT IN ('". join ("','", $idrelations). "')" : "";
			
			#echo "criteria=$criteria";
			$this->_deleteSoftRelation ("id1='". $vo->id. "' ". $criteria, $nature);
		} // foreach entries and persons
		// Entities
		if (!empty($context['entities'])) {
			$dao = DAO::getDAO('tablefields');
            		$keys = array_keys ($context['entities']);
			foreach ($keys as $name) {
				$name = addslashes ($name);
				$vofield = $dao->find("class='". $context['class']. "' AND name='". $name. "' AND type='entities'");
				if (!$vofield) {
					if(preg_match('/^([a-zA-Z]+)(\d+)$/', $name, $m))
					{
						$vofield = $dao->find("class='". $context['class']. "' AND name='". addslashes($m[1]). "' AND type='entities'");
					}
					if(!$vofield)
						trigger_error ("invalid name for field of type entities", E_USER_ERROR);
					if(!is_array($context['entities'][$name])) $context['entities'][$name] = explode(',', $context['entities'][$name]);
				}
				$idrelations = array ();
				if (is_array($context['entities'][$name])) {
					$i=0;
					foreach ($context['entities'][$name] as $id) {
						$id = (int)$id;
						if(!$id) continue;
						$db->execute (lq ("REPLACE INTO #_TP_relations (id2,id1,nature,degree) VALUES ('". $id. "','". $vo->id. "','". $name. "',".$i.")")) 
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$idrelations[] = $db->insert_id();
						++$i;
					}
				}
				$criteria = $idrelations ? "AND idrelation NOT IN ('". join("','", $idrelations). "')" : "";
				$this->_deleteSoftRelation("id1='". $vo->id. "' ". $criteria, $name);
			} // for each entities fields
		}
	} //end of function

	/**
	 * Suppression des relations dites 'soft'
	 *
	 * Suppression des personnes ou entrées d'indexs liées à une entité
	 *
	 * @param array $ids les identifiants des objets liés
	 * @param string $nature la nature des objets liés
	 */
	//most of this should be transfered in the entries and persons logic
	protected function _deleteSoftRelation ($ids, $nature = '')
	{
		global $db;
		if (is_array ($ids)) {
			$criteria = "id1 IN ('".join ("','", $ids). "')";
		} elseif (is_numeric ($ids)) {
			$criteria = "id1='". $ids. "'";
		} else {
			$criteria = $ids;
		}
        	$checkjointtable = false;
		if ($nature == 'E') {
			$naturecriteria = " AND nature='E'";
		} elseif ($nature == 'G') {
			$naturecriteria = " AND nature='G'";
			$checkjointtable = true;
		} elseif (strlen ($nature)>1) {
			$naturecriteria = " AND nature='". $nature. "'";
			$checkjointtable = false;
		} else  {
			$naturecriteria = " AND nature IN ('G','E') OR LENGTH(nature)>1";
			$checkjointtable = true;
		}

		if ($checkjointtable) {
		// with Mysql 4.0 we could do much more rapid stuff using multiple delete. How is it supported by PostgreSQL, I don't not... so brute force:
		// get the joint table first
			$dao = DAO::getDAO('relations');
			$vos = $dao->findMany($criteria. $naturecriteria, '', 'idrelation');
			$ids = array();
			foreach ($vos as $vo) { 
				$ids[]= $vo->idrelation; 
			}
			if ($ids) {
				// getting the tables name from persons and persontype would be to long. Let's suppose
				// the number of classes are low and it is worse trying to delete in all the tables
				$dao    = DAO::getDAO('classes');
				$tables = $dao->findMany("classtype='persons'", '', 'class');
				$where  = "idrelation IN ('".join("','",$ids)."')";
				foreach($tables as $table) {
					$db->execute(lq("DELETE FROM #_TP_entities_". $table->class. " WHERE ". $where)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
		}
		$db->execute(lq("DELETE FROM #_TP_relations WHERE ". $criteria. $naturecriteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// select all the items not in entities_$table
		// those with status<=1 must be deleted
		// thise with status> must be depublished
		foreach(array('entries', 'persons') as $table) {
			$result = $db->execute (lq ("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_relations ON id2=id WHERE id1 is NULL")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$idstodelete    = array();
			$idstounpublish = array();
			while (!$result->EOF) {
				if (abs ($result->fields['status']) == 1) {
					$idstodelete[]    = $result->fields['id']; 
				} else {
					$idstounpublish[] = $result->fields['id']; 
				}
				$result->MoveNext();
			}

			if ($idstodelete) {
				$logic = Logic::getLogic($table);
				$localcontext = array('id' => $idstodelete, 'idrelation' => array());
				$err = array();
				$logic->deleteAction($localcontext, $err);
			}

			if ($idstounpublish) {
				$db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN (". join(",", $idstounpublish). ") AND status>=32")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		} //end of foreach tables
	}



	/**
	 * Récupère le groupe utilisateur d'une nouvelle entité
	 * return the usergroup for new entity
	 *
	 * @param array $contextle contexte
	 * @param integer $idparent identifiant du parent
	 */
	protected function _getUserGroup(&$context, $idparent)
	{
		global $db;
		if (C::get('admin', 'lodeluser')) { // take it from the context.
			$usergroup = (int)C::get('usergroup', 'lodeluser');
			if ($usergroup > 0) {
				return $usergroup;
			}
		}
		if ($idparent) { // take the group of the parent
			$vo = $this->_getMainTableDAO()->getById($idparent, 'id, usergroup');
			$usergroup = $vo->usergroup;
			if ($db->errorno()) {
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if (!$usergroup) {
				trigger_error("ERROR: You have not the rights: (2)", E_USER_ERROR);
			}
		} else {
			$usergroup = 1;
			# trigger_error("ERROR: Only administrator have the rights to add an entity at this level", E_USER_ERROR);
		}
		return $usergroup;
	}

	/**
	 * Création des relations avec les parents d'une entité
	 *
	 * Cette méthode crée les relations entre une entité et tous ses ancêtres. 
	 *
	 * @param integer identifiant de la nouvelle entité
	 * @param integer identifiant du parent direct de la nouvelle entité
	*/
	protected function _createRelationWithParents($id, $idparent)
	{
		global $db;
		// can't do INSERT SELECT because work on the same table... support for old MySQL version
		$result = $db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='".$idparent."' AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$values = '';
		while (!$result->EOF) {
			$values.= "('". $result->fields['id1']. "','$id','P','". ($result->fields['degree']+1). "'),";
			$result->MoveNext();
		}
		$values.= "('". $idparent. "','". $id. "','P',1)";
		$db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		//if ($lock) unlock();
	}

	/**
	 * Utilisé dans la méthode viewAction() pour remplir le contexte d'information supplémentaire
	 *
	 * @param object &$vo l'objet utilisé dans viewAction
	 * @param array &$context le contexte, tableau passé par référence
	 */
	protected function _populateContextRelatedTables($vo, &$context)
	{
		global $db;
		foreach (array('entries' => array('E', 'identry', 'entrytypes'),
				'persons' => array('G', 'idperson', 'persontypes')) as $table => $info) {
			list($nature, $idfield, $type) = $info;
			$degree = 0;
			
			$result = $db->execute(lq("
				SELECT #_TP_$table.*,#_TP_relations.idrelation,#_TP_relations.degree,#_TP_$type.class 
					FROM #_TP_$table INNER JOIN #_TP_relations ON id2=#_TP_$table.id INNER JOIN #_TP_$type ON #_TP_$table.idtype=#_TP_$type.id 
					WHERE  id1='".$vo->id. "' AND nature='". $nature. "'  ORDER BY degree")) 
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$relatedtable         = array();
			$relatedrelationtable = array();
			while (!$result->EOF) {
				$degree = $result->fields['degree'] ? $result->fields['degree'] : (++$degree);
				unset($ref); // detach from the other references
				$ref   = $result->fields;
				$class = $result->fields['class'];
				$relatedtable[$class][$result->fields['id']] = &$ref;
				if ($table == "persons") {
					$relatedrelationtable[$class][$result->fields['idrelation']] = &$ref;
				}
				if(!isset($context[$table])) $context[$table] = array();
				$context[$table][$result->fields['idtype']][$degree] = &$ref;
				$result->MoveNext();
			}

			// load related table
			if ($relatedtable) {
				foreach ($relatedtable as $class => $ids) {
					$result2=$db->execute(lq("SELECT * FROM #_TP_".$class." WHERE ".$idfield." ".sql_in_array(array_keys($ids)))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					while (!$result2->EOF) {
						$id = $result2->fields[$idfield];
						if(!isset($ids[$id]['data']) || !is_array($ids[$id]['data'])) $ids[$id]['data'] = array();
						$ids[$id]['data'] = array_merge((array)$ids[$id]['data'], $result2->fields);
						$result2->MoveNext();
					}
				}
			}
			// load relation related table
			if ($relatedrelationtable) {
				foreach ($relatedrelationtable as $class=>$ids) {
					$result2 = $db->execute(lq("SELECT * FROM #_TP_entities_".$class." WHERE idrelation ".sql_in_array(array_keys($ids)))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					while (!$result2->EOF) {
						$id = $result2->fields['idrelation'];
						if(!isset($ids[$id]['data']) || !is_array($ids[$id]['data'])) $ids[$id]['data'] = array();
						$ids[$id]['data'] = array_merge((array)$ids[$id]['data'], $result2->fields);
						$result2->MoveNext();
					}
				}
			}

		} // foreach classtype
		
		// Entities
		$result = $db->execute(lq("SELECT * FROM #_TP_relations WHERE id1='". $vo->id. "' AND LENGTH(nature)>1 ORDER BY idrelation DESC")) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$relations = array();
		$multiple = false;
		$count = 0;
		while(!$result->EOF) {
			if(!isset($relations[$result->fields['nature']][$result->fields['degree']]) || !is_array($relations[$result->fields['nature']][$result->fields['degree']]))
				$relations[$result->fields['nature']][$result->fields['degree']] = array();

			$relations[$result->fields['nature']][$result->fields['degree']][] = $result->fields['id2'];
			$result->MoveNext();
		}
		if($relations)
			$context['entities'] = array();
		foreach($relations as $k=>$v) {
			foreach($v as $ids)
			{
				if(isset($context['entities'][$k]))
					$context['entities'][$k] .= join(',', $ids).',';
				else
					$context['entities'][$k] = join(",", $ids).',';
			}
		}
	}

	/**
	 * Calcul des identifiants littéraux d'une entité
	 *
	 * Cette méthode permet de générer un identifiant littéral, utilisé pour les liens permanents
	 * pour une entité. Elle utilise son titre
	 *
	 * @param integer $id identifiant numérique de l'entité
	 * @param string $title titre générique de l'entité
	 */
	protected function _calculateIdentifier($id, $title)
	{
		return preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($title)));
	}
	
	// begin{publicfields} automatic generation  //
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //
	// end{uniquefields} automatic generation  //

} // class
?>
