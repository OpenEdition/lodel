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

  /* Load and desarchive Lodel */

if (file_exists("lodelconfig.php")) { msg_error("Lodel est déjà installé dans ce répertoire. Si vous souhaitez refaire une installation, effacez tous les fichiers de ce répertoire mise a part le fichier lodelloader.php et lancer à nouveau l'execution de ce script dans votre navigateur."); }

//
// Test if gzopen exists.
// Currently gzopen is require since we use pclzip to decompress the archive
// A private archive format could be used in the future.
//

if (!function_exists("gzopen")) {
  msg_error("La librairie zlib n'a pu être trouvée sur votre serveur. Ce script d'installation automatique ne peut donc pas poursuivre. Pour installer Lodel, veuillez télécharger Lodel, décompresser l'archive puis lancer votre navigateur sur lodeladmin/install.php");
}


//
// Introduction message
//

if (!$_REQUEST['install']) {
  msg_intro();
}

//
// Check whether we are allowed to communicate with the outside
//

$hassocket=function_exists("fsockopen");

$chmod=$_REQUEST['chmod'];

if (!$chmod) {
  //
  // Test whether the server as write right on the current directory
  //
  $testfile="testlodelinstall.php";
  @unlink($testfile);#
  $fp=@fopen($testfile,"w");
  if (!$fp || !@fwrite($fp,'<?php echo "hello"; ?>')) msg_error("Le serveur web n'a pas les droit d'écriture sur ce répertoire. Veuillez avec votre logiciel ftp donner les droits d'écriture sur ce répertoire et relancer l'éxecution de ce script (bouton \"recharger\" sur votre navigateur)");

  $chmod=guessfilemask();

  // now, check we can acces with such a mode

  $reducedchmod=false;
  if ($hassocket) {
    do {
      @chmod($testfile,$chmod);
      $url="http://".$_SERVER['SERVER_NAME'].preg_replace("/[^\/]+$/","",$_SERVER['REQUEST_URI']).$testfile;
      $client=new Snoopy;
      $client->read_timeout = 30;
      $client->fetch($url);
      $succes=true;
      if ($client->status==500) { // internal server error
	// strict restriction of the write right
	if ($chmod!=($chmod & 0755)) {
	  $chmod&=0755;
	  $reducedchmod=true;
	  // let's try again
	} else {
	  $succes=false; break;
	}
      } elseif ($client->status==200) { 
	break;
      } else { // another status... likely bad
	$succes=false; break;
      }
      
    } while (1);
    unset($client);
  }

  msg_chmod($chmod,$reducedchmod);
}
@unlink($testfile);

//
// Download Lodel
//

$archiveurl="http://www.lodel.org/download/lodel-latest.zip";
$archivefile="lodel.zip";
if (!file_exists($archivefile)) {
  if (!$hassocket) msg_error("La configuration de votre serveur ne permet le téléchargement automatique de Lodel. Veuillez télécharger l'archive Lodel et la poser sur le serveur dans le même répertoire que ce script en la renomant \"lodel.zip\". Relancez ensuite ce script.");
  $fpwrite=fopen($archivefile,"w");
  $client=new Snoopy;
  $client->read_timeout = 600;
  $client->fetch($archiveurl,$fpwrite);
  
  if ($client->status!=200) {
    fclose($fpwrite);
    @unlink($archivefile);
    msg_error("Le téléchargement du fichier $archiveurl n'a pas fonctionné. L'erreur produite est: ".$client->response_code."\<br>Si vous ne pouvez résoudre cette erreur, veuillez télécharger l'archive Lodel et la poser sur le serveur dans le même répertoire que ce script en la renomant \"lodel.zip\". Relancez ensuite ce script d'installation automatique.");
  }
  fclose($fpwrite);
}
unset($client);

//
// Desarchive
//

pclzip_include();
$ziparchive=new PclZip($archivefile);
$ziparchive->extract(PCLZIP_OPT_REMOVE_PATH,"lodel",PCLZIP_CB_POST_EXTRACT,"setchmod");

function setchmod ($p_event,&$p_header) {
  global $chmod;
  chmod ($p_header['filename'],$chmod & ($p_header['folder'] ? 0777 : 0666));
  return 1;
}

//
// lance sur l'installation
//

header("Location: lodeladmin/install.php?option1=1&tache=plateform&filemask=".decoct($chmod));


//
// function to guess the file permissions
//

function guessfilemask() {
  //
  // Guess the correct filemask setting
  // (code from SPIP)

  $self = basename($_SERVER['PHP_SELF']);
  $uid_dir = @fileowner('.');
  $uid_self = @fileowner($self);
  $gid_dir = @filegroup('.');
  $gid_self = @filegroup($self);
  $perms_self = @fileperms($self);

  // Compare the ownership and groupship of the directory, the installer script and 
  // the file created by php.

  if ($uid_dir > 0 && $uid_dir == $uid_self && @fileowner($testfile) == $uid_dir)
    $chmod = 0700;
  else if ($gid_dir > 0 && $gid_dir == $gid2_self && @filegroup($testfile) == $gid_dir)
    $chmod = 0770;
  else
    $chmod = 0777;

  // Add the same read and executation rights as the installer script has.
  if ($perms_self > 0) {
    // add the execution right where there is read right
    $perms_self = ($perms_self & 0777) | (($perms_self & 0444) >> 2); 
    $chmod |= $perms_self;
  }

  return $chmod;
}



/**********************************************************************************/
/**********************************************************************************/
/**********************************************************************************/
/**********************************************************************************/

/************************************************
 Snoopy - the PHP net client
 Author: Monte Ohrt <monte@ispi.net>
 Copyright (c): 1999-2000 ispi, all rights reserved
 Version: 1.01

 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 You may contact the author of Snoopy by e-mail at:
 monte@ispi.net

 Or, write to:
 Monte Ohrt
 CTO, ispi
 237 S. 70th suite 220
 Lincoln, NE 68510

 The latest version of Snoopy can be obtained from:
 http://snoopy.sourceforge.com

*************************************************/

class Snoopy
{
  /**** Public variables ****/
	
  /* user definable vars */

  // hack by Ghislain
  var $fpwrite; // write the output to a file rather than to the memory

  var $host			=	"www.php.net";		// host name we are connecting to
  var $port			=	80;					// port we are connecting to
  var $proxy_host		=	"";					// proxy host to use
  var $proxy_port		=	"";					// proxy port to use
  var $agent			=	"Snoopy v1.01";		// agent we masquerade as
  var	$referer		=	"";					// referer info to pass
  var $cookies		=	array();			// array of cookies to pass
  // $cookies["username"]="joe";
  var	$rawheaders		=	array();			// array of raw headers to send
  // $rawheaders["Content-type"]="text/html";

  var $maxredirs		=	5;					// http redirection depth maximum. 0 = disallow
  var $lastredirectaddr	=	"";				// contains address of last redirected address
  var	$offsiteok		=	true;				// allows redirection off-site
  var $maxframes		=	0;					// frame content depth maximum. 0 = disallow
  var $expandlinks	=	true;				// expand links to fully qualified URLs.
  // this only applies to fetchlinks()
  // or submitlinks()
  var $passcookies	=	true;				// pass set cookies back through redirects
  // NOTE: this currently does not respect
  // dates, domains or paths.
	
  var	$user			=	"";					// user for http authentication
  var	$pass			=	"";					// password for http authentication
	
  // http accept types
  var $accept			=	"image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*";
	
  var $results		=	"";					// where the content is put
		
  var $error			=	"";					// error messages sent here
  var	$response_code	=	"";					// response code returned from server
  var	$headers		=	array();			// headers returned from server sent here
  var	$maxlength		=	500000;				// max return data length (body)
  var $read_timeout	=	0;					// timeout on read operations, in seconds
  // supported only since PHP 4 Beta 4
  // set to 0 to disallow timeouts
  var $timed_out		=	false;				// if a read operation timed out
  var	$status			=	0;					// http request status
	
  var	$curl_path		=	"/usr/local/bin/curl";
  // Snoopy will use cURL for fetching
  // SSL content if a full system path to
  // the cURL binary is supplied here.
  // set to false if you do not have
  // cURL installed. See http://curl.haxx.se
  // for details on installing cURL.
  // Snoopy does *not* use the cURL
  // library functions built into php,
  // as these functions are not stable
  // as of this Snoopy release.
	
