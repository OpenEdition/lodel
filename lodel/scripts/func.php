<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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


function writefile ($filename,&$text)
{
 //echo "nom de fichier : $filename";
   if (file_exists($filename)) 
   { 
     if (! (unlink($filename)) ) die ("Ne peut pas supprimer $filename. probleme de droit contacter Luc ou Ghislain");
   }
   return ($f=fopen($filename,"w")) && fputs($f,$text) && fclose($f) && chmod ($filename,0666 & octdec($GLOBALS[filemask]));
}


function get_tache (&$id)

{
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]taches WHERE id='$id' AND statut>0") or die (mysql_error());
  if (!($row=mysql_fetch_assoc($result))) { back(); return; }
  $row=array_merge($row,unserialize($row[context]));
  return $row;
}

function posttraitement(&$context)

{
  if ($context) {
    foreach($context as $key=>$val) {
      if (is_array($val)) {
	posttraitement($context[$key]);
      } else {
	if ($key!="meta") $context[$key]=str_replace("\n"," ",htmlspecialchars(stripslashes($val)));
      }
    }
  }
}

//
// $context est soit un tableau qui sera serialise soit une chaine deja serialise
//

function make_tache($nom,$etape,$context,$id=0)

{
  global $iduser;
  if (is_array($context)) $context=serialize($context);
  mysql_query("REPLACE INTO $GLOBALS[tp]taches (id,nom,etape,user,context) VALUES ('$id','$nom','$etape','$iduser','$context')") or die (mysql_error());
  return mysql_insert_id();
}

function update_tache_etape($id,$etape)

{
  mysql_query("UPDATE $GLOBALS[tp]taches SET etape='$etape' WHERE id='$id'") or die (mysql_error());
 # ne pas faire ca, car si la tache n'est pas modifiee, il renvoie 0
# if (mysql_affected_rows()!=1) die ("Erreur d'update de id=$id");
}

//
// previouscontext est la chaine serialisee
// newcontext est un array

function update_tache_context($id,$newcontext,$previouscontext="")

{
  if ($previouscontext) { // on merge les deux contextes
    $contextstr=serialize(array_merge(unserialize($previouscontext),$newcontext));
  } else {
    $contextstr=serialize($newcontext);
  }

  mysql_query("UPDATE $GLOBALS[tp]taches SET context='$contextstr' WHERE id='$id'") or die (mysql_error());

}

function rmscript($source) {
	// Remplace toutes les balises ouvrantes susceptibles de lancer un script
	return eregi_replace("<(\%|\?|( *)script)", "&lt;\\1", $source);
}


function extract_post() {
	// Extrait toutes les variables passées par la méthode post puis les stocke dans 
	// le tableau $context
	global $home;
	
	foreach ($GLOBALS[HTTP_POST_VARS] as $key=>$val) {
	  if (!$GLOBALS[context][$key]) // protege
	    $GLOBALS[context][$key]=$val;
	}
	function clean_for_extract_post(&$var) {
	  if (is_array($var)) {
	    array_walk($var,"clean_for_extract_post");
	  } else return rmscript(trim($val));
	}
#	print_r($GLOBALS[context]);
	array_walk($GLOBALS[context],"clean_for_extract_post");
#	echo "--------------------------------";
#	print_r($GLOBALS[context]);flush();
#	die("fini $context");
}

function get_ordre_max ($table,$where="") 

{
  if ($where) $where="WHERE ".$where;

  require_once ($GLOBALS[home]."connect.php");
  $result=mysql_query ("SELECT MAX(ordre) FROM $GLOBALS[tp]$table $where") or die (mysql_error());
  if (mysql_num_rows($result)) list($ordre)=mysql_fetch_row($result);
  if (!$ordre) $ordre=0;

  return $ordre+1;
}

function chordre($table,$id,$critere,$dir,$inverse="")

