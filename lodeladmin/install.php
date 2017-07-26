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
	const CRITICAL = "CRITICAL";

	static public function checkPHPExts() { 
		$msg = '';
		foreach(self::$php_exts as $ext) {
			if(TRUE ===  extension_loaded($ext)) {
				$msg .= "{$ext}: Ok\n";
			} else {
				$msg .= "{$ext}: Missing\n";
				self::$error = self::CRITICAL;
			}	
		}
		echo $msg;	
	}

	static public function checkLodelCfgExists() {
		return is_file(self::$lodelCfgLoc) && is_readable(self::$lodelCfgLoc);
	}

	static public function includeCfg() {
		if(defined('INC_LODELCONFIG')) return;
		if(TRUE === self::checkLodelCfgExists()) {
			require 'lodelconfig.php';
		} else {
			trigger_error('Fail lodelconfig', E_USER_ERROR);
			self::$error = self::CRITICAL;
			exit();
		}
	}

	static public function checkDB(){
		function_exists(ADONewConnection) || require "vendor/autoload.php";
		$db = ADONewConnection(C::get('dbDriver', 'cfg'));
		$conn = $db->connect(C::get('dbhost', 'cfg'), C::get('dbusername', 'cfg'), C::get('dbpasswd', 'cfg'), C::get('database', 'cfg'));
		if(TRUE !== $conn){
			trigger_error("SQL ERROR :\n".$db->ErrorMsg(), E_USER_ERROR);
			self::$error = self::CRITICAL;
			exit();
		}
		return $conn;
	}

	static public function includeConnect() {
		defined('INC_CONNECT') || include 'connect.php';
	}

	static public function mysql_query_file() {
		self::includeConnect();
		self::includeCfg();
		global $db;
		$initFile = LODELROOT.'lodel/install/init.sql';
		var_dump($initFile); exit;
                $sqlfile = preg_replace('/#_M?TP_/', C::get('tableprefix', 'cfg'), file_get_contents($filename));
                $sqlfile = str_replace('_CHARSET_', ' CHARACTER SET utf8 COLLATE utf8_general_ci' , $sqlfile);
                if (!$sqlfile) return;

                $len=strlen($sqlfile);
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
                                #echo $cmd,"<BR>\n";
                                if ($cmd) {
                                        // should we drop tables before create them ?
                                        if ($droptables && preg_match('/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?'.$cfg['tableprefix'].'(\w+)/',$cmd,$result)) {
                                                if (!@mysql_query('DROP TABLE IF EXISTS '.$result[1])) {
                                                        $err.="$cmd <font COLOR=red>".mysql_error().'</font><br>';
                                                }
                                        }
                                        // execute the command
                                        if (!@mysql_query($cmd)) {
                                                $err.="$cmd <font COLOR=red>".mysql_error().'</font><br>';
                                        }
                                }
                                $ilast=$i+1;
                        }
                }
                return $err;
        }

	
}

echo "<pre>";

Install::checkPHPExts();
Install::includeCfg();
Install::checkDB();
Install::includeConnect();
	

echo "</pre>";
