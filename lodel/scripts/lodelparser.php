<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier LodelParser
 */

// traitement particulier des attributs d'une loop
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//

/**
 * Classe LodelParser
 *
 * Classe utilitaire pour parser le Lodelscript - Fille de la classe Parser
 *
 */
class LodelParser extends Parser
{
	/**
	 * Tableau associatif concernant le status des textes
	 *
	 * @var array
	 */
	protected $textstatus = array ('-1' => ' traduire', '1' => ' revoir', '2' => 'traduit');
	
	/**
	 * Tableau associatif concernant le status associé à la couleur
	 *
	 * @var array
	 */
	protected $colorstatus = array ('-1' => 'red', '1' => 'orange', 2 => 'green');

	/**
	 * Liste des langues de la traduction
	 *
	 * @var array
	 */
	protected $translationlanglist = "'fr','en','es','de'";

	/**
	 * Nombre de langues disponibles
	 * Tient compte de la base utilisée
	 * @var array
	 */
	protected $nbLangs = array();

	/**
	 * Tableau contenant les noms et types des classes
	 */
	protected $classes = array();

	/**
	 * Tableau des tables/champs, référence vers $tablefields
	 */
	protected $tablefields;

    	/** instance de la classe */
    	static private $_instance;
    
	/** préfixe currentdb */
    	protected $prefix;

	/** préfixe maindb */
    	protected $mprefix;

