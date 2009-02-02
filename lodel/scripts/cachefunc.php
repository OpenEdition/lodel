<?php
/**
 * Fichier utilitaire pour la gestion du cache
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
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */


/**
 * Nettoyage du répertoire de CACHE
 *
 * Cette fonction appelle removefilesincache() si $allCache = true
 * @param bool $allCache
 * @see func.php -> function update()
 */
function clearcache($allCache=true)
{
	global $site;
	$_REQUEST['clearcache'] = false; // to avoid to erase the CACHE again
	if($allCache) {
		if (defined("SITEROOT")) {
			removefilesincache(SITEROOT, SITEROOT."lodel/edition", SITEROOT."lodel/admin");
		}	else {
			removefilesincache(".");
		}
	} else { // seules les données ont été modifiées : on supprime seulement les fichiers HTML mis en cache
		if(!class_exists('Cache_Lite', false))
			require 'Cache/Lite.php';
		if (defined("SITEROOT")) {
			$options = $GLOBALS['cacheOptions'];
			$cacheReps = array(SITEROOT, SITEROOT."lodel/edition", SITEROOT."lodel/admin");
			foreach($cacheReps as $rep) {
				$cache = null;
				$rep = "./".$rep;
				$options['cacheDir'] = $rep.'/CACHE/';
				if(!file_exists($options['cacheDir']))
					continue;
				$cache = new Cache_Lite($options);
				if($site) {
					$cache->clean($site); // html
				} else {
					$cache->clean();
				}
			}
		} else {
			$cache = new Cache_Lite($GLOBALS['cacheOptions']);
			if($site) {
				$cache->clean($site); // html
			} else {
				$cache->clean();
			}
		}
		// clean ADODB cache files
		if (defined("SITEROOT")) {
			removefilesincache(SITEROOT.'/CACHE/adodb', SITEROOT."lodel/edition/CACHE/adodb", SITEROOT."lodel/admin/CACHE/adodb");
		}	else {
			removefilesincache("./CACHE/adodb");
		}
	}
}

/**
 * Nettoyage des fichiers du répertoire de CACHE
 *
 * On ajoute le répertoire CACHE dans le code, ce qui empêche de détruire le contenu d'un autre
 * répertoire.
 */
function removefilesincache()
{
	global $site;
	$options = $GLOBALS['cacheOptions'];
	$dirs = func_get_args();
	foreach ($dirs as $rep) {
		$rep = "./".$rep;
		if(FALSE === strpos($rep, '/CACHE/'))
			$rep .= "/CACHE/";

		// fichiers/répertoires gérés indépendament de cache_lite
		if(!is_dir($rep)) continue;

		clearstatcache();

		$cache = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rep));
		foreach($cache as $file) {
			if($cache->isDot() || $cache->isDir() || !$cache->isWritable() || ($dir = basename($cache->getPath())) == 'CVS' 
				|| $dir == 'upload' || $dir == 'require_caching') continue;
			unlink($file);
		}
// 		$fd = opendir($rep) or trigger_error("Impossible d'ouvrir $rep", E_USER_ERROR);
// 		while (($file = readdir($fd)) !== false) {
// 			if (($file{0} == ".") || ($file == "CVS") || ($file == "upload") || ($file == 'require_caching'))
// 				continue;
// 			$file = $rep. "/". $file;
// 			if (is_dir($file)) { //si c'est un répertoire on execute la fonction récursivement
// 				removefilesincache($file);
// 			} elseif (is_writeable($file)) {
// 				@unlink($file);
// 			}
// 		}
// 		closedir($fd);	
	}
}

/**
 * Fonction générant le nom du fichier caché (prise de Cache_Lite)
 * @param string $id base du nom du fichier à générer
 * @param string $group groupe du fichier
 * @param array $options options du cache
*/
function getCachedFileName($id, $group, $options) {
        if ($options['fileNameProtection']) {
            $suffix = 'cache_'.md5($group).'_'.md5($id);
        } else {
            $suffix = 'cache_'.$group.'_'.$id;
        }
        $root = $options['cacheDir'] ? $options['cacheDir'] : './CACHE/';
        if ($options['hashedDirectoryLevel']>0) {
            $hash = md5($suffix);
            for ($i=0 ; $i<$options['hashedDirectoryLevel'] ; $i++) {
                $root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
            }   
        }
        return $root.$suffix;
}
?>
