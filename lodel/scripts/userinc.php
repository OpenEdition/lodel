<?php
// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require_once("lodelconfig.php");
include_once($home."auth.php"); // secu
if ($userpriv<LEVEL_ADMIN) return; // secu
include_once ($home."func.php");


// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("users");
  return;
}


$critere="id='$id' AND statut>0";

//
// ajoute ou edit
//

if ($edit) { // modifie ou ajoute

  extract_post();
  // validation
  do {
    $len=strlen($context[username]);
    if ($len<3 || $len>10 || preg_match("/\W/",$context[username])) { $err=$context[erreur_username]=1; }

    if (!$context[nom]) { $context[erreur_nom]=$err=1; }
    $passwd=$context[passwd];
    if ($passwd || !$id) { // si le pass a ete modifie
      $len=strlen($passwd);
      if ($len<3 || $len>10) { $err=$context[erreur_passwd]=1; }
    }

    // verifie le courriel
    if ($context[courriel] && !ereg(".*\@[^\.]*\..*",$context[courriel])) { $context[erreur_courriel]=$err=1; }// repris de SPIP
      
    if (!$groupes || !is_array($groupes)) { $context[erreur_groupes]=$err=1; }
 
    if ($err) break;
    include_once ($home."connect.php");

    // cherche si le username existe deja
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]users WHERE username='$context[username]' AND id!='$id'") or die (mysql_error());  
    if (mysql_num_rows($result)>0) { $context[erreur_dupusername]=$err=1; }

    if ($context[privilege]>$userpriv) { $err=1; } // securite

    if ($err) break;

    if ($id>0) { // il faut rechercher le statut et (peut etre) le passwd
      $result=mysql_query("SELECT passwd,statut FROM $GLOBALS[tp]users WHERE id='$id'") or die (mysql_error());
      list($passwd_db,$statut)=mysql_fetch_array($result);
    } else {
      $statut=1;
    }
    if (!$passwd) { // pas de passwd... on prend celui de la base de donnee
      $passwd=$passwd_db;
    } else { // on encrypte le passwd
      $passwd=md5($context[passwd].$context[username]);
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]users (id,username,passwd,nom,courriel,privilege,statut) VALUES ('$id','$context[username]','$passwd','$context[nom]','$context[courriel]','$context[privilege]','$statut')") or die (mysql_error());

    if ($context[privilege]<LEVEL_ADMIN) {
      if (!$id) $id=mysql_insert_id();

      // change les groupes
      mysql_query("DELETE FROM $GLOBALS[tp]users_groupes WHERE iduser='$id'") or die (mysql_error());
      foreach ($groupes as $groupe) {
	mysql_query("INSERT INTO $GLOBALS[tp]users_groupes (idgroupe, iduser) VALUES  ('$groupe','$id')") or die (mysql_error());
      }
    }

    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]users WHERE $critere") or die (mysql_error());
  //$context=mysql_fetch_assoc($result);
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);


$context[passwd]="";


function makeselectprivilege()

{
  global $context,$userpriv;
#ifndef LODELLIGHT
  $arr=array(LEVEL_VISITEUR=>"Visiteur",
	     LEVEL_REDACTEUR=>"Rédacteur",
	     LEVEL_EDITEUR=>"Editeur",
	     LEVEL_ADMIN=>"Administrateur",
	     );
#else
#  $arr=array(LEVEL_VISITEUR=>"Visiteur",
#	     LEVEL_REDACTEUR=>"Rédacteur",
#	     LEVEL_EDITEUR=>"Editeur",
#	     LEVEL_ADMIN=>"Administrateur",
#	     LEVEL_ADMINLODEL=>"Super administrateur"
#	     );
#endif

  foreach ($arr as $k=>$v) {
    $selected=$context[privilege]==$k ? "selected" : "";
    echo "<option value=\"$k\" $selected>$v</option>\n";
  }
}

function makeselectgroupes() 

{
  global $context,$groupes;

  // cherche les groupes de l'utilisateur
  $groupes=array();
  if ($context[id] && $context[privilege]<LEVEL_ADMIN) {
    $result=mysql_query("SELECT idgroupe FROM $GLOBALS[tp]users_groupes WHERE iduser='$context[id]'") or die (mysql_error());
    while ($row=mysql_fetch_row($result)) array_push($groupes,$row[0]);
  }

  // cherche le nom des groupes sauf le groupe "tous"
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tp]groupes WHERE id>1") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=in_array($row[id],$groupes) ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[nom]</OPTION>\n";
  }
}

?>
