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

include_once ("$GLOBALS[home]/func.php");

function traite_tableau ($intext) {

	global $leveltable,$text;

	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
	xml_set_element_handler($xml_parser, "startElement_Tableau", "endElement_Tableau");
	xml_set_character_data_handler($xml_parser, "characterHandler_Tableau");

	$leveltable=0;
	$text="";

	  if (!xml_parse($xml_parser, $intext)) {
    		echo sprintf("<br>XML error: %s at line %d <br><br>",
		xml_error_string(xml_get_error_code($xml_parser)),
		xml_get_current_line_number($xml_parser));
 	  }
#	echo "->",htmlentities($text);

	xml_parser_free($xml_parser);


	return $text;

//# cherche les tables
//	  $table=array();
//	  $tagdebut="<TABLE"; $lendebut=strlen($tagdebut);
//	  $tagfin="</TABLE>"; $lenfin=strlen($tagfin);
//	  $offset=0;
//	  while($offset=strpos($text,$tagdebut,$offset)) {
//		  $table[$offset]="open";
//		  $offset+=$lendebut;
//	  }
//	  #recherche la fin
//	  $offset=0;
//	  while($offset=strpos($text,$tagfin,$offset)) {
//		  $table[$offset]="close";
//		  $offset+=$lenfin;
//	  }
//	  # cherche les tables exterieures
//	  $table=sort($table);
//	  while (list ($pos,$tag)=each($table)) {
//		  $level=1;
//		  do {
//			  list($fin,$tag)=each($table);
//			  if ($tag=="open") { $level++;} else { $level--;}
//		  } while ($level==0);
//		  # cherche les balises r2r
//		  $intable=substr($text,$pos,$pos-$fin);
//		  preg_match_all("/<r2r:(\w+)\b[^>]*>/i",$intable,$result,PREG_SET_ORDER);
//		  if (count(array_unique($result[1]))==1) { # un seul type de balise a l'interieur
//			  $intable=preg_replace("/<\/?r2r:(\w+)\b[^>]*>/i","",$intable);
//		  }
//	  }
}


////////// HANDLER XML POUR TRAITER LES TABLEAUX /////////
// utilise les globals $leveltable, $texttable et $r2rtags

function startElement_Tableau($parser, $name, $attrs) {
  global $r2rtags,$text,$texttable,$leveltable;

  if ($name=="table") {
	if ($leveltable==0) {
  		$r2rtags=array();
		$texttable="";
	}
	$leveltable++;
  }
  if (strpos($name,"r2r:")===0) $r2rtags[$name]=1;
  
  $balise="<$name";
  foreach ($attrs as $att => $val) {
    $balise.=" $att=\"".translate_xmldata($val)."\"";
  }
  $balise.=">";
  if ($leveltable) {
	$texttable.=$balise;
  } else {
	$text.=$balise;
  }
}

function endElement_Tableau($parser, $name) {
  global $r2rtags,$text,$texttable,$leveltable;

  if ($leveltable) {
	$texttable.="</$name>";
  } else {
    $text.="</$name>";
  }

  if ($name=="table") {
	$leveltable--;
	if ($leveltable==0) {
		if (count($r2rtags)==1) {
			$tag=key($r2rtags);
			$text.="<$tag>".preg_replace("/<\/?r2r:[^>]+>/i","",$texttable)."</$tag>";
		}
	}
  }
}

function characterHandler_Tableau($parser,$data)

{
  global $leveltable,$text,$texttable;

  $data=translate_xmldata($data);
  if ($leveltable) {
	$texttable.=$data;
  } else {
  	$text.=$data;
  }
}


////////////// FIN HANDLER XML //////////

?>
