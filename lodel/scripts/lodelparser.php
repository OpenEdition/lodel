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

require_once($home."func.php");
require_once($home."balises.php");
require_once($home."parser.php");


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
			  &$select,&$where,&$rank,&$groupby,&$having)

{
  global $site,$home;
  static $tablefields; // charge qu'une seule fois

  // convertion des codes specifiques dans le where
  // ce bout de code depend du parenthesage et du trim fait dans parse_loop.
  $where=preg_replace (array(
		    "/\(trash\)/i",
		    "/\(ok\)/i",
		    "/\(rightgroup\)/i"
		    ),
	      array(
		    "status<=0",
		    "status>0",
		    '".($GLOBALS[user][admin] ? "1" : "(usergroup IN ($GLOBALS[user][groups]))")."'
		    ),$where);
  //

  $classes=array("documents","publications");
  foreach ($classes as $class) {
    // gere les tables principales liees a entites
    $ind=array_search("#_TP_$class",$tables);
    if ($ind!==FALSE && $ind!==NULL) {
      array_push($tables,"#_TP_entities");
      // put entites just after the class table
      #print_r($tablesinselect);
      array_splice($tablesinselect,$ind+1,0,"#_TP_entities");
      #print_r($tablesinselect);
      protect5($select,$where,$rank,$groupby,$having,"#_TP_$class","title");

      $where.=" AND #_TP_$class.identity=#_TP_entities.id AND class='$class'";
    }
  }
#  echo "where 1:",htmlentities($where),"<br>";
  if (in_array("#_TP_entities",$tables)) {
    if (preg_match("/\bclass\b/",$where)) {
      array_push($tables,"#_TP_types");
      protect5($select,$where,$rank,$groupby,$having,"#_TP_entities","id|status|rank");
      $jointypesentitiesadded=1;
      $where.=" AND #_TP_entities.idtype=#_TP_types.id";
      ## c'est inutile pour le moment: preg_replace("/\bclass\b/","#_TP_types.class",$where).
    }
#  echo "where 1bis:",htmlentities($where),"<br>";
    if (!$jointypesentitiesadded && preg_match("/\btype\b/",$where)) {
      array_push($tables,"#_TP_types");
      protect5($select,$where,$rank,$groupby,$having,"#_TP_entities","id|status|rank");
      $where.=" AND #_TP_entities.idtype=#_TP_types.id";
    }
    if (preg_match("/\bparent\b/",$where)) {
      array_push($tables,"#_TP_entities as entities_interne2");
      protect5($select,$where,$rank,$groupby,$having,"#_TP_entities","id|idtype|identifier|groupe|user|rank|status|idparent");
      $where=preg_replace("/\bparent\b/","entities_interne2.identifier",$where)." AND entities_interne2.id=#_TP_entities.idparent";
    }
    if (in_array("#_TP_types",$tables)) { # compatibilite avec avant... et puis c'est pratique quand meme.
      $extrainselect.=", #_TP_types.type , #_TP_types.class";
    }
  }// fin de entities

  // verifie le status
  if (!preg_match("/\bstatus\b/i",$where)) { // test que l'element n'est pas a la poubelle
    if (!$tablefields) require($home."tablefields.php");
    $teststatus=array();
    if ($where) array_push($teststatus,$where);
    foreach ($tables as $table) {
      if (preg_match("/\b(\w+)\s+as\s+(\w+)\b/i",$table,$result)) {
	$realtable=$result[2];
	$table=$result[1];
      } else {
	$realtable=$table;
      }
      if ($tablefields[$realtable] &&
	  !in_array("status",$tablefields[$realtable])) continue;
      if ($realtable==lq("#_TP_session")) continue;

      if ($realtable==lq("#_TP_entities")) {
	$lowstatus='"-64".($GLOBALS[user][admin] ? "" : "*('.$table.'.usergroup IN ($GLOBALS[user][groups]))")';
      } else {
	$lowstatus="-64";
      }
      array_push($teststatus,"($table.status>\".(\$GLOBALS[user][visitor] ? $lowstatus : \"0\").\")");
    }
    $where=join(" AND ",$teststatus);
  }
#  echo "where 2:",htmlentities($where),"<br>";

    if ($site) {
      ///////// CODE SPECIFIQUE -- gere les tables croisees
      //
      // les regexp ci-dessous sont insuffisantes, il faudrait tester que ce n'est pas dans une zone quotee de la clause where !!!!
      //

      // auteurs
     if (in_array("#_TP_persons",$tables)) {
       // fait ca en premier
       if (preg_match("/\b(iddocument|identity)\b/",$where)) {
	 // on a besoin de la table croisee entities_persons
	 array_push($tables,"#_TP_entities_persons");
	 array_push($tablesinselect,"#_TP_entities_persons"); // on veut aussi recuperer les infos qui viennent de cette table
	 $where=preg_replace("/\biddocument\b/","identity",$where);
	 $where.=" AND idperson=#_TP_persons.id";
       }
       if (preg_match("/\btype\b/",$where)) {
	 protect5($select,$where,$rank,$groupby,$having,"#_TP_persons","id|status");
	 protect5($select,$where,$rank,$groupby,$having,"#_TP_entities_persons","rank");
	 array_push($tables,"#_TP_persontypes");
	 // maintenant, il y a deux solutuions
	 if (!in_array("#_TP_entities_persons",$tables)) { // s'il n'y a pas cette table ca veut dire qu'on veut juste savoir s'il y a au moins une entree, donc il faut faire le groupeby.
	   array_push($tables,"#_TP_entities_persons");
	   $groupby.=" #_TP_entities_persons.idperson";
	 }
	 $where.=" AND #_TP_entities_persons.idtype=#_TP_persontypes.id AND #_TP_entities_persons.idperson=#_TP_persons.id";
       }
     }
     if (in_array("#_TP_entities",$tables) && preg_match("/\bidpersonne\b/",$where)) {
	// on a besoin de la table croise entites_personnes
	array_push($tables,"#_TP_entities_persons");
	$where.=" AND #_TP_entities_persons.identity=#_TP_entities.id";
     }
     // entrees
     if (in_array("#_TP_entries",$tables)) {
	if (preg_match("/\btype\b/",$where)) {
	  protect5($select,$where,$rank,$groupby,$having,"#_TP_entries","id|status|rank");
	  array_push($tables,"#_TP_entrytypes");
	  $where.=" AND #_TP_entries.idtype=#_TP_entrytypes.id";
	}
       if (preg_match("/\b(iddocument|identity)\b/",$where)) {
	  // on a besoin de la table croise entites_entrees
	  array_push($tables,"#_TP_entities_entries");
	  $where=preg_replace("/\biddocument\b/","identity",$where);
	  $where.=" AND identry=#_TP_entries.id";
	}
      }
      if (in_array("#_TP_entities",$tables) && preg_match("/\bidentree\b/",$where)) {
	// on a besoin de la table croise entites_entrees
	array_push($tables,"#_TP_entities_entries");
	$where.=" AND #_TP_entities_entries.identity=#_TP_entities.id";
      }
      if (in_array("#_TP_usergroups",$tables) && preg_match("/\biduser\b/",$where)) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"#_TP_users_usergroups");
	$where.=" AND idgroup=#_TP_usergroups.id";
      }
      if (in_array("#_TP_users",$tables) && in_array("#_TP_session",$tables)) {
	$where.=" AND iduser=#_TP_users.id";
      }
      if (in_array("#_TP_fields",$tables) && preg_match("/\bclass\b/",$where)) {
	// on a besoin de la table croise groupesdechamps
	protect5($select,$where,$rank,$groupby,$having,"#_TP_fields","id|status|rank");
	array_push($tables,"#_TP_fieldgroups");
	$where.=" AND #_TP_fieldgroups.id=#_TP_fields.idgroup";
	$extrainselect.=", #_TP_fieldgroups.class";
     }
     // entrees

    } // site

    array_walk($tables,"prefixtablesindatabase");
    array_walk($tablesinselect,"prefixtablesindatabase");
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
      return '($GLOBALS[right][admin] || in_array($context[group],explode(\',\',$GLOBALS[user][groups])))';
    }
    if (preg_match("/^OPTION[_.]/",$varname)) { // options
      return "getoption('".strtolower(substr($varname,7))."',$context)";
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
      $this->fct_txt.='if (!(@include_once("CACHE/filterfunc.php"))) require_once($GLOBALS[home]."filterfunc.php");';
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
    if ($db->errorno()) die($db->errormsg());
    if (!$textexists) { // text does not exists. Have to create it.
      $lang=$GLOBALS['user']['lang'] ? $GLOBALS['user']['lang'] : ""; // unlikely useful but...
      $db->execute("INSERT INTO ".$prefix."texts (name,textgroup,contents,lang) VALUES ('$name','$group','','$lang')") or $this->errmsg ($db->errormsg());
    }
    if ($group!="site") usecurrentdb();
  }

  if ($tag=="text") {
    // modify inline
    $modifyif='$context[\'righteditor\']';
    if ($group=='interface') $modifyif.=' && $context[\'usertranslationmode\']';

    $modify=' if ('.$modifyif.') { ?><a href="'.SITEROOT.'lodel/admin/text.php?id=<?php echo $id; ?>">[M]</a> <?php if (!$text) $text=\''.$name.'\';  } ';

    return '<?php list($id,$text)=getlodeltext("'.$name.'","'.$group.'");'.$modify.
      ' echo preg_replace("/(\r\n?\s*){2,}/","<br />",$text); ?>';
  } else {
    // modify at the end of the file
    ##$modify=' if ($context[\'usertranslationmode\'] && !$text) $text=\'@'.strtoupper($name).'\'; ';
    $modify="";
    $fullname=strtoupper($group).'.'.strtoupper($name);

    if (!$this->translationform[$fullname]) { // make the modify form
      $this->translationform[$fullname]='<?php mkeditlodeltext("'.$name.'","'.$group.'"); ?>';
    }
    return 'getlodeltextcontents("'.$name.'","'.$group.'")';
  }
}


