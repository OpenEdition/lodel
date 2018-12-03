<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe Logic
 */

/**
 * Classe des logiques métiers.
 *
 * <p>Cette classe définit les actions de base des différentes logiques métiers utilisées dans Lodel.
 * Elle est la classe 'mère' des logiques métiers se trouvant dans le répertoire /logic.
 * Elles est aussi la liaison entre la couche d'abstraction de la base de données (DAO/VO) et la
 * vue.</p>
 *
 */
class Logic
{
	/**#@+
	 * @access private
	 */
	/**
	 * Nom de la table SQL centrale et de la classe.
	 *
	 * Table and class name of the central table
	 * @var string
	 */
	protected $maintable;

	/**
	 * critère SQL du rang
	 * Give the SQL criteria which make a group from the ranking point of view.
	 * @var string
	 */
	protected $rankcriteria;
	/**#@-*/

	static protected $_logics = array();

	/**
	 * Constructeur de la classe.
	 *
	 * Positionne simplement le nom de la table principale.
	 * @param string $maintable le nom de la table principale.
	 */
	public function __construct($maintable)
	{
		$this->maintable = $maintable;
	}

	/**
	* Logic factory
	* @param string $table the name of the logic to call
	*/
	public static function getLogic($table)
	{
		if (isset(self::$_logics[$table])) {
			return self::$_logics[$table]; // cache
		}

		$logicclass = $table. 'Logic';
		if(!class_exists($logicclass))
		{
			$file = C::get('sharedir', 'cfg').'/plugins/custom/'.$table.'/logic.php';
			if(!file_exists($file))
				trigger_error('ERROR: unknown logic', E_USER_ERROR);
			include $file;
			if(!class_exists($logicclass,false) || !is_subclass_of($logicclass, 'Logic'))
				trigger_error('ERROR: cannot find the class, or the logic plugin file does not extend the Logic OR GenericLogic class', E_USER_ERROR);
		}
		self::$_logics[$table] = new $logicclass;

		return self::$_logics[$table];
	}

