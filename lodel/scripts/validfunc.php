<?
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




function validfield(&$text,$type,$default="",$name="")

{
  static $tmpdir;
  require_once("fieldfunc.php");
  if ($GLOBALS['lodelfieldtypes'][$type]['autostriptags'] && !is_array($text)) {
    $text=strip_tags($text);
  }

  switch ($type) {
  case "text" :
  case "tinytext" :
  case "longtext" :
    if (!$text) $text=$default;
    return true; // always true
    break;
  case "type" :
    if ($text && !preg_match("/^[a-zA-Z0-9_][a-zA-Z0-9_ -]*$/",$text)) return $type;
    break;
  case "class" :
  case "classtype" :
    $text=strtolower($text);
    if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/",$text)) return $type;
    require_once("fieldfunc.php");
    if (reservedword($text)) return "reservedsql";
    break;
  case "tablefield" :
    $text=strtolower($text);
    if (!preg_match("/^[a-z0-9]{2,}$/",$text)) return $type;
    require_once("fieldfunc.php");
    if (reservedword($text)) return "reservedsql";
    break;
    if ($text && !preg_match("/^[a-zA-Z0-9]+$/",$text)) return $type;
    break;
  case "mlstyle" :
    $text=strtolower($text);
    $stylesarr=preg_split("/[\n,;]/",$text);
    foreach($stylesarr as $style) {
      $style=trim($style);
      if ($style && !preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?\s*(:\s*([a-zA-Z]{2}|--))?$/",$style)) return $type;
    }
    break;
  case "style" :
    if ($text) {
      $text=strtolower($text);
      $stylesarr=preg_split("/[\n,;]/",$text);
      foreach($stylesarr as $style) {
	if (!preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?$/",trim($style))) return $type;
      }
    }
    break;
  case "passwd" :
  case "username" :
    if ($text) {
      $len=strlen($text);
      if ($len<3 || $len>12 || !preg_match("/^[0-9A-Za-z_;.?!@:,&]+$/",$text)) return $type;
    }
    break;
  case "lang" :
    if ($text && !preg_match("/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/",$text)) return $type;
    break;
  case "date" :
  case "datetime" :
  case "time" :
    require_once("date.php");
    if ($text) {
      $text=mysqldatetime($text,$type);
      if (!$text) return $type;
    } elseif ($default) {
      $dt=mysqldatetime($default,$type);
      if ($dt) {
	$text=$dt;
      } else {
	die("ERROR: default value not a date or time: \"$default\"");
      }
    }
    break;
  case "int" :
    if ((!isset($text) || $text==="") && $default!=="") $text=intval($default);
    if (isset($text) && (!is_numeric($text) || intval($text)!=$text)) return "int";
    break;
  case "number" : 
    if ((!isset($text) || $text==="") && $default!=="") $text=doubleval($default);
    if (isset($text) && !is_numeric($text)) return "numeric";
    break;
  case "email" : 
    
    if (!$text && $default) $text=$default;
    if ($text) {
     /* $validchar='-0-9A-Z_a-z';
      if (!preg_match("/^[$validchar]+@([$validchar]+\.)+[$validchar]+$/",$text)) return "email";*/
      if(!_validEmail($text))
   			return 'email';
 
    }
    break;
  case "url" : 
    if (!$text && $default) $text=$default;
    if ($text) 
    {
    	if(!_validUrl($text))
    		return "url";
    	
    /*  $validchar='-0-9A-Z_a-z';
      if (!preg_match("/^(http|ftp):\/\/([$validchar]+\.)+[$validchar]+/",$text)) return "url";*/
    }
    break;
  case "boolean" :
    $text=$text ? 1 : 0;
    break;
  case "tplfile" :
    $text=trim($text); // should be done elsewhere but to be sure...
    if (strpos($text,"/")!==false || $text[0]==".") return "tplfile";
    break;
  case "color" :
    if ($text && !preg_match("/^#[A-Fa-f0-9]{3,6}$/",$text)) return "color";
    break;
  case "entity" :
    $text=intval($text);
    // check it exists
    $dao=&getDAO("entities");
    $vo=$dao->getById($text,"1");
    if (!$vo) return "entity";
    break;
  case "textgroups" :
    return $text=="site" || $text=="interface";
    break;
  case "select" :
  case "multipleselect" :
    return true; // cannot validate
  case "mltext" :
    if (is_array($text)) {
      $str="";
      foreach($text as $lang=>$v) {	       
	if ($lang!="empty" && $v) $str.="<r2r:ml lang=\"".$lang."\">$v</r2r:ml>";
      }
      $text=$str;
    }
    return true;
  case 'list':
    return true;
  case 'image' :  	
  case 'file' :
    if (!is_array($text)) { unset($text); return true; }
    if (!$name) trigger_error("ERROR: \$name is not set in validfunc.php",E_USER_ERROR);
    switch($text['radio']) {
    case 'upload':
      // let's upload
      $files=&$_FILES[$name];
      // look for an error ?
      if (!$files || $files['error']['upload'] ||
	  !$files['tmp_name']['upload'] || $files['tmp_name']['upload']=="none") { 
	unset($text); 
	return "upload";
      }
      // check if the tmpdir is defined
      if (!$tmpdir[$type]) { 
	// look for a unique dirname.
	do {  $tmpdir[$type]="docannexe/$type/tmpdir-".rand();  } while (file_exists(SITEROOT.$tmpdir[$type]));
      }
      // let's transfer
      $text=save_annex_file($type,$tmpdir[$type],$files['tmp_name']['upload'],
			     $files['name']['upload'],true,true,$err);
      if ($err) return $err;
      return true;
    case 'serverfile':
      // check if the tmpdir is defined
      if (!$tmpdir[$type]) { 
	// look for a unique dirname.
	do {  $tmpdir[$type]="docannexe/$type/tmpdir-".rand();  } while (file_exists(SITEROOT.$tmpdir[$type]));
      }
      
      // let's move
      $text=basename($text['localfilename']);
      $text=save_annex_file($type,$tmpdir[$type],SITEROOT."CACHE/upload/$text",
			    $text,false,false,$err);
      if ($err) return $err;
      return true;
    case 'delete':
      $filetodelete=true;
    case '' :
      // validate	     
      $text=$text['previousvalue'];
      if (!$text) return true;
      if (!preg_match("/^docannexe\/(image|file)\/[^\.\/]+\/[^\/]+$/",$text)) {
	die("ERROR: invalid filename of type $type");
      }
      if ($filetodelete) { unlink(SITEROOT.$text); $text=""; unset($filetodelete);}
      return true;
    default:
      die("ERROR: unknow radio value for $name");
    } // switch
  default:
    return false; // pas de validation
  }

  return true; // validated
}

/**
 * Function that validate an email adresses
 * Check if the domain exists and if the username is valid
 * Thanks to Alejandro Gervasio and Anonymous Loozah for the article and the code :
 * original source code :http://www.devshed.com/showblog/7775/Email-Address-Verification-with-PHP
 * @param $email email adress to validate
 * 
 */

function _validEmail($email)
{
	  // checks proper syntax
	//timeout  
	$timeout = intval(50*(ini_get("max_execution_time")/100));
	if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/" , $email))
	return false;

	// gets domain name
	list($username,$domain)=split('@',$email);
	// checks for if MX records in the DNS
	$mxhosts = array();
	if(!getmxrr($domain, $mxhosts)) 
	{
		// no mx records, ok to check domain
		if (!@fsockopen($domain,25,$errno,$errstr,$timeout)) 
			return false;
		else 
			return true;
	} 
	else 
	{
		// mx records found
		foreach ($mxhosts as $host) 
			if (@fsockopen($host,25,$errno,$errstr,$timeout)) 
				return true;
		return false;
	}
}

/**
 * Function that validate an url
 * Parse the url to check the scheme and the host.
 * 
 * @param $url Internet adress to be validated
 */

function _validUrl($url)
{
	$parsedurl = parse_url($url);
	#print_r($parsedurl);
	if(!preg_match("/^(http|ftp|https|file|gopher|telnet|nntp|news)$/i",@$parsedurl['scheme'])  || empty($parsedurl['host']))
		return false;
	else
	{
		if(function_exists('checkdnsrr')) // the function checkdnsrr doest not exists on Windows plateforms
		{
			if(checkdnsrr($parsedurl['host'], 'A'))
				return true;
			else
				return false;
		}
		else
			return true;
	}

}
?>
