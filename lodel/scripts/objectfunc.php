<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire de gestion des objets
 */

/**
 * Inclue les documents et les publi dans objets
 */
function makeobjetstable()
{
	global $db;

	$err = query_cmds_forobjectfunc('
	DELETE FROM #_TP_objets;
	INSERT INTO #_TP_objets (id,class) SELECT identity,"entities" FROM #_TP_entities;
	');
	if ($err)
		return $err;

	// ajoute un grand nombre a tous les id.

	$offset = 2000000000;

	$tables = array ("entities" => array ("idtype"), "types" => array ("id"), 
			"persontypes" => array ("id"), "entrytypes" => array ("id"), 
			"entries" => array ("id", "idparent", "idtype"), "persons" => array ("id"), 
			"entites_personnes" => array ("idperson", "idtype"), 
			"entites_entrees" => array ("identry"), 
			"typeentites_typeentrees" => array ("identrytype", "identitytype"), 
			"typeentites_typepersonnes" => array ("idpersontype", "identitytype"), 
			"typeentites_typeentites" => array ("identitytype", "idtypeentite2"));

	foreach ($tables as $table => $idsname)	{
		foreach ($idsname as $idname)	{
			$err .= query_cmds_forobjetfunc('
			 UPDATE #_TP_'.$table.' SET '.$idname.'='.$idname.'+'.$offset.' WHERE '.$idname.'>0;
			');
			if ($err)
				return $err;
		}
	}

	$conv = array (	"personnes" => array ("entites_personnes" => "idperson",), 
			"entrees" => array ("entites_entrees" => "identry", "entrees" => "idparent",), 
			"types" => array ("entites" => "idtype", 
			"typeentites_typeentites" => array ("identitytype", "idtypeentite2"), 
			"typeentites_typeentrees" => "identitytype", 
			"typeentites_typepersonnes" => "identitytype",),
			"typepersonnes" => array ("typeentites_typepersonnes" => "idpersontype", 
			"entites_personnes" => "idtype",), 
			"typeentrees" => array ("typeentites_typeentrees" => "identrytype", 
			"entrees" => "idtype",));

	foreach ($conv as $maintable => $changes)	{
		$result = $db->execute(lq("SELECT id FROM #_TP_$maintable")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		#echo "$maintable...\n";
		while ($id = $result->fields("id")) {
			#echo "$maintable...$id<br />\n";
			$newid = uniqueid($maintable);
			$err .= query_cmds_forobjetfunc('
			UPDATE #_TP_'.$maintable.' SET id='.$newid.' WHERE id='.$id.';
			 ');
			if ($err)
				return $err;

			foreach ($changes as $table => $idsname) {
				if (!is_array($idsname))
					$idsname = array ($idsname);
				foreach ($idsname as $idname) {
					$err .= query_cmds_forobjectfunc('
					UPDATE #_TP_'.$table.' SET '.$idname.'='.$newid.' WHERE '.$idname.'='.$id.';
					');
					if ($err)
						return $err;
				}
			}
			$result->MoveNext();
		}
		$result->Close();
		#echo "ok<br />";

	}

	// check all the id have been converted

	$err = "";
	foreach ($tables as $table => $idsname) {
		foreach ($idsname as $idname)	{
			$count = $db->getOne(lq("SELECT count(*) FROM #_TP_$table WHERE $idname>$offset"));
			if ($count === false)
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			if ($count)
				$err .= "<strong>warning</strong>: reste $count $idname non converti dans $table. si vous pensez que ce sont des restes de bug, vous pouvez les detruire avec la requete SQL suivante: DELETE FROM $GLOBALS[tp]$table WHERE $idname>$offset<br />\n";
		}
	}
	if ($err)
		return $err;

	return FALSE;
}