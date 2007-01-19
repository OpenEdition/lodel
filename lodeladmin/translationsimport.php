<?php
/**
 * Fichier translationsimport - Importation des traductions
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */

require 'lodelconfig.php';
require 'auth.php';
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
require_once 'func.php';
require_once 'importfunc.php';
$context['textgroups'] = 'interface';
$lang = '';
$file = extract_import('translation', $context, 'xml');

if ($file) {
  require_once 'validfunc.php';
  require_once 'importfunc.php';
  require_once 'translationfunc.php';
  $xmldb = new XMLDB_Translations($context['textgroups']);
  $xmldb->readFromFile($file);
  back();
}

require 'calcul-page.php';
calcul_page($context, 'translationsimport');


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
				if ($dir == 'CACHE') {
					$localcontext['maybedeleted'] = 1;
				}
				call_user_func("code_do_$funcname", $localcontext);
      }
			closedir ($dh);
		}
	}
}

function loop_translation(&$context, $funcname)
{
	$arr = preg_split("/<\/?row>/", file_get_contents($context['fullfilename']));
	$langs = array();
	for($i = 1; $i<count($arr); $i+= 2) {
		$localcontext = $context;
		foreach (array('lang', 'title', 'creationdate', 'modificationdate') as $tag) {
			if (preg_match("/<$tag>(.*)<\/$tag>/", $arr[$i], $result)) {
				$localcontext[$tag] = trim(strip_tags($result[1]));
			}
		}
		if (!$localcontext['lang']) {
			continue;
		}
		call_user_func("code_do_$funcname", $localcontext);
	}
}
?>