<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$idparent=intval($idparent);
$id=intval($id);
$idtype=intval($idtype);
$tplcreation="";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("entites");
  return;
}

$critere="id='$id'";

//
// ordre
//
if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
  list($idparent)=mysql_fetch_row($result);
  // recupere les type de documents annexe
  $result=mysql_query ("SELECT id FROM $GLOBALS[tp]types WHERE type LIKE 'documentannexe-%'") or die (mysql_error());
  $idtypes=array();
  while ($row=mysql_fetch_assoc($result)) { array_push($idtypes,$row[id]); }
  chordre("entites",$id,"idparent='$idparent' AND idtype IN (".join(",",$idtypes).")",$dir);
  back();
}


//
// ajoute ou edit
//


if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$idtype) die("il faut preciser l'idtype");
    $result=mysql_query("SELECT type FROM $GLOBALS[tp]types WHERE id='$idtype' AND statut>0") or die (mysql_error());
  if (!mysql_num_rows($result)) die ("type '$type' inconnu (1)");
  list($type)=mysql_fetch_row($result);


  if ($type=="documentannexe-lienfichier") {
    // charge le fichier si necessaire
      if ($docfile && $docfile!="none") {
	// place le fichier en place
	// verifie que le repertoire du document existe
	$dir="docannexe/$idparent";
	if (!file_exists("../../".$dir)) {
	  if (!@mkdir("../../".$dir,0755)) die("impossible de creer le repertoire $dir");
	}
	$lien=$dir."/".basename($docfile_name);
	copy($docfile,"../../$lien");
      } else {
	// recherche le lien
	include_once ($home."connect.php");
	$result=mysql_query("SELECT lien FROM $GLOBALS[tp]documents WHERE identite='$id'") or die (mysql_error());
	list($lien)=mysql_fetch_row($result);
      }
    } elseif ($type=="documentannexe-liendocument") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT identite FROM $GLOBALS[tp]documents WHERE identite='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[erreur_documentnonexist]=1;
      } else {
	$lien="document.html?id=".$lien;
      }
    } elseif ($type=="documentannexe-lienpublication") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT identite FROM $GLOBALS[tp]publications WHERE identite='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[erreur_publicationnonexist]=1;
      } else {
	$lien="sommaire.html?id=".$lien;
      }
    } elseif ($type=="documentannexe-lienexterne") {
      // verifie l'adresse
      $lien=$context[lien];
      if ($lien && !preg_match("/http:\/\//i",$lien)) $lien="http://".$lien;
      $url=parse_url($lien);
      if (!$url[host] || !preg_match("/^[\w-]+(\.[\w-]+)+$/",$url[host])) { $context[erreur_urlinvalide]=$err=1; }
    } else {
      die ("erreur type incorrecte");
    }
    if (!$lien) { $context[erreur_lieninexistant]=$err=1; }
    // fin de chargement

    if ($err) break;
    include_once ($home."connect.php");

    require_once($home."entitefunc.php");
    $context[entite][titre]=$context[titre];
    $context[entite][commentaire]=$context[commentaire];
    $context[entite][lien]=$lien;
    $context[idparent]=$idparent;

    enregistre_entite(&$context,$id,"documents","",TRUE);

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,$GLOBALS[tp]types.type,tplcreation FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id'") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
  if ($context[type]=="documentannexe-liendocument" || $context[type]=="documentannexe-lienpublication") {
    // recupere le numero
    preg_match("/id=(\d+)\b/",$context[lien],$result);
    $context[lien]=$result[1];
  }
  $tplcreation=$context[tplcreation];
} elseif ($idparent) {
  $context[idparent]=$idparent;
  if (!$idtype && !$type) die("il faut preciser le type de docannexe");
} else {
  die("il faut preciser un document auquel on veut ajouter le document annexe");
}


if (!$tplcreation) {
  // cherche le tpl
  $critere=$idtype ? "id='$idtype'" : "type='$type'";
    $result=mysql_query("SELECT tplcreation,id, type FROM $GLOBALS[tp]types WHERE $critere AND statut>0") or die (mysql_error());
  if (!mysql_num_rows($result)) die ("type '$type' inconnu");
  list($tplcreation,$context[idtype],$context[type])=mysql_fetch_row($result);
}

$context[id]=$id;


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,$tplcreation);


?>
