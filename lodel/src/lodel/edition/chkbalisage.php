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

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTOR,NORECORDURL);
include ($home."func.php");

if ($cancel) include ("abandon.php");

$tache=gettask($idtache);

require_once ($home."balises.php");


// ajoute les balises "entrees"
require_once ($home."connect.php");
require_once($home."champfunc.php");
$result=mysql_query("SELECT style,title FROM $GLOBALS[tp]entrytypes WHERE status>0");
while (list($style,$title)=mysql_fetch_row($result)) { 
  $styles=decode_mlstyle($style);
  foreach($styles as $lang => $style) {
    if ($lang && $lang!="--") { // multi-language
      $balises[$style]=$title." ($lang)";
    } else { // single-language
      $balises[$style]=$title;
    }
  }
}


// ajoute les balises "personnes"
$result=mysql_query("SELECT style,title,styledescription,titledescription FROM $GLOBALS[tp]persontypes WHERE status>0");
while ($row=mysql_fetch_row($result)) { 
  $balises[$row[0]]=$row[1];
  $balises[$row[2]]=$row[3];
}

// ajoute les balises "documents"
  $result=mysql_query("SELECT $GLOBALS[tp]fields.style,$GLOBALS[tp]fields.title,$GLOBALS[tp]fields.type FROM $GLOBALS[tp]fields,$GLOBALS[tp]fieldgroups WHERE idgroup=$GLOBALS[tp]fieldgroups.id AND  class='documents' AND $GLOBALS[tp]fields.status>0") or die (mysql_error());
while (list($style,$title,$type)=mysql_fetch_row($result)) { 
  if ($type=="mltext") {
    require_once($home."champfunc.php");
    $styles=decode_mlstyle($style);
    foreach($styles as $lang => $style) {
      $balises[$style]=$title." ($lang)";
    }
  } else {
    $balises[$style]=$title;
  }
}


$textorig=$text=join("",file($tache[fichier]));

// cherche les sousbalises, retirent les de $balises et prepare le changement d'ecriture.
// une sous balises est definie par la presence d'une balise HTML (le caractere < en pratique) ou parce qu'elle est vide dans $balises
$srch=array(); $rpl=array();
foreach ($balises as $b=>$v) {
  if (!$v || strpos($v,"<")!==FALSE) { // sous balises
    array_push($srch,"/<r2r:$b>/si");array_push($rpl,$v); // balises ouvrante
    // balises fermantes, il faut inverser leur rank
    preg_match_all("/<(\w+)\b[^>]*>/",$v,$result,PREG_PATTERN_ORDER); // recupere les balises html (et seulement les balises)
    $v="";
    while ($html=array_pop($result[1])) $v.="</$html>";// met les dans l'rank inverse, et transforme les en balises fermantes
    array_push($srch,"/<\/r2r:$b>/si");array_push($rpl,$v);
    
    $balises[$b]=""; // supprime cette sousbalises (ca change rien normalement)
  }
}

array_push($srch,
#	     "/<\/?r2r:article>/",
#	     "/<r2r:([^>]+)>/e",
#	     "/<\/r2r:([^>]+)>/",
	     "/<r2rc:([^>]+)>/",
	     "/<\/r2rc:([^>]+)>/");

array_push($rpl,
#	     "",
#	     "'<tr valign=\"top\"><td class=\"chkbalisagetdbalise\">'.\$balises[strtolower('\\1')].'</td><td class=\"chkbalisagetdparagraphe\">'",	     
#	     "</td></tr>\n",
	     "<span title=\"\\1\" style=\"background-color: #F3F3F3;\">",
	     "</span>");

$text=preg_replace($srch,$rpl,$text);

// detecte les zones balises
$arr=preg_split("/<(\/?r2r):([^>]+)>/",traite_separateur($text),-1,PREG_SPLIT_DELIM_CAPTURE);
$level=0;

$tablescontent=array(); // contient les contenus des tables
// on s'assure que la partie principale est la premiere
$part="main";

