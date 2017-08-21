<?php

class Install {

        const CRITICAL = 'CRITICAL';
        const LODELROOT = '../';
	static private $php_exts = array(
				'mbstring',
				'xml',
				'gd',
				'curl',
				'mysqlnd',
				'zip',
				);
	static private $error = NULL;
	static private $lodelCfgLoc = self::LODELROOT.'lodelconfig.php';	
	static private $initFile = self::LODELROOT.'lodel/install/init.sql';
	static private $initTranslationsFile = self::LODELROOT.'lodel/install/init-translations.sql';
	static private $username = 'admin';

	static private function checkError() {	
		if( self::CRITICAL === self::$error ) {
			echo "<h3 class=\"error\">Interruption, check error messages above</h3>\n";
			self::closeHtml();
			exit(); 
		} else {
			echo "<h3 class=\"ok\">Completed</h3>";
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

        static private function includeConnect() {
                defined('INC_CONNECT') || include 'connect.php';
        }

        static private function mysql_query_file($file, $droptables = FALSE) {
                self::includeConnect();
                global $db;
                $sqlfile = preg_replace('/#_M?TP_/', C::get('tableprefix', 'cfg'), file_get_contents($file));
                $sqlfile = str_replace('_CHARSET_', ' CHARACTER SET utf8 COLLATE utf8_general_ci' , $sqlfile);
                if (!$sqlfile) return;
                $len = strlen($sqlfile);
                $ilast = 0;
                for ($i = 0; $i < $len; $i++) {
                        $c = $sqlfile{$i};
                        if ($c == '\\') { $i++; continue; } // quoted char
                        if ($c == '#') {
                                for (; $i < $len; $i++) {
                                        if ($sqlfile{$i} == "\n") break;
                                        $sqlfile{$i} = " ";
                                }
                        } elseif ($c == "'") {
                                $i++;
                                for (; $i < $len; $i++) {
                                        $c = $sqlfile{$i};
                                        if ($c == '\\') { $i++; continue; } // quoted char
                                        if ($c == "'") break;
                                }
                        } elseif ($c == ";") { // end of SQL statment
                                $cmd = trim(substr($sqlfile, $ilast, $i - $ilast));
                               // echo $cmd,"\n";
                                if ($cmd) {
                                        // should we drop tables before create them ?
                                        if ($droptables && preg_match('/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?'.C::get('tableprefix', 'cfg').'(\w+)/', $cmd, $result)) {
                                                if (!$db->query('DROP TABLE IF EXISTS '.$result[1])) {
                                                        echo "<strong>SQL ERROR: ".$db->ErrorMsg()."</strong>\n";
                                                        self::$error = self::CRITICAL;
                                                }
                                        }
                                        // execute the command
                                        if (!$db->query($cmd)) {
                                                echo "<strong>SQL ERROR: ".$db->ErrorMsg()."</strong>\n";
                                                self::$error = self::CRITICAL;
                                        }
                                }
                                $ilast = $i+1;
                        }
                }
                self::checkError();
        }

        static public function checkPHPExts() {
                $msg = '';
                foreach(self::$php_exts as $ext) {
                        if(TRUE ===  extension_loaded($ext)) {
                                $msg .= "{$ext}: <strong class=\"ok\">OK</strong>\n";
                        } else {
                                $msg .= "{$ext}: <strong class=\"error\">Missing<strong>\n";
                                self::$error = self::CRITICAL;
                        }
                }
                echo $msg;
                self::checkError();
        }

	static public function includeCfg() {
		if(self::checkLodelCfgLoaded()) return;
		if(TRUE === self::checkLodelCfgExists()) {
			require 'lodelconfig.php';
		} else {
			echo "<strong class=\"error\">Fail include lodelconfig</strong>\n";
			self::$error = self::CRITICAL;
		}
		if(FALSE === self::checkLodelCfgLoaded()) {
                        echo "<strong class=\"error\">Fail load lodelconfig</strong>\n";
                        self::$error = self::CRITICAL;
		}
		self::checkError();
	}

	static public function checkDB(){
		function_exists('ADONewConnection') || require "vendor/autoload.php";
		$db = ADONewConnection(C::get('dbDriver', 'cfg'));
		$conn = $db->connect(C::get('dbhost', 'cfg'), C::get('dbusername', 'cfg'), C::get('dbpasswd', 'cfg'), C::get('database', 'cfg'));
		if(TRUE !== $conn){
			echo "SQL ERROR: ".$db->ErrorMsg()."\n";
			self::$error = self::CRITICAL;
		}
		self::checkError();
		return $conn;
	}

	static public function createTables(){
		self::mysql_query_file(self::$initFile);
	}

        static public function insertTexts(){
                self::mysql_query_file(self::$initTranslationsFile);
        }

	static public function openHtml(){
		$open = <<<EOD
<!DOCTYPE html>
<html>
<head>
<title>Lodel Installation</title>
<meta charset="UTF-8">
<style>
.ok{color:green}
.error{color:red}
</style>
</head>
<body>
<pre>
EOD;
		echo $open;
	}

        static public function closeHtml(){
                $close = <<<EOD
</pre>
</body>  
</html>
EOD;
                echo $close;
        }

	
}



Install::openHtml();
echo <<<EOD
<h1>Lodel Installation</h1>
EOD;
echo <<<EOD
<h2>PHP Extensions Check</h2>
EOD;
Install::checkPHPExts();
echo <<<EOD
<h2>Config File</h2>
EOD;
Install::includeCfg();
echo <<<EOD
<h2>DB Connection</h2>
EOD;
Install::checkDB();
echo <<<EOD
<h2>Tables Creation</h2>
EOD;
Install::createTables();
echo <<<EOD
<h2>Texts Insertion</h2>
EOD;
Install::insertTexts();
echo <<<EOD
<h2>SuperAdmin Creation</h2>
EOD;
Install::closeHtml();	
