<?

require("revueconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);


$context[id]=intval($id);
$context[tache]=$tache=intval($tache);

if ($htmlfile && $htmlfile!="none") include ("$home/chargementrtf.php");


include ("$home/calcul-page.php");
calcul_page($context,"chargement");



?>
















