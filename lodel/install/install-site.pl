#!/usr/bin/perl


$version=shift @ARGV;

unless ($version && ($version=="devel" || $version=~/^\d+\.\d+/)) {
  print STDERR "Veuillez preciser un numero de version ou devel\n";
  exit;
}
$versionsuffix=$version=="devel" ? "" : "-".$version;

$homesite="../lodel$versionsuffix/revue";
$homesitetpl="../../lodel$versionsuffix/revue";

unless (-e $homesite) {
  print STDERR "La version '$version' n'existe pas sur le disque\n";
  exit;
}

unless (-e "siteconfig.php") {
  print STDERR "Installation du fichier siteconfig.php. Verifier le contenu.\n";
  system ("cp $homesite/siteconfig.php .");
  if (!$version || $version>=0.4 ) {
    unless (-e "siteconfig.php") {
      print STDERR "Impossible de copier le fichier siteconfig.php\n";
      exit;
    }
  }
}

slink ("../lodelconfig.php","lodelconfig.php");

if (-e "siteconfig.php") {
  $php=`php -v`;
  if ($php) {
    $checkversion=`php -q -C ../lodel$versionsuffix/install/version.php`;
    if ($checkversion=~/error/) {
      print STDERR "Erreur lors du parsage du fichier siteconfig.php:\n\n$checkversion";
      exit;
    }
    if ($version!=$checkversion) {
      print "La version dans siteconfig.php $checkversion est differente de $version\n";
      exit;
    }
  } else {
    print STDERR "Attention: La commande php ne semble pas etre disponible ou fonction. Impossible de verifier si la version est correcte dans siteconfig.php\n";
  }
}


#############################################################################
# charge le fichier d'install


open (FILE,"$homesite/../install/install-fichier.dat") or die ("impossible d'ouvrir install-fichier.dat");

my $dirsource=".";
my $dirdest=".";


foreach (<FILE>) {
  s/\#.*$//; # enleve les commentaires
  chop;
  next unless $_;

  my ($cmd,$arg1,$arg2)=split;
  $arg1=~s/\$homesite/$homesite/g;
  $arg2=~s/\$homesite/$homesite/g;
  $arg1=~s/\$homelodel/../g;
  $arg2=~s/\$homelodel/../g;

  $filedest="$dirdest/$arg1";
  # quelle commande ?
  if ($cmd eq "dirsource") {
    $dirsource=$arg1;
  } elsif ($cmd eq "dirdestination") {
    $dirdest=$arg1;
  } elsif ($cmd eq "mkdir") {
    mkdir $arg1,oct($arg2);
  } elsif ($cmd eq "ln") {
    $toroot=$filedest; $toroot=~s/^\.\///g; 
    $toroot=~s/([^\/]+)\//..\//g;
    $toroot=~s/[^\/]+$//;
#    print STDERR "3 $dirdest $dirsource $toroot $arg1\n";
    $filedest=~s/\.php$/.html/ if $dirdest eq ".";
    slink("$toroot$dirsource/$arg1",$filedest) unless -e $filedest;
  } elsif ($cmd eq "cp") {
    $filedest=~s/\.php$/.html/ if $dirdest eq ".";
    system ("cp -fr $dirsource/$arg1 $filedest") unless filemtime($filedest)>filemtime(" $dirsource/$arg1");
  } elsif ($cmd eq "touch") {
    system ("touch $filedest") unless -e  $filedest;
  } elsif ($cmd eq "htaccess") {
    htaccess($filedest) if -e $filedest;
  } else {
    die ("command inconnue: \"$cmd\"");
  }
}

close FILE;

#############################################################################



sub filemtime {

#  ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,
#   $atime,$mtime,$ctime,$blksize,$blocks)
   @res = stat $_[0];

  return $res[9];
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