function parse_after(&$text)

{
  if ($this->translationform) {
    // add the translations form before the body    
    $closepos=strpos($text,"</body>");
    if ($closepos===false) return; // no idea what to do...

    $code='<?php if ($context[\'usertranslationmode\']) { require_once($GLOBALS[home]."translationfunc.php"); mkeditlodeltextJS(); ?>
<form method="post" action="'.$GLOBALS['home'].'../../lodeladmin/text.php"><input type="hidden" name="edit" value="1">
 <input type="submit" value="[Update]">
<div id="translationforms">'.join("",$this->translationform).'</div>
<input type="submit" value="[Update]"></form>
<?php } ?>';

    $text=substr_replace($text,$code,$closepos,0);
  }
}


} // end of the class LodelParser


function prefixtablesindatabase(&$table) {
#  if (($GLOBALS[database]!=$GLOBALS[currentdb]) &&
#      ($table=="#_TP_sites" || 
#       $table=="#_TP_session" ||
#       $table=="#_TP_users")) {
#    $table=$GLOBALS[database].".".$table;
#  }
  if ((DATABASE!=$GLOBALS[currentdb]) &&
      ($table=="$GLOBALS[tp]sites" || 
       $table=="$GLOBALS[tp]session")) {
    $table=DATABASE.".".$table;
  }
}


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

function protect5(&$sql1,&$sql2,&$sql3,&$sql4,&$sql5,$table,$fields)

{
  protect($sql1,$table,$fields);
  protect($sql2,$table,$fields);
  protect($sql3,$table,$fields);
  protect($sql4,$table,$fields);
  protect($sql5,$table,$fields);
}


function protect (&$sql,$table,$fields)

{
  // regarde s'il y a des champs, deja
  if (!preg_match("/\b(?<!\\.)($fields)\b/",$sql)) return;

  // separe la chaine par les quotes qui ne sont pas escapes. 
  // ajoute un espace au debut pour des raisons de facilite
  $arr=preg_split("/(?<!\\\)'/",$sql);
  for($i=0;$i<count($arr);$i+=2)
    $arr[$i]=preg_replace("/\b(?<![\\.[])($fields)\b/","$table.\\1",$arr[$i]);
  $sql=join("'",$arr);
}
    

?>
