<?

// gere les groupe. L'acces est reserve au administrateur.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once("$home/func.php");


$id=intval($id);

if ($id==1) back(); // on ne modifie ni n'efface le groupe tous !

$critere="id='$id'";

//
// ajoute ou edit
//
//
// supression
//
if ($id>0 && $delete>=2) {
  lock_write("groupes","users_groupes","publications","documents");
  do {
    // verifie qu'il n'y a pas de publi ou de documents qui ont ce groupe
    mysql_query("SELECT id FROM $GLOBALS[tableprefix]publications WHERE groupe='$id'") or die (mysql_error());
    if (mysql_num_rows($result)) { $context[erreur_publications_exist]=$err=1; }
    mysql_query("SELECT id FROM $GLOBALS[tableprefix]documents WHERE groupe='$id'") or die (mysql_error());
    if (mysql_num_rows($result)) { $context[erreur_documents_exist]=$err=1; }
    if ($err) break;

    mysql_query("DELETE FROM $GLOBALS[tableprefix]groupes WHERE id='$id'") or die (mysql_error());
    mysql_query("DELETE FROM $GLOBALS[tableprefix]users_groupes WHERE idgroupe='$id'") or die (mysql_error());
    back();
  } while(0);
}

if ($edit && !$delete) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if ($err) break;
    include_once ("$home/connect.php");

    mysql_query ("REPLACE INTO $GLOBALS[tableprefix]groupes (id,nom) VALUES ('$id','$context[nom]')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once ("$home/connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]groupes WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}
$context[delete]=$delete;

// post-traitement
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,"groupe");


?>
