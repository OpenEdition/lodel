#!/usr/bin/perl


$version=shift @ARGV;

unless ($version && ($version=="devel" || $version=~/^\d+\.\d+/)) {
  print STDERR "Veuillez preciser un numero de version ou devel\n";
  exit;
}
$versionsuffix=$version=="devel" ? "" : "-".$version;

$homerevue="../lodel$versionsuffix/revue";
$homerevuetpl="../../lodel$versionsuffix/revue";

unless (-e $homerevue) {
  print STDERR "La version '$version' n'existe pas sur le disque\n";
  exit;
}

unless (-e "revueconfig.php") {
  print STDERR "Installation du fichier revueconfig.php. Verifier le contenu.\n";
  system ("cp $homerevue/revueconfig.php .");
  if (!$version || $version>=0.4 ) {
    unless (-e "revueconfig.php") {
      print STDERR "Impossible de copier le fichier revueconfig.php\n";
      exit;
    }
  }
}

slink ("../lodelconfig.php","lodelconfig.php");

if (-e "revueconfig.php") {
  $php=`php -v`;
  if ($php) {
    $checkversion=`php -q -C ../lodel$versionsuffix/install/version.php`;
    if ($checkversion=~/error/) {
      print STDERR "Erreur lors du parsage du fichier revueconfig.php:\n\n$checkversion";
      exit;
    }
    if ($version!=$checkversion) {
      print "La version dans revueconfig.php $checkversion est differente de $version\n";
      exit;
    }
  } else {
    print STDERR "Attention: impossible de verifier si la version est correcte dans revueconfig.php\n";
  }
}





# si le groupe de l'utilisateur est www, alors faire: umask 0007
umask 0007;

mkdir "CACHE", 0770;
mkdir "tpl", 0755;
mkdir "images", 0755;
mkdir "docannexe", 0770;

####### .htaccess dans tpl et CACHE

htaccess ("tpl");
htaccess ("CACHE");

######### repertoire revue

print "Revue\n";

slink ("../styles_lodel.css","styles_lodel.css");

`/bin/cp $homerevue/styles_revue.css styles_revue.css` if -e "$homerevue/styles_revue.css";

`/bin/cp -r $homerevue/images .` if -e "$homerevue/images";
####copier images et style_revues.....

@revuefile=(
	    "auteur",
	    "auteurs-complet",
	    "auteurs",
	    "chrono",
	    "chronos-complet",
	    "chronos",
	    "document",
            "geo",
            "geos-complet",
            "geos",
	    "index",
	    "macros",
	    "mot",
	    "mots-complet",
	    "mots",
	    "signaler",
	    "sommaire");

foreach (@revuefile) {
  slink ("$homerevue/$_.php","$_.html");
}

slink ("../lodel/admin/login.php","login.php");
slink ("../lodel/admin/logout.php","logout.php");
slink ("../$homerevue/lodel/admin/tpl/login.html","tpl/login.html");
slink ("../$homerevue/lodel/edition/tpl/desk.html","tpl/desk.html");


mkdir "lodel", 0755;
mkdir "lodel/txt", 0770;
mkdir "lodel/rtf", 0770;

htaccess ("lodel/txt");
htaccess ("lodel/rtf");

########### edition
print "Edition\n";

mkdir "lodel/edition", 0755;
mkdir "lodel/edition/tpl", 0755;
mkdir "lodel/edition/CACHE", 0770;
chdir "lodel/edition";
htaccess ("CACHE");
htaccess ("tpl");

slink ("../../lodelconfig.php","lodelconfig.php");
slink ("../../revueconfig.php","revueconfig.php");
slink ("../../styles_revue.css","styles_revue.css");
slink ("../../styles_lodel.css","styles_lodel.css");
slink ("../../$homerevue/lodel/edition/maj.php","maj.php");
slink ("../../$homerevue/lodel/edition/images","images");
slink ("../../../lodel/admin/login.php","login.php");
slink ("../../../lodel/admin/logout.php","logout.php");
slink ("../../../$homerevue/lodel/admin/tpl/login.html","tpl/login.html");
slink ("../../../$homerevue/lodel/edition/tpl/macros.html","tpl/macros.html");

