<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier backend RSS - Appel la vue associée au template backend.html
 *
 * La même opération est possible en utilisant : index.php?page=backend
 */

require 'siteconfig.php';

try
{
    include 'auth.php';
    authenticate();
    
    View::getView()->renderCached('backend');
    exit;
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>