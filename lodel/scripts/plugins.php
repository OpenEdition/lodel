<?php

abstract class Plugins
{
	static protected $_config;

	static public $triggers = array('preview','postview','preedit','postedit','prelogin','postlogin','preauth','postauth');

	static public function init($classname)
	{ // until PHP 5.3
	//	self::$_config = C::get(get_called_class().'.config', 'triggers');
		self::$_config = C::get($classname.'.config', 'triggers');
		if(false === self::$_config) trigger_error('ERROR: cannot fetch config values for plugin '.$classname, E_USER_ERROR);
	}

	static protected function _checkRights($level)
	{
		return ((int)$level < (int)C::get('rights', 'lodeluser'));
	}

	abstract static public function enableAction(&$context, &$error);
	abstract static public function disableAction(&$context, &$error);
}

?>