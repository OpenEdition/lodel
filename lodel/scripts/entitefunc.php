<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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



//
// fonction qui renvoie les valeures perennes: status, groupe, rank, iduser
//

function get_variables_perennes($context,$critere) 

{
    $groupe= ($rightadmin && $context[groupe]) ? intval($context[groupe]) : "groupe";

    $result=mysql_query("SELECT rank,$groupe,status,iduser FROM $GLOBALS[tp]entities WHERE $critere") or die (mysql_error());
    if (!mysql_num_rows($result)) { die ("vous n'avez pas les rights: get_variables_perennes"); }
    return mysql_fetch_row($result);
    // renvoie l'rank, le groupe, le status
}


//
// fonction qui retourne le status d'une entite
//

function getstatus($id) 

{
    $result=mysql_query("SELECT status FROM $GLOBALS[tp]entities WHERE id='$id'") or die (mysql_error());
    if (!mysql_num_rows($result)) die ("l'entites '$id' n'existe pas");
    list ($status)=mysql_fetch_row($result);
    return $status;
    // renvoie l'rank, le groupe, le status
}


//
// fonction qui renvoie le groupe d'une entite.
//

function getusergroup($context,$idparent)

{
  global $rightadmin,$usergroupes;

  // cherche le groupe et les rights
  if ($rightadmin) { // on prend celui qu'on nous donne
    $groupe=intval($context[groupe]); if (!$groupe) $groupe=1;

  } elseif ($idparent) { // on prend celui du idparent
    $result=mysql_query("SELECT groupe FROM $GLOBALS[tp]entities WHERE id='$idparent' AND groupe IN ($usergroupes)") or die (mysql_error());
    if (!mysql_num_rows($result)) 	die("vous n'avez pas les rights: sortie 2");
    list($groupe)=mysql_fetch_row($result);
  } else {
    die("vous n'avez pas les rights: sortie 3");
  }
  return $groupe;
}


//
// gere la creation et la modification d'une entite
//

function enregistre_entite (&$context,$id,$class,$champcritere="",$returnonerror=TRUE) 

