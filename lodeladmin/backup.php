<?php
/**
 * Fichier backup - Gestion du backup des données d'une installation Lodel
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
 * @package lodeladmin
 */

require 'lodelconfig.php';
include $home.'auth.php';
authenticate(LEVEL_ADMINLODEL);
require 'func.php';
require 'backupfunc.php';

$context['importdir'] = $importdir;

if ($backup) {
	// il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
	$dirtotar  = array();
	$dirlocked = tempnam('/tmp', 'lodeldump_'). '.dir'; // this allow to be sure to have a unique dir.
	mkdir($dirlocked, 0700);
	$outfile = 'lodel.sql';
	$fh = fopen($dirlocked. '/'. $outfile, 'w');
	if (!$fh) {
		die ("ERROR: unable to open a temporary file in write mode");
	}

	// save the main database
	if (fputs($fh, 'DROP DATABASE '. DATABASE. ";\nCREATE DATABASE ". DATABASE. ";USE ". DATABASE. ";\n") === FALSE) {
		die("ERROR: unable to write in the temporary file");
	}

	$GLOBALS['currentprefix'] = '#_TP_';
	mysql_dump(DATABASE, $GLOBALS['lodelbasetables'], '', TRUE, $fh);

	// find the sites to backup
	$result = $db->execute(lq('SELECT name FROM #_MTP_sites WHERE status>-32')) or dberror();
	while (!$result->EOF) {
		$name = $result->fields['name'];
		dump_site($name, TRUE, $fh);
		if (!$sqlonly) {
			array_push($dirtotar, "$name/lodel/sources", "$name/docannexe");
		}
		$result->MoveNext();
	}
	fclose($fh);

	// tar les sites et ajoute la base
	$archivetmp      = tempnam('/tmp', 'lodeldump_');
	$archivefilename = 'lodel-'. date('dmy'). '.tar.gz';

	chdir(LODELROOT);

#  echo "tar czf $archivetmp ".join(" ",$dirtotar)." -C $dirlocked $outfile\n"; flush();
	system("tar czf $archivetmp ". join(' ', $dirtotar). " -C $dirlocked $outfile") !== FALSE or die ("impossible d'executer tar");
	unlink($dirlocked. '/'. $outfile);
	rmdir($dirlocked);

	chdir('lodeladmin'. ($version ? '-'. $version : ''));

	if (operation($operation, $archivetmp, $archivefilename, $context)) {
		return;
	}
}

require 'view.php';
$view = &View::getView();
$view->render($context, 'backup');
?>