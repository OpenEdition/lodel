<?

// gere les utilisateurs. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_SUPERADMIN);
include_once ("$home/func.php");

$url_retour="revues.php";

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="id='$id'";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ("$home/trash.php");
  treattrash("revues",$critere);
  return;
//
// ajoute ou edit
//
} 

if ($edit) { // modifie ou ajoute

  extract_post();
  // validation
  do {
    if (!$context[nom]) { $context[erreur_nom]=$err=1; }
    if (!$context[rep] || preg_match("/\W/",$context[rep])) { $context[erreur_rep]=$err=1; }
    elseif (!is_dir("../../$context[rep]")) { $context[erreur_rep_not_exists]=$err=1; }    
    if ($err) break;
    include_once ("$home/connect.php");

    // lit les informations options, status, etc... si la revue existe deja
    if ($id) {
      $result=mysql_query ("SELECT options,status FROM revues WHERE id='$id'") or die (mysql_error());
      list($options,$status)=mysql_fetch_row($result);
    } else {
      $options=""; $status=1;
    }

    mysql_query("REPLACE INTO revues (id,nom,rep,soustitre,options,status) VALUES ('$id','$context[nom]','$context[rep]','$context[soustitre]','$options','$status')") or die (mysql_error());

    if (!$id) { header("location: install-revue.php?rep=$context[rep]"); die("erreur de redirection"); }

    @header("Location: $url_retour");return;

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ("$home/connect.php");
  $critere.=" AND status>0";

  $result=mysql_query("SELECT * FROM revues WHERE $critere") or die ("erreur SELECT");
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement ($context);

include ("$home/calcul-page.php");
calcul_page($context,"revue");

?>



