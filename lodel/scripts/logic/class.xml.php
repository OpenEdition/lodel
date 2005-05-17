<?php
  /*
   *
   *  LODEL - Logiciel d'Edition ELectronique.
   *
   *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
   *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
   *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
   *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 
  /**
   *  XML Logic
   */
class XMLLogic extends Logic {

  /** 
   * Constructor
   */
  function XMLLogic () {
    $this->Logic ("translations");
  }
  
  /**
   * Generate the XML for an entity.
   * Must have context['id'] given
   */
  function generateXMLAction (&$context, &$error) {

    if (!$context['id'])
      die ('ERROR : no id given. Id attribute is required to generate XML file');

    global $db;
    $id=$context['id'];
    //check if the given id is OK
    $row = $db->getOne(lq("SELECT 1 FROM #_TP_objects WHERE id='$id' AND class='entities'"));
    if (!$row) {//if the object is not an entity do not generate XML
      header ("Location: not-found.html"); return;
    }
    $row = $db->getRow(lq("SELECT e.id,t.type,t.class FROM #_TP_entities as e, #_TP_types as t WHERE e.id='$id' AND e.idtype=t.id"));
    if (!$row) die("Error. Type and Class unknown for this entity");
    
    $context['class'] = $class = $row['class'];
    $context['type'] = $row['type'];
    $context['identity'] = $id;

    require_once ("xmlfunc.php");
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
//	  die ("ERROR: $errormsg<br />".str_replace ("\n","<br>", htmlentities (@join ("", @file ($errfile)))));
//	@unlink("$tmpfile.err");
//      }	else {// PCLZIP library. 
//	require ("pclzip.lib.php");
//	$archive = new PclZip ($tmpfile.".zip");
//	$v_list = $archive->create (array($tmpfile.".xsd", $tmpfile.".xml"));
//	if ($v_list == 0) die ("ERROR : ".$archive->errorInfo(true));
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
    if ($context['view'])	{
      return "_ok";
    } else  {// "download"
      download ("", "$class-$id.xml", $contents);
    }
  }
  /**
   * Generate the XSD Schema for a class
   * Must have context['class'] given
   */
  function generateXSDAction (&$context, &$error) {
  	
    if (!$context['class'])
      die ('ERROR: no class given. Class attribute is required to generate XSD Schema');
    require('xmlfunc.php');
    //verif if the given class is OK
    global $db;
    $class = $context['class'];
    $row = $db->getOne (lq ("SELECT id FROM #_TP_classes WHERE class='$class'"));
    if (!$row) {
      header ("Location: not-found.html"); return;
    }
    $originalname = $context['site']. '-'.$context['class']. '-schema-xml.xsd';
    $ret = calculateXMLSchema ($context);
    download ("", $originalname, $ret);
  }
}
?>