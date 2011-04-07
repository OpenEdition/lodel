<?php
/**
 * Fichier LodelParser
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
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
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Sophie Malafosse
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

// traitement particulier des attributs d'une loop
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//

require_once "func.php";
require_once "balises.php";
require_once "parser.php";

/**
 * Classe LodelParser
 * 
 * Classe utilitaire pour parser le Lodelscript - Fille de la classe Parser
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajoutée depuis la version 0.8
 * @see parser.php
 */
class LodelParser extends Parser
{
	var $filterfunc_loaded = FALSE;
	/**
	 * Tableau associatif concernant le status des textes
	 *
	 * @var array
	 */
	var $textstatus = array ('-1' => ' traduire', '1' => ' revoir', '2' => 'traduit');
	
	/**
	 * Tableau associatif concernant le status associé à la couleur
	 *
	 * @var array
	 */
	var $colorstatus = array ('-1' => 'red', '1' => 'orange', 2 => 'green');

	/**
	 * Liste des langues de la traduction
	 *
	 * @var array
	 */
	var $translationlanglist = "'fr','en','es','de'";


	/**
	 * Constructeur.
	 */
	function LodelParser()
	{ // constructor
		$this->Parser();
		$this->commands[] = 'TEXT'; // catch the text
		$this->variablechar = '@'; // catch the @

		if (DBDRIVER == 'mysql') {
			$this->codepieces['sqlquery'] = "mysql_query(lq(%s))";
		}	else { // call the ADODB driver
			die("DBDRIVER not supported currently for the parser");
			$this->codepieces['sqlerror'] = "or die($GLOBALS[db]->errormsg())";
			$this->codepieces['sqlquery'] = "$GLOBALS[db]->execute(%s)";
			$this->codepieces['sqlfetch'] = '';
		}
	}

