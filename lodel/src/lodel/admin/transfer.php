<?php
/*
*
*  LODEL - Logiciel d'Edition ELectronique.
*
*  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
*  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
*  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
*  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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


require "siteconfig.php";
require "auth.php";
authenticate();
$lodeluser['rights']=128;

require_once "func.php";
require_once "fieldfunc.php";
require_once "connect.php";

$context['confirm'] = intval($_POST['confirm']);

$tables = gettables();
if ($tables[$GLOBALS[tp]."v07_objets"]) {
	if ($_POST['confirm07']) {
		if (!$GLOBALS['tp']) {
			die("ERROR: unsafe operation. Comment this line if your database contains only Lodel tables. If not, you can't continue.");
		}
		foreach(array_keys($tables) as $table) {
			if (strpos($table,$GLOBALS['tp']."v07_")===0) {
	$basename=substr($table,strlen($GLOBALS['tp']."v07_"));
		$err=mysql_query_cmds('
DROP TABLE IF EXISTS #_TP_'.$basename.';
RENAME TABLE #_TP_v07_'.$basename.' TO #_TP_'.$basename.';
');    
			} elseif (strpos($table,$GLOBALS['tp'])===0) {
		$err = mysql_query_cmds('
DROP TABLE IF EXISTS '.$table.';
');
			} // sinon rien
			if ($err) {
				die($err);
			}
		} // foreach tables
	} else {
		$context['vzerosept'] = true;
	}
}


if ($_POST['confirm']) {
	$tables = gettables();
	do { // block de control
		if ($tables["$GLOBALS[tp]objets"]) {

			// traductions de la base
			$translationconv=  array(
					"objects (id,class)"=>"objets (id,classe)",
					"entities (id,idparent,idtype,identifier,usergroup,iduser,rank,status,upd)"=>"entites (id,idparent,idtype,identifiant,groupe,iduser,ordre,statut,maj)",
					"relations (id1,id2,nature,degree)"=>"relations (id1,id2,nature,degres)",
					"tablefields (id,name,idgroup,title,style,type,condition,defaultvalue,processing,allowedtags,filtering,edition,comment,status,rank,upd)"=>"champs (id,nom,idgroupe,titre,style,type,condition,defaut,traitement,balises,filtrage,edition,commentaire,statut,ordre,maj)",
					"tablefieldgroups (id,name,class,title,comment,status,rank,upd)"=>"groupesdechamps (id,nom,classe,titre,commentaire,statut,ordre,maj)",
					"persons (id,g_familyname,g_firstname,status,upd)"=>"personnes (id,nomfamille,prenom,statut,maj)",
					"users (id,username,passwd,name,email,userrights,status,upd)"=>"users (id,username,passwd,nom,courriel,privilege,statut,maj)",
					"usergroups (id,name,status,upd)"=>"groupes (id,nom,statut,maj)",
					"users_usergroups (idgroup,iduser)"=>"users_groupes (idgroupe,iduser)",
					"types (id,type,title,class,tpl,tplcreation,tpledition,import,rank,status,upd)"=>"types (id,type,titre,classe,tpl,tplcreation,tpledition,import,ordre,statut,maj)",
					"persontypes (id,type,title,style,titledescription,styledescription,tpl,tplindex,rank,status,upd)"=>"typepersonnes (id,type,titre,style,titredescription,styledescription,tpl,tplindex,ordre,statut,maj)",
					"entrytypes (id,type,title,style,tpl,tplindex,rank,status,flat,newbyimportallowed,useabrevation,sort,upd)"=>"typeentrees (id,type,titre,style,tpl,tplindex,ordre,statut,lineaire,nvimportable,utiliseabrev,tri,maj)",
					"entries (id,idparent,g_name,abrev,lang,idtype,rank,status,upd)"=>"entrees (id,idparent,nom,abrev,langue,idtype,ordre,statut,maj)",
					"tasks (id,name,step,user,context,status,upd)"=>"taches (id,nom,etape,user,context,statut,maj)",
					"texts (id,name,contents,status,upd)"=>"textes (id,nom,texte,statut,maj)",
					"entities_persons (idperson,identity,idtype,rank,prefix,description,fonction,affiliation,courriel)"=>"entites_personnes (idpersonne,identite,idtype,ordre,prefix,description,fonction,affiliation,courriel)",
					"entities_entries (identry,identity)"=>"entites_entrees (identree,identite)",
					"entitytypes_entitytypes (identitytype,identitytype2,condition)"=>"typeentites_typeentites (idtypeentite,idtypeentite2,condition)",
					"entitytypes_entrytypes (identitytype,identrytype,condition)"=>"typeentites_typeentrees (idtypeentite,idtypeentree,condition)",
					"entitytypes_persontypes (identitytype,idpersontype,condition)"=>"typeentites_typepersonnes (idtypeentite,idtypepersonne,condition)",
					"options (id,name,type,value,rank,status,upd)"=>"options (id,nom,type,valeur,ordre,statut,maj)",
					"documents"=>"documents",
					"publications"=>"publications",
					"sites"=>"sites",
					"pileurl"=>"pileurl",
					);

			$todelete = array();
			foreach($translationconv as $new => $old) {
				list($newtable,$values) = explode(" ", $new);
				list($oldtable,$select) = explode(" ", $old);

				if (!$tables[$GLOBALS['tp'].$oldtable]) continue;

				if (!$tables[$GLOBALS['tp']."v07_".$oldtable]) {
					$err = mysql_query_cmds('
RENAME TABLE #_TP_'.$oldtable.' TO #_TP_v07_'.$oldtable.';
						');
					if ($err) break;
				} else {
					$err = mysql_query_cmds('
DROP TABLE #_TP_'.$oldtable.';
');
					if ($err) break;
				}
				$oldtable = "v07_". $oldtable;

				//duplicate the table
				$db->SetFetchMode(ADODB_FETCH_NUM);
				list($t, $create) = $db->getRow(lq("SHOW CREATE TABLE #_TP_$oldtable"));
				$db->SetFetchMode(ADODB_FETCH_ASSOC);
				if ($select && $values) {
					$select     = preg_replace("/[()]/","",$select);
					$select_arr = explode(",",$select);
					$values_arr = explode(",",preg_replace("/[()]/","",$values));
					for($i=0; $i<count($select_arr); $i++) {
						$create = str_replace("`$select_arr[$i]`","`$values_arr[$i]`",$create);
					}
				} else {
					$values = "";
					$select = "*";
				}
				$create = str_replace(lq("`#_TP_$oldtable`"),lq("`#_TP_$newtable`"),$create);
				$err = mysql_query_cmds($create);

				//
				if ($err) {
					break;
				}
				$err = mysql_query_cmds("INSERT INTO #_TP_$newtable $values SELECT $select FROM #_TP_$oldtable");
				if ($err) {
					break;
				}
				array_push($todelete, $oldtable);
				$report.="traduction de la table $oldtable<br>\n";
			} // foreach }}}
			$tables = gettables();
			if ($err) {
				break;
			}
		} // fini la translation if }}}

		// add field in text class
		if ($tables["$GLOBALS[tp]texts"]) {
			$textfields = array("lang"=>"CHAR(5) NOT NULL",
			"textgroup"=>"VARCHAR(10) NOT NULL");
			$fields = getfields("texts");
			foreach ($textfields as $f => $t) {
				if ($fields[$f]) continue;
				$err=mysql_query_cmds('
ALTER TABLE #_TP_texts ADD '.$f.' '.$t.';
ALTER TABLE #_TP_texts ADD INDEX index_'.$f.' ('.$f.');
');
				if ($err) {
					break 2;
				}
			}

			if (!$fields['textgroup']) {
				$err = mysql_query_cmds('
UPDATE #_TP_texts SET textgroup=\'site\'
');
				if ($err) {
					break;
				}
			}
			$report.= "ajouter les champs a la table textes<br>";
		}

		// Ajout le champ lang a users (au deux niveaux)
		$fields = getfields("users");
		if (!$fields['lang']) {
			$err = mysql_query_cmds('
ALTER TABLE #_TP_users ADD lang CHAR(5) NOT NULL;
UPDATE #_TP_users SET lang=\'fr\'
');
			if ($err) {
				break;
			}
			$report.= "Ajout des lang dans users (local)<br>";
		}
		if (!$fields['rank']) {
			$err = mysql_query_cmds('
ALTER TABLE #_TP_users ADD rank INT UNSIGNED DEFAULT '0' NOT NULL
');
			if ($err) {
				break;
			}
			$report.= "Ajout de rank a users (local)<br>";
		}
		mysql_select_db($GLOBALS['database']);
		$fields = getfields("users", $GLOBALS['database']);
		if (!$fields['lang']) {
			$err = mysql_query_cmds('
ALTER TABLE #_TP_users ADD lang CHAR(5) NOT NULL;
UPDATE #_TP_users SET lang=\'fr\'
');
			if ($err) {
				break;
			}
			$report.= "Ajout des lang dans users (global)<br>";
		}
		if (!$fields['rank']) {
			$err = mysql_query_cmds('
ALTER TABLE #_TP_users ADD rank INT UNSIGNED DEFAULT '0' NOT NULL
');
			if ($err) {
				break;
			}
			$report.= "Ajout de rank a users (local)<br>";
		}
		mysql_select_db($GLOBALS['currentdb']);

		if (!$tables["$GLOBALS[tp]translations"]) {
			if ($err = create("translations")) {
				break; // create the translation table
			}
		}
		// publications et documents
		foreach (array("publications", "documents") as $classe) {
		$fields=getfields($classe);
		if ($fields['identite']) {
			$err=mysql_query_cmds("ALTER TABLE #_TP_$classe CHANGE identite identity	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE");
			if ($err) break 2;
			$report.="changement de identite en identity pour la table $classe<br>";
		}
		}


			/////////////////////
			// ENTITIES
			if ($tables["$GLOBALS[tp]entities"]) {
				$fields=getfields("entities");
				if (!$fields['creationdate']) {
					$err=mysql_query_cmds('
ALTER TABLE #_TP_entities ADD creationdate DATETIME;
ALTER TABLE #_TP_entities ADD modificationdate DATETIME;
ALTER TABLE #_TP_entities ADD creationmethod VARCHAR(16);
ALTER TABLE #_TP_entities ADD creationinfo TINYTEXT;
');
					if ($err) break;
					$report.="ajout de creationdate, modificationdate, creationmethod et creationinfo <br>";
					$justcreatedate=1;
				}
	if (!$fields['g_title']) {
		$err=mysql_query_cmds('
ALTER TABLE #_TP_entities ADD g_title TINYTEXT NOT NULL;
');
		if ($err) break;
		$report.="ajout de g_title<br>";
		$justcreatedate=1;
	}


			$result=mysql_query("SELECT 1 FROM $GLOBALS[tp]entities WHERE g_title!='' LIMIT 1") or trigger_error(mysql_error(),E_USER_ERROR);
			if ($justcreatedate || !mysql_num_rows($result)) {
	foreach (array("publications","documents") as $classe) {
		$fields=getfields($classe);
		if (!$fields['titre']) continue;
		$morefields="";
		if ($fields['fichiersource']) {
			$morefields.=",fichiersource,importversion";
		}

		$result=mysql_query("SELECT identity,titre,datepubli".$morefields." FROM $GLOBALS[tp]$classe") or die (mysql_error());
		while (list($id,$title,$datepubli,$fichiersource,$importversion)=mysql_fetch_row($result)) {
			$title=strip_tags($title);
			if (strlen($title)>255) {
				$title=substr($title,0,256);
				$title=preg_replace("/\S+$/","",$title);
			}
			$title=addslashes($title);
			if (preg_match("/oocharge/i",$importversion)) {
				$creationmethod="servoo";
				$creationinfo=$fichiersource;
			} else {
				$creationmethod="form";
				$creationinfo="html";
			}

			mysql_query("UPDATE $GLOBALS[tp]entities set g_title='$title', creationdate='$datepubli', modificationdate='$datepubli',creationmethod='$creationmethod',creationinfo='$creationinfo' WHERE id='$id'") or trigger_error(mysql_error(),E_USER_ERROR);
		}
	} // classes
	mysql_query("UPDATE $GLOBALS[tp]entities set creationmethod='form',creationinfo='html' WHERE creationmethod is NULL or creationmethod=''") or trigger_error(mysql_error(),E_USER_ERROR);
	$report.="Remplissage de g_title, creationdate et modificationdate<br />\n";
			}

			// decrease the protected status!
				$err=mysql_query_cmds('
UPDATE #_TP_entities SET status=8 where status=32;
UPDATE #_TP_entities SET status=-8 where status=-32;
');
	if ($err) break;
	if (mysql_affected_rows()>0) $report.="Reduction du statut des publications et documents proteges<br/>";
		} // table entite


		/////////////////////
		// USERGROUPS
		if ($tables["$GLOBALS[tp]usergroups"]) {
			$fields=getfields("usergroups");
			if (!$fields['rank']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_usergroups ADD rank INT UNSIGNED DEFAULT \'0\' NOT NULL;
');
	if ($err) break;
	$report.="Ajout de rank a usergroups<br/>";
			}
		}      


		////////////////
		// ENTREES 1/2
		$entryclasses=array();
		$entrytypes=array();
		if ($tables["$GLOBALS[tp]entrytypes"]) {
			$entrytypesdao=getDAO("entrytypes");
			$vos=$entrytypesdao->findMany("status>0");
			foreach($vos as $vo) {
	if (!$vo->class) {
		$entryclasses[$vo->type]=array($vo->title,"entries","identry");
		$entrytypes[]=$vo;
	}
			}
		}

		///////////////////////
		// CLASSES AND TABLEFIELDSGROUP
		if (!$tables["$GLOBALS[tp]classes"]) {
			$err=create("classes");

			// change here the rank for the documents
			$result=$db->execute(lq("SELECT id FROM #_TP_types WHERE class='documents'")) or dberror();
			$ids=array();
			while(!$result->EOF) {
	$ids[]=$result->fields['id'];
	$result->MoveNext();
			}
			$err=mysql_query_cmds("UPDATE #_TP_entities SET rank=rank+10000 WHERE id IN (".join(",",$ids).")");
			if ($err) break;
		}
		$fields=getfields("tablefieldgroups");
//    if (!$fields['idclass']) {
//      $err=mysql_query_cmds('
//ALTER TABLE #_TP_tablefieldgroups ADD  idclass  INT UNSIGNED DEFAULT \'0\' NOT NULL;
//ALTER TABLE #_TP_tablefieldgroups ADD  INDEX index_idclass (idclass);
// ');
//      if ($err) break;
//      $report.="Creation de la table classe<br>\n";
//    }
		$classes=array("publications"=>array("Publications","entities","identity"),
			"documents"=>array("Documents","entities","identity"),
			"documentsannexes"=>array("Documents Annexes","entities","identity"),
			"personnes"=>array("Personnes","persons","idperson"),
			);
		if ($entryclasses) $classes+=$entryclasses;
		foreach ($classes as $class=>$arr) {
			list ($title,$classtype,$idfield)=$arr;
			##echo "SELECT id FROM $GLOBALS[tp]classes WHERE class='$class'<br>";
			$result=mysql_query("SELECT id FROM $GLOBALS[tp]classes WHERE class='$class'") or trigger_error(mysql_error(),E_USER_ERROR);
			if (mysql_num_rows($result)>0) { 
	if ($classtype=="entries") unset($entryclasses[$class]); 
	continue; 
			}
			$id=uniqueid("classes");
			$err=mysql_query_cmds("INSERT INTO #_TP_classes (id,class,classtype,title,status,rank) VALUES('$id','$class','$classtype','$title','32','1');");
			if ($err) break 2;
			//      $err=mysql_query_cmds("UPDATE #_TP_tablefieldgroups SET idclass='$id' WHERE class='$class';");
			//      if ($err) break 2;

			$db->execute(lq("CREATE TABLE IF NOT EXISTS #_TP_$class ( $idfield INTEGER UNSIGNED UNIQUE, KEY index_$idfield ($idfield))")) or dberror();

			$report.="Creation de la classe $classe <br>\n";
		}

		/////////////////////
		// TABLEFIELDS
		if ($tables["$GLOBALS[tp]tablefields"]) {
	$err=mysql_query_cmds('
UPDATE #_TP_tablefields SET type=\'file\' WHERE type=\'fichier\';
UPDATE #_TP_tablefields SET type=\'email\' WHERE type=\'mail\';
UPDATE #_TP_tablefields SET edition=\'importable\' WHERE edition=\'\';
UPDATE #_TP_tablefields SET style=\'notebaspage,footnotetext\' WHERE name=\'notebaspage\';
UPDATE #_TP_tablefields SET style=\'notefin,endnotetext\' WHERE name=\'notefin\';
UPDATE #_TP_tablefields SET style=\'texte, standart, default\' WHERE name=\'texte\';
');
	if ($err) break;
			$fields=getfields("tablefields");
			if (!$fields['g_name']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_tablefields ADD g_name VARCHAR(255) NOT NULL;
ALTER TABLE #_TP_tablefields ADD INDEX index_g_name (g_name);
');
			}
			if (!$fields['editionparams']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_tablefields ADD editionparams TINYTEXT NOT NULL;
UPDATE #_TP_tablefields SET editionparams=\'30\' WHERE edition=\'textarea30\';
UPDATE #_TP_tablefields SET editionparams=\'10\' WHERE edition=\'textarea10\';
UPDATE #_TP_tablefields SET edition=\'textarea\' WHERE edition LIKE \'textarea%\';
');
	if ($err) break;
			}
			if (!$fields['weight']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_tablefields ADD weight TINYINT NOT NULL;
');
	if ($err) break;
			}
			if (!$fields['class']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_tablefields ADD class VARCHAR(64) NOT NULL, ADD INDEX index_class (class);
');

	if ($err) break;
	// get the class of each group
	$result=mysql_query("SELECT id,class FROM $GLOBALS[tp]tablefieldgroups WHERE status>0") or trigger_error(mysql_error(),E_USER_ERROR);
	while($row=mysql_fetch_assoc($result)) {
		$err=mysql_query_cmds('
UPDATE #_TP_tablefields SET class=\''.$row['class'].'\' WHERE idgroup='.$row['id'].';
');	  
	}
	$report.="Ajout de class a tablefields<br/>";
			}
		}      
		

		///////////////////////
		// OPTIONS
		if ($tables["$GLOBALS[tp]options"]) {
			$fields=getfields("options");
			if (!$fields['defaultvalue']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_options ADD  idgroup  INT UNSIGNED DEFAULT \'0\' NOT NULL;
ALTER TABLE #_TP_options ADD  INDEX index_idgroup (idgroup);
ALTER TABLE #_TP_options CHANGE name name VARCHAR(255) NOT NULL;
ALTER TABLE #_TP_options ADD  UNIQUE unique_name (name,idgroup);
ALTER TABLE #_TP_options CHANGE type type VARCHAR(255);
ALTER TABLE #_TP_options ADD  title  TINYTEXT NOT NULL;
ALTER TABLE #_TP_options ADD  edition  TINYTEXT NOT NULL;
ALTER TABLE #_TP_options ADD  editionparams  TINYTEXT NOT NULL;
ALTER TABLE #_TP_options ADD  defaultvalue  TEXT NOT NULL;
ALTER TABLE #_TP_options ADD  comment  TEXT NOT NULL;
ALTER TABLE #_TP_options ADD  userrights TINYINT UNSIGNED DEFAULT \'0\' NOT NULL;
');
	if ($err) break;
	foreach (array("s"=>"text",
					"pass"=>"passwd",
					"mail"=>"email",
					"col"=>"color",
					"i"=>"int") as $from=>$to) {
		$err=mysql_query_cmds('
UPDATE #_TP_options SET type=\''.$to.'\' WHERE type=\''.$from.'\';
');
		if ($err) break;
	}
	if ($err) break;
	$report.="Mise a jour de la table options<br/>";
			}
		}

		///////////////////////
		// OPTIONGROUPS
		if (!$tables["$GLOBALS[tp]optiongroups"]) {
			$err=create("optiongroups");
			if ($err) break;
			$err=mysql_query_cmds('
REPLACE INTO #_TP_optiongroups (id,name,title,status) VALUES (1,\'servoo\',\'ServOO\',1);
UPDATE #_TP_options SET idgroup=1 WHERE name LIKE \'servoo%\';
REPLACE INTO #_TP_optiongroups (id,name,title,status) VALUES (2,\'features\',\'Fonctions optionnelles\',1);
UPDATE #_TP_options SET idgroup=2 WHERE idgroup=0;
UPDATE #_TP_options SET idgroup=2 WHERE idgroup=0;
');
			if ($err) break;
		}



		///////////////////////
			// DOCUMENTSANNEXES

	// execute allways if (!$tables["$GLOBALS[tp]documentsannexes"]) {
		$idgroup=array();

			// create the group of fields for documentsannexe from those in documents
		foreach(array("grtitre","grgestion","grtexte") as $grp) {

			$query="SELECT * FROM $GLOBALS[tp]tablefieldgroups  WHERE name='$grp' ";
			$result=mysql_query($query." AND class='documentsannexes'") or trigger_error(mysql_error(),E_USER_ERROR);
			if (mysql_num_rows($result)>0) {
	$row=mysql_fetch_assoc($result);
	$idgroup[$grp]=$row['id'];
			} else {
	$result=mysql_query($query." AND class='documents'") or trigger_error(mysql_error(),E_USER_ERROR);
	$row=mysql_fetch_assoc($result);
	#echo "row:$row";print_r($row);
	if (!$row) { $err="Impossible de trouver le groupe de champ $grp"; break 2; }
	$row['class']="documentsannexes";
	$row['id']=0; // create a new one
	#print_r($row);
	setrecord("tablefieldgroups",0,$row);
	$idgroup[$grp]=mysql_insert_id();
			}
			}

			// create the fields in documentsannexes from documents
		$haslien=",lien";
			foreach(array("titre","lien","texte") as $field) {
	$result=mysql_query(lq("SELECT #_TP_tablefields.*,#_TP_tablefieldgroups.name as grp FROM #_TP_tablefieldgroups INNER JOIN #_TP_tablefields ON idgroup=#_TP_tablefieldgroups.id WHERE #_TP_tablefields.name='$field' AND #_TP_tablefieldgroups.class='documents'")) or trigger_error(mysql_error(),E_USER_ERROR);
	$row=mysql_fetch_assoc($result);
	if (!$row) {
		if ($field=="lien") {
			// let's create it
			$row=array('name'=>'lien','title'=>'Lien','type'=>'tinytext','condition'=>'*','edition'=>'importable','status'=>32,'rank'=>100);
			$haslien="";
		} else {
			$err="Impossible de trouver le tablefield $field"; break 2; 
		}
	}
	$row2=$row;
	unset($row2['grp']);
	$row2['idgroup']=$idgroup[$row['grp']];
	$row2['class']='documentsannexes';
	$row2['id']=0;
	setrecord("tablefields",0,$row2);
			}
			addfield("documentsannexes");
			
			// transfert the types 
			$err=mysql_query_cmds("
UPDATE #_TP_types SET class='documentsannexes' WHERE type LIKE 'documentannexe-%';
");
			// get the documents to transfer
			$result=mysql_query("SELECT $GLOBALS[tp]entities.id FROM $GLOBALS[tp]entities,$GLOBALS[tp]types WHERE idtype=$GLOBALS[tp]types.id AND type LIKE 'documentannexe-%'") or trigger_error(mysql_error(),E_USER_ERROR);
			$ids=array();
			while(list($id)=mysql_fetch_row($result)) $ids[]=$id;
			if (!$ids) {
	$rep.="aucun document a transferer<br>\n"; 
			} else {
	$ids=join(",",$ids);
	// transfert the documents
	$err=mysql_query_cmds("
REPLACE INTO #_TP_documentsannexes (identity,titre,texte".$haslien.")  SELECT identity,titre,texte".$haslien." FROM #_TP_documents WHERE identity IN ($ids);
DELETE FROM #_TP_documents WHERE identity IN ($ids);
");
	if ($err) break;
	$rep.="documents transferés<br>\n"; 
			}
			//}

			if (file_exists(SITEROOT."docannexe/fichier")) {
	rename(SITEROOT."docannexe/fichier",SITEROOT."docannexe/file");
			}

		/////////////////////
		// ENTRYTYPES
		if ($tables["$GLOBALS[tp]entrytypes"]) {
			$fields=getfields("entrytypes");
			if (!$fields['class']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entrytypes ADD class		VARCHAR(64) NOT NULL;
UPDATE #_TP_entrytypes SET class=type;
');
	if ($err) break;
	$report.="Ajout de class a entrytypes<br/>";
			}
			if (!$fields['edition']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entrytypes ADD edition TINYTEXT NOT NULL;
UPDATE #_TP_entrytypes SET edition=\'pool\';
');
	if ($err) break;
	$report.="Ajout de edition a entrytypes<br/>";
			}
			if (!$fields['g_type']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entrytypes ADD g_type VARCHAR(255) NOT NULL;
ALTER TABLE #_TP_entrytypes ADD INDEX index_g_type (g_type);
UPDATE #_TP_entrytypes SET g_type=\'dc.subject\' WHERE type=\'motcle\';
');
	if ($err) break;
	$report.="Ajout de g_type a entrytypes<br/>";
			}

//      if (!$fields['edition']) {
//	$err=mysql_query_cmds('
//ALTER TABLE #_TP_entrytypes ADD edition TINYTEXT NOT NULL;
//UPDATE #_TP_entrytypes SET edition=\'pool\';
//');
//	if ($err) break;
//	$report.="Ajout de edition a entrytypes<br/>";
//      }
			if ($fields['useabrevation']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entrytypes DROP useabrevation;
');
	if ($err) break;
	$report.="Supprime useabrevation<br/>";
			}

			$err=mysql_query_cmds('
UPDATE #_TP_entrytypes SET sort=\'sortkey\' WHERE sort=\'nom\';
UPDATE #_TP_entrytypes SET sort=\'rank\' WHERE sort=\'ordre\';
');
			if ($err) break;
			if (mysql_affected_rows()>0) $report.="modifie sort by name en sort by sortkey<br/>";
		}      


		/////////////////////
		// TYPES
		if ($tables["$GLOBALS[tp]types"]) {
			// transfert the types 
			$err=mysql_query_cmds("
UPDATE #_TP_types SET tpledition='edition' WHERE tpledition='edition-hierarchique' OR tpledition='edition-numero' OR tpledition='edition-rubrique' OR tpledition='edition-lineaire';
");
			if ($err) break;
			if (mysql_affected_rows()>0) $report.="Mise ajour de tpledition de types<br/>";
			$fields=getfields("types");
			if (!$fields['display']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_types ADD display VARCHAR(10) DEFAULT \'\';
UPDATE #_TP_types SET display=\'unfolded\' WHERE type LIKE \'regroupement%\';
ALTER TABLE #_TP_types ADD creationstatus TINYINT DEFAULT \'-1\' NOT NULL;
');
	if ($err) break;
	$report.="Ajout de display et creationstatus<br/>";	
			}
		}

		/////////////////////
		// PERSONTYPES
		if ($tables["$GLOBALS[tp]persontypes"]) {
			$fields=getfields("persontypes");
			if (!$fields['class']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_persontypes ADD class VARCHAR(64) NOT NULL;
UPDATE #_TP_persontypes SET class=type;
');
	if ($err) break;
	$report.="Ajout de class a persontypes<br/>";
			}
			if (!$fields['g_type']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_persontypes ADD g_type VARCHAR(255) NOT NULL;
ALTER TABLE #_TP_persontypes ADD INDEX index_g_type (g_type);
UPDATE #_TP_persontypes SET g_type=\'dc.creator\' WHERE type=\'auteur\';
');
	if ($err) break;
	$report.="Ajout de g_type a persontypes<br/>";
			}
//      if (!$fields['edition']) {
//	$err=mysql_query_cmds('
//ALTER TABLE #_TP_persontypes ADD edition TINYTEXT NOT NULL;
//UPDATE #_TP_persontypes SET edition=\'\';
//');
//	if ($err) break;
//	$report.="Ajout de edition a persontypes<br/>";
//      }
		}


	if ($tables["$GLOBALS[tp]persons"]) {
		$fields=getfields("persons");
		if (!$fields['idtype']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_persons ADD idtype INT UNSIGNED NOT NULL DEFAULT \'0\';
');
	// look for the links
	$result=$db->execute(lq("SELECT DISTINCT idperson,idtype FROM #_TP_entities_persons")) or dberror();
	$type=array();
	while(!$result->EOF) {
		$row=$result->fields;
		if (!$type[$row['idperson']]) {
			// set it
			$db->execute(lq("UPDATE #_TP_persons SET idtype='".$row['idtype']."' WHERE id='".$row['idperson']."'")) or dberror();
			$type[$row['idperson']]=$row['idtype'];
		} elseif ($type[$row['idperson']]!=$row['idtype']) {
			// boring case.
			require_once("dao.php");
			$dao=&getDAO("persons");
			$vo=$dao->getById($row['idperson']);
			$vo->id=0; // create a new one
			$vo->idtype=$row['idtype']; // with a different idtype
			$dao->quote($vo);
			$newid=$dao->save($vo);
			$db->execute(lq("UPDATE #_TP_entities_persons SET idperson='$newid' WHERE idperson='".$row['idperson']."' AND idtype='".$row['idtype']."'")) or dberror();
		} else {
			// nothing to do. Should not happends with the distinct
		}
		$result->MoveNext();
	}
	if ($err) break;
	$report.="Ajout de idtype a persons<br/>";
		}
		if (!$fields['sortkey']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_persons ADD sortkey VARCHAR(255) NOT NULL;
');
			$dao=&getDAO("persons");      
			$vos=$dao->findMany("1","","id,g_familyname,g_firstname");
			foreach($vos as $vo) {
	$vo->sortkey=makeSortKey(trim($vo->g_name));
	$dao->quote($vo);
	$dao->save($vo);
			}
			if ($err) break;
			$report.="Ajout de sortkey a persons<br/>";
		}
	}

	if ($tables["$GLOBALS[tp]entries"]) {
		$fields=getfields("entries");
		if (!$fields['sortkey']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entries ADD sortkey VARCHAR(255) NOT NULL;
');
			$dao=&getDAO("entries");      
			$vos=$dao->findMany("1","","id,g_name");
			foreach($vos as $vo) {
	$vo->sortkey=makeSortKey(trim($vo->g_name));
	$dao->quote($vo);
	$dao->save($vo);
			}
			if ($err) break;
			$report.="Ajout de sortkey a entries<br/>";
		}
		if (!$fields['abrev']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entries DROP abrev;
');
			if ($err) break;
			$report.="Ajout de sortkey a entries<br/>";
		}
	}

		/////////////////////
		// RELATIONS
		if ($tables["$GLOBALS[tp]relations"]) {
			$fields=getfields("relations");
			if (!$fields['idrelation']) {
	$howmany=$db->getone(lq("SELECT COUNT(*) FROM #_TP_relations"));
	for($i=0; $i<$howmany; $i++) {
		$id=uniqueid("relations");
		if ($i==0) $minid=$id;
	}
	$err=mysql_query_cmds('
ALTER TABLE #_TP_relations ADD idrelation INT UNSIGNED NOT NULL auto_increment, ADD PRIMARY KEY (idrelation);
UPDATE #_TP_relations SET idrelation=idrelation+'.($minid-1).' ORDER BY idrelation DESC;
ALTER TABLE #_TP_relations ADD UNIQUE (id1,id2,degree,nature);
ALTER TABLE #_TP_relations CHANGE degree degree TINYINT;
ALTER TABLE #_TP_relations CHANGE nature nature VARCHAR(32) DEFAULT \'P\' NOT NULL;
');
	if ($err) break;
	$report.="Ajout de idrelation a relations<br/>";
			}
#      if (!$fields['location']) {
#	$err=mysql_query_cmds('
#ALTER TABLE #_TP_relations ADD location VARCHAR(255);
#');
#	if ($err) break;
#	$report.="Ajout de location a relations<br/>";
#      }

			if ($tables["$GLOBALS[tp]entities_entries"]) {
	$howmany=$db->getone(lq("SELECT COUNT(*) FROM #_TP_entities_entries"));
	for($i=0; $i<$howmany; $i++) {
		$id=uniqueid("relations");
		if ($i==0) $minid=$id;
	}

	$err=mysql_query_cmds('
ALTER TABLE #_TP_entities_entries ADD id INT UNSIGNED NOT NULL auto_increment, ADD PRIMARY KEY (id);
UPDATE #_TP_entities_entries SET id=id+'.($minid-1).';
INSERT INTO #_TP_relations (idrelation,id1,id2,nature) SELECT id,identity,identry,\'E\' FROM #_TP_entities_entries;
DROP TABLE #_TP_entities_entries;
');
	if ($err) break;
	$report.="Ajout des entities_entries a relations et suppression de la table<br/>";
			}


			if ($tables["$GLOBALS[tp]entities_persons"]) {
	// create uniqueid
	$howmany=$db->getone(lq("SELECT COUNT(*) FROM #_TP_entities_persons"));
	for($i=0; $i<$howmany; $i++) {
		$id=uniqueid("relations");
		if ($i==0) $minid=$id;
	}
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entities_persons ADD id INT UNSIGNED DEFAULT \'0\' NOT NULL auto_increment, ADD PRIMARY KEY (id);
UPDATE #_TP_entities_persons SET id=id+'.($minid-1).';
INSERT INTO #_TP_relations (idrelation,id1,id2,degree,nature) SELECT id,identity,idperson,rank,\'G\' FROM #_TP_entities_persons;
ALTER TABLE #_TP_entities_persons DROP identity;
ALTER TABLE #_TP_entities_persons DROP idperson;
ALTER TABLE #_TP_entities_persons DROP idtype;
ALTER TABLE #_TP_entities_persons DROP rank;
ALTER TABLE #_TP_entities_persons CHANGE id idrelation INT UNSIGNED NOT NULL auto_increment;
DROP TABLE IF EXISTS #_TP_entities_personnes;
RENAME TABLE #_TP_entities_persons TO  #_TP_entities_personnes;
UPDATE #_TP_persontypes SET class=\'personnes\';

INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'prenom\', \'0\', \'PrÃ©nom\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'12\', \'20050104115252\', \'firstname\', \'\', \'personnes\');
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'nomfamille\', \'0\', \'Nom de famille\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'13\', \'20050104115309\', \'familyname\', \'\', \'personnes\');
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'prefix\', \'0\', \'PrÃ©fix\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'14\', \'20050104115405\', \'title\', \'4\', \'entities_personnes\');
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'affiliation\', \'0\', \'Affiliation\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'15\', \'20050104114132\', \'\', \'\', \'entities_personnes\');
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'fonction\', \'0\', \'Fonction\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'16\', \'20050104114147\', \'\', \'\', \'entities_personnes\');
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'description\', \'0\', \'Description\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'17\', \'20050104114206\', \'\', \'\', \'entities_personnes\');
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'courriel\', \'0\', \'Courriel\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'18\', \'20050104114221\', \'\', \'\', \'entities_personnes\');

');
	if ($err) break;
	
	addfield("personnes");
	$err=mysql_query_cmds('
INSERT #_TP_personnes (idperson,prenom,nomfamille) SELECT id,g_firstname,g_familyname FROM #_TP_persons;
');

	if ($err) break;
	$report.="Ajout des entities_persons a relations<br/>";
			}
		}

		
		$dccreator=$db->getOne(lq("SELECT 1 FROM #_TP_persontypes WHERE g_type='dc.creator'"));
		if (!$dccreator) {
			$err=mysql_query_cmds('
UPDATE #_TP_persontypes SET g_type=\'dc.creator\' where name=\'auteur\';
');
			if ($err) break;
			$report.="Ajout de dc.creator<br/>";
		}

		////////////////
		// ENTREES 2/2

		if ($entryclasses) {
			foreach(array_keys($entryclasses) as $class) {
	// create the field
	$err=mysql_query_cmds('
INSERT INTO #_TP_tablefields ( name, idgroup, title, style, type, condition, defaultvalue, processing, allowedtags, filtering, edition, comment, status, rank, upd, g_name, editionparams, class) VALUES (\'nom\', \'0\', \'Nom\', \'\', \'tinytext\', \'*\', \'\', \'\', \'\', \'\', \'editable\', \'\', \'32\', \'12\', \'20050104115252\', \'index key\', \'\', \''.$class.'\');
');
	if ($err) break 2;
	addfield($class);
	$err=mysql_query_cmds('
INSERT INTO #_TP_'.$class.' (identry,nom) SELECT id,g_name FROM #_TP_entries;
');
	if ($err) break 2;
	$report.="Ajout des champs de $class<br/>";
			}
		}
		if ($entrytypes) {
			foreach($entrytypes as $type) {
	$arr=preg_split("/\s*,\s*/",$type->style,-1,PREG_SPLIT_NO_EMPTY);
	if (count($arr)<=1) continue;
	// multilingue
	for($i=0; $i<count($arr); $i++) {
		list($name,$lang)=preg_split("/\s*:\s*/",$arr[$i]);
		$vo=$entrytypesdao->getById($type->id); // reload because the table has changed
		$vo->style=$name;
		if ($i>0) {
			$vo->id=0;
			$vo->type=$name;
			$vo->title="Index par ".$name;
		}
		$idtype=$entrytypesdao->save($vo);
		$err=mysql_query_cmds('
UPDATE #_TP_entries SET idtype='.$idtype.' WHERE idtype='.$type->id.' AND lang=\''.$lang.'\'
');
		if ($err) break 3;
		$report.="Ajout des types multilingue de ".$vo->type."<br/>";
	}
			}
			if ($fields['lang']) {
	$err=mysql_query_cmds('
ALTER TABLE #_TP_entries DROP lang;
');
	if ($err) break;
	$report.="Supprime lang<br/>";
			}
		}

		////////////////
		// INTERNAL STYLES

		if (!$tables["$GLOBALS[tp]internalstyles"]) {
			if ($err=create("internalstyles")) break;
		}


		////////////////
		// CHARACTER STYLES

		if (!$tables["$GLOBALS[tp]characterstyles"]) {
			if ($err=create("characterstyles")) break;
		}


		////////////////
		// OBJECTS

		if ($tables["$GLOBALS[tp]objects"]) {
			$err=mysql_query_cmds('
UPDATE #_TP_objects SET class=\'entities\' WHERE class=\'documents\';
UPDATE #_TP_objects SET class=\'entities\' WHERE class=\'publications\';
UPDATE #_TP_objects SET class=\'entries\' WHERE class=\'entrees\';
UPDATE #_TP_objects SET class=\'persons\' WHERE class=\'personnes\';
UPDATE #_TP_objects SET class=\'entrytypes\' WHERE class=\'typeentrees\';
UPDATE #_TP_objects SET class=\'persontypes\' WHERE class=\'typepersonnes\';
');
			if (mysql_affected_rows()>0) {
	// check object validity
	$result=$db->execute(lq("SELECT id,class FROM #_TP_objects")) or dberror();
	$ids=array();
	while(!$result->EOF) {
		$ids[$result->fields['class']][]=$result->fields['id'];
		$result->MoveNext();
	}
	$err="";
	foreach($ids as $class=>$id) {
		if (!$class) {
			// rebuild
			foreach(array("entities","entries","persons","entrytypes","persontypes","types") as $class) {
				$result=$db->execute(lq("SELECT id FROM #_TP_".$class." WHERE id ".sql_in_array($id))) or dberror();
				while(!$result->EOF) {
		$db->execute(lq("UPDATE #_TP_objects SET class='".$class."' WHERE id='".$result->fields['id']."'")) or dberror();
		$result->MoveNext();
				}
			}
		} else {
			if ($class=="relations") continue;
			$count=$db->getOne(lq("SELECT count(*) FROM #_TP_".$class." WHERE id ".sql_in_array($id)));
			if ($db->errorno()) dberror();
			if ($count!=count($id)) { $err.="Objects n'est pas a jour. Probleme avec la class $class $count!=".count($id)."<br>"; }
		}
	}
	
	if ($err) break;
	$report.="Mise a jour de la table objet et verification<br/>";
			}
		}


		// fini, faire quelque chose
	} while(0);
}


