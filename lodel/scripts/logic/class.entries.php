<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */


/**
 * Classe de logique des entrées
 *
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
		if (empty($context['id'])) // pour une nouvelle entrée on propose le statut du type de l'entrée
			$context['status']=DAO::getDAO ("entrytypes")->getById ($context['idtype'],"status")->status;
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
		if(empty($context['id']))
			trigger_error('ERROR: missing id', E_USER_ERROR);

		$dao = $this->_getMainTableDAO();
		$id = (int) $context['id'];
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
		global $db;
		$daotype = DAO::getDAO ($this->daoname);

		if(empty($context['idtype']))
			trigger_error("ERROR: idtype must be known in EntriesLogic::viewAction", E_USER_ERROR);

		if(C::get('site_ext', 'cfg'))
		{
			$context['external'] = true;
			$context['site_ext'] = C::get('site_ext', 'cfg');
			$context['db_ext'] = DATABASE.'_'.$context['site_ext'];
			$db->SelectDB($context['db_ext']) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		} else $context['external'] = false;

		$votype = $daotype->getById($context['idtype']);
		if (!$votype) {
			trigger_error("ERROR: idtype must be known in EntriesLogic::viewAction", E_USER_ERROR);
		}
		if(!isset($context['type']) || !is_array($context['type']))
			$context['type'] = array();

		$daotablefields = DAO::getDAO("tablefields");
		$g_names = $daotablefields->findMany("class='". $votype->class. "' AND status>0 AND g_name!=''", "", "name,g_name");
		foreach ($g_names as $g_name) {
			$name = str_replace(' ', '_', $g_name->g_name);
			$context[$name] = $g_name->name;
		}
		if (!isset($context['screen_name']))
			$context['screen_name'] = isset($context['index_key']) ? $context['index_key'] : "";

		usecurrentdb();

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

		if (empty($context['idtype'])) {
			trigger_error("ERROR: idtype must be known in EntriesLogic::editAction", E_USER_ERROR);
		}

		// get the class
		$idtype = $context['idtype'];
		$daotype = DAO::getDAO ("entrytypes");
		$votype = $daotype->getById ($idtype, "class,newbyimportallowed,flat,sort,status");
		if(!$votype)
			trigger_error("ERROR: invalid idtype", E_USER_ERROR);
		$class = $context['class'] = $votype->class;

		$this->_executeHooks($context, $error, 'pre');

		$id = isset($context['id']) ? $context['id'] : null;
		$status = isset($context['status']) ? $context['status'] : null;
		if ($votype->status == 21) // 21 == INDÉPUBLIABLE
			$status = 21;
		if (!$clean) {
			if (!$this->validateFields($context,$error)) {
				if ($status>-64) { // if the entity is imported and will be checked that's fine, let's continue
					return "_error";
				}
			}
		}
		$g_index_key = $this->getGenericEquivalent($class,'index key');
		if (!$g_index_key) {
			trigger_error("ERROR: The generic field 'index key' is required. Please edit your editorial model.", E_USER_ERROR);
		}

		// get the dao for working with the object
		$dao = $this->_getMainTableDAO ();

		// search if the entries exists
		$vo = null;
		$entry_exists = false;
		if (isset ($context['g_name'])) {
			if (!$context['g_name']) return '_error'; // empty entry!
			$tmpgname = $context['g_name'];
			myaddslashes($tmpgname);
			$vo = $dao->find ("BINARY g_name='". $tmpgname. "' AND idtype='". $idtype."' AND status>-64");
			if ($vo && $vo->id) {
				$entry_exists=true;
				if (isset($dao->rights['protect']))
					$context['protected'] = true;
				$context['idparent'] = $vo->idparent;
				$id=$context['id'] = $vo->id;
			}

if (empty($context['data'][$g_index_key])) {
			$context['data'][$g_index_key]=$context['g_name']; }
		}

		if(empty($context['data'][$g_index_key]))
			return '_error';

		if (!$entry_exists) {
			$index_key = &$context['data'][$g_index_key];
			$index_key = str_replace(',',' ',$index_key); // remove the , because it is a separator
			if (isset($context['lo']) && $context['lo'] == 'entries') {  // check it does not a duplicate
				$tmpindex_key = $index_key;
				myaddslashes($tmpindex_key);
				$vo=$dao->find("BINARY g_name='". $tmpindex_key. "' AND idtype='". $idtype. "' AND status>-64 AND id!='".$id."'", 'id');
				if ($vo && $vo->id) {
					$error[$g_index_key] = "1";
					return '_error';
				}
			}

			if (!$vo) { // create or edit the entity

				if ($id) {
					$new=false;
					$dao->instantiateObject ($vo);
					$vo->id=$id;
					if ($status == 21) $vo->status = $status;
				} else {
					if (!$votype->newbyimportallowed && // entrytype does not allow entry creation
                                            (!isset($context['lo']) || ($context['lo']!="entries")) && // if we are not in entry creation page
                                            C::get('lo') != "entities_edition" && // but allow it in entity edit page
                                            !C::get("identity") /*&& // and from reloading page
                                            (C::get('lo') != 'entities_import' && !C::get('reload'))*/
                                            ) {
						return "_error";
					}
					$new=true;
					$vo=$dao->createObject();
					$vo->status=$status ? $status : -1;
				}
			}
			if (isset($dao->rights['protect'])) $vo->protect = !empty($context['protected']) ? 1 : 0;
			if ($votype->flat) {
				$vo->idparent=0; // force the entry to be at root
			} else {
				$vo->idparent= (int) (isset($context['idparent']) ? $context['idparent'] : 0);
			}

			// populate the entry table

			$vo->idtype=$idtype;
			if (empty($context['g_name'])) {
				$vo->g_name=$index_key;
			} else {
				$vo->g_name = $context['g_name'];
			}
			if (empty($context['sortkey'])) {
                $sorkey = $vo->g_name;
            } else {
                $sorkey = $context['sortkey'];
            }
			$vo->sortkey=makeSortKey($sorkey);
			$id=$context['id']=$dao->save($vo);

			// save the class table
			$gdao=DAO::getGenericDAO($class,"identry");
			$gdao->instantiateObject($gvo);
			$context['data']['id']=$context['id'];

			$this->_populateObject($gvo,$context['data']);
			$gvo->identry=$id;

			$this->_moveFiles($id,$this->files_to_move,$gvo);
			$gdao->save($gvo,$new);
		}
		elseif(!$votype->newbyimportallowed && C::get('lo') === 'entities_import' && !C::get('reload'))
                    return '_error'; // document reloading ? if not we don't keep current entries if entrytype dos not allow it

		$this->saveRelations($vo, $context);

		// save the related tables
		if (!empty($context['identity'])) {
			$dao=DAO::getDAO ("relations");
			$rvo=$dao->find("id1='".(int) $context['identity']. "' AND id2='". $id. "' AND nature='E'", "idrelation");
			$new_relation = false;
			if (!$rvo) {
				$dao->instantiateObject ($rvo);
				$rvo->id1=(int)$context['identity'];
				$rvo->id2=$id;
				$rvo->degree=(int)$context['degree'];
				$rvo->nature='E';
				$idrelation=$context['idrelation'] = $dao->save($rvo);
				$new_relation = true;
			} else {
				$idrelation=$context['idrelation'] = $rvo->idrelation;
			}
			// save the entities_class table
			$gdao=DAO::getGenericDAO("entities_".$class,"idrelation");
			$gdao->instantiateObject($gvo);
			$this->_populateObject($gvo,$context['data']);
			$gvo->idrelation=$idrelation;
			$gdao->save($gvo,$new_relation);
		}

		$this->_executeHooks($context, $error, 'post');
		update();
		return "_back";
	}

	/**
	 * Used in editAction to do extra operation after the object has been saved
	 * Here we save relations between entries where sort type is defined as rank
	 *
	 * @param object $vo l'objet qui a été créé
	 * @param array $context le contexte
	 */
	public function saveRelations($vo, &$context)
	{
		$votype = DAO::getDAO ("entrytypes")->getById ($vo->idtype, "sort");

		if('rank' == $votype->sort && $vo->idparent && !empty($context['id']))
		{
			$daorel = DAO::getDAO('relations');
			$vorel = $daorel->createObject();
			$vorel->id1 = $vo->idparent;
			$vorel->id2 = $context['id'];
			$vorel->nature = 'P';
			$vorel->degree = isset($context['degree']) ? ++$context['degree'] : 1;
			$daorel->save($vorel);
			$this->saveRelations(DAO::getDAO('entries')->getById($vo->idparent), $context);
		}
	}

	/**
	 * Changement du rang d'un objet (index seulement)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		return parent::changeRankAction($context, $error, 'idparent,idtype', '');
	}

	/**
	 * Association massive d'entrées/entité
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function massassocAction(&$context, &$error)
	{
		if(!empty($context['getentitieslist']))
		{ // AJAX list entities
			$this->listAction($context, $error);
			echo View::getView()->getIncTpl($context, 'entries_massassoc', '', 'tpl/', 2);
			return '_ajax';
		}

		if(!empty($context['getentrieslist']))
		{ // AJAX list entries
			$this->listAction($context, $error);
			echo View::getView()->getIncTpl($context, 'entries_massassoc', '', 'tpl/', 1);
			return '_ajax';
		}

		if(!empty($context['getrelatedentitieslist']))
		{ // AJAX list related entities
			$this->listAction($context, $error);
			echo View::getView()->getIncTpl($context, 'entries_massassoc', '', 'tpl/', 3);
			return '_ajax';
		}

		if(!empty($context['getrelatedentrieslist']))
		{ // AJAX list related entries
			$this->listAction($context, $error);
			echo View::getView()->getIncTpl($context, 'entries_massassoc', '', 'tpl/', 4);
			return '_ajax';
		}

		if(!empty($context['navigate']))
		{
			echo View::getView()->getIncTpl($context, 'entries_massassoc', '', 'tpl/', 7);
			return '_ajax';
		}

		if(!empty($context['propose']))
		{
			if(empty($context['query']))
				trigger_error('ERROR: missing entry name', E_USER_ERROR);

			function_exists('search') || require 'searchfunc.php';

			$context['responses'] = array();

			$searchEngine = C::get('solr', 'cfg');
			if(empty($searchEngine))
			{
				if(!C::get('searchEngine', 'cfg'))
					trigger_error('ERROR: no search engine has been defined', E_USER_ERROR);

				$response = search($context, '', '');
				if(!empty($response))
				{
					$context['nbresults'] = count($response);
					$context['responses'] = $response;
				}
				unset($response);
			}
			else
			{
				if(empty($searchEngine['instance']) || empty($searchEngine['idField']))
					trigger_error('ERROR: missing configuration for Solr engine', E_USER_ERROR);

				$context['fields'] = (array) $searchEngine['idField'];
				if(!empty($searchEngine['searchField']))
				{
					if(is_array($searchEngine['searchField']))
					{
						$context['f'] = array();
						foreach($searchEngine['searchField'] as $field)
							$context['f'][] = $field;
					}
					else
						$context['f'] = $searchEngine['searchField'];
				}

				$response = solrSearch($context, $searchEngine);
				if(!empty($response))
				{
					if(!empty($response['response']['docs']))
					{
						foreach($response['response']['docs'] as $k => $doc)
						{
							$resp = (array) $doc;
							if('id' !== $searchEngine['idField'])
								$resp['id'] = $resp[$searchEngine['idField']];
							$context['responses'][] = $resp;
						}
					}
					$context['nbresults'] = $response['response']['numFound'];
					unset($response);
				}
			}

			$context['ids'] = array();
			foreach($context['responses'] as $response)
				$context['ids'][] = $response['id'];

			unset($context['responses']);

			echo View::getView()->getIncTpl($context, 'entries_massassoc', '', 'tpl/', 6);
			return '_ajax';
		}

		if(empty($context['edit']))
		{ // not editing
			$this->listAction($context, $error);
			return 'entries_massassoc';
		}

		global $db;

		if(!empty($context['dissociate']))
		{
			if(empty($context['id']))
				trigger_error('ERROR: missing id', E_USER_ERROR);

			$vo = DAO::getDAO('history')->find('id='.(int)$context['id']);
			if(!$vo)
				trigger_error('ERROR: invalid id', E_USER_ERROR);

			$c = unserialize($vo->context);
			foreach($c['identries'] as $identry)
			{
				$identry = explode('_', $identry);
				if(false === strpos($identry[0], '.'))
				{
					$db->Execute(lq("DELETE FROM #_TP_relations WHERE nature='E' AND id2=".(int)$identry[1]." AND id1 ".sql_in_array($c['identities']))) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
				}
				else
				{
					$identry[0] = explode('.', $identry[0]);
					$db->Execute(lq("DELETE FROM `".DATABASE.'_'.$identry[0][0]."`.#_TP_relations_ext WHERE nature='EE' AND id2=".(int)$identry[0][1]." AND id1 ".sql_in_array($c['identities'])." AND site=".$db->quote(C::get('site', 'cfg')))) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
					$db->Execute(lq("DELETE FROM #_TP_relations_ext WHERE nature='E' AND id2=".(int)$identry[0][1]." AND id1 ".sql_in_array($c['identities'])." AND site=".$db->quote($identry[0][0]))) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
				}
			}

			DAO::getDAO('history')->delete($vo);

			update();
			return '_ajax';
		}

		if((empty($context['identities']) && empty($context['entitiesset'])) || (empty($context['identries']) && empty($context['entriesset'])))
			trigger_error('ERROR: missing ids', E_USER_ERROR);

		if(!empty($context['identries']) && is_array($context['identries']))
			$context['identries'] = array_unique($context['identries']);
		if(!empty($context['identities']) && is_array($context['identities']))
			$context['identities'] = array_unique($context['identities']);

		if(!empty($context['associate']))
		{ // need to log this action
			$context['idsentities'] = $context['idsentries'] = array();
			$associate = true;
			$context['toassociate'] = true;
			unset($context['associate']);
		}

		if(!empty($context['entitiesset']) && is_array($context['identries']))
		{ // full reset
			foreach($context['identries'] as $e)
			{
				$e = explode('_', $e);
				if(false !== strpos($e[1], '.'))
				{
					$site = explode('.', $e[1]);
					$db->Execute(lq("DELETE FROM #_TP_relations_ext WHERE nature='E' AND id2=".(int) $e[0])) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
					$db->Execute(lq("DELETE FROM `".DATABASE.'_'.$site[0]."`.#_TP_relations_ext WHERE site=".$db->quote(C::get('site', 'cfg'))." AND nature='EE' AND id2=".(int) $e[0])) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
				}
				else
				{
					$db->Execute(lq("DELETE FROM #_TP_relations WHERE nature='E' AND id2=".(int) $e[0])) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
				}
			}
			if(empty($context['identities']))
			{
				update();
				return '_ajax';
			}
			unset($context['entriesset']);
		}
		elseif(!empty($context['entriesset']) && is_array($context['identities']))
		{ // full reset
			$extEntryTypes = $db->getArray(lq("SELECT name FROM #_TP_tablefields WHERE type='entries' AND name NOT IN (SELECT type FROM #_TP_entrytypes)"));
			if(!empty($extEntryTypes))
			{
				foreach($extEntryTypes as $k => $v)
				{
					$v = explode('.', $v['name']);
					$extEntryTypes[$k] = current($v);
				}
			}

			foreach($context['identities'] as $id)
			{
				$id = (int) $id;
				$db->Execute(lq("DELETE FROM #_TP_relations WHERE nature='E' AND id1=".$id)) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);;
				$db->Execute(lq("DELETE FROM #_TP_relations_ext WHERE nature='E' AND id1=".$id)) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);;
				foreach($extEntryTypes as $site)
				{
					$db->Execute(lq("DELETE FROM `".DATABASE.'_'.$site."`.#_TP_relations_ext WHERE site=".$db->quote(C::get('site', 'cfg'))." AND nature='EE' AND id1=".$id))
						 or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
				}
			}
			if(empty($context['identries']))
			{
				update();
				return '_ajax';
			}
			unset($context['entriesset']);
		}

		if(!empty($context['identries']) && is_array($context['identries']))
		{
			$localcontext = $context;
			$entries = $context['identries'];
			foreach($entries as $entry)
			{
				$entry = explode('_', $entry);
				$localcontext['identries'] = current($entry);
				$localcontext['idtype'] = end($entry);
				$this->massAssocAction($localcontext, $error);
				if(!empty($context['toassociate']))
				{
					$context['idsentities'] += $localcontext['idsentities'];
					$context['idsentries'] += $localcontext['idsentries'];
				}
			}
		}
		else
		{
			if(empty($context['idtype']))
				trigger_error('ERROR: missing idtype', E_USER_ERROR);

			$identry = $context['identries'];
			$daoEntries = DAO::getDAO('entries');
			$daoEntrytypes = DAO::getDAO('entrytypes');

			$idtype = $context['idtype'];
			if(false !== strpos($idtype, '.'))
			{ // site.idtype
				$typeName = $idtype;
				$idtype = explode('.', $idtype);
				$site_ext = current($idtype); // grab the site
				$idtype = (int) end($idtype); // grab the idtype
				$db->SelectDB(DATABASE.'_'.$site_ext) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
				$reltable = lq('#_TP_relations_ext');
				$where = ' AND externalallowed=1';
			}
			else
			{
				$site_ext = false;
				$reltable = lq('#_TP_relations');
				$where = '';
			}

			$type = $daoEntrytypes->find('id='.$idtype.$where, 'type'); // check entrytype is allowed to be shared
			if(!$type) trigger_error('ERROR: invalid idtype '.$idtype.$where, E_USER_ERROR);
			if(!isset($typeName)) $typeName = $type->type;

			$entry = $daoEntries->find('id='.$identry, 'id'); // check entry exists
			if(!$entry) trigger_error('ERROR: invalid entry', E_USER_ERROR);

			if(!empty($context['toassociate']))
				$context['idsentries'][] = $context['idtype'].'_'.$identry;

			usecurrentdb();

			foreach($context['identities'] as $id)
			{
				$id = (int) $id;

				$authorized = $db->GetOne(lq('
				SELECT COUNT(tf.id)
					FROM #_TP_entities e
					JOIN #_TP_types t ON (e.idtype=t.id)
					JOIN #_TP_tablefields tf ON (t.class=tf.class)
					WHERE e.id='.$id.' AND tf.type="entries" AND tf.name="'.$typeName.'"'));

				if($authorized > 0)
				{
					$exists = $db->getOne(lq('SELECT idrelation FROM '.$reltable.' WHERE id1='.$id.' AND nature="E" AND id2='.$identry));

					if(!$exists)
					{
						$degree = (int)$db->GetOne(lq('
					SELECT MAX(degree)
						FROM '.$reltable.'
						WHERE id1='.$id.' AND nature="E"'));

						$query = '
					REPLACE INTO '.$reltable.' ';
						if($site_ext)
						{
							$query .= "(id1,id2,nature,degree,site) VALUES ({$id}, {$identry}, 'E', ".++$degree.", ".$db->quote($site_ext).")";
							if(!C::get('db_no_intrusion.'.$site_ext, 'cfg'))
                                                            $db->Execute(lq("REPLACE INTO `".DATABASE.'_'.$site_ext."`.#_TP_relations_ext (id1,id2,nature,degree,site) VALUES ({$id}, {$identry}, 'EE', ".$degree.", ".$db->quote(C::get('site', 'cfg')).")")) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
						}
						else
						{
							$query .= "(id1,id2,nature,degree) VALUES ({$id}, {$identry}, 'E', ".++$degree.")";
						}

						$db->Execute($query) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
						$db->Execute(lq("UPDATE #_TP_entities set upd=NOW() WHERE id=".$id)) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
						if(!empty($context['toassociate']))
							$context['idsentities'][] = $id;
					}
				}

				$childs = $db->Execute(lq('
				SELECT DISTINCT(id2)
					FROM #_TP_relations r
					JOIN #_TP_entities e ON (e.id=r.id2)
					JOIN #_TP_types t ON (e.idtype=t.id)
					JOIN #_TP_tablefields tf ON (t.class=tf.class)
					WHERE r.nature="P" AND r.id1='.$id.' AND tf.type="entries" AND tf.name="'.$typeName.'"'))
						or trigger_error('SQLERROR: '.$db->ErrorMsg(), E_USER_ERROR);

				while(!$childs->EOF)
				{
					$childId = $childs->fields['id2'];

					$authorized = $db->GetOne(lq('
				SELECT COUNT(tf.id)
					FROM #_TP_entities e
					JOIN #_TP_types t ON (e.idtype=t.id)
					JOIN #_TP_tablefields tf ON (t.class=tf.class)
					WHERE e.id='.$childId.' AND tf.type="entries" AND tf.name="'.$typeName.'"'));

					if(!$authorized)
					{
						$childs->MoveNext();
						continue;
					}

					$exists = $db->getOne(lq('SELECT idrelation FROM '.$reltable.' WHERE id1='.$childId.' AND nature="E" AND id2='.$identry));

					if($exists)
					{
						$childs->MoveNext();
						continue;
					}

				$degree = (int)$db->GetOne('
			SELECT MAX(degree)
				FROM '.$reltable.'
				WHERE id1='.$childId.' AND nature="E"');

					$query = '
				REPLACE INTO '.$reltable.' ';
					if($site_ext)
					{
						$query .= "(id1,id2,nature,degree,site) VALUES ({$childId}, {$identry}, 'E', ".++$degree.", '{$site_ext}')";
						if(!C::get('db_no_intrusion.'.$site_ext, 'cfg'))
                                                    $db->Execute(lq("REPLACE INTO `".DATABASE.'_'.$site_ext."`.#_TP_relations_ext (id1,id2,nature,degree,site) VALUES ({$childId}, {$identry}, 'EE', ".$degree.", ".$db->quote(C::get('site', 'cfg')).")")) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
					}
					else
					{
						$query .= "(id1,id2,nature,degree) VALUES ({$childId}, {$identry}, 'E', ".++$degree.")";
					}

					$db->Execute($query) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
					$db->Execute(lq("UPDATE #_TP_entities set upd=NOW() WHERE id=".$childId)) or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);
					if(!empty($context['toassociate']))
						$context['idsentities'][] = $childId;

					$childs->MoveNext();
				}
				$childs->Close();
			}
		}

		update();

		if(isset($associate))
		{
			$context['idsentities'] = array_unique($context['idsentities']);
			$context['idsentries'] = array_unique($context['idsentries']);

			if(!empty($context['idsentries']) && !empty($context['idsentities']))
			{
				$db->Execute(lq('INSERT INTO #_TP_history SET nature="A", context='.$db->quote(serialize(array('identities' => $context['idsentities'], 'identries' => $context['idsentries'])))))
					or trigger_error('SQL ERROR: '.$db->ErrorMsg(), E_USER_ERROR);

				$id = (int)$db->Insert_ID();
				$localcontext = array_merge($context, $db->GetRow(lq("SELECT * FROM #_TP_history WHERE id=".$id)));
				echo View::getView()->getIncTpl($localcontext, 'entries_massassoc', '', 'tpl/', 5);
			}
		}

		return '_ajax';
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
			$context['type']['sort'] = isset($context['type']['sort']) ? $context['type']['sort'] : null;
			$context['id'] = isset($context['id']) ? $context['id'] : null;
			$context['idtype'] = isset($context['idtype']) ? $context['idtype'] : null;
			do {
				$result=$db->execute (lq ("
				SELECT *
					FROM #_TP_entries
					WHERE idtype='".$context['idtype']."' AND id!='".$context['id']."' AND idparent ".sql_in_array ($ids). "
					AND ABS(status) >= 21 ORDER BY ". $context['type']['sort']))
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

			if ($this->idrelation) {
				$gdao=DAO::getGenericDAO("entities_".$class,"idrelation");
				$gdao->deleteObject($this->idrelation);
			}
		}

		if ($this->idrelation) {
			$dao=DAO::getDAO ('relations');
			$dao->delete ('idrelation '. sql_in_array ($this->idrelation));
		}

		is_array($id) || $id = (array)$id;

		foreach($id as $i)
		{
			$ids = $db->execute(lq('SELECT * FROM #_TP_relations_ext WHERE id1='.$i.' AND nature="EE"'))
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			if(!$ids) return;

			while(!$ids->EOF)
			{
				$db->SelectDB(DATABASE.'_'.$ids->fields['site']) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				$db->execute(lq('DELETE FROM #_TP_relations_ext WHERE id2='.$i.' AND nature="E" AND site='.$db->quote(C::get('site', 'cfg'))))
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

				$ids->MoveNext();
			}

			usecurrentdb();
			$db->execute(lq('DELETE FROM #_TP_relations_ext WHERE id1='.$i.' AND nature="EE"'))
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
	}

} // class
