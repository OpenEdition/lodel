<?php
include_once($home."func.php");

// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

require($home."typetypefunc.php");


//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 

  do { // block d'exception
    include_once ($home."connect.php");
    lock_write("types","typeentites_typeentites","typeentites_typeentrees","typeentites_typepersonnes","entites");
    // check the type can be deleted.
    $result=mysql_query("SELECT count(*) FROM $GLOBALS[tp]entites WHERE idtype='$id' AND statut>-64") or die (mysql_error());
    list($count)=mysql_fetch_row($result);
    if ($count) { $context[erreur_entites_existent]=$count; unlock(); break; }

    typetypes_delete("idtypeentite='$id'");

    $delete=2; // supprime pour de vrai
    include ($home."trash.php");
    treattrash("types",$critere,TRUE);
    return;
  } while (0); // block d'exception
}

$critere.=" AND statut>0";

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
    $context[type]=trim($context[type]);
    if (!$context[type]) $err=$context[erreur_type]=1;
    //    if (!$context[tpl]) $err=$context[erreur_tpl]=1;
    if ($err) break;

    include_once ($home."connect.php");
    lock_write("types","typeentites_typeentites","typeentites_typeentrees","typeentites_typepersonnes");

    // verifie que ce type n'existe pas.
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]types WHERE type='$context[type]' AND classe='$classe' AND id!='$id'") or die (mysql_error());
    if (mysql_num_rows($result)) { unlock(); $context[erreur_type_existe]=1; break; }

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
    typetype_insert($id,$typeentite,"typeentite2");

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

function loop_typeentites($context,$funcname)
{  loop_typetable ("typeentite2","typeentite",$context,$funcname);}


?>