	function parse_loop_extra(& $tables, & $tablesinselect, & $extrainselect, & $selectparts)
	{
		global $site, $home, $db;
		static $tablefields; // charge qu'une seule fois
		if (!$tablefields) {
			require ('tablefields.php');
		}

		// split the SQL parts into quoted/non-quoted par
		foreach ($selectparts as $k => $v) {
			$selectparts[$k] = $this->sqlsplit($v);
		}

		$where = & $selectparts['where'];

		// convertion des codes specifiques dans le where
		// ce bout de code depend du parenthesage et du trim fait dans parse_loop.
		#  $where=preg_replace (array(
		#		    "/\(trash\)/i",
		#		    "/\(ok\)/i",
		#		    "/\(rightgroup\)/i"
		#		    ),
		#	      array(
		#		    "status<=0",
		#		    "status>0",
		#		    '".($GLOBALS[lodeluser][admin] ? "1" : "(usergroup IN ($GLOBALS[lodeluser][groups]))")."'
		#		    ),$where);
		//
		if ($tablefields[lq("#_TP_classes")]) {

			$dao = &getDAO("classes");
			$classes = $dao->findMany("status > 0");
			foreach ($classes as $class) {
				// manage the linked tables...
				// do we have the table class in $tables ?
				$ind = array_search($class->class, $tables);
				if ($ind === FALSE || $ind === NULL) {
					continue;
				}

				$alias = "alias_".$class->classtype."_".$class->class;
				$aliastype = "aliastype_".$class->classtype. "_". $class->class;
				$aliasbyclasstype[$class->classtype] = $alias;
				$classbyclasstype[$class->classtype] = $class->class;

				switch ($class->classtype) {
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
					die("ERROR: internal error in lodelparser");
				}

				array_push($tables, $class->classtype. " AS ". $alias, typestable($class->classtype). " AS ". $aliastype);

				// put entites just after the class table
				array_splice($tablesinselect, $ind +1, 0, $alias);

				$where[count($where) - 1] .= " AND ". $class->class. ".". $longid."=". $alias. ".id AND ".$alias.".idtype=". $aliastype.".id AND ". $aliastype. ".class=";
				$where[] = "'". $class->class. "'"; // quoted part
				$where[] = "";
				$extrainselect .= ", ".$aliastype. ".type , ". $aliastype. ".class";

				if (preg_match_sql("/\bparent\b/", $where) && 
						($class->classtype == "entities" || $class->classtype == "entries")) {
					array_push($tables, $class->classtype. " AS ". $alias. "_parent");
					$fullid = $class->classtype == "entries" ? "g_name" : "identifier";
					preg_replace_sql("/\bparent\b/", $alias. "_parent.".$fullid, $where);
					$where[count($where) - 1] .= " AND ". $alias. "_parent.id=". $alias. ".idparent";
				}
			}
		}

		if (in_array("entities", $tables)) {
			if (preg_match_sql("/\bclass\b/", $where) || preg_match_sql("/\btype\b/", $where)) {
				array_push($tables, "types");
				protect($selectparts, "entities", "id|status|rank");
				$jointypesentitiesadded = 1;
				$where[count($where) - 1] .= " AND entities.idtype=types.id";
			}
			if (preg_match_sql("/\bparent\b/", $where)) {
				array_push($tables, "entities as entities_parent");
				protect($selectparts, "entities", "id|idtype|identifier|usergroup|iduser|rank|status|idparent|creationdate|modificationdate|g_title");
				preg_replace_sql("/\bparent\b/", "entities_parent.identifier", $where);
				$where[count($where) - 1] .= " AND entities_parent.id=entities.idparent";
			}
			if (in_array("types", $tables)) { // compatibilite avec avant... et puis c est pratique quand meme.
				$extrainselect .= ", types.type , types.class";
			}
		} // fin de entities

		// verifie le status
		if (!preg_match_sql("/\bstatus\b/i", $where)) { // test que l'element n'est pas a la poubelle
			$teststatus = array ();
			foreach ($tables as $table) {
				list ($table, $alias) = preg_split("/\s+AS\s+/i", $table);
				if (!$alias){
					$alias = $table;
				}

				$realtable = $this->prefixTableName($table);
				if (!$tablefields[$realtable] || !in_array("status", $tablefields[$realtable])) {
					continue;
				}
				if ($table == "session") {
					continue;
				}
				if ($table == "entities") {
					$lowstatus = '"-64".($GLOBALS[lodeluser][admin] ? "" : "*('.$alias.'.usergroup IN (".$GLOBALS[lodeluser][groups]."))")';
				}	else {
					$lowstatus = "-64";
				}
				$where[count($where) - 1] .= " AND (".$alias.".status>\".(\$GLOBALS[lodeluser][visitor] ? $lowstatus : \"0\").\")";
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
				if (in_array($table, $tables) && preg_match_sql("/\b(type|g_type)\b/", $where)) {
					protect($selectparts, $table, "id|status|rank");
					array_push($tables, $typetable);
					$where[count($where) - 1] .= " AND ".$table.".idtype=".$typetable.".id";
				}

				if (in_array($table, $tables) || $aliasbyclasstype[$table])	{
					if ($aliasbyclasstype[$table]) {
						$class = $classbyclasstype[$table];
						$table = $aliasbyclasstype[$table];
					}	else {
						$class = "";
					}
					// fait ca en premier
					if (preg_match_sql("/\b(iddocument|identity)\b/", $where)) {
						// on a besoin de la table croisee entities_persons
						$alias = "relation_entities_".$table; // use alias for security
						array_push($tables, "relations as ".$alias); ###,"entities_persons");
						#print_R($where);
						preg_replace_sql("/\b(iddocument|identity)\b/", $alias.".id1", $where);
						#print_R($where);
						$where[count($where) - 1] .= " AND $alias.id2=$table.id";

						if ($class && $typetable == "persontypes") { // related table for persons only
							$relatedtable = "entities_".$class;
							if (!in_array($relatedtable, $tables)) {
								array_push($tables, $relatedtable);
								$where[count($where) - 1] .= " AND ".$relatedtable.".idrelation=".$alias.".idrelation";
								$extrainselect .= ", ".$relatedtable.".* ";
							}
						}
					}
				}
			}

			if ($aliasbyclasstype['entities'] || in_array("entities", $tables))	{
				foreach (array ("persons" => "idperson", "entries" => "identry") as $table => $regexp) {

					if (!preg_match_sql("/\b($regexp)\b/", $where)) {
						continue;
					}
					// on a besoin de la table croise relation
					$alias = "relation2_".$table; // use alias for security
					array_push($tables, "relations as ".$alias);
					preg_replace_sql("/\b($regexp)\b/", $alias.".id2", $where);
					$where[count($where) - 1] .= " AND ".$alias.".id1=". ($aliasbyclasstype['entities'] ? $aliasbyclasstype['entities'] : "entities").".id";

					if ($table == "persons" && $classbyclasstype['persons']) { // related table for persons only
						$relatedtable = "entities_".$classbyclasstype['persons'];
						if (!in_array($relatedtable, $tables)) {
							array_push($tables, $relatedtable);
							$where[count($where) - 1] .= " AND ".$relatedtable.".idrelation=".$alias.".idrelation";
							$extrainselect .= ", ".$relatedtable.".* ";
						}
					}
				}
			}

			if (in_array("usergroups", $tables) && preg_match_sql("/\biduser\b/", $where)) {
				// on a besoin de la table croise users_groupes
				array_push($tables, "users_usergroups");
				$where[count($where) - 1] .= " AND idgroup=usergroups.id";
			}
			if (in_array("users", $tables) && in_array("session", $tables))	{
				$where[count($where) - 1] .= " AND iduser=users.id";
			}

		} // site

		// join the SQL parts
		foreach ($selectparts as $k => $v) {
			if (is_array($v)) {
				$selectparts[$k] = join("", $v);
			}
		}

		$selectparts['where'] = lq($selectparts['where']);
		if (preg_match("/\b(count|min|max|avg|bit_and|bit_or|bit_xor|group_concat|std|stddev|stddev_pop|stddev_samp|sum|var_pop|var_samp|variance)\s*\(/i", $selectparts['select']))
			$extrainselect = ""; // group by function

		$extrainselect = lq($extrainselect);

		$tables = array_map(array (& $this, "prefixTableName"), $tables);
		$tablesinselect = array_map(array (& $this, "prefixTableName"), $tablesinselect);
	}

