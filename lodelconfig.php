<?

// emplacement des scripts
$home="/www/revues/lodeldevel/lodel/scripts";

// base du site
$urlroot="/"; # urlroot doit toujours se terminer par /, il ne peut etre vide
#$shareurl="http://lodeldevel.revues.org/lodel/share";
$shareurl="http://lodeldevel/lodel/share";

// $revueagauche=0;

// timeout pour les sessions
$timeout=120*60;

// nom de la base de donnee
$database="lodeldevel";
// nom d'utilisateur
$dbusername="lodeluser";
// mot de passe
$dbpasswd="45mlkj,n";
// host
#$dbhost="r3.revues.org";
$dbhost="adelie";

// repertoire de mysql
$mysqldir="/usr/local/mysql/bin";

# type lineaire (table indexls)
define (TYPE_MOTCLE,2);
define (TYPE_MOTCLE_PERMANENT,3);

# type hierarchique (table indexhs)
define (TYPE_PERIODE,1);
define (TYPE_GEOGRAPHIE,4);

# utilise pour lodellight essentiellement
$tableprefix="";

# nom de la session (cookie)
$sessionname="session$database";


############################################

setlocale ("LC_ALL","FR");
set_magic_quotes_runtime(0);
ignore_user_abort();

// securite
$currentdb="";

define (NORECORDURL,1);

?>
