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

// traitement particulier des attributs d'une loop
// l'essentiel des optimisations et aide a l'uitilisateur doivent
// en general etre ajouter ici
//

require_once($home."func.php");
require_once($home."balises.php");
require_once($home."parser.php");


class LodelParser extends Parser {
  var $filterfunc_loaded=FALSE;


function parse_loop_extra(&$tables,
			  &$tablesinselect,&$extrainselect,
			  &$select,&$where,&$ordre,&$groupby,&$having)

{
  global $site,$home;
  static $tablefields; // charge qu'une seule fois

  // convertion des codes specifiques dans le where
  // ce bout de code depend du parenthesage et du trim fait dans parse_loop.
  $where=preg_replace (array(
		    "/\(trash\)/i",
		    "/\(ok\)/i",
		    "/\(okgroupe\)/i"
		    ),
	      array(
		    "statut<=0",
		    "statut>0",
		    '".($GLOBALS[droitadmin] ? "1" : "(groupe IN ($GLOBALS[usergroupes]))")."'
		    ),$where);
  //

  $classes=array("documents","publications");
  foreach ($classes as $classe) {
    // gere les tables principales liees a entites
    $ind=array_search("$GLOBALS[tableprefix]$classe",$tables);
    if ($ind!==FALSE && $ind!==NULL) {
      array_push($tables,"$GLOBALS[tableprefix]entites");
      // put entites just after the classe table
      #print_r($tablesinselect);
      array_splice($tablesinselect,$ind+1,0,"$GLOBALS[tableprefix]entites");
      #print_r($tablesinselect);

      $where.=" AND $GLOBALS[tableprefix]$classe.identite=$GLOBALS[tableprefix]entites.id AND classe='$classe'";
    }
  }
#  echo "where 1:",htmlentities($where),"<br>";
  if (in_array("$GLOBALS[tableprefix]entites",$tables)) {
    if (preg_match("/\bclasse\b/",$where)) {
      array_push($tables,"$GLOBALS[tableprefix]types");
      protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]entites","id|statut|ordre");
      $jointypesentitesadded=1;
      $where.=" AND $GLOBALS[tableprefix]entites.idtype=$GLOBALS[tableprefix]types.id";
      ## c'est inutile pour le moment: preg_replace("/\bclasse\b/","$GLOBALS[tableprefix]types.classe",$where).
    }
#  echo "where 1bis:",htmlentities($where),"<br>";
    if (!$jointypesentitesadded && preg_match("/\btype\b/",$where)) {
      array_push($tables,"$GLOBALS[tableprefix]types");
      protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]entites","id|statut|ordre");
      $where.=" AND $GLOBALS[tableprefix]entites.idtype=$GLOBALS[tableprefix]types.id";
    }
    if (preg_match("/\bparent\b/",$where)) {
      array_push($tables,"$GLOBALS[tableprefix]entites as entites_interne2");
      protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]entites","id|idtype|identifiant|groupe|user|ordre|statut|idparent");
      $where=preg_replace("/\bparent\b/","entites_interne2.identifiant",$where)." AND entites_interne2.id=$GLOBALS[tableprefix]entites.idparent";
    }
    if (in_array("$GLOBALS[tableprefix]types",$tables)) { # compatibilite avec avant... et puis c'est pratique quand meme.
      $extrainselect.=", $GLOBALS[tableprefix]types.type , $GLOBALS[tableprefix]types.classe";
    }
  }// fin de entites

  // verifie le statut
  if (!preg_match("/\bstatut\b/i",$where)) { // test que l'element n'est pas a la poubelle
    if (!$tablefields) require($home."tablefields.php");
    $teststatut=array();
    if ($where) array_push($teststatut,$where);
    foreach ($tables as $table) {
      if (preg_match("/\sas\s+(\w+)/",$table,$result)) $table=$result[1];
#      echo "* ",$table,"    ",join(" ",array_keys($tablefields)),"<br/>";
#      print_r($tablefields[$table]);
      if ($tablefields[$table] &&
	  !in_array("statut",$tablefields[$table])) continue;
      if ($table=="$GLOBALS[tableprefix]session") continue;

      if ($table=="$GLOBALS[tableprefix]entites") {
	$lowstatut='"-64".($GLOBALS[droitadmin] ? "" : "*('.$table.'.groupe IN ($GLOBALS[usergroupes]))")';
      } else {
	$lowstatut="-64";
      }
      array_push($teststatut,"($table.statut>\".(\$GLOBALS[droitvisiteur] ? $lowstatut : \"0\").\")");
    }
    $where=join(" AND ",$teststatut);
  }
