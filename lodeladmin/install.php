<?php

class Install {

	static private $php_exts = array(
				'mbstring',
				'xml',
				'gd',
				'curl',
				'mysqlnd',
				'zip',
				);
	static private $lodelCfgLoc = '../lodelconfig.php';	
	static private $error = NULL;
	const CRITICAL = 'CRITICAL';
	const LODELROOT = '../';
	static private $initFile = 'lodel/install/init.sql';
	static private $initTranslationsFile = 'lodel/install/init-translations.sql';

	static public function checkPHPExts() { 
		$msg = '';
		foreach(self::$php_exts as $ext) {
			if(TRUE ===  extension_loaded($ext)) {
				$msg .= "{$ext}: <strong>Ok</strong>\n";
			} else {
				$msg .= "{$ext}: <strong class=\"error\">Missing<strong>\n";
				self::$error = self::CRITICAL;
			}	
		}
		echo $msg;	
		self::checkError();
	}

	static private function checkError() {	
		if( self::CRITICAL === self::$error ) {
			die("<strong class=\"error\">Interruption, check error messages above</strong>\n"); 
		}
	}

	static private function checkLodelCfgExists() {
		return is_file(self::$lodelCfgLoc) && is_readable(self::$lodelCfgLoc);
	}

	static private function checkLodelCfgLoaded() {
		return defined('INC_LODELCONFIG');
	}

	static private function checkLodelCfg() {
		return self::checkLodelCfgExists() && self::checkLodelCfgLoaded();
	}

	static public function includeCfg() {
		if(self::checkLodelCfgLoaded()) return;
		if(TRUE === self::checkLodelCfgExists()) {
			require 'lodelconfig.php';
		} else {
			trigger_error('Fail include lodelconfig', E_USER_ERROR);
			self::$error = self::CRITICAL;
		}
		if(FALSE === self::checkLodelCfgLoaded()) {
                        trigger_error('Fail load lodelconfig', E_USER_ERROR);
                        self::$error = self::CRITICAL;
		}
		self::checkError();
	}

	static public function checkDB(){
		function_exists('ADONewConnection') || require "vendor/autoload.php";
		$db = ADONewConnection(C::get('dbDriver', 'cfg'));
		$conn = $db->connect(C::get('dbhost', 'cfg'), C::get('dbusername', 'cfg'), C::get('dbpasswd', 'cfg'), C::get('database', 'cfg'));
		if(TRUE !== $conn){
			trigger_error("SQL ERROR :\n".$db->ErrorMsg(), E_USER_ERROR);
			self::$error = self::CRITICAL;
		}
		self::checkError();
		return $conn;
	}

	static public function createTables(){
		self::mysql_query_file(LODELROOT.self::$initFile);
		echo "Tables creation: <strong>OK</strong>\n";
	}

        static public function insertTexts(){
                self::mysql_query_file(LODELROOT.self::$initTranslationsFile);
		echo "Texts insertion: <strong>OK</strong>";
        }

	static private function includeConnect() {
		defined('INC_CONNECT') || include 'connect.php';
	}

	static private function mysql_query_file($file) {
		self::includeConnect();
		global $db;
                $sqlfile = preg_replace('/#_M?TP_/', C::get('tableprefix', 'cfg'), file_get_contents($file));
                $sqlfile = str_replace('_CHARSET_', ' CHARACTER SET utf8 COLLATE utf8_general_ci' , $sqlfile);
                if (!$sqlfile) return;
		$err = '';
		$droptables = FALSE;
                $len=strlen($sqlfile);
		$ilast = 0;
                for ($i=0; $i<$len; $i++) {
                        $c=$sqlfile{$i};
                        if ($c=='\\') { $i++; continue; } // quoted char
                        if ($c=='#') { 
                                for (; $i<$len; $i++) {
                                        if ($sqlfile{$i}=="\n") break;
                                        $sqlfile{$i}=" ";
                                }      
                        } elseif ($c=="'") {
                                $i++;
                                for (; $i<$len; $i++) {
                                        $c=$sqlfile{$i};
                                        if ($c=='\\') { $i++; continue; } // quoted char
                                        if ($c=="'") break;
                                }
                        } elseif ($c==";") { // end of SQL statment
                                $cmd=trim(substr($sqlfile,$ilast,$i-$ilast));
                                //echo $cmd,"\n";
                                if ($cmd) {
                                        // should we drop tables before create them ?
                                        if ($droptables && preg_match('/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?'.$cfg['tableprefix'].'(\w+)/',$cmd,$result)) {
                                                if (!$db->query('DROP TABLE IF EXISTS '.$result[1])) {
				                        trigger_error("SQL ERROR :\n".$db->ErrorMsg(), E_USER_ERROR);
                        				self::$error = self::CRITICAL;
                                                }
                                        }
                                        // execute the command
                                        if (!$db->query($cmd)) {
			                        trigger_error("SQL ERROR :\n".$db->ErrorMsg(), E_USER_ERROR);
                        			self::$error = self::CRITICAL;
                                        }
                                }
                                $ilast=$i+1;
                        }
                }
                self::checkError();
        }

	
}



echo "<pre>";

Install::checkPHPExts();
Install::includeCfg();
Install::checkDB();
Install::createTables();
Install::insertTexts();
	

echo "</pre>";
