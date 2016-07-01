<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe LodelException
 */

// Lodel error code
defined('E_USER_LODEL_BAD_REQUEST') || define('E_USER_LODEL_BAD_REQUEST', 65534);
defined('E_USER_LODEL_NOT_FOUND') || define('E_USER_LODEL_NOT_FOUND', 131068);
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
				E_DEPRECATED => 'Deprecated',
                                E_USER_LODEL_BAD_REQUEST => 'Bad Request',
                                E_USER_LODEL_NOT_FOUND => 'Page not Found'
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
	public function __construct($errstr, $errno, $errfile, $errline, $http_code = 500) 
	{
		parent::__construct();
		
		$this->debug = (int)C::get('debugMode', 'cfg');
		$this->message = nl2br($errstr);
		$this->code = $errno;
		$this->file = $errfile;
		$this->line = $errline;

		// we are maybe buffering, so clear it
		if(!C::get('redactor', 'lodeluser') || 1 > $this->debug)
			while(@ob_end_clean());

		if(!headers_sent())
		{
			header("HTTP/1.0 $http_code Internal Error");
			header("Status: $http_code Internal Error");
			header("Connection: Close");
		}

		if(C::get('contactbug', 'cfg') && ((int)C::get('debugMode', 'cfg') || (bool)C::get('sendErrorMsg', 'cfg')))
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
		$ret = '';
		if(0 < $this->debug || C::get('redactor', 'lodeluser')) {
//			$ret = '</body><p class="error">';
			$ret = (E_USER_ERROR == $this->code || E_USER_NOTICE == $this->code || E_USER_WARNING == $this->code ? '' : 'PHP ');
			$ret .= "Error ".(isset(self::$type[$this->code]) ? "(".self::$type[$this->code].")" : '')." in file '".$this->file."' on line ".$this->line." : <br />";
			$ret .= $this->message.'</p>';
		} else {
			if(C::get('showPubErrMsg', 'cfg')){
				$ret = "Sorry! Internal error. Please contact the webmaster and try reloading the page. ";
				if(C::get('contactbug', 'cfg'))
				$ret .= "(".C::get('contactbug', 'cfg').")";
			}
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
				if(1 > C::get('debugMode', 'cfg'))
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
			if (get_class($exception) == 'LodelException')
				throw $exception;
			else 
				throw new LodelException($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine());
		} 
		catch(LodelException $e)
		{
		    echo '<pre style="border: 1px red solid; padding: .5em; font: normal bold 1.2em monospace; color: red; background: yellow; white-space: pre-wrap;">';
		    switch(C::get('debugMode', 'cfg')){
			case 2:
				ob_start('htmlentities');
                    		debug_print_backtrace();
                    		ob_end_flush();
			break;
                                ob_start('htmlentities');
                                debug_print_backtrace();
                                ob_end_flush();
				die();
			break;
			case 1:
			default:
				print_r($e->getContent());
		    }
		    echo '</pre>';
		    if(C::get('dieOnErr', 'cfg')) die();
		}
	}
}

set_error_handler(array('LodelException', 'error_handler')); // errors
set_exception_handler(array('LodelException', 'exception_handler')); // exceptions not catched
error_reporting(C::get('debugMode', 'cfg') ? -1 : (E_CORE_ERROR | E_COMPILE_ERROR | E_ERROR | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED));
