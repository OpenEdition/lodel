<?

include_once($home."func.php");

// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("types",$critere);
  return;
}

$critere.=" AND status>0";


//
// ordre
//

if ($id>0 && $dir) {
  # cherche le parent
  chordre("types",$id,"status>0",$dir);
  back();
}
//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[type]) $err=$context[erreur_type]=1;
#    if (!$context[tpl]) $err=$context[erreur_tpl]=1;
    if ($err) break;

    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le status
      $result=mysql_query("SELECT status,ordre FROM $GLOBALS[tp]types WHERE id='$id'") or die (mysql_error());
      list($status,$ordre)=mysql_fetch_array($result);
    } else {
      $status=1;
      $ordre=get_ordre_max("types");
    }


    mysql_query ("REPLACE INTO $GLOBALS[tp]types (id,type,titre,classe,tpl,tpledit,tplcreation,status,ordre) VALUES ('$id','$context[type]','$context[titre]','$classe','$context[tpl]','$context[tpledit]','$context[tplcreation]','$status','$ordre')") or die (mysql_error());

    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]types WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);


?>
