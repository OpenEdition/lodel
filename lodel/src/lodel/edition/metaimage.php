<?php
die("en cours de (re)developpement");


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

if ($idsite) { $id=intval($idsite); $type="site"; $db=$database; }
elseif ($iddocument) { $id=intval($iddocument); $type="document"; $db=$currentdb; }
elseif ($idpublication) { $id=intval($idpublication); $type="publication"; $db=$currentdb; }
else { die("erreur"); }

$table=$type."s";
$context[idtype]="id".$type;
$context[id]=$id;

$result=mysql_db_query($db,"SELECT meta FROM $GLOBALS[tp]$table WHERE id='$id'") or die (mysql_error());
if (!mysql_num_rows($result)) { header("Location: not-found.html"); return; }

list($metastr)=mysql_fetch_row($result);
$meta=unserialize($metastr);

do {
  if ($delete) {
    unlink("../../$meta[meta_image]"); unset($meta[meta_image]);
  } elseif ($edit) {
    extract_post();

    // charge le fichier si necessaire
    if (!$imgfile || $imgfile=="none") { $context[erreur_chargement]=1; break; }
    $result=getimagesize($imgfile);
    if ($result[2]==1) { $ext="gif"; }
    elseif ($result[2]==2) { $ext="jpg"; }
    elseif ($result[2]==3) { $ext="png"; }
    else { $context[erreur_image]=1; break; }

    if ($meta[meta_image]) { unlink("../../$meta[meta_image]"); }
    $meta[meta_image]="docannexe/img-$table-$id.$ext";
    if ($context[taille]) {
      include_once($home."images.php");
      resize_image($context[taille],$imgfile,"../../$meta[meta_image]");
    } else {
      copy($imgfile,"../../$meta[meta_image]");
    }
  } else { break; }

  $metastr=serialize($meta);

  mysql_db_query($db,"UPDATE $GLOBALS[tp]$table SET meta='$metastr' WHERE id='$id'") or die(mysql_error());

  back();

} while (0);


$context[image]=$meta[meta_image];

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"metaimage");


?>
