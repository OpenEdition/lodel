<?php
/**
 * Fichier utilitaire de gestion des objets
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
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
?>