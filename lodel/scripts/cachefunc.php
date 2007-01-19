<?
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
 * @package lodel
 */


/**
 * Nettoyage du répertoire de CACHE
 *
 * Cette fonction appelle removefilesincache()
 */
function clearcache()
{
	if (defined("SITEROOT")) {
		removefilesincache(SITEROOT, SITEROOT."lodel/edition", SITEROOT."lodel/admin");
	}	else {
		removefilesincache(".");
	}
}

/**
 * Nettoyage des fichiers du répertoire de CACHE
 *
 * Note importante : cette fonction pourrait être écrite de facon beaucoup plus simple avec 
 * de la récurrence. Pour des raisons de sécurité/risque de bugs, elle est doublement 
 * protegée.
 * On ajoute le répertoire CACHE dans le code, ce qui empêche de détruire le contenu d'un autre
 * répertoire. On ne se propage pas de facon récurrente.
 */
function removefilesincache()
{
	foreach (func_get_args() as $rep) {
		if (!$rep) {
			$rep = ".";
		}
		$rep .= "/CACHE";
		$fd = opendir($rep) or die("Impossible d'ouvrir $rep");

		while (($file = readdir($fd)) !== false) {
			#echo $rep," ",$file," ",(substr($file,0,1)==".") || ($file=="CVS"),"<br />";
			if (($file[0] == ".") || ($file == "CVS") || ($file == "upload"))
				continue;
			$file = $rep. "/". $file;
			if (is_dir($file)) { //si c'est un répertoire on l'ouvre
				$rep2 = $file;
				$fd2 = opendir($rep2) or die("Impossible d'ouvrir $file");
				while (($file = readdir($fd2)) !== false) {
					if (substr($file, 0, 1) == ".")
						continue;
					$file = $rep2."/".$file;
					if (is_file($file))	{
						@unlink($file);
					}
				}
				closedir($fd2);
			}	elseif (is_file($file))	{
				unlink($file);
			}
		}
		closedir($fd);
	}
}
?>