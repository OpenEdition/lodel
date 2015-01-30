<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Script du moteur de recherche
 */

require 'siteconfig.php';

try
{
    include 'auth.php';
    include_once 'connect.php';
    authenticate();
    include 'search.inc.php';
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
