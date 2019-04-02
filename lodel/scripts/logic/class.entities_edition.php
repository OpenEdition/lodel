<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des entités (gestion de l'édition)
 *
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
				if (empty($context['varname'])) return;

				$varname = $context['varname'];

				if (empty($context['idtype'])) {
					trigger_error("ERROR: internal error in Entities_EditionLogic::loop_persons_in_entities", E_USER_ERROR);
				}
				$idtype = $context['idtype'];
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

		if(!function_exists('loop_inline_entries_by_type_in_entities')){
			function loop_inline_entries_by_type_in_entities($context, $funcname)
			{
				if (empty($context['varname'])) {
					return;
				}
				$varname = $context['varname'];

				if (empty($context['idtype']) || !is_numeric($context['idtype'])) {
					return;
				}

				if(!isset($context['entries'][$context['idtype']])){
					return;
				}

				global $db;
				$idtype = $context['idtype'];
				$votype = DAO::getDAO("entrytypes")->getById($idtype, "id,sort,flat");
				if (!$votype) {
					trigger_error("ERROR: internal error in loop_entries_in_entities", E_USER_ERROR);
				}
				$context['id'] = 0; // start by the parents
				$degree        = 0;
				$maxdegree     = 0;

				foreach ( $context['entries'][$idtype] as $entry ) {
					$localcontext              = array_merge($context, $entry);
					$localcontext['name']      = $varname;
					$localcontext['classtype'] = 'entries';
					$localcontext['degree']    = $degree;

					call_user_func("code_do_$funcname", $localcontext);
					if($degree > $maxdegree) $maxdegree = $degree;
					$degree++;
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
				if (empty($context['varname'])) {
					return;
				}
				$varname = $context['varname'];

				if (empty($context['idtype']) || !is_numeric($context['idtype'])) {
					return;
				}

				global $db;
				$external = isset($context['external']) && $context['external'] ? true : false;

				if($external) {
					$entries_name = 'externalentries';
					$db->SelectDB(DATABASE.'_'.$context['matches'][1][0]);
				} else $entries_name = 'entries';

				$idtype = $context['idtype'];
				$votype = DAO::getDAO("entrytypes")->getById($idtype, "id,sort,flat");
				if (!$votype) {
					trigger_error("ERROR: internal error in loop_entries_in_entities", E_USER_ERROR);
				}

				if($external) $idtype = $context['matches'][1][0].'.'.$idtype;

				$checkarr = array();
				if (isset($context[$entries_name][$idtype]) && is_array($context[$entries_name][$idtype])) {
					foreach ($context[$entries_name][$idtype] as $entry) {
						$checkarr[] = &$entry['g_name'];
					}
				}

				$context['id'] = 0; // start by the parents
				loop_entries_in_entities_rec ($context, $funcname, $votype, $checkarr);
				if($external) {
					usecurrentdb();
				}
			}
		}
		if(!function_exists('loop_entries_in_entities_rec'))
		{
			function loop_entries_in_entities_rec ($context, $funcname, &$votype, &$checkarr)
			{
				global $db;
				if(!isset($context['id'])) return;
				$screen_name = $db->getRow(lq("SELECT field.name as screen_name, type.class as class_type from #_TP_tablefields field, #_TP_entrytypes type WHERE field.g_name='screen name' AND field.class=type.class AND type.id=".$context['idtype']));
                $is_screenname = false;
				if (!empty($screen_name)) {
				    $is_screenname = true;
				}
				// get the entries
				$result = $db->execute(lq("SELECT * FROM #_TP_entries WHERE idtype='". $votype->id. "' AND idparent='". $context['id']. "' AND status>-64 ORDER BY ". $votype->sort)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

				while (!$result->EOF) {
					$localcontext = array_merge($context, $result->fields);
					if ($is_screenname) {
					    $sn_value = $db->getRow(lq("SELECT ".$screen_name['screen_name']." as snname FROM ".$screen_name['class_type']." WHERE identry=".$result->fields['id']));
					    $localcontext['screen_name']=multilingue($sn_value['snname'], $context['sitelang']);
					}
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

				if (empty($context['varname'])) {
					if (function_exists("code_alter_$funcname")) {
						call_user_func("code_alter_$funcname",$context);
					}
					return;
				}
				$varname = $context['varname'];
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

		$context['timestamp'] = time();

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
			$debug_dump = '';
			if (!empty($context['siteinfos']) && strpos($context['siteinfos']['name'], 'calenda')!==false) {
				$debug_dump = "\n__CALENDA editAction context__:\n".print_r($context, true);
				$debug_dump .= "\n__CALENDA editAction SERVER__:\n".print_r($_SERVER, true);
				$debug_dump .= "\n__CALENDA editAction REQUEST__:\n".print_r($_REQUEST, true);
				$debug_dump .= "\n__CALENDA editAction FILES__:\n".print_r($_FILES, true);
			}
			trigger_error("ERROR: you don't have the right to perform this operation".$debug_dump, E_USER_ERROR);
		}
		if (isset($context['cancel'])) {
			return '_back';
		}

		$id = isset($context['id']) ? $context['id'] : null;
		if (!empty($id)) {
			$select = array();
			if(empty($context['idparent'])) $select[] = 'idparent';
			if(empty($context['idtype'])) $select[] = 'idtype';
			if(empty($context['status'])) $select[] = 'status';
			if(!empty($context['timestamp'])) $select[] = 'upd';
			if(!empty($select)) {
				$vo  = $this->_getMainTableDAO()->getById($id, join(',', $select));
				/* Vérification de modification concurrente */
				if ( isset($context['timestamp']) && (int)$context['timestamp'] < strtotime($vo->upd)) {
					$error['concurrent'] = "concurrent_edition";
					return "_error";
				}
				if(!$vo) trigger_error('ERROR: can not find entity '.$id, E_USER_ERROR);
				foreach($select as $v)
					$context[$v] = $vo->$v;
			}
		}

		if(empty($context['idtype']))
			trigger_error("ERROR: idtype is not valid in Entities_EditionLogic::editAction", E_USER_ERROR);

		$idparent = isset($context['idparent']) ? $context['idparent'] : null;
		$idtype   = $context['idtype'];
		$status = isset($context['status']) ? $context['status'] : null;
		$this->_isAuthorizedStatus($status);
		// iduser

		$context['iduser'] = !SINGLESITE && C::get('adminlodel', 'lodeluser') ? 0 : C::get('id', 'lodeluser');
		function_exists('checkTypesCompatibility') || include 'entitiesfunc.php';
		if (!checkTypesCompatibility($id, $idparent, $idtype)) {
			$error['idtype'] = "types_compatibility entity $id of type $idtype cannot be child of $idparent";
			return '_error';
		}

		// get the class
		$votype = DAO::getDAO("types")->getById($idtype, "class,creationstatus,search");
		if (!$votype) {
			trigger_error("ERROR: idtype is not valid in Entities_EditionLogic::editAction", E_USER_ERROR);
		}
		$class = $context['class']=$votype->class;
		// Récupération des valeurs par défaut pour les champs vides À L'IMPORT
		if (isset($context['lo']) && $context['lo'] == 'entities_import' && !empty($context['idtask']) && !$error) {
			$fields = DAO::getDAO("tablefields")->findMany("class='". $context['class']. "' AND status>0 AND type!='passwd'", "", "name,defaultvalue");
			foreach($fields as $field) {
				if (empty($context['data'][$field->name]))
					$context['data'][$field->name] = $field->defaultvalue;
			}
		}

		/* Éxécution des hooks */
		$this->_executeHooks($context, $error, 'pre');

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
			if(!empty($context['reload'])) {
			/*
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
                            $entryFields = DAO::getDAO('tablefields')->findMany("class='".$context['class']."' AND status>0 AND type='entries'");
                            if(!empty($entryFields))
                            {
                                $arrTypes = array();
                                $daoentrytypes = DAO::getDAO("entrytypes");
                                // remise à 0 des index lorsqu'on a newbyimportallowed = 0
                                foreach($entryFields as $entryField)
                                {
                                    $eType = $daoentrytypes->find("type='".$entryField->name."'");
                                    $arrTypes[$eType->id] = array('class' => $eType->class, 'newbyimportallowed' => $eType->newbyimportallowed);

                                    if(!$eType->newbyimportallowed)
                                        $context['entries'][$eType->id] = array();
                                }

                                unset($entryFields, $eType);

                                // on remet les index déjà enregistrés dans la bdd lorsqu'on a newbyimportallowed = 0
                                $Entryfields = DAO::getDAO("relations")->findMany("id1='{$id}' AND nature = 'E'", "", "id2");
                                $daoentries = DAO::getDAO("entries");
                                foreach($Entryfields as $ffield)
                                {
                                    $entry = $daoentries->getById($ffield->id2, 'idtype, g_name');
                                    if(!$arrTypes[$entry->idtype]['newbyimportallowed'])
                                        $context['entries'][$entry->idtype][] = array('g_name' => $entry->g_name, 'class' => $arrTypes[$entry->idtype]['class'], 'idtype' => $entry->idtype);
                                }

                                unset($arrTypes, $Entryfields, $entry);

                            }
			}

			$Filefields = DAO::getDAO("tablefields")->findMany("class='". $context['class']. "' AND status>0 AND (type='file' OR type='image')", "",   "name");
			foreach($Filefields as $ffield) {
				$gdaoaf = DAO::getGenericDAO ($class, "identity");
				$tmpfile = $gdaoaf->getById($id, $ffield->name);
				$fieldname = $ffield->name;
				if(isset($context['data'][$ffield->name]) && $context['data'][$ffield->name] == 'deleted') {
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

		$this->_executeHooks($context, $error, 'post');

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
         * Récupération d'un bloc pour le sitemap en ajax
         *
         * @param array &$context le contexte passé par référence
         * @param array &$error le tableau des erreurs éventuelles passé par référence
         */
        public function sitemapAction(&$context, &$error)
        {
                $GLOBALS['nodesk'] = true;
                $context['folder'] = 'yes';
                echo View::getView()->getIncTpl($context, 'edition', '', '', 1);
                return '_ajax';
        }

        /**
         * Changement du rank/parent d'une entité via le drag'n'drop côté lodel/edition
         *
         * @param array &$context le contexte passé par référence
         * @param array &$error le tableau des erreurs éventuelles passé par référence
         */
        public function ddchangerankAction(&$context, &$error)
        {
                if(empty($context['ids']) || empty($context['id']))
                        trigger_error('ERROR: please specify ids', E_USER_ERROR);

                $idparent = isset($context['idparent']) ? $context['idparent'] : null;
                $id = (int) $context['id'];
                $dao = DAO::getDAO('entities');
                $vo = $dao->find('id='.$id);
                if(!$vo) trigger_error('ERROR: invalid id', E_USER_ERROR);

                $idtype = (int)$vo->idtype;

                if((int)$vo->idparent !== $idparent)
                { // we change of parent
                        function_exists('checkTypesCompatibility') || include 'entitiesfunc.php';
                        if (!checkTypesCompatibility($id, $idparent, $idtype))
                                trigger_error('ERROR: invalid parent idtype', E_USER_ERROR);
			$oldidparent = $vo->idparent;
                        $vo->idparent = $idparent;
                }

                $i = 1;
                foreach($context['ids'] as $curid)
                {
                        $curid = explode('_', $curid);
                        if(count($curid) < 2) continue; // surely 'entity_' ??
                        $curid = (int)$curid[1];
                        if($curid === $id) {
                                $vo->rank = $i;
                                $dao->save($vo);
                        } else {
                                $curvo = $dao->find('id='.$curid);
                                if(!$curvo) trigger_error('ERROR: invalid id', E_USER_ERROR);
                                $curvo->rank = $i;
                                $dao->save($curvo);
                        }

                        ++$i;
                }

		if(isset($oldidparent))
		{
			global $db;
			$ids = $db->getArray(lq("SELECT id2 FROM #_TP_relations WHERE id1=".$id." AND nature='P'"));
			if($ids)
			{
				foreach($ids as $curid)
				{
					$db->execute(lq("UPDATE #_TP_relations SET id1=".$idparent." WHERE id1=".$oldidparent." AND id2=".$curid['id2']." AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
			$db->execute(lq("UPDATE #_TP_relations SET id1=".$idparent." WHERE id1=".$oldidparent." AND id2=".$id." AND nature='P'"))  or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}

                update();
                return '_ajax';
        }

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

		$entitiesLogic = Logic::getLogic('entities');
		$entitiesLogic->_publishSoftRelation(array($vo->id), $vo->status); //Mise à jour des softs relations

		// Entries and Persons
		foreach (array ('entries' => 'E', 'persons' => 'G') as $table => $nature) {
			// put the id's from entrees and autresentrees into idtypes
			if (empty($context[$table]) || !is_array($context[$table])) {
				continue;
			}
			
			//table name for types
			$table_types = ($table == 'entries'?'entry':'person').'types';

			$ctx_idtypes = array_keys ($context[$table]);
			
			$logic = Logic::getLogic($table);
			$ids         = array();
			$active_relations = array();
			foreach ($ctx_idtypes as $idtype) {
				if (empty($context[$table][$idtype])) {
					continue;
				}

				$itemscontext = $context[$table][$idtype];

				if(!is_array($itemscontext)) trigger_error('ERROR: invalid items', E_USER_ERROR);
				$degree = 1;
				$newRelations = false;
				$relations = array();
				foreach ($itemscontext as $k => $itemcontext) {
					if (!is_numeric($k)) {
						continue;
					}
					$itemcontext['identity'] = $vo->id;
					$itemcontext['do']       = $context['do'];
					$itemcontext['idtype']   = $idtype;
					$itemcontext['status']   = $status;
					$itemcontext['degree']   = $degree++;

					$ret = $logic->editAction($itemcontext, $error);
					if ($ret!="_error" && $itemcontext['id']) {
						if(!isset($ids[$idtype])) $ids[$idtype] = array();
						$ids[$idtype][] = $itemcontext['id'];
						if (isset($itemcontext['idrelation'])) {
							$relations[] = $itemcontext['idrelation'];
						} else {
							$newRelations = true;
						}
					}
				}
				if ($newRelations) {
					if (!$vo->id) {
						trigger_error("ERROR: internal error in Entities_EditionLogic ::_saveRelatedTables");
					}
					$degree = 1;
					foreach ($ids[$idtype] as $id) {
						$db->execute (lq ("REPLACE INTO #_TP_relations (id2,id1,nature,degree) VALUES ('". $id. "','". $vo->id."','". $nature."','". ($degree++). "')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$active_relations[] = $db->insert_id();
					}
				} else {
					$active_relations = array_merge($active_relations, $relations);
				}
			}
			// delete relation not used
			$criteria = "";
			if(count($active_relations) > 0) {
				$criteria = "AND idrelation NOT IN ('". join ("','", $active_relations). "')";
			}
			// We have to append another constraint telling that we want to delete only relations with a type we are aware of (present in $context)
			if(count($ctx_idtypes) > 0) {
				$ttdao = DAO::getDAO($table_types);
				$criteria .= " AND id2 IN (SELECT id FROM ".$table." WHERE idtype IN ('".join("','", $ctx_idtypes)."'))";
			}

			$entitiesLogic->_deleteSoftRelation ("id1='". $vo->id. "' ". $criteria, $nature);
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
				$entitiesLogic->_deleteSoftRelation("id1='". $vo->id. "' ". $criteria, $name);
			} // for each entities fields
		}

		// external entries
		$db->execute(lq('DELETE FROM #_TP_relations_ext WHERE id1='.$context['id'].' AND nature="E"'))
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if(!empty($context['externalentries']))
		{
			$entrytypes = array_keys($context['externalentries']);
			$daoet = DAO::getDAO('entrytypes');
			$daoe = DAO::getDAO('entries');
			$sql = '';
			$extsql = array();
			$cursite = $db->quote(C::get('site', 'cfg'));
			foreach($entrytypes as $entrytype)
			{
				if(empty($context['externalentries'][$entrytype])) continue;
				$i = 0;
				if(!preg_match('/^([a-z0-9\-]+)\.(\d+)$/', $entrytype, $m)) continue;

				if($db->database != DATABASE.'_'.$m[1])
					$db->SelectDB(DATABASE.'_'.$m[1]);
				$id = (int)$m[2];
				$ok = $daoet->find('id='.$id); // check the entry idtype
				if(!$ok) continue;
				$site = $db->quote($m[1]);
				if(!is_array($context['externalentries'][$entrytype]))
					$context['externalentries'][$entrytype] = explode(',', $context['externalentries'][$entrytype]);
				$entries =& $context['externalentries'][$entrytype];
				$degree = $db->GetOne(lq("SELECT MAX(degree) FROM #_TP_relations_ext WHERE id2={$context['id']} AND nature='E' AND site=".$site));
				$degree = $degree ? $degree+1 : 0;
				foreach($entries as $entry)
				{
					$entry = is_array($entry) ? trim($entry['g_name']) : trim($entry);
					if(!$entry) continue;
					$extentry = $daoe->find('BINARY(g_name)=BINARY('.$db->quote($entry).')', 'id');
					if(!$extentry) continue;
					$sql .= "({$context['id']}, {$extentry->id}, 'E', {$i}, {$site}),";
					if(!C::get('db_no_intrusion.'.$m[1], 'cfg'))
					{
                                            $extsql[] = lq("INSERT INTO `".DATABASE.'_'.$m[1]."`.#_TP_relations_ext (id1,id2,nature,degree,site)
						VALUES ({$extentry->id}, {$context['id']}, 'EE', {$degree}, {$cursite})");
                                        }
					++$i;
					++$degree;
				}
				$db->execute(lq('DELETE FROM #_TP_relations_ext WHERE id2='.$context['id'].' AND nature="EE" AND site='.$cursite))
					 or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}

			usecurrentdb();

			if($sql)
			{
				$sql = lq('INSERT INTO #_TP_relations_ext (id1, id2, nature, degree, site) VALUES ').substr($sql, 0, -1);
				$db->execute($sql) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				foreach($extsql as $s)
				{
					$db->execute($s) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
		}
	} //end of function

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
				$relatedtable[$class][$result->fields['id']][$degree] = &$ref;
				if ($table == "persons" || $table == "entries") {
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
						foreach( array_keys($ids[$id]) as $degree){
							if(!isset($ids[$id][$degree]['data']) || !is_array($ids[$id][$degree]['data'])) $ids[$id][$degree]['data'] = array();
							$ids[$id][$degree]['data'] = array_merge((array)$ids[$id][$degree]['data'], $result2->fields);
						}
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

		// external entries
		$result = $db->execute(lq("SELECT * FROM #_TP_relations_ext WHERE id1='". $vo->id. "' AND nature='E' ORDER BY idrelation DESC"))
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		$dao = DAO::getDAO('entries');
		$context['externalentries'] = array();
		while(!$result->EOF) {
			$site = $result->fields['site'];
			if($db->database != DATABASE.'_'.$site)
				$db->SelectDB(DATABASE.'_'.$site);

			$id = $result->fields['id2'];
			$entry = $dao->find('id='.$id, 'idtype, g_name');
			if($entry)
			{
				if(!isset($context['externalentries'][$site.'.'.$entry->idtype]))
					$context['externalentries'][$site.'.'.$entry->idtype] = array();
				$context['externalentries'][$site.'.'.$entry->idtype][$result->fields['degree']] = array('g_name'=>$entry->g_name);
			}
			$result->MoveNext();
		}
		usecurrentdb();
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
