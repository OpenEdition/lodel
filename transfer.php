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


require("siteconfig.php");
require ($home."auth.php");
#authenticate(LEVEL_ADMINLODEL,NORECORDURL);
authenticate();
require_once($home."func.php");
require_once($home."champfunc.php");
require_once($home."connect.php");

$context['confirm']=intval($confirm);

if ($confirm) {
  $tables=gettables();
  do { // block de control

    //
    // add field in text class
    //
    if ($tables["$GLOBALS[tp]textes"]) {
      $textfields=array("lang"=>"CHAR(5) NOT NULL",
			"textgroup"=>"VARCHAR(10) NOT NULL");

      $fields=getfields("textes");
      foreach ($textfields as $f=>$t) {
	if ($fields[$f]) continue;
	$err=mysql_query_cmds('
 ALTER TABLE _PREFIXTABLE_textes ADD '.$f.' '.$t.';
 ALTER TABLE _PREFIXTABLE_textes ADD INDEX index_'.$f.' ('.$f.');
 ');
	if ($err) break 2;
      }

      if (!$fields['textgroup']) {
	$err=mysql_query_cmds('
 UPDATE _PREFIXTABLE_textes SET textgroup=\'site\'
 ');
	if ($err) break;
      }

      $report.="ajouter les champs a la table textes<br>";
    }
    //----------------------------------------------------
    //
    // Ajout le champ lang a users (au deux niveaux)
    $fields=getfields("users");
    if (!$fields['lang']) {
      $err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_users ADD lang CHAR(5) NOT NULL;
UPDATE _PREFIXTABLE_users SET lang=\'fr\'
');
      if ($err) break;
      $report.="Ajout des lang dans users (local)<br>";
    }

    mysql_select_db($GLOBALS['database']);
    $fields=getfields("users",$GLOBALS['database']);
    if (!$fields['lang']) {
      $err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_users ADD lang CHAR(5) NOT NULL;
UPDATE _PREFIXTABLE_users SET lang=\'fr\'
');
      if ($err) break;
      $report.="Ajout des lang dans users (global)<br>";
    }
    mysql_select_db($GLOBALS['currentdb']);


    if (!$tables["$GLOBALS[tp]translations"]) {
      if ($err=create("translations")) break; // create the translation table
    }

    if (1 || $tables["$GLOBALS[tp]objets"] || !$tables["$GLOBALS[tp]objects"]) {
      // traductions de la base

      $translationconv=array(
			     "objects (id,class)"=>"objets (id,classe)",
			     "entities (id,idparent,idtype,identifier,usergroup,iduser,rank,status,upd)"=>"entites (id,idparent,idtype,identifiant,groupe,iduser,ordre,statut,maj)",
			     "relations (id1,id2,nature,degree)"=>"relations (id1,id2,nature,degres)",
			     "fields (id,name,idgroup,title,style,type,condition,defaultvalue,processing,allowedtags,filtering,edition,comment,status,rank,upd)"=>"champs (id,nom,idgroupe,titre,style,type,condition,defaut,traitement,balises,filtrage,edition,commentaire,statut,ordre,maj)",
			     "fieldgroups (id,name,class,title,commentaire,status,rank,upd)"=>"groupesdechamps (id,nom,classe,titre,commentaire,statut,ordre,maj)",
			     "persons (id,lastname,firstname,status,upd)"=>"personnes (id,nomfamille,prenom,statut,maj)",
			     "users (id,username,passwd,name,email,userrights,lang,status,upd)"=>"users (id,username,passwd,nom,courriel,privilege,lang,statut,maj)",
			     "usergroups (id,name,status,upd)"=>"groupes (id,nom,statut,maj)",
			     "users_usergroups (idgroup,iduser)"=>"users_groupes (idgroupe,iduser)",
			     "types (id,type,title,class,tpl,tplcreation,tpledition,import,rank,status,upd)"=>"types (id,type,titre,classe,tpl,tplcreation,tpledition,import,ordre,statut,maj)",
			     "persontypes (id,type,title,style,titledescription,styledescription,tpl,tplindex,rank,status,upd)"=>"typepersonnes (id,type,titre,style,titredescription,styledescription,tpl,tplindex,ordre,statut,maj)",
			     "entrytypes (id,type,title,style,tpl,tplindex,rank,status,flat,newbyimportallowed,useabrevation,sort,upd)"=>"typeentrees (id,type,titre,style,tpl,tplindex,ordre,statut,lineaire,nvimportable,utiliseabrev,tri,maj)",
			     "entries (id,idparent,name,abrev,lang,idtype,rank,status,upd)"=>"entrees (id,idparent,nom,abrev,langue,idtype,ordre,statut,maj)",
			     "tasks (id,name,step,user,context,status,upd)"=>"taches (id,nom,etape,user,context,statut,maj)",
			     "texts (id,name,contents,lang,textgroup,status,upd)"=>"textes (id,nom,texte,lang,textgroup,statut,maj)",
			     "entities_persons (idperson,identity,idtype,rank,prefix,description,function,affiliation,email)"=>"entites_personnes (idpersonne,identite,idtype,ordre,prefix,description,fonction,affiliation,courriel)",
			     "entities_entries (identry,identity)"=>"entites_entrees (identree,identite)",
			     "entitytypes_entitytypes (identitytype,identitytype2,condition)"=>"typeentites_typeentites (idtypeentite,idtypeentite2,condition)",
			     "entitytypes_entrytypes (identitytype,identrytype,condition)"=>"typeentites_typeentrees (idtypeentite,idtypeentree,condition)",
			     "entitytypes_persontypes (identitytype,idpersontype,condition)"=>"typeentites_typepersonnes (idtypeentite,idtypepersonne,condition)",
			     "options (id,name,type,value,rank,status,upd)"=>"options (id,nom,type,valeur,ordre,statut,maj)",
#			     "translations (id,lang,title,textgroups,translators,modificationdate,creationdate,rank,status,upd)"=>"translations (id,lang,titre,textgroups,translators,modificationdate,creationdate,ordre,statut,maj)"
			     );

      $todelete=array();
      foreach($translationconv as $new=>$old) {
	list($newtable,$values)=explode(" ",$new);
	list($oldtable,$select)=explode(" ",$old);
	$select=preg_replace("/[()]/","",$select);
	if ($newtable==$oldtable) {
	  if ($tables["$GLOBALS[tp]$oldtable"."_old"]) continue; // deja fait.
	  $err=mysql_query_cmds("RENAME TABLE _PREFIXTABLE_$oldtable TO _PREFIXTABLE_$oldtable"."_old;");
	  $oldtable.="_old";

	  if ($err) break;
	} else {
	  if ($tables["$GLOBALS[tp]$newtable"]) continue; // deja fait
	}
	$err=create($newtable);
	if ($err) break;
	$err=mysql_query_cmds("INSERT INTO _PREFIXTABLE_$newtable $values SELECT $select FROM _PREFIXTABLE_$oldtable");
	if ($err) break;
	array_push($todelete,$oldtable);
	$report.="traduction de la table $oldtable<br>\n";
      }
      if ($err) break;
      foreach ($todelete as $table) {
	$err=mysql_query_cmds("DROP TABLE _PREFIXTABLE_$table;");
	if ($err) break;
      }
      if ($err) break;
      $report.="efface les anciennes tables<br>";
    } // fini la translation

    /////////////////////
    /// publications et documents

     foreach (array("publications","documents") as $classe) {
      $fields=getfields($classe);
      if ($fields['identite']) {
	$err=mysql_query_cmds("ALTER TABLE _PREFIXTABLE_$classe CHANGE identite identity	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE");
	if ($err) break 2;
	$report.="changement de identite en identity pour la table $classe<br>";
      }
     }


    /////////////////////
    // ENTITYTITLE

    if ($tables["$GLOBALS[tp]entities"]) {
      $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]entities WHERE entitytitle!='' LIMIT 1") or die(mysql_error());
      if (!mysql_num_rows($result)) {
	foreach (array("publications","documents") as $classe) {
	  $result=mysql_query("SELECT identity,titre FROM $GLOBALS[tp]$classe") or die (mysql_error());
	  while (list($id,$titre)=mysql_fetch_row($result)) {
	    $titre=strip_tags($titre);
	    if (strlen($titre)>255) {
	      $titre=substr($titre,0,256);
	      $titre=preg_replace("/\S+$/","",$titre);
	    }
	    $titre=addslashes($titre);
	    mysql_query("UPDATE $GLOBALS[tp]entities set entitytitle='$titre' WHERE id='$id'") or die(mysql_error());
	  }
	} // classes
	$report.="Remplissage de entitytitle a partir de titre<br />\n";
      }
    } // table entite


    /////////////////////
    // USERGROUPS
    if ($tables["$GLOBALS[tp]usergroups"]) {
      $fields=getfields("usergroups");
      if (!$fields['rank']) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_usergroups ADD rank INT UNSIGNED DEFAULT \'0\' NOT NULL;
');
	if ($err) break;
	$report.="Ajout de rank a usergroups<br/>";
      }
    }      

    // fini, faire quelque chose
  } while(0);
}


