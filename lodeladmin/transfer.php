<?php
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
    if ($tables["$GLOBALS[tp]revues"]) {
      $err=mysql_query_cmds("RENAME TABLE _PREFIXTABLE_revues TO _PREFIXTABLE_sites;");
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
