<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cï¿½ou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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


function writefile ($filename,$text)
{
# echo "name de fichier : $filename";
   if (file_exists($filename)) { 
     if (! (unlink($filename)) ) die ("Ne peut pas supprimer $filename. probleme de right contacter Luc ou Ghislain");
   }
  $ret=($f=fopen($filename,"w")) && (fputs($f,$text)!==false) && fclose($f);
   
  @chmod ($filename,0666 & octdec($GLOBALS['filemask']));
  return  $ret;
}


function postprocessing(&$context)

{
  if ($context) {
    foreach($context as $key=>$val) {
      if (is_array($val)) {
	postprocessing($context[$key]);
      } else {
	$context[$key]=str_replace(array("\n","ï¿½240"),array(" ","&amp;nbsp;"),htmlspecialchars(stripslashes($val)));
      }
    }
  }
}



#desuet
#function rmscript($source) {
#	// Remplace toutes les balises ouvrantes susceptibles de lancer un script
#	return eregi_replace("<(\%|\?|( *)script)", "&lt;\\1", $source);
#}

/**
 *   Extrait toutes les variables passï¿½s par la mï¿½hode post puis les stocke dans 
 *   le tableau $context
 */

function extract_post($arr=-1) {
  if (!is_array($arr)) $arr=&$_POST;
  foreach ($arr as $key=>$val) {
    if (!isset($GLOBALS['context'][$key])) // protege
      $GLOBALS['context'][$key]=$val;
  }
  array_walk($GLOBALS['context'],"clean_request_variable");
}


function clean_request_variable(&$var) 
{
	static $filter;
	if (!$filter) {
		require_once 'class.inputfilter.php';
		$filter = new InputFilter(array(), array(), 1, 1);
  }

	if (is_array($var)) {
	#print_r($var);
	foreach(array_keys($var) as $k) {
		clean_request_variable($var[$k]);
	}
	} else {
		$var = magic_stripslashes($var);
		$var = str_replace(array("\n", "&nbsp;"), array("", "ï¿½240"), $filter->process(trim($var)));
  }
}

function magic_addslashes($var) 
{
	if (!get_magic_quotes_gpc()) {
		$var = addslashes($var);
	}
	return $var;
}

function magic_stripslashes($var) 
{
	if (get_magic_quotes_gpc()) {
		$var = stripslashes($var);
	}
	return $var;
}


function get_max_rank ($table,$where="") 

{
  if ($where) $where="WHERE ".$where;

  #require_once ($GLOBALS[home]."connect.php");
  $rank=$db->getone("SELECT MAX(rank) FROM #_TP_$table $where");
  if ($db->errorno()) dberror();

  return $rank+1;
}

function chrank($table,$id,$critere,$dir,$inverse="",$jointables="")

{
  global $db;

  $table="#_TP_$table";
  $dir=$dir=="up" ? -1 : 1;  if ($inverse) $dir=-$dir;
  $desc=$dir>0 ? "" : "DESC";
  if ($jointables) {
    $jointables=",#_TP_".
      trim(join(",#_TP_",preg_split("/,\s*/",$jointables)));
  }
  $result=$db->execute(lq("SELECT $table.id,$table.rank FROM $table $jointables WHERE $critere ORDER BY $table.rank $desc")) or dberror();

  $rank=$dir>0 ? 1 : mysql_num_rows($result);

  while ($row=$result->fetchrow($result)) {
    if ($row['id']==$id) {
      # intervertit avec le suivant s il existe
      if (!($row2=$result->fetchrow($result))) break;
      $db->execute(lq("UPDATE $table SET rank='$rank' WHERE id='$row2[id]'")) or dberror();
      $rank+=$dir;
    }
    if ($row['rank']!=$rank) {
      $db->execute(lq("UPDATE $table SET rank='$rank' WHERE id='$row[id]'")) or dberror();
    }
    $rank+=$dir;
  }
} 


/**
 * function returning the closing tag corresponding to the opening tag in the sequence
 * this function could be smarter.
 */

