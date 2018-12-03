<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

  /** 
   * @access private
   */
define('SERVOOLIBDIR',dirname(__FILE__)."/");
if(!class_exists('soapclientmime', false))
{
	require(SERVOOLIBDIR."nusoap.php");
	require(SERVOOLIBDIR."nusoapmime.php");
}


/**
 * Client class for communicating with ServOO server.
 * This class is a wrapper around the class ServOO_SOAP_Server on the server-side.
 * convert* methods convert the input files into the specified format
 * and perform high level processing on the return file like unpacking ZIP archive or
 * processing the XHTML file.
 * Consider first using convertToAssoc since it is the highest-level method
 * provided by this library. 
 * If finer control is required, lower-level methods are available. Listed by decreasing order
 * of processing: convertToXHTML, convertUnpack, convertToFile, convertToString.
 */


class ServOO_Client {

  /**
   * true if an error occured.
   */
  var $error;

  /**
   * message of the last error.
   */
  var $error_message;

  /** 
   * array containing the http_request params.
   * @access private
   */
  var $http_request;


  /** 
   * @access private
   */
  var $_soapclient;

  /** 
   * @access private
   */
  var $_images;

  /**
   * ServOO_Client constructor.
   * 
   * @param string $url Location of the ServOO server.
   */

  function __construct($url) { 
    $this->_soapclient = new soapclientmime($url);

    $err = $this->_soapclient->getError();
    if ($err) {
      $this->error_message=$err;
    }
  }


  /**
   * Set the proxy parameters
   * @param string $user username
   * @param string $pass password
   */
  function setproxy($user,$pass) {
    $this->_soapclient->setHTTPProxy($user,$pass);
  }

  /**
   * Set the basic authentification parameters
   * @param string $user username
   * @param string $pass password
   */

  function setauth($user,$pass) {
    $this->_soapclient->setCredentials($user,$pass);
  }

  /**
   * convert the file $infilename from $informat into $outformat.
   * @param string $infilename input filename
   * @param string $informat MIME format or filename extension of the input file
   * @param string $outformat MIME format, filename extension, or especial type of the output file
   * @param array $options options to send to ServOO. Available options depend on outformat.
   * @return bool true on success
   */

  function convertToString($infilename,$informat,$outformat,&$outstring,$options=array()) {
    $this->_soapclient->setHTTPEncoding('deflate, gzip');
    $cid = $this->_soapclient->addAttachment('', $infilename);

    $att=new servooattachment($cid);

    // sent the file to convert
    $this->call("convert",
		array($outformat,$informat,$att,$options));

    //    <theAttachedFile href="cid:9393829292aa"/>
    #echo $this->_soapclient->outgoing_payload;

    // what do we get ?
    if ($this->error) return false;
    $attachments=$this->_soapclient->getAttachments();
    if (!$attachments) {
      $this->error=true;
      $this->error_message="no attachment in the server response";
      return false;
    }
    $outstring=$attachments[0]['data'];

    return true;
  }


  /**
   * Convert the file $infilename from $informat into $outformat and save the resulting file on the 
   * disk as $outfilename
   *
   * @param string $infilename input filename
   * @param string $outfilename output filename
   * @param string $informat MIME format or filename extension of the input file
   * @param string $outformat MIME format, filename extension, or especial type of the output file
   * @param array $options options to send to ServOO. Available options depend on outformat.
   * @return bool true on success
   */

  function convertToFile($infilename,$informat,$outformat,$outfilename,$options=array()) {
    $ret=$this->convertToString($infilename,$informat,
				$outformat,$outstring,
				$options);
    if (!$ret) return false;

    // delete before writing (useful when directory is in write acces but file is not
    if (file_exists($outfilename)) { 
      if (! (unlink($outfilename)) ) {
	$this->error_message="cannot delete the file $outfilename";
	return false;
      }
    }
    if (($f=fopen($outfilename,"w")) && 
	(fputs($f,$outstring)!==FALSE) && 
	fclose($f)) return true;
    $this->error_message="cannot write the file $outfilename";
    return false;
  }



  /**
   * Convert the file $infilename from $informat into $outformat and extract the files from 
   * the ZIP archive returned by the ServOO. You must ensure that ServOO return ZIP archive 
   * for the selected $outformat (raise an error if not a ZIP archive).
   * 
   * @param string $infilename input filename
   * @param string $outfilename output filename
   * @param string $informat MIME format or filename extension of the input file
   * @param string $outformat MIME format, filename extension, or especial type of the output file
   * @param string $tmpdir directory where files in the archive are extracted.
   * @param array $options options to send to ServOO. Available options depend on outformat.
   * @param array $zipoptions options to pass to PclZip lib. Two additional options
   * are provided: denyextensions and allowextensions to select the files to extract. These
   * options must be perl regexp. 
   * If allowextensions is not set the default value is xhtml|jpg|png|gif
   * for security reason. Set allowextensions to "" or any other value 
   * if you don't want the default. The regexp search is done case-insensitive (/i modifier)
   * @return array list of file fullname. False on error message if any error
   */

