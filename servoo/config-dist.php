<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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


# Nom de la base de donnees
$database="lodeldevelserveuroo";

# Nom d'utilisateur
$dbusername="lodeluser";
# Mot de passe
$dbpasswd="XXXXXXXXXX";
# Hote de la BD
$dbhost="localhost";

# repertoire d'installation de java
$javapath="/usr/java/j2sdk1.4.1_02";

# directory to the OpenOffice classes.
$openofficeclassespath="/usr/local/OpenOffice.org1.1.0/program/classes";

# version d'OpenOffice
define("OPENOFFICEVERSION","1.1");

# message renvoie
define ("MESSAGEVERSION","Visit http://www.lodel.org/servoo");


# host surlequel est le serveur OpenOffice
$servoohost="localhost";

# port du serveur OpenOffice sur le host
$servooport="9303";

# chemin pour la commande zip
$zipcmd="/usr/bin/zip";

# chemin pour la commande unzip
$unzipcmd="/usr/bin/unzip";

# Prefix pour les tables, utile quand on utilise qu'une seule database pour plusieurs applications
$tp="";

# chemin relatif pour le repertoire d'include

$home="include/";

# URL contenant les fichiers communs partagés
# par exemple $shareurl="http://lodel.revues.org/share";
# la version sera ajoutee sur le dernier repertoire, donc la chaine ne doit pas se terminer par /
$shareurl="/share";


# Nom de la session (cookie)
$sessionname="sessionservoo";

# Timeout pour les sessions
# en seconde
$logintimeout=120*60;

if (!defined("TOINCLUDE")) define("TOINCLUDE","../include/");
?>