	/** singleton */
	static public function getParser()
	{
		if(!isset(self::$_instance))
		{
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		return self::$_instance;
	}

	/**
	 * Constructeur.
	 */
	protected function __construct()
	{ // constructor
		parent::__construct();
		$this->variablechar = '@'; // catch the @
		
		if(!($this->tablefields = cache_get('tablefields')))
		{
			include_once 'tablefields.php';
			$this->tablefields =& $tablefields;
		}

		$this->prefix = C::get('tableprefix', 'cfg');
		$this->database = C::get('database', 'cfg');
		$this->mprefix = '`'.$this->database.'`.'.$this->prefix;

		if(isset($this->tablefields[$this->prefix."classes"]) && !($this->classes = cache_get('classes')))
		{
			defined('INC_CONNECT') || include 'connect.php';
			global $db;
			$obj = $db->Execute("SELECT class,classtype FROM {$GLOBALS['tp']}classes WHERE status>0")
				or trigger_error('SQL Error:<br/>'.$db->ErrorMsg(), E_USER_ERROR);
			while(!$obj->EOF) 
			{
				$this->classes[$obj->fields['class']] = array('class'=>$obj->fields['class'], 'classtype'=>$obj->fields['classtype']);
				$obj->MoveNext();
			}
			$obj->Close();
			unset($obj);
			// caching
			$cache = getCacheObject();
			$cache->set(getCacheIdFromId('classes'), $this->classes);
		}
	}

	protected function parse_loop_extra(& $tables, & $tablesinselect, & $extrainselect, & $selectparts)
	{
		global $db;
		static $site;
		static $single;
		if(!isset($site)) $site = C::get('site', 'cfg');
		if(!isset($single)) $single = C::get('singledatabase', 'cfg') != "on";
		// split the SQL parts into quoted/non-quoted part
		$selectparts = array_map(array($this, 'sqlsplit'), $selectparts);

		$where = & $selectparts['where'];

		$tablescopy = array_flip($tables);
		if (!empty($this->classes)) {
			foreach ($this->classes as $class) {
				// manage the linked tables...
				// do we have the table class in $tables ?
				if(isset($tablescopy[$class['class']])) $ind = $tablescopy[$class['class']];
				else continue;

				$alias = "alias_".$class['classtype']."_".$class['class'];
				$aliastype = "aliastype_".$class['classtype']. "_". $class['class'];
				$aliasbyclasstype[$class['classtype']] = $alias;
				$classbyclasstype[$class['classtype']] = $class['class'];

				switch ($class['classtype']) {
				case "entities" :
					$typetable = "types";
					protect($selectparts, $alias, "id|idtype|identifier|usergroup|iduser|rank|status|idparent|creationdate|modificationdate|g_title");
					$longid = "identity";
					break;
				case "entries" :
					$typetable = "entrytypes";
					protect($selectparts, $alias, "id|idtype|g_name|sortkey|rank|status|idparent");
					$longid = "identry";
					break;
				case "persons" :
					$typetable = "persontypes";
					protect($selectparts, $alias, "id|idtype|g_familyname|g_firstname|status");
					$longid = "idperson";
					break;
				default :
					trigger_error("ERROR: internal error in lodelparser", E_USER_ERROR);
				}

				$tables[] = $class['classtype']. " AS ". $alias;
				$tablescopy[$class['classtype']. " AS ". $alias] = true;
				$tables[] = typestable($class['classtype']). " AS ". $aliastype;
				$tablescopy[typestable($class['classtype']). " AS ". $aliastype] = true;

// 				array_push($tables, $class['classtype']. " AS ". $alias, typestable($class['classtype']). " AS ". $aliastype);

				// put entites just after the class table
				array_splice($tablesinselect, $ind +1, 0, $alias);

				$where[count($where) - 1] .= " AND ". $class['class']. ".". $longid."=". $alias. ".id AND ".$alias.".idtype=". $aliastype.".id AND ". $aliastype. ".class=";
				$where[] = "'". $class['class']. "'"; // quoted part
				$where[] = "";
				$extrainselect .= ", ".$aliastype. ".type , ". $aliastype. ".class";

				if (($class['classtype'] == "entities" || $class['classtype'] == "entries") && preg_match_sql("/\bparent\b/", $where)) {
					$tables[] = $class['classtype']. " AS ". $alias. "_parent";
					$tablescopy[$class['classtype']. " AS ". $alias. "_parent"] = true;
// 					array_push($tables, $class['classtype']. " AS ". $alias. "_parent");
					$fullid = $class['classtype'] == "entries" ? "g_name" : "identifier";
					preg_replace_sql("/\bparent\b/", $alias. "_parent.".$fullid, $where);
					$where[count($where) - 1] .= " AND ". $alias. "_parent.id=". $alias. ".idparent";
				}
			}
		}

		$wherecount = count($where) - 1;

		if (isset($tablescopy['entities'])) {
			$hasType = isset($tablescopy['types']);
			if (preg_match_sql("/\bclass\b/", $where) || preg_match_sql("/\btype\b/", $where)) {
				$tables[] = 'types';
				$tablescopy['types'] = true;
				$hasType = true;
// 				array_push($tables, "types");
				protect($selectparts, "entities", "id|status|rank");
				$where[$wherecount] .= " AND entities.idtype=types.id";
			}
			if (preg_match_sql("/\bparent\b/", $where)) {
				$tables[] = "entities AS entities_parent";
				$tablescopy['entities AS entities_parent'] = true;
// 				array_push($tables, "entities as entities_parent");
				protect($selectparts, "entities", "id|idtype|identifier|usergroup|iduser|rank|status|idparent|creationdate|modificationdate|g_title");
				preg_replace_sql("/\bparent\b/", "entities_parent.identifier", $where);
				$where[$wherecount] .= " AND entities_parent.id=entities.idparent";
			}
			if ($hasType) { // compatibilite avec avant... et puis c est pratique quand meme.
				$extrainselect .= ", types.type , types.class";
			}
		} // fin de entities

		// verifie le status
		if (!preg_match_sql("/\bstatus\b/i", $where)) { // test que l'element n'est pas a la poubelle
			foreach ($tables as $table) {
				$splitted = preg_split("/\s+AS\s+/i", $table);
				$table = $splitted[0];
				if(isset($splitted[1]))
					$alias = $splitted[1];
				else $alias = null;
				$realtable = $this->prefixTableName($table);
				$main = (FALSE === strpos($table, 'lodelmain')) ? false : true;
				if (!$alias){
					$alias = $main ? $realtable : $table;
				}
				if ($table == "session" || !isset($this->tablefields[$realtable]) || !in_array("status", $this->tablefields[$realtable])) {
					continue;
				}
				// test for ambiguous column name
				if(!$main) {
					$alias = (isset($tablescopy[$this->database.".".$table]) || isset($tablescopy['lodelmain.'.$table]) && $site && $single) 
						? '`'.$this->database."_".$site.'`.'.$alias : $alias;
				}

				$lowstatus = ($table == "entities") ? '"-64". (C::get(\'admin\', \'lodeluser\') ? "" : "*('.$alias.'.usergroup IN (".C::get(\'groups\', \'lodeluser\')."))")' : "-64";
				$where[$wherecount] .= " AND (".$alias.".status>\".(C::get('visitor', 'lodeluser') ? $lowstatus : \"0\").\")";
			}
		}
		#  echo "where 2:",htmlentities($where),"<br>";

		if ($site) {
			///////// CODE SPECIFIQUE -- gere les tables croisees
			//
			// les regexp ci-dessous sont insuffisantes, il faudrait tester que ce n'est pas dans une zone quotee de la clause where !!!!
			//
			// persons and entries
			foreach (array ("persons" => "persontypes", "entries" => "entrytypes") as $table => $typetable) {
				if (isset($tablescopy[$table]) && preg_match_sql("/\b(type|g_type)\b/", $where)) {
					protect($selectparts, $table, "id|status|rank");
					$tables[] = $typetable;
					$tablescopy[$typetable] = true;
// 					array_push($tables, $typetable);
					$where[$wherecount] .= " AND ".$table.".idtype=".$typetable.".id";
				}

				if (isset($tablescopy[$table]) || isset($aliasbyclasstype[$table]))	{
					if (isset($aliasbyclasstype[$table])) {
						$class = $classbyclasstype[$table];
						$table = $aliasbyclasstype[$table];
					}	else {
						$class = "";
					}
					// fait ca en premier
					if (preg_match_sql("/\b(iddocument|identity)\b/", $where)) {
						// on a besoin de la table croisee entities_persons
						$alias = "relation_entities_".$table; // use alias for security
						$tables[] = "relations AS ".$alias;
						$tablescopy['relations AS '.$alias] = true;
// 						array_push($tables, "relations as ".$alias); ###,"entities_persons");
						#print_R($where);
						preg_replace_sql("/\b(?<!\.)(iddocument|identity)\b/", $alias.".id1", $where);
						#print_R($where);
						$where[$wherecount] .= " AND $alias.id2=$table.id";

						if ($class && $typetable == "persontypes") { // related table for persons only
							$relatedtable = "entities_".$class;
							if (!isset($tablescopy[$relatedtable])) {
								$tables[] = $relatedtable;
								$tablescopy[$relatedtable] = true;
// 								array_push($tables, $relatedtable);
								$where[$wherecount] .= " AND ".$relatedtable.".idrelation=".$alias.".idrelation";
								$extrainselect .= ", ".$relatedtable.".* ";
							}
						}
					}
				}
			}

			if (isset($aliasbyclasstype['entities']) || isset($tablescopy['entities']))
			{
				foreach (array ("persons" => "idperson", "entries" => "identry") as $table => $regexp) 
				{
					if (!preg_match_sql("/\b($regexp)\b/", $where)) {
						continue;
					}
					// on a besoin de la table croise relation
					$alias = "relation2_".$table; // use alias for security
					$tables[] = "relations AS ".$alias;
					$tablescopy["relations AS ".$alias] = true;
// 					array_push($tables, "relations as ".$alias);
					preg_replace_sql("/\b($regexp)\b/", $alias.".id2", $where);
					$where[$wherecount] .= " AND ".$alias.".id1=". (isset($aliasbyclasstype['entities']) ? $aliasbyclasstype['entities'] : "entities").".id";

					if ($table == "persons" && isset($classbyclasstype['persons'])) { // related table for persons only
						$relatedtable = "entities_".$classbyclasstype['persons'];
						if (!isset($tablescopy[$relatedtable])) {
							$tables[] = $relatedtable;
							$tablescopy[$relatedtable] = true;
// 							array_push($tables, $relatedtable);
							$where[$wherecount] .= " AND ".$relatedtable.".idrelation=".$alias.".idrelation";
							$extrainselect .= ", ".$relatedtable.".* ";
						}
					}
				}
			}

			if (isset($tablescopy['usergroups']) && preg_match_sql("/\biduser\b/", $where)) {
				// on a besoin de la table croise users_groupes
				$tables[] = "users_usergroups";
				$tablescopy['users_usergroups'] = true;
// 				array_push($tables, "users_usergroups");
				$where[$wherecount] .= " AND idgroup=usergroups.id";
			}
			if (isset($tablescopy['users']) && isset($tablescopy['session']))
			{
				$where[$wherecount] .= " AND iduser=users.id";
			}
		} // site

		unset($tablescopy);

		// join the SQL parts
		foreach ($selectparts as $k => $v) {
			if (is_array($v)) {
				$selectparts[$k] = join("", $v);
			}
		}

		$selectparts['where'] = $this->lq($selectparts['where']);
		if (isset($selectparts['select']) && preg_match("/\b(count|min|max|avg|bit_and|bit_or|bit_xor|group_concat|std|stddev|stddev_pop|stddev_samp|sum|var_pop|var_samp|variance)\s*\(/i", $selectparts['select']))
			$extrainselect = ""; // group by function
		else $extrainselect = $this->lq($extrainselect);

		$tables = array_map(array ($this, "prefixTableName"), $tables);
		$tablesinselect = array_map(array ($this, "prefixTableName"), $tablesinselect);
	}

	/**
	* Lodel Query : 
	*
	* Transforme les requêtes en résolvant les jointures et en cherchant les bonnes
	* tables dans les bases de données (suivant notamment le préfix utilisé pour le nommage des
	* tables).
	*
	* @param string $query la requête à traduire
	* @return string la requête traduite
	*/
	public function lq($query)
	{
		if (strpos($query, '#_') !== false)	{
			// the easiest, fast replace
			$query = strtr($query, array('#_TP_'=>$GLOBALS['tp'], '#_MTP_' => $this->mprefix));
	
			// any other ?
			if (strpos($query, '#_') !== false) {
				$cmd = array (
		'#_entitiestypesjoin_' => $GLOBALS['tp'].'types INNER JOIN '.$GLOBALS['tp'].'entities 
					ON '.$GLOBALS['tp'].'types.id='.$GLOBALS['tp'].'entities.idtype',
	
		'#_tablefieldsandgroupsjoin_' => $GLOBALS['tp'].'tablefieldgroups INNER JOIN '.$GLOBALS['tp'].'tablefields 
					ON '.$GLOBALS['tp'].'tablefields.idgroup='.$GLOBALS['tp'].'tablefieldgroups.id',
	
		'#_tablefieldgroupsandclassesjoin_' => $GLOBALS['tp'].'tablefieldgroups INNER JOIN '.$GLOBALS['tp'].'classes 
					ON '.$GLOBALS['tp'].'classes.class='.$GLOBALS['tp'].'tablefieldgroups.class');
	
				$query = strtr($query, $cmd);
			}
		}
		return $query;
	}

	//
	// Traitement special des variables
	//
	public function parse_variable_extra($prefix, $varname)
	{
		// VARIABLES SPECIALES
		if ($prefix == "#") {
			if ($varname == "GROUPRIGHT") {
				return '(C::get(\'admin\', \'lodeluser\') || in_array($context[\'usergroup\'],explode(\',\',C::get(\'groups\', \'lodeluser\')))';
			}
			if(0 === strpos($varname, 'OPTION') && ('.' === $varname{6} || '_' === $varname{6})) {// options
				return "getoption('".strtolower(substr($varname, 7))."')";
			}
		}
		elseif ($prefix == "@") {
			$dotpos = strpos($varname, ".");
			if ($dotpos) {
				$name = substr($varname, $dotpos +1);
				$group = substr($varname, 0, $dotpos);
			}	else {
				$name = $varname;
				$group = "site";
			}
			return $this->maketext($name, $group, "@");
		}
		return FALSE;
	}

	//
	// fonction qui gere les decodage du contenu des differentes parties
	// d'une loop (DO*)
	// fonction speciale pour lodel 
	//
	protected function decode_loop_content_extra($balise, & $content, & $options, $tables)
	{
		$filtered = false;

		foreach($tables as $table)
		{
			if(isset($this->classes[$table]))
			{
				$filtered = true;
				break;
			}
		}

// 		$havepublications = in_array("publications", $tables);
// 		$havedocuments = in_array("textes", $tables);
		//
		// est-ce qu'on veut le prev et next publication ?
		//

		// desactive le 10/03/04
		//  if ($havepublications && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$content[$balise])) {
		//    $content["PRE_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication($context);';
		//  }
		// les filtrages automatiques
		if ($filtered) 
        	{
			$options['sqlfetchassoc'] = 'filtered_mysql_fetch_assoc($context,%s)';
			$this->fct_txt .= 'if (!function_exists("filtered_mysql_fetch_assoc")) include_once("filterfunc.php");';
		}
		else
		{
			$options['sqlfetchassoc'] = null;
		}
	}

	protected function parse_TEXT()
	{
		$attr = $this->_decode_attributs($this->arr[$this->ind + 1]);

		if (!$attr['NAME']) {
			$this->_errmsg("ERROR: The TEXT tag has no NAME attribute");
		}
		$name = addslashes(stripslashes(trim($attr['NAME'])));
		$group = '';

		if(isset($attr['GROUP'])) $group = addslashes(stripslashes(trim($attr['GROUP'])));

		if (!$group) {
			$group = "site";
		}

		$this->_clearposition();
		$this->arr[$this->ind] = $this->maketext($name, $group, "text");
	}

	protected function maketext($name, $group, $tag)
	{
		global $db;
		static $done = array();
		$name = strtolower($name);
		$group = strtolower($group);

		if (C::get('visitor', 'lodeluser') && !isset($done[$tag][$group][$name])) 
		{ // cherche si le texte existe
			$prefix = ($group != "site") ? $this->mprefix : $this->prefix;
			if(!isset($this->nbLangs[$prefix]))
				$this->nbLangs[$prefix] = $db->GetOne("SELECT count(distinct(lang)) FROM {$prefix}translations");

			$textexists = $db->Execute(
				"SELECT COUNT(id) AS nb 
					FROM {$prefix}texts 
					WHERE name=? AND textgroup=? 
					LIMIT 1", array($name, $group));

			if ($db->errorno()) {
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if ($textexists->fields['nb'] < $this->nbLangs[$prefix]) { 
				// text does not exists or not available in every langs
				// Have to create them
				Logic::getLogic("texts")->createTexts($name, $group);
			}
			
			$done[$tag][$group][$name] = true;
		}

		$fullname = $group.'.'.$name;
		$this->translationtags[] = "'".$fullname."'"; // save all the TEXT for the CACHE
		if ($tag == "text")	{
			// modify inline
			$modifyif = 'C::get(\'editor\', \'lodeluser\')';
			if ($group == 'interface')
				$modifyif .= ' && C::get(\'translationmode\', \'lodeluser\')';

			$modify = ' if ('.$modifyif.') { ?><a href="'.SITEROOT.'lodel/admin/index.php?do=edit&amp;lo=texts&amp;id=<?php echo $id; ?>">[M]</a> <?php if (!$text) $text=\''.$name.'\';  } ';

			return '<?php getlodeltext("'.$name.'","'.$group.'",$id,$text,$status);'.$modify.' echo preg_replace("/(\r\n?\s*){2,}/","<br />",$text); ?>';
		}	else {
			// modify at the end of the file
			##$modify=' if ($context[\'lodeluser\'][\'translationmode\'] && !$text) $text=\'@'.strtoupper($name).'\'; ';
			$modify = "";
			if (!isset($this->translationform[$fullname]))
			{ // make the modify form
				$this->translationform[$fullname] = '<?php mkeditlodeltext("'.$name.'","'.$group.'"); ?>';
			}
			return 'getlodeltextcontents("'.$name.'","'.$group.'")';
		}
	}

	protected function parse_after(& $text)
	{
		// add the translation system when in translation mode
		if ($this->translationform) {
			// add the translations form before the body    
			$closepos = strpos($text, "</body>");
			if ($closepos === false)
				return; // no idea what to do...
			$tf = join("", $this->translationform);
			$code = <<<PHP
<?php if (C::get('translationmode', 'lodeluser')=="interface" || (!defined('backoffice') && !defined('backoffice-lodeladmin') && C::get('translationmode', 'lodeluser')=="site")) {
if(!function_exists('mkeditlodeltextJS')) include("translationfunc.php"); mkeditlodeltextJS(); ?>
<hr />
<form method="post" action="index.<?php echo !defined('backoffice') ? C::get('extensionscripts', 'cfg') : 'php';?>">
<input type="hidden" name="edit" value="1" />
<input type="hidden" name="do" value="edit" />
<input type="hidden" name="lo" value="texts" />
<fieldset id="translationforms">
	<legend><?php echo getlodeltextcontents('TRANSLATIONS_FOR_THIS_PAGE','lodeladmin'); ?></legend>
<input type="submit" class="button" value="<?php echo getlodeltextcontents('update', 'common'); ?>" />
<dl id="translation"><a id="top" href="#bottom"> --bottom -- </a>{$tf}<a id="bottom" href="#top"> --top-- </a></dl>
<input type="submit" class="button" value="<?php echo getlodeltextcontents('update', 'common');?>" />
</fieldset>
</form>
<?php } ?>
PHP;
			unset($tf);
			$text = substr_replace($text, $code, $closepos, 0);
		}

		if ($this->translationtags)	{
			$tt = join(',', $this->translationtags);
			$text = <<<PHP
<?php 
\$langfile="lang-".\$context['sitelang']."_tpl_{$this->tpl}";
if(!isset(\$GLOBALS['langcache'][\$context['sitelang']])) { \$GLOBALS['langcache'][\$context['sitelang']] = array(); }
\$langcontents = cache_get(\$langfile);

if (\$langcontents === false) {
    \$cachelang = generateLangCache(\$context['sitelang'], \$langfile, array({$tt}));
    if (!is_array(\$cachelang)) {
        \$cachelang = array(\$cachelang);
    }
	array_merge(\$GLOBALS['langcache'][\$context['sitelang']], \$cachelang);
} else {
	array_merge(\$GLOBALS['langcache'][\$context['sitelang']], \$langcontents);
}
unset(\$langfile, \$langcontents, \$cachelang);
?>
{$text}
PHP;
			unset($tt);
		}

		// add the code for the desk
		if (!isset($GLOBALS['nodesk'])) {
			$deskbegin = <<<PHP
<?php if (C::get('visitor', 'lodeluser')) { // insert the desk
	echo '<?php echo \$this->getIncTpl(\$context,"desk","", \$this->_home."../tpl/"); ?>';
?>
<div id="lodel-container">
<?php } ?>
PHP;

			$deskend = <<<PHP
<?php if (C::get('visitor', 'lodeluser')) { // insert end of the desk
 ?></div><?php } ?>
PHP;

			$bodystarttag = strpos($text, "<body");
            		if(false === $bodystarttag) return;

			if ($text{$bodystarttag+5} === '>') {
				// pas d'attributs dans le body, pas de pbs
				$bodyendtag = $bodystarttag + 6;
			} else {
				// on a des attributs dans la balise body.
				// si c'est du LS ca va faire planter le desk
				// on traite ce cas séparément
				preg_match("`<body(.*)[^?]>`", $text, $res);
			    	$bodyendtag = isset($res[0]) ? $bodystarttag + strlen($res[0]) : $bodystarttag;
			}

			if($bodyendtag) {
				$text = substr_replace($text, $deskbegin, $bodyendtag, 0);
				$text = substr_replace($text, $deskend, strpos($text, "</body>"), 0);
			}
 		}
 	}

	#function prefixTableNameRef(&$table) { 
	#  $table2=$this->prefixTableName($table); $table=$table2; // necessaire de passer par table2... sinon ca crash PHP
	#}
	protected function prefixTableName($table)
	{
		$prefixedtable = $this->prefix.$table;
		$mprefixedtable = $this->mprefix.$table;

		if('"' === $table{0})
			return $table;
		if(isset($this->tablefields[$prefixedtable]))
			return $prefixedtable;
		elseif(isset($this->tablefields[$mprefixedtable]))
			return $mprefixedtable;
		elseif('alias' === substr($table, 0, 5))
			return $table;

		$dbname = $alias = "";
		if (FALSE !== stripos($table, ' as ') && preg_match("/\b((?:\w+\.)?\w+)(\s+as\s+\w+)\b/i", $table, $result))	{
			$table = $result[1];
			$alias = $result[2];
		}
		if (FALSE !== strpos($table, '.') && preg_match("/\b(\w+\.)(\w+)\b/", $table, $result)) {
			$table = $result[2];
			$dbname = $result[1];
			if ($dbname == "lodelmain.") {
				$dbname = $this->database.".";
			}
		}

                $prefixedtable = $this->prefix.$table;
                $mprefixedtable = $this->mprefix.$table;

        	if (isset($this->tablefields[$prefixedtable]) && ($dbname == "" || $dbname == $GLOBALS['currentdb'].".")) {
			return $prefixedtable.$alias;
		} elseif (isset($this->tablefields[$mprefixedtable]) && ($dbname == "" || $dbname == $this->database.".")) {
			return $mprefixedtable.$alias;
		} else {
			return ($dbname ? '`'.substr($dbname, 0, -1).'`.'.$table.$alias : $table.$alias);
		}
	}

	protected function sqlsplit($sql)
	{
		$inquote = false;
		$inphp = false;
		$escaped = false;
		$arr = array ();
		$ind = 0;
		$i = -1;
		while(isset($sql{++$i}))
		{
			$c = $sql {$i};
			#echo $c=='"';
			if (!$escaped) {
				if ($c == '"') {
					$inphp = !$inphp;
					if (!$inquote) {
						++$ind;
					}

				}	elseif ($c == "'" && !$inphp)	{
					$inquote = !$inquote;
					++$ind;
				}
			}
			$escaped = $c == "\\" && !$escaped;
			if(!isset($arr[$ind])) $arr[$ind] = $c;
			else $arr[$ind] .= $c;
		}
		if ($inphp || $inquote) {
			$this->_errmsg("incorrect quoting");
		}
		return $arr;
	}

} // end of the class LodelParser

function protect(& $sql, $table, $fields)
{
	foreach ($sql as $k => $v) 
    	{
		$i = 0;
		while(isset($v[$i]))
		{
			if(!isset($v[$i])) { $sql[$k][$i]=''; $i += 2; continue; }
			$sql[$k][$i] = preg_replace("/\b(?<!\.)($fields)\b/", "$table.\\1", $v[$i]);
			$i += 2;
		}
// 		$n = count($v);
// 		for ($i = 0; $i < $n; $i += 2) {
// 			if(!isset($v[$i])) { $sql[$k][$i]=''; continue; }
// 			$sql[$k][$i] = preg_replace("/\b(?<!\.)($fields)\b/", "$table.\\1", $v[$i]);
// 		}
	}
}

function preg_match_sql($find, $arr)
{
	$i = 0;
	while(isset($arr[$i]))
	{
		if (preg_match($find, $arr[$i])) {
			return true;
		}
		$i += 2;
	}
// 	$n = count($arr);
// 	for ($i = 0; $i < $n; $i += 2) {
// 		if (preg_match($find, $arr[$i])) {
// 			return true;
// 		}
// 	}
	return false;
}

function preg_replace_sql($find, $rpl, & $arr)
{
	$i = 0;
	while(isset($arr[$i]))
	{
		$arr[$i] = preg_replace($find, $rpl, $arr[$i]);
		$i += 2;
	}
// 	$n = count($arr);
// 	for ($i = 0; $i < $n; $i += 2) {
// 		$arr[$i] = preg_replace($find, $rpl, $arr[$i]);
// 	}
}

function typestable($classtype)
{
	if('entities' == $classtype) return 'types';
	elseif('entries' == $classtype) return 'entrytypes';
	elseif('persons' == $classtype) return 'persontypes';
// 	switch ($classtype) {
// 	case "entities" :
// 		return "types";
// 	case "entries" :
// 		return "entrytypes";
// 	case "persons" :
// 		return "persontypes";
// 	}
}
