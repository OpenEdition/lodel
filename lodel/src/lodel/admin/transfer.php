<?php
require("siteconfig.php");
include ($home."auth.php");
#authenticate(LEVEL_ADMINLODEL);
authenticate();
include ($home."func.php");

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

    // changement du niveau des utilisateurs

    //Ancienne valeur
    //(LEVEL_VISITEUR,1);
    //(LEVEL_REDACTEUR,2);
    //(LEVEL_EDITEUR,4);
    //(LEVEL_ADMIN,32);

    $err=mysql_query_cmds('
UPDATE _PREFIXTABLE_users SET privilege=10 where privilege=1;
UPDATE _PREFIXTABLE_users SET privilege=20 where privilege=2;
UPDATE _PREFIXTABLE_users SET privilege=30 where privilege=4;
UPDATE _PREFIXTABLE_users SET privilege=40 where privilege=32;
');
    if ($err) break;

    if ($tables["$GLOBALS[tp]users"]) {
      $fields=getfields("users");
      if ($fields[email]) {
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_users CHANGE email courriel VARCHAR(255);
');
	if ($err) break;
	$report.="Changement de email en courriel dans la table users<br>\n";
      }
    }

    // est-ce que la table des indexhs exists ?
    // on renome
    if ($tables["$GLOBALS[tp]indexls"]) { // il faut modifier et  renomer

      // converti toutes la base en UTF-8
      // c'est a cet endroit, mais ca pourrait etre ailleurs
      $report.=isotoutf8();


      // ajoute les champs
      $err=mysql_query_cmds('
# renome mot en nom et retaille
ALTER TABLE _PREFIXTABLE_indexls CHANGE mot nom VARCHAR(255) NOT NULL;
# ajoute abrev
ALTER TABLE _PREFIXTABLE_indexls ADD abrev VARCHAR(15) NOT NULL;
# ajoute parent
ALTER TABLE _PREFIXTABLE_indexls ADD idparent INT UNSIGNED DEFAULT \'0\' NOT NULL;
# ajoute l index sur nom
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_nom (nom);
ALTER TABLE _PREFIXTABLE_indexls DROP INDEX index_mot;
# ajoute l index sur abrev
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_abrev (abrev);
# ajoute l index sur parent
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_idparent (idparent);
# change le nom de l index dans la table de liaison
ALTER TABLE _PREFIXTABLE_documents_indexls CHANGE idindexl identree INT UNSIGNED DEFAULT \'0\' NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_indexls CHANGE iddocument identite INT UNSIGNED DEFAULT \'0\' NOT NULL;
# change type en idtype
ALTER TABLE _PREFIXTABLE_indexls CHANGE type	idtype		TINYINT DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_indexls ADD INDEX index_idtype (idtype);
# change le type des motcles permanents
UPDATE _PREFIXTABLE_indexls SET statut=32, idtype=2 WHERE idtype=3;
# change lang en langue
ALTER TABLE _PREFIXTABLE_indexls CHANGE lang langue CHAR(2) NOT NULL;
# positionne la langue correctement
UPDATE _PREFIXTABLE_indexls SET langue=\'fr\' WHERE langue=\'\';
# ok c est bon, on peut renomer

DROP TABLE IF EXISTS _PREFIXTABLE_entrees;
RENAME TABLE _PREFIXTABLE_indexls TO _PREFIXTABLE_entrees;
DROP TABLE IF EXISTS _PREFIXTABLE_entites_entrees;
RENAME TABLE _PREFIXTABLE_documents_indexls TO _PREFIXTABLE_entites_entrees;
');
      if ($err) break;
      $report.="Changement de nom et adaptation de la table indexls<br>\n";
    }

    // est-ce que la table des indexhs exists ?
    if ($tables["$GLOBALS[tp]indexhs"]) { // fusion !
      lock_write("indexhs","entrees","documents_indexhs","entites_entrees");
      $result=mysql_query("SELECT * FROM $GLOBALS[tp]indexhs") or die (mysql_error());

      //
      // reinsert pour obtenir les nouveaux id
      //
      $convid=array(); // contient la convertion de l'ancien id vers le nvx
      $oldparent=array(); // contient l'ancien parent en fonction du nvx id
      while ($row=mysql_fetch_assoc($result)) {
	myquote($row);
	if ($row[statut]>-32) $row[statut]=32; // periode et geo sont permanents
	if (!$row[lang]) $row[lang]="fr";
	$err=mysql_query_cmds("INSERT INTO _PREFIXTABLE_entrees (idparent,nom,abrev,langue,idtype,ordre,statut) VALUES ('$row[parent]','$row[nom]','$row[abrev]','$row[lang]','$row[type]','$row[ordre]','$row[statut]');");
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
	  $cmds.="UPDATE _PREFIXTABLE_entrees SET idparent=".$convid[$oldparent[$newid]]." WHERE id=$newid;";
      }
      $err=mysql_query_cmds($cmds);
      if ($err) break;
      $report.="Importation de la table indexhs dans indexs (2/2)<br>\n";

      // cherche le lien documents_indexhs
      //
      $cmds="";
      $result=mysql_query("SELECT * FROM $GLOBALS[tp]documents_indexhs") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	// ajoute dans la table le lien
	$cmds.="INSERT INTO _PREFIXTABLE_entites_entrees (identree,identite) VALUES ('".$convid[$row[idindexhs]]."','$row[iddocument]');";
      }

#      die( preg_replace("/;/","<br>",$cmds));
      $err=mysql_query_cmds($cmds);
      if ($err) break;
      $report.="Importation de la table documents_indexhs dans entites_entrees<br>\n";

      //
      // on met a jour la langue au cas ou et on dÈtruit l'ancienne table alors
      //
      $err=mysql_query_cmds('
UPDATE _PREFIXTABLE_entrees SET langue=\'fr\' WHERE langue=\'\';
DROP TABLE _PREFIXTABLE_documents_indexhs;
DROP TABLE _PREFIXTABLE_indexhs;
');
      unlock();
      if ($err) break;
      $report.="Destruction de la table indexhs et documents_indexhs<br>\n";
    }
    $tables=gettables(); // remet a jour la liste des tables;
    if (!$tables["$GLOBALS[tp]typeentrees"]) { // il faut creer cette table, et les autres...
      if ($err=create("typeentrees")) break;
      $err=mysql_query_cmds("
INSERT INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES('1','periode','p√©riode','periode','chrono','chronos','1','0','0','1','ordre','2');
INSERT INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES('4','geographie','g√©ographie','geographie','geo','geos','1','0','0','1','ordre','3');
INSERT INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES('2','motcle','mot cl√©','motcle','mot','mots','1','1','1','0','nom','1');
");
	$report.="Creation de typeentrees<br>\n";
	if ($err) break;
    }
    if ($tables["$GLOBALS[tp]documents"]) {
      // cherche les fields de documents 
      $fields=getfields("documents");
      $champs=array("surtitre","commentaire","lien");
      foreach ($champs as $champ) {
	if ($fields[$champ]) continue;
	$err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents ADD     '.$champ.'        TINYTEXT NOT NULL;
');
	if ($err) break;
	$report.="Ajout de champs dans documents<br>\n";
      }
  }
   if ($tables["$GLOBALS[tp]documents_auteurs"]) {
      // cherche les fields de documents 
      $fields=getfields("documents_auteurs");
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
    if ($tables["$GLOBALS[tp]auteurs"]) {
      // cherche les fields de documents 
      $fields=getfields("auteurs");
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
    if ($tables["$GLOBALS[tp]auteurs"]) {
      $err=mysql_query_cmds('
DROP TABLE IF EXISTS _PREFIXTABLE_personnes;
RENAME TABLE _PREFIXTABLE_auteurs TO _PREFIXTABLE_personnes; 
');
      if ($err) break;
      $report.="Renomage de la table auteurs<br>\n";
    }
    if ($tables["$GLOBALS[tp]documents_auteurs"]) {
      $err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_documents_auteurs CHANGE idauteur idpersonne INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_auteurs CHANGE iddocument identite INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD INDEX index_idpersonne (idpersonne);
ALTER TABLE _PREFIXTABLE_documents_auteurs DROP INDEX index_idauteur;
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD INDEX index_identite (identite);
ALTER TABLE _PREFIXTABLE_documents_auteurs DROP INDEX index_iddocument;
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD idtype INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_documents_auteurs ADD INDEX index_idtype (idtype);
UPDATE _PREFIXTABLE_documents_auteurs SET idtype=1;
DROP TABLE IF EXISTS _PREFIXTABLE_entites_personnes;
RENAME TABLE _PREFIXTABLE_documents_auteurs TO _PREFIXTABLE_entites_personnes;
');
      if ($err) break;
      $report.="Modification de documents_auteurs et renommage<br>\n";
    }
    $tables=gettables(); // remet a jour la liste des tables;
    if (!$tables["$GLOBALS[tp]typepersonnes"]) {
	if ($err=create("typepersonnes")) break; // recharge pour les typepersonnes
      $err=mysql_query_cmds("
REPLACE INTO _PREFIXTABLE_typepersonnes (id,type,titre,style,titredescription,styledescription,tpl,tplindex,statut,ordre) VALUES('1','auteur','auteur','auteur','description de l''auteur','descriptionauteur','auteur','auteurs','1','1');
");
      if ($err) break;
      $report.="Creation de typepersonnes<br>\n";
    }
    if ($tables["$GLOBALS[tp]typepublis"]) {
      $err=mysql_query_cmds('
ALTER TABLE _PREFIXTABLE_typepublis CHANGE nom	type		VARCHAR(64) NOT NULL;
ALTER TABLE _PREFIXTABLE_typepublis ADD classe		VARCHAR(64) NOT NULL;
ALTER TABLE _PREFIXTABLE_typepublis ADD tplcreation	TINYTEXT NOT NULL;
ALTER TABLE _PREFIXTABLE_typepublis ADD ordre		INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE _PREFIXTABLE_typepublis ADD	titre	        VARCHAR(255) NOT NULL;
UPDATE _PREFIXTABLE_typepublis SET classe=\'publications\', titre=type, tplcreation=\'publication\';
ALTER TABLE _PREFIXTABLE_typepublis CHANGE titre	titre	        VARCHAR(255) NOT NULL;
ALTER TABLE _PREFIXTABLE_typepublis CHANGE tpledit 	tpledition	TINYTEXT NOT NULL;
DROP TABLE IF EXISTS _PREFIXTABLE_types;
RENAME TABLE _PREFIXTABLE_typepublis TO _PREFIXTABLE_types;
');
      if ($err) break;
      $report.="Transformation de typepublis en types<br>";
    }
    if ($tables["$GLOBALS[tp]typedocs"]) {
      $err=mysql_query_cmds('
INSERT INTO _PREFIXTABLE_types (type,titre,tpl,statut,classe,tplcreation)
          SELECT nom,nom,tpl,statut,"documents","chargement" FROM _PREFIXTABLE_typedocs;
DROP TABLE IF EXISTS _PREFIXTABLE_typedocs;
');
      if ($err) break;
      $report.="Insertions de typedocs dans types<br>";
    }

    if (!$tables["$GLOBALS[tp]entites"]) {
      # verifie l'integrite du type de documents
      $result=mysql_query("SELECT $GLOBALS[tp]documents.id,$GLOBALS[tp]documents.type FROM  $GLOBALS[tp]documents LEFT JOIN $GLOBALS[tp]types ON $GLOBALS[tp]documents.type=$GLOBALS[tp]types.type   WHERE  $GLOBALS[tp]types.id IS NULL") or die (mysql_error());
      if (mysql_num_rows($result)) {
	$err="<fond color=\"red\">Des documents ont un type impossible &agrave; trouver dans la tables des types</font><br>";
	while ($row=mysql_fetch_assoc($result)) {
	  $err.="documents \"$row[id]\" type: \"$row[type]\"<br>\n";
	}
	break;
      }
      # verifie l'integrite du type de publications
      $result=mysql_query("SELECT $GLOBALS[tp]publications.id,$GLOBALS[tp]publications.type FROM  $GLOBALS[tp]publications LEFT JOIN $GLOBALS[tp]types ON $GLOBALS[tp]publications.type=$GLOBALS[tp]types.type   WHERE $GLOBALS[tp]types.id IS NULL") or die (mysql_error());
      if (mysql_num_rows($result)) {
	$err="<fond color=\"red\">Des publications ont un type impossible &agrave; trouver dans la tables des types</font><br>";
	while ($row=mysql_fetch_assoc($result)) {
	  $err.="publications \"$row[id]\" type: \"$row[type]\"<br>\n";
	}
	break;
      }
$err=mysql_query_cmds(' 
	INSERT INTO _PREFIXTABLE_types (type, titre, tpl, tpledition, tplcreation, ordre, classe) VALUES ("regroupement-documentsannexes","regroupement de documents annexes", "", "", "creation-regroupement", 0, "publications"); 
	UPDATE _PREFIXTABLE_types SET titre="s√©rie lin√©aire", tplcreation="creation-serie" WHERE type="serie_lineaire"; 
	UPDATE _PREFIXTABLE_types SET titre="s√©rie hi√©rarchique", tplcreation="creation-serie" WHERE type="serie_hierarchique"; 
	UPDATE _PREFIXTABLE_types SET titre="regroupement", tplcreation="creation-regroupement", tpl="", tpledition="" WHERE type="regroupement"; 
	UPDATE _PREFIXTABLE_types SET titre="num√©ro", tplcreation="creation-numero" WHERE type="numero"; 
	UPDATE _PREFIXTABLE_types SET type="rubrique", tpledition="edition-rubrique", tplcreation="creation-rubrique", titre="rubrique" WHERE type="theme";
'); 
if($err) break; 
$report.="
        Ajout du type regroupement-documentsannexes<br />
        Transformation des titres des types<br />
        Suppression des Èventuelles valeurs de tpl et tpledition pour un regroupement<br />
        Nouveau tpl de creation pour chacun des types<br />
";

      // recupere l'id max des documents
      $result=mysql_query("SELECT max(id) FROM $GLOBALS[tp]documents") or die(mysql_error());
      list($offset)=mysql_fetch_row($result);
      $offset++;

      // ok, on cree la table entites maintenant
      if ($err=create("entites")) break;
      // on ajoute l'idparent pour pouvoir faire le traitement tranquillement ensuite
      $err=mysql_query_cmds('
INSERT INTO _PREFIXTABLE_entites (id,idparent,idtype,nom,iduser,groupe,ordre,statut)
         SELECT _PREFIXTABLE_documents.id,_PREFIXTABLE_documents.publication+'.$offset.',_PREFIXTABLE_types.id,_PREFIXTABLE_documents.titre,user,1,_PREFIXTABLE_documents.ordre,_PREFIXTABLE_documents.statut FROM _PREFIXTABLE_documents,_PREFIXTABLE_types WHERE _PREFIXTABLE_types.type=_PREFIXTABLE_documents.type;
ALTER TABLE _PREFIXTABLE_documents CHANGE id identite	INT UNSIGNED DEFAULT 0 NOT NULL  UNIQUE;
UPDATE _PREFIXTABLE_publications SET parent=parent+'.$offset.' WHERE parent>0;
INSERT INTO _PREFIXTABLE_entites (id,idparent,idtype,nom,groupe,ordre,statut)
         SELECT _PREFIXTABLE_publications.id+'.$offset.',_PREFIXTABLE_publications.parent,_PREFIXTABLE_types.id,_PREFIXTABLE_publications.nom,1,_PREFIXTABLE_publications.ordre,_PREFIXTABLE_publications.statut FROM _PREFIXTABLE_publications,_PREFIXTABLE_types WHERE _PREFIXTABLE_types.type=_PREFIXTABLE_publications.type;
ALTER TABLE _PREFIXTABLE_publications CHANGE id identite	INT UNSIGNED DEFAULT 0 NOT NULL  UNIQUE;
UPDATE _PREFIXTABLE_publications SET identite=identite+'.$offset.';
');
      if ($err) break;
      // ok, on s'occupe des documents annexes maintenant.

      // on commence par les types de document annexes
      $err=mysql_query_cmds("
INSERT INTO _PREFIXTABLE_types (type,titre,tplcreation,ordre,classe,statut) VALUES('documentannexe-lienfichier','sur un fichier','documentannexe-lienfichier','2','documents',32);
INSERT INTO _PREFIXTABLE_types (type,titre,tplcreation,ordre,classe,statut) VALUES('documentannexe-liendocument','sur un document interne','documentannexe-liendocument','3','documents',32);
INSERT INTO _PREFIXTABLE_types (type,titre,tplcreation,ordre,classe,statut) VALUES('documentannexe-lienpublication','sur une publication interne','documentannexe-lienpublication','5','documents',32);
INSERT INTO _PREFIXTABLE_types (type,titre,tplcreation,ordre,classe,statut) VALUES('documentannexe-lienexterne','sur un site externe','documentannexe-lien','6','documents',32);
");
      if ($err) break;

      // on construit le idtype de conversion
      $result=mysql_query("SELECT id,type FROM $GLOBALS[tp]types WHERE type LIKE 'documentannexe-%'") or die (mysql_error());
      $idtypes=array();
      while ($row=mysql_fetch_assoc($result)) {
	$type=str_replace("documentannexe-","",$row[type]);
	#echo $type," ",$id,"<br>";
	$idtypes[$type]=$row[id];
      }
      // ok, on s'occupe maintenant de l'importation des documentsannexes
      $result=mysql_query("SELECT * FROM $GLOBALS[tp]documentsannexes") or die(mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	myquote($row);
	$idtype=$idtypes[$row[type]];
	if (!$idtype) { $err="Probleme de detection du type de documentsannexes<br>"; break; }
	$err=mysql_query_cmd("INSERT INTO _PREFIXTABLE_entites (idparent,idtype,nom,ordre,statut) VALUES ('$row[iddocument]','$idtype','$row[titre]','$row[ordre]','$row[statut]')");
	if ($err) break;
	$id=mysql_insert_id();
	$err=mysql_query_cmd("INSERT INTO _PREFIXTABLE_documents (identite,titre,commentaire,lien) VALUES ('$id','$row[titre]','$row[commentaire]','$row[lien]')");
	if ($err) break;
      }
      if ($err) break;
      $err=mysql_query_cmd("DROP TABLE _PREFIXTABLE_documentsannexes");
      if ($err) break;
      $report.="Conversion des documentsannexes en documents<br>";

      // ok, on cree la table relations maintenant
      if ($err=create("relations")) break;
      // on parcourt la structure de facon recurrente maintenant
      require_once($home."managedb.php");
      $idparents=array(0);
      do {
	$idlist=join(",",$idparents);
	// cherche les fils de idparents
	$result=mysql_query("SELECT id,idparent FROM $GLOBALS[tp]entites WHERE idparent IN ($idlist) $critere") or die(mysql_error());

	$idparents=array();
	while ($row=mysql_fetch_assoc($result)) {
	  array_push ($idparents,$row[id]);
	  creeparente($row[id],$row[idparent]);
	}
      } while ($idparents);
      $report.="Creation de relations et calcul des parentes<br>";
      $fields=getfields("documents");
      foreach (array("type","maj","statut","ordre","groupe","user","publication") as $f) {
	if ($fields[$f]) {
	  $err=mysql_query_cmds("ALTER TABLE _PREFIXTABLE_documents DROP $f;");
	}
      }
      $fields=getfields("publications");
      foreach (array("id","parent","type","maj","statut","ordre","groupe","nom") as $f) {
	if ($fields[$f]) {
	  $err=mysql_query_cmds("ALTER TABLE _PREFIXTABLE_publications DROP $f;");
	}
      }
      if ($err) break;
      $report.="Transfert des documents dans entites offset=$offset<br>";
    }

    $fields=getfields("publications");
    if ($fields[directeur]) { // import les directeurs
      include_once($home."entitefunc.php");

      // cherche le type
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]typepersonnes WHERE type='auteur'") or die(mysql_error());
      if (!mysql_num_rows($result)) die ("type inconnu ?");
      list($idtype)=mysql_fetch_row($result);

	//
      $result=mysql_query("SELECT identite,directeur,statut FROM $GLOBALS[tp]publications,$GLOBALS[tp]entites WHERE id=identite AND directeur!=''") or die(mysql_error());

      while (list($id,$directeur,$statut)=mysql_fetch_row($result)) {
	$lcontext=array();
	list($lcontext[prefix][$idtype][1],
	     $lcontext[prenom][$idtype][1],
	     $lcontext[nomfamille][$idtype][1])=
	  extractnom($directeur);
	enregistre_personnes ($lcontext,$id,$statut,FALSE);
      }
      $err=mysql_query_cmds("ALTER TABLE _PREFIXTABLE_publications DROP directeur;");
      $report.="Importation des directeurs<br>";
    }


    if (!$tables["$GLOBALS[tp]groupes"]) {
	if ($err=create("groupes")) break;
	$err=mysql_query_cmd("REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous')");
	if ($err) break;
    }

    if (!$tables["$GLOBALS[tp]typeentites_typepersonnes"]) {
	if ($err=create("typeentites_typepersonnes")) break;
    }

    if (!$tables["$GLOBALS[tp]typeentites_typeentrees"]) {
	if ($err=create("typeentites_typeentrees")) break;
    }
    if (!$tables["$GLOBALS[tp]typeentites_typeentites"]) {
	if ($err=create("typeentites_typeentites")) break;
    }

    if (!$tables["$GLOBALS[tp]groupesdechamps"]) {
      if ($err=create("groupesdechamps")) break;
      if ($err=chargeinserts("groupesdechamps")) break;
    }
    if (!$tables["$GLOBALS[tp]champs"]) {
      if ($err=create("champs")) break;
      if ($err=chargeinserts("champs")) break;

      $fields[documents]=getfields("documents");
      $fields[publications]=getfields("publications");

      require_once($home."champfunc.php");
      
      // creer les champs dans les tables correspondantes
      $result=mysql_query("SELECT $GLOBALS[tp]champs.nom,type,classe FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND  $GLOBALS[tp]champs.statut>0") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	$alter=$fields[$row[classe]][$row[nom]] ? "MODIFY" : "ADD";
	mysql_query("ALTER TABLE $GLOBALS[tp]$row[classe] $alter $row[nom] ".$sqltype[$row[type]]) or die (mysql_error());
      }

      // importe dans les documents
      $fields=getfields("documents");
      unset($fields[identite]); // enleve identite
      unset($fields[meta]); // enleve meta
      $result=mysql_query("SELECT identite FROM $GLOBALS[tp]documents") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	$filename="../txt/r2r-$row[identite].xml";
	if (!file_exists($filename)) { $err.="Le fichier $filename n'existe pas<br>"; continue; }
	$file=utf8_encode(file_get_contents($filename));
	$updates=array();
	foreach(array_keys($fields) as $field) {
	  if ($field=="resume") {
	    if (preg_match_all("/<R2R:$field\s*(?:lang=\"(\w+)\")>(.*?)<\/R2R:$field>/is",$file,$matchs,PREG_SET_ORDER)) {
	      $resume="";
	      foreach ($matchs as $match) {
		$resume.="<r2r:ml lang=\"$match[1]\">$match[2]</r2r:ml>";
	      }
	      array_push($updates," resume='".addslashes($resume)."'");
	    }

	  } else { // other fields
	    if (preg_match("/<R2R:$field\s*(?:lang=\"fr\")?>(.*?)<\/R2R:$field>/is",$file,$match)) {
	      array_push($updates," $field='".addslashes($match[1])."'");
	    }
	  }
	}
	// copie le fichier original rtf
	$rtffile="r2r-$row[identite].rtf";

	if (file_exists("../rtf/$rtffile")) {
	  array_push($updates," fichiersource='$rtffile'");
	  $dest="../sources/entite-$row[identite].source";
	  if (!copy("../rtf/$rtffile",$dest)) die("probleme avec la copie du fichier $rtffile dans $dest");
	  @chmod ($dest,0600);
	} else {
	  $report.="Le fichier source $rtffile n'existe pas<br />";
	}
	if ($updates) {
	  mysql_query("UPDATE $GLOBALS[tp]documents SET ".join(",",$updates)." WHERE identite=$row[identite]") or die (mysql_error());
	}
      }
      if ($err) break;
    }


    // efface les repertoires CACHE
    require_once($home."cachefunc.php");
    removefilesincache(".","../edition","../..");

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
function mysql_query_cmds($cmds,$table="") 

{
  $sqlfile=str_replace("_PREFIXTABLE_",$GLOBALS[tp],$cmds);
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if ($table) { // select the commands operating on the table  $table
    $sql=preg_grep("/(REPLACE|INSERT)\s+INTO\s+$GLOBALS[tp]$table\s/i",$sql);
  }
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
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $tables=array();
  while (list($table) = mysql_fetch_row($result)) {
    $tables[$table]=TRUE;
  }
  return $tables;
}


function getfields($table)

{
  $fields = mysql_list_fields($GLOBALS[currentdb],$GLOBALS[tp].$table) or die (mysql_error());
  $columns = mysql_num_fields($fields);
  $arr=array();
  for ($i = 0; $i < $columns; $i++) {
    $fieldname=mysql_field_name($fields, $i);
    $arr[$fieldname]=1;
  }
  return $arr;
}


# pose des problemes... ca ecrase les anciens types
function chargeinserts($table)

{
  global $home,$report;
      // charge l'install
  $file=$home."../install/inserts-site.sql";
  if (!file_exists($file)) {
    $err="Le fichier $file n'existe pas !";
    break;
  }
  $err=mysql_query_cmds(utf8_encode(join("",file($file))),$table);
  if ($err) return $err;
  $report.="Import des insert dans la table $table<br>";
  return FALSE;
}

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
  $report.="CrÈation de la table $table<br>";
  return FALSE;
}



// fonction de conversion de isolatin en utf8
function isotoutf8 ()

{

  $result = mysql_list_tables($GLOBALS[currentdb]) or die(mysql_error());

  // Tableau contenant la liste des tables ‡ ne pas parcourrir pour gagner du temps.
  #$blacklist=array("relations","entites_entrees","users_groupes");
  // On parcours toutes les tables
  while (list($table) = mysql_fetch_row($result)) {
    #if(in_array ($table, $blacklist)) continue;

     $report.="conversion en utf8 de  la table $table<br />\n";
    // On parcours toutes les enregistrements de chaque table
    $resultselect = mysql_query("SELECT * FROM $table") or die(mysql_error());
    while($valeurs = mysql_fetch_row($resultselect)) {
      $nbchamps = mysql_num_fields($resultselect);

      // Construction de la clause SET et WHERE de l'update
      // en parcourant toutes les valeurs de chaque enregistrement.
      $set=array();
      $where=array();
      for ($i=0; $i < $nbchamps; $i++) {
	$type  = mysql_field_type($resultselect, $i);
	$name  = mysql_field_name($resultselect, $i);
				
	// Construction de la clause SET
	if(($type=="string"||$type=="blob") && $valeurs[$i]!="") array_push($set,$name."='".addslashes(utf8_encode($valeurs[$i]))."'");
	  
	// Construction de la clause WHERE
        if (is_null($valeurs[$i])) {
           array_push($where,$name." IS NULL");
	} else if($type=="string"||$type=="blob") {
	  array_push($where,$name."='".addslashes($valeurs[$i])."'");
	} else {
	  array_push($where,$name."='$valeurs[$i]'");
	}
      } // parcourt les champs
      // S'il y a une modification ‡ faire on lance la requete
      if($set) {
	$requete="UPDATE $table SET ".join(", ",$set)." WHERE ".join(" AND ",$where);
	if (!mysql_query($requete)) { echo htmlentities($requete),"<br>"; die(mysql_error()); }
      }
    } // parcourt les lignes
  } // parcourt les tables
  return $report;
}

function extractnom($personne) {
  // ok, on cherche maintenant a separer le nom et le prenom

  if (preg_match("/^\s*(Pr\.|Dr\.)/",$personne,$result)) {
    $prefix=$result[1];
    $personne=str_replace($result[0],"",$personne);
  }

  $nom=$personne;

  while ($nom && strtoupper($nom)!=$nom) { $nom=substr(strstr($nom," "),1);}
  if ($nom) {
    $prenom=str_replace($nom,"",$personne);
  } else { // sinon coupe apres le premiere espace
    if (preg_match("/^\s*(.*?)\s+([^\s]+)\s*$/i",$personne,$result)) {
      $prenom=$result[1]; $nom=$result[2];
    } else $nom=$personne;
  }
  return array($prefix,$prenom,$nom);
}


?>