  /**** Private variables ****/	
	
  var	$_maxlinelen	=	4096;				// max line length (headers)
	
  var $_httpmethod	=	"GET";				// default http request method
  var $_httpversion	=	"HTTP/1.0";			// default http request version
  var $_submit_method	=	"POST";				// default submit method
  var $_submit_type	=	"application/x-www-form-urlencoded";	// default submit type
  var $_mime_boundary	=   "";					// MIME boundary for multipart/form-data submit type
  var $_redirectaddr	=	false;				// will be set if page fetched is a redirect
  var $_redirectdepth	=	0;					// increments on an http redirect
  var $_frameurls		= 	array();			// frame src urls
  var $_framedepth	=	0;					// increments on frame depth
	
  var $_isproxy		=	false;				// set if using a proxy server
  var $_fp_timeout	=	30;					// timeout for socket connection

  /*======================================================================*\
   Function:	fetch
   Purpose:	fetch the contents of a web page
   (and possibly other protocols in the
   future like ftp, nntp, gopher, etc.)
   Input:		$URI	the location of the page to fetch
   Output:		$this->results	the output text from the fetch
   \*======================================================================*/

  function fetch($URI,$fpwrite=0)
  {
	
    if ($fpwrite) $this->fpwrite=$fpwrite;

    //preg_match("|^([^:]+)://([^:/]+)(:[\d]+)*(.*)|",$URI,$URI_PARTS);
    $URI_PARTS = parse_url($URI);
    if (!empty($URI_PARTS["user"]))
      $this->user = $URI_PARTS["user"];
    if (!empty($URI_PARTS["pass"]))
      $this->pass = $URI_PARTS["pass"];
			
    switch($URI_PARTS["scheme"])
      {
      case "http":
	$this->host = $URI_PARTS["host"];
	if(!empty($URI_PARTS["port"]))
	  $this->port = $URI_PARTS["port"];
	if($this->_connect($fp))
	  {
	    if($this->_isproxy)
	      {
		// using proxy, send entire URI
		$this->_httprequest($URI,$fp,$URI,$this->_httpmethod);
	      }
	    else
	      {
		$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
		// no proxy, send only the path
		$this->_httprequest($path, $fp, $URI, $this->_httpmethod);
	      }
					
	    $this->_disconnect($fp);

	    if($this->_redirectaddr)
	      {
		/* url was redirected, check if we've hit the max depth */
		if($this->maxredirs > $this->_redirectdepth)
		  {
		    // only follow redirect if it's on this site, or offsiteok is true
		    if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
		      {
			/* follow the redirect */
			$this->_redirectdepth++;
			$this->lastredirectaddr=$this->_redirectaddr;
			$this->fetch($this->_redirectaddr);
		      }
		  }
	      }

	    if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
	      {
		$frameurls = $this->_frameurls;
		$this->_frameurls = array();
						
		while(list(,$frameurl) = each($frameurls))
		  {
		    if($this->_framedepth < $this->maxframes)
		      {
			$this->fetch($frameurl);
			$this->_framedepth++;
		      }
		    else
		      break;
		  }
	      }					
	  }
	else
	  {
	    return false;
	  }
	return true;					
	break;
      default:
	// not a valid protocol
	$this->error	=	'protocol not supported "'.$URI_PARTS["scheme"].'"\n';
	return false;
	break;
      }		
    return true;
  }


  /*======================================================================*\
   Private functions
   \*======================================================================*/
	
	
  /*======================================================================*\
   Function:	_expandlinks
   Purpose:	expand each link into a fully qualified URL
   Input:		$links			the links to qualify
   $URI			the full URI to get the base from
   Output:		$expandedLinks	the expanded links
   \*======================================================================*/

  function _expandlinks($links,$URI)
  {
		
    preg_match("/^[^\?]+/",$URI,$match);

    $match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|","",$match[0]);
				
    $search = array( 	"|^http://".preg_quote($this->host)."|i",
			"|^(?!http://)(\/)?(?!mailto:)|i",
			"|/\./|",
			"|/[^\/]+/\.\./|"
			);
						
    $replace = array(	"",
			$match."/",
			"/",
			"/"
			);			
				
    $expandedLinks = preg_replace($search,$replace,$links);

    return $expandedLinks;
  }

  /*======================================================================*\
   Function:	_httprequest
   Purpose:	go get the http data from the server
   Input:		$url		the url to fetch
   $fp			the current open file pointer
   $URI		the full URI
   $body		body contents to send if any (POST)
   Output:		
   \*======================================================================*/
	
  function _httprequest($url,$fp,$URI,$http_method,$content_type="",$body="")
  {
    $cookie_headers = '';
    if($this->passcookies && $this->_redirectaddr)
      $this->setcookies();
			
    $URI_PARTS = parse_url($URI);
    if(empty($url))
      $url = "/";
    $headers = $http_method." ".$url." ".$this->_httpversion."\r\n";		
    if(!empty($this->agent))
      $headers .= "User-Agent: ".$this->agent."\r\n";
    if(!empty($this->host) && !isset($this->rawheaders['Host']))
      $headers .= "Host: ".$this->host."\r\n";
    if(!empty($this->accept))
      $headers .= "Accept: ".$this->accept."\r\n";
    if(!empty($this->referer))
      $headers .= "Referer: ".$this->referer."\r\n";
    if(!empty($this->cookies))
      {			
	if(!is_array($this->cookies))
	  $this->cookies = (array)$this->cookies;
	
	reset($this->cookies);
	if ( count($this->cookies) > 0 ) {
	  $cookie_headers .= 'Cookie: ';
	  foreach ( $this->cookies as $cookieKey => $cookieVal ) {
	    $cookie_headers .= $cookieKey."=".urlencode($cookieVal)."; ";
	  }
	  $headers .= substr($cookie_headers,0,-2) . "\r\n";
	} 
      }
    if(!empty($this->rawheaders))
      {
	if(!is_array($this->rawheaders))
	  $this->rawheaders = (array)$this->rawheaders;
	while(list($headerKey,$headerVal) = each($this->rawheaders))
	  $headers .= $headerKey.": ".$headerVal."\r\n";
      }
    if(!empty($content_type)) {
      $headers .= "Content-type: $content_type";
      if ($content_type == "multipart/form-data")
	$headers .= "; boundary=".$this->_mime_boundary;
      $headers .= "\r\n";
    }
    if(!empty($body))	
      $headers .= "Content-length: ".strlen($body)."\r\n";
    if(!empty($this->user) || !empty($this->pass))	
      $headers .= "Authorization: Basic ".base64_encode($this->user.":".$this->pass)."\r\n";

    $headers .= "\r\n";
		
    // set the read timeout if needed
    if ($this->read_timeout > 0)
      socket_set_timeout($fp, $this->read_timeout);
    $this->timed_out = false;
		
    fwrite($fp,$headers.$body,strlen($headers.$body));
		
    $this->_redirectaddr = false;
    unset($this->headers);
						
    while($currentHeader = fgets($fp,$this->_maxlinelen))
      {
	if ($this->read_timeout > 0 && $this->_check_timeout($fp))
	  {
	    $this->status=-100;
	    return false;
	  }
				
	if($currentHeader == "\r\n")
	  break;
						
	// if a header begins with Location: or URI:, set the redirect
	if(preg_match("/^(Location:|URI:)/i",$currentHeader))
	  {
	    // get URL portion of the redirect
	    preg_match("/^(Location:|URI:)[ ]+(.*)/",chop($currentHeader),$matches);
	    // look for :// in the Location header to see if hostname is included
	    if(!preg_match("|\:\/\/|",$matches[2]))
	      {
		// no host in the path, so prepend
		$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
		// eliminate double slash
		if(!preg_match("|^/|",$matches[2]))
		  $this->_redirectaddr .= "/".$matches[2];
		else
		  $this->_redirectaddr .= $matches[2];
	      }
	    else
	      $this->_redirectaddr = $matches[2];
	  }
		
	if(preg_match("|^HTTP/|",$currentHeader))
	  {      if(preg_match("|^HTTP/[^\s]*\s(.*?)\s|",$currentHeader, $status))
	      {
		$this->status= $status[1];      }				
	    $this->response_code = $currentHeader;
	  }
				
	$this->headers[] = $currentHeader;
      }

    $results = '';
    do {
      $_data = fread($fp, $this->maxlength);
      if (strlen($_data) == 0) {
	break;
      }
      // hack by Ghislain
      if ($this->fpwrite) {
	fwrite($this->fpwrite,$_data);
      } else {
	$results .= $_data;
      }
      //
    } while(true);

    if ($this->read_timeout > 0 && $this->_check_timeout($fp))
      {
	$this->status=-100;
	return false;
      }
		
    // check if there is a a redirect meta tag
		
    if(preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]+URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))
      {
	$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
      }

    // have we hit our frame depth and is there frame src to fetch?
    if(($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
      {
	$this->results[] = $results;
	for($x=0; $x<count($match[1]); $x++)
	  $this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);
      }
    // have we already fetched framed content?
    elseif(is_array($this->results))
      $this->results[] = $results;
    // no framed content
    else
      $this->results = $results;
		
    return true;
  }


	
  /*======================================================================*\
   Function:	_check_timeout
   Purpose:	checks whether timeout has occurred
   Input:		$fp	file pointer
   \*======================================================================*/

  function _check_timeout($fp)
  {
    if ($this->read_timeout > 0) {
      $fp_status = socket_get_status($fp);
      if ($fp_status["timed_out"]) {
	$this->timed_out = true;
	return true;
      }
    }
    return false;
  }

  /*======================================================================*\
   Function:	_connect
   Purpose:	make a socket connection
   Input:		$fp	file pointer
   \*======================================================================*/
	
  function _connect(&$fp)
  {
    if(!empty($this->proxy_host) && !empty($this->proxy_port))
      {
	$this->_isproxy = true;
	$host = $this->proxy_host;
	$port = $this->proxy_port;
      }
    else
      {
	$host = $this->host;
	$port = $this->port;
      }
	
    $this->status = 0;
		
    if($fp = fsockopen(
		       $host,
		       $port,
		       $errno,
		       $errstr,
		       $this->_fp_timeout
		       ))
      {
	// socket connection succeeded

	return true;
      }
    else
      {
	// socket connection failed
	$this->status = $errno;
	switch($errno)
	  {
	  case -3:
	    $this->error="socket creation failed (-3)";
	  case -4:
	    $this->error="dns lookup failure (-4)";
	  case -5:
	    $this->error="connection refused or timed out (-5)";
	  default:
	    $this->error="connection failed (".$errno.")";
	  }
	return false;
      }
  }
  /*======================================================================*\
   Function:	_disconnect
   Purpose:	disconnect a socket connection
   Input:		$fp	file pointer
   \*======================================================================*/
	
  function _disconnect($fp)
  {
    return(fclose($fp));
  }

} // end of Snoopy class

