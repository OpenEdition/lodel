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


#############################################################################
# charge le fichier d'install


open (FILE,"$homerevue/../install/install-fichier.dat") or die ("impossible d'ouvrir install-fichier.dat");

my $dirsource=".";
my $dirdest=".";


foreach (<FILE>) {
  s/\#.*$//; # enleve les commentaires
  chop;
  next unless $_;

  my ($cmd,$arg1,$arg2)=split;
  $arg1=~s/\$homerevue/$homerevue/g;
  $arg2=~s/\$homerevue/$homerevue/g;
  $arg1=~s/\$homelodel/../g;
  $arg2=~s/\$homelodel/../g;

  # quelle commande ?
  if ($cmd eq "dirsource") {
    $dirsource=$arg1;
  } elsif ($cmd eq "dirdestination") {
    $dirdest=$arg1;
  } elsif ($cmd eq "mkdir") {
    mkdir $arg1,oct($arg2);
  } elsif ($cmd eq "ln") {
    $toroot="$dirdest/$arg1"; $toroot=~s/^\.\///g; 
    $toroot=~s/([^\/]+)\//..\//g;
    $toroot=~s/[^\/]+$//;
#    print STDERR "3 $dirdest $dirsource $toroot $arg1\n";
    slink("$toroot$dirsource/$arg1","$dirdest/$arg1") unless -e "$dirdest/$arg1";
  } elsif ($cmd eq "cp") {
    system ("cp -fr $dirsource/$arg1 $dirdest/$arg1") unless filemtime("$dirdest/$arg1")>filemtime(" $dirsource/$arg1");
  } elsif ($cmd eq "touch") {
    system ("touch $dirdest/$arg1");
  } elsif ($cmd eq "htaccess") {
    htaccess("$dirdest/$arg1") unless -e "$dirdest/$arg1";
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
