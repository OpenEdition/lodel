<?php


//
// fonction qui renvoie les valeures perennes: statut, groupe, ordre, iduser
//

function get_variables_perennes($context,$critere) 

{
    $groupe= ($admin && $context[groupe]) ? intval($context[groupe]) : "groupe";

    $result=mysql_query("SELECT ordre,$groupe,statut,iduser FROM $GLOBALS[tp]entites WHERE $critere") or die (mysql_error());
    if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits: get_variables_perennes"); }
    return mysql_fetch_row($result);
    // renvoie l'ordre, le groupe, le statut
}


//
// fonction qui retourne le statut d'une entite
//

function get_statut($id) 

{
    $result=mysql_query("SELECT statut FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
    if (!mysql_num_rows($result)) die ("l'entites '$id' n'existe pas");
    list ($statut)=mysql_fetch_row($result);
    return $statut;
    // renvoie l'ordre, le groupe, le statut
}


//
// fonction qui renvoie le groupe d'une entite.
//

function get_groupe($context,$idparent)

{
  global $admin,$usergroupes;

  // cherche le groupe et les droits
  if ($admin) { // on prend celui qu'on nous donne
    $groupe=intval($context[groupe]); if (!$groupe) $groupe=1;

  } elseif ($idparent) { // on prend celui du idparent
    $result=mysql_query("SELECT groupe FROM $GLOBALS[tp]entites WHERE id='$idparent' AND groupe IN ($usergroupes)") or die (mysql_error());
    if (!mysql_num_rows($result)) 	die("vous n'avez pas les droits: sortie 2");
    list($groupe)=mysql_fetch_row($result);
  } else {
    die("vous n'avez pas les droits: sortie 3");
  }
  return $groupe;
}


//
// gere la creation et la modification d'une entite
//

function enregistre_entite (&$context,$id,$classe,$champcritere,$returnonerror=TRUE) 

{
  global $home,$admin,$usergroupes;

  $iduser= $GLOBLAS[adminlodel] ? 0 : $GLOBALS[iduser];

  $entite=$context[entite];
#  if (!$context[nom]) $context[erreur_nom]=$err=1;
  $context[idtype]=intval($context[idtype]);
  if ($champcritere) $champcritere=" AND ".$champcritere;

  // check for errors and build the set
  $sets=array();
  require_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]champs.nom,type,condition,classe FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND classe='$classe' AND $GLOBALS[tp]champs.statut>0 AND $GLOBALS[tp]groupesdechamps.statut>0 $champcritere") or die (mysql_error());
  while (list($nom,$type,$condition)=mysql_fetch_row($result)) {
    require_once($home."textfunc.php");
    if ($condition=="+" && !trim($entite[$nom])) $err=$erreur[$nom]="+";
    switch ($type) {
    case "date" : 
      if (isset($entite[$nom])) {
	$entite[$nom]=mysqldate($entite[$nom]);
	if (!$entite[$nom]) $err=$erreur[$nom]="date";
      }
      break;
    case "int" :
      if (isset($entite[$nom]) && 
	  (!is_numeric($entite[$nom]) || intval($entite[$nom])!=$entite[$nom])) 
	$err=$erreur[$nom]="int";
      break;
    case "number" : if (isset($entite[$nom]) && 
			!is_numeric($entite[$nom])) $err=$erreur[$nom]="numeric";
      break;
    case "url" : if (isset($entite[$nom])) {
#      $validchar='-!#$%&\'*+\\\\\/0-9=?A-Z^_`a-z{|}~';
      $validchar='-0-9A-Z_a-z';
      if (!preg_match("/^[$validchar]+@([$validchar]+\.)+[$validchar]+$/",$entite[$nom])) $err=$erreur[$nom]="url";
    }
      break;
    case "boolean" :
      $entite[$nom]=$entite[$nom] ? 1 : 0;
      break;
    case "mltext" :
      if (is_array($entite[$nom])) {
	$str="";
	foreach($entite[$nom] as $lang=>$value) {
	  $value=trim($value);
	  if ($value) $str.="<r2r:ml lang=\"$lang\">$value</r2r:ml>";
	}
	$entite[$nom]=$str;
      }
    }
    $sets[$nom]="'".addslashes(stripslashes($entite[$nom]))."'"; // this is for security reason, only the authorized $nom are copied into sets. Add also the quote.
  } // end of while over the results

  if ($err) { 
    $context[erreur]=$erreur;
    if ($returnonerror) return FALSE;
  }
  
  $id=intval($context[id]);
  $idparent=intval($context[idparent]);

  lock_write($classe,"entites","relations",
	     "entites_personnes","personnes",
	     "entites_entrees","entrees","typeentrees","types");

  if ($id>0) { // UPDATE
    if ($id>0 && !$GLOBALS[admin]) {
      // verifie que le document est editable par cette personne
      $result=mysql_query("SELECT id FROM  $GLOBALS[tp]entites WHERE id='$id' AND groupe IN ($usergroupes)") or die(mysql_error());
      if (!mysql_num_rows($result)) die("vous n'avez pas les droits. Erreur dans l'interface");
    }
    // change group ?
    $groupeset= ($admin && $context[groupe]) ? ", groupe=".intval($context[groupe]) : "";
    // chage type ?
    $typeset=$context[idtype] ? ",idtype='$context[idtype]'" : "";
    // change statut ?
    $statut=get_statut($id);
    if ($statut<=-64 && $context[statut]) {
      $statut=intval($context[statut]);
      $statutset=",statut='$statut' ";
    }
    mysql_query("UPDATE $GLOBALS[tp]entites SET nom='$context[nom]' $typeset $groupeset $statutset WHERE id='$id'") or die(mysql_error());
    if ($grouperec && $admin) change_groupe_rec($id,$groupe);

    foreach ($sets as $nom=>$value) { $sets[$nom]=$nom."=".$value; }
    mysql_query("UPDATE $GLOBALS[tp]$classe SET ".join(",",$sets)." WHERE identite='$id'") or die (mysql_error());

  } else { // INSERT
    require_once($home."entitefunc.php");
    // cherche le groupe et les droits
    $groupe=get_groupe($context,$idparent);
    // cherche l'ordre
    $ordre=get_ordre_max("entites","idparent='$idparent'");
    $statut=$context[statut] ? intval($context[statut]) : -1; // non publie par defaut
    if (!$context[idtype]) { // prend le premier venu
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE classe='$classe' AND statut>0 ORDER BY ordre LIMIT 0,1") or die(mysql_error());
      if (!mysql_num_rows($result)) die("pas de type valide ?");
      list($context[idtype])=mysql_fetch_row($result);
    }
    mysql_query("INSERT INTO $GLOBALS[tp]entites (id,idparent,idtype,nom,ordre,statut,groupe,iduser) VALUES ('$id','$idparent','$context[idtype]','$context[nom]','$ordre','$statut','$groupe','$iduser')") or die (mysql_error());

    require_once($home."managedb.php");
    $id=mysql_insert_id();
    creeparente($id,$context[idparent],FALSE);

    mysql_query("INSERT INTO $GLOBALS[tp]$classe (identite,".join(",",array_keys($sets)).") VALUES ('$id',".join(",",$sets).")") or die (mysql_error());
  }  

  enregistre_personnes($context,$id,$statut,FALSE);
  enregistre_entrees($context,$id,$statut,FALSE);
  unlock();

  return $id;
}