/**********************************************************************************/
/**********************************************************************************/
/**********************************************************************************/
/**********************************************************************************/

// --------------------------------------------------------------------------------
// PhpConcept Library - Zip Module 2.1
// --------------------------------------------------------------------------------
// License GNU/LGPL - Vincent Blavet - December 2003
// http://www.phpconcept.net
// --------------------------------------------------------------------------------
//
// Presentation :
//   PclZip is a PHP library that manage ZIP archives.
//   So far tests show that archives generated by PclZip are readable by
//   WinZip application and other tools.
//
// Description :
//   See readme.txt and http://www.phpconcept.net
//
// Warning :
//   This library and the associated files are non commercial, non professional
//   work.
//   It should not have unexpected results. However if any damage is caused by
//   this software the author can not be responsible.
//   The use of this software is at the risk of the user.
//
// --------------------------------------------------------------------------------
// $Id$
// --------------------------------------------------------------------------------

function pclzip_include() {

  define( 'PCLZIP_READ_BLOCK_SIZE', 2048 );
 
  define( 'PCLZIP_SEPARATOR', ',' );
  define( 'PCLZIP_ERROR_EXTERNAL', 0 );
  define( 'PCLZIP_TEMPORARY_DIR', '' );


  $GLOBALS[g_pclzip_version] = "2.1";
  define( 'PCLZIP_ERR_USER_ABORTED', 2 );
  define( 'PCLZIP_ERR_NO_ERROR', 0 );
  define( 'PCLZIP_ERR_WRITE_OPEN_FAIL', -1 );
  define( 'PCLZIP_ERR_READ_OPEN_FAIL', -2 );
  define( 'PCLZIP_ERR_INVALID_PARAMETER', -3 );
  define( 'PCLZIP_ERR_MISSING_FILE', -4 );
  define( 'PCLZIP_ERR_FILENAME_TOO_LONG', -5 );
  define( 'PCLZIP_ERR_INVALID_ZIP', -6 );
  define( 'PCLZIP_ERR_BAD_EXTRACTED_FILE', -7 );
  define( 'PCLZIP_ERR_DIR_CREATE_FAIL', -8 );
  define( 'PCLZIP_ERR_BAD_EXTENSION', -9 );
  define( 'PCLZIP_ERR_BAD_FORMAT', -10 );
  define( 'PCLZIP_ERR_DELETE_FILE_FAIL', -11 );
  define( 'PCLZIP_ERR_RENAME_FILE_FAIL', -12 );
  define( 'PCLZIP_ERR_BAD_CHECKSUM', -13 );
  define( 'PCLZIP_ERR_INVALID_ARCHIVE_ZIP', -14 );
  define( 'PCLZIP_ERR_MISSING_OPTION_VALUE', -15 );
  define( 'PCLZIP_ERR_INVALID_OPTION_VALUE', -16 );

  define( 'PCLZIP_OPT_PATH', 77001 );
  define( 'PCLZIP_OPT_ADD_PATH', 77002 );
  define( 'PCLZIP_OPT_REMOVE_PATH', 77003 );
  define( 'PCLZIP_OPT_REMOVE_ALL_PATH', 77004 );
  define( 'PCLZIP_OPT_SET_CHMOD', 77005 );
  define( 'PCLZIP_OPT_EXTRACT_AS_STRING', 77006 );
  define( 'PCLZIP_OPT_NO_COMPRESSION', 77007 );
  define( 'PCLZIP_OPT_BY_NAME', 77008 );
  define( 'PCLZIP_OPT_BY_INDEX', 77009 );
  define( 'PCLZIP_OPT_BY_EREG', 77010 );
  define( 'PCLZIP_OPT_BY_PREG', 77011 );
  define( 'PCLZIP_OPT_COMMENT', 77012 );
  define( 'PCLZIP_OPT_ADD_COMMENT', 77013 );
  define( 'PCLZIP_OPT_PREPEND_COMMENT', 77014 );
  define( 'PCLZIP_OPT_EXTRACT_IN_OUTPUT', 77015 );

  define( 'PCLZIP_CB_PRE_EXTRACT', 78001 );
  define( 'PCLZIP_CB_POST_EXTRACT', 78002 );
  define( 'PCLZIP_CB_PRE_ADD', 78003 );
  define( 'PCLZIP_CB_POST_ADD', 78004 );
  /* For futur use
   define( 'PCLZIP_CB_PRE_LIST', 78005 );
   define( 'PCLZIP_CB_POST_LIST', 78006 );
   define( 'PCLZIP_CB_PRE_DELETE', 78007 );
   define( 'PCLZIP_CB_POST_DELETE', 78008 );
  */

}                    class PclZip
{
  var $zipname = '';

  var $zip_fd = 0;

  var $error_code = 1;
  var $error_string = '';
  function PclZip($p_zipname)
  {
    
    if (!function_exists('gzopen'))
      {  die('Abort '.basename(__FILE__).' : Missing zlib extensions');
      }

    $this->zipname = $p_zipname;
    $this->zip_fd = 0;
    return;
  }
  
  function extract(/* options */)
  {
    $v_result=1;

    $this->privErrorReset();

    if (!$this->privCheckFormat()) {  return(0);
    }

    $v_options = array();
    $v_path = "./";
    $v_remove_path = "";
    $v_remove_all_path = false;

    $v_size = func_num_args();
    
    $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;

    if ($v_size > 0) {  $v_arg_list = &func_get_args();
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {
	$v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,                                  array (PCLZIP_OPT_PATH => 'optional',                                         PCLZIP_OPT_REMOVE_PATH => 'optional',                                         PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',                                         PCLZIP_OPT_ADD_PATH => 'optional',                                         PCLZIP_CB_PRE_EXTRACT => 'optional',                                         PCLZIP_CB_POST_EXTRACT => 'optional',                                         PCLZIP_OPT_SET_CHMOD => 'optional',                                         PCLZIP_OPT_BY_NAME => 'optional',                                         PCLZIP_OPT_BY_EREG => 'optional',                                         PCLZIP_OPT_BY_PREG => 'optional',                                         PCLZIP_OPT_BY_INDEX => 'optional',                                         PCLZIP_OPT_EXTRACT_AS_STRING => 'optional',                                         PCLZIP_OPT_EXTRACT_IN_OUTPUT => 'optional' ));
        if ($v_result != 1) {          return 0;
        }
	if (isset($v_options[PCLZIP_OPT_PATH])) {$v_path = $v_options[PCLZIP_OPT_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_PATH])) {$v_remove_path = $v_options[PCLZIP_OPT_REMOVE_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {$v_remove_all_path = $v_options[PCLZIP_OPT_REMOVE_ALL_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_ADD_PATH])) {          if ((strlen($v_path) > 0) && (substr($v_path, -1) != '/')) {  $v_path .= '/';}$v_path .= $v_options[PCLZIP_OPT_ADD_PATH];
        }
      }
      else {
	$v_path = $v_arg_list[0];
	if ($v_size == 2) {$v_remove_path = $v_arg_list[1];
        }
        else if ($v_size > 2) {          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");
	  return 0;
        }
      }
    }

        
    $p_list = array();
    $v_result = $this->privExtractByRule($p_list, $v_path, $v_remove_path,
					 $v_remove_all_path, $v_options);
    if ($v_result < 1) {
      unset($p_list);  return(0);
    }
    return $p_list;
  }
  function errorCode()
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      return(PclErrorCode());
    }
    else {
      return($this->error_code);
    }
  }
  function errorName($p_with_code=false)
  {
    $v_name = array ( PCLZIP_ERR_NO_ERROR => 'PCLZIP_ERR_NO_ERROR',            PCLZIP_ERR_WRITE_OPEN_FAIL => 'PCLZIP_ERR_WRITE_OPEN_FAIL',            PCLZIP_ERR_READ_OPEN_FAIL => 'PCLZIP_ERR_READ_OPEN_FAIL',            PCLZIP_ERR_INVALID_PARAMETER => 'PCLZIP_ERR_INVALID_PARAMETER',            PCLZIP_ERR_MISSING_FILE => 'PCLZIP_ERR_MISSING_FILE',            PCLZIP_ERR_FILENAME_TOO_LONG => 'PCLZIP_ERR_FILENAME_TOO_LONG',            PCLZIP_ERR_INVALID_ZIP => 'PCLZIP_ERR_INVALID_ZIP',            PCLZIP_ERR_BAD_EXTRACTED_FILE => 'PCLZIP_ERR_BAD_EXTRACTED_FILE',            PCLZIP_ERR_DIR_CREATE_FAIL => 'PCLZIP_ERR_DIR_CREATE_FAIL',            PCLZIP_ERR_BAD_EXTENSION => 'PCLZIP_ERR_BAD_EXTENSION',            PCLZIP_ERR_BAD_FORMAT => 'PCLZIP_ERR_BAD_FORMAT',            PCLZIP_ERR_DELETE_FILE_FAIL => 'PCLZIP_ERR_DELETE_FILE_FAIL',            PCLZIP_ERR_RENAME_FILE_FAIL => 'PCLZIP_ERR_RENAME_FILE_FAIL',            PCLZIP_ERR_BAD_CHECKSUM => 'PCLZIP_ERR_BAD_CHECKSUM',            PCLZIP_ERR_INVALID_ARCHIVE_ZIP => 'PCLZIP_ERR_INVALID_ARCHIVE_ZIP',            PCLZIP_ERR_MISSING_OPTION_VALUE => 'PCLZIP_ERR_MISSING_OPTION_VALUE',            PCLZIP_ERR_INVALID_OPTION_VALUE => 'PCLZIP_ERR_INVALID_OPTION_VALUE' );

    if (isset($v_name[$this->error_code])) {
      $v_value = $v_name[$this->error_code];
    }
    else {
      $v_value = 'NoName';
    }

    if ($p_with_code) {
      return($v_value.' ('.$this->error_code.')');
    }
    else {
      return($v_value);
    }
  }
  function errorInfo($p_full=false)
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      return(PclErrorString());
    }
    else {
      if ($p_full) {
        return($this->errorName(true)." : ".$this->error_string);
      }
      else {
        return($this->error_string." [code ".$this->error_code."]");
      }
    }
  }
  



  function privCheckFormat($p_level=0)
  {
    $v_result = true;

    clearstatcache();

    $this->privErrorReset();

    if (!is_file($this->zipname)) {  PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "Missing archive file '".$this->zipname."'");  return(false);
    }

    if (!is_readable($this->zipname)) {  PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to read archive '".$this->zipname."'");  return(false);
    }

        
        
    return $v_result;
  }
  function privParseOptions(&$p_options_list, $p_size, &$v_result_list, $v_requested_options=false)
  {
    $v_result=1;

    $i=0;
    while ($i<$p_size) {
      if (!isset($v_requested_options[$p_options_list[$i]])) {      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid optional parameter '".$p_options_list[$i]."' for this method");
	return PclZip::errorCode();
      }
      switch ($p_options_list[$i]) {      case PCLZIP_OPT_PATH :
      case PCLZIP_OPT_REMOVE_PATH :
      case PCLZIP_OPT_ADD_PATH :          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	$v_result_list[$p_options_list[$i]] = PclZipUtilTranslateWinPath($p_options_list[$i+1], false);          $i++;
        break;
      case PCLZIP_OPT_BY_NAME :          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	if (is_string($p_options_list[$i+1])) {    $v_result_list[$p_options_list[$i]][0] = $p_options_list[$i+1];}else if (is_array($p_options_list[$i+1])) {    $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];}else {              PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Wrong parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}          $i++;
        break;
      case PCLZIP_OPT_BY_EREG :
      case PCLZIP_OPT_BY_PREG :          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	if (is_string($p_options_list[$i+1])) {    $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];}else {              PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Wrong parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}          $i++;
        break;
      case PCLZIP_OPT_COMMENT :
      case PCLZIP_OPT_ADD_COMMENT :
      case PCLZIP_OPT_PREPEND_COMMENT :          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE,
													    "Missing parameter value for option '"
													    .PclZipUtilOptionText($p_options_list[$i])
													    ."'");
	  return PclZip::errorCode();}
	if (is_string($p_options_list[$i+1])) {    $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];}else {              PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE,
																			 "Wrong parameter value for option '"
																			 .PclZipUtilOptionText($p_options_list[$i])
																			 ."'");
	  return PclZip::errorCode();}          $i++;
        break;
      case PCLZIP_OPT_BY_INDEX :          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	$v_work_list = array();if (is_string($p_options_list[$i+1])) {                      $p_options_list[$i+1] = strtr($p_options_list[$i+1], ' ', '');
	  $v_work_list = explode(",", $p_options_list[$i+1]);}else if (is_integer($p_options_list[$i+1])) {                  $v_work_list[0] = $p_options_list[$i+1].'-'.$p_options_list[$i+1];}else if (is_array($p_options_list[$i+1])) {                  $v_work_list = $p_options_list[$i+1];}else {              PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Value must be integer, string or array for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}                                        $v_sort_flag=false;$v_sort_value=0;for ($j=0; $j<sizeof($v_work_list); $j++) {                  $v_item_list = explode("-", $v_work_list[$j]);    $v_size_item_list = sizeof($v_item_list);                                                      if ($v_size_item_list == 1) {                          $v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];        $v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[0];    }    elseif ($v_size_item_list == 2) {                          $v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];        $v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[1];    }    else {                          PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Too many values in index range for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	    return PclZip::errorCode();    }
	  if ($v_result_list[$p_options_list[$i]][$j]['start'] < $v_sort_value) {                          $v_sort_flag=true;
	    PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Invalid order of index range for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	    return PclZip::errorCode();    }    $v_sort_value = $v_result_list[$p_options_list[$i]][$j]['start'];}          if ($v_sort_flag) {                            }
	$i++;
        break;
      case PCLZIP_OPT_REMOVE_ALL_PATH :
      case PCLZIP_OPT_EXTRACT_AS_STRING :
      case PCLZIP_OPT_NO_COMPRESSION :
      case PCLZIP_OPT_EXTRACT_IN_OUTPUT :$v_result_list[$p_options_list[$i]] = true;        break;
      case PCLZIP_OPT_SET_CHMOD :          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	$v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];          $i++;
        break;
      case PCLZIP_CB_PRE_EXTRACT :
      case PCLZIP_CB_POST_EXTRACT :
      case PCLZIP_CB_PRE_ADD :
      case PCLZIP_CB_POST_ADD :
        /* for futur use
	 case PCLZIP_CB_PRE_DELETE :
	 case PCLZIP_CB_POST_DELETE :
	 case PCLZIP_CB_PRE_LIST :
	 case PCLZIP_CB_POST_LIST :
        */          if (($i+1) >= $p_size) {              PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	$v_function_name = $p_options_list[$i+1];          if (!function_exists($v_function_name)) {              PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Function '".$v_function_name."()' is not an existing function for option '".PclZipUtilOptionText($p_options_list[$i])."'");
	  return PclZip::errorCode();}
	$v_result_list[$p_options_list[$i]] = $v_function_name;$i++;
        break;

      default :          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
					      "Unknown parameter '"
					      .$p_options_list[$i]."'");
	return PclZip::errorCode();
      }
      $i++;
    }

    if ($v_requested_options !== false) {
      for ($key=reset($v_requested_options); $key=key($v_requested_options); $key=next($v_requested_options)) {      if ($v_requested_options[$key] == 'mandatory') {                    if (!isset($v_result_list[$key])) {              PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Missing mandatory parameter ".PclZipUtilOptionText($key)."(".$key.")");
	    return PclZip::errorCode();}
        }
      }
    }
    return $v_result;
  }
  
  function privOpenFd($p_mode)
  {
    $v_result=1;

    if ($this->zip_fd != 0)
      {  PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Zip file \''.$this->zipname.'\' already open');
        return PclZip::errorCode();
      }
    if (($this->zip_fd = @fopen($this->zipname, $p_mode)) == 0)
      {  PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in '.$p_mode.' mode');
        return PclZip::errorCode();
      }
    return $v_result;
  }
  function privCloseFd()
  {
    $v_result=1;

    if ($this->zip_fd != 0)
      @fclose($this->zip_fd);
    $this->zip_fd = 0;
    return $v_result;
  }
  
  function privConvertHeader2FileInfo($p_header, &$p_info)
  {
    $v_result=1;

    $p_info['filename'] = $p_header['filename'];
    $p_info['stored_filename'] = $p_header['stored_filename'];
    $p_info['size'] = $p_header['size'];
    $p_info['compressed_size'] = $p_header['compressed_size'];
    $p_info['mtime'] = $p_header['mtime'];
    $p_info['comment'] = $p_header['comment'];
    $p_info['folder'] = (($p_header['external']&0x00000010)==0x00000010);
    $p_info['index'] = $p_header['index'];
    $p_info['status'] = $p_header['status'];
    return $v_result;
  }
  function privExtractByRule(&$p_file_list, $p_path, $p_remove_path, $p_remove_all_path, &$p_options)
  {
    $v_result=1;

    if (($p_path == "") || ((substr($p_path, 0, 1) != "/") && (substr($p_path, 0, 3) != "../") && (substr($p_path,1,2)!=":/")))
      $p_path = "./".$p_path;

    if (($p_path != "./") && ($p_path != "/"))
      {  while (substr($p_path, -1) == "/")
	  {      $p_path = substr($p_path, 0, strlen($p_path)-1);    }
      }

    if (($p_remove_path != "") && (substr($p_remove_path, -1) != '/'))
      {
	$p_remove_path .= '/';
      }
    $p_remove_path_size = strlen($p_remove_path);
    if (($v_result = $this->privOpenFd('rb')) != 1)
      {  return $v_result;
      }

    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
      {  $this->privCloseFd();
	return $v_result;
      }

    $v_pos_entry = $v_central_dir['offset'];

    $j_start = 0;
    for ($i=0, $v_nb_extracted=0; $i<$v_central_dir['entries']; $i++)
      {
	@rewind($this->zip_fd);  if (@fseek($this->zip_fd, $v_pos_entry))
				   {      $this->privCloseFd();
				     PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');
				     return PclZip::errorCode();
				   }
        $v_header = array();
	if (($v_result = $this->privReadCentralFileHeader($v_header)) != 1)
	  {      $this->privCloseFd();
	    return $v_result;
	  }
	$v_header['index'] = $i;
	$v_pos_entry = ftell($this->zip_fd);
	$v_extract = false;
	if (   (isset($p_options[PCLZIP_OPT_BY_NAME]))&& ($p_options[PCLZIP_OPT_BY_NAME] != 0)) {          for ($j=0; ($j<sizeof($p_options[PCLZIP_OPT_BY_NAME])) && (!$v_extract); $j++) {                      if (substr($p_options[PCLZIP_OPT_BY_NAME][$j], -1) == "/") {                                  if (   (strlen($v_header['stored_filename']) > strlen($p_options[PCLZIP_OPT_BY_NAME][$j]))            && (substr($v_header['stored_filename'], 0, strlen($p_options[PCLZIP_OPT_BY_NAME][$j])) == $p_options[PCLZIP_OPT_BY_NAME][$j])) {                                  $v_extract = true;        }    }                  elseif ($v_header['stored_filename'] == $p_options[PCLZIP_OPT_BY_NAME][$j]) {                          $v_extract = true;    }}
	}
	else if (   (isset($p_options[PCLZIP_OPT_BY_EREG]))     && ($p_options[PCLZIP_OPT_BY_EREG] != "")) {if (ereg($p_options[PCLZIP_OPT_BY_EREG], $v_header['stored_filename'])) {                  $v_extract = true;}
	}
	else if (   (isset($p_options[PCLZIP_OPT_BY_PREG]))     && ($p_options[PCLZIP_OPT_BY_PREG] != "")) {if (preg_match($p_options[PCLZIP_OPT_BY_PREG], $v_header['stored_filename'])) {                  $v_extract = true;}
	}
	else if (   (isset($p_options[PCLZIP_OPT_BY_INDEX]))     && ($p_options[PCLZIP_OPT_BY_INDEX] != 0)) {                    for ($j=$j_start; ($j<sizeof($p_options[PCLZIP_OPT_BY_INDEX])) && (!$v_extract); $j++) {        if (($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['start']) && ($i<=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end'])) {                          $v_extract = true;    }    if ($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end']) {                          $j_start = $j+1;    }
	    if ($p_options[PCLZIP_OPT_BY_INDEX][$j]['start']>$i) {                          break;    }}
	}
	else {          $v_extract = true;
	}
      
	if ($v_extract)
	  {
	    @rewind($this->zip_fd);      if (@fseek($this->zip_fd, $v_header['offset']))
					   {          $this->privCloseFd();
					     PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');
					     return PclZip::errorCode();
					   }
	    if ($p_options[PCLZIP_OPT_EXTRACT_AS_STRING]) {
	      $v_result1 = $this->privExtractFileAsString($v_header, $v_string);if ($v_result1 < 1) {  $this->privCloseFd();              return $v_result1;}
	      if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted])) != 1){              $this->privCloseFd();
		return $v_result;}
	      $p_file_list[$v_nb_extracted]['content'] = $v_string;
	      $v_nb_extracted++;          if ($v_result1 == 2) {	break;}
	    }      elseif (   (isset($p_options[PCLZIP_OPT_EXTRACT_IN_OUTPUT]))
			      && ($p_options[PCLZIP_OPT_EXTRACT_IN_OUTPUT])) {          $v_result1 = $this->privExtractFileInOutput($v_header, $p_options);if ($v_result1 < 1) {  $this->privCloseFd();              return $v_result1;}
		     if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1) {  $this->privCloseFd();              return $v_result;}
		     if ($v_result1 == 2) {	break;}
	    }      else {          $v_result1 = $this->privExtractFile($v_header,
								       $p_path, $p_remove_path,
								       $p_remove_all_path,
								       $p_options);if ($v_result1 < 1) {  $this->privCloseFd();              return $v_result1;}
	      if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1){              $this->privCloseFd();
		return $v_result;}
	      if ($v_result1 == 2) {	break;}
	    }
	  }
      }

    $this->privCloseFd();
    return $v_result;
  }
  function privExtractFile(&$p_entry, $p_path, $p_remove_path, $p_remove_all_path, &$p_options)
  {
    $v_result=1;

    if (($v_result = $this->privReadFileHeader($v_header)) != 1)
      {        return $v_result;
      }

    
        
    if ($p_remove_all_path == true) {      $p_entry['filename'] = basename($p_entry['filename']);
    }

    else if ($p_remove_path != "")
      {  if (PclZipUtilPathInclusion($p_remove_path, $p_entry['filename']) == 2)
	  {
	    $p_entry['status'] = "filtered";
	    return $v_result;
	  }

	$p_remove_path_size = strlen($p_remove_path);
	if (substr($p_entry['filename'], 0, $p_remove_path_size) == $p_remove_path)
	  {
	    $p_entry['filename'] = substr($p_entry['filename'], $p_remove_path_size);
	  }
      }

    if ($p_path != '')
      {
	$p_entry['filename'] = $p_path."/".$p_entry['filename'];
      }

    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);
      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      if ($v_result == 0) {      $p_entry['status'] = "skipped";
        $v_result = 1;
      }
      if ($v_result == 2) {              $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
      $p_entry['filename'] = $v_local_header['filename'];}

        
    if ($p_entry['status'] == 'ok') {

      if (file_exists($p_entry['filename']))
	{
	  if (is_dir($p_entry['filename']))
	    {
              $p_entry['status'] = "already_a_directory";
	    }  else if (!is_writeable($p_entry['filename']))
	    {
              $p_entry['status'] = "write_protected";
	    }
	  else if (filemtime($p_entry['filename']) > $p_entry['mtime'])
	    {
              $p_entry['status'] = "newer_exist";
	    }
	}

      else {
	if ((($p_entry['external']&0x00000010)==0x00000010) || (substr($p_entry['filename'], -1) == '/'))
	  $v_dir_to_check = $p_entry['filename'];
	else if (!strstr($p_entry['filename'], "/"))
	  $v_dir_to_check = "";
	else
	  $v_dir_to_check = dirname($p_entry['filename']);

	if (($v_result = $this->privDirCheck($v_dir_to_check, (($p_entry['external']&0x00000010)==0x00000010))) != 1) {
	  $p_entry['status'] = "path_creation_fail";
	  $v_result = 1;
	}
      }
    }

    if ($p_entry['status'] == 'ok') {
      if (!(($p_entry['external']&0x00000010)==0x00000010))
	{
	  if ($p_entry['compressed_size'] == $p_entry['size'])
	    {          if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0){                $p_entry['status'] = "write_error";
		return $v_result;}
	      $v_size = $p_entry['compressed_size'];while ($v_size != 0){  $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);              $v_buffer = fread($this->zip_fd, $v_read_size);  $v_binary_data = pack('a'.$v_read_size, $v_buffer);  @fwrite($v_dest_file, $v_binary_data, $v_read_size);  $v_size -= $v_read_size;}
	      fclose($v_dest_file);
	      touch($p_entry['filename'], $p_entry['mtime']);
	    }
	  else
	    {                    if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {                $p_entry['status'] = "write_error";
		return $v_result;}
	      $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);
	      $v_file_content = gzinflate($v_buffer);unset($v_buffer);
	      @fwrite($v_dest_file, $v_file_content, $p_entry['size']);unset($v_file_content);
	      @fclose($v_dest_file);
	      touch($p_entry['filename'], $p_entry['mtime']);
	    }
	  if (isset($p_options[PCLZIP_OPT_SET_CHMOD])) {          chmod($p_entry['filename'], $p_options[PCLZIP_OPT_SET_CHMOD]);
	  }
	}
    }

    if ($p_entry['status'] == "aborted") {
      $p_entry['status'] = "skipped";
    }
	
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);
      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      if ($v_result == 2) {    	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }
    return $v_result;
  }
  function privExtractFileInOutput(&$p_entry, &$p_options)
  {
    $v_result=1;

    if (($v_result = $this->privReadFileHeader($v_header)) != 1) {  return $v_result;
    }

    
        
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);
      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      if ($v_result == 0) {      $p_entry['status'] = "skipped";
        $v_result = 1;
      }
      if ($v_result == 2) {              $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
      $p_entry['filename'] = $v_local_header['filename'];}

        
    if ($p_entry['status'] == 'ok') {
      if (!(($p_entry['external']&0x00000010)==0x00000010)) {      if ($p_entry['compressed_size'] == $p_entry['size']) {                    $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);
          echo $v_buffer;unset($v_buffer);
        }
        else {                    $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);          $v_file_content = gzinflate($v_buffer);unset($v_buffer);
          echo $v_file_content;unset($v_file_content);
        }    }
    }

    if ($p_entry['status'] == "aborted") {
      $p_entry['status'] = "skipped";
    }

    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);
      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      if ($v_result == 2) {    	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    return $v_result;
  }
  function privExtractFileAsString(&$p_entry, &$p_string)
  {
    $v_result=1;

    $v_header = array();
    if (($v_result = $this->privReadFileHeader($v_header)) != 1)
      {        return $v_result;
      }

    
        
        
    if (!(($p_entry['external']&0x00000010)==0x00000010))
      {  if ($p_entry['compressed_size'] == $p_entry['size'])
	  {                    $p_string = fread($this->zip_fd, $p_entry['compressed_size']);
	  }
	else
	  {            $v_data = fread($this->zip_fd, $p_entry['compressed_size']);
	    $p_string = gzinflate($v_data);
	  }
      }
    else {  }
    return $v_result;
  }
  function privReadFileHeader(&$p_header)
  {
    $v_result=1;

    $v_binary_data = @fread($this->zip_fd, 4);
    $v_data = unpack('Vid', $v_binary_data);
    
    if ($v_data['id'] != 0x04034b50)
      {
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Invalid archive structure');
        return PclZip::errorCode();
      }

    $v_binary_data = fread($this->zip_fd, 26);

    if (strlen($v_binary_data) != 26)
      {
	$p_header['filename'] = "";
	$p_header['status'] = "invalid_header";
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid block size : ".strlen($v_binary_data));
        return PclZip::errorCode();
      }
    $v_data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $v_binary_data);
    $p_header['filename'] = fread($this->zip_fd, $v_data['filename_len']);
    if ($v_data['extra_len'] != 0) {
      $p_header['extra'] = fread($this->zip_fd, $v_data['extra_len']);
    }
    else {
      $p_header['extra'] = '';
    }
    
    $p_header['compression'] = $v_data['compression'];
    $p_header['size'] = $v_data['size'];
    $p_header['compressed_size'] = $v_data['compressed_size'];
    $p_header['crc'] = $v_data['crc'];
    $p_header['flag'] = $v_data['flag'];
    
    $p_header['mdate'] = $v_data['mdate'];
    $p_header['mtime'] = $v_data['mtime'];
    if ($p_header['mdate'] && $p_header['mtime'])
      {  $v_hour = ($p_header['mtime'] & 0xF800) >> 11;
	$v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
	$v_seconde = ($p_header['mtime'] & 0x001F)*2;
	$v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
	$v_month = ($p_header['mdate'] & 0x01E0) >> 5;
	$v_day = $p_header['mdate'] & 0x001F;
	$p_header['mtime'] = mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
      }
    else
      {
	$p_header['mtime'] = time();}
        
    $p_header['stored_filename'] = $p_header['filename'];

    $p_header['status'] = "ok";
    return $v_result;
  }
  function privReadCentralFileHeader(&$p_header)
  {
    $v_result=1;

    $v_binary_data = @fread($this->zip_fd, 4);
    $v_data = unpack('Vid', $v_binary_data);
    
    if ($v_data['id'] != 0x02014b50)
      {
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Invalid archive structure');
        return PclZip::errorCode();
      }

    $v_binary_data = fread($this->zip_fd, 42);

    if (strlen($v_binary_data) != 42)
      {
	$p_header['filename'] = "";
	$p_header['status'] = "invalid_header";
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid block size : ".strlen($v_binary_data));
        return PclZip::errorCode();
      }
    $p_header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $v_binary_data);
    if ($p_header['filename_len'] != 0)
      $p_header['filename'] = fread($this->zip_fd, $p_header['filename_len']);
    else
      $p_header['filename'] = '';
    if ($p_header['extra_len'] != 0)
      $p_header['extra'] = fread($this->zip_fd, $p_header['extra_len']);
    else
      $p_header['extra'] = '';
    if ($p_header['comment_len'] != 0)
      $p_header['comment'] = fread($this->zip_fd, $p_header['comment_len']);
    else
      $p_header['comment'] = '';
                          
    if ($p_header['mdate'] && $p_header['mtime'])
      {  $v_hour = ($p_header['mtime'] & 0xF800) >> 11;
	$v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
	$v_seconde = ($p_header['mtime'] & 0x001F)*2;
	$v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
	$v_month = ($p_header['mdate'] & 0x01E0) >> 5;
	$v_day = $p_header['mdate'] & 0x001F;
	$p_header['mtime'] = mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
      }
    else
      {
	$p_header['mtime'] = time();}

    $p_header['stored_filename'] = $p_header['filename'];

    $p_header['status'] = 'ok';
    if (substr($p_header['filename'], -1) == '/')
      {
	$p_header['external'] = 0x41FF0010;}

    return $v_result;
  }

  function privReadEndCentralDir(&$p_central_dir)
  {
    $v_result=1;

    $v_size = filesize($this->zipname);
    @fseek($this->zip_fd, $v_size);
    if (@ftell($this->zip_fd) != $v_size)
      {  PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to go to the end of the archive \''.$this->zipname.'\'');
        return PclZip::errorCode();
      }
    $v_found = 0;
    if ($v_size > 26) {  @fseek($this->zip_fd, $v_size-22);  if (($v_pos = @ftell($this->zip_fd)) != ($v_size-22))
							       {      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to seek back to the middle of the archive \''.$this->zipname.'\'');
								 return PclZip::errorCode();
							       }
      $v_binary_data = @fread($this->zip_fd, 4);  $v_data = @unpack('Vid', $v_binary_data);
      if ($v_data['id'] == 0x06054b50) {      $v_found = 1;
      }

      $v_pos = ftell($this->zip_fd);
    }

    if (!$v_found) {  $v_maximum_size = 65557;       if ($v_maximum_size > $v_size)
						       $v_maximum_size = $v_size;
      @fseek($this->zip_fd, $v_size-$v_maximum_size);
      if (@ftell($this->zip_fd) != ($v_size-$v_maximum_size))
	{      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to seek back to the middle of the archive \''.$this->zipname.'\'');
	  return PclZip::errorCode();
	}
      $v_pos = ftell($this->zip_fd);
      $v_bytes = 0x00000000;
      while ($v_pos < $v_size)
	{      $v_byte = @fread($this->zip_fd, 1);
	  $v_bytes = ($v_bytes << 8) | Ord($v_byte);
	  if ($v_bytes == 0x504b0506)
	    {          $v_pos++;break;
	    }

	  $v_pos++;
	}
      if ($v_pos == $v_size)
	{
	  PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Unable to find End of Central Dir Record signature");
	  return PclZip::errorCode();
	}
    }

    $v_binary_data = fread($this->zip_fd, 18);

    if (strlen($v_binary_data) != 18)
      {
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid End of Central Dir Record size : ".strlen($v_binary_data));
        return PclZip::errorCode();
      }
    $v_data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $v_binary_data);
    if (($v_pos + $v_data['comment_size'] + 18) != $v_size)
      {
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Fail to find the right signature");
        return PclZip::errorCode();
      }

    if ($v_data['comment_size'] != 0)
      $p_central_dir['comment'] = fread($this->zip_fd, $v_data['comment_size']);
    else
      $p_central_dir['comment'] = '';
    
    $p_central_dir['entries'] = $v_data['entries'];
    $p_central_dir['disk_entries'] = $v_data['disk_entries'];
    $p_central_dir['offset'] = $v_data['offset'];
    $p_central_dir['size'] = $v_data['size'];
    $p_central_dir['disk'] = $v_data['disk'];
    $p_central_dir['disk_start'] = $v_data['disk_start'];
    return $v_result;
  }

  function privDirCheck($p_dir, $p_is_dir=false)
  {
    $v_result = 1;

    
    if (($p_is_dir) && (substr($p_dir, -1)=='/'))
      {
	$p_dir = substr($p_dir, 0, strlen($p_dir)-1);
      }
    
    if ((is_dir($p_dir)) || ($p_dir == ""))
      {  return 1;
      }

    $p_parent_dir = dirname($p_dir);
    
    if ($p_parent_dir != $p_dir)
      {  if ($p_parent_dir != "")
	  {
	    if (($v_result = $this->privDirCheck($p_parent_dir)) != 1)
	      {          return $v_result;
	      }
	  }
      }
    if (!@mkdir($p_dir, 0777))
      {  PclZip::privErrorLog(PCLZIP_ERR_DIR_CREATE_FAIL, "Unable to create directory '$p_dir'");
        return PclZip::errorCode();
      }
    return $v_result;
  }

  function privErrorLog($p_error_code=0, $p_error_string='')
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      PclError($p_error_code, $p_error_string);
    }
    else {
      $this->error_code = $p_error_code;
      $this->error_string = $p_error_string;
    }
  }

  function privErrorReset()
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      PclErrorReset();
    }
    else {
      $this->error_code = 1;
      $this->error_string = '';
    }
  }

}
function PclZipUtilPathReduction($p_dir)
{
  $v_result = "";

  if ($p_dir != "")
    {  $v_list = explode("/", $p_dir);
      for ($i=sizeof($v_list)-1; $i>=0; $i--)
	{      if ($v_list[$i] == ".")
	    {                  }
	  else if ($v_list[$i] == "..")
	    {          $i--;
	    }
	  else if (($v_list[$i] == "") && ($i!=(sizeof($v_list)-1)) && ($i!=0))
	    {                  }
	  else
	    {$v_result = $v_list[$i].($i!=(sizeof($v_list)-1)?"/".$v_result:"");
	    }
	}
    }
  return $v_result;
}

