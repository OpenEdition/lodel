<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

// traitement particulier des attributs d'une loop
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//

require_once("func.php");
require_once("balises.php");
require_once("parser.php");


class LodelParser extends Parser {
  var $filterfunc_loaded=FALSE;


  var $textstatus=array("-1"=>"à traduire","1"=>"à revoir","2"=>"traduit");
  var $colorstatus=array("-1"=>"red","1"=>"orange",2=>"green");
  var $translationlanglist="'fr','en','es','de'";


  function LodelParser() { // constructor
    $this->Parser();
    $this->commands[]="TEXT"; // catch the text
    $this->variablechar="@"; // catch the @


    if (DBDRIVER=="mysql") {
      $this->codepieces['sqlquery']="mysql_query(lq(%s))";
    } else {    // call the ADODB driver
      die("DBDRIVER not supported currently for the parser");
      $this->codepieces['sqlerror']="or die($GLOBALS[db]->errormsg())";
      $this->codepieces['sqlquery']="$GLOBALS[db]->execute(%s)";
      $this->codepieces['sqlfetch']="";
    }
  }



function parse_loop_extra(&$tables,
			  &$tablesinselect,&$extrainselect,
			  &$selectparts)

{
  global $site,$home,$db;
  static $tablefields; // charge qu'une seule fois
  if (!$tablefields) require("tablefields.php");


  // split the SQL parts into quoted/non-quoted par
  foreach($selectparts as $k=>$v) {
    $selectparts[$k]=$this->sqlsplit($v);
  }

  $where=&$selectparts['where'];

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
    
    $dao=&getDAO("classes");
    $classes=$dao->findMany("classtype='entities'");
    foreach ($classes as $class) {
      // gere les tables principales liees a entites
      $ind=array_search($class->class,$tables);
      if ($ind!==FALSE && $ind!==NULL) {
	array_push($tables,"entities");
	// put entites just after the class table
	array_splice($tablesinselect,$ind+1,0,"entities");
	protect($selectparts,$class,"title");
	$where[count($where)-1].=" AND ".$class->class.".identity=entities.id AND class=";
	$where[]="'".$class->class."'"; // quoted part
	$where[]="";
      }
    }
  }

  if (in_array("entities",$tables)) {
    if (preg_match_sql("/\bclass\b/",$where) || preg_match_sql("/\btype\b/",$where)) {
      array_push($tables,"types");
      protect($selectparts,"entities","id|status|rank");
      $jointypesentitiesadded=1;
      $where[count($where)-1].=" AND entities.idtype=types.id";
    }
    if (preg_match_sql("/\bparent\b/",$where)) {
      array_push($tables,"entities as entities_interne2");
      protect($selectparts,"entities","id|idtype|identifier|usergroup|iduser|rank|status|idparent|creationdate|modificationdate|g_title");
      $where=preg_replace_sql("/\bparent\b/","entities_interne2.identifier",$where)." AND entities_interne2.id=entities.idparent";
    }
    if (in_array("types",$tables)) { // compatibilite avec avant... et puis c est pratique quand meme.
      $extrainselect.=", types.type , types.class";
    }
  }// fin de entities

  // verifie le status
  if (!preg_match_sql("/\bstatus\b/i",$where)) { // test que l'element n'est pas a la poubelle
    $teststatus=array();
    foreach ($tables as $table) {
      
      $realtable=$this->prefixTableName($table);
      if ($tablefields[$realtable] &&
	  !in_array("status",$tablefields[$realtable])) continue;
      if ($realtable=="session") continue;

      if ($realtable=="entities") {
	$lowstatus='"-64".($GLOBALS[lodeluser][admin] ? "" : "*('.$table.'.usergroup IN ($GLOBALS[lodeluser][groups]))")';
      } else {
	$lowstatus="-64";
      }
      $where[count($where)-1].=" AND ($table.status>\".(\$GLOBALS[lodeluser][visitor] ? $lowstatus : \"0\").\")";
    }
  }
#  echo "where 2:",htmlentities($where),"<br>";

