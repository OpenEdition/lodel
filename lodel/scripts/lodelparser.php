<?php
// traitement particulier des attributs d'une loop
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//

require_once($home."func.php");
require_once($home."balises.php");
require_once($home."parser.php");


class LodelParser extends Parser {
  var $filterfunc_loaded=FALSE;


function parse_loop_extra(&$tables,
			    &$tablesinselect,&$extrainselect,
			    &$where,&$ordre,&$groupby)

{
  global $site;

  // convertion des code specifique dans le where
  // ce bout de code depend du parenthesage et du trim fait dans parse_loop.
  $where=preg_replace (array(
		    "/\(trash\)/i",
		    "/\(ok\)/i",
		    "/\(okgroup\)/i"
		    ),
	      array(
		    "statut<=0",
		    "statut>0",
		    '".($GLOBALS[admin] ? "1" : "(groupe IN ($GLOBALS[usergroupes]))")."'
		    ),$where);
  //

  // gere les tables principales liees a entites
  if (in_array("documents",$tables)) {
    array_push($tables,"entites");
    array_push($tablesinselect,"entites");
    $where.=" AND $GLOBALS[tp]documents.identite=$GLOBALS[tp]entites.id AND classe='documents'";
  }
  if (in_array("publications",$tables)) {
    array_push($tables,"entites");
    array_push($tablesinselect,"entites");
    $where.=" AND $GLOBALS[tp]publications.identite=$GLOBALS[tp]entites.id AND classe='publications'";
  }
#  echo "where 1:",htmlentities($where),"<br>";
  if (in_array("entites",$tables)) {
    if (preg_match("/\bclasse\b/",$where)) {
      array_push($tables,"types");
      protect2($where,$ordre,"entites","id|statut|ordre");
      $jointypesentitesadded=1;
      $where.=" AND $GLOBALS[tp]entites.idtype=$GLOBALS[tp]types.id";
      ## c'est inutile pour le moment: preg_replace("/\bclasse\b/","$GLOBALS[tp]types.classe",$where).
    }
#  echo "where 1bis:",htmlentities($where),"<br>";
    if (!$jointypesentitesadded && preg_match("/\btype\b/",$where)) {
      array_push($tables,"types");
      protect2($where,$ordre,"entites","id|statut|ordre");
      $where.=" AND $GLOBALS[tp]entites.idtype=$GLOBALS[tp]types.id";
    }
    if (preg_match("/\bparent\b/",$where)) {
      array_push($tables,"entites as entites_interne2");
      protect2($where,$ordre,"entites","id|idtype|nom|groupe|user|ordre|statut");
      $where=preg_replace("/\bparent\b/","entites_interne2.nom",$where)." AND entites_interne2.id=$GLOBALS[tp]entites.idparent";
    }
    if (in_array("types",$tables)) { # compatibilite avec avant... et puis c'est pratique quand meme.
      $extrainselect.=", $GLOBALS[tp]types.type , $GLOBALS[tp]types.classe";
    }
  }// fin de entites

  // verifie le statut
  if (!preg_match("/\bstatut\b/i",$where)) { // test que l'element n'est pas a la poubelle
    $teststatut=array();
    if ($where) array_push($teststatut,$where);
    foreach ($tables as $table) {
      if (preg_match("/\sas\s+(\w+)/",$table,$result)) $table=$result[1];
      if ($table=="session" || 
	  $table=="documents" || 
	  $table=="publications"||
	  $table=="relations") continue;

      if ($table=="entites") {
	$lowstatut='"-64".($GLOBALS[admin] ? "" : "*('.$GLOBALS[tp].$table.'.groupe IN ($GLOBALS[usergroupes]))")';
      } else {
	$lowstatut="-64";
      }
      array_push($teststatut,"($GLOBALS[tp]$table.statut>\".(\$GLOBALS[visiteur] ? $lowstatut : \"0\").\")");
    }
    $where=join(" AND ",$teststatut);
  }
#  echo "where 2:",htmlentities($where),"<br>";

    if ($site) {
      ///////// CODE SPECIFIQUE -- gere les tables croisees
      if (in_array("taches",$tables) && in_array("publications",$tables)) $where.=" AND publication=r2r_publications.id";

#      if (in_array("documents",$tables) && in_array("publications",$tables)) {
#	$where.=" AND publication=$GLOBALS[tp]publications.id";
#      }
      //
      // les regexp ci-dessous sont insuffisantes, il faudrait tester que ce n'est pas dans une zone quotee de la clause where !!!!
      //

      // auteurs
     if (in_array("personnes",$tables)) {
       // fait ca en premier
       if (preg_match("/\biddocument\b/",$where)) {
	 // on a besoin de la table croisee entites_personnes
	 array_push($tables,"entites_personnes");
	 array_push($tablesinselect,"entites_personnes"); // on veut aussi recuperer les infos qui viennent de cette table
	 $where=preg_replace("/\biddocument\b/","identite",$where);
	 $where.=" AND idpersonne=$GLOBALS[tp]personnes.id";
       }
       if (preg_match("/\btype\b/",$where)) {
	 protect2($where,$ordre,"personnes","id|statut");
	 protect2($where,$ordre,"entites_personnes","ordre");
	 array_push($tables,"typepersonnes");
	 // maintenant, il y a deux solutuions
	 if (!in_array("entites_personnes",$tables)) { // s'il n'y a pas cette table ca veut dire qu'on veut juste savoir s'il y a au moins une entree, donc il faut faire le groupeby.
	   array_push($tables,"entites_personnes");
	   $groupby.=" $GLOBALS[tp]entites_personnes.idpersonne";
	 }
	 $where.=" AND $GLOBALS[tp]entites_personnes.idtype=$GLOBALS[tp]typepersonnes.id AND $GLOBALS[tp]entites_personnes.idpersonne=$GLOBALS[tp]personnes.id";
       }
     }
     if (in_array("entites",$tables) && preg_match("/\bidpersonne\b/",$where)) {
	// on a besoin de la table croise entites_personnes
	array_push($tables,"entites_personnes");
	$where.=" AND $GLOBALS[tp]entites_personnes.identite=$GLOBALS[tp]entites.id";
     }
     // entrees
     if (in_array("entrees",$tables)) {
	if (preg_match("/\btype\b/",$where)) {
	  protect ($where,"entrees","id|statut|ordre");
	  protect ($ordre,"entrees","id|statut|ordre");
	  array_push($tables,"typeentrees");
	  $where.=" AND $GLOBALS[tp]entrees.idtype=$GLOBALS[tp]typeentrees.id";
	}
	if (preg_match("/\biddocument\b/",$where)) {
	  // on a besoin de la table croise entites_entrees
	  array_push($tables,"entites_entrees");
	  $where=preg_replace("/\biddocument\b/","identite",$where);
	  $where.=" AND identree=$GLOBALS[tp]entrees.id";
	}
      }
      if (in_array("entites",$tables) && preg_match("/\bidentree\b/",$where)) {
	// on a besoin de la table croise entites_entrees
	array_push($tables,"entites_entrees");
	$where.=" AND $GLOBALS[tp]entites_entrees.identite=$GLOBALS[tp]entites.id";
      }
      if (in_array("groupes",$tables) && preg_match("/\biduser\b/",$where)) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"users_groupes");
	$where.=" AND idgroupe=$GLOBALS[tp]groupes.id";
      }
      if (in_array("users",$tables) && in_array("session",$tables)) {
	$where.=" AND iduser=$GLOBALS[tp]users.id";
      }
      if (in_array("champs",$tables) && preg_match("/\bclasse\b/",$where)) {
	// on a besoin de la table croise groupesdechamps
	protect2($where,$ordre,"champs","id|statut|ordre");
	array_push($tables,"groupesdechamps");
	$where.=" AND $GLOBALS[tp]groupesdechamps.id=$GLOBALS[tp]champs.idgroupe";
	$extrainselect.=", $GLOBALS[tp]groupesdechamps.classe";
     }
     // entrees

    } // site

    array_walk($tables,"prefixtablesindatabase");
    array_walk($tablesinselect,"prefixtablesindatabase");
}