// recupere les balises de debut et de fin
$startbalise="<r2r:$arr[2]>";
$endbalise="</r2r:".$arr[count($arr)-2].">";

$arr=array_slice($arr,3,-3); // enleve les trois premiers et les trois derniers correspondant aux balises r2r:document

while ($arr) {
  $subtext=array_shift($arr);
  if ($subtext=="r2r") {
    // balise ouvrante
    $level++;
    $bal=array_shift($arr);
#    if ($balisesdocumentassocie[$bal] || $bal==$styleforcss) { // change the part
    if ($balisesdocumentassocie[$bal]) { // change the part
      $part=$bal;
    } else { // others balises
      $bal=strtolower(trim($bal));
      $textbal=$balises[$bal];
      if ($bal=="invalidcharacters") $textbal='<div style="color: red">Le style contient des caract&egrave;res invalides</div>';
      if (!$textbal) $textbal='<div style="color: red">Style "'.$bal.'" non reconnu</div>';
      $tablescontent[$part].='<tr valign="top"><td class="chkbalisagetdbalise">'.$textbal.'</td><td class="chkbalisagetdparagraphe">';
    }
  } elseif ($subtext=="/r2r") {
    // balise fermante
    $level--;
    $bal=array_shift($arr);
#    if ($balisesdocumentassocie[$bal] || $bal==$styleforcss) { // end of a part
    if ($balisesdocumentassocie[$bal]) { // end of a part
      $part="main";
    } else {
      $tablescontent[$part].="</td></td>\n";
    }
  } elseif ($level==0 && trim($subtext)) {
    // pas bon, zone none reconnuee
    $tablescontent[$part].='<tr valign="top"><td class="chkbalisagetdbalise"><div style="color: red">PARAGRAPHE NON STYL&Eacute;</div></td><td class="chkbalisagetdparagraphe">'.$subtext."</td></tr>\n";
  } else {
    $tablescontent[$part].=$subtext;
  }
}

// backup the stylecss in the context
#$context[$styleforcss]=$tablescontent[$styleforcss];
#unset($tablescontent[$styleforcss]);

if (count($tablescontent)>1) { // ok il faut decouper le fichier

  $i=2;
  foreach (array_keys($balisesdocumentassocie) as $bal) {
    if (!preg_match_all("/<r2r:$bal>(.*?)<\/r2r:$bal>/s",$textorig,$results,PREG_PATTERN_ORDER)) continue;
    $text=$startbalise.join("",$results[1]).$endbalise; // construit un fichier propre
    $textorig=str_replace($results[0],"",$textorig); // supprime les zones

    $tache["fichierdecoupe$i"]="$tache[fichier]-$i";
    $tache["typedoc$i"]=$bal;
    $text=$startbalise.$text.$endbalise;
    writefile($tache["fichierdecoupe$i"],$text);
    $i++;
  }

  $tache["fichierdecoupe1"]="$tache[fichier]-1";
  writefile($tache[fichierdecoupe1],$textorig); // d'abord la partie principale

  // on est oblige de faire ca pour enregistrer en premier la partie principale pour recuperer l'id qui est le parent des s fichiers
#  die (htmlentities($texts[main]));

  
  updatetask_context($idtache,$tache);
}


if (preg_match("/<r2r:(titrenumero|nomnumero|typenumero)>/i",$text)) {
  $context[urlsuite]="importsommaire.php?idtache=$idtache";
} else {
  $context[urlsuite]="document.php?idtache=$idtache";
}


function loop_partie_fichier($context,$funcname)

{
  global $tablescontent,$balisesdocumentassocie;

  foreach ($tablescontent as $part => $context[tablecontent]) {
    if ($part=="main") continue;
    $context[part]=$balisesdocumentassocie[$part];
    call_user_func("code_do_$funcname",$context);
  }
  // partie principale maintenant

  $context[part]=count($texts)==1 ? "" : "main";
  $context[tablecontent]=$tablescontent[main];
  call_user_func("code_do_$funcname",$context);
}

$context[idtache]=$idtache;
require ($home."calcul-page.php");
calcul_page($context,"chkbalisage");




?>
