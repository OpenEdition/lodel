<?php
/**	
 * Logique des plugins
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
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
 * @since Fichier ajouté depuis la version 0.9
 * @version CVS:$Id: class.plugins.php 4646 2009-01-28 14:53:49Z mignot $
 */



/**
 * Classe de logique des plugins des sites
 * 
 * @package lodel/logic
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
 * @since Classe ajouté depuis la version 0.9
 * @see logic/class.plugin.php
 * @see logic.php
 */
class PluginsLogic extends MainPluginsLogic
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct("plugins");
	}

	/**
	 * Enable a plugin for a site. Must be configured before (== has already an entry in the database)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function enableAction(&$context, &$error)
	{
		if(empty($context['name']))
		{
			trigger_error('You need to specify the name of the plugin to enable', E_USER_ERROR);
		}

		parent::listAction($context, $error);  // security 
		if($error)
			return '_error';

		$dao = $this->_getMainTableDao();
		$vo = $dao->find('name="'.addslashes($context['name']).'"');
		$new = false;
		if(!$vo)
		{
			$new = true;
			$vo = $dao->createObject();
			$vo->name = $this->_plugin['name'];
			$vo->config = (isset($this->_plugin['config']) ? serialize($this->_plugin['config']) : '');
		}	
		$vo->status = 1;
		$context['id'] = $dao->save($vo, $new);

		parent::factory($context, $error, $this->_plugin['name'].'_'.__FUNCTION__, true); // call the enableAction func from the plugin
		@unlink(SITEROOT.'/CACHE/triggers');
		clearcache();
		return '_back';
	}

	/**
	 * Disable a plugin for a site. Must be configured before (== has already an entry in the database)
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function disableAction(&$context, &$error)
	{
		if(empty($context['name']))
		{
			trigger_error('You need to specify the name of the plugin to disable', E_USER_ERROR);
		}

		parent::listAction($context, $error); // security 
		if($error)
			return '_error';

		$dao = $this->_getMainTableDao();
		$vo = $dao->find('name="'.addslashes($context['name']).'"');
		if(!$vo)
		{
			$error[] = 'Cannot find the plugin '.$context['name'];
			return '_error';
		}
		
		// call the disableAction func from the plugin
		parent::factory($context, $error, $this->_plugin['name'].'_'.__FUNCTION__, true);

		$vo->status = 0;
		$context['id'] = $dao->save($vo);
		
		@unlink(SITEROOT.'/CACHE/triggers');
		clearcache();
		return '_back';
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('config' => array('longtext', '+'),
									'name' => array('text', '+'),
									);
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('name'), );
	}
	// end{uniquefields} automatic generation  //


} // class 

?>