//
// Traitement special des variables
//


function parse_variable_extra ($nomvar)

{
  // VARIABLES SPECIALES
  //
  if ($nomvar=="OKGROUPE") {
    return '($GLOBALS[admin] || in_array($context[groupe],split(",",$GLOBALS[usergroupes])))';
  }
  return FALSE;
}



//
// fonction qui gere les decodage du contenu des differentes parties
// d'une loop (DO*)
// fonction speciale pour lodel 
//


function decode_loop_content_extra ($balise,$tables,&$ret)

{
  global $home;

  $havepublications=in_array("publications",$tables);
  $havedocuments=in_array("documents",$tables);
  //
  // est-ce qu'on veut le prev et next publication ?
  //
  if ($havepublications && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$ret[$balise])) {
    $ret["PRE_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication(&$context);';
  }
  // les filtrages automatiques
  if ($havedocuments || $havepublications) {
    $ret[fetch_assoc_func]="filtered_mysql_fetch_assoc";
    if (!$this->filterfunc_loaded) {
      $this->filterfunc_loaded=TRUE;
      $this->fct_txt.='if (!(@include_once("CACHE/filterfunc.php"))) require_once($GLOBALS[home]."filterfunc.php");';
    }
  }
}

/*
  global $home,$balisesdocument_lieautexte,$balisesdocument_nonlieautexte;

  //
  // est-ce qu'on veut le texte ?
  //
  if (in_array("documents",$tables)) {
# as-t-on besoin des balises liees au texte ?
    if (preg_match_all("/\[\(?#(".join("|",$balisesdocument_lieautexte).")\b/i",$ret[$balise],$result,PREG_PATTERN_ORDER)) {
      $withtextebalises='"'.strtolower(join('","',$result[1])).'"';
    } else {
      $withtextebalises="";
    }

# as-t-on besoin de balises non liees au texte
    if (preg_match_all("/\[\(?#(".join("|",$balisesdocument_nonlieautexte).")\b/i",$ret[$balise],$result,PREG_PATTERN_ORDER))  {
      $ret["PRE_".$balise]='$filename="lodel/txt/r2r-$context[id].xml";
if (file_exists($filename)) {
include_once ("$GLOBALS[home]/xmlfunc.php");
$text=join("",file($filename));
$arr=array("'.strtolower(join('","',$result[1])).'");';
      if ($withtextebalises) { // on a aussi besoin des balises liees au texte
	$ret["PRE_".$balise].='if ($context[textepublie] || $GLOBALS[visiteur]) array_push ($arr,'.$withtextebalises.');';
      }
      $ret["PRE_".$balise].='$context=array_merge($context,extract_xml($arr,$text)); }';
    } elseif ($withtextebalises) { // les balises liees au texte seulement... ca permet d'optimiser un minimum. On evite ainsi d'appeler le parser xml quand le texte n'est pas publie.
      $ret["PRE_".$balise]='if ($context[textepublie] || $GLOBALS[visiteur]) {
$filename="lodel/txt/r2r-$context[id].xml";
if (file_exists($filename)) {
include_once ("$GLOBALS[home]/xmlfunc.php");
$text=join("",file($filename));
$context=array_merge($context,extract_xml(array('.$withtextebalises.'),$text));
}}';
    }
  } // table documents ?
*/
// fin fonction decode_content_extra

}