  function convertUnpack($infilename,$informat,$outformat, $outdir,
			 $options=array(),
			 $zipoptions=array()) {

    $tmpoutdir = C::get('tmpoutdir', 'cfg');
    if(empty($tmpoutdir) && !empty($outdir)) {
	$tmpoutdir = $outdir;
    }
    $outfilename=tempnam($tmpoutdir,"servooclient");
    $ret=$this->convertToFile($infilename,$informat,
			      $outformat,$outfilename,
			      $options);
    if (!$ret) {
	@unlink($outfilename);
	return $ret;
    }
    $err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
    if(!class_exists('PclZip', false))
    	require(SERVOOLIBDIR."pclzip/pclzip.lib.php"); // use the modified PclZip !!!!!!

    // create Zip object
    $zip=new PclZip($outfilename);
    // 
    if (isset($zipoptions['denyextensions'])) $GLOBALS['user_vars']['denyextensions']=$zipoptions['denyextensions'];
    if (isset($zipoptions['allowextensions'])) {
      $GLOBALS['user_vars']['allowextensions']=$zipoptions['allowextensions'];
    } else {
      // default value
      $GLOBALS['user_vars']['allowextensions']="xhtml|jpg|png|gif";
    }

    $ret=$zip->extract(PCLZIP_OPT_PATH,$tmpoutdir,
		       PCLZIP_OPT_REMOVE_ALL_PATH,
		       PCLZIP_CB_PRE_EXTRACT,"_convertUnpack_Pre_Extract_CB");

    unlink($outfilename);

    if ($ret==0) {
      $this->error_message=$zip->errorInfo(true);
      error_reporting($err);
      return false;
    }
    $filelist=array();
    foreach($ret as $entry) {
      if ($entry['status']=="ok") $filelist[]=$entry['filename'];
    }
    error_reporting($err);
    return $filelist;
  }

  
  /**
   * Convert the file $infilename from $informat into $outformat and extract the xhtml file and the
   * images from the ZIP archive returned by the ServOO.
   * You must ensure that ServOO return ZIP archive containing a xhtml file and images 
   * for the selected $outformat.The images are transfered as defined by a callback 
   * function and the XHTML is updated to account for this change (change the href location).
   * 
   * @param string $infilename input filename
   * @param string $informat MIME format or filename extension of the input file
   * @param string $outformat MIME format, filename extension, or especial type of the output file
   * @param string $tmpdir temporary directory for extracting the archive.
   * @param string $imagesdir directory where to transfer the image if no callback function is given.
   * @param array $options options to send to ServOO. Available options depend on outformat.
   * @param array $zipoptions See {@link ServOO_Client::convertUnpack()} for details.
   * @param function $callbackimages callback function taking threes arguments (
   * the original filename, the index in the zip archive and the user defined variable $user_vars)
   * and must return the new full filename where the image shall be extracted or an empty string
   * if the images should not be extracted. If no callback is provided the images filename 
   * a automatically assigned and stored in the $tmpdir directory.
   * @param mixed $user_vars variable transmitted to the callback.
   * @access public
   * @return array list of file fullname. False on error message if any error
   */

