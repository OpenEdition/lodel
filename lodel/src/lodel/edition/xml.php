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



// charge le fichier xml et
require("siteconfig.php");
require ($home."auth.php");
authenticate(LEVEL_VISITEUR);
require_once ($home."func.php");
require_once ($home."textfunc.php");

$context[identite]=$context[id]=$id=intval($id);
$context[classe]="documents";

require_once($home."connect.php");
require_once($home."entitefunc.php");

$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,type FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
$context=array_merge($context,mysql_fetch_assoc($result));

//
// cherche s'il y a des documents annexe et combien
//
$result=mysql_query("SELECT count(*) FROM $GLOBALS[entitestypesjoin] WHERE idparent='$id' AND $GLOBAL[tp]entites.statut>0 AND type LIKE 'documentannexe-%'") or die (mysql_error());
list($context[documentsannexes])=mysql_fetch_row($result);

# calculate the page and store it into $contents

require_once($home."xmlfunc.php");

$contents=calculateXML($context);

if ($valid) {
  $tmpdir=tmpdir();
  $tmpfile=tempnam($tmpdir,"lodelxml_");
  writefile($tmpfile.".xml",$contents);
  $contents=calculateXMLSchema($context);
  writefile($tmpfile.".xsd",$contents);

#  if (!$zipcmd) die("ERROR: the zip command is required for validating XML using ServOO. Configure lodelconfig.php");
#  $errfile=$tmpfile.".err";
#  system($zipcmd." $tmpfile.zip $tmpfile.xsd $tmpfile.xml  1>&2 2>$errfile");
#  if (filesize($errfile)>0) die("ERROR: $errormsg<br />".str_replace("\n","<br>",htmlentities(@join("",@file($errfile)))));
#
#  @unlink("$tmpfile.xml");
#  @unlink("$tmpfile.xsd");
#  @unlink("$tmpfile.err");


  require($home."pclzip.lib.php");
  $archive=new PclZip($tmpfile.".zip");
  $v_list = $archive->create(array($tmpfile.".xsd",$tmpfile.".xml"));
  if ($v_list == 0) die("ERROR : ".$archive->errorInfo(true));
  @unlink("$tmpfile.xml");
  @unlink("$tmpfile.xsd");

  $cmds="DWL file1; XVL MSV; RTN convertedfile;";

  require ($home."serveurfunc.php");
  list($ret,$retval)=contact_servoo($cmds,array($tmpfile.".zip"));

  @unlink("$tmpfile.zip");

  $context[reponse]=str_replace("\n","<br />",htmlentities($ret));

  require_once ($home."calcul-page.php");
  calcul_page($context,"xml-valid");
} elseif ($view) {
  echo $contents;
  return;
} else {
  // "telechargement"
  $originalname="entite-$id.xml";

  download("",$originalname,$contents);
}






function loop_valeurs_des_champs($context,$funcname)

{
  global $erreur;

  $result=mysql_query("SELECT nom,type FROM $GLOBALS[tp]champs WHERE idgroupe='$context[id]' AND statut>0 ORDER BY ordre") or die(mysql_error());

  $haveresult=mysql_num_rows($result)>0;
  if ($haveresult && function_exists("code_before_$funcname")) 
    call_user_func("code_before_$funcname",$context);

  while ($row=mysql_fetch_assoc($result)) {
    $row[value]=$context[$row[nom]];
    call_user_func("code_do_$funcname",$row);
  }
  if ($haveresult && function_exists("code_after_$funcname")) 
    call_user_func("code_after_$funcname",$context);
}

function loop_valeurs_des_champs_require() { return array("id"); }





/**
 * Met le namespace xhtml pour toutes balises qui n'ont pas de namespace et supprime le namespace r2r.
 */

function namespace($text)
{
  $ns="xhtml";
    // place l'espace de nom sur toutes les balises xhtml
  $text = preg_replace(array("/<(\/?)(\w+(\s+[^>]*)?>)/", // add xhtml
			     "/(<\/?)r2r:/"),   // remove r2r
		       array("<\\1$ns:\\2",
			     "\\1"),$text);
	// puis place l'espace de nom sur les attributs
#	return preg_replace_callback("/(<($ns):\w+)((\s+\w+\s*=\s*\"[^\"]*\")+)/", "callback_ns_attributes", $text);
	return $text;
} 

/**
 * Fonction de callback permettant d'ajouter un espace de nom aux attributs d'une balise xhtml.
 */

function callback_ns_attributes($matches){
  $str = $matches[1];
  $ns = $matches[2];
  $arr=preg_split("/\"/",$matches[3]);
  for($i=0; $i<count($arr); $i+=2) { 
    if($arr[$i]){
      $attr = trim(str_replace("=","",$arr[$i]));
      if ($attr!="lang" && $attr!="space" && $attr!="base"
	  && $attr!="class" && $attr!="style") 
	$str.=" ".$ns.":".ltrim($attr)."=\"".$arr[$i+1]."\"";
      else $str.=$arr[$i]."\"".$arr[$i+1]."\"";
    }
  }
  return $str;
}




?>
