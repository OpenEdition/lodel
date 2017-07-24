<?php

class Install {

	static public $php_exts = array(
				'mbstring',
				'xml',
				'gd',
				'curl',
				'mysqlnd',
				'zip'
				);
	

	static public function checkPHPExts($ext) { 
		return extension_loaded($ext);		
	}

	static public function checkLodelCfgExists() {
		return is_file('../lodelconfig.php') && is_readable('../lodelconfig.php');
	}
}

var_dump(Install::checkLodelCfgExists());