{
  $table=$GLOBALS[tp].$table;
  $dir=$dir=="up" ? -1 : 1;  if ($inverse) $dir=-$dir;
  $desc=$dir>0 ? "" : "DESC";
  $result=mysql_query("SELECT id,ordre FROM $table WHERE $critere ORDER BY ordre $desc") or die (mysql_error());

  $ordre=$dir>0 ? 1 : mysql_num_rows($result);
  while ($row=mysql_fetch_assoc($result)) {
    if ($row[id]==$id) {
      # intervertit avec le suivant s'il existe
      if (!($row2=mysql_fetch_assoc($result))) break;
      mysql_query("UPDATE $table SET ordre='$ordre' WHERE id='$row2[id]'") or die (mysql_error());
      $ordre+=$dir;
    }
    if ($row[ordre]!=$ordre) {
      mysql_query("UPDATE $table SET ordre='$ordre' WHERE id='$row[id]'") or die (mysql_error());
    }
    $ordre+=$dir;
  }
} 

function myquote (&$var)
{
  if (is_array($var)) {
    array_walk($var,"myquote");
    return $var;
  } else {
    return $var=addslashes(stripslashes($var));
  }
}

function myaddslashes (&$var)
{
  if (is_array($var)) {
    array_walk($var,"myaddslashes");
    return $var;
  } else {
    return $var=addslashes($var);
  }
}


function mystripslashes (&$var)

{
  if (is_array($var)) {
    array_walk($var,"mystripslashes");
    return $var;
  } else {
    return $var=stripslashes($var);
  }
}

function myfilemtime($filename)

{
  return file_exists($filename) ? filemtime($filename) : 0;
}


function copy_images (&$text,$callback,$argument="",$count=1)

{
    // copy les images en lieu sur et change l'acces
    $imglist=array();

    if (is_array($text)) {
      foreach ($text as $k=>$t) {
	copy_images_private($t,$callback,$argument,$count,$imglist);
	$text[$k]=$t;
      }
    } else {
      copy_images_private($text,$callback,$argument,$count,$imglist);
    }
}

function copy_images_private (&$text,$callback,$argument,&$count,&$imglist)

{
    preg_match_all("/<img\b[^>]+src=\"([^\"]+\.([^\"\.]+))\"/i",$text,$results,PREG_SET_ORDER);
    foreach ($results as $result) {
      $imgfile=$result[1];
      if ($imglist[$imgfile]) {
	$text=str_replace($result[0],"<img src=\"$imglist[$imgfile]\"",$text);
      } else {
	$ext=$result[2];
	$imglist[$imgfile]=$newimgfile=$callback($imgfile,$ext,$count,$argument);
#	echo "images: $imgfile $newimgfile <br>";
      	$text=str_replace($result[0],"<img src=\"$newimgfile\"",$text);
      	$count++;
	}
    }
}


//function addmeta(&$meta) # liste variable de valeurs
//
//{
//  $arg=array_shift(func_get_args());
//  $meta=serialize(array_merge($arg,unserialize($meta)));
//}
//

function addmeta(&$arr,$meta="")

{
  foreach ($arr as $k=>$v) {
    if (strpos($k,"meta_")===0) {
      if (!isset($metaarr)) { // cree le hash des meta
	  $metaarr=$meta ? unserialize($meta) : array();
      }
      if ($v) {
	$metaarr[$k]=$v;
      } else {
	unset($metaarr[$k]);
      }
    }
  }
  return $metaarr ? serialize($metaarr) : $meta;
}


function back()

{
  global $database,$idsession;
  //echo "idsession = $idsession<br>\n";
  $result=mysql_db_query($database,"SELECT id,currenturl FROM $GLOBALS[tp]session WHERE id='$idsession'") or die (mysql_error());
  list ($id,$currenturl)=mysql_fetch_row($result);

  mysql_db_query($database,"UPDATE $GLOBALS[tp]session SET currenturl='' WHERE id='$idsession'") or die (mysql_error());

  //echo "retourne: id=$id url=$currenturl";
  header("Location: http://$GLOBALS[SERVER_NAME]$currenturl");exit;
}




function export_prevnextpublication (&$context)

{
//
// cherche le numero precedent et le suivant
//

// suivant

  $querybase="SELECT id FROM $GLOBALS[tp]entites WHERE idparent='$context[idparent]' AND";
  $result=mysql_query ("$querybase ordre>'$context[ordre]' ORDER BY ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    $context[nextpublication]=makeurlwithid("sommaire",$nextid);
  }
  // precedent:
  $result=mysql_query ("$querybase ordre<'$context[ordre]' ORDER BY ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($previd)=mysql_fetch_row($result);
    $context[prevpublication]=makeurlwithid("sommaire",$previd);
  }
}

