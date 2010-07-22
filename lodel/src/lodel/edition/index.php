<?php
/**
 * Fichier racine de lodel/edition
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
 * @package lodel/source/lodel/edition
 */


define('backoffice', true);
define('backoffice-edition', true);

require 'siteconfig.php';

try
{
	include 'auth.php';

	// Authentification HTTP pour les flux RSS coté édition (flux du tableau de bord) : Cf. auth.php
	if (C::get('page') == 'backend' && C::get('format')) {
		authenticate(LEVEL_VISITOR, 'HTTP');
	}
	else {
		authenticate(LEVEL_VISITOR);
	}

	if (!C::get('do') && !C::get('lo')) 
	{
		recordurl();
	
		$id = C::get('id');
		if ($id) {
			do {
				$row = $db->getRow(lq("SELECT tpledition,idparent,idtype FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='$id'"));
				if ($row === false) {
					trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
				if (!$row) {
					header ("Location: not-found.html");
					return;
				}
				$base              = $row['tpledition'];
				$idparent          = $row['idparent'];
				$context['idtype'] = $row['idtype'];
				if (!$base) {
					$context['id'] = $id = $idparent;
				}
			} while (!$base && $idparent);
		} else {
			if ($base = C::get('page')) { // call a special page (and template)
				if (strlen($base) > 64 || preg_match("/[^a-zA-Z0-9_\/-]/", $base)) {
					trigger_error("invalid page", E_USER_ERROR);
				}
			} else {
				$base = 'edition';
			}
		}
		View::getView()->renderCached($base);
		return;
	} else {
		// automatic logic
		if(!C::get('lo')) {
			$do = C::get('do');
			switch ($do) { // Detection automatique de la logique en fonction de l'action
				case 'move':
				case 'preparemove':
				case 'changestatus':
				case 'download':
					$lo = 'entities_advanced';
					break;
				case 'cleanIndex':
				case 'deleteIndex':
				case 'addIndex':
					$lo = 'entities_index';
					break;
				case 'view':
				case 'edit':
					$lo = 'entities_edition';
					break;
				case 'import':
					$lo = 'entities_import';
					break;
				default :
					$lo = '_' === $do{0} ? 'plugins' : 'entities';
					break;
			}
			C::set('lo', $lo);
		}
	
		Controller::getController()->execute(array('entities', 'entities_advanced', 'entities_edition', 'entities_import', 'entities_index', 'filebrowser', 'tasks', 'xml', 'users', 'plugins'), C::get('lo'));
	}
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
