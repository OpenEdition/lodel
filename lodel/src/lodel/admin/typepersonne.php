<?

// gere les types de personnes. L'acces est reserve au superadministrateur.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


// calcul le critere pour determiner ce qu'il faut   editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("typepersonnes",$critere);
  return;
}

$critere.=" AND status>0";

if ($id>0 && $dir) {
  # cherche le parent
  chordre("typepersonnes",$id,"status>0",$dir);
  back();
}
//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom] || !preg_match("/[\w-]/",$context[nom])) $err=$context[erreur_nom]=1;
    if (!$context[tpl]) $err=$context[erreur_tpl]=1;
    if (!$context[tplindex]) $err=$context[erreur_tplindex]=1;
    if (!$context[titre]) $err=$context[erreur_titre]=1;
    if (!$context[style] || !preg_match("/^[a-zA-Z0-9]*$/",$context[style])) $err=$context[erreur_style]=1;
    if ($err) break;

    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le status
      $result=mysql_query("SELECT status,ordre FROM $GLOBALS[tableprefix]typepersonnes WHERE id='$id'") or die (mysql_error());
      list($status,$ordre)=mysql_fetch_array($result);
    } else {
      $status=1;
      $ordre=get_ordre_max("typepersonnes");
    }

    mysql_query ("REPLACE INTO $GLOBALS[tableprefix]typepersonnes (id,nom,titre,style,tpl,tplindex,status,ordre) VALUES ('$id','$context[nom]','$context[titre]','$context[style]','$context[tpl]','$context[tplindex]','$status','$ordre')") or die (mysql_error());
    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]typepersonnes WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"typepersonne");


?>
