<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe AuthHTTP
 */

/**
 * Classe permettant l'authentification HTTP
 * 
 * <p>Cette classe est utilisée pour l'authentification HTTP (basic)</p>
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