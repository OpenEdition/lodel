<?php
/**
 * Fichier utilitaire pour l'import
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

/**
 * Extraction d'un fichier importé dans le répertoire d'import
 *
 * Cette fonction est utilisée dans l'import de données, de ME et de traductions
 * @param string $footprint
 * @param array &$context le context
 * @param string $ext l'extension du fichier (par défaut 'zip')
 * @return le fichier qui a été extrait du répertoire d'import
 */
function extract_import($footprint, & $context, $ext = 'zip')
{

	$context['importdir'] = C::get('importdir', 'cfg');
	$GLOBALS['fileregexp'] = '('.$footprint.')-\w+(?:-\d+)?.'.$ext;

	$GLOBALS['importdirs'] = array ( C::get('home', 'cfg')."../install/plateform");
	if ($context['importdir']) {
		$GLOBALS['importdirs'][] = $context['importdir'];
	}

	$archive = !empty($_FILES['archive']['tmp_name']) ? $_FILES['archive']['tmp_name'] : null;
	if($archive) $context['error_upload'] = $_FILES['archive']['error'];

	if (empty($context['error_upload']) && $archive && $archive != "none" && is_uploaded_file($archive)) { // Upload
		$file = cache_get_path(null) . DIRECTORY_SEPARATOR . $_FILES['archive']['name'];
		if (!preg_match("/^".$GLOBALS['fileregexp']."$/", $file)) {
			$file = $footprint."-import-".date("dmy").".".$ext;
		}

		if (!move_uploaded_file($archive, $file)) {
			trigger_error("ERROR: a problem occurs while moving the uploaded file.", E_USER_ERROR);
		}
	} else { // rien
		$file = "";
	}
	return $file;
}

?>