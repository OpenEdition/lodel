<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier racine de lodel/edition
 */


define('backoffice', true);
define('backoffice-edition', true);

require 'siteconfig.php';

try
{
	include 'auth.php';
    C::set('env', 'edition');

	// Authentification HTTP pour les flux RSS coté édition (flux du tableau de bord) : Cf. auth.php
	if (C::get('page') == 'backend' && C::get('format')) {
		authenticate(LEVEL_VISITOR, 'HTTP');
	}
	else {
		authenticate(LEVEL_VISITOR);
	}
	if (!C::get('do') && !C::get('lo')) 
	{
		recordurl();
	
		$id = C::get('id');
		if ($id) {
			do {
				$row = $db->getRow(lq("SELECT tpledition,idparent,idtype FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='$id'"));
				if ($row === false) {
					trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
				if (!$row) {
					header ("Location: not-found.html");
					return;
				}
				$base              = $row['tpledition'];
				$idparent          = $row['idparent'];
				$context['idtype'] = $row['idtype'];
				if (!$base) {
					$context['id'] = $id = $idparent;
				}
			} while (!$base && $idparent);
		} else {
			if ($base = C::get('page')) { // call a special page (and template)
				if (strlen($base) > 64 || preg_match("/[^a-zA-Z0-9_\/-]/", $base)) {
					trigger_error("invalid page", E_USER_ERROR);
				}
			} else {
				$base = 'edition';
			}
		}
		View::getView()->renderCached($base);
		return;
	} else {
		// automatic logic
		if(!C::get('lo')) {
			$do = C::get('do');
			switch ($do) { // Detection automatique de la logique en fonction de l'action
				case 'move':
				case 'preparemove':
				case 'changestatus':
				case 'download':
					$lo = 'entities_advanced';
					break;
				case 'cleanIndex':
				case 'deleteIndex':
				case 'addIndex':
					$lo = 'entities_index';
					break;
				case 'view':
				case 'edit':
					$lo = 'entities_edition';
					break;
				case 'import':
					$lo = 'entities_import';
					break;
				default :
					$lo = '_' === $do{0} ? 'plugins' : 'entities';
					break;
			}
			C::set('lo', $lo);
		} else {
			if ((C::get('do') === 'delete')&& (C::get('lo') === 'tasks')) {
				$mask = C::get('tmp_importdir', 'cfg')."*-".C::get('id','lodeluser')."-".$context['fromdoc'];
				array_map("unlink", glob ($mask));
			}
		}
		Controller::getController()->execute(array('entities', 'entities_advanced', 'entities_edition', 'entities_import', 'entities_index', 'filebrowser', 'tasks', 'xml', 'users', 'plugins'), C::get('lo'));
	}
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
