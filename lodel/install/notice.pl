#!/usr/bin/perl
# LODEL - Logiciel d'Édition ÉLectronique.
# @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
# @authors    See COPYRIGHT file

foreach $file(@ARGV) {
  $file=~/\.(\w+)$/;
  $type=$1;
  next unless $type eq "php" || $type eq "html" || $type eq "sql"  || $type eq "pl" || $type eq "css" || $type eq "" || $type eq "mod" || $type eq "dtd" ;

  print STDERR $file,"\n";
  open(FILE, $file) or die ("impossible d'ouvrir $file");
  @lines=<FILE>;
  close FILE;

  open(FILE, ">$file") or die ("impossible d'ouvrir en ecriture $file");

  $firstline=$lines[0];
  if ($type eq "html" && $firstline=~/^<\?/) {
    $type="php"; 
  }

  if ($type eq "" && $firstline=~/^#!/) {
    $type="script"; 
  }
  if ($type eq "php" || $type eq "pl" || $type eq "html" || $type eq "script") {
    unless (($type eq "html" && $firstline=~/<USE MACROFILE/) || ( ($type eq "html" || $type eq "mod" || type eq "dtd") && $firstline=~/<!--/)) {
      # ecrit la premiere ligne car elle contient des infos importantes
      print FILE (shift @lines);
    }
  }

  print FILE notice($type)."\n\n";
  print FILE join("",@lines);

  close FILE;
}


sub notice {
  my ($type)=@_;
  my $com;

  if ($type eq "php" || $type eq "css") {
    $com=" *  ";
  } elsif ($type eq "sql") {
    $com="#  ";
  } elsif ($type eq "pl") {
    $com="#  ";
  } elsif ($type eq "script") {
    $com="#  ";
  } else {
    $com="   ";
  }

  $notice='
LODEL - Logiciel d\'Édition ÉLectronique.
@license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
@authors    See COPYRIGHT file
';


  $notice=~s/^.*/$com$&/mg;
  $notice=~s/\s+$//mg;

  if ($type eq "html" || $type eq "mod" || $type eq "dtd") {
    $notice="<!--\n".$notice."\n-->\n";
  } elsif ($type eq "php" || $type eq "css") {
    $notice="/*\n".$notice."\n*/";
  }

  return $notice;
}
