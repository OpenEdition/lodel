<?

// gere les entrees. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des entrees.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  $delete=2; // destruction en -64;
  include ($home."trash.php");
  treattrash("entrees",$critere);
  return;
}

//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le parent
  $result=mysql_query ("SELECT parent FROM $GLOBALS[tp]entrees WHERE $critere") or die (mysql_error());
  list($parent)=mysql_fetch_row($result);
  chordre("entrees",$id,"parent='$parent' AND status>-64",$dir);
  back();
}


//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if ($err) break;
    include_once ($home."connect.php");

    $parent=intval($context[parent]);
    if ($id>0) { // il faut rechercher le status, le type et l'ordre
      $result=mysql_query("SELECT status,idtype,ordre FROM entrees$GLOBALS[tp] WHERE id='$id'") or die (mysql_error());
      list($status,$context[idtype],$ordre)=mysql_fetch_array($result);
    } else {
      $status=1;
      if (!$context[idtype]) die ("Erreur interne. Il manque le type dans le formulaire");
      $context[idtype]=intval($context[idtype]);
      $ordre=get_ordre_max("entrees"," parent='$parent' AND idtype='$context[idtype]'");
    }
    if ($protege) $status=$id && $status>0 ? 32 : -32;    

    mysql_query ("REPLACE INTO $GLOBALS[tp]entrees (id,parent,nom,abrev,ordre,lang,status,idtype) VALUES ('$id','$parent','$context[nom]','$context[abrev]','$ordre','$context[lang]','$status','$context[idtype]')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]entrees WHERE $critere AND status>-32") or die ("erreur SELECT");
  $context=array_merge(mysql_fetch_assoc($result),$context);
}

// cherche le type. As-t-on l'id ou du texte ?
if ($context[idtype]) {
  $critere="id='".intval($context[idtype])."'";
} elseif ($type && preg_match("/[\w-]/",$type)) {
  $critere="nom='$type'";
} else die("preciser un type");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]typeentrees WHERE $critere AND status>0") or die (mysql_error());
if (!mysql_num_rows($result)) die("type incorrecte ($context[idtype],$type)");
$context= array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[idtype]=$context[type_id]; // importe l'id du type dans type




// post-traitement
posttraitement($context);

include($home."langues.php");

include ($home."calcul-page.php");
calcul_page($context,"entree");


function make_selection_entree($parent=0,$rep="")

{
  global $context;

  $result=mysql_query("SELECT nom,id FROM $GLOBALS[tp]entrees WHERE idtype='$context[idtype]' AND parent='".intval($parent)."' ORDER BY $context[type_tri]") or die (mysql_error());
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    $selected=$row[id]==$context[parent] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$rep$row[nom]</OPTION>\n";
    make_selection_entree($row[id],"$rep$row[nom]/");
  }

}



?>
