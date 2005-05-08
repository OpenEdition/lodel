<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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
 //echo "name de fichier : $filename";
   if (file_exists($filename)) 
   { 
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
	$context[$key]=str_replace(array("\n","Â\240"),array(" ","&amp;nbsp;"),htmlspecialchars(stripslashes($val)));
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
 *   Extrait toutes les variables passées par la méthode post puis les stocke dans 
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


function clean_request_variable(&$var) {
  static $filter;

  if (!$filter) {
    require_once("class.inputfilter.php");
    $filter=new InputFilter(array(),array(),1,1);
  }

  if (is_array($var)) {
    array_walk($var,"clean_request_variable");
  } else {
    $var=str_replace(array("\n","&nbsp;"),array("","Â\240"),$filter->process(trim($var)));
    if (!get_magic_quotes_gpc()) $var=addslashes($var);
  }
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

function closetags($text) 
{
  preg_match_all("/<(\w+)\b[^>]*>/",$text,$results,PREG_PATTERN_ORDER);
  $n=count($results[1]);
  for($i=$n-1; $i>=0; $i--) $ret.="</".$results[1][$i].">";
  return $ret;
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
#function unlock()
#{
#  // Dévérouille toutes les tables vérouillées précédemment par la 
#  // fonction lock_write()
#  if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) 
#    $db->execute(lq("UNLOCK TABLES") or dberror();
#}
#
#
#function lock_write()
#{
#  // Vérouille toutes les tables en écriture
#  $list=func_get_args();
#  if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) 
#     $db->execute(lq("LOCK TABLES #_TP_".join (" WRITE ,".$GLOBALS['tp'],$list)." WRITE") or dberror();
#}

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

function getoption($name)
{
  global $db;
  static $options_cache;

  if (!$name) return;

  if (!isset($options_cache)) {
    $optionsfile=SITEROOT."CACHE/options_cache.php";

    if (file_exists($optionsfile)) {
      include($optionsfile);
    } else {
      $result=$db->execute(lq("SELECT #_TP_options.name,value,defaultvalue,#_TP_optiongroups.name as grpname FROM #_TP_options INNER JOIN #_TP_optiongroups ON #_TP_optiongroups.id=idgroup WHERE #_TP_optiongroups.status>0 AND #_TP_options.status>0"));
      if (!$result) {
	if ($db->errorno()!=1146) dberror(); 	
	// table does not exists... that can happen during the installation	
	$options_cache=array();
      }
      // create the cache options file
      $txt="<"."?php\n\$options_cache=array(\n";
      while ($result && !$result->EOF) {
	$optname=$result->fields['grpname'].".".$result->fields['name'];
	$value=$result->fields['value'] ? $result->fields['value'] : $result->fields['defaultvalue'];
	$txt.="'".$optname."'=>'".$value."',\n";
	$options_cache[$optname]=$value;
	$result->MoveNext();
      }
      $txt.=");?".">";
      writefile($optionsfile,$txt);
    }
  }
  if (is_array($name)) {
    foreach ($name as $n) {
      if ($options_cache[$n]) $ret[$n]=$options_cache[$n];
    }    
    return $ret;
  } else {
    if ($options_cache[$name]) // cached ?
      return $options_cache[$name];
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
    getlodeltext($name,$group,$id,$contents,$status,$lang);
    return $contents;
  }
}


function makeurlwithid ($id,$base="index")

{
  if (is_numeric($base)) { $t=$id; $id=$base; $base=$t; } // exchange

  if (defined("URI")) {
    $uri=URI;
  } else {
    // compat 0.7
    if ($GLOBALS['idagauche']) $uri="leftid";
  }

  switch($uri) {
  case 'leftid':
    return $base.$id.".".$GLOBALS['extensionscripts'];
    //////////
  case 'path':
    $result=$GLOBALS['db']->execute(lq("SELECT identifier FROM #_TP_entities INNER JOIN #_TP_relations ON id1=id WHERE id2='".intval($id)."' ORDER BY degree DESC")) or dberror();
    while(!$result->EOF) {
      $path.="/".$result->fields['identifier'];
      $result->MoveNext();
    }
    $path.="/". $GLOBALS['db']->getOne(lq("SELECT identifier FROM #_TP_entities WHERE id='".intval($id)."'"));
    if ($GLOBALS['db']->errorno()) dberror();
    return $base.".".$GLOBALS['extensionscripts']."?".$path;
    //////////
  default:
    return $base.".".$GLOBALS['extensionscripts']."?id=".$id;
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

function makeSortKey($text)

{
  return trim(strtolower(strtr(
			  strtr(utf8_decode(strip_tags($text)),
				//'¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
				'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ',
				'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'),
			  array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
				'¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'))));
}

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

  if ($factory[$table]) return $factory[$table]; // cache

  require_once("dao.php");
  require_once("dao/class.".$table.".php");
  $daoclass=$table."DAO";
  return $factory[$table]=new $daoclass;
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

  return $factory[$table]=new genericDAO ($table,$idfield);
}



// valeur de retour identifier ce script
return 568;

?>
