<?
//
// traitement particulier des attributs d'une boucle
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//


function parse_boucle_extra(&$tables,&$tablesinselect,&$where,&$ordre,&$groupby)

{
  global $revue;

  // convertion des code specifique dans le where
  // ce bout de code depend du parenthesage et du trim fait dans parse_boucle.
  $where=preg_replace (array(
		    "/\(trash\)/i",
		    "/\(ok\)/i",
		    "/\(okgroup\)/i"
		    ),
	      array(
		    "status<=0",
		    "status>0",
		    '".($GLOBALS[admin] ? "1" : "(groupe IN ($GLOBALS[usergroupes]))")."'
		    ),$where);
  //

  // verifie le status
  if (!preg_match("/\bstatus\b/i",$where)) { // test que l'element n'est pas a la poubelle
    $teststatus=array();
    if ($where) array_push($teststatus,$where);
    foreach ($tables as $table) {
      if ($table=="session") continue;
      if ($table=="documents" || $table=="publications") {
	$lowstatus='"-64".($GLOBALS[admin] ? "" : "*('.$GLOBALS[tableprefix].$table.'.groupe IN ($GLOBALS[usergroupes]))")';
      } else {
	$lowstatus="-64";
      }
      array_push($teststatus,"($GLOBALS[tableprefix]$table.status>\".(\$GLOBALS[visiteur] ? $lowstatus : \"0\").\")");
    }
    $where=join(" AND ",$teststatus);
  }


#ifndef LODELLIGHT
    if ($revue) {
#endif
      ///////// CODE SPECIFIQUE -- gere les tables croisees
      if (in_array("taches",$tables) && in_array("publications",$tables)) $where.=" AND publication=r2r_publications.id";

      if (in_array("documents",$tables) && in_array("publications",$tables)) {
	$where.=" AND publication=$GLOBALS[tableprefix]publications.id";
      }

#ifndef LODELLIGHT
      if (in_array("documentsannexes",$tables)) {
	$where=preg_replace(array("/\btype_lienfichier\b/i",
				  "/\btype_liendocument\b/i",
				  "/\btype_lienpublication\b/i",
				  "/\btype_lienexterne\b/i",
				  "/\btype_lieninterne\b/i"),
			    array("type='".TYPE_LIENFICHIER."'",
				  "type='".TYPE_LIENDOCUMENT."'",
				  "type='".TYPE_LIENPUBLICATION."'",
				  "type='".TYPE_LIENEXTERNE."'",
				  "(type='".TYPE_LIENDOCUMENT."' OR type='".TYPE_LIENPUBLICATION."')"),
			    $where);
      }

      //
      // les regexp ci-dessous sont insuffisantes, il faudrait tester que ce n'est pas dans une zone quotee de la clause where !!!!
      //

      // auteurs
     if (in_array("personnes",$tables)) {
       // fait ca en premier
       if (preg_match("/\biddocument\b/",$where)) {
	 // on a besoin de la table croisee documents_personnes
	 array_push($tables,"documents_personnes");
	 array_push($tablesinselect,"documents_personnes"); // on veut aussi recuperer les infos qui viennent de cette table
	 $where.=" AND idpersonne=$GLOBALS[tableprefix]personnes.id";
       }
       if (preg_match("/\btype\b/",$where)) {
	 protect2($where,$ordre,"personnes","id|status|nom");
	 protect2($where,$ordre,"documents_personnes","ordre");

	 array_push($tables,"typepersonnes");
	 // maintenant, il y a deux solutuions
	 if (!in_array("documents_personnes",$tables)) { // s'il n'y a pas cette table ca veut dire qu'on veut juste savoir s'il y a au moins une entree, donc il faut faire le groupeby.
	   array_push($tables,"documents_personnes");
	   $groupby.=" $GLOBALS[tableprefix]documents_personnes.idpersonne";
	 }
	 $where=preg_replace("/\btype\b/","$GLOBALS[tableprefix]typepersonnes.nom",$where)." AND $GLOBALS[tableprefix]documents_personnes.idtype=$GLOBALS[tableprefix]typepersonnes.id AND $GLOBALS[tableprefix]documents_personnes.idpersonne=$GLOBALS[tableprefix]personnes.id";
       }
     }
     if (in_array("documents",$tables) && preg_match("/\bidpersonne\b/",$where)) {
	// on a besoin de la table croise documents_personnes
	array_push($tables,"documents_personnes");
	$where.=" AND iddocument=documents.id";
     }
     // entrees
     if (in_array("entrees",$tables)) {
	if (preg_match("/\btype\b/",$where)) {
	  protect ($where,"entrees","id|status|nom|ordre");
	  protect ($ordre,"entrees","id|status|nom|ordre");
	  array_push($tables,"typeentrees");
	  $where=preg_replace("/\btype\b/","$GLOBALS[tableprefix]typeentrees.nom",$where)." AND $GLOBALS[tableprefix]entrees.idtype=$GLOBALS[tableprefix]typeentrees.id";
	}
	if (preg_match("/\biddocument\b/",$where)) {
	  // on a besoin de la table croise documents_entrees
	  array_push($tables,"documents_entrees");
	  $where.=" AND identree=$GLOBALS[tableprefix]entrees.id";
	}
      }
      if (in_array("documents",$tables) && preg_match("/\bidentree\b/",$where)) {
	// on a besoin de la table croise documents_entrees
	array_push($tables,"documents_entrees");
	$where.=" AND iddocument=$GLOBALS[tableprefix]documents.id";
      }
      if (in_array("groupes",$tables) && preg_match("/\biduser\b/",$where)) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"users_groupes");
	$where.=" AND idgroupe=$GLOBALS[tableprefix]groupes.id";
      }
      if (in_array("users",$tables) && in_array("session",$tables)) {
	$where.=" AND iduser=$GLOBALS[tableprefix]users.id";
      }