{
  global $home,$rightadmin,$usergroupes;

  $iduser= $GLOBALS[rightadminlodel] ? 0 : $GLOBALS[iduser];

  $entite=& $context[entite];
  $context[idtype]=intval($context[idtype]);
  $id=intval($context[id]);
  $idparent=intval($context[idparent]);

#  print_r($context);
  //
  // check we have the right to add such an entite
  //
  if ($id>0) {
    $result=mysql_query("SELECT idparent,idtype FROM $GLOBALS[tp]entities WHERE id='$id'") or die(mysql_error());
    list($idparent,$context[idtype])=mysql_fetch_row($result);
  }
  if ($idparent>0) {
    $result=mysql_query("SELECT condition FROM $GLOBALS[tp]entitytypes_entitytypes,$GLOBALS[tp]entities WHERE id='$idparent' AND idtypeentite2=idtype AND identitytype='$context[idtype]'") or die(mysql_error());
  } else {
    $result=mysql_query("SELECT condition FROM $GLOBALS[tp]entitytypes_entitytypes WHERE idtypeentite2=0 AND identitytype='$context[idtype]'") or die(mysql_error());

  }
  if (mysql_num_rows($result)<=0) die("ERROR: Entities of type $context[idtype] are not allowed in entity $idparent");
  //
  // ok, we are allowed to modifiy/add this entity.
  //

  // for new entite, all the field must be set with the defaut value if any.
  // for the old entite, the criteria must be true to modifie the field.
  if ($champcritere && $id) $extrawhere=" AND ".$champcritere;
  if ($champcritere) $champcritere=",(".$champcritere.")";

  // check for errors and build the set
  $sets=array();
  require_once ($home."connect.php");
  require_once ($home."champfunc.php");

  // file to move once the document id is know.
  $files_to_move=array();
  

  $result=mysql_query("SELECT $GLOBALS[tp]fields.name,type,condition,defaut,balises $champcritere FROM $GLOBALS[tp]fields,$GLOBALS[tp]fieldgroups WHERE idgroup=$GLOBALS[tp]fieldgroups.id AND class='$class' AND $GLOBALS[tp]fields.status>0 AND $GLOBALS[tp]fieldgroups.status>0 $extrawhere") or die (mysql_error());
  while (list($name,$type,$condition,$defaut,$balises,$critereok)=mysql_fetch_row($result)) {
    require_once($home."textfunc.php");
    // check if the field is required or not, and rise an error if any problem.

    if ( !$champcritere || $critereok) {
      if ($condition=="+" && !trim($entite[$name])) $error[$name]="+";
    } else {
      $entite[$name]="";
    }

    // clean automatically the fields when required.
    if (!is_array($entite[$name]) && trim($entite[$name]) && $GLOBALS[fieldtypes][$type][autostriptags]) $entite[$name]=trim(strip_tags($entite[$name]));
    // special processing depending on the type.
    switch ($type) {
    case "date" :
    case "datetime" :
    case "time" :
      include_once($home."date.php");
      if ($entite[$name]) {
	$entite[$name]=mysqldatetime($entite[$name],$type);
	if (!$entite[$name]) $error[$name]=$type;
      } elseif ($defaut) {
	$dt=mysqldatetime($defaut,$type);
	if ($dt) {
	  $entite[$name]=$dt;
	} else {
	  die("valeur par defaut non reconnue: \"$defaut\"");
	}
      }
      break;
    case "int" :
      if ((!isset($entite[$name]) || $entite[$name]==="") && $defaut!=="") $entite[$name]=intval($defaut);
      if (isset($entite[$name]) && 
	  (!is_numeric($entite[$name]) || intval($entite[$name])!=$entite[$name])) 
	$error[$name]="int";
      break;
    case "number" : 
      if ((!isset($entite[$name]) || $entite[$name]==="") && $defaut!=="") $entite[$name]=doubleval($defaut);
      if (isset($entite[$name]) && 
			!is_numeric($entite[$name])) $error[$name]="numeric";
      break;
    case "email" : 
      if (!$entite[$name] && $defaut) $entite[$name]=$defaut;
      if ($entite[$name]) {
#      $validchar='-!#$%&\'*+\\\\\/0-9=?A-Z^_`a-z{|}~';
	$validchar='-0-9A-Z_a-z';
	if (!preg_match("/^[$validchar]+@([$validchar]+\.)+[$validchar]+$/",$entite[$name])) $error[$name]="url";
      }
      break;
    case "url" : 
      if (!$entite[$name] && $defaut) $entite[$name]=$defaut;
      if ($entite[$name]) {
#      $validchar='-!#$%&\'*+\\\\\/0-9=?A-Z^_`a-z{|}~';
	$validchar='-0-9A-Z_a-z';
	if (!preg_match("/^(http|ftp):\/\/([$validchar]+\.)+[$validchar]+/",$entite[$name])) $error[$name]="url";
      }
      break;
    case "boolean" :
      $entite[$name]=$entite[$name] ? 1 : 0;
      break;
    case "mltext" :
      if (is_array($entite[$name])) {
	$str="";
	foreach($entite[$name] as $lang=>$value) {
	  $value=lodel_strip_tags(trim($value),$balises);
	  if ($value) $str.="<r2r:ml lang=\"$lang\">$value</r2r:ml>";
	}
	$entite[$name]=$str;
      }
      break;
    case 'image' :
    case 'fichier' :
      if ($context['error'][$name]) {  $error[$name]=$context['error'][$name]; break; } // error has been already detected
      if (is_array($entite[$name])) unset($entite[$name]);
      if (!$entite[$name] || $entite[$name]=="none") break;
      // check for a hack or a bug
      $lodelsource='lodel\/sources';
      $docannexe='docannexe\/'.$type.'\/([^\.\/]+)';
      if (!preg_match("/^(?:$lodelsource|$docannexe)\/[^\/]+$/",$entite[$name],$dirresult)) die("ERROR: bad filename in $name \"$entite[$name]\"");

      // if the filename is not "temporary", there is nothing to do
      if (!preg_match("/^tmpdir-\d+$/",$dirresult[1])) break;
      // add this file to the file to move.
      $files_to_move[$name]=array(filename=>$entite[$name],type=>$type,name=>$name);
      #unset($entite[$name]); // it must not be update... the old files must be remove later (once everything is checked)
      break;
    default :
      if (isset($entite[$name])) $entite[$name]=lodel_strip_tags($entite[$name],$balises);
      // recheck entite is still not empty
      if (!isset($entite[$name])) $entite[$name]=lodel_strip_tags($defaut,$balises);
    }
    if (isset($entite[$name])) {
      $sets[$name]="'".addslashes(stripslashes($entite[$name]))."'"; // this is for security reason, only the authorized $name are copied into sets. Add also the quote.
    }
  } // end of while over the results

  if ($error) { 
    $context['error']=$error;
    if ($returnonerror) return FALSE;
  }

  lock_write($class,"objets","entites","relations",
	     "entites_personnes","personnes",
	     "entites_entrees","entrees","typeentrees","types");

  if ($id>0) { // UPDATE
    if ($id>0 && !$GLOBALS[rightadmin]) {
      // verifie que le document est editable par cette personne
      $result=mysql_query("SELECT id FROM  $GLOBALS[tp]entities WHERE id='$id' AND groupe IN ($usergroupes)") or die(mysql_error());
      if (!mysql_num_rows($result)) die("vous n'avez pas les rights. Erreur dans l'interface");
    }
    // change group ?
    $groupeset= ($rightadmin && $context[groupe]) ? ", groupe=".intval($context[groupe]) : "";
    // change type ?
    $typeset=$context[idtype] ? ",idtype='$context[idtype]'" : "";
    // change status ?
    $status=getstatus($id);
    if ($status<=-64 && $context[status]) {
      $status=intval($context[status]);
      $statusset=",status='$status' ";
    }
    mysql_query("UPDATE $GLOBALS[tp]entities SET identifier='$context[identifier]' $typeset $groupeset $statusset WHERE id='$id'") or die(mysql_error());
    if ($grouperec && $rightadmin) change_groupe_rec($id,$groupe);

    move_files($id,$files_to_move,$sets);

    foreach ($sets as $name=>$value) { $sets[$name]=$name."=".$value; }
    if ($sets) mysql_query("UPDATE $GLOBALS[tp]$class SET ".join(",",$sets)." WHERE identity='$id'") or die (mysql_error());

  } else { // INSERT
    require_once($home."entitefunc.php");
    // cherche le groupe et les rights
    $groupe=getusergroup($context,$idparent);
    // cherche l'rank
    $rank=get_rank_max("entites","idparent='$idparent'");
    $status=$context[status] ? intval($context[status]) : -1; // non publie par defaut
    if (!$context[idtype]) { // prend le premier venu
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE class='$class' AND status>0 ORDER BY rank LIMIT 0,1") or die(mysql_error());
      if (!mysql_num_rows($result)) die("pas de type valide ?");
      list($context[idtype])=mysql_fetch_row($result);
    }
    $id=uniqueid($class);
    mysql_query("INSERT INTO $GLOBALS[tp]entities (id,idparent,idtype,identifier,rank,status,groupe,iduser) VALUES ('$id','$idparent','$context[idtype]','$context[identifier]','$rank','$status','$groupe','$iduser')") or die (mysql_error());

    require_once($home."managedb.php");
    creeparente($id,$context[idparent],FALSE);
    move_files($id,$files_to_move,$sets);

    $sets[identity]="'$id'";
    mysql_query("INSERT INTO $GLOBALS[tp]$class (".join(",",array_keys($sets)).") VALUES (".join(",",$sets).")") or die (mysql_error());
  }  

  enregistre_personnes($context,$id,$status,FALSE);
  enregistre_entrees($context,$id,$status,FALSE);

  if ($status>0) touch(SITEROOT."CACHE/maj");
  unlock();

  return $id;
}

