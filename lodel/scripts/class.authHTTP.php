<?php
/**
 * Fichier de la classe AuthHTTP
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
 * @author Sophie Malafosse
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id: 
 */


/**
 * Classe permettant l'authentification HTTP
 * 
 * <p>Cette classe est utilisée pour l'authentification HTTP (basic)</p>
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @since Fichier ajouté depuis la version 0.8
 * @see auth.php
 * @see loginHTTP.php
 * @see src/edition/index.php
 */

class AuthHTTP
{
	/**
	* Login récupéré dans le header
	* @var string
	*/
	var $login;

	/**
	* Mot de passe récupéré dans le header
	* @var string
	*/
	var $password;

	
	/**
	* 
	* retourne un booléen : true si le login et le mot de passe sont récupérés,  
	* false sinon
	* @return bool
	*/
	function getHeader()
	{
		$this->reset();
		if (!headers_sent())
		{
			if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
			{
				// variables non initialisées
				return false;
			}
			elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
			{
				// récupère le login et le mot de passe
				$this->login = $_SERVER['PHP_AUTH_USER'];
				$this->password = $_SERVER['PHP_AUTH_PW'];
				return true;
			}
		}
		return false;
	}

	/**
        * Renvoi du header avec demande d'authentification
        *
        * @return 
        */
	function errorLogin()
	{
		header('WWW-Authenticate: Basic realm="Authentification requise"');
	        header('HTTP/1.0 401 Unauthorized');
		echo "L'accès à cette ressource requiert une authentification : veuillez entrer le nom d'utilisateur
		et le mot de passe que vous utilisez sous Lodel";
		exit;
	}

	
	/**
	* initialisation des variables
	*/
	function reset()
	{
		$this->login = '';
		$this->password = '';
	}

	/**
	* Retourne dans un tableau le login et le mot de passe
	*
	* @return array
	*/
	function getIdentifiers()
	{
		return array(
		"login" => $this->login,
		"password" => $this->password);
	}
	
}