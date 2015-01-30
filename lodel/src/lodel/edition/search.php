<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Script de recherche
 */

define('backoffice', true);
define('backoffice-edition', true);
require 'siteconfig.php';

try
{
	include 'auth.php';
    C::set('env', 'edition');
	authenticate(LEVEL_VISITOR);

	$class = 'search';
	include 'search.inc.php';
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>