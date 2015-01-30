<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier racine - porte d'entrée principale du site
 *
 * Ce fichier permet de faire appel aux différentes entités (documents), via leur id, leur
 * identifier (lien permanent). Il permet aussi d'appeler un template particulier (via l'argument
 * page=)
 * Voici des exemples d'utilisations
 * <code>
 * index.php?/histoire/france/charlemagne-le-pieux
 * index.php?id=48
 * index.php?page=rss20
 * index.php?do=view&idtype=2
 * </code>
 */

require 'siteconfig.php';

try
{
	//gestion de l'authentification
	include 'auth.php';
	authenticate();
    
	// record the url if logged
	if (C::get('visitor', 'lodeluser')) {
		recordurl();
	}

	if(!C::get('debugMode', 'cfg') && !C::get('visitor', 'lodeluser'))
	{
		if(View::getView()->renderIfCacheIsValid()) exit();
	}

    	$accepted_logic = array();
	$called_logic = null;

	if ($do = C::get('do'))
	{
		if ($do === 'edit' || $do === 'view') 
		{
			$lo = C::get('lo');
			if(!$lo || $lo != 'texts')
			{
				// check for the right to change this document
				if (!($idtype = C::get('idtype'))) {
					trigger_error('ERROR: idtype must be given', E_USER_ERROR);
				}
				defined('INC_CONNECT') || include 'connect.php'; // init DB if not already done
				$vo = DAO::getDAO('types')->find("id='{$idtype}' and public>0 and status>0");
				if (!$vo) {
					trigger_error("ERROR: you are not allowed to add this kind of document", E_USER_ERROR);
				}
				unset($vo);
				if(!C::get('editor', 'lodeluser'))
				{
					$lodeluser['temporary'] = true;
					$lodeluser['rights']  = LEVEL_REDACTOR; // grant temporary
					$lodeluser['visitor'] = 1;
					$lodeluser['groups'] = 1;
					$lodeluser['editor']  = 0;
					$lodeluser['redactor'] = 1;
					$lodeluser['admin']	= 0;
					$lodeluser['adminlodel'] = 0;
					$GLOBALS['nodesk'] = true;
					C::setUser($lodeluser);
					unset($lodeluser);
					$_REQUEST['clearcache'] = false;
					C::set('nocache', false);
				}
				$accepted_logic = array('entities_edition');
            			C::set('lo', 'entities_edition');
				$called_logic = 'entities_edition';
			}
			elseif($lo == 'texts')
			{
				$accepted_logic = array($lo);
				$called_logic = $lo;
			}
		} elseif('_' === $do{0}) {
			$accepted_logic = array('plugins');
			C::set('lo', 'plugins');
		} else {
			trigger_error('ERROR: unknown action', E_USER_ERROR);
		}
	}

	Controller::getController()->execute($accepted_logic, $called_logic);
	exit();
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