$context[error]=$err;
$context[report]=$report;

require($home."calcul-page.php");
calcul_page($context,"transfer");



function mysql_query_cmd($cmd) 

{
  $cmd=str_replace("_PREFIXTABLE_","$GLOBALS[tp]",$cmd);
  if (!mysql_query($cmd)) { 
    $err="$cmd <font COLOR=red>".mysql_error()."</font><br>";
    return $err;
  }
  return FALSE;
}


// faire attention avec cette fontion... elle supporte pas les ; dans les chaines de caractere...
function mysql_query_cmds($cmds,$table="") 

{
  $sqlfile=str_replace("_PREFIXTABLE_",$GLOBALS[tp],$cmds);
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if ($table) { // select the commands operating on the table  $table
    $sql=preg_grep("/(REPLACE|INSERT)\s+INTO\s+$GLOBALS[tp]$table\s/i",$sql);
  }
  if (!$sql) return;

  foreach ($sql as $cmd) {
    $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
    if ($cmd) {
      if (!mysql_query($cmd)) { 
	$err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
	break; // sort, ca sert a rien de continuer
      }
    }
  }
  return $err;
}

function gettables()

{
  // recupere la liste des tables
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $tables=array();
  while (list($table) = mysql_fetch_row($result)) {
    $tables[$table]=TRUE;
  }
  return $tables;
}


