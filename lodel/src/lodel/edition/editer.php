<?php 

die("desuet, appeler directement document.php");

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$id=intval($id);

if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT idparent,idtype FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
  list($idparent,$idtype)=mysql_fetch_row($result);
  getsiteoptions ();
  $critere=$options[ordrepartypedoc] ? "AND idtype='$idtype'": "";
  chordre("entites",$id,"idparent='$idparent' $critere",$dir);
  back();
//
// supression et restauration
//
} elseif ($edit || $charge) { # prepare l'envoi dans extrainfo
# on fait une copie dans tmp
# cherche les info pour creer la tache
  $result=mysql_query("SELECT idparent,ordre FROM $GLOBALS[tp]entites WHERE id=$id AND status>-64") or die (mysql_error());
  if ($row=mysql_fetch_assoc($result)) {
    $row[iddocument]=$id;
    if ($edit) {
      $tempname=tempnam("","r2r");
      copy("../txt/r2r-$id.xml",$tempname.".html");	
      $row[fichier]=$tempname;
      $idtache=make_tache("Edition $id",3,$row);
      header("location: extrainfo.php?id=$idtache");
    } else {
      $idtache=make_tache("Rechargement $id",1,$row);
      header("location: oochargement.php?tache=$idtache");
    }
  } else {
    header("location: ../../not-found.html");
  }
} else {
  # extrait le status de l'article
  $result=mysql_query("SELECT status FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
  list($context[status])=mysql_fetch_row($result);
}


$context[id]=$id;

posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"editer");


?>