	/**
	 * Implémentation par défaut de l'action permettant d'appeler l'affichage d'un objet.
	 *
	 * Cette fonction récupère les données de l'objet <em>via</em> la DAO de l'objet. Ensuite elle
	 * met ces données dans le context (utilisation de la fonction privée _populateContext())
	 *
	 * view an object Action
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function viewAction(&$context, &$error)
	{
		if ($error) return; // nothing to do if it is an error.
		if (empty($context['id'])) return "_ok"; // just add a new Object

		$id = $context['id'];
		$vo  = $this->_getMainTableDAO()->getById($id);
		if (!$vo) //erreur critique
			trigger_error("ERROR: can't find object $id in the table ". $this->maintable, E_USER_ERROR);
		if(isset($vo->passwd)) $vo->passwd = null; // clean the passwd !
		$this->_populateContext($vo, $context); //rempli le context

		if('tablefields' == $this->maintable && !empty($context['mask'])) {
			$context['mask'] = unserialize(html_entity_decode(stripslashes($context['mask'])));
		}
		//ajout d'informations supplémentaires dans le contexte (éventuellement)
		$ret=$this->_populateContextRelatedTables($vo, $context);

		return $ret ? $ret : "_ok";
	}

	/**
	 * Implémentation par défaut de l'action de copie d'un objet.
	 * Récupère l'objet que l'on veut créer et le copie en ajoutant un prefixe devant.
	 *
	 * copy an object Action
	 *
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function copyAction(&$context, &$error)
	{
		$ret = $this->viewAction($context, $error);
		$copyof = getlodeltextcontents("copyof", "common");
		if (isset($context['name'])) {
			$context['name'] = $copyof. "_". $context['name'];
		} elseif (isset($context['type']) && !is_array($context['type'])) {
			$context['type'] = $copyof. "_". $context['type'];
		}
		if (isset($context['title'])) {
			$context['title'] = $copyof. " ". $context['title'];
		} elseif (isset($context['username'])) {
			$context['username'] = $copyof. "_". $context['username'];
		}
		unset($context['id']);
		return $ret;
	}

	/**
	 * Implémenation de l'action d'ajout ou d'édition d'un objet.
	 *
	 * <p>Cette fonction crée un nouvel objet ou édite un objet existant. Dans un premier temps les
	 * données sont validées (suivant leur type) puis elles sont rentrées dans la base de données <em>via</em> la DAO associée à l'objet.
	 * Utilise _prepareEdit() pour effectuer des opérations de préparation avant l'édition de l'objet puis _populateContext() pour ajouter des informations supplémentaires au context. Et enfin _saveRelatedTables() pour sauver d'éventuelles informations dans des tables liées.
	 * </p>

	 * add/edit Action
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @param boolean $clean false si on ne doit pas nettoyer les données (par défaut à false).
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function editAction(&$context, &$error, $clean = false)
	{
		if ($clean != 'CLEAN') {      // validate the forms data
			if (!$this->validateFields($context, $error)) {
				return '_error';
			}
		}

		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$this->_prepareEdit($dao, $context);
		// create or edit
		if (!empty($context['id'])) {
			$dao->instantiateObject($vo);
			$vo->id = $context['id'];
		} else {
			$create = true;
			$vo = $dao->createObject();
		}
		if (isset($dao->rights['protect'])) {
			$vo->protect = isset($context['protected']) && $context['protected'] ? 1 : 0;
		}
		// put the context into
		$this->_populateObject($vo, $context);
		if (!$dao->save($vo)) trigger_error("You don't have the rights to modify or create this object", E_USER_ERROR);
		$ret = $this->_saveRelatedTables($vo, $context);
		if(isset($create) && isset($context['lo']) && ('users' == $context['lo'] || 'restricted_users' == $context['lo'])) {
			$this->_sendPrivateInformation($context);
		}
		update();
		return $ret ? $ret : "_back";
	}

	/**
	 * Implémentation par défaut de l'action qui permet de changer le rang d'un objet.
	 *
	 * Cette action modifie la rang (rank) d'un objet. Peut-être restreinte à un status particulier
	 * et à un étage particulier (groupe).
	 *
	 * Change rank action
	 * Default implementation
	 *
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @param string $groupfields champ de groupe. Utilisé pour limité le changement de rang à un étage. Par défaut vide.
	 * @param string $status utilisé pour changer le rang d'objets ayant un status particulier. il s'agit d'une condition. Par défaut est : status>0
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		if(empty($context['id']))
			trigger_error('ERROR: missing id in Logic::changeRankAction', E_USER_ERROR);

		$criterias = array();
		$id = $context['id'];
		if (!empty($groupfields)) {
			$vo  = $this->_getMainTableDAO()->getById($id, $groupfields);
			foreach (explode(",", $groupfields) as $field) {
				$criterias[] = $field. "='". $vo->$field. "'";
			}
		}
		if ($status) $criterias[] = $status;
		$criteria = join(" AND ", $criterias);
		$this->_changeRank($id, isset($context['dir']) ? $context['dir'] : '', $criteria);

		update();
		return '_back';
	}

	/**
	 * Implémentation par défaut de l'action qui permet de supprimer un objet.
	 * <p>Cette action vérifie tout d'abord que l'objet peut-être supprimé puis prépare
	 * la suppression (fonction _prepareDelete()) et enfin utilise la DAO pour supprimer l'objet</p>
	 * Delete
	 * Default implementation
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function deleteAction(&$context, &$error)
	{
		global $db;
		if(empty($context['id']))
			trigger_error('ERROR: missing id in Logic::deleteAction', E_USER_ERROR);

		$id = $context['id'];

		if ($this->isdeletelocked($id)) {
			trigger_error("This object is locked for deletion. Please report the bug", E_USER_ERROR);
		}
		$dao = $this->_getMainTableDAO();
		$this->_prepareDelete($dao, $context);
		$dao->deleteObject($id);

		$ret=$this->_deleteRelatedTables($id);
		update();
		return $ret ? $ret : '_back';
	}

	/**
	 * Implémentation par défaut de la fonction right
	 *
	 * Cette fonction permet de retourner les droits pour un niveau d'accès particulier
	 *
	 * Return the right for a given kind of access
	 * @param string $access le niveau d'accès
	 * @return integer entier représentant le droit pour l'accès demandé.
	 */
	public function rights($access)
	{
		$dao = $this->_getMainTableDAO();
		return isset($dao->rights[$access]) ? $dao->rights[$access] : '';
	}

