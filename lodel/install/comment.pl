#!/usr/bin/perl
#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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



foreach $filename (@ARGV) {
    open (TXT,$filename);


    foreach (<TXT>) {
      if (/^\s*\#ifndef\s*LODELLIGHT/) { $lodel=1; $lodellight=0;}
      if (/^\s*\#else/)  { $lodel=0; $lodellight=1; }
      if (/^\s*\#endif/)  { $lodel=0; $lodellight=0; }

      if ($lodellight && !/^\s*\#/) { $file.="#"; }
      $file.=$_;
    }
    close (TXT);

    print STDERR "$filename\n";
    open (TXT,">$filename");
    print TXT $file;
    close (TXT);
}
