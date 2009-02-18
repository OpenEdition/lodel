<?php
// 5.3
if(!defined('E_DEPRECATED'))
	define('E_DEPRECATED', 8192);
if(!defined('E_USER_DEPRECATED'))
	define('E_USER_DEPRECATED', 16384);
// 5.2
if(!defined('E_RECOVERABLE_ERROR'))
	define('E_RECOVERABLE_ERROR', 4096);

class LodelException extends Exception 
{
	public function __construct($errstr, $errno, $errfile, $errline) 
	{
		parent::__construct();
		
		$this->debug = (bool)$GLOBALS['debugMode'];
		$this->errstr = $errstr;
		$this->errno = $errno;
		$this->errfile = $errfile;
		$this->errline = $errline;
		$this->type = array( 	E_ERROR => 'Error',
					E_WARNING => 'Warning',
					E_PARSE => 'Parse Error',
					E_NOTICE => 'Notice',
					E_CORE_ERROR => 'Core Error',
					E_CORE_WARNING => 'Core Warning',
					E_COMPILE_ERROR => 'Compile Error',
					E_COMPILE_WARNING => 'Compile Warning',
					E_USER_WARNING => 'Internal Warning',
					E_USER_ERROR => 'Internal Error',
					E_STRICT => 'Strict Error',
					E_RECOVERABLE_ERROR => 'Recoverable Error',
					E_DEPRECATED => 'Deprecated'
					);

		if(!headers_sent())
		{
			header("HTTP/1.0 500 Internal Error");
			header("Status: 500 Internal Error");
			header("Connection: Close");
		}

		if($GLOBALS['contactbug'])
		{
			$sujet = "[BUG] LODELEXCEPTION - ".$GLOBALS['version']." - ".$GLOBALS['currentdb'];
			$contenu = "Erreur de requete sur la page http://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$_SERVER['REQUEST_URI']." (' ".$_SERVER["REMOTE_ADDR"]." ')\n";
			$contenu .= (E_USER_ERROR == $this->errno ? 'Internal' : 'PHP');
			$contenu .= " error (type ".$this->type[$this->errno].") in file '".$this->errfile."' on line ".$this->errline." : \n".$this->errstr;
			@mail($GLOBALS['contactbug'], $sujet, $contenu);
		}	
	}

	public function getContent() 
	{
		if(TRUE === $this->debug) {
			$ret = '</body><p class="error">';
			$ret .= (E_USER_ERROR == $this->errno ? 'Internal' : 'PHP');
			$ret .= " error in file '".$this->errfile."' on line ".$this->errline." : <br />";
			$ret .= $this->errstr.'</p>';
		} else {
			$ret = "Internal error. Please contact the webmaster.";
		}
		return $ret;
	}
	
	public static function exception_error_handler($errno, $errstr, $errfile, $errline) 
	{
		// if error was triggered by @function, juste ignore it
		if(error_reporting() === 0) 
		{
    			return true;
  		}

		switch($errno) 
		{
			case E_NOTICE:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			case E_STRICT:
			case E_USER_NOTICE:
			break;

			case E_RECOVERABLE_ERROR:
			case E_WARNING:
			case E_USER_WARNING:
			case E_USER_ERROR:
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
			default: throw new LodelException($errstr, $errno, $errfile, $errline);
			break;
		}
		return true;
	}
}

set_error_handler(array('LodelException', 'exception_error_handler'));
// les niveaux d'erreur à afficher
error_reporting(E_ALL ^ E_NOTICE);

?>