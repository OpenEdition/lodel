<?php
//
// gere la creation et la modification d'une publication
//

function pub_edition (&$context,$critere) 

{
  global $home,$admin,$usergroupes;

  if (!$context[nom]) $context[erreur_nom]=$err=1;
  if (!$context[idtype]) die ("pub_edition: manque l'idtype"); 

  include_once($home."date.php");
  if ($context[date]) {
    $date=mysqldate($context[date]);
    if (!$date) { $context[erreur_date]=$err=1; }
  } else { $date=""; }

  if ($err) return FALSE;
  include_once ($home."connect.php");
  
  $id=intval($context[id]);
  $idparent=intval($context[idparent]);

  if ($id && $context[grouperec] && $admin) {
    lock_write("publications","documents","entites","relations");
  } else {
    lock_write("publications","entites","relations");
  }
  require_once($home."entitefunc.php");
  if ($id>0) { // il faut rechercher le status, l'ordre, le groupe
    if (!$critere) die ("erreur interne");
    list($ordre,$groupe,$status)=get_variables_perennes($context,$critere);
  } else { 
    // cherche le groupe et les droits
    $groupe=get_groupe($critere,$idparent);
    // cherche l'ordre
    $ordre=get_ordre_max("entites");
    $status=-1; // non publie par defaut
    $meta="";
  }
#  $meta=addmeta($context,$meta);


  mysql_query ("REPLACE INTO $GLOBALS[tp]entites (id,idparent,idtype,nom,ordre,status,groupe) VALUES ('$id','$idparent','$context[idtype]','$context[nom]','$ordre','$status','$groupe')") or die (mysql_error());

  require_once($home."managedb.php");
  if (!$id) {
    $id=mysql_insert_id();
    creeparente($id,$context[idparent],FALSE);
  }

  mysql_query("REPLACE INTO $GLOBALS[tp]publications (identite,titre,soustitre,directeur,texte,date) VALUES ('$id','$context[titre]','$context[soustitre]','$context[directeur]','$context[texte]','$date')") or die (mysql_error());
  
  if ($id && $grouperec && $admin) change_groupe_rec($id,$groupe);
  if (!$id) $id=mysql_insert_id();
  unlock();
  enregistre_personnes($context,$id,$status);
  enregistre_entrees($context,$id,$status);

  return $id;
}



function change_groupe_rec($id,$groupe)

{

##### a reecrire avec la table relation
##### a reecrire avec la table relation
##### a reecrire avec la table relation
##### a reecrire avec la table relation

  // cherche les publis a changer
  $ids=array($id);
  $idparents=array($id);

  do {
    $idlist=join(",",$idparents);
    // cherche les fils de idparents
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]entites WHERE idparent IN ($idlist)") or die(mysql_error());

    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);
    }
  } while ($idparents);

  // update toutes les publications
  $idlist=join(",",$ids);

  mysql_query("UPDATE $GLOBALS[tp]entites SET groupe='$groupe' WHERE id IN ($idlist)") or die(mysql_error());
  # cherche les ids
}


function enregistre_personnes (&$context,$identite,$status)

