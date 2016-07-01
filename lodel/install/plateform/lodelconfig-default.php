<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

# version de Lodel
$cfg['version']="1.0";
# revision SVN de la release
$cfg['revision']="443X";


# Racine de lodel sur le systeme
$cfg['pathroot']=".";


# Base du site
# ATTENTION : $urlroot doit toujours se terminer par /, il ne peut etre vide
$cfg['urlroot']="/";


# Emplacement des scripts
# par exemple $home="/var/www/lodel/scripts";
# cette variable est ecrasee dans siteconfig.php a partir de la version 0.5
# elle pourra alors etre supprimee de ce script
# cette variable doit se terminer par / obligatoirement.
$cfg['home']="";


# URL contenant les fichiers communs partagés
# par exemple $shareurl="http://lodel.revues.org/share";
# la version sera ajoutee sur le dernier repertoire, donc la chaine ne doit pas se terminer par /
$cfg['shareurl']=$cfg['urlroot']."share";

# Repertoire contenant les fichiers communs partagés
# par exemple $sharedir="/var/www/lodel/share";
# ->a supprimer de ce fichier quand tous les lodel seront passe en versionning
$cfg['sharedir']="{$cfg['pathroot']}/share";


# Localisation des fichiers archive pour l'import de donnees
$cfg['importdir']="";

# Timeout pour les sessions
# en seconde
$cfg['timeout']=120*60;

# Timeout pour les cookies
# en seconde
$cfg['cookietimeout']=4*3600;


# Nom de la base de donnees
$cfg['database']="";

# Nom d'utilisateur
$cfg['dbusername']="";
# Mot de passe
$cfg['dbpasswd']="";
# Hote de la BD
$cfg['dbhost']="";
# driver mysql
$cfg['dbDriver'] = 'mysqli';

# contact bug. Adresse mail de la personne contactee automatiquement en cas de bug
$cfg['contactbug']="";

# Repertoire contenant le binaire de mysql
$cfg['mysqldir']="/usr/bin";

# Prefix pour les tables. Utile quand on utilise qu'une seule database pour plusieurs applications.
$cfg['tableprefix']="lodel_";

# LODEL n'utilise qu'une seule DB. Sinon, il utilise une DB principale plus une DB par site. "on" ou "" (ou "off")
$cfg['singledatabase']="off";


# Nom de la session (cookie)
$cfg['sessionname']="session{$cfg['database']}";

# Détection automatique de la langue de navigation
$cfg['detectlanguage'] = true;
# Choix par défaut de la langue de navigation
// $cfg['mainlanguageoption'] = 'options.metadonneessite.langueprincipale';


# type d'URL
$cfg['extensionscripts']="";      # extension .php ou .html pour les scripts accessibles par les internautes
define("URI","id");        # position de l'id dans l'URL, a gauche signifie du genre documentXXX.php


# configuration d'OTX
$cfg['otxurl']="";
$cfg['otxusername']="";
$cfg['otxpasswd']="";
# repertoire temporaire d'extraction d'OTX
$cfg['tmpoutdir']="";
# taille maximum du fichier permis à l'upload pour OTX
$cfg['maxUploadFileSize'] = 10240000;

# configuration du proxy pour atteindre OTX
$cfg['proxyhost']="";
$cfg['proxyport']="8080";

 #tableau des types de fichiers acceptés à l'upload
$cfg['authorizedFiles'] = array( '.png', '.gif', '.jpg', '.jpeg', '.tif', '.doc', '.odt', '.ods', '.odp', '.pdf', '.ppt', '.sxw', '.xls', '.rtf', '.zip', '.gz', '.ps', '.ai', '.eps', '.swf', '.rar', '.mpg', '.mpeg', '.avi', '.asf', '.flv', '.wmv', '.docx', '.xlsx', '.pptx', '.mp3', '.mp4', '.ogg', '.xml');

# lock les tables.
# Chez certains hebergeurs n'acceptent pas les LOCK

define("DONTUSELOCKTABLES",false);

# liste des sites dans lesquels on n'écrit pas
# utilisé pour les index externes
# exemple d'utilisation : $cfg['db_no_intrusion'] = array('mon_site' => true);
$cfg['db_no_intrusion'] = array();

############################################
# config reserve au systeme de config automatique
# la presence de ces variables est obligatoire pour la configuration
$cfg['chooseoptions']="";
$cfg['includepath']=""; # pour les sites qui ont un include automatique (defini par php.ini)
$cfg['htaccess']="on";    #
$cfg['filemask']="0777";
$cfg['usesymlink']="";
$cfg['installoption']="";
$cfg['installlang']="fr";
############################################

# config du cache #
$cfg['cacheDir'] = sys_get_temp_dir();
$cfg['cacheOptions'] = array(
        'driver'    => 'file',
        'prefix'    => 'lodel',
        'cache_dir'          => sys_get_temp_dir(),
        'default_expire'     => 3600,
);
##################
$GLOBALS['cacheOptions'] = $cfg['cacheOptions'];
// debugMode : 0 = off, 1 = affichage simple des erreurs, 2 = affichage du debug backtrace, 3 = affichage du debug backtrace + arrêt du script
$cfg['debugMode']=0; 
// showPubErrMsg : affichage d'un message générique en cas d'erreur pour utilisateur non identifié 
$cfg['showPubErrMsg'] = false;
// dieOnErr : arrêt du script en cas d'erreur, indépendamment du debugMode
$cfg['dieOnErr'] = false;
$cfg['locale']="fr_FR.UTF8";
setlocale (LC_ALL,$cfg['locale']);
date_default_timezone_set('Europe/Paris');
ignore_user_abort();


// securite
$currentdb="";
$cfg['currentdb']="";
$GLOBALS['currentdb'] = $cfg['currentdb'];

if (!$cfg['filemask']) {
	$cfg['filemask'] = 0700;
}

define ("NORECORDURL",1);
define ("INC_LODELCONFIG",1);
?>