function prefixtablesindatabase(&$table) {
  if ($table=="sites" || $table=="session") $table=$GLOBALS[database].".".$table;
}


// prefix les tables si necessaire

function prefix_tablename ($tablename)

{
#ifndef LODELLIGHT
  return $tablename;
#else
#  preg_match_all("/\b(\w+)\./",$tablename,$result);
#  foreach ($result[1] as $tbl) {
#    $tablename=preg_replace ("/\b$tbl\./",$GLOBALS[tp].$tbl.".",$tablename);
#  }
#  //  echo $tablename,"<br>";
#  return $tablename;
#endif
}

function protect2(&$sql1,&$sql2,$table,$fields)

{
  protect(&$sql1,$table,$fields);
  protect(&$sql2,$table,$fields);
}

function protect (&$sql,$table,$fields)

{
  // regarde s'il y a des champs, deja
  if (!preg_match("/\b(?<!\\.)($fields)\b/",$sql)) return;

  // separe la chaine par les quotes qui ne sont pas escapes. 
  // ajoute un espace au debut pour des raisons de facilite
  $arr=preg_split("/(?<!\\\)'/",$sql);
  for($i=0;$i<count($arr);$i+=2)
    $arr[$i]=preg_replace("/\b(?<![\\.[])($fields)\b/","$GLOBALS[tp]$table.\\1",$arr[$i]);
  $sql=join("'",$arr);
}
    

?>
