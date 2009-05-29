#!/usr/bin/perl
#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
#  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
#  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
#  Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
#  Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
#  Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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

  if ($type eq "html" || $type eq "mod" || $type eq "dtd") {
    $notice="<!--\n".$notice."\n-->\n";
  } elsif ($type eq "php" || $type eq "css") {
    $notice="/*\n".$notice."\n*/";
  }

  return $notice;
}
