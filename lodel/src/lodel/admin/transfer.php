<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);
include ($home."func.php");

$err="";
$report="";

if ($confirm) {
  $tables=gettables();
  do { // block de control
    // est-ce que la table des indexhs exists ?
    // on renome
    if ($tables["$GLOBALS[prefixtable]indexls"]) { // il faut modifier et  renomer
      // ajoute les champs
      $err=mysql_query_cmds('
# renome mot en nom et retaille
ALTER TABLE _PREFIXTABLE_indexls CHANGE mot nom VARCHAR(255) NOT NULL;
# ajoute abrev
ALTER TABLE _PREFIXTABLE_indexls ADD abrev VARCHAR(15) NOT NULL;
# ajoute parent
ALTER TABLE _PREFIXTABLE_indexls ADD parent INT UNSIGNED DEFAULT \'0\' NOT NULL;
# ajoute l index sur nom
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_nom (nom);
# ajoute l index sur abrev
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_abrev (abrev);
# ajoute l index sur parent
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_parent (parent);
# change le nom de l index dans la table de liaison
ALTER TABLE _PREFIXTABLE_documents_indexls CHANGE idindexl identree INT UNSIGNED DEFAULT \'0\' NOT NULL;
# change le type des motcles permanents
UPDATE _PREFIXTABLE_indexls SET status=32, type=2 WHERE type=3;
# ok c est bon, on peut renomer
DROP TABLE IF EXISTS _PREFIXTABLE_entrees;
RENAME TABLE _PREFIXTABLE_indexls TO _PREFIXTABLE_entrees;
RENAME TABLE _PREFIXTABLE_documents_indexls TO _PREFIXTABLE_documents_entrees;
# positionne la langue correctement
UPDATE _PREFIXTABLE_entrees SET lang=\'fr\' WHERE lang=\'\';
');
      if ($err) break;
      $report.="Changement de nom et adaptation de la table indexls<br>\n";
    }

    // est-ce que la table des indexhs exists ?
    if ($tables["$GLOBALS[prefixtable]indexhs"]) { // fusion !
      lock_write("indexhs","entrees","documents_indexhs","documents_entrees");
      $result=mysql_query("SELECT * FROM $GLOBALS[prefixtable]indexhs") or die (mysql_error());

      //
      // reinsert pour obtenir les nouveaux id
      //
      $convid=array(); // contient la convertion de l'ancien id vers le nvx
      $oldparent=array(); // contient l'ancien parent en fonction du nvx id
      while ($row=mysql_fetch_assoc($result)) {
	myquote($row);
	if ($row[status]>-32) $row[status]=32; // periode et geo sont permanents
	if (!$row[lang]) $row[lang]="fr";
	$err=mysql_query_cmds("INSERT INTO _PREFIXTABLE_entrees (parent,nom,abrev,lang,type,ordre,status) VALUES ('$row[parent]','$row[nom]','$row[abrev]','$row[lang]','$row[type]','$row[ordre]','$row[status]');");
	if ($err) break 2;
	$newid=mysql_insert_id();
	$convid[$row[id]]=$newid;
	$oldparent[$newid]=$row[parent];
      }
      $report.="Importation de la table indexhs dans entrees (1/2)<br>\n";
      //
      //
      // modifie les parents
      //
      $cmds="";
      foreach($convid as $oldid=>$newid) { // parcourt les nvx champs
	if ($oldparent[$newid])
	  $cmds.="UPDATE _PREFIXTABLE_entrees SET parent=".$convid[$oldparent[$newid]]." WHERE id=$newid;";
      }
      $err=mysql_query_cmds($cmds);
      if ($err) break;
      $report.="Importation de la table indexhs dans indexs (2/2)<br>\n";

      // cherche le lien documents_indexhs
      //
      $cmds="";
      $result=mysql_query("SELECT * FROM $GLOBALS[prefixtable]documents_indexhs") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	// ajoute dans la table le lien
	$cmds.="INSERT INTO _PREFIXTABLE_documents_entrees (identree,iddocument) VALUES ('".$convid[$row[idindexhs]]."','$row[iddocument]');";
      }

#      die( preg_replace("/;/","<br>",$cmds));
      $err=mysql_query_cmds($cmds);
      if ($err) break;
      $report.="Importation de la table documents_indexhs dans documents_entrees<br>\n";

      //
      // on met a jour la langue au cas ou et on détruit l'ancienne table alors
      //
      $err=mysql_query_cmds('
UPDATE _PREFIXTABLE_entrees SET lang=\'fr\' WHERE lang=\'\';
DROP TABLE _PREFIXTABLE_documents_indexhs;
DROP TABLE _PREFIXTABLE_indexhs;
');
      unlock();
      if ($err) break;
      $report.="Destruction de la table indexhs et documents_indexhs<br>\n";
    }
    $tables=gettables(); // remet a jour la liste des tables;
#    if ($tables["$GLOBALS[prefixtable]typeindexs"]) { // il faut renommer cette table
#      $err=mysql_query_cmds('
#DROP TABLE IF EXISTS _PREFIXTABLE_typeentrees;
#RENAME TABLE _PREFIXTABLE_typeindexs TO _PREFIXTABLE_typeentrees;
#');
#      if ($err) break;
#      $report.="Conversion de typeindexs<br>\n";
#    }
#    if ($tables["$GLOBALS[prefixtable]indexs"]) { // il faut renommer cette table
#      $err=mysql_query_cmds('
#RENAME TABLE _PREFIXTABLE_indexs TO _PREFIXTABLE_entrees;
#ALTER TABLE _PREFIXTABLE_entrees CHANGE type	idtype		TINYINT DEFAULT 0 NOT NULL;
#ALTER TABLE _PREFIXTABLE_entrees ADD INDEX index_idtype (idtype);
#');
#      if ($err) break;
#      $report.="Conversion de indexs<br>\n";
#    }
#    if ($tables["$GLOBALS[prefixtable]documents_indexs"]) { // il faut renommer cette table
#      $err=mysql_query_cmds('
#RENAME TABLE _PREFIXTABLE_documents_indexs TO _PREFIXTABLE_documents_entrees;
#ALTER TABLE  _PREFIXTABLE_documents_entrees  CHANGE idindex identree		INT UNSIGNED DEFAULT 0 NOT NULL;
#ALTER TABLE _PREFIXTABLE_documents_entrees ADD INDEX  index_identree (identree);
#');
#      if ($err) break;
#      $report.="Conversion de documents_indexs<br>\n";
#    }
    $tables=gettables(); // remet a jour la liste des tables;
    if (!$tables["$GLOBALS[prefixtable]typeentrees"]) { // il faut creer cette table, et les autres...
      $insert=1;
      if ($err=create("typeentrees")) break;
    } else {
      $fields=getfields("$GLOBALS[prefixtable]typeentrees");
      if (!$fields[tplindex]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_typeentrees ADD	tplindex	TINYTEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout de la colonne tplindex a typeentree<br>\n";
	$insert=1;
      }
      if (!$fields[style]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_typeentrees ADD	style	TINYTEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout de la colonne style a typeentree<br>\n";
	$insert=1;
      }
    }
    if ($tables["$GLOBALS[prefixtable]documents"]) {
      // cherche les fields de documents 
      $fields=getfields("$GLOBALS[prefixtable]documents");
      if (!$fields[surtitre]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents ADD     surtitre	TEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout du champ surtitre dans documents<br>\n";
      }
    }
    if ($tables["$GLOBALS[prefixtable]documents_auteurs"]) {
      // cherche les fields de documents 
      $fields=getfields("$GLOBALS[prefixtable]documents_auteurs");
      if (!$fields[description]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD     description             TEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout du champ description dans documents_auteurs<br>\n";
      }
      $champs=array("prefix","fonction","affiliation","courriel");
      foreach ($champs as $champ) {
	if ($fields[$champ]) continue;
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD     '.$champ.'        TINYTEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout du champ $champ dans documents_auteurs<br>\n";
      }
    }
    if ($tables["$GLOBALS[prefixtable]auteurs"]) {
      // cherche les fields de documents 
      $fields=getfields("$GLOBALS[prefixtable]auteurs");
      $champs=array("prefix","fonction","affiliation","courriel");
      foreach ($champs as $champ) {
	if (!$fields[$champ]) continue;
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_auteurs DROP     '.$champ.';
');
	if ($err) break;
	$report.="Suppression du champ $champ dans auteurs<br>\n";
      }
    }
    $tables=gettables(); // remet a jour la liste des tables;
    // mise en place de personne
    if ($tables["$GLOBALS[prefixtable]auteurs"]) {
      $err=mysql_query_cmds('
DROP TABLE IF EXISTS _PREFIXTABLE_personnes;
RENAME TABLE _PREFIXTABLE_auteurs TO _PREFIXTABLE_personnes; 
');
      if ($err) break;
      $report.="Renomage de la table auteurs<br>\n";
    }
    if ($tables["$GLOBALS[prefixtable]documents_auteurs"]) {
      $err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents_auteurs CHANGE idauteur idpersonne INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD INDEX index_idpersonne (idpersonne);
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD idtype INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD INDEX index_idtype (idtype);
UPDATE _PREFIXTABLE_documents_auteurs SET idtype=1;
DROP TABLE IF EXISTS _PREFIXTABLE_documents_personnes;
RENAME TABLE _PREFIXTABLE_documents_auteurs TO _PREFIXTABLE_documents_personnes;
');
      if ($err) break;
      $report.="Modification de documents_auteurs et renommage<br>\n";
    }
    $tables=gettables(); // remet a jour la liste des tables;
    if (!$tables["$GLOBALS[prefixtable]typepersonnes"]) {
	if ($err=create("typepersonnes")) break; // recharge pour les typepersonnes
    }

    if ($insert) {
      chargeinserts();
      $report.="Recharge le fichier init-revue.sql<br>\n";
    }
    // fini, faire quelque chose
  } while(0);
}

$context[erreur]=$err;
$context[report]=$report;

include ($home."calcul-page.php");
calcul_page($context,"transfer");



function mysql_query_cmds($cmds) 

{
  $sqlfile=str_replace("_PREFIXTABLE_","$GLOBALS[tableprefix]",$cmds);
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if (!$sql) return;

  foreach ($sql as $cmd) {
    $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
    if ($cmd) {
      if (!mysql_query($cmd)) { 
	$err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
      }
    }
  }
  return $err;
}

function gettables()

{
  // recupere la liste des tables
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $tables=array();
  while ($row = mysql_fetch_row($result)) $tables[$row[0]]=TRUE;
  return $tables;
}

function getfields($table)

{
  $fields = mysql_list_fields($GLOBALS[currentdb],$table) or die (mysql_error());
  $columns = mysql_num_fields($fields);
  $arr=array();
  for ($i = 0; $i < $columns; $i++) {
    $fieldname=mysql_field_name($fields, $i);
    $arr[$fieldname]=1;
  }
  return $arr;
}



function chargeinserts() 

{
  global $home,$report;
      // charge l'install
  $file=$home."../install/inserts-revue.sql";
  if (!file_exists($file)) {
    $err="Le fichier $file n'existe pas !";
    break;
  }
  $err=mysql_query_cmds(utf8_encode(join('',file($file))));
  if ($err) return $err;
  $report.="Création des tables<br>";
  return FALSE;
}

function create($table) 

{
  global $home,$report;
      // charge l'install
  $file=$home."../install/init-revue.sql";
  if (!file_exists($file)) {
    $err="Le fichier $file n'existe pas !";
    break;
  }
  
  if (!preg_match ("/CREATE TABLE[\s\w]+_PREFIXTABLE_$table\s*\(.*?;/s",join('',file($file)),$result)) return "impossible de creer la table $table car elle n'existe pas dans le fichier init-revue.sql<br>";
  
  $err=mysql_query_cmds($result[0]);
  if ($err) return $err;
  $report.="Création de la table $table<br>";
  return FALSE;
}

?>