function closetags($text) {
	preg_match_all("/<(\w+)\b[^>]*>/",$text,$results,PREG_PATTERN_ORDER);
	$n=count($results[1]);
	for($i=$n-1; $i>=0; $i--) $ret.="</".$results[1][$i].">";
	return $ret;
}


/*
function myquote (&$var)
{
  if (is_array($var)) {
    array_walk($var,"myquote");
    return $var;
  } else {
    return $var=addslashes(stripslashes($var));
  }
}
*/
function myaddslashes (&$var)
{
  if (is_array($var)) {
    array_walk($var,"myaddslashes");
    return $var;
  } else {
    return $var=addslashes($var);
  }
}



function myfilemtime($filename)

{
  return file_exists($filename) ? filemtime($filename) : 0;
}


function update()

{ if (defined("SITEROOT")) {
    @touch(SITEROOT."CACHE/maj");
  } else {
    @touch("CACHE/maj");
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



function translate_xmldata($data) 

{
	return strtr($data,array("&"=>"&amp;","<" => "&lt;", ">" => "&gt;"));
}


### use the transaction now.
function unlock()
{
  // Déverrouilles toutes les tables vérouillées
  // fonction lock_write()
  if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) 
    $db->execute(lq("UNLOCK TABLES")) or dberror();
}


function lock_write()
{
  // Vérouille toutes les tables MySQL en écriture
  $list = func_get_args();
  if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) 
     $db->execute(lq("LOCK TABLES #_TP_". join (" WRITE ,".$GLOBALS['tp'],$list)." WRITE")) or dberror();
}

function prefix_keys($prefix,$arr)

{
  if (!$arr) return $arr;
  foreach ($arr as $k=>$v) $outarr[$prefix.$k]=$v;
  return $outarr;
}


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

function getoption($name) {

  global $db;
  static $options_cache;
  if (!$name) return;
  if (!isset($options_cache)) {
    $optionsfile=SITEROOT."CACHE/options_cache.php";
	
    if (file_exists($optionsfile)) {
      require($optionsfile);
    } else {
      require_once('optionfunc.php');
     	$options_cache = cacheOptionsInFile($optionsfile);
    }
  }
  if (is_array($name)) {
    foreach ($name as $n) {
      if ($options_cache[$n]) $ret[$n]=stripslashes($options_cache[$n]);
    }    
    return  ($ret);
  } else {
    if ($options_cache[$name]) // cached ?
      return  stripslashes ($options_cache[$name]);
    $critere="name='$name'";
  }
}


function getlodeltext($name,$group,&$id,&$contents,&$status,$lang=-1)

{
  if ($group=="") {
    if ($name[0]!='[' && $name[1]!='@') return array(0,$name);
    $dotpos=strpos($name,".");
    if ($dotpos) {
      $group=substr($name,1,$dotpos); 
      $name=substr($name,$dotpos+1,-1);
    } else {
      die("ERROR: unknow group for getlodeltext");
    }
  }
  if ($lang==-1) $lang=$GLOBALS['la'] ? $GLOBALS['la'] : $GLOBALS['lodeluser']['lang'];
  if (!$lang) $lang = $GLOBALS['installlang']; // if no lang is specified choose the default installation language
  require_once("connect.php");
  global $db;

  if ($group!="site") {
    usemaindb();
    $prefix="#_MTP_";
  } else {
    $prefix="#_TP_";
  }

  $critere=$GLOBALS['lodeluser']['visitor'] ? "" : "AND status>0";
  $logic=false;
  do {
    $arr=$db->getRow("SELECT id,contents,status FROM ".lq($prefix)."texts WHERE name='".$name."' AND textgroup='".$group."' AND (lang='$lang' OR lang='') $critere ORDER BY lang DESC");
    if ($arr===false) dberror();
    if (!$GLOBALS['lodeluser']['admin'] || $logic) break;
    if (!$arr) {
      // create the textfield
      require_once("logic.php");
      $logic=getLogic("texts");
      $logic->createTexts($name,$group);
    }
  } while(!$arr);

  if ($group!="site") usecurrentdb();

  $id=$arr['id'];
  $contents=$arr['contents'];
  $status=$arr['status'];
  if (!$contents && $GLOBALS['lodeluser']['visitor']) $contents="@".$name;
}