	//
	// Traitement special des variables
	//
	function parse_variable_extra($prefix, $varname)
	{
		// VARIABLES SPECIALES
		if ($prefix == "#") {
			if ($varname == "GROUPRIGHT") {
				return '($GLOBALS[lodeluser][admin] || in_array($context[usergroup],explode(\',\',$GLOBALS[lodeluser][groups])))';
			}
			if (preg_match("/^OPTION[_.]/", $varname)) { // options
				return "getoption('".strtolower(substr($varname, 7))."')";
			}
		}

		if ($prefix == "@") {
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
	function decode_loop_content_extra($balise, & $content, & $options, $tables)
	{
		global $home;

		$havepublications = in_array("publications", $tables);
		$havedocuments = in_array("documents", $tables);
		//
		// est-ce qu'on veut le prev et next publication ?
		//

		// desactive le 10/03/04
		//  if ($havepublications && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$content[$balise])) {
		//    $content["PRE_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication($context);';
		//  }
		// les filtrages automatiques
		if ($havedocuments || $havepublications) {
			$options['sqlfetchassoc'] = 'filtered_mysql_fetch_assoc($context,%s)';
			if (!$this->filterfunc_loaded){
				$this->filterfunc_loaded = TRUE;
				$this->fct_txt .= 'if (!(@include_once(getCachePath("filterfunc.php")))) require_once("filterfunc.php");';
			}
		}
	}

	function parse_TEXT()
	{
		$attr = $this->_decode_attributs($this->arr[$this->ind + 1]);

		if (!$attr['NAME']) {
			$this->errmsg("ERROR: The TEXT tag has no NAME attribute");
		}
		$name = addslashes(stripslashes(trim($attr['NAME'])));

		$group = addslashes(stripslashes(trim($attr['GROUP'])));
		if (!$group) {
			$group = "site";
		}

		$this->_clearposition();
		$this->arr[$this->ind] = $this->maketext($name, $group, "text");
	}

	function maketext($name, $group, $tag)
	{
		global $db;

		$name = strtolower($name);
		$group = strtolower($group);

		if ($GLOBALS['righteditor']) { // cherche si le texte existe
			require_once 'connect.php';

			if ($group != "site") {
				usemaindb();
				$prefix = lq("#_MTP_");
			} else	{
				$prefix = lq("#_TP_");
			}
			$textexists = $db->getOne("SELECT 1 FROM ".$prefix."texts WHERE name='$name' AND textgroup='$group'");
			if ($db->errorno()) {
				dberror();
			}
			if (!$textexists)	{ // text does not exists. Have to create it.
				require_once "logic.php";
				$textslogic = getLogic("texts");
				$textslogic->createTexts($name, $group);
			}
			if ($group != "site") {
				usecurrentdb();
			}
		}

		$fullname = $group.'.'.$name;
		$this->translationtags[] = "'".$fullname."'"; // save all the TEXT for the CACHE
		if ($tag == "text")	{
			// modify inline
			$modifyif = '$context[\'righteditor\']';
			if ($group == 'interface')
				$modifyif .= ' && $context[\'lodeluser\'][\'translationmode\']';

			$modify = ' if ('.$modifyif.') { ?><a href="'.SITEROOT.'lodel/admin/index.php?do=edit&lo=texts&id=<?php echo $id; ?>">[M]</a> <?php if (!$text) $text=\''.$name.'\';  } ';

			return '<?php getlodeltext("'.$name.'","'.$group.'",$id,$text,$status);'.$modify.' echo preg_replace("/(\r\n?\s*){2,}/","<br />",$text); ?>';
		}	else {
			// modify at the end of the file
			##$modify=' if ($context[\'lodeluser\'][\'translationmode\'] && !$text) $text=\'@'.strtoupper($name).'\'; ';
			$modify = "";
			if (!$this->translationform[$fullname])
			{ // make the modify form
				$this->translationform[$fullname] = '<?php mkeditlodeltext("'.$name.'","'.$group.'"); ?>';
			}
			return 'getlodeltextcontents("'.$name.'","'.$group.'")';
		}
	}

	function parse_after(& $text)
	{
		// add the translation system when in translation mode
		if ($this->translationform) {
			// add the translations form before the body    
			$closepos = strpos($text, "</body>");
			if ($closepos === false)
				return; // no idea what to do...

			$code = '<?php if ($context[\'lodeluser\'][\'translationmode\']=="interface") { require_once("translationfunc.php"); mkeditlodeltextJS(); ?>
			<hr />
			<form method="post" action="index.php">
			<input type="hidden" name="edit" value="1" />
			<input type="hidden" name="do" value="edit" />
			<input type="hidden" name="lo" value="texts" />
			<fieldset id="translationforms">
				<legend>'.getlodeltextcontents('TRANSLATIONS_FOR_THIS_PAGE','lodeladmin') .'</legend>
			<input type="submit" class="button" value="<?php echo getlodeltextcontents(\'update\', \'common\');?>" />
			<dl id="translation"><a id="top" href="#bottom"> --bottom -- </a>'.join("", $this->translationform).'<a id="bottom" href="#top"> --top-- </a></dl>
			<input type="submit" class="button" value="<?php echo getlodeltextcontents(\'update\', \'common\');?>" />
			</fieldset>
			</form>
			<?php } ?>';

			$text = substr_replace($text, $code, $closepos, 0);
		}
		if ($this->translationtags)	{
			$text = '<'.'?php
	$langfile=getCachePath("lang-". $GLOBALS[\'lang\']. "/". basename(__FILE__));
	if (!file_exists($langfile)) {
		if(!function_exists("generateLangCache")) { require "view.php"; }
		generateLangCache($GLOBALS[\'lang\'], $langfile, array('. join(',', $this->translationtags).'));
	} else {
		require_once($langfile);
	}
	?'.'>
'. $text;
		}

		// add the code for the desk
		if (!$GLOBALS['nodesk']) {
			$deskbegin = '<'.'?php if ($GLOBALS[\'lodeluser\'][\'visitor\'] || $GLOBALS[\'lodeluser\'][\'adminlodel\']) { // insert the desk
	if(!function_exists("insert_template")) { include "view.php"; }
	insert_template($context,"desk","",$GLOBALS[\'home\']."../tpl/");
	?'.'><div id="lodel-container"><'.'?php  } ?'.'>';

			$deskend = '<'.'?php if ($GLOBALS[\'lodeluser\'][\'visitor\'] || $GLOBALS[\'lodeluser\'][\'adminlodel\']) { // insert end of the desk
	?'.'></div><'.'?php  } ?'.'>';


			/* modifs par Pierre-Alain Mignot */
			$bodystarttag = strpos($text, "<body>");
			if ($bodystarttag !== false) {
				// pas d'attributs dans le body, pas de pbs
				$bodyendtag = $bodystarttag + 6;
			} else {
				// on a des attributs dans la balise body.
				// si c'est du LS ca va faire planter le desk
				// on traite ce cas séparément
				preg_match("`<body(.*)[^?]>`", $text, $res);
				$bodystarttag = strpos($text, "<body");
				$bodyendtag = $bodystarttag + strlen($res[0]);
			}
			if($bodyendtag) {
				$text = substr_replace($text, $deskbegin, $bodyendtag, 0);
				unset($desk);
				$len = strlen($text) - 30; // optimise a little bit the search
				if ($len < 0)
					$len = 0;
				$endbody = strpos($text, "</body", $len);
				if ($endbody === false)
					$endbody = strpos($text, "</body", 0);
				$text = substr_replace($text, $deskend, $endbody, 0);
			}

 		}
 	}

	#function prefixTableNameRef(&$table) { 
	#  $table2=$this->prefixTableName($table); $table=$table2; // necessaire de passer par table2... sinon ca crash PHP
	#}
	function prefixTableName($table)
	{
		global $home;
		static $tablefields;
		if (!$tablefields) {
			require ('tablefields.php');
		}

		if (preg_match("/\b((?:\w+\.)?\w+)(\s+as\s+\w+)\b/i", $table, $result))	{
			$table = $result[1];
			$alias = $result[2];
		}
		if (preg_match("/\b(\w+\.)(\w+)\b/", $table, $result)) {
			$table = $result[2];
			$dbname = $result[1];
			if ($dbname == "lodelmain.") {
				$dbname = DATABASE.".";
			}
		}

		$prefixedtable = lq("#_TP_".$table);
		if ($tablefields[$prefixedtable] && ($dbname == "" || $dbname == $GLOBALS['currentdb'].".")) {
			return $prefixedtable.$alias;
		} elseif ($tablefields[lq("#_MTP_".$table)] && ($dbname == "" || $dbname == DATABASE.".")) {
			return lq("#_MTP_".$table).$alias;
		}	else {
			return $dbname.$table.$alias;
		}

	}

	function sqlsplit($sql)
	{
		$inquote = false;
		$inphp = false;
		$n = strlen($sql);
		$arr = array ();
		$ind = 0;
		for ($i = 0; $i < $n; $i ++) {
			$c = $sql {
				$i};
			#echo $c=='"';
			if (!$escaped) {
				if ($c == '"') {
					$inphp = !$inphp;
					if (!$inquote) {
						$ind ++;
					}

				}	elseif ($c == "'" && !$inphp)	{
					$inquote = !$inquote;
					$ind ++;
				}
			}
			$escaped = $c == "\\" && !$escaped;
			$arr[$ind] .= $c;
		}
		if ($inphp || $inquote) {
			$this->errmsg("incorrect quoting");
		}
		return $arr;
	}

} // end of the class LodelParser

function protect(& $sql, $table, $fields)
{
	foreach ($sql as $k => $v) {
		$n = count($v);
		for ($i = 0; $i < $n; $i += 2) {
			$sql[$k][$i] = preg_replace("/\b(?<!\.)($fields)\b/", "$table.\\1", $v[$i]);
		}
	}
}

function preg_match_sql($find, $arr)
{
	$n = count($arr);
	for ($i = 0; $i < $n; $i += 2) {
		if (preg_match($find, $arr[$i])) {
			return true;
		}
	}
	return false;
}

function preg_replace_sql($find, $rpl, & $arr)
{
	$n = count($arr);
	for ($i = 0; $i < $n; $i += 2) {
		$arr[$i] = preg_replace($find, $rpl, $arr[$i]);
	}
}

function typestable($classtype)
{
	switch ($classtype) {
	case "entities" :
		return "types";
	case "entries" :
		return "entrytypes";
	case "persons" :
		return "persontypes";
	}
}
?>