function PclZipUtilPathInclusion($p_dir, $p_path)
{
  $v_result = 1;

  $v_list_dir = explode("/", $p_dir);
  $v_list_dir_size = sizeof($v_list_dir);
  $v_list_path = explode("/", $p_path);
  $v_list_path_size = sizeof($v_list_path);

  $i = 0;
  $j = 0;
  while (($i < $v_list_dir_size) && ($j < $v_list_path_size) && ($v_result)) {
    if ($v_list_dir[$i] == '') {
      $i++;
      continue;
    }
    if ($v_list_path[$j] == '') {
      $j++;
      continue;
    }
    if (($v_list_dir[$i] != $v_list_path[$j]) && ($v_list_dir[$i] != '') && ( $v_list_path[$j] != ''))  {      $v_result = 0;
    }
    $i++;
    $j++;
  }

  if ($v_result) {        while (($j < $v_list_path_size) && ($v_list_path[$j] == '')) $j++;
    while (($i < $v_list_dir_size) && ($v_list_dir[$i] == '')) $i++;
      
    if (($i >= $v_list_dir_size) && ($j >= $v_list_path_size)) {      $v_result = 2;
    }
    else if ($i < $v_list_dir_size) {      $v_result = 0;
    }
  }
  return $v_result;
}

function PclZipUtilOptionText($p_option)
{
    
  switch ($p_option) {
  case PCLZIP_OPT_PATH :
    $v_result = 'PCLZIP_OPT_PATH';
    break;
  case PCLZIP_OPT_ADD_PATH :
    $v_result = 'PCLZIP_OPT_ADD_PATH';
    break;
  case PCLZIP_OPT_REMOVE_PATH :
    $v_result = 'PCLZIP_OPT_REMOVE_PATH';
    break;
  case PCLZIP_OPT_REMOVE_ALL_PATH :
    $v_result = 'PCLZIP_OPT_REMOVE_ALL_PATH';
    break;
  case PCLZIP_OPT_EXTRACT_AS_STRING :
    $v_result = 'PCLZIP_OPT_EXTRACT_AS_STRING';
    break;
  case PCLZIP_OPT_SET_CHMOD :
    $v_result = 'PCLZIP_OPT_SET_CHMOD';
    break;
  case PCLZIP_OPT_BY_NAME :
    $v_result = 'PCLZIP_OPT_BY_NAME';
    break;
  case PCLZIP_OPT_BY_INDEX :
    $v_result = 'PCLZIP_OPT_BY_INDEX';
    break;
  case PCLZIP_OPT_BY_EREG :
    $v_result = 'PCLZIP_OPT_BY_EREG';
    break;
  case PCLZIP_OPT_BY_PREG :
    $v_result = 'PCLZIP_OPT_BY_PREG';
    break;
  case PCLZIP_CB_PRE_EXTRACT :
    $v_result = 'PCLZIP_CB_PRE_EXTRACT';
    break;
  case PCLZIP_CB_POST_EXTRACT :
    $v_result = 'PCLZIP_CB_POST_EXTRACT';
    break;
  case PCLZIP_CB_PRE_ADD :
    $v_result = 'PCLZIP_CB_PRE_ADD';
    break;
  case PCLZIP_CB_POST_ADD :
    $v_result = 'PCLZIP_CB_POST_ADD';
    break;

  default :
    $v_result = 'Unknown';
  }
  return $v_result;
}
function PclZipUtilTranslateWinPath($p_path, $p_remove_disk_letter=true)
{
  if (defined('PHP_OS') && stristr(PHP_OS, 'win')) {
#    if (stristr(php_uname(), 'windows')) {  if (($p_remove_disk_letter) && (($v_position = strpos($p_path, ':')) != false)) {
    $p_path = substr($p_path, $v_position+1);
  }  if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0,1) == '\\')) {
    $p_path = strtr($p_path, '\\', '/');
  }
  return $p_path;
}