#ifndef LODELLIGHT
    } // revue
#endif
}



//
// Traitement special des variables
//


function parse_variable_extra ($nomvar)

{
  // VARIABLES SPECIALES
  //
  if ($nomvar=="OFFLINE") {
    return '($context[status]<0)';
  } elseif ($nomvar=="OKGROUPE") {
    return '($GLOBALS[admin] || in_array($context[groupe],split(",",$GLOBALS[usergroupes])))';
  }
  return FALSE;
}



//
// fonction qui gere les decodage du contenu des differentes parties
// d'une boucle (DO*)
// fonction speciale pour lodel 
//

include_once($home."balises.php");


function decode_content_extra ($balise,&$ret,$tables)

{
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
      $ret["EXTRACT_".$balise]='$filename="lodel/txt/r2r-$context[id].xml";
if (file_exists($filename)) {
include_once ("$GLOBALS[home]/xmlfunc.php");
$text=join("",file($filename));
$arr=array("'.strtolower(join('","',$result[1])).'");';
      if ($withtextebalises) { // on a aussi besoin des balises liees au texte
	$ret["EXTRACT_".$balise].='if ($context[textepublie] || $GLOBALS[visiteur]) array_push ($arr,'.$withtextebalises.');';
      }
      $ret["EXTRACT_".$balise].='$context=array_merge($context,extract_xml($arr,$text)); }';
    } elseif ($withtextebalises) { // les balises liees au texte seulement... ca permet d'optimiser un minimum. On evite ainsi d'appeler le parser xml quand le texte n'est pas publie.
      $ret["EXTRACT_".$balise]='if ($context[textepublie] || $GLOBALS[visiteur]) {
$filename="lodel/txt/r2r-$context[id].xml";
if (file_exists($filename)) {
include_once ("$GLOBALS[home]/xmlfunc.php");
$text=join("",file($filename));
$context=array_merge($context,extract_xml(array('.$withtextebalises.'),$text));
}}';
    }
  } // table documents ?


    //
    // est-ce qu'on veut le prev et next publication ?
    //
  if (in_array("publications",$tables) && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$ret[$balise])) {
    $ret["EXTRACT_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication(&$context);';
  }
}
// fin fonction decode_content_extra



//
// traitement particulier avant la creation du code de la boucle
// il est possible d'ajouter des instructions avant la requete 
// mysql $premysqlquery et apres $postmysqlquery
// il est aussi possible d'ajouter des champs dans le select $extrafield
//

function make_boucle_code_extra($tables)

{

#ifndef LODELLIGHT
  // gestion du changement de database
  if (in_array("revue",$tables) || in_array("session",$tables)) {
    $premysqlquery='mysql_select_db($GLOBALS[database]);';
    $postmysqlquery='mysql_select_db($GLOBALS[currentdb]);';
  } else {
    $premysqlquery="";
    $postmysqlquery="";
  }

  // traitement particulier pour les documents
  // champ supplementaire datepubli

  if (in_array("documents",$tables)) {
    $extrafield=",(datepubli<=NOW()) as textepublie";
  }
#endif

  return array($premysqlquery,$postmysqlquery,$extrafield);
}



// prefix les tables si necessaire

function prefix_tablename ($tablename)

{
#ifndef LODELLIGHT
  return $tablename;
#else
#  preg_match_all("/\b(\w+)\./",$tablename,$result);
#  foreach ($result[1] as $tbl) {
#    $tablename=preg_replace ("/\b$tbl\./",$GLOBALS[tableprefix].$tbl.".",$tablename);
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
    $arr[$i]=preg_replace("/\b(?<!\\.)($fields)\b/","$GLOBALS[prefixtable]$table.\\1",$arr[$i]);
  $sql=join("'",$arr);
}
    

?>