function enregistre_personnes (&$context,$identite,$statut,$lock=TRUE)

{
  if ($lock) lock_write("entites_personnes","personnes");
  // detruit les liens dans la table entites_personnes
 mysql_query("DELETE FROM $GLOBALS[tp]entites_personnes WHERE identite='$identite'") or die (mysql_error());

 if (!$context[nomfamille]) { if ($lock) unlock(); return; }

  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  foreach (array_keys($context[nomfamille]) as $idtype) { // boucle sur les types
    foreach (array_keys($context[nomfamille][$idtype]) as $ind) { // boucle sur les ind
      // extrait les valeurs des differentes variables
      foreach ($vars as $var) {
	$bal[$var]=trim(addslashes(stripslashes(strip_tags($context[$var][$idtype][$ind]))));
      }
      // cherche si l'personne existe deja
      $result=mysql_query("SELECT id,statut FROM $GLOBALS[tp]personnes WHERE nomfamille='".$bal[nomfamille]."' AND prenom='".$bal[prenom]."'") or die (mysql_error());
      if (mysql_num_rows($result)>0) { // ok, l'personne existe deja
	list($id,$oldstatut)=mysql_fetch_array($result); // on recupere sont id et sont statut
	if ($statut>0 && $oldstatut<0) { // Faut-il publier l'personne ?
	  mysql_query("UPDATE $GLOBALS[tp]personnes SET statut=1 WHERE id='$id'") or die (mysql_error());
	}
      } else {
	mysql_query ("INSERT INTO $GLOBALS[tp]personnes (statut,nomfamille,prenom) VALUES ('$statut','$bal[nomfamille]','$bal[prenom]')") or die (mysql_error());
	$id=mysql_insert_id();
      }

      $ordre=$ind;

      // ajoute l'personne dans la table entites_personnes
      // ainsi que la description
      mysql_query("INSERT INTO $GLOBALS[tp]entites_personnes (idpersonne,identite,idtype,ordre,description,prefix,affiliation,fonction,courriel) VALUES ('$id','$identite','$idtype','$ordre','$bal[description]','$bal[prefix]','$bal[affiliation]','$bal[fonction]','$bal[courriel]')") or die (mysql_error());
    } // boucle sur les ind
  } // boucle sur les types
  if ($lock) unlock();
}



