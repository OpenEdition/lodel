<?php
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

$critere.=" AND statut>0";

require($home."typetypefunc.php");

//
// ordre
//

if ($id>0 && $dir) {
  # cherche le parent
  chordre("types",$id,"statut>0",$dir);
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

    lock_write("types","typeentites_typeentrees","typeentites_typepersonnes");
    if ($id>0) { // il faut rechercher le statut
      $result=mysql_query("SELECT statut,ordre FROM $GLOBALS[tp]types WHERE id='$id'") or die (mysql_error());
      list($statut,$ordre)=mysql_fetch_array($result);
    } else {
      $statut=1;
      $ordre=get_ordre_max("types");
    }
    mysql_query ("REPLACE INTO $GLOBALS[tp]types (id,type,titre,classe,tpl,tpledition,tplcreation,statut,ordre) VALUES ('$id','$context[type]','$context[titre]','$classe','$context[tpl]','$context[tpledition]','$context[tplcreation]','$statut','$ordre')") or die (mysql_error());

    if ($id) {
      typetypes_delete("idtypeentite='$id'");
    } else {
      $id=mysql_insert_id();
    }
    typetype_insert($id,$typeentree,"typeentree");
    typetype_insert($id,$typepersonne,"typepersonne");

    unlock();
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


function loop_typepersonnes($context,$funcname)
{  loop_typetable ("typepersonne","typeentite",$context,$funcname);}

function loop_typeentrees($context,$funcname)
{  loop_typetable ("typeentree","typeentite",$context,$funcname);}

?>
