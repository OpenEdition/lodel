<?

// gere les publications. L'acces est reserve aux administrateurs de la revue.
// assure l'edition, la supression, la restauration des publications.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...

$context[id]=$id=intval($id);
if ($parent) $idparent=$parent;
$context[idparent]=$idparent=intval($idparent);


if ($id>0 && !$admin) {
  $critere=" AND groupe IN ($usergroupes)";
} else $critere="";

if ($id>0 && $dir) {
  lock_write("entites");
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entites WHERE id='$id' $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
  list($idparent)=mysql_fetch_row($result);
  chordre("entites",$id,"idparent='$idparent'",$dir);
  unlock("entites");
  back();

//
// supression et restauration
//
} elseif ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  die ("il faut utiliser supprime.php a la place");
  return;
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  include ($home."publicationfunc.php");

  extract_post();
  // edition et sort si ca marche
  if (pub_edition($context,"id='$id'".$critere)) back();

} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT *, type  FROM  $GLOBALS[publicationstypesjoin] WHERE $GLOBALS[tp]entites.id='$id'  $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  include_once ($home."textfunc.php");
  $context[type]=trim(rmscript(strip_tags($type)));
  if ($context[type]) {
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$context[type]' AND status>0") or die (mysql_error());
    if (!mysql_num_rows($result)) die("type inconnu $context[type]");
    list($context[idtype])=mysql_fetch_row($result);
  }
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"publication");


function makeselecttype() 

{
  global $context;

  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tp]types WHERE classe='publications' AND status>0") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[type]==$row[nom] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[titre]</OPTION>\n";
  }
}

function makeselectgroupes() 

{
  global $context;
      
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tp]groupes") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[groupe]==$row[id] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[nom]</OPTION>\n";
  }
}


/*
function boucle_personnes(&$context,$funcname)

{
  global $id; // id de la publication

  $result=mysql_query("SELECT * FROM $GLOBALS[tp]personnes,$GLOBALS[tp]documents_personnes WHERE idpersonne=id AND idtype='$context[id]' AND iddocument='$id'") or die(mysql_error());
  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    call_user_func("code_boucle_$funcname",$localcontext);
  }
}
*/


?>
