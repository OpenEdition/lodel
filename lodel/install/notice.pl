#!/usr/bin/perl




foreach $file(@ARGV) {
  $file=~/\.(\w+)$/;
  $type=$1;
  next unless $type eq "php" || $type eq "html" || $type eq "sql"  || $type eq "pl";
  print STDERR $file,"\n";
  open(FILE, $file) or die ("impossible d'ouvrir $file");
  @lines=<FILE>;
  close FILE;

  open(FILE, ">$file") or die ("impossible d'ouvrir en ecriture $file");

  if ($type eq "php" || $type eq "pl" || $type eq "html") {
    # ecrit la premiere ligne car elle contient des infos importantes
    print FILE (shift @lines);

  }

  print FILE notice($type)."\n\n";
  print FILE join("",@lines);

  close FILE;
}


sub notice {
  my ($type)=@_;
  my $com;

  if ($type eq "php") {
    $com=" *  ";
  } elsif ($type eq "sql") {
    $com="#  ";
  } elsif ($type eq "pl") {
    $com="#  ";
  } else {
    $com="   ";
  }

  $notice='
LODEL - Logiciel d\'Edition ELectronique.

Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot

Home page: http://www.lodel.org

E-Mail: lodel@lodel.org

                          All Rights Reserved

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
';


  $notice=~s/^.*/$com$&/mg;
  $notice=~s/\s+$//mg;

  if ($type eq "html") {
    $notice="<!--\n".$notice."-->\n";
  } elsif ($type eq "php") {
    $notice="/*\n".$notice."*/";
  }

  return $notice;
}