function translate_xmldata($data) 

{
	return strtr($data,array("&"=>"&amp;","<" => "&lt;", ">" => "&gt;"));
}


function unlock()
{
  // Dévérouille toutes les tables vérouillées précédemment par la 
  // fonction lock_write()
  if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) 
    mysql_query("UNLOCK TABLES") or die (mysql_error());
}


function lock_write()
{
  // Vérouille toutes les tables en écriture
  $list=func_get_args();
  if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) 
     mysql_query("LOCK TABLES $GLOBALS[tp]".join (" WRITE ,".$GLOBALS[tp],$list)." WRITE") or die (mysql_error());
}

#function prefix_keys($prefix,$arr)
#
#{
#  if (!$arr) return $arr;
#  foreach ($arr as $k=>$v) $outarr[$prefix.$k]=$v;
#  return $outarr;
#}

function array_merge_withprefix($arr1,$prefix,$arr2)

{
  if (!$arr2) return $arr1;
  foreach ($arr2 as $k=>$v) $arr1[$prefix.$k]=$v;
  return $arr1;
}

#function extract_options($context,$listoptions)
#
#{
#  $newoptions=array();
#  foreach ($listoptions as $opt) { if ($context["option_$opt"]) $newoptions["option_$opt"]=1; }
#  return serialize($newoptions);
#}



function makeurlwithid ($base,$id)

{
  if ($GLOBALS[idagauche]) {
    return $base.$id.".".$GLOBALS[extensionscripts];
  } else {
    return $base.".".$GLOBALS[extensionscripts]."?id=".$id;
  }
}

if (!function_exists("file_get_contents")) {
  function file_get_contents($file) 
  {
    $fp=fopen($file,"r") or die("Impossible to read the file $file");
    while(!feof($fp)) $res.=fread($fp,2048);
    fclose($fp);
    return $res;
  }
}

/**
 * sent the header and the file for downloading
 * 
 * @param     string   name of the real file.
 * @param     string   name to send to the browser.
 * 
 */

function download($filename,$originalname="",$contents="")

{
  // taken from phpMyAdmin
  // Download
  if (!$originalname) $originalname=$filename;
  if ($filename && !is_readable($filename)) die ("ERROR: The file \"$filename\" is not readable");
  $originalname=preg_replace("/.*\//","",$originalname);

  get_PMA_define();
  header("Content-type: application/force-download");
  header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  // lem9 & loic1: IE need specific headers
  if (PMA_USR_BROWSER_AGENT == 'IE') {
    header('Content-Disposition: inline; filename="' . $originalname . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
  } else {
    $size=$filename ? filesize($filename) : strlen($contents);
    header('Content-Disposition: attachment; filename="' . $originalname . '"');
    header('Content-Length: '.$size.'"');
    header('Pragma: no-cache');
  }
  if ($filename) { readfile($filename); } else { echo $contents; }
}


// taken from phpMyAdmin 2.5.4

function get_PMA_define()

{

// Determines platform (OS), browser and version of the user
// Based on a phpBuilder article:
//   see http://www.phpbuilder.net/columns/tim20000821.php

    // loic1 - 2001/25/11: use the new globals arrays defined with
    // php 4.1+
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
    } else if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
        $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
    } else if (!isset($HTTP_USER_AGENT)) {
        $HTTP_USER_AGENT = '';
    }

    // 1. Platform
    if (strstr($HTTP_USER_AGENT, 'Win')) {
        define('PMA_USR_OS', 'Win');
    } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
        define('PMA_USR_OS', 'Mac');
    } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
        define('PMA_USR_OS', 'Linux');
    } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
        define('PMA_USR_OS', 'Unix');
    } else if (strstr($HTTP_USER_AGENT, 'OS/2')) {
        define('PMA_USR_OS', 'OS/2');
    } else {
        define('PMA_USR_OS', 'Other');
    }

    // 2. browser and version
    // (must check everything else before Mozilla)

    if (ereg('Opera(/| )([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[2]);
        define('PMA_USR_BROWSER_AGENT', 'OPERA');
    } else if (ereg('MSIE ([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'IE');
    } else if (ereg('OmniWeb/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
    //} else if (ereg('Konqueror/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
    // Konqueror 2.2.2 says Konqueror/2.2.2
    // Konqueror 3.0.3 says Konqueror/3
    } else if (ereg('(Konqueror/)(.*)(;)', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[2]);
        define('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
    } else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)
               && ereg('Safari/([0-9]*)', $HTTP_USER_AGENT, $log_version2)) {
        define('PMA_USR_BROWSER_VER', $log_version[1] . '.' . $log_version2[1]);
        define('PMA_USR_BROWSER_AGENT', 'SAFARI');
    } else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        define('PMA_USR_BROWSER_VER', $log_version[1]);
        define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
    } else {
        define('PMA_USR_BROWSER_VER', 0);
        define('PMA_USR_BROWSER_AGENT', 'OTHER');
    }
}

