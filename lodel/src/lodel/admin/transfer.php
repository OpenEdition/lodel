<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);
include ($home."func.php");

$err="";
$report="";

if ($confirm) {
  // recupere la liste des tables
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $tables=array();
  while ($row = mysql_fetch_row($result)) $tables[$row[0]]=TRUE;

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
ALTER TABLE _PREFIXTABLE_documents_indexls CHANGE idindexl idindex INT UNSIGNED DEFAULT \'0\' NOT NULL;
# change le type des motcles permanents
UPDATE _PREFIXTABLE_indexls SET status=32, type=2 WHERE type=3;
# ok c est bon, on peut renomer
RENAME TABLE _PREFIXTABLE_indexls TO _PREFIXTABLE_indexs;
RENAME TABLE _PREFIXTABLE_documents_indexls TO _PREFIXTABLE_documents_indexs;
# positionne la langue correctement
UPDATE _PREFIXTABLE_indexs SET lang=\'fr\' WHERE lang=\'\';
');
      if ($err) break;
      $report.="Changement de nom et adaptation de la table indexls<br>\n";
    }

    // est-ce que la table des indexhs exists ?
    if ($tables["$GLOBALS[prefixtable]indexhs"]) { // fusion !
      lock_write("indexhs","indexs","documents_indexhs","documents_indexs");
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
	$err=mysql_query_cmds("INSERT INTO _PREFIXTABLE_indexs (parent,nom,abrev,lang,type,ordre,status) VALUES ('$row[parent]','$row[nom]','$row[abrev]','$row[lang]','$row[type]','$row[ordre]','$row[status]');");
	if ($err) break 2;
	$newid=mysql_insert_id();
	$convid[$row[id]]=$newid;
	$oldparent[$newid]=$row[parent];
      }
      $report.="Importation de la table indexhs dans indexs (1/2)<br>\n";
      //
      //
      // modifie les parents
      //
      $cmds="";
      foreach($convid as $oldid=>$newid) { // parcourt les nvx champs
	if ($oldparent[$newid])
	  $cmds.="UPDATE _PREFIXTABLE_indexs SET parent=".$convid[$oldparent[$newid]]." WHERE id=$newid;";
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
	$cmds.="INSERT INTO _PREFIXTABLE_documents_indexs (idindex,iddocument) VALUES ('".$convid[$row[idindexhs]]."','$row[iddocument]');";
      }

#      die( preg_replace("/;/","<br>",$cmds));
      $err=mysql_query_cmds($cmds);
      if ($err) break;
      $report.="Importation de la table documents_indexhs dans documents_indexs<br>\n";

      //
      // on met a jour la langue au cas ou et on détruit l'ancienne table alors
      //
      $err=mysql_query_cmds('
UPDATE _PREFIXTABLE_indexs SET lang=\'fr\' WHERE lang=\'\';
DROP TABLE _PREFIXTABLE_documents_indexhs;
DROP TABLE _PREFIXTABLE_indexhs;
');
      unlock();
      if ($err) break;
      $report.="Destruction de la table indexhs et documents_indexhs<br>\n";
    }
    if ($tables["$GLOBALS[prefixtable]typeindexs"]) { // il faut renommer cette table
      $err=mysql_query_cmds('
RENAME TABLE _PREFIXTABLE_typeindexs TO _PREFIXTABLE_typeentrees;
');
      if ($err) break;
      $report.="Conversion de typeindexs<br>\n";
    }
    if ($tables["$GLOBALS[prefixtable]indexs"]) { // il faut renommer cette table
      $err=mysql_query_cmds('
RENAME TABLE _PREFIXTABLE_indexs TO _PREFIXTABLE_entrees;
ALTER TABLE _PREFIXTABLE_entrees CHANGE type	typeid		TINYINT DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_entrees ADD INDEX index_typeid (typeid);
');
      if ($err) break;
      $report.="Conversion de indexs<br>\n";
    }
    if ($tables["$GLOBALS[prefixtable]documents_indexs"]) { // il faut renommer cette table
      $err=mysql_query_cmds('
RENAME TABLE _PREFIXTABLE_documents_indexs TO _PREFIXTABLE_documents_entrees;
ALTER TABLE  _PREFIXTABLE_documents_entrees  CHANGE idindex identree		INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_entrees ADD INDEX  index_identree (identree);
');
      if ($err) break;
      $report.="Conversion de documents_indexs<br>\n";
    }
    if (!$tables["$GLOBALS[prefixtable]typeentrees"]) { // il faut creer cette table, et les autres...
      if (!rechargeinit()) break;
    } else {
      $fields=getfields("$GLOBALS[prefixtable]typeentrees");
      if (!$fields[tplindex]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_typeentrees ADD	tplindex	TINYTEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout de la colonne tplindex a typeentree<br>\n";
	if (!rechargeinit()) break; // recharge pour les typeentrees
      }
      if (!$fields[style]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_typeentrees ADD	style	TINYTEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout de la colonne style a typeentree<br>\n";
	if (!rechargeinit()) break; // recharge pour les typeentrees
      }
    }
    if ($tables["$GLOBALS[prefixtable]documents"]) {
      // cherche les fields de documents 
      $documentsfields=getfields("$GLOBALS[prefixtable]documents");
      if (!$documentsfields[surtitre]) {
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
      if (!$fields[prefix]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD     prefix             	VARCHAR(64) NOT NULL;
');
	if ($err) break;
	$report.="Ajout du champ prefix dans documents_auteurs<br>\n";
      }
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

function rechargeinit() 

{
  global $home,$report;
      // charge l'install
  $file=$home."../install/init-revue.sql";
  if (!file_exists($file)) {
    $err="Le fichier $file n'existe pas !";
    break;
  }
  $err=mysql_query_cmds(join('',file($file)));
  if ($err) return FALSE;
  $report.="Création des tables<br>";
  return TRUE;
}

?>