function move_files($id,$files_to_move,&$sets)

{
  foreach ($files_to_move as $file) {
    $src=SITEROOT.$file[filename];
    $dest=basename($file[filename]); // basename
    if (!$dest) die("ERROR: error in move_files");
    // new path to the file
    $dirdest="docannexe/$file[type]/$id";
    if (!file_exists(SITEROOT.$dirdest)) {
      if (!@mkdir(SITEROOT.$dirdest,0777 & octdec($GLOBALS[filemask]))) die("ERROR: impossible to create the directory \"$dir\"");
    }
    $dest=$dirdest."/".$dest;
    $sets[$file[name]]="'".addslashes($dest)."'";
    if ($src==SITEROOT.$dest) continue;
    rename($src,SITEROOT.$dest);
    chmod (SITEROOT.$dest,0666 & octdec($GLOBALS[filemask]));
    @rmdir(dirname($src)); // do not complain, the directory may not be empty
  }
}



function enregistre_personnes (&$context,$identity,$status,$lock=TRUE)

{
  if ($lock) lock_write("objet","entites_personnes","personnes");
  // detruit les liens dans la table entites_personnes
 mysql_query("DELETE FROM $GLOBALS[tp]entities_persons WHERE identity='$identity'") or die (mysql_error());

 if (!$context[nomfamille]) { if ($lock) unlock(); return; }

 if ($status>-64 && $status<-1) $status=-1;
 if ($status>1) $status=1;

  $vars=array("prefix"=>1,"nomfamille"=>1,"prenom"=>1,"description"=>0,"fonction"=>0,"affiliation"=>0,"courriel"=>1);
  foreach (array_keys($context[nomfamille]) as $idtype) { // boucle sur les types
    foreach (array_keys($context[nomfamille][$idtype]) as $ind) { // boucle sur les ind
      // extrait les valeurs des differentes variables
      foreach ($vars as $var=>$strip) {
	$t=$strip ? strip_tags($context[$var][$idtype][$ind]) : $context[$var][$idtype][$ind];
	$bal[$var]=trim(addslashes(stripslashes($t)));
      }
      if (!$bal[prenom] && !$bal[nomfamille]) continue;
      // cherche si l'personne existe deja
      $result=mysql_query("SELECT id,status FROM $GLOBALS[tp]persons WHERE nomfamille='".$bal[nomfamille]."' AND prenom='".$bal[prenom]."'") or die (mysql_error());
      if (mysql_num_rows($result)>0) { // ok, l'personne existe deja
	list($id,$oldstatus)=mysql_fetch_array($result); // on recupere sont id et sont status
	if (($status>0 && $oldstatus<0) || ($oldstatus<=-64 && $status>$oldstatus)) { // Faut-il publier l'personne ?
	  mysql_query("UPDATE $GLOBALS[tp]persons SET status='$status' WHERE id='$id'") or die (mysql_error());
	}
      } else {
	$id=uniqueid("personnes");
	mysql_query ("INSERT INTO $GLOBALS[tp]persons (id,status,nomfamille,prenom) VALUES ('$id','$status','$bal[nomfamille]','$bal[prenom]')") or die (mysql_error());
      }

      $rank=$ind;

      // ajoute l'personne dans la table entites_personnes
      // ainsi que la description
      mysql_query("INSERT INTO $GLOBALS[tp]entities_persons (idperson,identity,idtype,rank,description,prefix,affiliation,fonction,courriel) VALUES ('$id','$identity','$idtype','$rank','$bal[description]','$bal[prefix]','$bal[affiliation]','$bal[fonction]','$bal[courriel]')") or die (mysql_error());
    } // boucle sur les ind
  } // boucle sur les types
  if ($lock) unlock();
}



