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
// fonction qui renvoie le groupe d'une entite.
//

function getusergroup($context,$idparent)

{
  global $user;

  // cherche le groupe et les rights
  if ($user['admin']) { // on prend celui qu'on nous donne
    $usergroup=intval($context[usergroup]); if (!$usergroup) $usergroup=1;

  } elseif ($idparent) { // on prend celui du idparent
    $usergroup=getone("SELECT usergroup FROM #_TP_entities WHERE id='$idparent' AND usergroup IN (".$user['groups'].")");
    if ($db->errorno()) die($db->errormsg());
    if (!$usergroup) die("ERROR: You have not the rights: (2)");
  } else {
    die("ERROR: You have not the rights: (3)");
  }
  return $usergroup;
}


//
// gere la creation et la modification d'une entite
//

function enregistre_entite (&$context,$id,$class,$champcritere="",$returnonerror=TRUE) 

{
  global $db,$home,$user;

  $iduser= $user['adminlodel'] ? 0 : $user['id'];

  $entity=& $context['entity'];
  $context['idtype']=intval($context['idtype']);
  $id=intval($context['id']);
  $idparent=intval($context['idparent']);

#  print_r($context);
  //
  // check we have the right to add such an entity
  //
  if ($id>0) {
    $row=$db->getrow(lq("SELECT idparent,idtype FROM #_TP_entities WHERE id='$id'"));
    if ($row===false) die($db->errormsg());
    list($idparent,$context['idtype'])=$row;
  }
  if ($idparent>0) {
    $condition=getone(lq("SELECT condition FROM #_TP_entitytypes_entitytypes,#_TP_entities WHERE id='$idparent' AND identitytype2=idtype AND identitytype='$context[idtype]'"));
  } else {
    $condition=$db->getone(lq("SELECT condition FROM #_TP_entitytypes_entitytypes WHERE identitytype2=0 AND identitytype='$context[idtype]'"));

  }
  if ($db->errorno()) die($db->errormsg());
  if ($condition===false) die("ERROR: Entities of type '$context[idtype]' are not allowed in entity $idparent");
  //
  // ok, we are allowed to modifiy/add this entity.
  //

  // for new entity, all the field must be set with the defaut value if any.
  // for the old entity, the criteria must be true to modifie the field.
  if ($champcritere && $id) $extrawhere=" AND ".$champcritere;
  if ($champcritere) $champcritere=",(".$champcritere.")";

  // check for errors and build the set
  $sets=array();
  require_once ($home."connect.php");
  require_once ($home."champfunc.php");
  require_once ($home."validfunc.php");

  // file to move once the document id is know.
  $files_to_move=array();
  

  $result=$db->execute(lq("SELECT #_TP_fields.name,type,condition,default,allowedtags $champcritere FROM #_TP_fields,#_TP_fieldgroups WHERE idgroup=#_TP_fieldgroups.id AND class='$class' AND #_TP_fields.status>0 AND #_TP_fieldgroups.status>0 $extrawhere")) or die($db->errormsg());
  while (!$result->EOF) {
    list($name,$type,$condition,$default,$allowedtags,$critereok)=$result->fields;
    require_once($home."textfunc.php");
    // check if the field is required or not, and rise an error if any problem.

    if ( !$champcritere || $critereok) {
      if ($condition=="+" && !trim($entity[$name])) $error[$name]="+";
    } else {
      $entity[$name]="";
    }

    // clean automatically the fields when required.
    if (!is_array($entity[$name]) && trim($entity[$name]) && $GLOBALS['fieldtypes'][$type]['autostriptags']) $entity[$name]=trim(strip_tags($entity[$name]));
    // special processing depending on the type.

    $valid=validfield($entity[$name],$type,$default);
    if ($type="text" && $valid===true) {
      // good, nothing to do.
      // text is handle in a particular way here
    } elseif (is_string($valid)) {
      // error
      $error[$name]=$valid;
    } else {
      // not validated... let's try other type
      switch($type) {
      case "mltext" :
      if (is_array($text)) {
	$str="";
	foreach($text as $lang=>$value) {
	  $value=lodel_strip_tags(trim($value),$allowedtags);
	  if ($value) $str.="<r2r:ml lang=\"$lang\">$value</r2r:ml>";
	}
	$text=$str;
      }
      break;
    case 'image' :
    case 'fichier' :
      if ($context['error'][$name]) {  $error[$name]=$context['error'][$name]; break; } // error has been already detected
      if (is_array($text)) unset($text);
      if (!$text || $text=="none") break;
      // check for a hack or a bug
      $lodelsource='lodel\/sources';
      $docannexe='docannexe\/'.$type.'\/([^\.\/]+)';
      if (!preg_match("/^(?:$lodelsource|$docannexe)\/[^\/]+$/",$text,$dirresult)) die("ERROR: bad filename in $name \"$text\"");

      // if the filename is not "temporary", there is nothing to do
      if (!preg_match("/^tmpdir-\d+$/",$dirresult[1])) break;
      // add this file to the file to move.
      $files_to_move[$name]=array('filename'=>$text,'type'=>$type,'name'=>$name);
      #unset($text); // it must not be update... the old files must be remove later (once everything is checked)
      break;
      default :
	if (isset($entity[$name])) $entity[$name]=lodel_strip_tags($entity[$name],$allowedtags);
	// recheck entity is still not empty
	if (!isset($entity[$name])) $entity[$name]=lodel_strip_tags($default,$allowedtags);
      }
    }
    if (isset($entity[$name])) {
      $sets[$name]="'".addslashes(stripslashes($entity[$name]))."'"; // this is for security reason, only the authorized $name are copied into sets. Add also the quote.
    }
    $result->MoveNext();
  } // end of while over the results

  if ($error) { 
    $context['error']=$error;
    if ($returnonerror) return FALSE;
  }

  #lock_write($class,"objets","entity","relations",
#	     "entity_personnes","personnes",
#	     "entity_entrees","entrees","entrytypes","types");

  if ($id>0) { // UPDATE
    if ($id>0 && !$user['admin']) {
      // verifie que le document est editable par cette personne
      $hasright=getone(lq("SELECT id FROM  #_TP_entities WHERE id='$id' AND usergroup IN (".$user['groups'].")"));
      if ($db->errorno()) die($db->errormsg());
      if (!$hasright) die("ERROR: You are not allowed. This is likely due to an error in the interface");
    }
    // change group ?
    $usergroupset=($user['admin'] && $context['usergroup']) ? ", usergroup=".intval($context['usergroup']) : "";
    // change type ?
    $typeset=$context['idtype'] ? ",idtype='$context[idtype]'" : "";
    // change status ?
    $status=getstatus($id);
    if ($status<=-64 && $context['status']) {
      $status=intval($context['status']);
      $statusset=",status='$status' ";
    }
    $db->execute(lq("UPDATE #_TP_entities SET identifier='$context[identifier]' $typeset $usergroupset $statusset WHERE id='$id'")) or die($db->errormsg());
    if ($usergrouprec && $user['admin']) change_usergroup_rec($id,$usergroup);

    move_files($id,$files_to_move,$sets);

    foreach ($sets as $name=>$value) { $sets[$name]=$name."=".$value; }
    if ($sets) $db->execute(lq("UPDATE #_TP_$class SET ".join(",",$sets)." WHERE identity='$id'")) or die($db->errormsg());

  } else { // INSERT
    require_once($home."entitefunc.php");
    // cherche le groupe et les rights
    $usergroup=getusergroup($context,$idparent);
    // cherche l'rank
    $rank=get_rank_max("entity","idparent='$idparent'");
    $status=$context['status'] ? intval($context['status']) : -1; // non publie par default
    if (!$context['idtype']) { // prend le premier venu
      $context['idtype']=getone(lq("SELECT id FROM #_TP_types WHERE class='$class' AND status>0 ORDER BY rank"));
      if ($db->errorno()) die($db->errormsg());
      if ($context['idtype']===false) die("pas de type valide ?");
    }
    $id=uniqueid($class);
    $db->execute(lq("INSERT INTO #_TP_entities (id,idparent,idtype,identifier,rank,status,usergroup,iduser) VALUES ('$id','$idparent','$context[idtype]','$context[identifier]','$rank','$status','$usergroup','$iduser')")) or die($db->errormsg());

    require_once($home."managedb.php");
    creeparente($id,$context['idparent'],false);
    move_files($id,$files_to_move,$sets);

    $sets['identity']="'$id'";
    $db->execute(lq("INSERT INTO #_TP_$class (".join(",",array_keys($sets)).") VALUES (".join(",",$sets).")")) or die($db->errormsg());
  }  

  enregistre_personnes($context,$id,$status,false);
  enregistre_entrees($context,$id,$status,false);

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
      if (!@mkdir(SITEROOT.$dirdest,0777 & octdec($GLOBALS['filemask']))) die("ERROR: impossible to create the directory \"$dir\"");
    }
    $dest=$dirdest."/".$dest;
    $sets[$file['name']]="'".addslashes($dest)."'";
    if ($src==SITEROOT.$dest) continue;
    rename($src,SITEROOT.$dest);
    chmod (SITEROOT.$dest,0666 & octdec($GLOBALS['filemask']));
    @rmdir(dirname($src)); // do not complain, the directory may not be empty
  }
}