	/**
	 * Implémentation par défaut de isdeletelocked()
	 *
	 * Indique si un objet donné est supprimable pour l'utilisateur courant.
	 *
	 * Say whether an object (given by its id and status if possible) is deletable by the current user or not
	 * @param integer $id l'identifiant numérique de l'objet
	 * @param integer $status status de l'objet. Par défaut vaut 0.
	 * @return boolean un booléen indiquant si l'objet peut être supprimé.
	 */
	public function isdeletelocked($id, $status=0)
	{
		// basic
		if (is_numeric($id)) {
			$id = (int)$id;
			$criteria = "id='". $id. "'";
			$nbexpected = 1;
		} else {
			$id = array_map('intval', $id);
			$criteria = "id IN ('". join("','", $id). "')";
			$nbexpected = count($id);
		}
		$dao = $this->_getMainTableDAO();
		$nbreal = $dao->count($criteria. " ". $dao->rightsCriteria("write"));
		return $nbexpected != $nbreal;
	}

	//! Private or protected from this point
	/**#@+
	 * @access private
	 */

	protected function _getMainTableDAO()
	{
		return DAO::getDAO($this->maintable);
	}


	/**
	 * Change the rank of an Object
	 */
	protected function _changeRank($id, $dir, $criteria)
	{
		global $db;
		if (is_numeric($dir)) {
			$dir = $dir>0 ? 1 : -1;
		} else {
			$dir = $dir == "up" ? -1 : 1;
 		}

		$dao = $this->_getMainTableDAO();
		$vos = $dao->findMany($criteria, "rank", "id, rank");
		$rankedvos = array();
		$rank = 1;
		foreach($vos as $vo)
		{
			if($vo->id == $id)
			{
				if(!isset($rankedvos[$rank + $dir]))
					$rankedvos[$rank + $dir] = $vo;
				else
				{
					$rankedvos[$rank+$dir+1] = $rankedvos[$rank + $dir];
					$rankedvos[$rank+$dir] = $vo;
				}
				$rank -= $dir;
			}
			elseif(!isset($rankedvos[$rank]))
				$rankedvos[$rank] = $vo;
			elseif(!isset($rankedvos[$rank+$dir]))
				$rankedvos[$rank+$dir] = $vo;
			else $rankedvos[$rank+2*$dir] = $vo;

			++$rank;
		}

		if(count($rankedvos) !== count($vos))
			trigger_error('ERROR: Logic::_changeRank error, please report this bug to lodel@lodel.org', E_USER_ERROR);

		foreach($rankedvos as $k=>$vo)
		{
			$vo->rank = $k;
			$dao->save($vo);
		}

		return;

		$newrank = $dir>0 ? 1 : $count;
		for ($i = 0 ; $i < $count ; $i++) {
			if ($vos[$i]->id == $id) {
				// exchange with the next if it exists
				if (!isset($vos[$i+1])) {
					break;
				}
				$rank -= $dir;
			}
			elseif(!isset($rankedvos[$rank]))
				$rankedvos[$rank] = $vo;
			elseif(!isset($rankedvos[$rank+$dir]))
				$rankedvos[$rank+$dir] = $vo;
			else $rankedvos[$rank+2*$dir] = $vo;

			++$rank;
		}

		if(count($rankedvos) !== count($vos))
			trigger_error('ERROR: Logic::_changeRank error, please report this bug to lodel@lodel.org', E_USER_ERROR);

		foreach($rankedvos as $k=>$vo)
		{
			$vo->rank = $k;
			$dao->save($vo);
		}

		return;

// 		$desc = $dir>0 ? "" : "DESC";
//
// 		$dao = $this->_getMainTableDAO();
// 		$vos = $dao->findMany($criteria, "rank $desc, id $desc", "id, rank");
//
// 		$count = count($vos);
// 		$newrank = $dir>0 ? 1 : $count;
// 		for ($i = 0 ; $i < $count ; $i++) {
// 			if ($vos[$i]->id == $id) {
// 				// exchange with the next if it exists
// 				if (!isset($vos[$i+1])) {
// 					break;
// 				}
// 				$vos[$i+1]->rank = $newrank;
// 				$dao->save($vos[$i+1]);
// 				$newrank+= $dir;
// 			}
// 			if ($vos[$i]->rank != $newrank) { // rebuild the rank if necessary
// 				$vos[$i]->rank = $newrank;
// 				$dao->save($vos[$i]);
// 			}
// 			if ($vos[$i]->id == $id) {
// 				++$i;
// 			}
// 			$newrank+= $dir;
// 		}
	}

