<?php

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
// 					E_DEPRECATED => 'Deprecated' PHP 5.3
					);
					
/*		if(TRUE === $request->get('CONFIG', 'log')) { // doit-on logger ?
			if(E_USER_ERROR == $this->errno) { // erreur appli
				$errorType = "[INTERNAL]";
			} else { // erreur php
				$errorType = "[PHP ".$this->type[$this->errno]."]";
			}
			$logMsg = $errorType." ".$this->errfile.":".$this->errline." : ".$this->errstr.".";
			require_once 'log.php';
			$logObj =& CLog::singleton();
			$ret = $logObj->log($logMsg, $errorType);
			if(TRUE !== $ret) { // erreur pendant l'enregistrement des logs
				$this->errstr .= "<p style=\"background-color:white;color:red;\">[INTERNAL Logs]:</p>".$ret."<p style=\"background-color:white;color:red;\">[/INTERNAL]</p>";
			}
		}*/	
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
		switch($errno) {
			case E_NOTICE:
			//case E_DEPRECATED: pour PHP 5.3
			//case E_USER_DEPRECATED: pour PHP 5.3
			case E_RECOVERABLE_ERROR:
			case E_STRICT:
// 			echo $errno.': '.$errstr.' on line '.$errline.' in file '.$errfile.'<br>';
			break;
			
			case E_WARNING:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			/* TODO ? */
			break;
			
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
?>