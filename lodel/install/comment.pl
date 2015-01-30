#!/usr/bin/perl
# LODEL - Logiciel d'Édition ÉLectronique.
# @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
# @authors    See COPYRIGHT file

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
