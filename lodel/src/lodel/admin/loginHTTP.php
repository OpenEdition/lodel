<?php
/**
 * Fichier de Login HTTP (basic)
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
 * @author Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source/lodel/admin
 */

require_once 'siteconfig.php';
require_once 'auth.php';
require_once 'class.authHTTP.php';

$httpAuth = new AuthHTTP();
if ($httpAuth->getHeader())
{
	// récupère les identifiants (login/password) du header
	$identifiers = $httpAuth->getIdentifiers();

	require_once 'func.php';
	extract_post($identifiers);

	require_once 'connect.php';
	require_once 'loginfunc.php';

	// les identifiants ne correspondent pas à un utilisateur Lodel
	if (!check_auth($context['login'],$context['password'],$site))
	{
		$httpAuth->errorLogin();	
 	 }

// OK
	else
	{
		$httpAuth->reset();
		$context['password'] = $passwd = 0;
		return;
	}
}

else $httpAuth->errorLogin();
?>