function enregistre_entrees (&$context,$identity,$status,$lock=TRUE)

{
  if ($lock) lock_write("objets","entites_entrees","entrees","typeentrees");
  // detruit les liens dans la table entites_indexhs
  mysql_query("DELETE FROM $GLOBALS[tp]entities_entries WHERE identity='$identity'") or die (mysql_error());

 if ($status>-64 && $status<-1) $status=-1;
 if ($status>1) $status=1;

 // put the id's from entrees and autresentrees into idtypes
 $idtypes=$context[entrees] ? array_keys($context[entrees]) : array();
 if ($context[autresentrees]) $idtypes=array_unique(array_merge($idtypes,array_keys($context[autresentrees])));

  if (!$idtypes) { if ($lock) unlock(); return; }

  // boucle sur les differents entrees
  foreach ($idtypes as $idtype) {
    $entrees=$context[entrees][$idtype];
    if ($context[autresentrees][$idtype]) {
      if ($entrees) {
	$entrees=array_merge($entrees,preg_split("/,/",$context[autresentrees][$idtype]));
      } else {
	$entrees=preg_split("/,/",$context[autresentrees][$idtype]);
      }
    } elseif (!$entrees) continue;
    $result=mysql_query("SELECT nvimportable,utiliseabrev FROM $GLOBALS[tp]entrytypes WHERE status>0 AND id='$idtype'") or die (mysql_error());
    if (mysql_num_rows($result)!=1) die ("error interne");
    $typeentree=mysql_fetch_assoc($result);

    foreach ($entrees as $entree) {
      // est-ce que $entree est un tableau ou directement l'entree ?
      if (is_array($entree)) {
	$lang=$entree[lang]=="--" ? "" : $entree[lang];
	$entree=$entree[name];
      } else {
	$lang="";
      }
      // on nettoie le name de l'entree
      $entree=trim(strip_tags($entree));
      myquote($entree); // etrange ? pourquoi ajouter ce bout de code ???
      if (!$entree) continue; // etrange elle est vide... tant pis
      // cherche l'id de l'entree si elle existe
      $langcriteria=$lang ? "AND lang='$lang'" : "";
      $result=mysql_query("SELECT id,status FROM $GLOBALS[tp]entries WHERE (abrev='$entree' OR name='$entree') AND idtype='$idtype' $langcriteria") or die(mysql_error());

      #echo $entree,":",mysql_num_rows($result),"<br>";
      if (mysql_num_rows($result)) { // l'entree exists
	list($id,$oldstatus)=mysql_fetch_array($result);

	$statusset="";
	if ($oldstatus<=-64 && $status>$oldstatus) $statusset=$status;
	if ($status>0 && $oldstatus<0) $statusset="abs(status)"; // faut-il publier ?
	if ($statusset) {
	  mysql_query("UPDATE $GLOBALS[tp]entries SET status=$statusset WHERE id='$id'") or die (mysql_error());	
	}
      } elseif ($typeentree[nvimportable]) { // l'entree n'existe pas. est-ce qu'on a le right de l'ajouter ?
	// oui,il faut ajouter le mot cle
	$abrev=$typeentree[utiliseabrev] ? strtoupper($entree) : "";
	$id=uniqueid("entrees");
	mysql_query ("INSERT INTO $GLOBALS[tp]entries (id,status,name,abrev,idtype,lang) VALUES ('$id','$status','$entree','$abrev','$idtype','$lang')") or die (mysql_error());
      } else {
	$id=0;
	// on ne l'ajoute pas... pas le right!
      }
      // ajoute l'entree dans la table entites_entrees
      // on pourrait optimiser un peu ca... en mettant plusieurs values dans 
      // une chaine et en faisant la requette a la fin !
      if ($id) {
	mysql_query("INSERT INTO $GLOBALS[tp]entities_entries (identry,identity) VALUES ('$id','$identity')") or die (mysql_error());
      }
    } // boucle sur les entrees d'un type
  } // boucle sur les type d'entree
  if ($lock) unlock();
}


