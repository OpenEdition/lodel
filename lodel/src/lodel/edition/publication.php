<?php
// gere les publications. L'acces est reserve aux administrateurs du site.
// assure l'edition, la supression, la restauration des publications.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...

$context[id]=$id=intval($id);
if ($parent) $idparent=$parent;
$context[idparent]=$idparent=intval($idparent);


if ($id>0 && !$admin) {
  $critere=" AND groupe IN ($usergroupes)";
} else $critere="";

if ($id>0 && $dir) {
  lock_write("entites");
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entites WHERE id='$id' $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
  list($idparent)=mysql_fetch_row($result);
  chordre("entites",$id,"idparent='$idparent'",$dir);
  unlock("entites");
  back();

//
// supression et restauration
//
} elseif ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  die ("il faut utiliser supprime.php a la place");
  return;
//
// ajoute ou edit
//
} elseif ($plus) {
  extract_post();
} elseif ($edit) { // modifie ou ajoute
  include ($home."publicationfunc.php");

  extract_post();
  // edition et sort si ca marche
  if(pub_edition($context,"id='$id'".$critere)) back();
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT *, type  FROM  $GLOBALS[publicationstypesjoin] WHERE $GLOBALS[tp]entites.id='$id'  $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
  extrait_personnes($id,&$context);
  extrait_entrees($id,&$context);
} else {
  include_once ($home."textfunc.php");
  $context[type]=trim(rmscript(strip_tags($type)));
  if ($context[type]) {
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$context[type]' AND status>0") or die (mysql_error());
    if (!mysql_num_rows($result)) die("type inconnu $context[type]");
    list($context[idtype])=mysql_fetch_row($result);
  }
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"publication");


function makeselecttype() 

{
  global $context;

  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tp]types WHERE classe='publications' AND status>0") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[type]==$row[nom] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[titre]</OPTION>\n";
  }
}

function makeselectgroupes() 

{
  global $context;
      
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tp]groupes") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[groupe]==$row[id] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[nom]</OPTION>\n";
  }
}


function loop_personnes(&$context,$funcname)

{
  global $id; // id de la publication

  $ind=0;
  $idtype=$context[id];
  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  do {
    $vide=TRUE;
    $localcontext=$context;
    $localcontext[ind]=++$ind;
    foreach($vars as $v) {
      $localcontext[$v]=$context[$v][$idtype][$ind];
      if ($vide && $localcontext[$v]) $vide=FALSE;
    }
    if ($vide && !$GLOBALS[plus][$idtype]) break;
    call_user_func("code_do_$funcname",$localcontext);
    if ($vide) break;
  } while (1);
}

function extrait_personnes($identite,&$context)

{
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]personnes,$GLOBALS[tp]entites_personnes WHERE idpersonne=id  AND identite='$identite'") or die(mysql_error());

  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  while($row=mysql_fetch_assoc($result)) {
    foreach($vars as $var) {
      $context[$var][$row[idtype]][$row[ordre]]=$row[$var];
    }
  }
}

function extrait_entrees($identite,&$context)

{
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]entrees,$GLOBALS[tp]entites_entrees WHERE identree=id  AND identite='$identite'") or die(mysql_error());

  while($row=mysql_fetch_assoc($result)) {
    if ($context[entrees][$row[idtype]]) {
      array_push($context[entrees][$row[idtype]],$row[nom]);
    } else {
      $context[entrees][$row[idtype]]=array($row[nom]);
    }
  }
}


function makeselectentrees (&$context)
     // le context doit contenir les informations sur le type a traiter
{
  $entreestrouvees=array();
  $entrees=$context[entrees][$context[id]];
#  echo "type:",$context[id];print_r($context[entrees]);
  makeselectentrees_rec(0,"",$entrees,$context,&$entreestrouvees);
  $context[autresentrees]=join(", ",array_diff($entrees,$entreestrouvees));
}

function makeselectentrees_rec($idparent,$rep,$entrees,&$context,&$entreestrouvees)

{
  if (!$context[tri]) die ("ERROR: internal error in makeselectentrees_rec");
  $result=mysql_query("SELECT id, abrev, nom FROM $GLOBALS[tp]entrees WHERE idparent='$idparent' AND idtype='$context[id]' ORDER BY $context[tri]") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$entrees && (in_array($row[abrev],$entrees) || in_array($row[nom],$entrees)) ? " selected" : "";
   if ($selected) array_push($entreestrouvees,$row[nom],$row[abrev]);
   $value=$context[useabrev] ? $row[abrev] : $row[nom];
    echo "<option value=\"$value\"$selected>$rep$row[nom]</option>\n";
    makeselectentrees_rec($row[id],$rep.$row[nom]."/",$entrees,$context,&$entreestrouvees);
  }
}




?>