    if ($site) {
      ///////// CODE SPECIFIQUE -- gere les tables croisees
      //
      // les regexp ci-dessous sont insuffisantes, il faudrait tester que ce n'est pas dans une zone quotee de la clause where !!!!
      //

      // persons and entries
      foreach(array("persons"=>"persontypes",
		    "entries"=>"entrytypes") as $table=>$typetable) {
	if (in_array($table,$tables)) {
	  // fait ca en premier
	  if (preg_match_sql("/\b(iddocument|identity)\b/",$where)) {
	    // on a besoin de la table croisee entities_persons
	    $alias="entities_".$table."_internal"; // use alias for security
	    array_push($tables,"relations as ".$alias); ###,"entities_persons");
	    $where=preg_replace("/\b(iddocument|identity)\b/",$alias.".id1",$where);
	    $where[count($where)-1].=" AND $alias.id2=$table.id";
	  }

	  if (preg_match_sql("/\b(type|g_type)\b/",$where)) {
	    protect($selectparts,$table,"id|status|rank");
	    array_push($tables,$typetable);
	    $where[count($where)-1].=" AND ".$table.".idtype=".$typetable.".id";
	  }
	}
      }

      foreach(array("persons"=>"idpersonne|idperson",
		    "entries"=>"identree|identry") as $table=>$regexp) {
	if (in_array("entities",$tables) && preg_match_sql("/\b($regexp)\b/",$where)) {
	// on a besoin de la table croise entites_personnes
	  $alias="entities_".$table."_internal2"; // use alias for security
	  array_push($tables,"relations as ".$alias);
	  preg_replace("/\b($re)\b/",$alias.".id2",$where);
	  $where[count($where)-1].=" AND $alias.id1=entities.id";
	}
      }

      if (in_array("usergroups",$tables) && preg_match_sql("/\biduser\b/",$where)) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"users_usergroups");
	$where[count($where)-1].=" AND idgroup=usergroups.id";
      }
      if (in_array("users",$tables) && in_array("session",$tables)) {
	$where[count($where)-1].=" AND iduser=users.id";
      }
     // entrees

    } // site

  // join the SQL parts
  foreach($selectparts as $k=>$v) {
    if (is_array($v)) $selectparts[$k]=join("",$v);
  }
  

  $selectparts['where']=lq($selectparts['where']);
  $extrainselect=lq($extrainselect);

#print_r($tables);

  array_walk($tables,"prefixTableNameRef");
  array_walk($tablesinselect,"prefixTableNameRef");
#print_r($tables);
#echo "--<br>";
}


//
// Traitement special des variables
//


function parse_variable_extra ($prefix,$varname)

{
  // VARIABLES SPECIALES
  //
  if ($prefix=="#") {
    if ($varname=="GROUPRIGHT") {
      return '($GLOBALS[right][admin] || in_array($context[group],explode(\',\',$GLOBALS[lodeluser][groups])))';
    }
    if (preg_match("/^OPTION[_.]/",$varname)) { // options
      return "getoption('".strtolower(substr($varname,7))."')";
    }
  }

  if ($prefix=="@") {
    $dotpos=strpos($varname,".");
    if ($dotpos) {
      $name=substr($varname,$dotpos+1);
      $group=substr($varname,0,$dotpos);
    } else {
      $name=$varname;
      $group="site";
    }
    return $this->maketext($name,$group,"@");
  }
  return FALSE;
}



//
// fonction qui gere les decodage du contenu des differentes parties
// d'une loop (DO*)
// fonction speciale pour lodel 
//


function decode_loop_content_extra ($balise,&$content,&$options,$tables)

{
  global $home;

  $havepublications=in_array("publications",$tables);
  $havedocuments=in_array("documents",$tables);
  //
  // est-ce qu'on veut le prev et next publication ?
  //

  // desactive le 10/03/04
//  if ($havepublications && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$content[$balise])) {
//    $content["PRE_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication($context);';
//  }
  // les filtrages automatiques
  if ($havedocuments || $havepublications) {
    $options['sqlfetchassoc']='filtered_mysql_fetch_assoc($context,%s)';
    if (!$this->filterfunc_loaded) {
      $this->filterfunc_loaded=TRUE;
      $this->fct_txt.='if (!(@include_once("CACHE/filterfunc.php"))) require_once("filterfunc.php");';
    }
  }
}


 function parse_TEXT()

 {
   $attr=$this->_decode_attributs($this->arr[$this->ind+1]);

   if (!$attr['NAME']) $this->errmsg("ERROR: The TEXT tag has no NAME attribute");
   $name=addslashes(stripslashes(trim($attr['NAME'])));

   $group=addslashes(stripslashes(trim($attr['GROUP'])));
   if (!$group) $group="site";

   $this->_clearposition();
   $this->arr[$this->ind]=$this->maketext($name,$group,"text");
 }

