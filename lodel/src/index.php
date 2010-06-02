<?php
/**
 * Fichier racine - porte d'entrée principale du site
 *
 * Ce fichier permet de faire appel aux différentes entités (documents), via leur id, leur
 * identifier (lien permanent). Il permet aussi d'appeler un template particulier (via l'argument
 * page=)
 * Voici des exemples d'utilisations
 * <code>
 * index.php?/histoire/france/charlemagne-le-pieux
 * index.php?id=48
 * index.php?page=rss20
 * index.php?do=view&idtype=2
 * </code>
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
 * @package lodel/source
 */

require 'siteconfig.php';

try
{
	//gestion de l'authentification
	include 'auth.php';
	authenticate();
    
	// record the url if logged
	if (C::get('visitor', 'lodeluser')) {
		recordurl();
	}

	if(!C::get('debugMode', 'cfg') && !C::get('visitor', 'lodeluser'))
	{
		if(View::getView()->renderIfCacheIsValid()) exit();
	}

    	$accepted_logic = array();
	$called_logic = null;

	if ($do = C::get('do'))
	{
		if ($do === 'edit' || $do === 'view') 
		{
			// check for the right to change this document
			if (!($idtype = C::get('idtype'))) {
				trigger_error('ERROR: idtype must be given', E_USER_ERROR);
			}
			defined('INC_CONNECT') || include 'connect.php'; // init DB if not already done
			$vo = DAO::getDAO('types')->find("id='{$idtype}' and public>0 and status>0");
			if (!$vo) {
				trigger_error("ERROR: you are not allowed to add this kind of document", E_USER_ERROR);
			}
			unset($vo);
			if(!C::get('editor', 'lodeluser'))
			{
				$lodeluser['temporary'] = true;
				$lodeluser['rights']  = LEVEL_REDACTOR; // grant temporary
				$lodeluser['visitor'] = 1;
				$lodeluser['groups'] = 1;
				$lodeluser['editor']  = 0;
				$lodeluser['redactor'] = 1;
				$lodeluser['admin']	= 0;
				$lodeluser['adminlodel'] = 0;
				$GLOBALS['nodesk'] = true;
				C::setUser($lodeluser);
				unset($lodeluser);
				$_REQUEST['clearcache'] = false;
				C::set('nocache', false);
			}
			$accepted_logic = array('entities_edition');
            		C::set('lo', 'entities_edition');
			$called_logic = 'entities_edition';
		} elseif('_' === $do{0}) {
			$accepted_logic = array('plugins');
			C::set('lo', 'plugins');
		} else {
			trigger_error('ERROR: unknown action', E_USER_ERROR);
		}
	}

	Controller::getController()->execute($accepted_logic, $called_logic);
	exit();
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