#  echo "where 2:",htmlentities($where),"<br>";

    if ($site) {
      ///////// CODE SPECIFIQUE -- gere les tables croisees
      #if (in_array("$GLOBALS[tableprefix]taches",$tables) && in_array("$GLOBALS[tableprefix]publications",$tables)) $where.=" AND publication=r2r_publications.id";

#      if (in_array("documents",$tables) && in_array("publications",$tables)) {
#	$where.=" AND publication=$GLOBALS[tableprefix]publications.id";
#      }
      //
      // les regexp ci-dessous sont insuffisantes, il faudrait tester que ce n'est pas dans une zone quotee de la clause where !!!!
      //

      // auteurs
     if (in_array("$GLOBALS[tableprefix]personnes",$tables)) {
       // fait ca en premier
       if (preg_match("/\b(iddocument|identite)\b/",$where)) {
	 // on a besoin de la table croisee entites_personnes
	 array_push($tables,"$GLOBALS[tableprefix]entites_personnes");
	 array_push($tablesinselect,"$GLOBALS[tableprefix]entites_personnes"); // on veut aussi recuperer les infos qui viennent de cette table
	 $where=preg_replace("/\biddocument\b/","identite",$where);
	 $where.=" AND idpersonne=$GLOBALS[tableprefix]personnes.id";
       }
       if (preg_match("/\btype\b/",$where)) {
	 protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]personnes","id|statut");
	 protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]entites_personnes","ordre");
	 array_push($tables,"$GLOBALS[tableprefix]typepersonnes");
	 // maintenant, il y a deux solutuions
	 if (!in_array("$GLOBALS[tableprefix]entites_personnes",$tables)) { // s'il n'y a pas cette table ca veut dire qu'on veut juste savoir s'il y a au moins une entree, donc il faut faire le groupeby.
	   array_push($tables,"$GLOBALS[tableprefix]entites_personnes");
	   $groupby.=" $GLOBALS[tableprefix]entites_personnes.idpersonne";
	 }
	 $where.=" AND $GLOBALS[tableprefix]entites_personnes.idtype=$GLOBALS[tableprefix]typepersonnes.id AND $GLOBALS[tableprefix]entites_personnes.idpersonne=$GLOBALS[tableprefix]personnes.id";
       }
     }
     if (in_array("$GLOBALS[tableprefix]entites",$tables) && preg_match("/\bidpersonne\b/",$where)) {
	// on a besoin de la table croise entites_personnes
	array_push($tables,"$GLOBALS[tableprefix]entites_personnes");
	$where.=" AND $GLOBALS[tableprefix]entites_personnes.identite=$GLOBALS[tableprefix]entites.id";
     }
     // entrees
     if (in_array("$GLOBALS[tableprefix]entrees",$tables)) {
	if (preg_match("/\btype\b/",$where)) {
	  protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]entrees","id|statut|ordre");
	  array_push($tables,"$GLOBALS[tableprefix]typeentrees");
	  $where.=" AND $GLOBALS[tableprefix]entrees.idtype=$GLOBALS[tableprefix]typeentrees.id";
	}
       if (preg_match("/\b(iddocument|identite)\b/",$where)) {
	  // on a besoin de la table croise entites_entrees
	  array_push($tables,"$GLOBALS[tableprefix]entites_entrees");
	  $where=preg_replace("/\biddocument\b/","identite",$where);
	  $where.=" AND identree=$GLOBALS[tableprefix]entrees.id";
	}
      }
      if (in_array("$GLOBALS[tableprefix]entites",$tables) && preg_match("/\bidentree\b/",$where)) {
	// on a besoin de la table croise entites_entrees
	array_push($tables,"$GLOBALS[tableprefix]entites_entrees");
	$where.=" AND $GLOBALS[tableprefix]entites_entrees.identite=$GLOBALS[tableprefix]entites.id";
      }
      if (in_array("$GLOBALS[tableprefix]groupes",$tables) && preg_match("/\biduser\b/",$where)) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"$GLOBALS[tableprefix]users_groupes");
	$where.=" AND idgroupe=$GLOBALS[tableprefix]groupes.id";
      }
      if (in_array("$GLOBALS[tableprefix]users",$tables) && in_array("$GLOBALS[tableprefix]session",$tables)) {
	$where.=" AND iduser=$GLOBALS[tableprefix]users.id";
      }
      if (in_array("$GLOBALS[tableprefix]champs",$tables) && preg_match("/\bclasse\b/",$where)) {
	// on a besoin de la table croise groupesdechamps
	protect5($select,$where,$ordre,$groupby,$having,"$GLOBALS[tableprefix]champs","id|statut|ordre");
	array_push($tables,"$GLOBALS[tableprefix]groupesdechamps");
	$where.=" AND $GLOBALS[tableprefix]groupesdechamps.id=$GLOBALS[tableprefix]champs.idgroupe";
	$extrainselect.=", $GLOBALS[tableprefix]groupesdechamps.classe";
     }
     // entrees

    } // site

    array_walk($tables,"prefixtablesindatabase");
    array_walk($tablesinselect,"prefixtablesindatabase");
}


