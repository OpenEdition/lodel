<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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
authenticate(LEVEL_ADMIN);

include_once ($home."dbxml.php");
include_once ($home."connect.php");

// inscrit les types de publication
#if (!mysql_query("INSERT INTO typepublis (nom,tpl) VALUES ('chronologique','sommaire')")) echo "typepubli chronologique existe deja<br>";

// inscrit les types d'article
$typedocs=array("article"=>"article",
		"compte rendu"=>"cr",
		"note de lecture"=>"note",
		"chronique"=>"chronique");

foreach ($typedocs as $typedoc=>$tpl) {
  if (!mysql_query("INSERT INTO typedocs (nom,tpl) VALUES ('$typedoc','$tpl')")) echo "typedoc $typedoc existe deja<br>";
}

// inscrit les periodes

$periodes=array("ANTI"=>"Antiquité",
		"MA"=>"Moyen Age",
		"AR"=>"Ancien Régime",
		"XVIII"=>"XVIII",
		"XIX"=>"XIX",
		"XX"=>"XX",
		"AUJ"=>"Aujourd\'hui",
		"TOUT"=>"Tout");

$ordre=1;
foreach ($periodes as $abrev=>$periode) {
  $result=mysql_query("SELECT id FROM indexhs WHERE nom='$periode' AND type=".TYPE_PERIODE."") or die (mysql_error());
  if (!mysql_num_rows($result)) { // verifie que la periode n'existe pas
    mysql_query("INSERT INTO indexhs (abrev,nom,ordre,type,lang) VALUES ('$abrev','$periode','$ordre','".TYPE_PERIODE."','fr')") or die (mysql_error());
  }
  $ordre++;
}


echo "<br>";



$rep=$home."../r2r/tmptxt";

if ($cont>0) {
  $dbconv=fopen("../txt/dbconv","a");
} else {
  $dbconv=fopen("../txt/dbconv","w");
}

fputs($dbconv,"index/i-auteurs-complet.html\tauteurs-complet.html
index/i-auteurs-complet.html\tauteurs-complet.html
index/i-themes-court.html\tmots.html
index/i-mots.html\tmots.html
index/i-chrono-court.html\tchrono-court.html
index/i-chrono.html\tchrono.html
index/i-chrono-ANTI.html\tchrono.html
index/i-chrono-AR.html\tchrono.html
index/i-chrono-AUJ.html\tchrono.html
index/i-chrono-MA.html\tchrono.html
index/i-chrono-TOUT.html\tchrono.html
index/i-chrono-XIX.html\tchrono.html
index/i-chrono-XX.html\tchrono.html
");


$d=dir($rep) or die ("impossible d'ouvrir $rep");
while ($entry=$d->read()) {
  if (!preg_match("/^\d+-\d+$/",$entry)) continue;
  $count++; if ($count<=$cont) continue;

  // creer le publication
  $id=enregistre_publication($entry);
  fputs($dbconv,"index/sommaire-$entry.html\tsommaire.html?id=$id\n");

  $d2=dir("$rep/$entry") or die ("impossible d'ouvrir $entry");
  $files=array();
  while ($entry2=$d2->read()) if (preg_match("/\.xml$/",$entry2)) array_push($files,$entry2);
  sort($files);
  $d2->close();
  foreach($files as $entry2) {
    $iddocument=r2renregistre("$rep/$entry/$entry2",$id);

    fputs($dbconv,"$entry/".str_replace(".txt.cl.xml",".html",$entry2)."\tdocument.html?id=$iddocument\n");
  }

#  if ($count>$cont+5) {
#    echo "<A HREF=\"r2renregistre.php?cont=$count\"><H1>continuer...</H1></A>";
#    break;
#  }
 echo "<A HREF=\"../edition\">Edition</A>";
}
$d->close();
fclose ($dbconv);



function r2renregistre ($filename,$id)

{
  echo "<u>$filename</u><br>\n";
  $text=join("",file($filename));
  $iddocument=enregistre(array(publication=>$id),$text);
  // copie le fichier en lieu sur !
  copy ($filename,"../txt/r2r-$iddocument.xml");
  return $iddocument;
}

function enregistre_publication ($dirname) {

  preg_match("/^(\d+)-(\d+)$/",$dirname,$result);
  $nom=$result[1]; $annee=$result[2];
  $ordre=$nom;
  while ($ordre>10) $ordre=intval($ordre/10);
  $ordre+=$annee*10;
  $nom="$annee-$nom";

  echo "$nom<br>";

  $result=mysql_query("SELECT id FROM publications WHERE nom='$nom'") or die (mysql_error());
  if (mysql_num_rows($result)>0) {
    list($id)=mysql_fetch_row($result);
    return $id;
  }

  mysql_query ("INSERT INTO publications (nom,ordre,type,statut,parent) VALUES ('$nom','$ordre','numero','-1','1')") or die (mysql_error());

  return mysql_insert_id();
}