function lodel_strip_tags($text,$balises) 

{
  global $home;
  require_once($home."balises.php");
  static $accepted; // cache the accepted balise;
  global $multiplelevel,$xhtmlgroups;

  // simple case.
  if (!$balises) return strip_tags($text);

  if (!$accepted[$balises]) { // not cached ?
    $accepted[$balises]=array();

    // split the groupe of balises
    $groups=preg_split("/\s*;\s*/",$balises);
    array_push($groups,""); // balises speciales
    // feed the accepted string with accepted tags.
    foreach ($groups as $group) {
      // lodel groups
      if ($multiplelevel[$group]) {
	foreach($multiplelevel[$group] as $k=>$v) { $accepted[$balises]["r2r:$k"]=true; }
      }
	// xhtml groups
      if ($xhtmlgroups[$group]) {
	foreach($xhtmlgroups[$group] as $k=>$v) {
	  if (is_numeric($k)) { 
	    $accepted[$balises][$v]=true; // accept the tag with any attributs
	  } else {
	    // accept the tag with attributs matching unless it is already fully accepted
	    if (!$accepted[$balises][$k]) $accepted[$balises][$k][]=$v; // add a regexp
	  }
	}
      } // that was a xhtml group
    } // foreach group
  } // not cached.

#  print_r($accepted);

  $acceptedtags=$accepted[$balises];

  // the simpliest case.
  if (!$accepted) return strip_tags($text);

  $arr=preg_split("/(<\/?)(\w*:?\w+)\b([^>]*>)/",stripslashes($text),-1,PREG_SPLIT_DELIM_CAPTURE);

  $stack=array(); $count=count($arr);
  for($i=1; $i<$count; $i+=4) {
    #echo htmlentities($arr[$i].$arr[$i+1].$arr[$i+2]),"<br/>";
    if ($arr[$i]=="</") { // closing tag
      if (!array_pop($stack)) $arr[$i]=$arr[$i+1]=$arr[$i+2]="";
    } else { // opening tag
      $tag=$arr[$i+1];
      $keep=false;

#      echo $tag,"<br/>";
      if (isset($acceptedtags[$tag])) {
	// simple case.
	if ($acceptedtags[$tag]===true) { // simple
	  $keep=true;
	} else { // must valid the regexp
	  foreach ($acceptedtags[$tag] as $re) {
	    #echo $re," ",$arr[$i+2]," ",preg_match("/(^|\s)$re(\s|>|$)/",$arr[$i+2]),"<br/>";

	    if (preg_match("/(^|\s)$re(\s|>|$)/",$arr[$i+2])) { $keep=true; break; }
	  }
	}
#	echo "keep:$keep<br/>";
      }
      #echo ":",$arr[$i],$arr[$i+1],$arr[$i+2]," ",htmlentities(substr($arr[$i+2],-2)),"<br/>";
      if (substr($arr[$i+2],-2)!="/>")  // not an opening closing.
	array_push($stack,$keep); // whether to keep the closing tag or not.
      if (!$keep) { $arr[$i]=$arr[$i+1]=$arr[$i+2]=""; }

    }
  }

  // now, we know the accepted tags
  return join("",$arr);
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
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]entities WHERE idparent IN ($idlist)") or die(mysql_error());

    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);
    }
  } while ($idparents);

  // update toutes les publications
  $idlist=join(",",$ids);

  mysql_query("UPDATE $GLOBALS[tp]entities SET groupe='$groupe' WHERE id IN ($idlist)") or die(mysql_error());
  # cherche les ids
}


