<?

# Racine de lodel sur le systeme
$pathroot="";


# Base du site
# ATTENTION : $urlroot doit toujours se terminer par /, il ne peut etre vide
$urlroot="/";


# Emplacement des scripts
# par exemple $home="/var/www/lodel/scripts";
# cette variable est ecrasee dans revueconfig.php a partir de la version 0.5
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

# Specifie si le nom de la revue se trouve a gauche dans l'url ou a droite
# nomrevues.domain.org ou domaine.org/nomrevue
$revueagauche=0;

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
$dbhost="localhost";

# Repertoire contenant le binaire de mysql
$mysqldir="/usr/bin";

# type lineaire (table indexls)
define (TYPE_MOTCLE,2);
define (TYPE_MOTCLE_PERMANENT,3);

# type hierarchique (table indexhs)
define (TYPE_PERIODE,1);
define (TYPE_GEOGRAPHIE,4);

# Utilise pour lodellight essentiellement
$tableprefix="";

# Nom de la session (cookie)
$sessionname="session$database";

############################################
# config reserve au systeme de config automatique
# la presence de ces variables est obligatoire pour la configuration
$includepath="";
$multidatabases="";

############################################

setlocale ("LC_ALL","FR");
set_magic_quotes_runtime(0);
ignore_user_abort();

// securite
$currentdb="";

define (NORECORDURL,1);

?>
