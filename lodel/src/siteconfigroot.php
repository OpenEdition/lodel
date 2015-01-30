<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de configuration
 */


require 'lodelconfig.php';

$cfg['site'] = "";

##########################################

if (!defined("SITEROOT")) {
    define("SITEROOT","");
}

$cfg['home'] = "lodel/scripts/";
$home = $cfg['home'];
$cfg['sharedir'] = SITEROOT . $cfg['sharedir'];

# recaptcha pour la partie signaler
# par défaut désactivé
$cfg['signaler_recaptcha'] = false;
$cfg['recaptcha_privatekey'] = ""; // clé privée recaptcha
$cfg['recaptcha_publickey'] = ""; // clé publique recaptcha

$cfg['searchEngine'] = false;

ini_set('include_path', SITEROOT. $cfg['home']. PATH_SEPARATOR. ini_get('include_path'));
// important here
// when this file is included, we are ALWAYS in the site root, so don't concat $home !!
require $cfg['home'].'context.php';

$cfg['home'] = SITEROOT. $cfg['home'];
C::setCfg($cfg);
require $home.'class.errors.php';
$home=null;
?>
