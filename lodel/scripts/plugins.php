<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilisé comme base d'un plugin utilisant une classe
 */

/**
 * Base class for plugins using class as hook
 * Classe servant de base pour les plugins utilisant les hook de type class
 */
abstract class Plugins
{
	/**
	 * @var array
	 */
	static private $_instances = array();
	/**
	 * @var array
	 */
	static private $_triggers = array('preview','postview','preedit','postedit','prelogin','postlogin','preauth','postauth');
	/**
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Constructor
	 * Set the config vars
	 *
	 * @param string $classname the class name of the calling plugin
	 */
	protected function __construct($classname)
	{
		$this->_config = C::get($classname.'.config', 'triggers');
		if(false === $this->_config)
		{
			defined('INC_CONNECT') || include 'connect.php';
			global $db;
			$config = $db->GetOne(lq('SELECT config FROM #_TP_plugins WHERE name='.$db->quote($classname)));
			if(false === $config)
				trigger_error('ERROR: can not fetch config values for plugin '.$classname, E_USER_ERROR);
			$this->_config = unserialize($config);
		}
		
		if(!empty($this->_config))
		{
			foreach($this->_config as $var=>$values)
			{
				if(!isset($values['value']) && isset($values['defaultValue'])) $this->_config[$var]['value'] = $values['defaultValue'];
			}
		}
	}

	static public function get($plugin)
	{
		if(!isset(self::$_instances[$plugin]))
		{
			defined('INC_CONNECT') || include 'connect.php';
			global $db;
			if(!defined('backoffice-lodeladmin'))
				usecurrentdb();
			$enabled = $db->GetOne(lq('SELECT status FROM #_TP_plugins WHERE name='.$db->quote($plugin)));
			if(!$enabled) trigger_error('ERROR: sorry the plugin '.$plugin.' is not enabled, please contact your administrator', E_USER_ERROR);
			self::$_instances[$plugin] = new $plugin($plugin);
		}

		return self::$_instances[$plugin];
	}

	static public function getTriggers()
	{
		return self::$_triggers;
	}

	/**
	 * Returns the config vars
	 *
	 * @param string $classname the class name of the calling plugin
	 */
	public function getConfig()
	{
		return $this->_config;
	}

	/**
	 * Compare the user rights against level passed by argument
	 *
	 * @param int $level the level to compare the user rights to
	 */
	protected function _checkRights($level)
	{
		return ((bool)(C::get('rights','lodeluser') >= $level));
	}

	/**
	 * Called when enabling a plugin
	 * This method is abstract, it HAS to be defined in child class
	 *
	 * @param array $context the $context, by reference
	 * @param array $error the error array, by reference
	 */
	abstract public function enableAction(&$context, &$error);

	/**
	 * Called when disabling a plugin
	 * This method is abstract, it HAS to be defined in child class
	 *
	 * @param array $context the $context, by reference
	 * @param array $error the error array, by reference
	 */
	abstract public function disableAction(&$context, &$error);
}