<?

// gere les publications. L'acces est reserve aux administrateurs de la revue.
// assure l'edition, la supression, la restauration des publications.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...

$context[id]=$id=intval($id);
$context[parent]=$parent=intval($parent);

if ($id>0) {
  $critere="id='$id'";
  if (!$admin) $critere.=" AND groupe IN ($usergroupes)";
} else $critere="";

if ($id>0 && $dir) {
  lock_write("publications");
  # cherche le parent
  $result=mysql_query ("SELECT parent FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
  list($parent)=mysql_fetch_row($result);
  chordre("publications",$id,"parent='$parent'",$dir);
  unlock("publications");
  back();

//
// supression et restauration
//
} elseif ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  die ("il faut utiliser supprime.php a la place");
  treattrash("publications",$critere);
  return;
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  include ($home."publicationfunc.php");

  extract_post();
  // edition et sort si ca marche
  if (pub_edition($context,$critere)) back();

} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  include_once ($home."textfunc.php");
  $context[type]=rmscript(strip_tags($type));
}

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"publication");


function makeselecttype() 

{
  global $context;

  $result=mysql_query("SELECT nom FROM $GLOBALS[tableprefix]typepublis WHERE status>0") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[type]==$row[nom] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[nom]\"$selected>$row[nom]</OPTION>\n";
  }
}

function makeselectgroupes() 

{
  global $context;
      
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tableprefix]groupes") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[groupe]==$row[id] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[nom]</OPTION>\n";
  }
}



?>
