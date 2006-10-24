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

require_once 'unset_globals.php';

function checkfile ($filename,$error=0) {
  $text=file($filename);
  return checkstring($text);
}

function checkstring (&$text,$error=0) {

  $xml_parser = xml_parser_create();
  xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0) or die("Parser incorrect");
  if ($error) {
    xml_set_element_handler($xml_parser, "startElementCHECK", "endElementCHECK");
    xml_set_character_data_handler($xml_parser, "characterHandlerCHECK");
  }
  
  if (!xml_parse($xml_parser, $text)) {
    if (!$error) {
      echo '<h1>ERROR</h1><p>Le fichier produit n\'est pas XML. Veuillez svp poster un rapport de bug sur <a href="http://sourceforge.net/projects/lodel/">http://sourceforge.net/projects/lodel<a/>. Pensez &agrave; joindre le fichier.<br />En attendant que le problème soit résolu, essayer de changer le stylage de votre fichier.</p><p><hr /></p>';

      require($GLOBALS[home]."xmlfunc.php");
      $text=indentXML($text);
      checkstring ($text,1);
      return;
    } else {
      echo "<font color=red>";
      echo preg_replace("/\n/se","'<br><b>'.((\$GLOBALS[line]++)+2).'</b> '",htmlspecialchars(substr($text,xml_get_current_byte_index($xml_parser)-2)));
      echo "</font>\n";
      echo sprintf("<br><H2>XML erreur: %s ligne %d</H2>",
		   xml_error_string(xml_get_error_code($xml_parser)),
		   xml_get_current_line_number($xml_parser));
      echo "L'erreur se situe avant la zone rouge. Elle peut être due à une erreur bien au dessus la ligne donnée par le parser<br>";
      echo "<br>".htmlentities($text);

      xml_parser_free($xml_parser);
      return FALSE;
    }
  }
  xml_parser_free($xml_parser);
  return TRUE;
}


function characterHandlerCHECK ($parser,$data) {
  echo preg_replace("/\n/se","'<br><b>'.((\$GLOBALS[line]++)+2).'</b> '",$data);
}

function startElementCHECK($parser, $name, $attrs) {
  $balise="<$name";
  foreach ($attrs as $att => $val) {
    $balise.=" $att=\"$val\"";
  }
  $balise.=">";

  echo "<font color=blue>".htmlentities($balise)."</font>";
}

function endElementCHECK($parser, $name) {
  echo "<font color=blue>&lt;/$name&gt;</font>";
}

//function defaultHandler($parser,$data)
//
//{ if (substr($data,0,1)=="&") echo "#$data#"; }
//
?>