function maketext($name,$group,$tag)

{
  global $db;

  $name=strtolower($name);
  $group=strtolower($group);

  if ($GLOBALS['righteditor']) {       // cherche si le texte existe
    require_once(TOINCLUDE."connect.php");

    if ($group!="site") {
      usemaindb();
      $prefix=lq("#_MTP_");
    } else {
      $prefix=lq("#_TP_");
    }
    $textexists=$db->getOne("SELECT 1 FROM ".$prefix."texts WHERE name='$name' AND textgroup='$group'");
    if ($db->errorno()) dberror();
    if (!$textexists) { // text does not exists. Have to create it.
      $lang=$GLOBALS['lodeluser']['lang'] ? $GLOBALS['lodeluser']['lang'] : ""; // unlikely useful but...
      $db->execute("INSERT INTO ".$prefix."texts (name,textgroup,contents,lang) VALUES ('$name','$group','','$lang')") or $this->errmsg ($db->errormsg());
    }
    if ($group!="site") usecurrentdb();
  }

  $fullname=$group.'.'.$name;
  $this->translationtags[]="'".$fullname."'"; // save all the TEXT for the CACHE

  if ($tag=="text") {
    // modify inline
    $modifyif='$context[\'righteditor\']';
    if ($group=='interface') $modifyif.=' && $context[\'lodeluser\'][\'translationmode\']';

    $modify=' if ('.$modifyif.') { ?><a href="'.SITEROOT.'lodel/admin/index.php?do=edit&lo=texts&id=<?php echo $id; ?>">[M]</a> <?php if (!$text) $text=\''.$name.'\';  } ';

    return '<?php getlodeltext("'.$name.'","'.$group.'",$id,$text,$status);'.$modify.
      ' echo preg_replace("/(\r\n?\s*){2,}/","<br />",$text); ?>';
  } else {
    // modify at the end of the file
    ##$modify=' if ($context[\'lodeluser\'][\'translationmode\'] && !$text) $text=\'@'.strtoupper($name).'\'; ';
    $modify="";
    if (!$this->translationform[$fullname]) { // make the modify form
      $this->translationform[$fullname]='<?php mkeditlodeltext("'.$name.'","'.$group.'"); ?>';
    }
    return 'getlodeltextcontents("'.$name.'","'.$group.'")';
  }
}


function parse_after(&$text)

