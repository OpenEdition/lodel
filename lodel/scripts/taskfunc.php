<?php
/**
 * Fichier utilitaire de gestion des tâches (table task)
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

// $context est soit un tableau qui sera serialise soit une chaine deja serialise
/**
 * Ajoute une tâche dans la table
 *
 * @param string $name le nom de la tâche
 * @param string $etape l'étape courante
 * @param array (ou string) $context le context courant pour l'étape
 * @param integer $id par défaut 0. l'id de la tâche
 * @return integer l'identifiant inséré (si c'est le cas)
 */
function maketask($name, $etape, $context, $id = 0)
{
	global $db;
	if (is_array($context))
		$context = addslashes(serialize($context));
	$id = (int)$id;
	$db->execute(lq("REPLACE INTO #_TP_tasks (id,name,step,user,context) VALUES ('$id','$name','$etape','".C::get('id', 'lodeluser')."','$context')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	return $db->insert_ID();
}

/**
 * Retrouve une tâche suivant son identifiant
 *
 * @param integer &$id l'identifiant de la tâche
 * @param array retourne les informations concernant la tâche
 */
function gettask(& $id)
{
	global $db;
	$id = (int)$id;
	$row = $db->getRow(lq("SELECT * FROM #_TP_tasks WHERE id='$id' AND status>0"));
	if ($row === false)
		trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	if (!$row) {
		View::getView()->back();
		return;
	}
	$row = array_merge($row, unserialize($row['context']));
	return $row;
}

/**
 * Suivant que le document est importé ou réimporté, les informations dans la tâche sont
 * différentes. Cette fonction uniformise ces informations dans le context ($context).
 *
 * Depending the document is imported or re-imported, the information in task are different.
 * This function uniformize the information in the context
 *
 * @param array $task un tableau contenant les infos d'une tâche
 * @param array &$context le context dans lequel seront mis à jour les informations
 */
function gettypeandclassfromtask($task, & $context)
{
	global $db;
	if (isset($task['identity']) && $task['identity']) {
		$row = $db->getRow(lq("SELECT class,idtype,idparent FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='".$task['identity']."'"));
		if ($db->errorno())
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$context['class'] = $row['class'];
		$context['idtype'] = $row['idtype'];
		$context['idparent'] = $row['idparent'];
		if (!$context['class'])
			trigger_error("ERROR: can't find entity ".$task['identity']." in gettypeandclassfromtask", E_USER_ERROR);
	} else {
		if (!isset($task['idtype']) || !($idtype = $task['idtype']))
			trigger_error("ERROR: idtype must be given by task in gettypeandclassfromtask", E_USER_ERROR);
		// get the type 
		$votype = DAO::getDAO("types")->getById($idtype, "class");
		$context['class'] = $votype->class;
	}
}