function enregistre_personnes (&$context,$identity,$status,$lock=TRUE)

{
  global $db;
  if ($lock) lock_write("objet","entities_persons","persons");
  // detruit les liens dans la table entites_personnes
  $db->execute(lq("DELETE FROM #_TP_entities_persons WHERE identity='$identity'")) or die($db->errormsg());

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
      $row=getrow("SELECT id,status FROM #_TP_persons WHERE nomfamille='".$bal[nomfamille]."' AND prenom='".$bal[prenom]."'");
      if ($row===false) die($db->errormsg());
      if ($row) { // ok, the person already exists
	list($id,$oldstatus)=$row; // on recupere sont id et sont status
	if (($status>0 && $oldstatus<0) || ($oldstatus<=-64 && $status>$oldstatus)) { // Faut-il publier l'personne ?
	  $db->execute(lq("UPDATE #_TP_persons SET status='$status' WHERE id='$id'")) or die($db->errormsg());
	}
      } else {
	$id=uniqueid("personnes");
	$db->execute(lq("INSERT INTO #_TP_persons (id,status,nomfamille,prenom) VALUES ('$id','$status','$bal[nomfamille]','$bal[prenom]')")) or die($db->errormsg());
      }

      $rank=$ind;

      // ajoute l'personne dans la table entites_personnes
      // ainsi que la description
      $db->execute(lq("INSERT INTO #_TP_entities_persons (idperson,identity,idtype,rank,description,prefix,affiliation,fonction,courriel) VALUES ('$id','$identity','$idtype','$rank','$bal[description]','$bal[prefix]','$bal[affiliation]','$bal[fonction]','$bal[courriel]')")) or die($db->errormsg());
    } // boucle sur les ind
  } // boucle sur les types
  if ($lock) unlock();
}



