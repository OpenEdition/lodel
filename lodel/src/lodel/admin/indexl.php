<?

// securite
if (!function_exists("authenticate") || !$GLOBALS[admin]) return;

// gere les index linéaire permanent. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des index linéaire.


// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
  if (!$restore) $critere.=" AND status>0";
  if ($type) $critere.=" AND type='$type'";
} else $critere="";


//
// ordre
//

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("indexls",$critere);
  return;
}
if (!$type) die("probleme interne contacter Ghislain");

//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  // pretraitement des entrees... met le resultat dans $context
  extract_post();
  // validation
  do {
    if (!$context[mot]) $err=$context[erreur_mot]=1;
    if ($err) break;
    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le status
      $result=mysql_query("SELECT status FROM indexls WHERE id='$id'") or die (mysql_error());
      list($status)=mysql_fetch_array($result);
      mysql_query ("REPLACE INTO indexls (id,mot,lang,status,type) VALUES ('$id','$context[mot]','$context[lang]','$status','$type')") or die (mysql_error());
    } else {
      // cree les mots cles
      $mots=preg_split("/\s*[,;\n]\s*/",$context[mot]);
      foreach($mots as $mot) {
	$mot=trim(strip_tags($mot));
	mysql_query ("INSERT INTO indexls (mot,lang,type) VALUES ('$mot','$context[lang]','$type')") or die (mysql_error());
      }
    }

    include_once($home."func.php");back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM indexls WHERE $critere") or die (mysql_error());
  $context=array_merge(mysql_fetch_assoc($result),$context);
}

// post-traitement
posttraitement($context);

include($home."langues.php");

?>