  function convertToXHTML($infilename,$informat,
			  $outformat,$tmpdir,$imagesdir="",
			  $options=array(),
			  $zipoptions=array(),
			  $callbackimages="",
			  $user_vars="") 
  {
    $tmpoutdir = C::get('tmpoutdir', 'cfg');
    if(empty($tmpoutdir) && !empty($tmpdir)) {
	$tmpoutdir = $tmpdir;
    }

    $outfilename=tempnam($tmpoutdir,"servooclient");
    $ret=$this->convertToFile($infilename,$informat,
			      $outformat,$outfilename,
			      $options);

    if (!$ret) {
	@unlink($outfilename);
	return $ret;
    }
    $err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
    if(!class_exists('PclZip', false))
    	require(SERVOOLIBDIR."pclzip/pclzip.lib.php"); // use the modified PclZip !!!!!!

    // create Zip object
    $zip=new PclZip($outfilename);
    // 

    if (($list = $zip->listContent()) == 0) {
	@unlink($outfilename);
      $this->error_message=$zip->errorInfo(true);
      error_reporting($err);
      return false;
    }

    $this->_images=array();
    $originalimages=array();
    $count=0;
    foreach($list as $entry) {
      if ($entry['status']!="ok") continue;
      // xhtml files
      if (substr($entry['filename'],-6)==".xhtml") {
	$ret=$zip->extract(PCLZIP_OPT_BY_INDEX, array ($entry['index']),
			   PCLZIP_OPT_EXTRACT_AS_STRING);
	if (!$ret) {
	  @unlink($outfilename);
	  $this->error_message=$zip->errorInfo(true);
	  error_reporting($err);
	  return false;
	}
	$xhtml=$ret[0]['content'];
	unset($ret);
      } else {
	// others files
	// check the extensions...
	$ext = strtolower(strrchr($entry['stored_filename'],'.'));
	if (isset($zipoptions['denyextensions']) && FALSE !== strpos($zipoptions['denyextensions'], $ext)) continue;
// 	    preg_match("/\.(".$zipoptions['denyextensions'].")$/i",$entry['stored_filename'])) continue;
	if (isset($zipoptions['allowextensions']) && FALSE !== strpos($zipoptions['allowextensions'], $ext)) continue;
// 	    !preg_match("/\.(".$zipoptions['allowextensions'].")$/i",$entry['stored_filename'])) continue;

	// make here an option to check further with GD.

	// now get the name of the image files

	if ($callbackimages && is_callable($callbackimages)) {
	  $name=call_user_func($callbackimages,
			       $entry['stored_filename'],
			       $entry['index'],
			       $user_vars);
	} else {
	  if ($imagesdir) {
	    $name=$imagesdir;
	  } elseif ($tmpdir) {
	    $name=$tmpdir;
	  } else {
	    $name=".";
	  }
// 	  preg_match("/\.\w+$/",$entry['stored_filename'],$result); // get extension
	  $name.="/img-".(++$count).$ext;
	}

	$this->_images[$entry['index']]=$name;
	$originalimages[$entry['index']]=$entry['stored_filename'];
      }
    }
    if ($this->_images) {

      $GLOBALS['user_vars']=$this->_images;
       
      // now extract the images
      $ret=$zip->extract(PCLZIP_OPT_REMOVE_ALL_PATH,
			 PCLZIP_CB_PRE_EXTRACT,"_convertToXML_Pre_Extract_CB");
      if ($ret==0) {
	@unlink($outfilename);
	$this->error_message=$zip->errorInfo(true);
	error_reporting($err);
	return false;
      }

      // change the xhtml file
      $search=array(); $rpl=array();
      foreach ($originalimages as $i=>$imgfilename) {
	$search[]='/(<img\b[^>]+\bsrc=")'.preg_quote($imgfilename,"/").'"/';
	$rpl[]="\\1".$this->_images[$i].'"';
	// this is not the best regexp ever, not 100% XML. It might fails in some very very odd cases
      }
      $xhtml=preg_replace($search,$rpl,$xhtml);
    }

    // remove the archive
    @unlink($outfilename);
    error_reporting($err);
    return $xhtml;
  }




  /**
   * Convert the file $infilename from $informat into $outformat, extract the stylized
   * blocks of the xhtml file into an association array. This is the
   * highest level function unless finer controls are required.
   * The images file are extracted from the archive and the <img src=""> are updated accordinatly.
   * 
   * @param string $infilename input filename
   * @param string $informat MIME format or filename extension of the input file
   * @param string $outformat MIME format, filename extension, or especial type of the output file
   * @param string $tmpdir temporary directory for extracting the archive.
   * @param string $imagesdir directory where to transfer the image if no callback function is given.
   * @param array $options options to send to ServOO. Available options depend on outformat.
   * @param array $zipoptions See {@link ServOO_Client::convertUnpack()} for details.
   * @param function $callbackimages callback function taking threes arguments (
   * the original filename, the index in the zip archive and the user defined variable $user_vars)
   * and must return the new full filename where the image shall be extracted or an empty string
   * if the images should not be extracted. If no callback is provided the images filename 
   * a automatically assigned and stored in the $tmpdir directory.
   * @param mixed $user_vars variable transmitted to the callback.
   * @access public
   * @return array associative array containing the style and the block. False on error message if any error
   */

