<?

// gere les types de entrees. L'acces est reserve au superadministrateur.

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
  treattrash("typeentrees",$critere);
  return;
}

$critere.=" AND status>0";

if ($id>0 && $dir) {
  # cherche le parent
  chordre("typeentrees",$id,"status>0",$dir);
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
    if (!$context[balise] || !preg_match("/^[a-zA-Z0-9]*$/",$context[balise])) $err=$context[erreur_balise]=1;
    if (!$context[style] || !preg_match("/^[a-zA-Z0-9]*$/",$context[style])) $err=$context[erreur_style]=1;
    if ($err) break;

    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le status
      $result=mysql_query("SELECT status,ordre FROM $GLOBALS[tableprefix]typeentrees WHERE id='$id'") or die (mysql_error());
      list($status,$ordre)=mysql_fetch_array($result);
    } else {
      $status=1;
      $ordre=get_ordre_max("typeentrees");
    }

    mysql_query ("REPLACE INTO $GLOBALS[tableprefix]typeentrees (id,nom,titre,balise,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES ('$id','$context[nom]','$context[titre]','$context[balise]','$context[style]','$context[tpl]','$context[tplindex]','$status','$context[lineaire]','$context[newimportable]','$context[useabrev]','$context[tri]','$ordre')") or die (mysql_error());
    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]typeentrees WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"typeentree");


?>