function enregistre_entrees (&$context,$identity,$status,$lock=TRUE)

{
  if ($lock) lock_write("objets","entity_entries","entries","entrytypes");
  // detruit les liens dans la table entites_indexhs
  $db->execute(lq("DELETE FROM #_TP_entities_entries WHERE identity='$identity'")) or die($db->errormsg());

 if ($status>-64 && $status<-1) $status=-1;
 if ($status>1) $status=1;

 // put the id's from entrees and autresentrees into idtypes
 $idtypes=$context[entries] ? array_keys($context[entries]) : array();
 if ($context[autresentries]) $idtypes=array_unique(array_merge($idtypes,array_keys($context[autresentries])));

  if (!$idtypes) { if ($lock) unlock(); return; }

  // boucle sur les differents entrees
  foreach ($idtypes as $idtype) {
    $entries=$context[entries][$idtype];
    if ($context[autresentries][$idtype]) {
      if ($entries) {
	$entries=array_merge($entries,preg_split("/,/",$context[autresentries][$idtype]));
      } else {
	$entries=preg_split("/,/",$context[autresentries][$idtype]);
      }
    } elseif (!$entries) continue;
    $entrytype=getrow(lq("SELECT newbyimportallowed,useabrevation FROM #_TP_entrytypes WHERE status>0 AND id='$idtype'"));
    if ($entrytype===false) die($db->errormsg());
    if (!$entrytype) die ("ERROR: internal error in enregistre_entries");


    foreach ($entries as $entry) {
      // est-ce que $entree est un tableau ou directement l'entree ?
      if (is_array($entry)) {
	$lang=$entry[lang]=="--" ? "" : $entry[lang];
	$entry=$entry['name'];
      } else {
	$lang="";
      }
      // on nettoie le name de l'entree
      $entry=trim(strip_tags($entry));
      myquote($entry); // etrange ? pourquoi ajouter ce bout de code ???
      if (!$entry) continue; // etrange elle est vide... tant pis
      // cherche l'id de l'entree si elle existe
      $langcriteria=$lang ? "AND lang='$lang'" : "";
      $row=getrow(lq("SELECT id,status FROM #_TP_entries WHERE (abrev='$entry' OR name='$entry') AND idtype='$idtype' $langcriteria"));
      if ($row===false) die($db->errormsg());

      if ($row) { // l'entree exists
	list($id,$oldstatus)=$row;
	$statusset="";
	if ($oldstatus<=-64 && $status>$oldstatus) $statusset=$status;
	if ($status>0 && $oldstatus<0) $statusset="abs(status)"; // faut-il publier ?
	if ($statusset) {
	  $db->execute(lq("UPDATE #_TP_entries SET status=$statusset WHERE id='$id'")) or die($db->errormsg());	
	}
      } elseif ($entrytype[newbyimportallowed]) { // l'entree n'existe pas. est-ce qu'on a le right de l'ajouter ?
	// oui,il faut ajouter le mot cle
	$abrev=$entrytype[useabrevation] ? strtoupper($entry) : "";
	$id=uniqueid("entries");
	$db->execute(lq("INSERT INTO #_TP_entries (id,status,name,abrev,idtype,lang) VALUES ('$id','$status','$entry','$abrev','$idtype','$lang')")) or die($db->errormsg());
      } else {
	$id=0;
	// on ne l'ajoute pas... pas le right!
      }
      // ajoute l'entree dans la table entites_entrees
      // on pourrait optimiser un peu ca... en mettant plusieurs values dans 
      // une chaine et en faisant la requette a la fin !
      if ($id) {
	$db->execute(lq("INSERT INTO #_TP_entities_entries (identry,identity) VALUES ('$id','$identity')")) or die($db->errormsg());
      }
    } // boucle sur les entrees d'un type
  } // boucle sur les type d'entree
  if ($lock) unlock();
}


