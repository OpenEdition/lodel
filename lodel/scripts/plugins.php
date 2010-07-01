<?php
/**
 * Fichier utilisé comme base d'un plugin utilisant une classe
 *
 * PHP version 5
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
 * @version CVS:$Id:
 * @package lodel
 * @since Fichier ajouté depuis la version 0.9
 */

/**
 * Base class for plugins using class as hook
 * Classe servant de base pour les plugins utilisant les hook de type class
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