/**********************************************************************************/
/**********************************************************************************/
/**********************************************************************************/
/**********************************************************************************/

/* function for printing the messages and errors */

function open_html() {
  ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859">
    <meta name="ROBOTS" content="nofollow, noindex">
    <style type="text/css">
    <!--
    a:link {
    color: #CC6633;
    text-decoration: none;
  }
 a:visited {
  color: #666666;
  }
 a:hover {
  color: #CC0000;
  }
 a:active {
  color: #333366;
  }
  p {
    font-family: Verdana,Arial;
    font-size: 14px;
  color: #544A35;
    text-align: justify;
    line-height: 18px;
    padding-bottom: 10px;
  }
  h1 {
    font-family: Verdana,Arial;
    font-size: 25px;
  color: #544A35;
    text-align: center;
    padding-bottom: 20px;
  }
  h2 {
    font-family: Verdana,Arial;
    font-size: 20px;
  color: #544A35;
    text-align: center;
    padding-bottom: 20px;
  }
  h3 {
    font-family: Verdana,Arial;
    font-size: 18px;
  color: #544A35;
    text-align: center;
    padding-bottom: 20px;
  }

  -->
    </style>


	<head>
	<title>Chargement et installation de LODEL</title>
	</head>
	<body bgcolor="#FFFFFF"  text="Black" vlink="black" link="black" alink="blue" onLoad="" marginwidth="0" marginheight="0" rightmargin="0" leftmargin="0" topmargin="0" bottommargin="0"> 

	<h1>Chargement et installation de LODEL</h1>


	<div align="center">
	<table width="600">
	<tr>
	<td>
	<?php
	}

