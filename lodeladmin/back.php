<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier back
 */

require_once 'lodelconfig.php';
require_once 'auth.php';
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
require_once 'func.php';

if ($_REQUEST['back']) {
	$back = intval($back);
} else {
	$back = -1;
}
back('', $back);
return;
?>