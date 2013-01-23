<?php
/**
 * Fichier de la classe LodelException
 *
 * PHP 5
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
 * @since Fichier ajouté depuis la version 0.9
 */

// 5.3
defined('E_DEPRECATED') || define('E_DEPRECATED', 8192);
defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', 16384);
// 5.2
defined('E_RECOVERABLE_ERROR') || define('E_RECOVERABLE_ERROR', 4096);
// 5.0
defined('E_STRICT') || define('E_STRICT', 2048); 

class LodelException extends Exception 
{
	static $type = array( 	E_ERROR => 'Error',
				E_WARNING => 'Warning',
				E_PARSE => 'Parse Error',
				E_NOTICE => 'Notice',
				E_CORE_ERROR => 'Core Error',
				E_CORE_WARNING => 'Core Warning',
				E_COMPILE_ERROR => 'Compile Error',
				E_COMPILE_WARNING => 'Compile Warning',
				E_USER_WARNING => 'Internal Warning',
				E_USER_ERROR => 'Internal Error',
				E_USER_NOTICE => 'User Notice',
				E_STRICT => 'Strict Error',
				E_RECOVERABLE_ERROR => 'Recoverable Error',
				E_DEPRECATED => 'Deprecated'
				);
	/**
	 * Constructor
	 * Will call Exception::__construct, send header if not already done, send mail if $contactbug have been set
	 * 
	 * @param string $errstr the error message
	 * @param int $errno the error code
	 * @param string $errfile the file where the error occured
	 * @param int $errline the line where the error occured
	 */
	public function __construct($errstr, $errno, $errfile, $errline) 
	{
		parent::__construct();
		
		$this->debug = (bool)C::get('debugMode', 'cfg');
		$this->message = nl2br($errstr);
		$this->code = $errno;
		$this->file = $errfile;
		$this->line = $errline;

		// we are maybe buffering, so clear it
		if(!C::get('redactor', 'lodeluser') || !$this->debug)
			while(@ob_end_clean());

		if(!headers_sent())
		{
			header("HTTP/1.0 500 Internal Error");
			header("Status: 500 Internal Error");
			header("Connection: Close");
		}

		if(C::get('contactbug', 'cfg') && ((bool)C::get('debugMode', 'cfg') || (bool)C::get('sendErrorMsg', 'cfg')))
		{
			$sujet = "[BUG] LODEL ".C::get('version', 'cfg')." - ".C::get('site', 'cfg');
			$contenu = "Erreur sur la page ";
			if (isset($_SERVER['HTTP_HOST']))
				$contenu .= "http://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$_SERVER['REQUEST_URI']." (' ".$_SERVER["REMOTE_ADDR"]." ')\n";
			$contenu .= (E_USER_ERROR == $this->code || E_USER_NOTICE == $this->code || E_USER_WARNING == $this->code) ? '' : 'PHP ';
			$contenu .= "Error ".(isset(self::$type[$this->code]) ? "(".self::$type[$this->code].")" : '')." in file '".$this->file."' on line ".$this->line." : ".$this->message;
			@mail(C::get('contactbug', 'cfg'), $sujet, $contenu);
		}
	}

	/**
	 * Return the error message if logged-in, else a standard message
	 */
	public function getContent()
	{
		if($this->debug || C::get('redactor', 'lodeluser')) {
			$ret = '</body><p class="error">';
			$ret .= (E_USER_ERROR == $this->code || E_USER_NOTICE == $this->code || E_USER_WARNING == $this->code ? '' : 'PHP ');
			$ret .= "Error ".(isset(self::$type[$this->code]) ? "(".self::$type[$this->code].")" : '')." in file '".$this->file."' on line ".$this->line." : <br />";
			$ret .= $this->message.'</p>';
		} else {
			$ret = "Sorry! Internal error. Please contact the webmaster and try reloading the page. ";
            		if(C::get('contactbug', 'cfg'))
                		$ret .= "(".C::get('contactbug', 'cfg').")";
		}
		return $ret;
	}
	
	/**
	 * Error handler
	 * This function either throws an exception or just ignores the message if error level is lower than error code
	 *
	 * @param int $errno the error code
	 * @param string $errstr the error message
	 * @param string $errfile the file where the error occured
	 * @param int $errline the line where the error occured
	 */
	public static function error_handler($errno, $errstr='', $errfile='', $errline=0) 
	{
		// if error was triggered by @function
		// or error level is lower than error code
		// just ignore it
		if(($err = error_reporting()) === 0 || !($err & $errno)) 
		{
    			return true;
  		}

		switch($errno) 
		{
            		case E_STRICT:
			case E_NOTICE:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			case E_USER_NOTICE:
			case E_RECOVERABLE_ERROR:
			case E_CORE_WARNING:
			case E_WARNING:
			case E_USER_WARNING:
			case E_COMPILE_WARNING:
				if(!C::get('debugMode', 'cfg'))
				{
					error_log('['.(isset(self::$type[$errno]) ? self::$type[$errno] : 'unknown').' - '.C::get('site','cfg').'] '.$errstr.' in file '.$errfile.' on line '.$errline, 0);
					break;
				}
			case E_USER_ERROR:
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			default: 
				self::exception_handler(new LodelException($errstr, $errno, $errfile, $errline));
			break;
		}
		return true;
	}

	/**
	 * Exception handler
	 *
	 * @param object $exception the exception object
	 */
	public static function exception_handler($exception)
	{
		try {
			throw new LodelException($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine());
		} 
		catch(LodelException $e)
		{
			die($e->getContent());
		}
	}
}

set_error_handler(array('LodelException', 'error_handler')); // errors
set_exception_handler(array('LodelException', 'exception_handler')); // exceptions not catched
error_reporting(C::get('debugMode', 'cfg') ? -1 : (E_CORE_ERROR | E_COMPILE_ERROR | E_ERROR | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED));