function close_html() {
  ?>
  </td>
    </table>
    </div>
    </body>
    <?php
}

function msg_error($msg) {
  open_html();
  echo $msg;
  close_html();
  exit();
}


function msg_intro() {
  open_html();
?>
<p align="center">
Bienvenue dans le script d'installation de <b>LODEL</b>, logiciel d'édition électronique</p>
<p>Ce script va:</p>
<ul>
    <li>télécharger automatiquement sur le site http://www.lodel.org la dernière version de Lodel.</li>
  <li>configurer Lodel</li>
</ul>

<p>Si vous souhaitez poursuivre et si vous acceptez les conditions d'utilisation de Lodel rappelé ci-dessous, veuillez cliquer sur "Installer Lodel".</p>


<h2>Condition d'utilisation de Lodel</h2>
<p align="justify">Ce programme est un logiciel libre&nbsp;; vous pouvez le redistribuer et/ou le modifier conform&eacute;ment aux dispositions de la Licence Publique G&eacute;n&eacute;rale GNU, telle que publi&eacute;e par la Free Software Foundation&nbsp;; version 2 de la licence, ou encore (&agrave; votre choix) toute version ult&eacute;rieure. </p>
<p align="justify">Ce programme est distribu&eacute; dans l'espoir qu'il sera utile, mais SANS AUCUNE GARANTIE&nbsp;; sans m&egrave;me la garantie implicite de COMMERCIALISATION ou D'ADAPTATION A UN OBJET PARTICULIER. Pour plus de d&eacute;tails, voir la Licence Publique G&eacute;n&eacute;rale GNU. </p>
<p align="justify">Un exemplaire de la Licence Publique G&eacute;n&eacute;rale GNU doit &ecirc;tre fourni avec ce programme&nbsp;; si ce n'est pas le cas, &eacute;crivez &agrave; la Free Software Foundation Inc., 675 Mass Ave, Cambridge, MA 02139, Etats-Unis. </p>

<p>GNU General Public License&nbsp;: <a href="http://www.gnu.org/licenses/gpl.txt">licence</a></p>

<form  method="post" action="lodelloader.php">
<input type="submit" name="install" value="Installer Lodel">
</form>

<?php
  close_html();
  exit();

}