	/**
	 * Validated the public fields and the unicity.
	 * @return return an array containing the error and warning, null otherwise.
	 */
	public function validateFields(&$context, &$error)
	{
		global $db;

		// Noms des logics qui sont traitées par des formulaires dans lesquels il y a des champs de type file ou image, et qui ont besoin d'un traitement particulier pour ces champs (i.e. pas des docs annexes)
		// Ne concerne que la partie admin de l'interface, ajouté pour les icônes liées aux classes et aux types
		// Cf. par ex. les formulaires edit_types.html ou edit_classes.html
		$adminFormLogics = array ('classes', 'entrytypes', 'persontypes', 'types');
		function_exists('validfield') || include "validfunc.php";
		$publicfields = $this->_publicfields();
		foreach ($publicfields as $field => $fielddescr) {
			list($type, $condition) = $fielddescr;
			if ($condition == "+" && $type != "boolean" && // boolean false are not transfered by HTTP/POST
					(
						!isset($context[$field]) ||   // not set
						$context[$field] === ""  // or empty string
					)) {
				$error[$field]="+";
			} elseif ($type == "passwd" && !trim($context[$field]) && $context['id']>0) {
				// passwd can be empty only if $context[id] exists... it is a little hack but.
				unset($context[$field]); // remove it
			} else {
				if (($type == "image" || $type == "file") && in_array($this->maintable, $adminFormLogics)){
					// traitement particulier des champs de type file et images dans les formulaires de la partie admin

					// répertoire de destination pour les fichiers et les images : array ($field, $répertoire)
					$directory =array ('icon' => 'lodel/icons');
					$valid = validfield($context[$field], $type, "",$field, "", $directory[$field]);
				} else {
					$valid = validfield($context[$field], $type, "",$field, '', '', $context);
				}
				if ($valid === false) {
					trigger_error("ERROR: \"$type\" can not be validated in logic.php", E_USER_ERROR);
				}
				if (is_string($valid)) {
					$error[$field]=$valid;
				}
			}
			if('tablefields' == $this->maintable && 'mask' == $field && 'entities' == $context['classtype']) {
				if(!empty($context['mask']['user']))
				{
					$this->_makeMask($context, $error);
					if(!isset($error['mask'])) $context['mask'] = addslashes(serialize($context['mask']));
				}
				else
				{
					$context['mask'] = '';
				}
			}
		}
		if ($error) {
			return false;
		}

		$ufields = $this->_uniqueFields();
		foreach ($ufields as $fields) { // all the unique set of fields
			$conditions=array();

			foreach ($fields as $field) { // set of fields which has to be unique.
				if(empty($context[$field]))
					break 2;
				$conditions[] = $field. "=". $db->quote($context[$field]);
			}
			// check
			$ret = $db->getOne("SELECT 1 FROM ". lq("#_TP_". $this->maintable). " WHERE status>-64 AND id!='". $context['id']. "' AND ". join(" AND ", $conditions));
			if ($db->errorno()) {
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if ($ret) {
				$error[$fields[0]] = "1"; // report the error on the first field
			}
		}

		return empty($error);
	}

	/**
	 * Crée le masque (regexp) en fonction du masque rentré dans l'interface
	 *
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 */
	protected function _makeMask(&$context, &$error)
	{
		if(empty($context['mask']['user'])) return;

		defined('PONCTUATION') || include 'utf8.php';

		$mask = $context['mask']['user'];
		if(isset($context['mask_regexp'])) {
			// disable eval options for more security
		    $mask = $context['mask']['user'] = preg_replace_callback("/^(.)(.*)(\\1)([msieDASuUXxJ]*)?$/s",
		        function ($text){return($text[1].$text[2].$text[1].str_replace('e','',"$text[4]"));},$mask);
			//$mask = $context['mask']['user'] = preg_replace('/^(.)(.*)(\\1)([msieDASuUXxJ]*)?$/e', "'\\1'.\\2.'\\1'.str_replace('e', '', \"\\4\")", $mask);
			if(FALSE === @preg_match($mask, 'just a test for user regexp')) {
				$error['mask'] = 'mask: '.getlodeltextcontents('mask_bad_regexp', 'common');
				return;
			} elseif(PREG_NO_ERROR !== preg_last_error()) {
				$error['mask'] = 'mask: '.getlodeltextcontents('mask_bad_regexp', 'common').' : PCRE : '.preg_last_error();
				return;
			}
			$context['mask']['lodel'] = $mask;
			$context['mask']['model'] = 'regexp';
		} elseif(isset($context['mask_reasonable'])) {
			$regexp = null;
			$ponct = false;
			$chars = false;
			$num = false;
			$unknown = false;
			$spaces = false;
			while($strlen = mb_strlen($mask, 'UTF-8')) {
				$c = mb_substr($mask,0,1,"UTF-8");
				$mask = mb_substr($mask,1,$strlen,"UTF-8");
				if($c >= '0' && $c <= '9') {
					$regexp .= (false === $num ? '[0-9]+' : '');
					$spaces = false;
					$num = true;
					$chars = false;
					$unknown = false;
					$ponct = false;
				} elseif( ($c >= 'a' && $c <= 'z') ||
					($c >= 'A' && $c <= 'Z')  ||
					(preg_match("/^[".ACCENTS."]$/u", $c)>0) ) {
					$regexp .= (false === $chars ? '[a-zA-Z'.ACCENTS.']+' : '');
					$spaces = false;
					$num = false;
					$chars = true;
					$unknown = false;
					$ponct = false;
				} elseif(preg_match("/^[".PONCTUATION."]$/u", $c)>0) {
					$regexp .= (false === $ponct ? '['.PONCTUATION.']+' : '');
					$spaces = false;
					$num = false;
					$chars = false;
					$unknown = false;
					$ponct = true;
				} elseif(preg_match("/^\s$/u", $c)>0) {
					$regexp .= (false === $spaces ? '\s+' : '');
					$spaces = true;
					$num = false;
					$chars = false;
					$unknown = false;
					$ponct = false;
				}  else {
					// unknown character ?
					$regexp .= (false === $unknown ? '.+?' : '');
					$spaces = false;
					$num = false;
					$chars = false;
					$unknown = true;
					$ponct = false;
				}
			}
			if(!is_null($regexp)) {
				$regexp = '/^'.$regexp.'$/';
				$context['mask']['lodel'] = $regexp;
				$context['mask']['model'] = 'reasonable';
			} else {
				$error['mask'] = 'mask: unknown error';
			}
		} else {
			// let's find masks like %something% and decode them
			preg_match_all("/(?<!\\\\)(?:%)(?!\s)(.*?)((?<!\\\\)(?:[\?\+\*]|\{[\d,]+\}))?(?<!(?:\\\\|\s))(?:%)/u", $mask, $m);
			$masks = array();
			foreach($m[0] as $k=>$v) {
				$content = $m[1][$k];
				$chars = 0;
				$num = 0;
				$spaces = 0;
				$pmask = '';
				while($strlen = mb_strlen($content, 'UTF-8')) {
					$c = mb_substr($content,0,1,"UTF-8");
					$content = mb_substr($content,1,$strlen,"UTF-8");
					if($c >= '0' && $c <= '9') {
						if($chars === 0 && $num === 0) {
							$pmask .= '[';
						}
						if(0 === $num) {
							$pmask .= '0-9';
						}
						$spaces = 0;
						$num++;
					} elseif(($c >= 'a' && $c <= 'z') ||
						($c >= 'A' && $c <= 'Z')  ||
						(preg_match("/^[".ACCENTS."]$/u", $c)>0) ) {
						if($num === 0 && $chars === 0) {
							$pmask .= '[';
						}
						if(0 === $chars) {
							$pmask .= 'a-zA-Z'.ACCENTS.'';
						}
						$spaces = 0;
						$chars++;
					} elseif(preg_match("/^\s$/u", $c)>0) {
						if($chars > 0 || $num > 0) {
							$pmask .= ($chars > 1 || $num > 1 ? ']+' : ']');
						}
						if(0 === $spaces) {
							$pmask .= '\s';
						} elseif(1 === $spaces) {
							$pmask .= '+';
						}
						$spaces++;
						$num = 0;
						$chars = 0;
					} else {
						if($chars > 0 || $num > 0) {
							$pmask .= ($chars > 1 || $num > 1 ? ']+' : ']');
						}
						$pmask .= preg_quote($c, '/');
						$spaces = 0;
						$num = 0;
						$chars = 0;
					}

				}
				if($chars > 0 || $num > 0) {
					$pmask .= ($chars > 1 || $num > 1 ? ']+' : ']');
				}
				if($m[2][$k]) $pmask = '('.$pmask.')'.$m[2][$k];
				$masks[$v] = $pmask;
			}
			$mask = preg_quote($mask, '/');
			foreach($masks as $k=>$m)
				$mask = str_replace(preg_quote($k, '/'), $m, $mask);
			$context['mask']['lodel'] = '/^'.$mask.'$/';
			$context['mask']['model'] = 'traditional';
		}
	}

	/**
	 * Return the public fields
	 * @access public
	 */
	public function getPublicFields()
	{
		return $this->_publicfields();
	}

	/**
	 * Return the unique fields
	 * @access protected
	 */
	protected function _publicfields()
	{
		trigger_error("call to abstract publicfields", E_USER_ERROR);
		return array();
	}

	/**
	 * Return the unique fields
	 * @access public
	 */
	public function getUniqueFields()
	{
		return $this->_uniqueFields();
	}

	/**
	 * Return the unique fields
	 * @access protected
	 */
	protected function _uniqueFields()
	{
		trigger_error("call to abstract uniquefields", E_USER_ERROR);
		return array();
	}

	/**
	 * Populate the object from the context. Only the public fields are inputted.
	 * @private
	 */
	protected function _populateObject($vo, &$context)
	{
		$publicfields = $this->_publicfields();
		foreach ($publicfields as $field => $fielddescr) {
			$vo->$field = isset($context[$field]) ? $context[$field] : null;
		}
	}

	/**
	 * Populate the context from the object. All fields are outputted.
	 * @protected
	 */
	protected function _populateContext($vo, &$context)
	{
		$view = (isset($context['do']) && $context['do'] == 'view');
		foreach ($vo as $k=>$v) {
			//Added by Jean - Be carefull using it
			//if value is a string and we want to view it (or edit it in a form,
			//open a form is a view action) then we htmlize it
			$context[$k] = is_string($v) && $view ? htmlspecialchars($v) : $v;
		}
	}

	/**
	 * Used in editAction to do extra operation before the object is saved.
	 * Usually it gather information used after in _saveRelatedTables
	 */
	protected function _prepareEdit($dao, &$context) {}

	/**
	 * Used in deleteAction to do extra operation before the object is saved.
	 * Usually it gather information used after in _deleteRelatedTables
	 */
	protected function _prepareDelete($dao, &$context) {}

	/**
	 * Used in editAction to do extra operation after the object has been saved
	 */
	protected function _saveRelatedTables($vo, &$context) {}

	/**
	 * Used in deleteAction to do extra operation after the object has been deleted
	 */
	protected function _deleteRelatedTables($id) {}

	/**
	 * Used in viewAction to do extra populate in the context
	 */
	protected function _populateContextRelatedTables($vo, &$context) {}

	/**
	 * process of particular type of fields
	 * @param string $type the type of the field
	 * @param array $context the context
	 * @param int $status the status; by default 0 if no status changed
	 */
	protected function _processSpecialFields($type, &$context, $status = 0)
	{
		global $db;

		if(empty($context['id']))
			trigger_error('ERROR: missing id in Logic::_processSpecialFields', E_USER_ERROR);

		$vo = DAO::getDAO('entities')->getById($context['id'], 'id, idtype');
		if(!$vo)
			trigger_error('ERROR: invalid id', E_USER_ERROR);

		$votype = DAO::getDAO ("types")->getById ($vo->idtype, 'class');
		if(!$votype)
			trigger_error('ERROR: invalid idtype', E_USER_ERROR);

		$class = $votype->class;
		unset($vo,$votype);

		$fields = DAO::getDAO("tablefields")->findMany ("(class='". $class. "' OR class='entities_". $class. "') AND type='". $type. "' AND status>0 ", "",    "name, type, class");
		if($fields && $type == 'history') {
			$updatecrit = "";
			foreach ($fields as $field) {
				$value = "";
				$this->_calculateHistoryField ($value, $context, $status);
				if (isset ($context['data'][$field->name])) { //if a value for this field is in the context, use it (to allow user to modify the field)
					$updatecrit .= ($updatecrit ? "," : ""). $field->name. "=CONCAT(". $db->Quote($value). "," .$db->Quote("\n".$context['data'][$field->name]) . ")";
				}
				else {
					$updatecrit .= ($updatecrit ? "," : ""). $field->name. "=CONCAT(".$db->Quote($value). ",".$field->name . ")";
				}
			}
			$db->execute (lq ("UPDATE #_TP_$class SET $updatecrit WHERE identity='". $context['id'].  "'"));
		}
	}

	/**
	 * special processing for particular types of field
	 * @param string $value the current value of the field
	 * @param array $context the current context
	 */
	protected function _calculateHistoryField(&$value, &$context, $status = 0)
	{
		if(empty($context['id'])) return;

		$dao = DAO::getDAO('users');
		if(C::get('adminlodel', 'lodeluser')) {
			usemaindb();
			$vo = $dao->getById(C::get('id', 'lodeluser'));
			usecurrentdb();
		}
		else {
			$vo = $dao->getById(C::get('id', 'lodeluser'));
		}
		//edition or change of status
		switch($status) {
			case 0:
			case -2:
				$line = getlodeltextcontents('editedby', 'common');
				break;
			case 1:
				$line = getlodeltextcontents('publishedby', 'common');
				break;
			case -1:
				$line = getlodeltextcontents('unpublishedby', 'common');
				break;
			case -4:
				$line = getlodeltextcontents('refusedby', 'common');
				break;
			case 8:
				$line = getlodeltextcontents('protectedby', 'common');
				break;
			case -8:
				$line = getlodeltextcontents('draftedby','common');
				break;
			default: //creation
				$line = getlodeltextcontents('createdby', 'common');
		}
		$line .= " ". (isset($vo->name) ? $vo->name : (isset($vo->username) ? $vo->username : C::get('lastname', 'lodeluser')));
		$line .= " ".getlodeltextcontents('on', 'common'). " ". date('d/m/Y H:i');
		$value = $line."\n";
		unset($line);
	}

	/**
	 * Vérification de la valeur du statut (champ status dans les tables)
	 * @param int $status la valeur du statut à insérer dans la base
	 * @return bool true si le paramètre $status correspond à une valeur autorisée, sinon déclenche une erreur php
	 */
	protected function _isAuthorizedStatus($status)
	{
		switch ($this->maintable) {
			case 'entities' : // -4: refusé, -3: proposé, -2: en édition
				$this->_authorizedStatus = C::get('temporary', 'lodeluser') ? array(-3, -1) : array(-64, -8, -4, -3, -2, -1, 1, 8, 17, 24);
				break;
			case 'persons' :
			case 'entries' : // 1: publié, 21: indépubliable, 32: protégé
				$this->_authorizedStatus = array(-64, -32, -1, 1, 21, 32);
				break;
			case 'texts' :
				$this->_authorizedStatus = array(-1, 1, 2);
				break;
			default : trigger_error("ERROR: Cannot find authorized status", E_USER_ERROR);

		}
		if (in_array($status, $this->_authorizedStatus) || $status == 0) {
			return true;
		} else {
			trigger_error("ERROR: Invalid status ! ", E_USER_ERROR);
		}
	}
	/**#@-*/

	/**
	 * Execution des hooks
	 *
	 * Cette méthode est appelée lors de l'édition d'une entitée afin d'exécuter les différents hooks
	 * définis pour chaque champ de l'entité.
	 *
	 */
	protected function _executeHooks(&$context, &$error, $prefix='pre'){
		global $db;
		require_once "hookfunc.php";

		if(!isset($context['class'])){
			$context['class'] = $db->GetOne(lq("SELECT class FROM #_TP_entities LEFT JOIN #_TP_types ON (#_TP_entities.idtype=#_TP_types.id) WHERE #_TP_entities.id = " . $db->Quote($context['id'])));
		}

		$fields = DAO::getDAO("tablefields")->findMany("class='". $context['class']. "' AND status>0 AND type!='passwd'", "", "name,editionhooks");

		foreach($fields as $field){
			$hooks = array_filter(explode(',', $field->editionhooks));
			foreach($hooks as $hook){
				$hook = str_replace("$prefix:",'',$hook, $count);
				// Laisse passer les fonctions non préfixée en 'pre' pour compatibilité
				if (($count>0 || ($prefix=='pre' && preg_match("/[a-zA-Z09\-_]:[a-zA-Z09\-_]/", $hook) === 0)) && is_callable($hook)) {
					if (strpos($hook,'::')) { // exception pour les méthodes static
						list($func, $method) = explode('::', $hook);
						$func::$method($context, $field->name, $error);
					} else {
						$hook($context, $field->name, $error);
					}
				}
			}
		}
	}

} // class Logic }}}


/*------------------------------------------------*/
/**
 * Logic factory
 */
if(!function_exists('getLogic'))
{
	function getLogic($table)
	{
		return Logic::getLogic($table);
	}
}

/**
 * function returning the right for $access in the table $table
 */
if(!function_exists('rights'))
{
	function rights($table, $access)
	{
		static $cache;
		if (!isset($cache[$table][$access])) {
			$cache[$table][$access] = Logic::getLogic($table)->rights($access);
		}
		return $cache[$table][$access];
	}
}

/**
 * Pipe function to test if an object can be deleted or not
 * (with cache)
 */
if(!function_exists('isdeletelocked'))
{
	function isdeletelocked($table, $id, $status = 0)
	{
		static $cache;
		$id = (int)$id;
		if (!isset($cache[$table][$id])) {
			$cache[$table][$id] = Logic::getLogic($table)->isdeletelocked($id, $status);
		}
		return $cache[$table][$id];
	}
}
