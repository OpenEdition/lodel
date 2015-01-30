<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour la génération des formulaires
 */

/*
 Version 0.5, 2006-08-06, http://alexandre.alapetite.net/doc-alex/xslt-php4-php5/

 ------------------------------------------------------------------
 Written by Alexandre Alapetite, http://alexandre.alapetite.net/cv/

Copyright 2004-2006, Licence: Creative Commons "Attribution-ShareAlike 2.0 France" BY-SA (FR),
 http://creativecommons.org/licenses/by-sa/2.0/fr/
 http://alexandre.alapetite.net/divers/apropos/#by-sa
 - Attribution. You must give the original author credit
 - Share Alike. If you alter, transform, or build upon this work,
   you may distribute the resulting work only under a license identical to this one
   (Can be included in GPL/LGPL projects)
 - The French law is authoritative
 - Any of these conditions can be waived if you get permission from Alexandre Alapetite
 - Please send to Alexandre Alapetite the modifications you make,
   in order to improve this file for the benefit of everybody

 If you want to distribute this code, please do it as a link to:
 http://alexandre.alapetite.net/doc-alex/xslt-php4-php5/
*/

// compatibilité PHP5
$xslArgs=null;
function xslt_create() {return new xsltprocessor();}
function xslt_errno($xh) {return 7;}
function xslt_error($xh) {return '?';}
function xslt_free($xh) {unset($xh);}
function xslt_process($xh,$xmlcontainer,$xslcontainer,$resultcontainer=null,$arguments=array(),$parameters=array())
{//See also: http://alexandre.alapetite.net/doc-alex/domxml-php4-php5/
 //Based on: http://www.php.net/manual/ref.xsl.php#45415
 $xml=new DOMDocument();
 $basedir=$xh->getParameter('sablotron','xslt_base_dir');
 if ($basedir && ($workdir=getcwd()))
  chdir($basedir);
 if (substr($xmlcontainer,0,4)=='arg:')
  $xml->loadXML($arguments[substr($xmlcontainer,4)]);
 else $xml->load($xmlcontainer);
 $xsl=new DOMDocument();
 if (substr($xslcontainer,0,4)=='arg:')
  $xsl_=&$arguments[substr($xslcontainer,4)];
 else $xsl_=file_get_contents($xslcontainer);
 $xsl->loadXML(str_replace('arg:/','arg://',$xsl_));
 $xh->importStyleSheet($xsl);
 global $xslArgs;
 $xslArgs=$arguments;
 foreach ($parameters as $param=>$value)
  $xh->setParameter('',$param,$value);
 $result=$xh->transformToXML($xml);
 if (isset($resultcontainer))
  file_put_contents($resultcontainer,$result); 
 if ($basedir && $workdir)
  chdir($workdir);
 if (isset($resultcontainer))
  return true;
 else return $result;
}
function xslt_set_base($xh,$base) {$xh->setParameter('sablotron','xslt_base_dir',str_replace('file://','',$base));}
function xslt_set_encoding($xh,$encoding) {} //Manual encoding, or use xsl:output @encoding in XSL document
function xslt_set_error_handler($xh,$handler) {}

class xslt_arg_stream
{
 public $position;
 private $xslArg;
 function stream_eof() {return $this->position>=strlen($this->xslArg);}
 function stream_open($path,$mode,$options,&$opened_path)
 {
  $this->position=0;
  $url=parse_url($path);
  $varname=$url['host'];
  global $xslArgs;
  if (isset($xslArgs['/'.$varname]))
   $this->xslArg=&$xslArgs['/'.$varname];
  elseif (isset($xslArgs[$varname]))
   $this->xslArg=&$xslArgs[$varname];
  else return false;
  return true;
 }
 function stream_read($count)
 {
  $ret=substr($this->xslArg,$this->position,$count);
  $this->position+=strlen($ret);
  return $ret;
 }
 function stream_tell() {return $this->position;}
 function url_stat() {return array();}
}

stream_wrapper_register('arg','xslt_arg_stream');

// début script
require_once 'generatefunc.php';

$xsltproc = xslt_create();

xslt_set_encoding($xsltproc, 'utf-8');
$xmlpath=".";
$tplpath="../src/lodel/admin/tpl";
$tables=array("persontypes",
	      "entrytypes",
	      "types","classes",
	      "tablefields",
	      "indextablefields", // vtable
	      "tablefieldgroups",
	      "users",
	      "usergroups",
	      "translations",
	      "options",
	      "optiongroups",
	      "characterstyles",
	      "internalstyles",
	      "translations",
		"texts",
		"restricted_users",
		"internal_messaging"
				);
foreach ($tables as $table) {
  echo "table=$table\n";
  $parameters=array(
		    "table"=>$table
		    );

  $html =
    xslt_process($xsltproc, "$xmlpath/init-site.xml", "$xmlpath/forms.xsl", NULL, NULL,$parameters);


  $html=str_replace(array("<phptag>","</phptag>",
			  "[#POSTACTION]","<ELSE></ELSE>",
			  "<br>"),
		    array("<"."?php ","?".">",
			  '<'.'?php echo basename($_SERVER[\'PHP_SELF\']); ?'.'>',"<ELSE/>",
			  "<br />"
			  ),$html
		    );

  if (empty($html)) {
    trigger_error('XSLT processing error: '. xslt_error($xsltproc), E_USER_ERROR);
  }


  $beginre='<!--\[\s*begin\{form\}[^\n]+?\]-->';
  $endre='<!--\[\s*end\{form\}[^\n]+?\]-->';

  $filename=$tplpath."/edit_$table.html";
  replaceInFile($filename,$beginre,$endre,$html);
}

xslt_free($xsltproc);
?>