function getfields($table,$database="")

{
  if (!$database) $database=$GLOBALS['currentdb'];
  $fields = mysql_list_fields($database,$GLOBALS[tp].$table) or die (mysql_error());
  $columns = mysql_num_fields($fields);
  $arr=array();
  for ($i = 0; $i < $columns; $i++) {
    $fieldname=mysql_field_name($fields, $i);
    $arr[$fieldname]=1;
  }
  return $arr;
}


# pose des problemes... ca ecrase les anciens types
#function chargeinserts($table)
#
#{
#  global $home,$report;
#      // charge l'install
#  $file=$home."../install/inserts-site.sql";
#  if (!file_exists($file)) {
#    $err="Le fichier $file n'existe pas !";
#    break;
#  }
#  $err=mysql_query_cmds(utf8_encode(join("",file($file))),$table);
#  if ($err) return $err;
#  $report.="Import des insert dans la table $table<br>";
#  return FALSE;
#}

function create($table) 

{
  global $home,$report;
      // charge l'install
  $file=$home."../install/init-site.sql";
  if (!file_exists($file)) {
    return "Le fichier $file n'existe pas !";
  }
  
  if (!preg_match ("/CREATE TABLE[\s\w]+_PREFIXTABLE_$table\s*\(.*?;/s",join('',file($file)),$result)) return "impossible de creer la table $table car elle n'existe pas dans le fichier init-site.sql<br>";
  
  $err=mysql_query_cmds($result[0]);
  if ($err) return $err;
  $report.="Cr&eacute;ation de la table $table<br>";
  return FALSE;
}



// fonction de conversion de isolatin en utf8
function isotoutf8 ($tables)

