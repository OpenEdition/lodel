<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire de gestion des groupes de texte
 */

$GLOBALS['textgroups'] = array ('interface' => array ('common', 'edition', 'admin', 'lodeladmin', 'install', 'lodelloader'), 'site' => array ('site'));

/**
 * Retourne la condition SQL sur un groupe de texte spécifié
 */
function textgroupswhere($textgroups)
{
	if (!$textgroups)
		trigger_error("ERROR: which textgroups ?", E_USER_ERROR);
	if (!empty($GLOBALS['textgroups'][$textgroups])) {
		return "textgroup IN ('".join("','", $GLOBALS['textgroups'][$textgroups])."')";
	} else {
		trigger_error("ERROR: unkown textgroup", E_USER_ERROR);
	}
}