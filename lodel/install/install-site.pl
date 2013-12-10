#!/usr/bin/perl
#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
#  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
#  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
#  Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
#  Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
#  Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
#
#  Home page: http://www.lodel.org
#
#  E-Mail: lodel@lodel.org
#
#                            All Rights Reserved
#
#     This program is free software; you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation; either version 2 of the License, or
#     (at your option) any later version.
#
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with this program; if not, write to the Free Software
#     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

$extensionscripts="php";

$version=shift @ARGV;
$droitlecture=shift @ARGV;
$droitecriture=shift @ARGV;

die('install-site.pl version droit_de_lecture droit_d_ecriture
Les droits sont donn�s sous la forme d\'une combinaison de u (user) g (group) et a (all)
ex: install-site.pl 0.7 uga ug
') unless $droitlecture && $droitecriture;

$filemask=0;

$filemask|=0500 if $droitlecture=~/u/;
$filemask|=0050 if $droitlecture=~/g/;
$filemask|=0005 if $droitlecture=~/a/;

$filemask|=0200 if $droitecriture=~/u/;
$filemask|=0020 if $droitecriture=~/g/;
$filemask|=0002 if $droitecriture=~/a/;

#printf "%o",$filemask;

unless ($version && ($version=="devel" || $version=~/^\d+\.\d+/)) {
  print STDERR "Veuillez preciser un numero de version ou devel\n";
  exit;
}

$homesite="../lodel/src";
$homesitetpl="../../lodel/src";

unless (-e $homesite) {
  print STDERR "La version '$version' n'existe pas sur le disque\n";
  exit;
}

#unless (-e "siteconfig.php") {
#  print STDERR "Installation du fichier siteconfig.php. Verifier le contenu.\n";
#  system ("cp $homesite/siteconfig.php .");
#  if (!$version || $version>=0.4 ) {
#    unless (-e "siteconfig.php") {
#      print STDERR "Impossible de copier le fichier siteconfig.php\n";
#      exit;
#    }
#  }
#}

slink ("../lodelconfig.php","lodelconfig.php");

if (-e "siteconfig.php") {
  $php=`php -v`;
  if ($php) {
    $checkversion=`php -q -C ../lodel/install/version.php`;
    if ($checkversion=~/error/) {
      print STDERR "Erreur lors du parsage du fichier siteconfig.php:\n\n$checkversion";
      exit;
    }
    if ($version!=$checkversion) {
      print STDERR "La version dans siteconfig.php $checkversion est differente de $version\n";
      exit;
    }
  } else {
    #print STDERR "Attention: La commande php ne semble pas etre disponible ou fonction. Impossible de verifier si la version est correcte dans siteconfig.php\n";
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
    unless (-e $arg1) {
      mkdir ($arg1,oct($arg2) & $filemask);
    }
    #print $arg1," ";
    #printf "%o",oct($arg2) & $filemask;
    chmod (oct($arg2) & $filemask,$arg1);
  } elsif ($cmd eq "ln") {
    $toroot=$filedest; $toroot=~s/^\.\///g;
    $toroot=~s/([^\/]+)\//..\//g;
    $toroot=~s/[^\/]+$//;
#    print STDERR "3 $dirdest $dirsource $toroot $arg1\n";
    $filedest=~s/\.php$/.html/ if $dirdest eq "." && $extensionscripts eq "html";
    slink("$toroot$dirsource/$arg1",$filedest) unless -e $filedest;
  } elsif ($cmd eq "cp") {
    $filedest=~s/\.php$/.html/ if $dirdest eq "." && $extensionscripts eq "html";
    system ("cp -fr $dirsource/$arg1 $filedest") unless filemtime($filedest)>filemtime(" $dirsource/$arg1");
#    print $filedest," ",$filemask," ","\n";
#    printf "%o\n",0644 & oct($filemask);
    $mode=(-f filedest) ? 0644 : 0755;
    chmod ($mode & $filemask,$filedest);
  } elsif ($cmd eq "touch") {
    system ("touch $filedest") unless -e  $filedest;
    chmod (0644 & $filemask,$filedest);
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
  symlink "$_[0]","$_[1]" or print "Warning: impossible de creer le lien symbolique de $_[0] vers $_[1]\n";
  print "Warning: impossible d'acceder au fichier $_[0] via le lien symbolique $_[1]\n" unless (-e $_[1]);

}

sub htaccess {
  $dir=shift;
  unlink "$dir/.htaccess";
  open(HT,">$dir/.htaccess") or die "Impossible d'ecrire dans $dir";
  print HT "deny from all\n";
  close (HT);
  chmod (0644 & $filemask, "$dir/.htaccess") or die "Can't chmod $dir/.htaccess";
}