{
  // add the translation system when in translation mode
  if ($this->translationform) {
    // add the translations form before the body    
    $closepos=strpos($text,"</body>");
    if ($closepos===false) return; // no idea what to do...

    $code='<?php if ($context[\'lodeluser\'][\'translationmode\']) { require_once("translationfunc.php"); mkeditlodeltextJS(); ?>
<form method="post" action="index.php"><input type="hidden" name="edit" value="1">
<input type="hidden" name="do" value="edit">
<input type="hidden" name="lo" value="texts">
<input type="submit" value="[Update]">
<div id="translationforms">'.join("",$this->translationform).'</div>
<input type="submit" value="[Update]"></form>
<?php } ?>';

    $text=substr_replace($text,$code,$closepos,0);
  }
  if ($this->translationtags) {
    // add the code for the translations
##    $text='<'.'?php
##  $langfile="CACHE/lang/".$GLOBALS[\'la\']."/".basename(__FILE__);
##  $maj="CACHE/langmaj"; if (defined("SITEROOT")) $maj=SITEROOT.$maj;
##  if (myfilemtime($maj)>=myfilemtime($langfile)) {
##    generateLangCache($GLOBALS[\'la\'],$langfile,array('.join(",",$this->translationtags).'));
##  } else {
##    require_once($langfile);
##  }
##?'.'>
##'.$text;
    $text='<'.'?php
  $langfile="CACHE/lang-".$GLOBALS[\'la\']."/".basename(__FILE__);
  if (!file_exists($langfile)) {
    generateLangCache($GLOBALS[\'la\'],$langfile,array('.join(",",$this->translationtags).'));
  } else {
    require_once($langfile);
  }
?'.'>
'.$text;
  }

  //


  // add the code for the desk
  if (!$GLOBALS['nodesk']) {
    $desk='<'.'?php if ($GLOBALS[\'lodeluser\'][\'visitor\'] || $GLOBALS[\'lodeluser\'][\'adminlodel\']) { // insert the desk
    calcul_page($context,"desk","",$GLOBALS[\'home\']."../tpl/");
  } else {
  } ?'.'>';

    $bodystarttag=strpos($text,"<body");
    if ($bodystarttag!==false) {
      $bodyendtag=strpos($text,">",$bodystarttag);
      $text=substr_replace($text,$desk.'<div id="lodel-container">',$bodyendtag+1,0);
      unset($desk);
      $len=strlen($text)-30; // optimise a little bit the search
      if ($len<0) $len=0;
      $endbody=strpos($text,"</body",$len);
      if ($endbody===false) $endbody=strpos($text,"</body",0);
      $text=substr_replace($text,'</div>',$endbody,0); 
    }
  }
}



function prefixTableName($table)

{
  global $home;
  static $tablefields;
  if (!$tablefields) require("tablefields.php");

  if (preg_match("/\b((?:\w+\.)?\w+)(\s+as\s+\w+)\b/i",$table,$result)) {
    $table=$result[1];
    $alias=$result[2];
  }
  if (preg_match("/\b(\w+\.)(\w+)\b/",$table,$result)) {
    $table=$result[2];
    $dbname=$result[1];
    if ($dbname=="lodelmain.") $dbname=DATABASE.".";
  }

  $prefixedtable=lq("#_TP_".$table);
  if ($tablefields[$prefixedtable] && ($dbname=="" || $dbname==$GLOBALS['currentdb'].".")) {
    return $prefixedtable.$alias;
  } elseif ($tablefields[lq("#_MTP_".$table)] && ($dbname=="" || $dbname==DATABASE.".")) {
    return lq("#_MTP_".$table).$alias;
  } else {
    return $dname.$table.$alias;
  }
   
  //if (!SINGLESITE && ($table==lq("#_MTP_sites") || $table==lq("#_MTP_session"))) {
  // $table=DATABASE.".".$table;
  //
}


function sqlsplit($sql)

{
  $inquote=false;
  $inphp=false;
  $n=strlen($sql);
  $arr=array();
  $ind=0;

  for ($i=0; $i < $n; $i++ ) {
    $c=$sql{$i};
    #echo $c=='"';

    if (!$escaped) {
      if ($c=='"') {
	$inphp=!$inphp;
	if (!$inquote) $ind++;

      } elseif ($c=="'" && !$inphp) {
	$inquote = !$inquote;
	$ind++;
      }
    }
    $escaped = $c=="\\" && !$escaped;
    $arr[$ind].=$c;
  }
  if ($inphp || $inquote) $this->errmsg("incorrect quoting");
  #print_r($arr);
  return $arr;
}



} // end of the class LodelParser


function prefixTableNameRef(&$table) { $table=LodelParser::prefixTableName($table); }

/**
 * Function to prefix automatically the name of a table.
 * sera tablefields for this.
 */


// prefix les tables si necessaire
/*
function prefix_tablename ($tablename)

{
#ifndef LODELLIGHT
  return $tablename;
#else
#  preg_match_all("/\b(\w+)\./",$tablename,$result);
#  foreach ($result[1] as $tbl) {
#    $tablename=preg_replace ("/\b$tbl\./",#_TP_.$tbl.".",$tablename);
#  }
#  //  echo $tablename,"<br>";
#  return $tablename;
#endif
}
*/



function protect (&$sql,$table,$fields)

{
  foreach ($sql as $k=>$v) {
    $n=count($v);
    for($i=0;$i<$n;$i+=2) {
      $sql[$k][$i]=preg_replace("/\b(?<!\.)($fields)\b/","$table.\\1",$v[$i]);
    }
  }
}

function preg_match_sql($find,$arr)

{
  $n=count($arr);
  for($i=0;$i<$n;$i+=2) {
    if (preg_match($find,$arr[$i])) return true;
  }
  return false;
}


function preg_replace_sql($find,$rpl,$arr)

{
  $n=count($arr);
  for($i=0;$i<$n;$i+=2) {
    preg_replace($find,$rpl,$arr[$i]);
  }
}



?>
