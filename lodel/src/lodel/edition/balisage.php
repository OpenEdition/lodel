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

# listes des balises et du texte affiche dans les select

die("desuet");

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

if ($cancel) include ("abandon.php");

$row=get_tache($id);

include ($home."balises.php");

function getselect ($balise)

{
  static $iselect=0;
  global $line,$balises;
  foreach ($balises as $b => $txt) {
    if ($line) {
      $selected=($line[$iselect]==$b) ? "selected" : "";
    } else {
      $selected= ($balise==$b) ? "selected" : "";
    }
    $selectstr.="<option $selected value=\"$b\">$txt</option>";
  }
  return "<select name=\"line[".$iselect++."]\">".$selectstr."</select>";
}

function getline ()
{
  static $iline=0;
  return $iline++;
}

////////// HANDLER XML /////////
function startElement($parser, $name, $attrs) {
  global $r2rtag,$html,$txt,$intable;

  if ($name=="r2r:article") return;
  if (strpos($name,"r2r:")===0) { # balise R2R
    $r2rtag=substr($name,4);    return;
  }
  if ($name=="p" && !$intable) {
    $html.='</td></tr><tr><td valign=top>'.getselect(strtolower($r2rtag)).'</td><td>';
    $r2rtag="";
    $txt.='<!--r2rline='.getline().'-->';
  }
  if ($name=="table") $intable++;
  $balise="<$name";
  foreach ($attrs as $att => $val) {
    $balise.=" $att=\"$val\"";
  }
  $balise.=">";
  $html.=$balise; # pour la sortie html, on attend doit etre dans le body
  $txt.=$balise;
}

function endElement($parser, $name) {
  global $r2rtag,$html,$txt;

  if ($name=="r2r:article") return;
  if ($name=="table") $intable--;
  if (strpos($name,"r2r:")===0) { $r2rtag=""; return; }
  $html.="</$name>";
  $txt.="</$name>";
}

function characterHandler($parser,$data)

{
  global $html,$txt;
#  echo $data,"<br>\n";flush();
  $data=strtr($data,array("&"=>"&amp;","<" => "&lt;", ">" => "&gt;"));
  $html.=$data;
  $txt.=$data;
}
////////////// FIN HANDLER XML //////////


$xml_parser = xml_parser_create();
xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterHandler");
if (!($fp = fopen($row[fichier].".html", "r"))) {
  die("could not open XML input");
}

$html="";
$txt="";
$r2rtag="";

while ($data = fread($fp, 4096)) {
  if (!xml_parse($xml_parser, $data, feof($fp))) {
    echo sprintf("<br>XML error: %s at line %d <br><br>",
		xml_error_string(xml_get_error_code($xml_parser)),
		xml_get_current_line_number($xml_parser));
    echo str_replace("\n","<br>",htmlentities(join("",file($row[fichier]))));
    die("");
    
  }
}
xml_parser_free($xml_parser);

if (!writefile($row[fichier].".lined",$txt)) {}
// update la base de donnee
update_tache_etape($id,2); // etape 2

$context[fichier]=$html;


$context[id]=$id;

include ($home."calcul-page.php");
calcul_page($context,"balisage");


?>
