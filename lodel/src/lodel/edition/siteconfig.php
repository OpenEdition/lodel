<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Siteconfig de lodel/edition
 */


define('SITEROOT', '../../');

if (!chdir(SITEROOT)) {
	trigger_error("ERROR: chdir fails", E_USER_ERROR);
}

require 'siteconfig.php';
chdir('lodel/edition');
?>