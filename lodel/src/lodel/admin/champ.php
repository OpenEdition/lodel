<?php
// gere les champs. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des champs.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  $delete=2; // destruction en -64;
  include ($home."trash.php");
  treattrash("champs",$critere);
  return;
}

//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le groupe
  $result=mysql_query ("SELECT groupe FROM $GLOBALS[tp]champs WHERE $critere") or die (mysql_error());
  list($idparent)=mysql_fetch_row($result);
  chordre("champs",$id,"groupe='$groupe' AND status>-64",$dir);
  back();
}


//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if (!$context[type]) $err=$context[erreur_type]=1;
    if ($err) break;
    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le status et l'ordre
      $result=mysql_query("SELECT status,ordre FROM $GLOBALS[tp]champs WHERE id='$id'") or die (mysql_error());
      list($status,$ordre)=mysql_fetch_array($result);
    } else {
      $status=1;
      if (!$context[classe]) die ("Erreur interne. Il manque la classe dans le formulaire");
      $ordre=get_ordre_max("champs"," groupe='$context[groupe]'");
    }
    if ($protege) $status=$id && $status>0 ? 32 : -32;    

    mysql_query ("REPLACE INTO $GLOBALS[tp]champs (id,nom,titre,groupe,classe,style,type,condition,traitement,ordre,status) VALUES ('$id','$context[nom]','$context[titre]','$context[groupe]','$context[classe]','$context[style]','$context[type]','$context[condition]','$context[traitement]','$ordre','$status')") or die (mysql_error());
    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]champs WHERE $critere AND status>-32") or die (mysql_error());
  $context=array_merge(mysql_fetch_assoc($result),$context);
} else {
  // cherche le classe.
  if ($classe && preg_match("/[\w-]/",$classe)) {
    $context[classe]=$classe;
  } else die("preciser une classe");
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"champ");






function make_selection_traitements()

{
  global $context;

  if ($context[id]) {
    $result=mysql_query("SELECT traitement FROM $GLOBALS[tp]champs WHERE id='$context[id]'") or die (mysql_error());
    list($mytraitement)=mysql_fetch_row($result);
  }
  foreach ($traitements as $traitement) {
    $selected=$traitement==$mytraitement ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$traitement\"$selected>$traitement</OPTION>\n";
  }
}


function make_selection_traitement()

{

  make_selection("traitement",
		 array("none"=>"aucun",
		       "strip_tags"=>"Enlève tout le HTML",
		       "strip_tags_excepted_i"=>"Enlève tout le HTML sauf l'italique",
		       "strip_tags_except_i_b"=>"Enlève tout le HTML sauf l'italique et le gras",
}


function make_selection($champ, $arr)

{
  global $context;

  if ($context[id]) {
    $result=mysql_query("SELECT $champ FROM $GLOBALS[tp]champs WHERE id='$context[id]'") or die (mysql_error());
    list($mykey)=mysql_fetch_row($result);
  }
  foreach ($arr as $key => $value) {
    $selected=$mykey==$key ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$key\"$selected>$value</OPTION>\n";
  }
}



?>
