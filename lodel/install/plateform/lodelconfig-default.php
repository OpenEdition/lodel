<?php
/* comment

Installation par defaut sans pre-configuration.

comment */


# Racine de lodel sur le systeme
$pathroot=".";


# Base du site
# ATTENTION : $urlroot doit toujours se terminer par /, il ne peut etre vide
$urlroot="/";


# Emplacement des scripts
# par exemple $home="/var/www/lodel/scripts";
# cette variable est ecrasee dans siteconfig.php a partir de la version 0.5
# elle pourra alors etre supprimee de ce script
# cette variable doit se terminer par / obligatoirement.
$home="$pathroot/lodel/scripts";


# URL contenant les fichiers communs partagés
# par exemple $shareurl="http://lodel.revues.org/share";
# la version sera ajoutee sur le dernier repertoire, donc la chaine ne doit pas se terminer par /
$shareurl="";

# Repertoire contenant les fichiers communs partagés
# par exemple $sharedir="/var/www/lodel/share";
# ->a supprimer de ce fichier quand tous les lodel seront passe en versionning
$sharedir="$pathroot/share";

# Specifie si le nom du site se trouve a gauche dans l'url ou a droite
# nomsites.domain.org ou domaine.org/nomsite
$siteagauche=0;

# Localisation des fichiers archive pour l'import de donnees
$importdir="/www-bin/revues/import";

# Timeout pour les sessions
# en seconde
$timeout=120*60;

# Nom de la base de donnees
$database="lodel";

# Nom d'utilisateur
$dbusername="";
# Mot de passe
$dbpasswd="";
# Hote de la BD
$dbhost="";

# contact bug. Adresse mail de la personne contactee automatiquement en cas de bug
$contactbug="";

# Repertoire contenant le binaire de mysql
$mysqldir="/usr/bin";

# chemin pour la commande zip
$zipcmd="/usr/bin/zip";

# chemin pour la commande unzip
$unzipcmd="/usr/bin/unzip";


# type lineaire (table indexls)
define (TYPE_MOTCLE,2);
define (TYPE_MOTCLE_PERMANENT,3);

# type hierarchique (table indexhs)
define (TYPE_PERIODE,1);
define (TYPE_GEOGRAPHIE,4);

# Prefix pour les tables. Utile quand on utilise qu'une seule database pour plusieurs applications.
$tableprefix="";

# LODEL n'utilise qu'une seule DB. Sinon, il utilise une DB principale plus une DB par site. "on" ou "" (ou "off")
$singledatabase="";


# Nom de la session (cookie)
$sessionname="session$database";


# type d'URL
$extensionscripts="";      # extension .php ou .html pour les scripts accessibles par les internautes 
$idagauche="";             # position de l'id dans l'URL, a gauche signifie du genre documentXXX.php

# TEMPORAIRE
# configuration du serveur OO
$serveuroourl="http://lodeldevel/serveuroo/index.php";
$serveuroousername="ruralia@localhost";
$serveuroopasswd="ruralia";



############################################
# config reserve au systeme de config automatique
# la presence de ces variables est obligatoire pour la configuration
$includepath=""; # pour les sites qui ont un include automatique (defini par php.ini)
$htaccess="on";    # 
$usesymlink="";

############################################

setlocale ("LC_ALL","FR");
set_magic_quotes_runtime(0);
ignore_user_abort();

// securite
$currentdb="";

define (NORECORDURL,1);

?>