{
  // Tableau contenant la liste des tables à ne pas parcourrir pour gagner du temps.
  #$blacklist=array("relations","entites_entrees","users_groupes");
  // On parcours toutes les tables


  foreach($tables as $table) {
    #if(in_array ($table, $blacklist)) continue;

     $report.="conversion en utf8 de  la table $table<br />\n";
    // On parcours toutes les enregistrements de chaque table
    $resultselect = mysql_query("SELECT * FROM $table") or die(mysql_error());
    while($valeurs = mysql_fetch_row($resultselect)) {
      $nbchamps = mysql_num_fields($resultselect);

      // Construction de la clause SET et WHERE de l'update
      // en parcourant toutes les valeurs de chaque enregistrement.
      $set=array();
      $where=array();
      for ($i=0; $i < $nbchamps; $i++) {
	$type  = mysql_field_type($resultselect, $i);
	$name  = mysql_field_name($resultselect, $i);
				
	// Construction de la clause SET
        $newvaleurs = str_replace (chr(146),"'", $valeurs[$i]);
	if(($type=="string"||$type=="blob") && $valeurs[$i]!="") array_push($set,$name."='".addslashes(utf8_encode($newvaleurs))."'");
	  
	// Construction de la clause WHERE
        if (is_null($valeurs[$i])) {
           array_push($where,$name." IS NULL");
	} else if($type=="string"||$type=="blob") {
	  array_push($where,$name."='".addslashes($valeurs[$i])."'");
	} else {
	  array_push($where,$name."='$valeurs[$i]'");
	}
      } // parcourt les champs
      // S'il y a une modification à faire on lance la requete
      if($set) {
	$requete="UPDATE $table SET ".join(", ",$set)." WHERE ".join(" AND ",$where);
	if (!mysql_query($requete)) { echo htmlentities($requete),"<br>"; die(mysql_error()); }
      }
    } // parcourt les lignes
  } // parcourt les tables
  return $report;
}

function extractnom($personne) {
  // ok, on cherche maintenant a separer le nom et le prenom

  if (preg_match("/^\s*(Pr\.|Dr\.)/",$personne,$result)) {
    $prefix=$result[1];
    $personne=str_replace($result[0],"",$personne);
  }

  $nom=$personne;

  while ($nom && strtoupper($nom)!=$nom) { $nom=substr(strstr($nom," "),1);}
  if ($nom) {
    $prenom=str_replace($nom,"",$personne);
  } else { // sinon coupe apres le premiere espace
    if (preg_match("/^\s*(.*?)\s+([^\s]+)\s*$/i",$personne,$result)) {
      $prenom=$result[1]; $nom=$result[2];
    } else $nom=$personne;
  }
  return array($prefix,$prenom,$nom);
}

function extract_meta($classe)

{
  $result=mysql_query("SELECT id,meta FROM $GLOBALS[tp]$classe WHERE meta LIKE '%meta_image%'") or die(mysql_error());

  while (list($id,$meta)=mysql_fetch_row($result)) {
    $meta=unserialize($meta);
    if (!$meta[meta_image]) continue;
    $file=SITEROOT.$meta[meta_image];
    $info=getimagesize($file);
    if (!is_array($info)) die("ERROR: the image format has not been recognized");
    $exts=array("gif", "jpg", "png", "swf", "psd", "bmp", "tiff", "tiff", "jpc", "jp2", "jpx", "jb2", "swc", "iff");
    $ext=$exts[$info[2]-1];
    
    $dirdest="docannexe/image/$id";
    $dest=$dirdest."/image.".$ext;

    // copy the file
    if(!file_exists(SITEROOT.$dirdest) && !mkdir(SITEROOT.$dirdest,0777  & octdec($GLOBALS[filemask]))) return FALSE;
    if (!copy($file,SITEROOT.$dest)) return FALSE;
    chmod(SITEROOT.$dest, 0666  & octdec($GLOBALS[filemask]));
    unlink($file);

    mysql_query("UPDATE $GLOBALS[tp]$classe SET icone='$dest' WHERE id='$id'") or die(mysql_error());
  }

  return TRUE;
}


function convertHTMLtoXHTML ($field,$contents)

