<?php
// gere les champs. L'acces est reserve au administrateur.
// assure l'edition, la supression, la restauration des champs.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");
include_once($home."champfunc.php");



$id=intval($id);
$critere=$id ? "$GLOBALS[tp]champs.id='$id'" : "";

//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le groupe
  $result=mysql_query ("SELECT idgroupe FROM $GLOBALS[tp]champs WHERE $critere") or die (mysql_error());
  list($idgroupe)=mysql_fetch_row($result);
  chordre("champs",$id,"idgroupe='$idgroupe' AND statut>-64",$dir);
  back();
}

if ($id && !$adminlodel) $critere.=" AND $GLOBALS[tp]champs.statut<32";


//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  $delete=2; // destruction complete;
  $result=mysql_query("SELECT $GLOBALS[tp]champs.nom,classe FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) die("ERROR: The field does not exist or you are not allowed to delete it.");
  list($nom,$classe)=mysql_fetch_row($result);
  mysql_query("ALTER TABLE $GLOBALS[tp]$classe DROP COLUMN $nom") or die (mysql_error());
  include ($home."trash.php");
  treattrash("champs",$critere);
  require_once($home."cachefunc.php");
  removefilesincache(".","../edition","../..");
  return;
}


//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    $context[nom]=trim($context[nom]);
    if (!$context[nom] || !isvalidfield($context[nom])) $err=$context[erreur_nom]=1;
	if (reservedword($context[nom])) $err=$context[erreur_nom_reserve]=1;
    if (!$context[type]) $err=$context[erreur_type]=1;
    $context[style]=trim($context[style]);
    if ($context[type]=="mltext") {
      if ($context[style] && !isvalidmlstyle($context[style])) $err=$context[erreur_mlstyle]=1;
    } else {
      if ($context[style] && !isvalidstyle($context[style])) $err=$context[erreur_style]=1;
    }

    if ($err) break;
    include_once ($home."connect.php");

    // lock the tables
    if ($context[classe]!="documents" && $context[classe]!="publications") die("Preciser une classe. Classe incorrecte");
    lock_write("champs",$context[classe],"groupesdechamps");

    $alter="";
    if ($id>0) { // il faut rechercher le statut et l'ordre
      $result=mysql_query("SELECT statut,ordre,idgroupe,type,nom,filtrage FROM $GLOBALS[tp]champs WHERE $critere") or die (mysql_error());
      if (!mysql_num_rows($result)) die("ERROR: The field does not exist or you are not allowed to delete it.");
      list($statut,$ordre,$oldidgroupe,$oldtype,$oldnom,$oldfiltrage)=mysql_fetch_row($result);
      if ($sqltype[$oldtype]!=$sqltype[$context[type]]) {
	$alter="MODIFY";
	if (!$confirmation) { $context[erreur_confirmation_type]=1; break; }
      } elseif ($oldnom!=$context[nom]) {
	$alter="CHANGE $oldnom";
      }

      if ($oldidgroupe!=$context[idgroupe]) $ordre=get_ordre_max("champs","idgroupe='$context[idgroupe]'");
    } else {
      // check that the field does not exist
      $result=mysql_query("SELECT $GLOBALS[tp]champs.id FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND $GLOBALS[tp]champs.nom='$context[nom]' AND classe='$context[classe]'") or die (mysql_error());
      if (mysql_num_rows($result)) { $context[erreur_nom_existe]=1; break; }
      // ok, it does not exist
      $statut=1;
      if (!$context[classe]) die ("Erreur interne. Il manque la classe dans le formulaire");
      $ordre=get_ordre_max("champs"," idgroupe='$context[idgroupe]'");
      $alter="ADD";
    }
    // adminlodel only are allow to protect/unprotect
    if ($adminlodel) {
      $newstatut=$protege ? 32 : 1;
      $statut=$statut>0 ? $newstatut : -$newstatut;    
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]champs (id,nom,titre,idgroupe,style,type,condition,defaut,traitement,filtrage,edition,ordre,statut) VALUES ('$id','$context[nom]','$context[titre]','$context[idgroupe]','$context[style]','$context[type]','$context[condition]','$context[defaut]','$context[traitement]','$context[filtrage]','$context[edition]','$ordre','$statut')") or die (mysql_error());

    if ($alter) { // modify or add or rename the field
      mysql_query("ALTER TABLE $GLOBALS[tp]$context[classe] $alter $context[nom] ".$sqltype[$context[type]]) or die (mysql_error());
    }
    if ($alter || $context[filtrage]!=$oldfiltrage) {
      require_once($home."cachefunc.php");
      removefilesincache(".","../edition","../..");
    }
    unlock();
    back();
  } while (0);
  unlock();
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]champs.*,classe FROM $GLOBALS[champsgroupesjoin] WHERE  $critere AND $GLOBALS[tp]champs.statut>-32") or die (mysql_error());
  if (!mysql_num_rows($result)) die("ERROR: You are not allowed to delete this field.");
  $context=array_merge(mysql_fetch_assoc($result),$context);
} else {
  // cherche le classe.
  if ($classe && !preg_match("/[^a-z]/",$classe)) {
    $context[classe]=$classe;
  } else die("Preciser une classe");
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"champ");





function make_select_traitements()

{

  make_select("traitement",
	      array(""=>"aucun",
		    '|strip_tags'=>"Enlever toutes les balises HTML",
		    '|strip_tags_keepnotes'=>"Enlever toutes les balises HTML sauf les appels de note",
		    '|strip_tags_keepnotes("<i>")'=>"Enlever toutes les balises HTML sauf les appels de note et l'italique",
		    '|strip_tags_keepnotes("<u><i>")'=>"Enlever toutes les balises HTML sauf les appels de note, l'italique et le sousligner",
		    ));
}


function make_select_conditions()

{
  make_select("condition",
		 array("*"=>"aucune",
		       "+"=>"champ obligatoire")
		 );
}

function make_select_edition()

{
  make_select("edition",
		 array(
		       "editable"=>"editable",
		       ""=>"non editable dans l'interface",
		       "text"=>"editable sur 1 ligne",
		       "textarea10"=>"editable sur 10 lignes",
		       "textarea30"=>"editable sur 30 lignes",
		       )
		 );
}


function make_select_types()

{
  make_select("type",$GLOBALS[typechamps]);
}


function make_select($champ, $arr)

{
  global $context;

  if ($context[$champ]) {
    $mykey=$context[$champ];
  } elseif ($context[id]) {
    $result=mysql_query("SELECT $champ FROM $GLOBALS[tp]champs WHERE id='$context[id]' AND statut>0") or die (mysql_error());
    list($mykey)=mysql_fetch_row($result);
    $mykey=htmlentities($mykey);
  }

  foreach ($arr as $key => $value) {
    $key=htmlentities($key);
    $selected=$mykey==$key ? " selected" : "";
    echo "<option value=\"$key\"$selected>$value</option>\n";
  }
}

function make_select_groupesdechamps()

{
  global $context;

  if (!$context[classe]) die ("ERROR: internal error in make_select_groupesdechamps");
  $result=mysql_query("SELECT id,titre FROM $GLOBALS[tp]groupesdechamps WHERE classe='$context[classe]' AND statut>0") or die (mysql_error());
  while ($row=mysql_fetch_assoc($result)) {
    $selected=$row[id]==$context[idgroupe] ? " selected" : "";
    echo "<option value=\"$row[id]\"$selected>$row[titre]</option>\n";
  }
}

?>
