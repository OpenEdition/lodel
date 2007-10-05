<?php
/**
 * Fichier de configuration - Version distribuée
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */


# Version exacte de Lodel
$revision = "0.8";


# Racine de lodel sur le systeme
$pathroot="/path/to";


# Base du site
# ATTENTION : $urlroot doit toujours se terminer par /, il ne peut etre vide
$urlroot="/";


# Emplacement des scripts
# par exemple $home="/var/www/lodel/scripts";
# cette variable est ecrasee dans siteconfig.php a partir de la version 0.5
# elle pourra alors etre supprimee de ce script
# cette variable doit se terminer par / obligatoirement.
$home="$pathroot/lodel/scripts/";


# URL contenant les fichiers communs partagés
# par exemple $shareurl="http://lodel.revues.org/share";
# la version sera ajoutee sur le dernier repertoire, donc la chaine ne doit pas se terminer par /
$shareurl="http://lodel.revues.org/share";

# Repertoire contenant les fichiers communs partagés
# par exemple $sharedir="/var/www/lodel/share";
# ->a supprimer de ce fichier quand tous les lodel seront passe en versionning
$sharedir="$pathroot/share";

# Specifie si le nom du site se trouve a gauche dans l'url ou a droite
# nomsites.domain.org (=1) ou domaine.org/nomsite (=0)
$siteagauche=0;

# Repertoire pour les fichiers archive pour l'import de donnees.
$importdir="/var/import";

# Timeout pour les sessions
# en seconde
$timeout=120*60;

# Nom de la base de donnees
$database="lodel";

# Nom d'utilisateur
$dbusername="lodeluser";
# Mot de passe
$dbpasswd="lodelpasswd";
# Hote de la BD
$dbhost="dbhost.domain.org";

# contact bug. Adresse mail de la personne contactee automatiquement en cas de bug
$contactbug="";


# Prefix pour les tables. Utile quand on utilise qu'une seule database pour plusieurs applications.
$tableprefix="";

# LODEL n'utilise qu'une seule DB. Sinon, il utilise une DB principale plus une DB par site. "on" ou "" (ou "off")
$singledatabase="";

# Nom de la session (cookie)
$sessionname="session$database";


# configuration du serveur OO
$servoourl="http://servoo.revues.org";
$servoousername="user";
$servoopasswd="pass";


# type d'URL
# extension .php ou .html pour les scripts accessibles par les internautes.
# "php" ou "html"
$extensionscripts="php";
# position de l'id dans l'URL, a gauche signifie du genre documentXXX.php.
# "on" ou ""
$idagauche="";

# chemin pour la commande zip
#$zipcmd="/usr/bin/zip";
$zipcmd="pclzip";

# chemin pour la commande unzip
#$unzipcmd="/usr/bin/unzip";
$unzipcmd="pclzip";

############################################
# config reserve au systeme de config automatique
# la presence de ces variables est obligatoire pour la configuration
$includepath=""; # pour les sites qui ont un include automatique (defini par php.ini)
$htaccess="on";    #
$filemask="0770";
$usesymlink="on";
$installoption="";
$locale = "fr_FR.UTF8";
############################################

# lock les tables.
# Chez certains hebergeurs n'acceptent pas les LOCK

define("DONTUSELOCKTABLES",false);


############################################

setlocale (LC_ALL,$locale);

set_magic_quotes_runtime(0);
ignore_user_abort();


// securite
$currentdb="";
$site="";

define("NORECORDURL",1);
define(INC_LODELCONFIG,1);

# repertoire temporaire
define(TMPDIR, '/tmp');

# types des urls (path-leftid)
define(URI, 'path');


?>
