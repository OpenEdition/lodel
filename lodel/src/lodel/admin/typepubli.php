<?

// gere les types de publications. L'acces est reserve au superadministrateur.

require("revueconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once("$home/func.php");


// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ("$home/trash.php");
  treattrash("typepublis",$critere);
  return;
}

$critere.=" AND status>0";
//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
#    if (!$context[tpl]) $err=$context[erreur_tpl]=1;
    if ($err) break;

    include_once ("$home/connect.php");

    if ($id>0) { // il faut rechercher le status
      $result=mysql_query("SELECT status FROM $GLOBALS[tableprefix]typepublis WHERE id='$id'") or die (mysql_error());
      list($status)=mysql_fetch_array($result);
    } else {
      $status=1;
    }

    mysql_query ("REPLACE INTO $GLOBALS[tableprefix]typepublis (id,nom,tpl,tpledit,status) VALUES ('$id','$context[nom]','$context[tpl]','$context[tpledit]','$status')") or die (mysql_error());

    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ("$home/connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]typepublis WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,"typepubli");


?>
