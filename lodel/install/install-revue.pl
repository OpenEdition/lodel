#!/usr/bin/perl


$homerevue="../lodel/revue";
$homerevuetpl="../../lodel/revue";

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

slink ("../lodelconfig.php","lodelconfig.php");
#slink ("../styles_revue.css","styles_revue.css");
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
	      "chkbalisage",
	      "docannexe",
              "deskedition",
	      "metaimage",
	      "editer",
	      "deplacer",
	      "publi",
	      "extrainfo",
	      "index",
	      "edition",
	      "edition-lineaire",
	      "edition-hierarchique",
	      "edition-numero",
	      "edition-regroupement",
	      "edition-theme",
	      "publication",
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