function getlodeltextcontents($name,$group="",$lang=-1)

{
  if ($lang==-1) $lang=$GLOBALS['la'] ? $GLOBALS['la'] : $GLOBALS['lodeluser']['lang'];
  if ($GLOBALS['langcache'][$lang][$group.".".$name]) {
    return $GLOBALS['langcache'][$lang][$group.".".$name];
  } else {
		#echo "name=$name,group=$group,id=$id,contents=$contents,status=$status,lang=$lang<br />";
    getlodeltext($name,$group,$id,$contents,$status,$lang);
    return $contents;
  }
}


function makeurlwithid ($id, $base = 'index')
{
	
	if (is_numeric($base)) {
		$t    = $id;
		$id   = $base;
		$base = $t;
	} // exchange
	if (defined('URI')) {
		$uri = URI;
	} else {
		// compat 0.7
		if ($GLOBALS['idagauche']) {
			$uri = 'leftid';
		}
	}
	
	$class = $GLOBALS['db']->getOne(lq("SELECT class FROM #_TP_objects WHERE id='$id'"));
		if ($GLOBALS['db']->errorno()) {
			dberror();
		}
	if($class != 'entities')
		$uri = '';
	switch($uri) {
	case 'leftid':
		return $base. $id. '.'. $GLOBALS['extensionscripts'];
	//fabrique des urls type index.php?/rubrique/mon-titre
	case 'path':
		$id = intval($id);
		$path = getPath($id,'path');
		return $path;
	case 'querystring':
		$id = intval($id);
		$path = getPath($id,'querystring');
		return $path;
	default:
		return $base. '.'. $GLOBALS['extensionscripts']. '?id='. $id;
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
 * retourne le chemin complet vers une entité
 *
 * @param integer $id identifiant numérique de l'entité
 * @param string $urltype le type d'url utilisé (path,querystring)
 * @return string le chemin
 * @since fonction ajoutée en 0.8
 */
function getPath($id, $urltype,$base='index')
{
	$urltype = 'querystring'; //la version actuelle de lodel ne gère que le type path
	if($urltype!='path' && $urltype!='querystring') {
		return;
	}
	$id = intval($id);
		$result = $GLOBALS['db']->execute(lq("SELECT identifier FROM #_TP_entities INNER JOIN #_TP_relations ON id1=id WHERE id2='$id' ORDER BY degree DESC")) or dberror();
		while(!$result->EOF) {
			$path.= '/'. $result->fields['identifier'];
			$result->MoveNext();
		}
		$row = $GLOBALS['db']->getRow(lq("SELECT identifier FROM #_TP_entities WHERE id='$id'"));
		if ($GLOBALS['db']->errorno()) {
			dberror();
		}
		$path.= "/$id-". $row['identifier'];
		if($urltype == 'path') {
			return $base. '.'. $GLOBALS['extensionscripts']. $path;
		}
		return "$base.". $GLOBALS['extensionscripts']. "?$path";
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

  if (!$originalname) $originalname=$filename;
  $originalname=preg_replace("/.*\//","",$originalname);
  $ext=substr($originalname,strrpos($originalname,".")+1);
  $size = $filename ? filesize($filename) : strlen($contents);
  get_PMA_define(); 
  if($mimetype[$ext] && !(PMA_USR_BROWSER_AGENT == 'IE' && $ext == "pdf" && PMA_USR_OS != "Mac")){
    $mime = $mimetype[$ext];
    $disposition = "inline";
  } else {
    $mime = "application/force-download";
    $disposition = "attachment";
  }
  if ($filename) {
    $fp=fopen($filename,"rb");
    if (!$fp) die ("ERROR: The file \"$filename\" is not readable");
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
 * Save the file or image files associated with a entites (annex file). Check it is a valid image.
 *
 * @param    dir    If $dir is numeric it is the id of the entites. In the other case, $dir should be a temporary directory.
 *
 */

function save_annex_file($type,$dir,$file,$filename,$uploaded,$move,&$error) 

{

  if ($type!="file" && $type!="image") die("ERROR: type is not a valid file type");
  if (!$dir) die("Internal error in saveuploadedfile dir=$dir");
  if (is_numeric($dir)) $dir="docannexe/$type/$dir";
  if (!$file) die("ERROR: save_annex_file file is not set");

  if ($type=="image") { // check this is really an image
    if ($uploaded) { // it must be first moved if not it cause problem on some provider where some directories are forbidden
      $tmpdir=tmpdir();
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
  } 

  checkdocannexedir($dir);

  if ($type=="image") {
    $filename=preg_replace("/\.\w+$/","",basename($filename)); // take only the name, remove the extensio
    $dest=$dir."/".$filename.".".$ext;
  } else {
    $dest=$dir."/".basename($filename);
  }

  if (!copy($file,SITEROOT.$dest)) die("ERROR: a problem occurs while moving the file.");
  // and try to delete
  if ($move) @unlink($file);

  @chmod(SITEROOT.$dest, 0666 & octdec($GLOBALS['filemask']));

  return $dest;
}

function checkdocannexedir($dir)

{
  if (!file_exists(SITEROOT.$dir)) {
    if (!@mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']))) die("ERROR: impossible to create the directory \"$dir\"");
    @chmod(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
    writefile(SITEROOT.$dir."/index.html","");
  }
}


function tmpdir()

{
  $tmpdir=defined("TMPDIR") && (TMPDIR) ? TMPDIR : "CACHE/tmp";

  if (!file_exists($tmpdir)) { 
    mkdir($tmpdir,0777  & octdec($GLOBALS['filemask']));
    chmod($tmpdir,0777 & octdec($GLOBALS['filemask'])); 
  }
  return $tmpdir;
}

function myhtmlentities($text)

{
  return str_replace(array("&","<",">","\""),array("&amp;","&lt;","&gt;","&quot;"),$text);
}


//
// Main function to add/modify records 
//

function setrecord($table,$id,$set,$context=array())

{
  global $db;

  $table=lq("#_TP_").$table;

  if ($id>0) { // update
    foreach($set as $k=>$v) {
      if (is_numeric($k)) { // get it from context
	$k=$v;
	$v=$context[$k];
      }
      if ($update) $update.=",";
      $update.="$k=".$db->qstr($v);
    }
    if ($update)
      $db->execute("UPDATE $table SET  $update WHERE id='$id'") or dberror();
  } else {
    $insert="";$values="";
    if (is_string($id) && $id=="unique") {
      $id=uniqueid($table);
      $insert="id";$values="'".$id."'";
    }
    foreach($set as $k=>$v) {
      if (is_numeric($k)) { // get it from context
	$k=$v;
	$v=$context[$k];
      }
      if ($insert) { $insert.=","; $values.=","; }
      $insert.=$k;
      $values.=$db->qstr($v);
    }

    if ($insert) {

      $db->execute("REPLACE INTO $table (".$insert.") VALUES (".$values.")") or dberror();
      if (!$id) $id=$db->insert_id();
    }
  }
  return $id;
}

/**
 *
 * Function to solve the UTF8 poor support in MySQL
 * This function should be i18n in the futur to support more language
 */
/**
 * Fonction qui indique si une chaine est en utf-8 ou non
 *
 * Cette fonction est insirï¿½ de Dotclear et de
 * http://w3.org/International/questions/qa-forms-utf-8.html.
 *
 * @param string $string la chaï¿½e ï¿½tester
 * @return le rï¿½ultat de la fonction preg_match c'est ï¿½dire false si la chaï¿½e n'est pas en
 * UTF8
 */
function isUTF8($string)
	{
		// From http://w3.org/International/questions/qa-forms-utf-8.html
		return preg_match('%^(?:
			  [\x09\x0A\x0D\x20-\x7E]			# ASCII
			| [\xC2-\xDF][\x80-\xBF]				# non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]			# excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]			# excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}			# planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
		)*$%xs', $string);
	}

/**
 * Transforme une chaine de caractï¿½e UTF8 en minuscules dï¿½accentuï¿½s
 *
 * Cette fonction prends en entrï¿½ une chaï¿½e en UTF8 et donne en sortie une chaï¿½e
 * o les accents ont ï¿½ï¿½remplacï¿½ par leur ï¿½uivalent dï¿½accentuï¿½ De plus les caractï¿½es
 * sont mis en minuscules et les espaces en dï¿½ut et fin de chaï¿½e sont enlevï¿½.
 *
 * Cette fonction est utilisï¿½ pour les entrï¿½s d'index ainsi que dans le moteur de recherche
 * et pour le calcul des identifiants littï¿½aux.
 *
 * @param string $text le texte ï¿½passer en entrï¿½
 * @return le texte transformï¿½en minuscule
 */
function makeSortKey($text)
{
	$text = strip_tags($text);
	//remplacement des caractï¿½es accentues en UTF8
	$replacement = array(chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe',
											chr(197).chr(160) => 'S',chr(197).chr(189) => 'Z', 
											chr(197).chr(161) => 's',	chr(197).chr(190) => 'z', 
											chr(197).chr(184) => 'Y',	chr(194).chr(165) => 'Y', 
											chr(194).chr(181) => 'u',	chr(195).chr(134) => 'AE',
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
											chr(195).chr(191) => 'y',
									);
	$text = strtr($text,$replacement);
	return trim(strtolower($text));
}

/**
 * rightonentity check if a user has the rights to perform an action on an entity
 * @param string $action the action to be performed : create, edit, delete,...
 * @param array $context the current context
 * @return boolean true if the user has the right, false ifnot
 */
function rightonentity ($action, $context) {
	if ($GLOBALS['lodeluser']['admin']) return true;

	if ($context['id'] && (!$context['usergroup'] || !$context['status'])) {
		// get the group, the status, and the parent
		$row = $GLOBALS['db']->getRow (lq ("SELECT idparent,status,usergroup FROM #_TP_entities WHERE id='".$context['id']."'"));
	if (!$row) die ("ERROR: internal error in rightonentity");
	$context = array_merge ($context, $row);
	}
  // groupright ?
	if ($context['usergroup']) {
  	$groupright = in_array ($context['usergroup'], explode (',', $GLOBALS['lodeluser']['groups']));
  	if (!$groupright)return false;
	}

	// only admin can work at the base.
	$editorok= $GLOBALS['lodeluser']['editor'] && $context['idparent'];
	// redactor are ok, only if they own the document and it is not protected.
	$redactorok = ($context['iduser']==$GLOBALS['lodeluser']['id'] && $GLOBALS['lodeluser']['redactor']) && $context['status']<8 && $context['idparent'];

	switch($action) {
	case 'create' :
		return ($GLOBALS['lodeluser']['editor'] ||  ($GLOBALS['lodeluser']['redactor'] && $context['status']<8));// &&  $context['id'];
		break;
	case 'delete' :
		return abs($context['status'])<8 && $editorok;
		break;
	case 'edit':
	case 'advanced' :
	case 'publish' :
	case 'move' :
	case 'changerank' :
		return $editorok || $redactorok;
		break;
	case 'unpublish' :
	case 'protect' :
		return $editorok;
		break;
	case 'changestatus' :
		if ($context['status']<0) {
			return $editorok || $redactorok;
		} else {
			return $editorok;
		} 
	default:
		if ($GLOBALS['lodeluser']['visitor'])
			die("ERROR: unknown action \"$action\" in the loop \"rightonentity\"");
		return;
	}
}//end of rightonentity function


/**
 * generate the SQL criteria depending whether ids is an array or a number
 */

function sql_in_array($ids) 

{
  return is_array($ids) ? "IN ('".join("','",$ids)."')" : "='".$ids."'";
}

/**
 * DAO factory
 *
 */

function &getDAO($table) {
	static $factory; // cache

	if ($factory[$table]) {
		return $factory[$table]; // cache
	}

  require_once 'dao.php' ;
  require_once 'dao/class.'.$table.'.php';
  $daoclass = $table. 'DAO';
  
  $factory[$table] = new $daoclass;
  return $factory[$table];
}

/**
 * generic DAO factory
 *
 */

function &getGenericDAO($table,$idfield)

{
  static $factory; // cache

  if ($factory[$table]) return $factory[$table]; // cache

  require_once("dao.php");
  require_once("genericdao.php");

  $factory[$table]=new genericDAO ($table,$idfield);
  return $factory[$table];
}

/**
 * Return true if a type can contains other types.
 * (this function is used in edition, to make entities clicable or not)
 * @param idtype id of the type
 */
function canContainTypes ($idtype) {
  global $db;
  //select types in entitytypes_entitytypes which can be contains in idtype (identitytypes2) 
  //but select only those who can be contains directly (not in advanced function)
  $sql = "SELECT COUNT(*) as count FROM #_TP_entitytypes_entitytypes , #_TP_types as t WHERE identitytype = t.id AND identitytype2='$idtype' AND t.display!='advanced'";
  $count = $db->getOne (lq($sql));
  if ($count === false) return false;
  if ($count > 0) return true;
  return false;
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

/**
 * Indentation de code HTML, XML
 *
 * @param string $source le code a indenter
 * @param string $indenter les caractères à utiliser pour l'indentation. Par défaut deux espaces.
 * @return le code indenté proprement :)
 */
function _indent($source, $indenter = '  ')
{
	if(preg_match('/<\?xml[^>]*\s* version\s*=\s*[\'"]([^"\']*)[\'"]\s*encoding\s*=\s*[\'"]([^"\']*)[\'"]\s*\?>/i', $source)) {
			$source = preg_replace('/<\?xml[^>]*\s* version\s*=\s*[\'"]([^"\']*)[\'"]\s*encoding\s*=\s*[\'"]([^"\']*)[\'"]\s*\?>/i', '', $source);
			require_once 'xmlfunc.php';
			$source = indentXML($source, false, $indenter);
			return $source;
		}
	$source = _indent_xhtml($source,$indenter);
	return $source;
}


/**
 * Indentation de code XHTML
 *
 * @param string $source le code a indenter
 * @param string $indenter les caractères à utiliser pour l'indentation. Par défaut deux espaces.
 * @return le code indenté proprement :)
 */
function _indent_xhtml($source, $indenter = '  ')
{
		$source = str_replace("\t","",$source);
		// Remove all space after ">" and before "<".
		$search = array("/>(\s)*/", "/(\s)*</");
		$replace = array(">", "<");
		$source = preg_replace($search, $replace, $source);
		// Iterate through the source.
		$level = 0;
		$source_len = strlen($source);
		$pt = 0;
		while ($pt < $source_len) {
			if ($source{$pt} === '<') {
				// We have entered a tag.
				// Remember the point where the tag starts.
				$started_at = $pt;
				$tag_level = 1;
				// If the second letter of the tag is "/", assume its an ending tag.
				if ($source{$pt+1} === '/') {
					$tag_level = -1;
				}
				// If the second letter of the tag is "!", assume its an "invisible" tag.
				if ($source{$pt+1} === '!') {
					$tag_level = 0;
				}
				// Iterate throught the source until the end of tag.
				while ($source{$pt} !== '>') {
					$pt++;
				}
				// If the second last letter is "/", assume its a self ending tag.
				if ($source{$pt-1} === '/') {
					$tag_level = 0;
				}
				$tag_lenght = $pt+1-$started_at;

				// Decide the level of indention for this tag.
				// If this was an ending tag, decrease indent level for this tag..
				if ($tag_level === -1) {
					$level--;
				}
				if ($level<0) {
					$level = 0;
				}
				// Place the tag in an array with proper indention.
				$array[] = str_repeat($indenter, $level). substr($source, $started_at, $tag_lenght);
				// If this was a starting tag, increase the indent level after this tag.
				if ($tag_level === 1) {
					$level++;
				}
				// if it was a self closing tag, dont do shit.
			}
			// Were out of the tag.
			// If next letter exists...
			if (($pt+1) < $source_len) {
				// ... and its not an "<".
				if ($source{$pt+1} !== '<') {
					$started_at = $pt+1;
					// Iterate through the source until the start of new tag or until we reach the end of file.
					while ($source{$pt} !== '<' && $pt < $source_len) {
						$pt++;
					}
					// If we found a "<" (we didnt find the end of file) 
					if ($source{$pt} === '<') {
						$tag_lenght = $pt-$started_at;
						// Place the stuff in an array with proper indention.
						$array[] = str_repeat($indenter, $level). substr($source, $started_at, $tag_lenght);
					}
					// If the next tag is "<", just advance pointer and let the tag indenter take care of it.
				} else {
					$pt++;
				}
				// If the next letter doesnt exist... Were done... well, almost..
			} else {
				break;
			}
		}
		
		// Replace old source with the new one we just collected into our array.
		#print_r($array);
		$c = count($array);
		for($i = 0 ; $i < $c ; $i++) {
			if(preg_match("/<textarea|pre [^>]+>/",$array[$i])) {
				$array[$i+1] = trim(str_replace("\n", "", $array[$i+1]));
			}	elseif(!preg_match("/<script|style [^>]+>/",$array[$i])) {
				// si c'est pas une balise script ou style
				$array[$i+1] = str_replace("\n", "", $array[$i+1]);
			} 
		}
		$source = implode($array, "\n");
		return $source;
}

/**
 * Récupération des champs génériques dc.* associés aux entités
 *
 * @param integer $id identifiant numérique de l'entité dont on veut récupérer un champ dc
 * @param string $dcfield le nom du champ à récupérer (sans le dc.devant). Ex : .'description' pour 'dc.description'
 * @return le contenu du champ passé dans le paramètre $dcfield
 */

function get_dc_fields($id, $dcfield)
{
	$dcfield = 'dc.' . $dcfield;
	global $db;
	if ($result = $db->execute(lq("SELECT #_TP_entities.id, #_TP_types.class, #_TP_tablefields.name, #_TP_tablefields.g_name
	FROM #_TP_entities, #_TP_types, #_TP_tablefields
  	WHERE (#_TP_tablefields.g_name = '$dcfield')
  	AND #_TP_tablefields.class = #_TP_types.class
  	AND #_TP_entities.idtype = #_TP_types.id
  	AND #_TP_entities.id = $id")
	))

	{
		if ($row = $result->fields)
			{
			$id  = $row['id'];
			$id_class_fields[$id]['class'] = $row['class'];
			$id_class_fields[$id][$row['g_name']] = $row['name'];
	
			if ($id_class_fields[$id][$dcfield])
				{
				$class_table = "#_TP_".$id_class_fields[$id]['class'];
				$field = $id_class_fields[$id][$dcfield];
				$result =$db->getOne(lq("SELECT $field FROM $class_table WHERE identity = '$id'"));
				if ($result===false) {
					dberror();
					}
  				}
  			return $result;
			} 
	else return false;
	}
else return false;
}

// Tente de récupérer la liste des locales du système dans un tableau

function list_system_locales(){
	ob_start();
	if(system('locale -a')){ 
		$str = ob_get_contents();
		ob_end_clean();
		return split("\n", trim($str));
	}else{
		return FALSE;
	}
}

// valeur de retour identifier ce script
return 568;

?>
