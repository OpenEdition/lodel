<?
include("lodelconfig.php");
include ("$home/auth.php");
authenticate();
include ("$home/func.php");

$context[id]=$id=intval($id);

include_once("$home/connect.php");
//
// cherche le document
//

$result=mysql_query("SELECT *,datepubli,(datepubli<=NOW()) as textepublie FROM documents WHERE id='$id'") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
$context=array_merge($context,mysql_fetch_assoc($result));
//
// charge le fichier XML et extrait les balises
//
if (!file_exists("lodel/txt/r2r-$id.xml")) { header ("Location: not-found.html"); return; }
$text=join("",file("lodel/txt/r2r-$id.xml"));

include ("$home/xmlfunc.php");
$balises=array("TITRE","RESUME","SURTITRE","SOUSTITRE","NOTEBASPAGE","ANNEXE","BIBLIOGRAPHIE");
if ($context[textepublie]) array_push($balises,"TEXTE");
$context=array_merge($context,extract_xml($balises,$text,TRUE));


//
// envoi
//

if ($envoi) {
  extract_post();

  // validation
  do {

    if (!$context[to]) { $err=$context[erreur_to]=1; }
    if (!$context[from]) { $err=$context[erreur_from]=1; }

    if ($err) break;

    //
    // calcul le mail
    // 
    foreach (array("to","from","message") as $bal) {
      $context[$bal]=htmlspecialchars(stripslashes($context[$bal]));
    }
    $context[subject]=""; // securite
    include ("$home/calcul-page.php");
    ob_start();
    calcul_page($context,"signaler-mail");
    $content=ob_get_contents();
    ob_end_clean();

    //
    // envoie le mail
    //
    if (!mail ($context[to],$context[subject],$content,"From: $context[from]")) { $context[erreur_mail]=1; break; }

    header ("location: document.html?id=$id");
    return;
  } while (0);
}


// post-traitement
foreach (array("to","from","message") as $bal) {
  $context[$bal]=htmlspecialchars(stripslashes($context[$bal]));
}


include_once ("$home/calcul-page.php");
calcul_page($context,"signaler");

?>
