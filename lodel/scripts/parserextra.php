<?


//
// traitement particulier des attributs d'une boucle
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//


function parse_boucle_extra(&$tables,&$where)

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

      // auteurs
     if (in_array("auteurs",$tables) && strpos($where,"iddocument")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_auteurs");
	$where.=" AND idauteur=auteurs.id";
      }
      if (in_array("documents",$tables) && strpos($where,"idauteur")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_auteurs");
	$where.=" AND iddocument=documents.id";
      }

      // indexhs
      if (in_array("indexhs",$tables) && strpos($where,"iddocument")!==FALSE) {
	// on a besoin de la table croise documents_indexhs
	array_push($tables,"documents_indexhs");
	$where.=" AND idindexh=indexhs.id";
      }

      if (in_array("documents",$tables) && strpos($where,"idindexh")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_indexhs");
	$where.=" AND iddocument=documents.id";
      }
      if (in_array("indexhs",$tables)) {
	$where=preg_replace(array("/type_periode/i","/type_geographie/i"),
			    array("type='".TYPE_PERIODE."'","type='".TYPE_GEOGRAPHIE."'"),
			    $where);
      }
#endif

      if (in_array("indexls",$tables) && strpos($where,"iddocument")!==FALSE) {
	// on a besoin de la table croise documents_indexls
	array_push($tables,"documents_indexls");
	$where.=" AND idindexl=$GLOBALS[tableprefix]indexls.id";
      }
      if (in_array("documents",$tables) && strpos($where,"idindexl")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_indexls");
	$where.=" AND iddocument=$GLOBALS[tableprefix]documents.id";
      }
      if (in_array("groupes",$tables) && strpos($where,"iduser")!==FALSE) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"users_groupes");
	$where.=" AND idgroupe=$GLOBALS[tableprefix]groupes.id";
      }
      if (in_array("indexls",$tables)) {
	$where=preg_replace(array("/\btype_motcle_permanent\b/i",
				  "/\btype_motcle\b/i",
				  "/\btype_tous_motcles\b/i"),
			    array("type='".TYPE_MOTCLE_PERMANENT."'",
				  "type='".TYPE_MOTCLE."'",
				  "(type='".TYPE_MOTCLE_PERMANENT."' OR type='".TYPE_MOTCLE."')"),
			    $where);
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
    

?>
