<?php
/**
 * Fichier locksite - Locked des sites
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cï¿½ou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cï¿½ou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cï¿½ou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */

// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require 'lodelconfig.php';
include $home. 'auth.php';
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
require_once 'func.php';

$id      = intval($id);
$critere = " id = '$id' AND status > 0";
mysql_select_db($database) or die (mysql_error());
if ($lock) { // lock
	// lock le site en ecriture
	lock_write ('session', 'sites');
	// cherche le nom de la site
	$result = mysql_query ("SELECT path FROM  $GLOBALS[tp]sites WHERE $critere") or die (mysql_error());
	list($site) = mysql_fetch_row($result);
	if (!$site) {
		die ("erreur lors de l'appel de la locksite. La site est inconnue ou supprimee");
	}
	// delogue tout le monde sauf moi.
	mysql_query ("DELETE FROM $GLOBALS[tp]session WHERE site='$site' AND iduser!='$iduser'") or die (mysql_error());
	// change le statut du site
	mysql_query ("UPDATE $GLOBALS[tp]sites SET status = 32 WHERE $critere") or die (mysql_error());
	unlock();

} elseif ($unlock) { // unlock
	mysql_query ("UPDATE $GLOBALS[tp]sites SET status = 1 WHERE $critere") or die (mysql_error());
} else { die ("lock ou unlock"); }

mysql_select_db($currentdb) or die (mysql_error());

// Nettoyage du cache
require_once 'cachefunc.php';
clearcache();

// Appel de la vue précédente
require_once 'view.php';
$view = &View::getView();
$view->back();
return;
?>
