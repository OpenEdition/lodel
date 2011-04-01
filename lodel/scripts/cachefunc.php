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
 * @author Pierre-Alain Mignot
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
 * Nettoyage du répertoire de CACHE
 *
 * Cette fonction appelle removefilesincache() si $allCache = true
 * @param bool $allCache
 * @see func.php -> function update()
 */
function clearcache($allCache=true)
{
	$_REQUEST['clearcache'] = false; // to avoid to erase the CACHE again
	$site = C::get('site', 'cfg');
	if($allCache) {
		removefilesincache(getCachePath());
	} else { // seules les données ont été modifiées : on supprime seulement les fichiers HTML mis en cache
		$options   = C::get('cacheOptions', 'cfg');
		if ($site) {
			$cache = new Cache_Lite($options);
			$cacheReps = array( getCachePath(), 
								getCachePath("edition"), 
								getCachePath("admin") );
			foreach($cacheReps as $rep) 
			{
				$options['cacheDir'] = $rep;
				if(!file_exists($options['cacheDir'])) continue;
				$cache->setOption('cacheDir', $options['cacheDir']);
				$cache->clean($site.'_page'); // page html
				$cache->clean($site.'_tpl_inc'); // tpl included
                		removefilesincache($options['cacheDir'].'/adodb_tpl/');
			}
		} else {
			$cache = new Cache_Lite($options);
			$cache->clean($site.'_page'); // page html
			$cache->clean($site.'_tpl_inc'); // tpl included
            		removefilesincache( getCachePath('adodb_tpl') );
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
	$options = C::get('cacheOptions', 'cfg');
	$dirs = func_get_args();
    	clearstatcache();
	foreach ($dirs as $rep) {

		if(!is_dir($rep)) continue;

		// fichiers/répertoires gérés indépendament de cache_lite
		$fd = @opendir($rep) or trigger_error("Impossible d'ouvrir $rep", E_USER_ERROR);

		while (($file = readdir($fd)) !== false) {
			if (($file{0} == ".") || ($file == "upload") || ($file == 'require_caching'))
				continue;
			$file = $rep. "/". $file;
			if (is_dir($file)) { //si c'est un répertoire on execute la fonction récursivement
				removefilesincache($file);
			} else {@unlink($file);}
		}
		closedir($fd);	
	}
}

/**
 * Check for a specific directory in cache
 * It will try to create it if it does not exists
 */
function checkCacheDir($dir)
{
	$dir = getCachePath( $dir ) . DIRECTORY_SEPARATOR ;

    if(is_dir($dir))
    {
        if(is_writeable($dir)) return true;
        trigger_error('ERROR: cannot write in directory '.$dir, E_USER_ERROR);
    }
    elseif(file_exists($dir))
    {
	if(!@unlink($dir)) trigger_error('ERROR: file '.$dir.' exists, is not a dir and cannot be removed!', E_USER_ERROR);
    }

	$filemask = C::get('filemask', 'cfg');

	if(! is_dir( $dir ) ){
		mkdir($dir, 0777 & octdec($filemask), true);
        chmod($dir, 0777 & octdec($filemask));
	}

    if(is_writeable( $dir ) ) {
        @mkdir($dir, 0777 & octdec($filemask));
        @chmod($dir, 0777 & octdec($filemask));
    } else {
        trigger_error('ERROR : cannot write in CACHE directory.', E_USER_ERROR);
    }
    return true;
}

/**
 * Try to get serialized datas from cache
 * This function uses file locking
 * Please note that this function will by default read datas from the siteroot cache
 *
 * @param string $filename
 * @param string $siteroot  
 * @return boolean or file contents
 */
function getFromCache($filename, $siteroot=true)
{
	$filename = getCachePath($filename);
	if($siteroot) $filename = SITEROOT . $filename;

	if(!($fh = @fopen($filename, 'rb'))) return false;
	
	@flock($fh, LOCK_SH);
	$datas = @stream_get_contents($fh);
	@fclose($fh);
	
	if(false === $datas) return false;
	
	return (@unserialize(base64_decode($datas)));
}

/**
 * Try to write serialized datas into cache
 * This function uses file locking
 * Please note that this function will by default write into the siteroot cache
 *
 * @param string $filename
 * @param string $datas
 * @param string $siteroot  
 * @return boolean false or int 
 */
function writeToCache($filename, $datas, $siteroot=true)
{
	$filename = getCachePath($filename);
	$filemask = octdec(C::get('filemask', 'cfg'));
	
	$dir = dirname($filename);
	if(!is_dir($dir)) 
	{
		if(file_exists($dir)) trigger_error('invalid file '.$filename, E_USER_ERROR);
		@mkdir($dir, 0777 & $filemask);
		@chmod($dir, 0777 & $filemask);
	}
	
	$fh = @fopen($filename, 'w+b');
	if(!$fh)
		trigger_error('Cannot open file '.$filename, E_USER_ERROR);
	
	@flock($fh, LOCK_EX);
	$ret = @fwrite($fh, base64_encode(serialize($datas)));
	@fclose($fh);
	
	if(false === $ret)
		trigger_error('Cannot write in file '.$filename, E_USER_ERROR);
		
	@chmod ($filename,0666 & $filemask);
	
	return $ret;
}

/**
 * Get the path of CACHE directory of the site
 * 
 * @return string CACHE directory
 */
function getCachePath( $path = "", $site = null)
{
	$cache = new Cache_Lite(C::get('cacheOptions', 'cfg'));
	return $cache->_cacheDir . DIRECTORY_SEPARATOR 
			. ( $site ? $site : C::get('site','cfg') ) . DIRECTORY_SEPARATOR 
			. C::get('env') . DIRECTORY_SEPARATOR 
			. $path;
}
?>