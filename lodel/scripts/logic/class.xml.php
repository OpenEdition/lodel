<?php
/**
 * Logique des fonctionnalités XML
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */
 
/**
 * Classe de logique des fonctionnalités XML
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class XMLLogic extends Logic {

	/** 
	* Constructor
	*/
	public function __construct() 
	{
		parent::__construct("translations");
	}
	
	/**
	* Generate the XML for an entity.
	* Must have context['id'] given
	*/
	public function generateXMLAction (&$context, &$error) 
	{
		if (empty($context['id']))
			trigger_error('ERROR : no id given. Id attribute is required to generate XML file', E_USER_ERROR);

		global $db;
		$id = (int) $context['id'];
		//check if the given id is OK
		$row = $db->getOne(lq("SELECT 1 FROM #_TP_objects WHERE id='$id' AND class='entities'"));
		if (!$row) {//if the object is not an entity do not generate XML
			header ("Location: not-found.html"); return;
		}
		$row = $db->getRow(lq("SELECT e.id,t.type,t.class FROM #_TP_entities as e, #_TP_types as t WHERE e.id='$id' AND e.idtype=t.id"));
		if (!$row) trigger_error("Error. Type and Class unknown for this entity", E_USER_ERROR);
		
		$context['class'] = $class = $row['class'];
		$context['type'] = $row['type'];
		$context['identity'] = $id;

		function_exists('calculateXML') || include 'xmlfunc.php';
		$context['contents'] =  $contents = calculateXML ($context);
		// !! BEWARE !!
		// validation shall be implemented in ServOO first. The code here comes from the 0.7 and is not adapted to the lodel 0.8 and higher.
		//
	// validation needed
//    if ($context['valid']) {
//      $contents .= $contents;
//      $tmpdir = tmpdir ();
//      $tmpfile = tempnam ($tmpdir, "lodelxml_");
//      writefile($tmpfile.".xml",$contents);
//      $contents=calculateXMLSchema ($context);
//      writefile ($tmpfile.".xsd", $contents);
//      if ($zipcmd && $zipcmd!="pclzip") { // ZIP command
//	$errfile=$tmpfile.".err";
//	system($zipcmd." $tmpfile.zip $tmpfile.xsd $tmpfile.xml  1>&2 2>$errfile");
//	if (filesize($errfile)>0) 
//	  trigger_error("ERROR: $errormsg<br />".str_replace ("\n","<br>", htmlentities (@join ("", @file ($errfile)))), E_USER_ERROR);
//	@unlink("$tmpfile.err");
//      }	else {// PCLZIP library. 
//	require ("pclzip.lib.php");
//	$archive = new PclZip ($tmpfile.".zip");
//	$v_list = $archive->create (array($tmpfile.".xsd", $tmpfile.".xml"));
//	if ($v_list == 0) trigger_error("ERROR : ".$archive->errorInfo(true), E_USER_ERROR);
//      }
//      require ("servoofunc.php");
//      $client = new ServOO ();
//      if ($client->error_message) {
//      	if ($context['url']) $error['url']='+';
//      	if ($context['username'])$error['username']='+';
//      	if ($context['passwd']) $error['passwd']='+';
//      }
//      $ret = $client->validateXML("","");
//      echo "ret=$ret";
//      if ($ret=="noservoo") 
//	$ret="Aucun ServOO n'est configur&eacute; pour r&eacute;aliser la conversion. " .
//	  "Vous pouvez faire la configuration dans les options du site (Administrer/Options)";
//  
//      @unlink ($tmpfile.".zip");
//      $context['reponse'] = str_replace ("\n","<br />", htmlentities($ret));
//      require_once ("calcul-page.php");
//      calcul_page ($context,"xml-valid");
//      exit (0);
//    }	else 
		if (isset($context['view']))	{
			return "_ok";
		} else  {// "download"
			download ("", "$class-$id.xml", $contents);
            		exit();
		}
	}
	/**
	* Generate the XSD Schema for a class
	* Must have context['class'] given
	*/
	public function generateXSDAction (&$context, &$error) {
		
		if (empty($context['class']))
			trigger_error('ERROR: no class given. Class attribute is required to generate XSD Schema', E_USER_ERROR);
		function_exists('calculateXML') || include 'xmlfunc.php';
		//verif if the given class is OK
		global $db;
		$class = addslashes($context['class']);
		$row = $db->getOne (lq ("SELECT id FROM #_TP_classes WHERE class='$class'"));
		if (!$row) {
			header ("Location: not-found.html"); return;
		}
		$originalname = C::get('site', 'cfg'). '-'.$context['class']. '-schema-xml.xsd';
		$ret = calculateXMLSchema ($context);
		download ("", $originalname, $ret);
        	exit();
	}
}