//
// Traitement special des variables
//


function parse_variable_extra ($nomvar)

{
  // VARIABLES SPECIALES
  //
  if ($nomvar=="OKGROUPE") {
    return '($GLOBALS[droitadmin] || in_array($context[groupe],explode(\',\',$GLOBALS[usergroupes])))';
  }
  if (substr($nomvar,0,7)=="OPTION_") { // options
    return 'getoption("'.strtolower(substr($nomvar,7)).'")';
  }
  return FALSE;
}



//
// fonction qui gere les decodage du contenu des differentes parties
// d'une loop (DO*)
// fonction speciale pour lodel 
//


function decode_loop_content_extra ($balise,&$content,&$options,$tables)

{
  global $home;

  $havepublications=in_array("publications",$tables);
  $havedocuments=in_array("documents",$tables);
  //
  // est-ce qu'on veut le prev et next publication ?
  //

  // desactive le 10/03/04
//  if ($havepublications && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$content[$balise])) {
//    $content["PRE_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication($context);';
//  }
  // les filtrages automatiques
  if ($havedocuments || $havepublications) {
    $options[fetch_assoc_func]='filtered_mysql_fetch_assoc($context,';
    if (!$this->filterfunc_loaded) {
      $this->filterfunc_loaded=TRUE;
      $this->fct_txt.='if (!(@include_once("CACHE/filterfunc.php"))) require_once($GLOBALS[home]."filterfunc.php");';
    }
  }
}


} // end of the class LodelParser


function prefixtablesindatabase(&$table) {
#  if (($GLOBALS[database]!=$GLOBALS[currentdb]) &&
#      ($table=="$GLOBALS[tableprefix]sites" || 
#       $table=="$GLOBALS[tableprefix]session" ||
#       $table=="$GLOBALS[tableprefix]users")) {
#    $table=$GLOBALS[database].".".$table;
#  }
  if (($GLOBALS[database]!=$GLOBALS[currentdb]) &&
      ($table=="$GLOBALS[tableprefix]sites" || 
       $table=="$GLOBALS[tableprefix]session")) {
    $table=$GLOBALS[database].".".$table;
  }
}


// prefix les tables si necessaire
/*
function prefix_tablename ($tablename)

{
#ifndef LODELLIGHT
  return $tablename;
#else
#  preg_match_all("/\b(\w+)\./",$tablename,$result);
#  foreach ($result[1] as $tbl) {
#    $tablename=preg_replace ("/\b$tbl\./",$GLOBALS[tableprefix].$tbl.".",$tablename);
#  }
#  //  echo $tablename,"<br>";
#  return $tablename;
#endif
}
*/

function protect5(&$sql1,&$sql2,&$sql3,&$sql4,&$sql5,$table,$fields)

{
  protect($sql1,$table,$fields);
  protect($sql2,$table,$fields);
  protect($sql3,$table,$fields);
  protect($sql4,$table,$fields);
  protect($sql5,$table,$fields);
}


function protect (&$sql,$table,$fields)

{
  // regarde s'il y a des champs, deja
  if (!preg_match("/\b(?<!\\.)($fields)\b/",$sql)) return;

  // separe la chaine par les quotes qui ne sont pas escapes. 
  // ajoute un espace au debut pour des raisons de facilite
  $arr=preg_split("/(?<!\\\)'/",$sql);
  for($i=0;$i<count($arr);$i+=2)
    $arr[$i]=preg_replace("/\b(?<![\\.[])($fields)\b/","$table.\\1",$arr[$i]);
  $sql=join("'",$arr);
}
    

?>