/**
 * Save the files associated with a entites (annex file). 
 *
 * @param    dir    If $dir is numeric it is the id of the entites. In the other case, $dir should be a temporary directory.
 *
 */

function save_annex_file($dir,$file,$filename,$uploaded=TRUE) {

  if (!$dir) die("Internal error in saveuploadedfile dir=$dir");
  if (is_numeric($dir)) $dir="docannexe/fichier/$dir";

  if (!file_exists(SITEROOT.$dir)) {
    if (!@mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS[filemask]))) die("ERROR: unable to create the directory \"$dir\"");
  }
  $filename=basename($filename); // take only the name
  if (!$file) die("ERROR: save_annex_file file is not set");
  $dest=$dir."/".$filename;
  if ($uploaded) {
    if (!move_uploaded_file($file,SITEROOT.$dest)) die("ERROR: a problem occurs while moving the uploaded file.");
  } else {
    // try to move
    if (!(@rename($file,SITEROOT.$dest))) {
      // no that fails,  try then to copy
      if (!copy($file,SITEROOT.$dest)) die("ERROR: a problem occurs while moving the file.");
      // and try to delete
      @unlink($file);
    }
  }


  @chmod(SITEROOT.$dest, 0666  & octdec($GLOBALS[filemask]));
  
  return $dest;
}

/**
 * Save the image files associated with a entites (annex file). Check it is a valid image.
 *
 * @param    dir    If $dir is numeric it is the id of the entites. In the other case, $dir should be a temporary directory.
 *
 */

function save_annex_image($dir,$file,$filename,$uploaded=TRUE) {

  if (!$dir) die("Internal error in saveuploadedfile dir=$dir");
  if (is_numeric($dir)) $dir="docannexe/image/$dir";
  if (!$file) die("ERROR: save_annex_file file is not set");

  $tmpdir=tmpdir();
  if ($uploaded) { // it must be first moved if not it cause problem on some provider where some directories are forbidden
    $newfile=$tmpdir."/".basename($file);
    if (!move_uploaded_file($file,$newfile)) die("ERROR: a problem occurs while moving the uploaded file from $file to $newfile.");    
    $file=$newfile;
  }
  $info=getimagesize($file);
  if (!is_array($info)) die("ERROR: the format of the image has not been recognized");
  $exts=array("gif", "jpg", "png", "swf", "psd", "bmp", "tiff", "tiff", "jpc", "jp2", "jpx", "jb2", "swc", "iff");
  $ext=$exts[$info[2]-1];

  if (!file_exists(SITEROOT.$dir)) {
    if (!@mkdir(SITEROOT.$dir,0777  & octdec($GLOBALS[filemask]))) die("ERROR: unable to create the directory \"$dir\"");
  }

  $filename=preg_replace("/\.\w+$/","",basename($filename)); // take only the name, remove the extensio
  $dest=$dir."/".$filename.".".$ext;
  // try to move
  if (!(@rename($file,SITEROOT.$dest))) {
    // no that fails,  try then to copy
    if (!copy($file,SITEROOT.$dest)) die("ERROR: a problem occurs while moving the file.");
    // and try to delete
    @unlink($file);
  }

  @chmod(SITEROOT.$dest, 0666 & octdec($GLOBALS[filemask]));
  
  return $dest;
}


function tmpdir()

{
  $tmpdir="CACHE/tmp";
  if (!file_exists($tmpdir)) { 
    mkdir($tmpdir,0777  & octdec($GLOBALS[filemask])); 
  }
  return $tmpdir;
}


// valeur de retour identifiant ce script
return 568;

?>