$context['error']=$err;
$context['report']=$report;

require("calcul-page.php");
calcul_page($context,"transfer");



function mysql_query_cmd($cmd) 

{
	$cmd=str_replace("#_TP_","$GLOBALS[tp]",$cmd);
	if (!mysql_query($cmd)) { 
		$err="$cmd <font COLOR=red>".mysql_error()."</font><br>";
		return $err;
	}
	return FALSE;
}


// faire attention avec cette fontion... elle supporte pas les ; dans les chaines de caractere...
function mysql_query_cmds($cmds,$table="") 

{
	$sqlfile=str_replace("#_TP_",$GLOBALS[tp],$cmds);
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


function getfields($table,$database="")

{
	if (!$database) $database=$GLOBALS['currentdb'];
	$fields = mysql_list_fields($database,$GLOBALS[tp].$table) or die (mysql_error());
	$columns = mysql_num_fields($fields);
	$arr=array();
	for ($i = 0; $i < $columns; $i++) {
		$fieldname=mysql_field_name($fields, $i);
		$arr[$fieldname]=1;
	}
	return $arr;
}


# pose des problemes... ca ecrase les anciens types
#function chargeinserts($table)
#
#{
#  global $home,$report;
#      // charge l'install
#  $file=$home."../install/inserts-site.sql";
#  if (!file_exists($file)) {
#    $err="Le fichier $file n'existe pas !";
#    break;
#  }
#  $err=mysql_query_cmds(utf8_encode(join("",file($file))),$table);
#  if ($err) return $err;
#  $report.="Import des insert dans la table $table<br>";
#  return FALSE;
#}

function create($table) 

{
	global $home,$report;
			// charge l'install
	$file=$home."../install/init-site.sql";
	if (!file_exists($file)) {
		return "Le fichier $file n'existe pas !";
	}
	
	if (!preg_match ("/CREATE TABLE[\s\w]+#_TP_$table\s*\(.*?;/s",join('',file($file)),$result)) return "impossible de creer la table $table car elle n'existe pas dans le fichier init-site.sql<br>";
	
	$err=mysql_query_cmds($result[0]);
	if ($err) return $err;
	$report.="Cr&eacute;ation de la table $table<br>";
	return FALSE;
}





function addfield($classe)

{
	require_once("fieldfunc.php");
	$fields=getfields($classe);

	$result=mysql_query("SELECT name,type FROM $GLOBALS[tp]tablefields WHERE class='$classe'") or trigger_error(mysql_error(),E_USER_ERROR);

	#echo "classe:$classe<br/>";
	while (list($tablefield,$type)=mysql_fetch_row($result)) {
		#echo "ici:$tablefield $type<br/>";
		if ($fields[$tablefield]) continue;
		#echo "ici:$tablefield - create<br/>";
		#echo 'ALTER TABLE #_TP_'.$classe.' ADD     '.$tablefield.' '.$GLOBALS[sqltype][$type].'<br>';
		$sqltype=$GLOBALS['lodelfieldtypes'][$type]['sql'];
		if (!$sqltype) continue;
		$err=mysql_query_cmds('
ALTER TABLE #_TP_'.$classe.' ADD     '.$tablefield.' '.$sqltype.';
');
		#echo "error:$err";
		if ($err) return $err;
	}
	return false;
}


function loop_fichiers(&$context,$funcname)
{
	global $importdirs,$fileregexp;

	foreach ($importdirs as $dir) {
		if ( $dh= @opendir($dir)) {
			while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$context[nom]="$dir/$file";
	call_user_func("code_do_$funcname",$context);
			}
			closedir ($dh);
		}
	}
}


if (!function_exists("setrecord")) {
function setrecord($table,$id,$set,$context=array())

{
	global $db;

	$table=lq("#_TP_").$table;

	if ($id>0) { // update
		foreach($set as $k=>$v) {
			if (is_numeric($k)) { // get it from context
	$k=$v;
	$v=$context[$k];
			}
			if ($update) $update.=",";
			$update.="$k=".$db->qstr($v);
		}
		if ($update)
			$db->execute("UPDATE $table SET  $update WHERE id='$id'") or dberror();
	} else {
		$insert="";$values="";
		if (is_string($id) && $id=="unique") {
			$id=uniqueid($table);
			$insert="id";$values="'".$id."'";
		}
		foreach($set as $k=>$v) {
			if (is_numeric($k)) { // get it from context
	$k=$v;
	$v=$context[$k];
			}
			if ($insert) { $insert.=","; $values.=","; }
			$insert.=$k;
			$values.=$db->qstr($v);
		}

		if ($insert) {
			$db->execute("REPLACE INTO $table (".$insert.") VALUES (".$values.")") or dberror();
			if (!$id) $id=$db->insert_id();
		}
	}
	return $id;
}
}

?>
