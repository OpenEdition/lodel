<?
die ("dessuet");
// enregistre dans la base de donnée le fichier

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ("$home/func.php");

if ($cancel) include ("abandon.php");

$row=get_tache($id);

if ($row[iddocument]) { # efface d'abord
  include_once("$home/managedb.php");
  // recupere les metas et le status
  $result=mysql_query("SELECT meta,status from documents WHERE id='$row[iddocument]'") or die (mysql_error());
  list($row[meta],$status)=mysql_fetch_row($result);
  if (!$row[status]) $status=$row[status]; // recupere le status si necessaire
  supprime_document($row[iddocument]);
}

$text=join("",file($row[fichier].".balise"));
include ("$home/dbxml.php");
$iddocument=enregistre($row,$text);


// change le nom des images
function img_rename($imgfile,$ext,$count)

{
  global $iddocument;

  $newimgfile="docannexe/r2r-img-$iddocument-$count.$ext";
  if ($imgfile!=$newimgfile) {
    rename ($imgfile,"../../$newimgfile") or die ("impossible de renomer l'image $imgfile en $newimgfile");
  }
  return $newimgfile;
}
copy_images($text,"img_rename");


// copie le fichier balise en lieu sur !
writefile("../txt/r2r-$iddocument.xml",$text);
// et le rtf s'il existe
$rtfname="$row[fichier].rtf";
if (file_exists($rtfname)) copy ($rtfname,"../rtf/r2r-$iddocument.rtf");

#echo "<br>temporaire $iddocument";return;


if ($ajouterdocannexe) {
  $url_retour="docannexe.php?iddocument=$iddocument";
} elseif ($visualiserdocument) {
  $url_retour="../../document.html?id=$iddocument";
}

// clot la tache et renvoie sur index.php
include ("abandon.php");

?>