function loop_champs($context,$funcname)

{
  global $error;

  $result=mysql_query("SELECT * FROM $GLOBALS[tp]fields WHERE idgroup='$context[id]' AND status>0 AND edition!='' ORDER BY rank") or die(mysql_error());

  $haveresult=mysql_num_rows($result)>0;
  if ($haveresult) call_user_func("code_before_$funcname",$context);

  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    $localcontext[value]=$context[entite][$row[name]];
    $localcontext[error]=$context[error][$row[name]];

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


function extrait_personnes($identity,&$context)

{
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]persons,$GLOBALS[tp]entities_persons WHERE idperson=id  AND identity='$identity'") or die(mysql_error());

  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  while($row=mysql_fetch_assoc($result)) {
    foreach($vars as $var) {
      $context[$var][$row[idtype]][$row[rank]]=$row[$var];
    }
  }
}

function extrait_entrees($identity,&$context)

{
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]entries,$GLOBALS[tp]entities_entries WHERE identry=id  AND identity='$identity'") or die(mysql_error());

  while($row=mysql_fetch_assoc($result)) {
    if ($context[entrees][$row[idtype]]) {
      array_push($context[entrees][$row[idtype]],$row[name]);
    } else {
      $context[entrees][$row[idtype]]=array($row[name]);
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
  makeselectentrees_rec(0,"",$entrees,$context,$entreestrouvees);
  $context[autresentrees]=$entrees ? join(", ",array_diff($entrees,$entreestrouvees)) : "";
}

function makeselectentrees_rec($idparent,$rep,$entrees,&$context,&$entreestrouvees)

{
  if (!$context[tri]) die ("ERROR: internal error in makeselectentrees_rec");
  $result=mysql_query("SELECT id, abrev, name FROM $GLOBALS[tp]entries WHERE idparent='$idparent' AND idtype='$context[id]' ORDER BY $context[tri]") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$entrees && (in_array($row[abrev],$entrees) || in_array($row[name],$entrees)) ? " selected" : "";
   if ($selected) array_push($entreestrouvees,$row[name],$row[abrev]);
   $value=$context[utiliseabrev] ? $row[abrev] : $row[name];
    echo "<option value=\"$value\"$selected>$rep$row[name]</option>\n";
    makeselectentrees_rec($row[id],$rep.$row[name]."/",$entrees,$context,$entreestrouvees);
  }
}