function msg_chmod($chmod,$reducedchmod)

{
  open_html();

  $chmod2=$chmod;
  for ($i=0; $i<3; $i++) {
    $fileperm="-".$fileperm;
    $dirperm=($chmod2 & 0001 ? "x" : "-") .$dirperm;

    $fileperm=($chmod2 & 0002 ? "w" : "-") .$fileperm;
    $dirperm=($chmod2 & 0002 ? "w" : "-") .$dirperm;

    $fileperm=($chmod2 & 0004 ? "r" : "-") .$fileperm;
    $dirperm=($chmod2 & 0004 ? "r" : "-") .$dirperm;

    $chmod2=$chmod2 >> 3;
  }


  echo "<p>Ce script a essayé de détecter les permissions à accorder sur les fichiers et les répertoires de Lodel. Les permissions détectées sont: $fileperm pour les fichiers et $dirperm pour les répertoires.</p>";

  if ($chmod & 0002) { echo "<p><b>Attention</b>: donner les droits d'écriture à \"tous\" peut sur certains serveurs être une serieuse faille de sécurité. Si vous avez des doutes veuillez demander conseil avant de poursuivre.</p>"; }


  if ($reducedchmod) echo "<p>Les droits en écriture on été restreint car la configuration du serveur ne permet pas d'éxecuter les scripts PHP avec des droits en écriture souple (ce qui est une bonne chose pour la sécurité). Cependant, il est possible que vous ne puissiez plus écrire ou éffacer les fichiers dans votre repertoire. Il vous faudra utiliser un File Manager en PHP pour le faire.</p>";

?>
<p>Si ces permissions conviennent veuillez cliquer sur continuer. Lodel va être télécharger ce qui peut prendre un certain temps. Merci de patentier</p>
<form  method="post" action="lodelloader.php">
<input type="hidden" name="chmod" value="<?php echo $chmod ?>">
<input type="submit" name="install" value="Télécharger Lodel">

</form>
<?php
  close_html();
  exit();
}


?>