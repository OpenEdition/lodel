<?php
/**
 * Session
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source/lodel/admin
 */

require 'siteconfig.php';
require 'auth.php';
authenticate(LEVEL_ADMIN);

require_once 'func.php';
$delete     = intval($_GET['delete']);
$deleteuser = intval($_GET['deleteuser']);
usemaindb();
$ids = array();
if ($deleteuser) {
	$result = $db->execute(lq("SELECT id FROM #_MTP_session WHERE iduser='".$deleteuser."'")) or dberror();
	while(!$result->EOF) {
		$ids[] = $result->fields['id'];
		$result->MoveNext();
	}
} elseif ($delete) {
	$ids[] = $delete;
} else {
	die ("ERROR: unknow operation");
}

if ($ids) {
	$idstr = join(',', $ids);
	// remove the session
	$db->execute(lq("DELETE FROM #_MTP_session WHERE id IN ($idstr)")) or dberror();
	// remove the url related to the session
	$db->execute(lq("DELETE FROM #_MTP_urlstack WHERE idsession IN ($idstr)")) or dberror();
}

usecurrentdb();
update();
require_once 'view.php';
$view = &View::getView();
$view->back();
?>