function enregistre_entrees (&$context,$identite,$statut,$lock=TRUE)

{
  if ($lock) lock_write("entites_entrees","entrees","typeentrees");
  // detruit les liens dans la table entites_indexhs
  mysql_query("DELETE FROM $GLOBALS[tp]entites_entrees WHERE identite='$identite'") or die (mysql_error());

#  print_r($context[entrees]);
  if (!$context[entrees]) { if ($lock) unlock(); return; }

  // boucle sur les differents entrees
  foreach ($context[entrees] as $idtype=>$entrees) {
    if ($context[autresentrees][$idtype]) {
      if ($entrees) {
	$entrees=array_merge($entrees,preg_split("/,/",$context[autresentrees][$idtype]));
      } else {
	$entrees=preg_split("/,/",$context[autresentrees][$idtype]);
      }
    } elseif (!$entrees) continue;
    $result=mysql_query("SELECT nvimportable,utiliseabrev FROM $GLOBALS[tp]typeentrees WHERE statut>0 AND id='$idtype'") or die (mysql_error());
    if (mysql_num_rows($result)!=1) die ("erreur interne");
    $typeentree=mysql_fetch_assoc($result);

    foreach ($entrees as $entree) {
      // on nettoie le contenu de l'entree
      $entree=trim(addslashes(strip_tags($entree))); 
      if (!$entree) continue; // etrange elle est vide... tant pis
      // cherche l'id de la entree si elle existe
      $result=mysql_query("SELECT id,statut FROM $GLOBALS[tp]entrees WHERE (abrev='$entree' OR nom='$entree')  AND statut>0 AND idtype='$idtype'") or die(mysql_error());

      #echo $entree,":",mysql_num_rows($result),"<br>";
      if (mysql_num_rows($result)) { // l'entree exists
	list($id,$oldstatut)=mysql_fetch_array($result);
	if ($statut>0 && $oldstatut<0) { // faut-il publier ?
	  mysql_query("UPDATE $GLOBALS[tp]entrees SET statut=abs(statut) WHERE id='$id'") or die (mysql_error());	
	}
      } elseif ($typeentree[nvimportable]) { // l'entree n'existe pas. est-ce qu'on a le droit de l'ajouter ?
	// oui,il faut ajouter le mot cle
	$abrev=$typeentree[utiliseabrev] ? strtoupper($entree) : "";
	mysql_query ("INSERT INTO $GLOBALS[tp]entrees (statut,nom,abrev,idtype,langue) VALUES ('$statut','$entree','$abrev','$idtype','$lang')") or die (mysql_error());
	$id=mysql_insert_id();
      } else {
	$id=0;
	// on ne l'ajoute pas... pas le droit!
      }
      // ajoute l'entree dans la table entites_entrees
      // on pourrait optimiser un peu ca... en mettant plusieurs values dans 
      // une chaine et en faisant la requette a la fin !
      if ($id)
	mysql_query("INSERT INTO $GLOBALS[tp]entites_entrees (identree,identite) VALUES ('$id','$identite')") or die (mysql_error());
    } // boucle sur les entrees d'un type
  } // boucle sur les type d'entree
  if ($lock) unlock();
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


function loop_champs($context,$funcname)

{
  global $erreur;

  $result=mysql_query("SELECT * FROM $GLOBALS[tp]champs WHERE idgroupe='$context[id]' AND statut>0 AND edition!='' ORDER BY ordre") or die(mysql_error());

  $haveresult=mysql_num_rows($result)>0;
  if ($haveresult) call_user_func("code_before_$funcname",$context);

  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    $localcontext[value]=$context[entite][$row[nom]];
    $localcontext[erreur]=$context[erreur][$row[nom]];

    call_user_func("code_do_$funcname",$localcontext);
  }

  if ($haveresult) call_user_func("code_after_$funcname",$context);
}

function loop_champs_require() { return array("id"); }



function loop_personnes(&$context,$funcname)

