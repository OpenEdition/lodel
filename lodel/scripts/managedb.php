<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

require_once ($home."func.php");

//
// fonctions pour gerer la table relation
//

function creeparente($id,$idparent,$lock=TRUE)

{
  // id est l'id de la nouvelle entite
  // idparent est l'id de son parent directe

  // insert tous les ailleux
  if ($lock) lock_write("relations");
  // on ne peut pas faire un INSERT SELECT parce que c'est la meme table !

  $result=mysql_query("SELECT id1,degres FROM $GLOBALS[tp]relations WHERE id2='$idparent' AND nature='P'") or die(mysql_error());
  while ($row=mysql_fetch_assoc($result)) {
    $values.="('$row[id1]','$id','P','".($row[degres]+1)."'),";
  }
  $values.="('$idparent','$id','P',1)";

  mysql_query("INSERT INTO $GLOBALS[tp]relations (id1,id2,nature,degres) VALUES $values") or die(mysql_error());
  if ($lock) unlock();
}


//
// Boucles pour afficher les docu et publi protegees
// 

function loop_publications_protegees(&$context,$funcname) 

{
  proteges($context,$funcname,"publications");
}
function loop_documents_proteges(&$context,$funcname) 

{
  proteges($context,$funcname,"documents");
}

function proteges (&$context,$funcname,$classe) 

{
  // cherche les ids
  if (!$context[proteges]) return;
  $ids=join(",",$context[proteges]);
  $result=mysql_query("SELECT *,type  FROM ($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]$classe ON identite=$GLOBALS[tp]entites.id WHERE $GLOBALS[tp]entites.id IN ($ids) AND $GLOBALS[tp]types.classe='$classe'") or die (mysql_error());
  $haveresults=mysql_num_rows($result);
  if ($haveresults && function_exists("code_before_$funcname"))  call_user_func("code_before_$funcname",$localcontext);
  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    call_user_func("code_do_$funcname",$localcontext);
  }
  if ($haveresults && function_exists("code_after_$funcname"))  call_user_func("code_after_$funcname",$localcontext);
}


//
//  Fonctions de suppression
//



function supprime ($id, $confirmation=FALSE, $mklock=TRUE, $critere="")

{
  if (!($id>=1)) die("ERROR: id is not valid un \"supprime\"");
  global $usergroupes,$droitadmin,$context;
  if ($mklock) {
    lock_write("objets","entites",
	       "publications","documents",
	       "personnes","entites_personnes",
	       "entrees","entites_entrees",
	       "relations");
  }

  $critere.=$droitadmin ? "" : " AND groupe IN ($usergroupes)";

  // verifie les tables a joindre pour le critere
  // a completer si necessaire
  if ($critere && preg_match("/\btype\b/",$critere)) {
    $tables=$GLOBALS[entitestypesjoin];
  } else {
    $tables=$GLOBALS[tp]."entites";
  }

  $context[proteges]=array();

  if (!$droitadmin) {
    // cherche l'id de la publication/document courante $id... verifie qu'on a les droits
    $result=mysql_query("SELECT statut FROM $tables WHERE $GLOBALS[tp]entites.id='$id' $critere") or die(mysql_error());
    if (!mysql_num_rows($result)) die("vous n'avez pas les droits. Erreur dans l'interface.");
    if (!$confirmation) {
      $row=mysql_fetch_assoc($result);
      if ($row[statut]>=32) {
	// ajoute au tableau des entitess protegees cet id
	array_push($context[proteges],$id);
      }
    }
  }

  // cherche les entites a supprimer
  $ids=array($id);

  // cherche les entites a detruire...

  $result=mysql_query("SELECT $GLOBALS[tp]entites.id,$GLOBALS[tp]entites.statut FROM $tables,$GLOBALS[tp]relations WHERE id1='$id' AND id2=$GLOBALS[tp]entites.id AND nature='P' $critere") or die(mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    array_push ($ids,$row[id]);
    if (!$confirmation && $row[statut]>=32) {
      // ajoute au tableau des entites protegees cet id
      array_push($context[proteges],$row[id]);
    }
  }

  if ($context[proteges]) { if ($mklock) unlock(); return FALSE; } // on ne peut pas supprimer, des entitess sont protegees


  // delete toutes les entitess
  $idlist=join(",",$ids);

  mysql_query("DELETE FROM $GLOBALS[tp]entites WHERE id IN ($idlist)") or die(mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tp]publications WHERE identite IN ($idlist)") or die(mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tp]documents WHERE identite IN ($idlist)") or die(mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tp]relations WHERE id1 IN ($idlist) OR id2 IN ($idlist)") or die(mysql_error());
  deleteuniqueid($ids);

  supprime_table($ids,"personne");
  supprime_table($ids,"entree");

  if ($mklock) unlock();
  return TRUE;
}


function supprime_table($ids,$table,$deletetable=TRUE,$deletecritere="")

