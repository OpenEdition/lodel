<?

include_once ("lodelconfig.php");
include_once ("$home/auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include_once ("$home/func.php");
include_once ("$home/langues.php");

if ($cancel) include ("abandon.php");

# lit la tache en cours
$row=get_tache($id);

$filename=$row[fichier];

// appele le bloc principal de extrainfomain.php
include ("$home/extrainfomain.php");


update_tache_etape($id,3); // etape 3
$context[id]=$id;
$context[interactive]=$interactive;

# essai d'importation massive automatique
if ($importsommaire) return;

include ("$home/calcul-page.php");
calcul_page($context,"extrainfo");


?>