{
  global $id; // id de la publication

  $ind=0;
  $idtype=$context[id];
  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  do {
    $vide=TRUE;
    $localcontext=$context;
    $localcontext[ind]=++$ind;
    foreach($vars as $v) {
      $localcontext[$v]=$context[$v][$idtype][$ind];
      if ($vide && $localcontext[$v]) $vide=FALSE;
    }
    if ($vide && !$GLOBALS[plus][$idtype]) break;
    call_user_func("code_do_$funcname",$localcontext);
    if ($vide) break;
  } while (1);
}

function loop_personnes_require() { return array("id"); }


function extrait_personnes($identite,&$context)

{
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]personnes,$GLOBALS[tp]entites_personnes WHERE idpersonne=id  AND identite='$identite'") or die(mysql_error());

  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  while($row=mysql_fetch_assoc($result)) {
    foreach($vars as $var) {
      $context[$var][$row[idtype]][$row[ordre]]=$row[$var];
    }
  }
}

function extrait_entrees($identite,&$context)

{
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]entrees,$GLOBALS[tp]entites_entrees WHERE identree=id  AND identite='$identite'") or die(mysql_error());

  while($row=mysql_fetch_assoc($result)) {
    if ($context[entrees][$row[idtype]]) {
      array_push($context[entrees][$row[idtype]],$row[nom]);
    } else {
      $context[entrees][$row[idtype]]=array($row[nom]);
    }
  }
}


// makeselect


function makeselectentrees (&$context)
     // le context doit contenir les informations sur le type a traiter
{
  $entreestrouvees=array();
  $entrees=$context[entrees][$context[id]];
#  echo "type:",$context[id];print_r($context[entrees]);
  makeselectentrees_rec(0,"",$entrees,$context,&$entreestrouvees);
  $context[autresentrees]=join(", ",array_diff($entrees,$entreestrouvees));
}

function makeselectentrees_rec($idparent,$rep,$entrees,&$context,&$entreestrouvees)

{
  if (!$context[tri]) die ("ERROR: internal error in makeselectentrees_rec");
  $result=mysql_query("SELECT id, abrev, nom FROM $GLOBALS[tp]entrees WHERE idparent='$idparent' AND idtype='$context[id]' ORDER BY $context[tri]") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$entrees && (in_array($row[abrev],$entrees) || in_array($row[nom],$entrees)) ? " selected" : "";
   if ($selected) array_push($entreestrouvees,$row[nom],$row[abrev]);
   $value=$context[utiliseabrev] ? $row[abrev] : $row[nom];
    echo "<option value=\"$value\"$selected>$rep$row[nom]</option>\n";
    makeselectentrees_rec($row[id],$rep.$row[nom]."/",$entrees,$context,&$entreestrouvees);
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

function makeselecttype($classe)

{
  global $context;

  if ($context[typedocfixe]) $critere="AND type='$context[typedoc]'";

  $result=mysql_query("SELECT id,type,titre FROM $GLOBALS[tp]types WHERE statut>0 AND classe='$classe' $critere AND type NOT LIKE 'documentannexe-%'") or die (mysql_error());
  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[idtype]==$row[id] ? " selected" : "";
    $nom=$row[titre] ? $row[titre] : $row[type];
    echo "<option value=\"$row[id]\"$selected>$nom</option>\n";
  }
}


function makeselectdate() {
  global $context;

  foreach (array("maintenant",
		 "jours",
		 "mois",
		 "années") as $date) {
    $selected=$context[dateselect]==$date ? "selected" : "";
    echo "<option value=\"$date\"$selected>$date</option>\n";
  }
}


function loop_mltext($context,$funcname) {

#  print_r($context[value]);
  if (is_array($context[value])) {
    foreach($context[value] as $lang=>$value) {
      $localcontext=$context;
      $localcontext[lang]=$lang;
      $localcontext[value]=$value;
      call_user_func("code_do_$funcname",$localcontext);
    }
  # pas super cette regexp... mais l'argument a deja ete processe !
  } elseif (preg_match_all("/&lt;r2r:ml lang\s*=&quot;(\w+)&quot;&gt;(.*?)&lt;\/r2r:ml&gt;/s",$context[value],$results,PREG_SET_ORDER)) {

    foreach($results as $result) {
      $localcontext=$context;
      $localcontext[lang]=$result[1];
      $localcontext[value]=$result[2];
      call_user_func("code_do_$funcname",$localcontext);
    }
  }

  $lang=$context[addlanginmltext][$context[nom]];
#  echo "lang=$lang  $context[nom]";
#  print_r($context[addlanginmltext]);
  if ($lang) {
    $localcontext=$context;
    $localcontext[lang]=$lang;
    $localcontext[value]="";
    call_user_func("code_do_$funcname",$localcontext);
  }
}


?>