function lodel_strip_tags($text,$allowedtags) 

{
  global $home;
  require_once($home."balises.php");
  static $accepted; // cache the accepted balise;
  global $multiplelevel,$xhtmlgroups;

  // simple case.
  if (!$allowedtags) return strip_tags($text);

  if (!$accepted[$allowedtags]) { // not cached ?
    $accepted[$allowedtags]=array();

    // split the groupe of balises
    $groups=preg_split("/\s*;\s*/",$allowedtags);
    array_push($groups,""); // balises speciales
    // feed the accepted string with accepted tags.
    foreach ($groups as $group) {
      // lodel groups
      if ($multiplelevel[$group]) {
	foreach($multiplelevel[$group] as $k=>$v) { $accepted[$allowedtags]["r2r:$k"]=true; }
      }
	// xhtml groups
      if ($xhtmlgroups[$group]) {
	foreach($xhtmlgroups[$group] as $k=>$v) {
	  if (is_numeric($k)) { 
	    $accepted[$allowedtags][$v]=true; // accept the tag with any attributs
	  } else {
	    // accept the tag with attributs matching unless it is already fully accepted
	    if (!$accepted[$allowedtags][$k]) $accepted[$allowedtags][$k][]=$v; // add a regexp
	  }
	}
      } // that was a xhtml group
    } // foreach group
  } // not cached.

#  print_r($accepted);

  $acceptedtags=$accepted[$allowedtags];

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


function change_usergroup_rec($id,$usergroup)

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
    $result=$db->execute(lq("SELECT id FROM #_TP_entities WHERE idparent IN ($idlist)")) or die($db->errormsg());

    $idparents=array();
    while (!$result->EOF) {
      array_push ($ids,$result->fields['id']);
      array_push ($idparents,$result->fields['id']);
      $result->MoveNext();
    }
  } while ($idparents);

  // update toutes les publications
  $idlist=join(",",$ids);

  $db->execute(lq("UPDATE #_TP_entities SET usergroup='$usergroup' WHERE id IN ($idlist)")) or die($db->errormsg());
  # cherche les ids
}


function loop_fields($context,$funcname)

