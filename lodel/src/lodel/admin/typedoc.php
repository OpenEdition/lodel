<?

// gere les types de documents. L'acces est reserve aux administrateurs.

require("revueconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once("$home/func.php");


// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="id='$id'";


//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ("$home/trash.php");
  treattrash("typedocs",$critere);
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
    if ($err) break;
    include_once ("$home/connect.php");

    if ($id>0) { // il faut rechercher le status et (peut etre) le passwd
      $result=mysql_query("SELECT status FROM $GLOBALS[tableprefix]typedocs WHERE id='$id'") or die (mysql_error());
      list($status)=mysql_fetch_array($result);
    } else {
      $status=1;
    }

    mysql_query ("REPLACE INTO $GLOBALS[tableprefix]typedocs (id,nom,tpl,status) VALUES ('$id','$context[nom]','$context[tpl]','$status')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once ("$home/connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]typedocs WHERE $critere") or die ("erreur SELECT");
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,"typedoc");


?>
