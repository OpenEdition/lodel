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

require("lodelconfig.php");
include ($home."func.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);
include ($home."connect.php");

$err="";
$report="";

if ($confirm) {
  $tables=gettables();
  do { // block de control
    foreach (array_keys($tables) as $table) {
      $result=mysql_query("SHOW CREATE TABLE $table") or die(mysql_error());
      if (!mysql_num_rows($result)) { $err="La requete \"SHOW CREATE TABLE $table\" ne renvoie rien"; break; }
      list($t,$create)=mysql_fetch_row($result);
      if (preg_match("/^\s*['`]status['`](.*),$/m",$create,$result)) {
	mysql_query("ALTER TABLE $table CHANGE status statut $result[1]") or die(mysql_error());
	$report.="change status en statut dans $table<br />\n";
      }
    }

    if ($tables["$GLOBALS[tp]users"]) {
      $fields=getfields("$GLOBALS[tp]users");
      if ($fields[email]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_users CHANGE email courriel VARCHAR(255);
');
	if ($err) break;
	$report.="Changement de email en courriel dans la table users<br>\n";
      }
    }


    if ($tables["$GLOBALS[tp]revues"]) {
      $err=mysql_query_cmds('
RENAME TABLE _PREFIXTABLE_revues TO _PREFIXTABLE_sites;
ALTER TABLE _PREFIXTABLE_sites CHANGE nom nom VARCHAR(255) NOT NULL;
ALTER TABLE _PREFIXTABLE_sites CHANGE rep rep VARCHAR(64) NOT NULL;
ALTER TABLE _PREFIXTABLE_sites ADD chemin VARCHAR(64) NOT NULL;
ALTER TABLE _PREFIXTABLE_sites ADD url TINYTEXT NOT NULL;
 ');
      if ($err) break;
      $report.="Changement de revues en sites<br>\n";
    }
    if ($tables["$GLOBALS[tp]session"]) {
      $fields=getfields("$GLOBALS[tp]session");
      if (!$fields["site"]) {
	$err=mysql_query_cmds("ALTER TABLE _PREFIXTABLE_session ADD site VARCHAR(64) BINARY NOT NULL;");
	if ($err) break;
	$report.="Changement de revue en site dans sessions<br>\n";
      }
    }
    // fini, faire quelque chose
  } while(0);
}

$context[erreur]=$err;
$context[report]=$report;

include ($home."calcul-page.php");
calcul_page($context,"transfer");



function mysql_query_cmd($cmd) 

{
  $cmd=str_replace("_PREFIXTABLE_","$GLOBALS[tp]",$cmd);
  if (!mysql_query($cmd)) { 
    $err="$cmd <font COLOR=red>".mysql_error()."</font><br>";
    return $err;
  }
  return FALSE;
}


// faire attention avec cette fontion... elle supporte pas les ; dans les chaines de caractere...
function mysql_query_cmds($cmds) 

{
  $sqlfile=str_replace("_PREFIXTABLE_","$GLOBALS[tp]",$cmds);
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if (!$sql) return;

  foreach ($sql as $cmd) {
    $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
    if ($cmd) {
      if (!mysql_query($cmd)) { 
	$err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
	break; // sort, ca sert a rien de continuer
      }
    }
  }
  return $err;
}

function gettables()

{
  // recupere la liste des tables
  $result=mysql_list_tables($GLOBALS[database]);
  $tables=array();
  while ($row = mysql_fetch_row($result)) $tables[$row[0]]=TRUE;
  return $tables;
}

function getfields($table)

{
  $fields = mysql_list_fields($GLOBALS[database],$table) or die (mysql_error());
  $columns = mysql_num_fields($fields);
  $arr=array();
  for ($i = 0; $i < $columns; $i++) {
    $fieldname=mysql_field_name($fields, $i);
    $arr[$fieldname]=1;
  }
  return $arr;
}


# pose des problemes... ca ecrase les anciens types
##function chargeinserts() 
##
##{
##  global $home,$report;
##      // charge l'install
##  $file=$home."../install/inserts-site.sql";
##  if (!file_exists($file)) {
##    $err="Le fichier $file n'existe pas !";
##    break;
##  }
##  $err=mysql_query_cmds(utf8_encode(join('',file($file))));
##  if ($err) return $err;
##  $report.="Création des tables<br>";
##  return FALSE;
##}

function create($table) 

{
  global $home,$report;
      // charge l'install
  $file=$home."../install/init-site.sql";
  if (!file_exists($file)) {
    $err="Le fichier $file n'existe pas !";
    break;
  }
  
  if (!preg_match ("/CREATE TABLE[\s\w]+_PREFIXTABLE_$table\s*\(.*?;/s",join('',file($file)),$result)) return "impossible de creer la table $table car elle n'existe pas dans le fichier init-site.sql<br>";
  
  $err=mysql_query_cmds($result[0]);
  if ($err) return $err;
  $report.="Création de la table $table<br>";
  return FALSE;
}

?>
