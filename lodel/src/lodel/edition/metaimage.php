<?php

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");
include ($home."champfunc.php");

if ($idsite) { $id=$context[idsite]=intval($idsite); $classe="sites"; $critere="id='$id'"; $db=$database; $champ="image"; }
elseif ($iddocument) { $id=$context[iddocument]=intval($iddocument); $classe="documents"; $critere="identite='$id'"; $db=$currentdb; }
elseif ($idpublication) { $id=$context[idpublication]=intval($idpublication); $classe="publications"; $critere="identite='$id'"; $db=$currentdb; }
else { die("Erreur preciser idsite, iddocument ou idpublication"); }

if (!$champ || !isvalidfield($champ)) die("Erreur. Preciser un champ");
$context[champ]=$champ;
$context[id]=$id;


do {
  if ($delete) {
    require_once($home."images.php");
    change_image("delete",$id,$classe,$champ);
    $newimagefile="";
  } elseif ($edit) {
    require_once($home."images.php");
    $newimagefile=change_image($imagefile,$id,$classe,$champ);
    if ($newimagefile===FALSE) { $context[erreur_chargement]=1; break; }
  } else break;
  mysql_db_query($db,"UPDATE $GLOBALS[tp]$classe SET $champ='$newimagefile' WHERE $critere") or die(mysql_error());
  back();
} while (0);

$result=mysql_db_query($db,"SELECT $champ FROM $GLOBALS[tp]$classe WHERE $critere") or die (mysql_error());
if (!mysql_num_rows($result)) { header("Location: not-found.html"); return; }
list($oldimagefile)=mysql_fetch_row($result);

$context[image]=$oldimagefile;

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"metaimage");


?>