  function convertToAssoc($infilename,$informat,
			  $outformat,$tmpdir,$imagesdir="",
			  $options=array(),
			  $zipoptions=array(),
			  $callbackimages="",
			  $user_vars="")
  {
    $options['block']=true; // this is the minimum required
    $ret=$this->convertToXHTML($infilename,$informat,$outformat,$tmpdir,$imagesdir,$options,$zipoptions,$callbackimages,$user_vars);
    if ($ret===false) return false;

    $struct=array();
    while ( ($pos=strpos($ret,"<soo:block "))!==false) {
      $endtagpos=strpos($ret,">",$pos)+1;
      $attr=substr($ret,$pos,$endtagpos-$pos+1);
      if (!preg_match('/class="([^"]+)"/',$attr,$result)) {
	// no class... strange but less go on
	$ret=substr($ret,$endtagpos);
	continue;
      }
      $class=$result[1];
      $closingtagpos=strpos($ret,"</soo:block>",$endtagpos);

      $struct[$class]=substr($ret,$endtagpos,$closingtagpos-$endtagpos);
      $ret=substr($ret,$closingtagpos+12);
    }
    return $struct;
  }


  /**
   * Get the version of the ServOO.
   * 
   * @return string version
   */

  function version()

  {
    $ret = $this->call("version");

    if ($this->error) {
      return "Error: " . $this->error_message ."\n";
    } else {
      return $ret;
    }
  }

  /**
   * Validate an XML file for a XML Schema
   * 
   * @param string $xml XML filename
   * @param string $xmlschema XML/Schema filename
   * @param strign $validator available validators are in ServOO_SOAP_Server class
   * @return string return nothing if the document is valid. Otherwise, the string return by the validator  or if any Error the message returned by the SOAP server
   */

 
  function validateXML($xml,$xmlschema,$validator="MSV") {

    $this->_soapclient->setHTTPEncoding('deflate, gzip');
    $cid = $this->_soapclient->addAttachment('', $xml);
    $att=new servooattachment($cid);


    $cid2 = $this->_soapclient->addAttachment('', $xmlschema);
    $att2=new servooattachment($cid2);

    // sent the file to convert
    $ret=$this->call("validateXML",
		     array($att2,$validator));

    // what do we get ?
    if ($this->error) {
      return false;
    } else {
      return $ret;
    }
  }

  /**
   * @access private
   */

    function call($operation,$params=array()) {
      if(!is_object($this->_soapclient)) {
          $this->error=true;
          $this->error_message='Invalid configuration, can not contact the ServOO';
	  return false;
      }
      // sent the file to convert
      $ret = $this->_soapclient->call($operation,
				      $params,
				      "urn:ServOO_SOAP_Server");

      // what do we get ?
      if ($this->_soapclient->fault || $this->_soapclient->error_str) {
	$this->error=true;
	if (is_array($ret) && isset($ret['faultcode'])){
	  $this->error_message=$ret['faultstring'];
	} elseif ($this->_soapclient->error_str) {
	  $this->error_message=$this->_soapclient->error_str;
	} else {
	  $this->error_message="unknown error";
	}
	$outstring="";
	return false;
      } elseif ( ($err = $this->_soapclient->getError()) ) {
	$this->error=true;
	if (strpos($err,"HTTP Error: Unsupported HTTP")!==false &&
	    preg_match("/^HTTP\/1\.1\s+(.*)/",$this->_soapclient->response,$result)) {
	  $this->error_message="HTTP Response: $result[1]";
	} elseif (strpos($err,"Response not of type text/xml")!==false) {
	  $this->error_message=$err."\n".$this->_soapclient->response;
	}
	return false;
      }
      return $ret;
    }

    /** 
     * get the images contained in the XHTML file.
     *
     * @return array array with images filename
     */

    function images()

    { return is_array($this->images) ? $this->images : array(); }

} // class Servoo_Client


/**
 * @access private
 */

function _convertUnpack_Pre_Extract_CB($p_event, &$p_header)

{
  global $user_vars;
  $ext = strtolower(strrchr($p_header['stored_filename'], '.'));
  if (isset($user_vars['denyextensions']) && FALSE !== strpos($zipoptions['denyextensions'], $ext)) return 0; 
//       preg_match("/\.(".$user_vars['denyextensions'].")$/i",$p_header['stored_filename'])) return 0;
  if (isset($user_vars['allowextensions']) && FALSE !== strpos($zipoptions['allowextensions'], $ext)) return 0; 
//       !preg_match("/\.(".$user_vars['allowextensions'].")$/i",$p_header['stored_filename'])) { return 0; }
  return 1;
}



/**
 * @access private
 */

function _convertToXML_Pre_Extract_CB($p_event, &$p_header)

{
  global $user_vars;
  if (isset($user_vars[$p_header['index']])) {
    $p_header['filename']=$user_vars[$p_header['index']];
    return 1; // extract with the new name
  } else {
    return 0; // don't extract
  }
}

/**
 * Little hack to manage attachement in nusoap 
 *
 * @access private
 */

class servooattachment {
  var $cid;
  function __construct($cid) {
    $this->cid=$cid;
  }

  function serialize($use)

  {  return '<infile href="cid:'.$this->cid.'"/>'; }
}

?>