{
  lock_write("entites_personnes","personnes");
  // detruit les liens dans la table entites_personnes
 mysql_query("DELETE FROM $GLOBALS[tp]entites_personnes WHERE identite='$identite'") or die (mysql_error());

 if (!$context[nomfamille]) { unlock(); return; }

  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  foreach (array_keys($context[nomfamille]) as $idtype) { // boucle sur les types
    foreach (array_keys($context[nomfamille][$idtype]) as $ind) { // boucle sur les ind
      // extrait les valeurs des differentes variables
      foreach ($vars as $var) {
	$bal[$var]=trim(addslashes(stripslashes(strip_tags($context[$var][$idtype][$ind]))));
      }
      // cherche si l'personne existe deja
      $result=mysql_query("SELECT id,status FROM $GLOBALS[tp]personnes WHERE nomfamille='".$bal[nomfamille]."' AND prenom='".$bal[prenom]."'") or die (mysql_error());
      if (mysql_num_rows($result)>0) { // ok, l'personne existe deja
	list($id,$oldstatus)=mysql_fetch_array($result); // on recupere sont id et sont status
	if ($status>0 && $oldstatus<0) { // Faut-il publier l'personne ?
	  mysql_query("UPDATE $GLOBALS[tp]personnes SET status=1 WHERE id='$id'") or die (mysql_error());
	}
      } else {
	mysql_query ("INSERT INTO $GLOBALS[tp]personnes (status,nomfamille,prenom) VALUES ('$status','$bal[nomfamille]','$bal[prenom]')") or die (mysql_error());
	$id=mysql_insert_id();
      }

      $ordre=$ind;

      // ajoute l'personne dans la table entites_personnes
      // ainsi que la description
      mysql_query("INSERT INTO $GLOBALS[tp]entites_personnes (idpersonne,identite,idtype,ordre,description,prefix,affiliation,fonction,courriel) VALUES ('$id','$identite','$idtype','$ordre','$bal[description]','$bal[prefix]','$bal[affiliation]','$bal[fonction]','$bal[courriel]')") or die (mysql_error());
    } // boucle sur les ind
  } // boucle sur les types
  unlock();
}



function enregistre_entrees (&$context,$identite,$status)

{
  lock_write("entites_entrees","entrees","typeentrees");
  // detruit les liens dans la table entites_indexhs
  mysql_query("DELETE FROM $GLOBALS[tp]entites_entrees WHERE identite='$identite'") or die (mysql_error());

  if (!$context[entrees]) { unlock(); return; }

  // boucle sur les differents entrees
  foreach ($context[entrees] as $idtype=>$entrees) {
    if ($context[autresentrees][$idtype]) {
      if ($entrees) {
	$entrees=array_merge($entrees,preg_split("/,/",$context[autresentrees][$idtype]));
      } else {
	$entrees=preg_split("/,/",$context[autresentrees][$idtype]);
      }
    } elseif (!$entrees) continue;

    $result=mysql_query("SELECT newimportable,useabrev FROM $GLOBALS[tp]typeentrees WHERE status>0 AND id='$idtype'") or die (mysql_error());
    if (mysql_num_rows($result)!=1) die ("erreur interne");
    $typeentree=mysql_fetch_assoc($result);

    foreach ($entrees as $entree) {
      // on nettoie le contenu de l'entree
      $entree=trim(addslashes(strip_tags($entree))); 
      if (!$entree) continue; // etrange elle est vide... tant pis
      // cherche l'id de la entree si elle existe
      $result=mysql_query("SELECT id,status FROM $GLOBALS[tp]entrees WHERE (abrev='$entree' OR nom='$entree')  AND status>0 AND idtype='$idtype'") or die(mysql_error());

      if (mysql_num_rows($result)) { // l'entree exists
	list($id,$oldstatus)=mysql_fetch_array($result);
	if ($status>0 && $oldstatus<0) { // faut-il publier ?
	  mysql_query("UPDATE $GLOBALS[tp]entrees SET status=1 WHERE id='$id'") or die (mysql_error());	
	}
      } elseif ($typeentree[newimportable]) { // l'entree n'existe pas. est-ce qu'on a le droit de l'ajouter ?
	// oui,il faut ajouter le mot cle
	$abrev=$typeentree[useabrev] ? strtoupper($entree) : "";
	mysql_query ("INSERT INTO $GLOBALS[tp]entrees (status,nom,abrev,idtype,lang) VALUES ('$status','$entree','$abrev','$idtype','$lang')") or die (mysql_error());
	$id=mysql_insert_id();
      } else {
	// non, on ne l'ajoute pas... mais il y a une erreur quelque part...
	$id=0;
	die ("erreur interne 2 dans enregistre_entrees: type: $balise entree: $entree");
      }
      // ajoute l'entree dans la table entites_entrees
      // on pourrait optimiser un peu ca... en mettant plusieurs values dans 
      // une chaine et en faisant la requette a la fin !
      if ($id)
	mysql_query("INSERT INTO $GLOBALS[tp]entites_entrees (identree,identite) VALUES ('$id','$identite')") or die (mysql_error());
    } // boucle sur les entrees d'un type
  } // boucle sur les type d'entree
  unlock();
}


?>
