<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 *  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 *  Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

/* comment

Installation par defaut sans pre-configuration.

comment */

# version de Lodel
$cfg['version']="0.9";
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
# temps de cache des résultats SQL
$cfg['sqlCacheTime'] = 3600*24;
$GLOBALS['sqlCacheTime']=$cfg['sqlCacheTime'];
# driver mysql
$cfg['dbDriver'] = 'mysql';

# contact bug. Adresse mail de la personne contactee automatiquement en cas de bug
$cfg['contactbug']="";

# Repertoire contenant le binaire de mysql
$cfg['mysqldir']="/usr/bin";

# chemin pour la commande zip ou pclzip pour utiliser la librairie pclzip
$cfg['zipcmd']="pclzip";

# chemin pour la commande unzip ou pclzip pour utiliser la librairie pclzip
$cfg['unzipcmd']="pclzip";


# Prefix pour les tables. Utile quand on utilise qu'une seule database pour plusieurs applications.
$cfg['tableprefix']="lodel_";

# LODEL n'utilise qu'une seule DB. Sinon, il utilise une DB principale plus une DB par site. "on" ou "" (ou "off")
$cfg['singledatabase']="on";


# Nom de la session (cookie)
$cfg['sessionname']="session{$cfg['database']}";


# type d'URL
$cfg['extensionscripts']="";      # extension .php ou .html pour les scripts accessibles par les internautes 
define("URI","id");        # position de l'id dans l'URL, a gauche signifie du genre documentXXX.php


# configuration du ServOO
$cfg['servoourl']="";
$cfg['servoousername']="";
$cfg['servoopasswd']="";
# repertoire temporaire d'extraction ServOO
$cfg['tmpoutdir']="";
# taille maximum du fichier permis à l'upload pour OTX
$cfg['maxUploadFileSize'] = 10240000;
# configuration du proxy pour atteindre le ServOO
$cfg['proxyhost']="";
$cfg['proxyport']="8080";

 #tableau des types de fichiers acceptés à l'upload
$cfg['authorizedFiles'] = array( '.png', '.gif', '.jpg', '.jpeg', '.tif', '.doc', '.odt', '.ods', '.odp', '.pdf', '.ppt', '.sxw', '.xls', '.rtf', '.zip', '.gz', '.ps', '.ai', '.eps', '.swf', '.rar', '.mpg', '.mpeg', '.avi', '.asf', '.flv', '.wmv', '.docx', '.xlsx', '.pptx', '.mp3', '.mp4', '.ogg', '.xml');

# lock les tables.
# Chez certains hebergeurs n'acceptent pas les LOCK

define("DONTUSELOCKTABLES",false);

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
# @see http://pear.php.net/manual/en/package.caching.cache-lite.cache-lite.cache-lite.php
$cfg['cacheOptions'] = array(
	'cacheDir' => sys_get_temp_dir() . PATH_SEPARATOR . 'lodel',
	'lifeTime' => 3600,
// pour débug : décommenter ici
// 	'pearErrorMode' => 8,
	'pearErrorMode' => 1,
	'fileNameProtection'=>true,
	'readControl'=>true,
	'readControlType'=>'crc32',
	'writeControl'=>true,
	'hashedDirectoryLevel'=>2,
	'fileLocking'=>true
	);
##################
$GLOBALS['cacheOptions'] = $cfg['cacheOptions'];
$cfg['debugMode']=false; // mettre à true pour afficher les erreurs générées pendant le calcul d'une page
$cfg['locale']="fr_FR.UTF8";
setlocale (LC_ALL,$cfg['locale']);

set_magic_quotes_runtime(0);
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