function makeselectgroupes() 

{
  global $context;
      
  $result=mysql_query("SELECT id,name FROM $GLOBALS[tp]usergroups") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[groupe]==$row[id] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[name]</OPTION>\n";
  }
}

function makeselecttype($class)

{
  global $context;

  if ($context[typedocfixe]) $critere="AND type='$context[typedoc]'";

  $result=mysql_query("SELECT id,type,title FROM $GLOBALS[tp]types WHERE status>0 AND class='$class' $critere AND type NOT LIKE 'documentannexe-%'") or die (mysql_error());
  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[idtype]==$row[id] ? " selected" : "";
    $name=$row[title] ? $row[title] : $row[type];
    echo "<option value=\"$row[id]\"$selected>$name</option>\n";
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
  } elseif (preg_match_all("/&lt;r2r:ml lang\s*=&quot;(\w+)&quot;&gt;(.*?)&lt;\/r2r:ml&gt;/s",$context[value],$results,PREG_SET_ORDER) ||
	    preg_match_all("/<r2r:ml lang\s*=\"(\w+)\">(.*?)<\/r2r:ml>/s",$context[value],$results,PREG_SET_ORDER)    ) {

    foreach($results as $result) {
      $localcontext=$context;
      $localcontext[lang]=$result[1];
      $localcontext[value]=$result[2];
      call_user_func("code_do_$funcname",$localcontext);
    }
  }

  $lang=$context[addlanginmltext][$context[name]];
#  echo "lang=$lang  $context[name]";
#  print_r($context[addlanginmltext]);
  if ($lang) {
    $localcontext=$context;
    $localcontext[lang]=$lang;
    $localcontext[value]="";
    call_user_func("code_do_$funcname",$localcontext);
  }
}


?>