{
  $contents=str_replace(array("\n","\t","\r")," ",$contents);
  // footnote

  if ($field=="notebaspage") {
    // note de R2R
#echo htmlentities($contents),"<br>";
    if (preg_match('/<a\s+name="(FN\d+)"\s*>/',$contents)) { // ok, il y a des definitions de note R2R ici
#echo "r2r document: $row[identite]</br>";
      // petit nettoyage
      $contents=preg_replace('/((?:<br><\/br>)?<a\s+name="FN\d+"><\/a>)((?:<\/\w+>)+)(<a\s+href="#FM\d+">)/','\\2\\1\\3',$contents);
      $arr=preg_split("/<br><\/br>(?=<a\s+name=\"FN\d+\">)/",trim($contents));
      for($i=0; $i<count($arr); $i++) {
	if ($i==0 && trim(strip_tags($arr[$i]))=="NOTES") { $arr[0]=""; continue;}
	if (preg_match('/^<a\s+name="FN(\d+)"><a\s+href="#FM(\d+)">(.*?)<\/a><\/a>/s',$arr[$i],$result2) ||
	    preg_match('/^<a\s+name="FN(\d+)"><\/a><a\s+href="#FM(\d+)">(.*?)<\/a>/s',$arr[$i],$result2)) { // c'est bien le debut d'un note
	  $arr[$i]='<div class="footnotebody"><a class="footnotedefinition" id="ftn'.$result2[1].'" href="#bodyftn'.$result2[2].'">'.$result2[3].'</a>'.substr($arr[$i],strlen($result2[0])).'</div>';
	} else {
	  die("La ".($i+1)."eme note mal forme dans le document $row[identite]:<br>".htmlentities($arr[$i]));
	}
      } // toutes les notes
      $contents=join("",$arr);
    } elseif (preg_match('/<p>\s*<a\s+href="#_nref_\d+"/',$contents)) { // Ted style ?
#echo "Ted document: $row[identite]<br>";
      $contents=preg_replace('/<p>\s*<a\s+href="#_nref_(\d+)"\s+name="_ndef_(\d+)"><sup><small>(.*?)<\/small><\/sup><\/a>(.*?)<\/p>/s',
			     '<div class="footnotebody"><a class="footnotedefinition" href="#bodyftn\\1" id="ftn\\2">\\3</a>\\4</div>',$contents);
	
    }
  } // fin note R2R
    
    
    // converti les appels de notes
  $srch=array('/<a\s+name="FM(\d+)">\s*<a\s+href="#FN(\d+)">(.*?)<\/a>\s*<\/a>/s', # R2R footnote call
	      '/<a\s+name="FM(\d+)">\s*<\/a>\s*<a\s+href="#FN(\d+)">(.*?)<\/a>/s', # R2R footnote call
	      '/<sup>\s*<small>\s*<\/small>\s*<\/sup>/',
	      '/<a\s+href="#_ndef_(\d+)" name="_nref_(\d+)"><sup><small>(.*?)<\/small><\/sup><\/a>/'); # Ted footnote call
  $rpl=array('<a class="footnotecall" href="#ftn\\2" id="bodyftn\\1">\\3</a>',
	     '<a class="footnotecall" href="#ftn\\2" id="bodyftn\\1">\\3</a>',
	     '',
	     '<a class="footnotecall" href="#ftn\\1" id="bodyftn\\2">\\3</a>');

  // convert u in span/style
  array_push($srch,"/<u>/","/<\/u>/");
  array_push($rpl,"<span style=\"text-decoration: underline\">","</span>");


  return preg_replace($srch,$rpl,$contents);
}


function addfield($classe)

{
  $fields=getfields($classe);

  $result=mysql_query("SELECT $GLOBALS[tp]champs.nom,type FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND classe='$classe'") or die(mysql_error());

  #echo "classe:$classe<br/>";
  while (list($champ,$type)=mysql_fetch_row($result)) {
    #echo "ici:$champ $type<br/>";
    if ($fields[$champ]) continue;
    #echo "ici:$champ - create<br/>";
    #echo 'ALTER TABLE _PREFIXTABLE_'.$classe.' ADD     '.$champ.' '.$GLOBALS[sqltype][$type].'<br>';
    $err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_'.$classe.' ADD     '.$champ.' '.$GLOBALS[sqltype][$type].';
 ');
    #echo "error:$err";
    if ($err) return $err;
  }
  return false;
}


function loop_fichiers(&$context,$funcname)
{
  global $importdirs,$fileregexp;

  foreach ($importdirs as $dir) {
    if ( $dh= @opendir($dir)) {
      while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$context[nom]="$dir/$file";
	call_user_func("code_do_$funcname",$context);
      }
      closedir ($dh);
    }
  }
}

?>