{
  global $db,$error;

  $result=$db->execute(lq("SELECT * FROM #_TP_fields WHERE idgroup='$context[id]' AND status>0 AND edition!='' ORDER BY rank")) or die($db->errormsg());

  $haveresult=$result->recordnumber()>0;
  if ($haveresult) call_user_func("code_before_$funcname",$context);

  while (!$result->EOF) {
    $localcontext=array_merge($context,$result->fields);
    $name=$result->fields['name'];
    $localcontext['value']=$context['entity'][$name];
    $localcontext['error']=$context['error'][$name];

    call_user_func("code_do_$funcname",$localcontext);
    $result->MoveNext();
  }

  if ($haveresult) call_user_func("code_after_$funcname",$context);
}





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
  $result=$db->execute(lq("SELECT * FROM #_TP_persons,#_TP_entities_persons WHERE idperson=id  AND identity='$identity'")) or die($db->errormsg());

  $vars=array("prefix","nomfamille","prenom","description","fonction","affiliation","courriel");
  while (!$result->EOF) {
    $row=$result->fields;
    foreach($vars as $var) {
      $context[$var][$row['idtype']][$row['rank']]=$row[$var];
    }
    $result->MoveNext();
  }
}

function extrait_entrees($identity,&$context)

{
  global $db;

  $result=$db->execute(lq("SELECT * FROM #_TP_entries,#_TP_entities_entries WHERE identry=id  AND identity='$identity'")) or die($db->errormsg());

  foreach($result->field as $row) {
  while (!$result->EOF) {
    $row=$result->fields;
    if ($context['entries'][$row['idtype']]) {
      array_push($context['entries'][$row['idtype']],$row['name']);
    } else {
      $context['entries'][$row['idtype']]=array($row['name']);
    }
    $result->MoveNext();
  }
}


// makeselect


function makeselectentries (&$context)
     // le context doit contenir les informations sur le type a traiter
{
  $entriestrouvees=array();
  $entries=$context[entries][$context[id]];
#  echo "type:",$context[id];print_r($context[entries]);
  makeselectentries_rec(0,"",$entries,$context,$entriestrouvees);
  $context[autresentries]=$entries ? join(", ",array_diff($entries,$entriestrouvees)) : "";
}

function makeselectentries_rec($idparent,$rep,$entries,&$context,&$entriestrouvees)

{
  if (!$context[tri]) die ("ERROR: internal error in makeselectentries_rec");
  $result=$db->execute(lq("SELECT id, abrev, name FROM #_TP_entries WHERE idparent='$idparent' AND idtype='$context[id]' ORDER BY $context[sort]")) or die($db->errormsg());

  while (!$result->EOF) {
    $row=$result->fields;
    $selected=$entries && (in_array($row['abrev'],$entries) || in_array($row['name'],$entries)) ? " selected" : "";
   if ($selected) array_push($entriestrouvees,$row['name'],$row['abrev']);
   $value=$context['useabrevation'] ? $row['abrev'] : $row['name'];
    echo "<option value=\"$value\"$selected>$rep$row[name]</option>\n";
    makeselectentries_rec($row[id],$rep.$row['name']."/",$entries,$context,$entriestrouvees);
    $result->MoveNext();
  }
}


function makeselectusergroups() 

{
  global $context,$db;
      
  $result=$db->execute(lq("SELECT id,name FROM #_TP_usergroups")) or die($db->errormsg());

  while (!$result->EOF) {
    list($id,$name)=$result->fields;
    $selected=$context['usergroup']==$id ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$name</OPTION>\n";
    $result->MoveNext();
  }
}

function makeselecttype($class)

{
  global $db,$context;

  if ($context['typedocfixe']) $critere="AND type='$context[typedoc]'";

  $result=$db->execute(lq("SELECT id,type,title FROM #_TP_types WHERE status>0 AND class='$class' $critere AND type NOT LIKE 'documentannexe-%'")) or die($db->errormsg());
  while (!$result->EOF) {
    list($id,$type,$title)=$result->fields;
    $selected=$context['idtype']==$id ? " selected" : "";
    $title=$title ? $title : $type;
    echo "<option value=\"$id\"$selected>$title</option>\n";
    $result->MoveNext();
  }
}


function makeselectdate() {
  global $context;

  foreach (array("maintenant",
		 "jours",
		 "mois",
		 "années") as $date) {
    $selected=$context['dateselect']==$date ? "selected" : "";
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

  $lang=$context[addlanginmltext][$context['name']];
  if ($lang) {
    $localcontext=$context;
    $localcontext[lang]=$lang;
    $localcontext[value]="";
    call_user_func("code_do_$funcname",$localcontext);
  }
}


?>
