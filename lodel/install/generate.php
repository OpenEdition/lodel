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


require_once("generatefunc.php");
## to be launch from lodel/scripts

$file = "init-site.xml";


$table="";
$uniqueid=false;
$rights=array();
$uniquefields=array();


function startElement($parser, $name, $attrs)
{
  global $table,$fp,$varlist,$publicfields,$rights,$uniqueid,$currentunique,$uniquefields;

   switch($name) {
   case "table" :
   case "vtable" :
     $table=$attrs['name'];
     if (!$table) die("nom de table introuvable");
     $uniqueid=isset($attrs['uniqueid']);
     $rights=array();
     if ($attrs['writeright']) $rights[]="'write'=>LEVEL_".strtoupper($attrs['writeright']);
     if ($attrs['protectright']) $rights[]="'protect'=>LEVEL_".strtoupper($attrs['protectright']);

     $varlist=array();
     $publicfields=array();
     $uniquefields=array();
     break;
   case "column" :
     $varlist[]=$attrs['name'];
     if (!$attrs['edittype']) break;
     if ($attrs['label'] || $attrs['visibility']=="hidden") {
       $condition=$attrs['required']=="true" ? "+" : "";
       $publicfields[]='"'.$attrs['name'].'"=>array("'.$attrs['edittype'].'","'.$condition.'")';
     }
     break;
   case "unique" :
     $currentunique=$attrs['name'];
     $uniquefields[$currentunique]=array();
     break;
   case "unique-column" :
     $uniquefields[$currentunique][]=$attrs['name'];
     break;
   }
}

function endElement($parser, $name)
{
  global $table;

   switch($name) {
   case "table" :
     buildDAO();
     buildLogic();

     $table="";
     break;
   case "vtable" :
     //buildDAO(); // don't build DAO for virtual table
     buildLogic();

     $table="";
     break;
   }
}


$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
if (!($fp = fopen($file, "r"))) {
   die("could not open XML input");
}

while ($data = fread($fp, 4096)) {
   if (!xml_parse($xml_parser, $data, feof($fp))) {
       die(sprintf("XML error: %s at line %d",
                   xml_error_string(xml_get_error_code($xml_parser)),
                   xml_get_current_line_number($xml_parser)));
   }
}
xml_parser_free($xml_parser);




function buildDAO() {
  global $table,$uniqueid,$varlist,$rights;


  $text.='

/**
  * VO of table '.$table.'
  */

class '.$table.'VO {
';
      foreach ($varlist as $var) {
	$text.="   var $".$var.";\n";
      }
      $text.='
}

/**
  * DAO of table '.$table.'
  */

class '.$table.'DAO extends DAO {

   function '.$table.'DAO() {
       $this->DAO("'.$table.'",'.($uniqueid ? "true" : "false").');
       $this->rights=array('.join(",",$rights).');
   }
';


  $daofile="../scripts/dao/class.".$table.".php";
  if (file_exists($daofile)) {
    // unique fields
    $beginre='\/\/\s*begin\{definitions\}[^\n]+?\/\/';
    $endre='\n\s*\/\/\s*end\{definitions\}[^\n]+?\/\/';
    $file=file_get_contents($daofile);
    if (preg_match("/$beginre/",$file)) {
      replaceInFile($daofile,$beginre,$endre,$text);
      return;
    }
  }

// create the file
  $fp=fopen($daofile,"w");
  fwrite($fp,"<"."?php".getnotice().$text.'
}

?'.'>');
     fclose($fp);
}



function buildLogic()

{
  global $table,$publicfields,$uniquefields;

  $filename="../scripts/logic/class.".$table.".php";

  if (!file_exists($filename)) return;
  $file=file_get_contents($filename);

  // public fields
  $beginre='\/\/\s*begin\{publicfields\}[^\n]+?\/\/';
  $endre='\n\s*\/\/\s*end\{publicfields\}[^\n]+?\/\/';
  if (preg_match("/$beginre/",$file)) {
    $newpublicfields='   function _publicfields() {
     return array('.join(",\n                  ",$publicfields).");
             }";
    replaceInFile($filename,$beginre,$endre,$newpublicfields);
  }

  // unique fields

  $beginre='\/\/\s*begin\{uniquefields\}[^\n]+?\/\/';
  $endre='\n\s*\/\/\s*end\{uniquefields\}[^\n]+?\/\/';

  if (preg_match("/$beginre/",$file)) {
    if ($uniquefields) {
      $newunique='
    function _uniqueFields() {  return array(';
      foreach ($uniquefields as $unique) {
	$newunique.='array("'.join('","',$unique).'"),';
      }
      $newunique.=");  }";
    }
    replaceInFile($filename,$beginre,$endre,$newunique);
  }
}


function getnotice() {
  return '
/*
 *
 *  LODEL - Logiciel d\'Edition ELectronique.
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

 //
 // File generate automatically the '.date('Y-m-d').'.
 //
';
}

?>