{
  $tables=$table."s";

  if (is_numeric($ids)) { # on a un seul document
    $critere="identite=".$ids;
  } else {
    $critere="identite IN (".join(",",$ids).")";
  }
  mysql_query("DELETE FROM $GLOBALS[tp]entites_$tables WHERE $critere") or die (mysql_error());

  if (!$deletetable) return;

  if ($deletecritere) $deletecritere.=" AND ";
  # efface tous les items qui ne sont pas dans entites_items... ce sont ceux qu'il faut detruire ou depublie
  $result=mysql_query("SELECT id FROM $GLOBALS[tp]$tables LEFT JOIN $GLOBALS[tp]entites_$tables ON id=id$table WHERE $deletecritere id$table is NULL") or die (mysql_error());
  
  $ids=array();
  while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
  if ($ids) { 
    // cherche ceux qui ne sont pas proteges
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]$tables WHERE id IN (".join(",",$ids).") AND (statut>-32 AND statut<32)");
    $idstodelete=array();
    while ($row=mysql_fetch_row($result)) { array_push ($idstodelete,$row[0]); }

    if ($idstodelete) {
      // efface ceux qui ne sont pas proteges
      mysql_query("DELETE FROM $GLOBALS[tp]$tables WHERE id IN (".join(",",$idstodelete).")") or die (mysql_error());
      deleteuniqueid($idstodelete);
    }

    // depublie ceux qui sont proteges.
    mysql_query("UPDATE $GLOBALS[tp]$tables SET statut=-abs(statut) WHERE id IN (".join(",",$ids).") AND statut>=32") or die (mysql_error());
  }
}


//
//  Fonctions de publication
//



function publi ($id,$statut,$confirmation,$mklock=TRUE)

{
  global $usergroupes,$droitadmin,$context;

  if ($mklock) {
    lock_write("entites",
	       "personnes","entites_personnes",
	       "entrees","entites_entrees",
	       "relations"); 
  }

  //
  // cherche les entitess a publier ou depublier
  //

  $critere=$droitadmin ? "" : " AND groupe IN ($usergroupes)";

  $ids=array();
  $context[proteges]=array();

  // cherche le statut (et l'id) de la publication courante
  $result=mysql_query("SELECT id,statut FROM $GLOBALS[tp]entites WHERE id='$id' AND statut>-32 $critere") or die(mysql_error());
  $row=mysql_fetch_assoc($result);

  if (!$row) die ("Vous n'avez pas les droits sur cette publication ou ce document... il y a un probleme dans l'interface");

    // verifie que la publication est depubliable
    // elle n'est pas depubliable si on n'a pas confirme et si son statut est 32 ou plus.
  if ($statut<0 && !$confirmation && $row[statut]>=32) {
    // ajoute au tableau des entitess protegees cet id
    array_push($context[proteges],$row[id]);
  }
  array_push ($ids,$row[id]);

  $result=mysql_query("SELECT id,statut FROM $GLOBALS[tp]entites,$GLOBALS[tp]relations WHERE id1='$id' AND id2=id AND nature='P' AND  statut>-32 $critere") or die(mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    if ($statut<0 && !$confirmation && $row[statut]>=32) {
      // ajoute au tableau des entitess protegees cet id
      array_push($context[proteges],$row[id]);
    }
    array_push ($ids,$row[id]);
  }

  if ($context[proteges] || !$ids) { if ($mklock) unlock(); return FALSE; } // on ne peut pas depublier

  // determine le critere pour l'update

  $critere=" id IN (".join(",",$ids).")";

  // mais attention, il ne faut pas reduire le statut quand on publie
  $extracritere=$statut>0 ? " AND statut<$statut" : ""; 

  mysql_query("UPDATE $GLOBALS[tp]entites SET statut=$statut WHERE $critere $extracritere") or die(mysql_error());

  publi_table($critere,$statut,"personne");
  publi_table($critere,$statut,"entree");

  return TRUE;
}


  

function publi_table($critere,$statut,$table)

{
  $statut=$statut>0 ? 1 : -1; // dans les tables le statut est seulement a +1 ou -1

# on pourrait utiliser le statut comme un compteur du nombre de document qui y 
# font reference, et ainsi mettre hors ligne facilement les personnes,
# mais cette procedure risque de buguer a terme.
  $tables=$table."s";
# cherches les id$tables a changer
  # identite ?

  if (is_numeric($critere)) { # on a un seul document
    $identite=intval($critere);
    $result=mysql_query("SELECT id$table FROM $GLOBALS[tp]entites_$tables WHERE identite=$identite") or die (mysql_error());
  } else { # on a une condition sur les documents
    $result=mysql_query("SELECT id$table FROM $GLOBALS[tp]entites_$tables,$GLOBALS[tp]entites WHERE identite=id AND $critere AND statut>-32") or die (mysql_error());
  }
  $ids=array();
  while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
  if (!$ids) return; # il n'y a rien a modifier
  $idlist=join(",",$ids);

  if ($statut>0) {
    // dans ce cas c'est simple
    mysql_query ("UPDATE $GLOBALS[tp]$tables SET statut=abs(statut) WHERE id IN ($idlist)") or die (mysql_error());
   

  } else { // statut<0
    # la c'est plus complique, il faut selectionner les $tables qui n'ont pas de document online... celui qu'on publie

    $result=mysql_query ("SELECT id$table FROM $GLOBALS[tp]entites_$tables, $GLOBALS[tp]entites WHERE identite=$GLOBALS[tp]entites.id AND $GLOBALS[tp]entites.statut>0 AND id$table IN ($idlist)") or die (mysql_error());
    $ids=array();
    while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
    if ($ids) $where="AND id NOT IN (".join(",",$ids).")";
    mysql_query("UPDATE $GLOBALS[tp]$tables SET statut=-abs(statut) WHERE id IN ($idlist) $where") or die (mysql_error());
  }
}


?>
