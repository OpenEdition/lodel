#!/usr/bin/perl
# LODEL - Logiciel d'Édition ÉLectronique.
# @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
# @authors    See COPYRIGHT file

if ($ARGV[0] eq "-e") {
    shift @ARGV;
    $regexp=shift @ARGV;
    print "regexp:",$regexp,"\n";
}

foreach $filename (@ARGV) {
    open (TXT,$filename);
    $file=join '',<TXT>;
    close (TXT);

    if ($regexp && $regexp ne "") {
	$change=eval("\$file=~s$regexp");
    }
    print "$filename: $change\n";
    next unless $change;
    open (TXT,">$filename");
    print TXT $file;
    close (TXT);
}