@editionfile=("a_editer",
	      "abandon",
	      "balisage",
	      "balises",
	      "chargement",
	      "chargesommaire",
	      "chkbalisage",
	      "docannexe",
              "deskedition",
	      "metaimage",
	      "editer",
	      "deplacer",
	      "publi",
	      "status",
	      "extrainfo",
	      "importsommaire",
	      "index",
	      "edition",
	      "edition-lineaire",
	      "edition-hierarchique",
	      "edition-numero",
	      "edition-regroupement",
	      "edition-theme",
	      "publication",
	      "publications_protegees",
	      "supprime",
	      "macros"
);

foreach (@editionfile) {
  slink ("../../$homerevue/lodel/edition/$_.php","$_.php") if -e "../../$homerevue/lodel/edition/$_.php";
  slink ("../../../$homerevue/lodel/edition/tpl/$_.html","tpl/$_.html") if -e "../../$homerevue/lodel/edition/tpl/$_.html";
}


chdir "../..";

############# admin

print "Admin\n";

mkdir "lodel/admin", 0755;
mkdir "lodel/admin/tpl", 0755;
mkdir "lodel/admin/CACHE", 0770;
mkdir "lodel/admin/upload", 0770;

chdir "lodel/admin";
htaccess ("CACHE");
htaccess ("tpl");

@adminfile=(
	    "index",
	    "deskadmin",
	    "motcle",
	    "motcles",
	    "indexl",
	    "options",
	    "periode",
	    "periodes",
	    "geographie",
	    "geographies",
	    "indexh",
	    "r2rcheck",
	    "r2renregistre",
	    "rmdb",
	    "texte",
	    "textes",
	    "typedoc",
	    "typedocs",
	    "typepubli",
	    "typepublis",
	    "backup",
	    "import",
	    "users",
	    "user",
	    "session",
	    "macros"
);

slink ("../../lodelconfig.php","lodelconfig.php");
slink ("../../revueconfig.php","revueconfig.php");
slink ("../../styles_revue.css","styles_revue.css");
slink ("../../styles_lodel.css","styles_lodel.css");
slink ("../../../lodel/admin/login.php","login.php");
slink ("../../../lodel/admin/logout.php","logout.php");
slink ("../../$homerevue/lodel/admin/images","images");
slink ("../../../$homerevue/lodel/admin/tpl/login.html","tpl/login.html");
slink ("../../../$homerevue/lodel/admin/tpl/macros.html","tpl/macros.html");

foreach (@adminfile) {
  slink ("../../$homerevue/lodel/admin/$_.php","$_.php") if -e "../../$homerevue/lodel/admin/$_.php";
  slink ("../../../$homerevue/lodel/admin/tpl/$_.html","tpl/$_.html") if -e "../../$homerevue/lodel/admin/tpl/$_.html";
}
chdir "../..";

### index
@touchfile=("docannexe/index.html","lodel/index.html","lodel/admin/upload/index.html");
foreach (@touchfile) {
  open (FILE,">$_") or die "impossible de creer $_\n";
  close (FILE);
}


sub slink {
  return if -e $_[1];
  symlink "$_[0]","$_[1]" or die "impossible de creer le lien symbolique de $_[0] vers $_[1]\n";
  die "impossible d'acceder au fichier $_[0] via le lien symbolique $_[1]" unless (-e $_[1]);

}

sub htaccess {
  $dir=shift;
  unlink "$dir/.htaccess";
  open(HT,">$dir/.htaccess") or die "Impossible d'ecrire dans $dir";
  print HT "deny from all\n";
  close (HT);
  chmod (0644, "$dir/.htaccess") or die "Can't chmod $dir/.htaccess";
}
