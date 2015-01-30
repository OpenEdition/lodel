<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des plugins
 */


/**
 * Classe de logique des plugins des sites
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

		if($error) return '_error';

		$dao = $this->_getMainTableDao();
		$vo = $dao->find('name="'.addslashes($context['name']).'"');
		$new = false;
		if(!$vo)
		{
			$new = true;
			$vo = $dao->createObject();
			$vo->name = $this->_plugin['name'];
			$vo->config = @serialize($this->_plugin['config']);
		}	
		$vo->status = 1;
		$context['id'] = $dao->save($vo, $new);

		$ret = parent::factory($context, $error, $this->_plugin['name'].'_'.__FUNCTION__, true); // call the enableAction func from the plugin

		if('_error' === $ret || $error)
		{
			$vo = $dao->find('name="'.addslashes($context['name']).'"');
			$vo->status = 0;
			$dao->save($vo);
			return '_error';
		}

		clearcache();
		return '_location:index.php?lo=plugins&do=list';
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

		if($error) return '_error';

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
		
		clearcache();
		return '_location:index.php?lo=plugins&do=list';
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