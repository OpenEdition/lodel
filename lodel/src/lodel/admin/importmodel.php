<?php
/**
 * Importation d'un modèle éditorial
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

require 'importfunc.php';
if ( ($context['error_table'] = isimportmodelallowed()) ) {
    require_once 'view.php';
		$view = &View::getView();
    $view->render($context, 'importmodel');
    exit;
}

$file = extract_import('model', $context);
if ($file && $delete) {
  // extra check. Need more ?
  if (dirname($file) == 'CACHE') {
    unlink($file);
  }

} elseif ($file) {
  require_once 'connect.php';
  require_once 'backupfunc.php';
  require 'func.php';

  $sqlfile = tempnam(tmpdir(), 'lodelimport_');
  $accepteddirs = array('tpl', 'css', 'images', 'js');
  $acceptedexts = array('html', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'tiff', 'js');

  if (!importFromZip($file, $accepteddirs, $acceptedexts, $sqlfile)) {
		$err = $context['error_extract'] = 1;
		break;
	}

  // execute the editorial model
  if (!execute_dump($sqlfile)) {
		$context['error_execute_dump'] = $err->errormsg();
	}
  @unlink($sqlfile);

	// change the id in order there are minimal and unique
	reinitobjetstable();

	require_once 'cachefunc.php';
	clearcache();

  if (!$err) {
    if ($frominstall) {
			header ('location: ../edition/index.php');
			exit;
		}
    back();
  }
}

require_once 'view.php';
$view = &View::getView();
if ($frominstall) {
  $context['frominstall'] = true;
  $GLOBALS['nodesk']      = true;
  $view->render($context, 'importmodel-frominstall');
} else {
  $view->render($context, 'importmodel');
}

function loop_files(&$context, $funcname)
{
	global $fileregexp,$importdirs,$home;

	foreach ($importdirs as $dir) {
		if ( $dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== FALSE) {
				if (!preg_match("/^$fileregexp$/i", $file)) {
					continue;
				}
				$localcontext = $context;
				$localcontext['filename']     = $file;
				$localcontext['fullfilename'] = "$dir/$file";
				if ($dir == "CACHE") {
					$localcontext['maybedeleted'] = 1;
				}
				// open ZIP archive and extract model.sql
				if ($unzipcmd && $unzipcmd != "pclzip") {
	  			$line = `$unzipcmd $dir/$file -c model.sql`;
				} else {
	  			require_once "pclzip.lib.php";
	  			$archive = new PclZip("$dir/$file");
	  			$arr = $archive->extract(PCLZIP_OPT_BY_NAME, "model.sql",
									PCLZIP_OPT_EXTRACT_AS_STRING);
	  			$line = $arr[0]['content'];
				}
				if (!$line) {
					continue;
				}

				$xml = "";
				if (preg_match("/<model>(.*?)<\/model>/s", $line, $result)) {
	  			$lines = preg_split("/\n/", $result[1]);
	  			$xml = "";
	  			foreach ($lines as $line) {
	    			$xml.= substr($line, 2). "\n";
	  			}
				}
				foreach (array('lodelversion', 'title', 'description', 'author', 'date', 'modelversion') as $tag) {
					if (preg_match("/<$tag>(.*?)<\/$tag>/s", $xml, $result)) {
						$localcontext[$tag] = str_replace(array("\r", "<",">", "\n"),
						array("", "&lt;", "&gt;", "<br />"),
						trim($result[1]));
					}
				}
				// check only the major version, sub-version are not checked
				if (doubleval($localcontext['lodelversion']) != doubleval($GLOBALS['version'])) {
					$localcontext['warning_version'] = 1;
				}
				call_user_func("code_do_$funcname", $localcontext);
			}
			closedir ($dh);
		}
	}
}

function reinitobjetstable()
{
	global $db;

	$db->execute(lq('DELETE FROM #_TP_objects')) or dberror();

  // ajoute un grand nombre a tous les id.
	$offset = 2000000000;
	$tables = array(
		'classes' => array('id'),
		'types' => array('id'),
		'persontypes' => array('id'),
		'entrytypes' => array('id'),
		'entitytypes_entitytypes' => array('identitytype', 'identitytype2'),
		);
	foreach ($tables as $table => $idsname) {
		foreach ($idsname as $idname) {
			$db->execute(lq('UPDATE #_TP_'. $table. ' SET '. $idname. '='. $idname. '+'. $offset. ' WHERE '.$idname. '>0')) or dberror();
		}
	}

	$conv = array('types' => array('entitytypes_entitytypes' => array('identitytype', 'identitytype2'), ),
								'persontypes' => array(), 'entrytypes' => array(), 'classes' => array());

	foreach ($conv as $maintable => $changes) {
		$result = $db->execute(lq("SELECT id FROM #_TP_$maintable")) or dberror();
		while ( ($id=$result->fields['id']) ) {
			$newid=uniqueid($maintable);
			$db->execute(lq('UPDATE #_TP_'.$maintable.' SET id='.$newid.' WHERE id='.$id)) or dberror();
			foreach ($changes as $table => $idsname) {
				if (!is_array($idsname)) {
					$idsname = array($idsname);
				}
				foreach ($idsname as $idname) {
					$db->execute(lq('UPDATE #_TP_'. $table. ' SET '. $idname. '='. $newid. ' WHERE '. $idname. '='. $id)) or dberror();
				}
			}
			$result->MoveNext();
		}
	}

	// check all the id have been converted
	$err = "";
	foreach ($tables as $table => $idsname) {
		foreach ($idsname as $idname) {
			$count = $db->getOne(lq("SELECT count(*) FROM #_TP_$table WHERE $idname>$offset"));
			if ($count === false) {
				dberror();
			}
			if ($count) {
				die("<strong>warning</strong>: reste $count $idname non converti dans $table. si vous pensez que ce sont des restes de bug, vous pouvez les detruire avec la requete SQL suivante: DELETE FROM $GLOBALS[tp]$table WHERE $idname>$offset<br />\n");
			}
		}
	}
	if ($err) {
		return $err;
	}
  return false;
}

function isimportmodelallowed() 
{
	global $db;
	// verifie qu'on peut importer le modele.
	foreach(array("#_TP_entities", "#_TP_entries", "#_TP_persons") as $table) {
		$haveelements = $db->getOne(lq("SELECT id FROM $table WHERE status>-64"));
		if ($db->errorno) {
			continue; // likely the table does not exists
		}
		if ($haveelements) {
			return $table;
		}
		$db->execute(lq("DELETE FROM $table WHERE status<=-64")) or dberror(); // in case...
	}
  return false;
}
?>