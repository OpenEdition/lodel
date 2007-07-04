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

require_once 'unset_globals.php';


function writefile ($filename,$text)
{
 //echo "nom de fichier : $filename";
   if (file_exists($filename)) 
   { 
     if (! (unlink($filename)) ) die ("Ne peut pas supprimer $filename. probleme de droit");
   }
   $ret=($f=fopen($filename,"w")) && (fputs($f,$text)!==false) && fclose($f);
   @chmod ($filename,0666 & octdec($GLOBALS[filemask]));
   return  $ret;
}


function get_tache (&$id)

{
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]taches WHERE id='$id' AND statut>0") or die (mysql_error());
  if (!($row=mysql_fetch_assoc($result))) { back(); return; }
  $arg2 = unserialize($row['context']);
  settype($arg2, 'array');
  $row=array_merge($row, $arg2);
  return $row;
}

function posttraitement(&$context)

{
  if ($context) {
    foreach($context as $key=>$val) {
      if (is_array($val)) {
	posttraitement($context[$key]);
      } else {
	$context[$key]=str_replace(array("\n","Â\240"),array(" ","&amp;nbsp;"),htmlspecialchars(stripslashes($val)));
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
	
  foreach ($_POST as $key=>$val) {
    if (!isset($GLOBALS[context][$key])) // protege
      $GLOBALS[context][$key]=$val;
  }
  function clean_for_extract_post(&$var) {
    if (is_array($var)) {
      array_walk($var,"clean_for_extract_post");
    } else {
      $var=str_replace(array("\n","&nbsp;"),array("","Â\240"),rmscript(trim($var)));
    }
  }
  array_walk($GLOBALS[context],"clean_for_extract_post");
}


function get_ordre_max ($table,$where="") 

{
  if ($where) $where="WHERE ".$where;

  #require_once ($GLOBALS[home]."connect.php");
  $result=mysql_query ("SELECT MAX(ordre) FROM $GLOBALS[tp]$table $where") or die (mysql_error());
  if (mysql_num_rows($result)) list($ordre)=mysql_fetch_row($result);
  if (!$ordre) $ordre=0;

  return $ordre+1;
}

function chordre($table,$id,$critere,$dir,$inverse="",$jointables="")

{
  $table=$GLOBALS[tp].$table;
  $dir=$dir=="up" ? -1 : 1;  if ($inverse) $dir=-$dir;
  $desc=$dir>0 ? "" : "DESC";
  if ($jointables) {
    $jointables=",".$GLOBALS[tp].
      trim(join(",".$GLOBALS[tp],preg_split("/,\s*/",$jointables)));
  }
  $result=mysql_query("SELECT $table.id,$table.ordre FROM $table $jointables WHERE $critere ORDER BY $table.ordre $desc") or die (mysql_error());

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
  preg_match_all("/<img\b[^>]+src=\"([^\"]+\.([^\"\.]+))\"([^>]*>)/i",$text,$results,PREG_SET_ORDER);
  foreach ($results as $result) {
    $imgfile=$result[1];
    if (substr($imgfile,0,5)=="http:") continue;
    if ($imglist[$imgfile]) {
      $text=str_replace($result[0],"<img src=\"$imglist[$imgfile]\"",$text);
    } else {
      $ext=$result[2];
      $imglist[$imgfile]=$newimgfile=$callback($imgfile,$ext,$count,$argument);
      if ($newimgfile) { // ok, the image has been correctly copied
#	echo "images: $imgfile $newimgfile <br>";
	$text=str_replace($result[0],"<img src=\"$newimgfile\"".$result[3],$text);
	@chmod(SITEROOT.$newimgfile, 0666  & octdec($GLOBALS['filemask']));
	$count++;
      } else { // no, problem copying the image
	$text=str_replace($result[0],"<span class=\"image_error\">[Image non convertie]</span>",$text);
      }
    }
  }
}



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
  header("Location: http://".$_SERVER[SERVER_NAME].$currenturl);exit;
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

function getoption($nom,$extracritere=" AND type!='pass'")
{
  static $options_cache;
  if (!$nom) return;

  if (is_array($nom)) {
    foreach ($nom as $n) {
      if ($options_cache[$n]) { // cached ?
	$ret[$n]=$options_cache[$n];
      } else { // not cached, get it
	$get[]=$n;
      }
    }
    if ($get) { // some to get
      $critere="nom IN ('".join("','",$get)."')";
    } else { // everything in the cache, let's return
      return $ret;
    }
  } else {
    if ($options_cache[$nom]) // cached ?
      return $options_cache[$nom];
    $critere="nom='$nom'";
  }

  $result=mysql_query("SELECT nom,valeur FROM $GLOBALS[tableprefix]options WHERE $critere $extracritere");
  if (!$result) {
    if (mysql_errno()==1146) return; // table does not exists... that can happen during the installation
    mysql_erro();
  }
  while (list($n,$val)=mysql_fetch_row($result)) {
    $options_cache[$n]=$val;
    $ret[$n]=$val;
  }

  if (is_array($nom)) {
    return $ret; // return an array
  } else {
    return $ret[$nom]; // return a string
  }
}



function makeurlwithid ($base,$id)

{
  if (is_numeric($base)) { $t=$id; $id=$base; $base=$t; } // exchange


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
  $mimetype = array(
		    'doc'=>'application/msword',
		    'htm'=>'text/html',
		    'html'=>'text/html',
		    'jpg'=>'image/jpeg',
		    'gif'=>'image/gif',
		    'png'=>'image/png',
		    'pdf'=>'application/pdf',
		    'txt'=>'text/plain',
		    'xls'=>'application/vnd.ms-excel'
		    );
  get_PMA_define();
  if (!$originalname) $originalname=$filename;
  $originalname=preg_replace("/.*\//","",$originalname);
  $ext=substr($originalname,strrpos($originalname,".")+1);
  $size = $filename ? filesize($filename) : strlen($contents);
  if($mimetype[$ext]){
    $mime = $mimetype[$ext];
    switch(PMA_USR_OS){
	case 'Win':
		if(PMA_USR_BROWSER_AGENT == 'IE'){
			if(intval(PMA_USR_BROWSER_VER) <= 6){
				$mime = "application/force-download";
    				$disposition = "attachment";
			}else{
				$disposition = "inline";
			}
		}else{
			$disposition = "inline";
		}
	break;
	default:
	$disposition = "inline";
    }
  } else {
    $mime = "application/force-download";
    $disposition = "attachment";
  }
  if (is_readable($filename)) {
    	$fp=fopen($filename,"rb");
  }else{
	die ("ERROR: The file \"$filename\" is not readable");
  }
  // fix for IE catching or PHP bug issue
  header("Pragma: public");
  header("Expires: 0"); // set expiration time
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

#  header("Cache-Control: ");// leave blank to avoid IE errors (from on uk.php.net)
#  header("Pragma: ");// leave blank to avoid IE errors (from on uk.php.net)

  header("Content-type: $mime\n");
  header("Content-transfer-encoding: binary\n");
  header("Content-length: ".$size."\n");
  header("Content-disposition: $disposition; filename=\"$originalname\"\n");
  sleep(1); // don't know why... (from on uk.php.net)
  if ($filename) {
    fpassthru($fp); 
  } else { 
    echo $contents; 
  }
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
    }

    #} else if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
    #    $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
    #} else if (!isset($HTTP_USER_AGENT)) {
    #    $HTTP_USER_AGENT = '';
    #}

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

function save_annex_file($dir,$file,$filename,$uploaded,&$error) {

  $error=FALSE;

  if (!$dir) die("Internal error in saveuploadedfile dir=$dir");
  if (is_numeric($dir)) $dir="docannexe/fichier/$dir";

  if (!file_exists(SITEROOT.$dir)) {
    if (!@mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']))) die("ERROR: unable to create the directory \"$dir\"");
    @chmod(SITEROOT.$dir,0777 & octdec($GLOBALS[filemask]));
  }
  $filename = rewriteFilename($filename);
  $filename=basename($filename); // take only the name
  if (!$file) die("ERROR: save_annex_file file is not set");
  //$filename = preg_replace('/[^\w.]+/', '-', $filename);
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

function save_annex_image($dir,$file,$filename,$uploaded,&$error) {

  $error=FALSE;

  if (!$dir) die("Internal error in saveuploadedfile dir=$dir");
  if (is_numeric($dir)) $dir="docannexe/image/$dir";
  if (!$file) die("ERROR: save_annex_file file is not set");

  $tmpdir=tmpdir();
  if ($uploaded) { // it must be first moved if not it cause problem on some provider where some directories are forbidden
    $newfile=$tmpdir."/".basename($file);
    if ($file!=$newfile && !move_uploaded_file($file,$newfile)) die("ERROR: a problem occurs while moving the uploaded file from $file to $newfile.");    
    $file=$newfile;
  }
  if (!filesize($file)) { $error="readerror"; return; }
  $info=getimagesize($file);
  if (!is_array($info)) { $error="imageformat"; return; }
  $exts=array("gif", "jpg", "png", "swf", "psd", "bmp", "tiff", "tiff", "jpc", "jp2", "jpx", "jb2", "swc", "iff");
  $ext=$exts[$info[2]-1];

  if (!$ext) { $error="imageformat"; return; }

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
  $tmpdir=defined("TMPDIR") && (TMPDIR) ? TMPDIR : "CACHE/tmp";

  if (!file_exists($tmpdir)) { 
    mkdir($tmpdir,0777  & octdec($GLOBALS[filemask]));
    chmod($tmpdir,0777 & octdec($GLOBALS[filemask])); 
  }
  return $tmpdir;
}

function myhtmlentities($text)

{
  return str_replace(array("&","<",">","\""),array("&amp;","&lt;","&gt;","&quot;"),$text);
}

function isEmail($str){
	return eregi('^[_a-z0-9-]+(\.[_a-z0-9-]*)*@[a-z0-9-]+(\.[a-z0-9-]+)+$', $str);
}


function rewriteFilename($string) {
     if(isUTF8($string)) {
	$string = preg_replace('/[^\w.-\/]+/', '_', makeSortKey($string));
     } else {
	$string = strip_tags($string);
     	$string = strtolower(htmlentities($string));
     	$string = preg_replace("/&(.)(uml);/", "$1e", $string);
     	$string = preg_replace("/&(.)(acute|cedil|circ|ring|tilde|uml);/", "$1", $string);
     	$string = preg_replace("([^\w.-]+)/", "_", html_entity_decode($string));
     	$string = trim($string, "-");
     	
     }
     return $string;
}


/**
 * Fonction qui indique si une chaine est en utf-8 ou non
 *
 * Cette fonction est inspirée de Dotclear et de
 * http://w3.org/International/questions/qa-forms-utf-8.html.
 *
 * @param string $string la chaîne à tester
 * @return le résultat de la fonction preg_match c'est-a-dire false si la chaine n'est pas en
 * UTF8
 */
function isUTF8($string)
	{
		// From http://w3.org/International/questions/qa-forms-utf-8.html
		return preg_match('%^(?:
			  [\x09\x0A\x0D\x20-\x7E]		# ASCII
			| [\xC2-\xDF][\x80-\xBF]		# non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}		# planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
		)*$%xs', $string);
	}

/**
 * Transforme une chaine de caractère UTF8 en minuscules désaccentuées
 *
 * Cette fonction prends en entrée une chaîne en UTF8 et donne en sortie une chaîne
 * où les accents ont été remplacés par leur équivalent désaccentué. De plus les caractères
 * sont mis en minuscules et les espaces en début et fin de chaine sont enlevés.
 *
 * @param string $text le texte à passer en entrée
 * @return le texte transformé en minuscule
 */
function makeSortKey($text)
{
	$text = strip_tags($text);
	//remplacement des caractères accentues en UTF8
	$replacement = array(chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe',
			chr(197).chr(160) => 'S', chr(197).chr(189) => 'Z',
			chr(197).chr(161) => 's', chr(197).chr(190) => 'z',
			chr(197).chr(184) => 'Y', chr(194).chr(165) => 'Y',
			chr(194).chr(181) => 'u', chr(195).chr(134) => 'AE',
			chr(195).chr(133) => 'A', chr(195).chr(132) => 'A',
			chr(195).chr(131) => 'A', chr(195).chr(130) => 'A',
			chr(195).chr(129) => 'A', chr(195).chr(128) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(144) => 'D',
			chr(195).chr(145) => 'N', chr(195).chr(146) => 'O',
			chr(195).chr(147) => 'O', chr(195).chr(148) => 'O',
			chr(195).chr(149) => 'O', chr(195).chr(150) => 'O',
			chr(195).chr(152) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 'SS', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(166) => 'ae',
			chr(195).chr(167) => 'c', chr(195).chr(168) => 'e',
			chr(195).chr(169) => 'e', chr(195).chr(170) => 'e',
			chr(195).chr(171) => 'e', chr(195).chr(172) => 'i',
			chr(195).chr(173) => 'i', chr(195).chr(174) => 'i',
			chr(195).chr(175) => 'i', chr(195).chr(176) => 'o',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(184) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y');
	$text = strtr($text,$replacement);
	return trim(strtolower($text));
}


// valeur de retour identifiant ce script
return 568;

?>