#!/usr/bin/perl
#
# Usage: versionning.pl liste de fichier
#



foreach $filename (@ARGV) {

#lecture du fichier
  open (TXT,$filename);
  $file=join '',<TXT>;
  close (TXT);

  $change= $file=~s/(?:include|require)(_once)?\s*\("lodelconfig.php"\)/require$1("revueconfig.php")/g;

  next unless $change;
  print "$filename:",$change,"\n";

# ecriture du fichier
  open (TXT,">$filename");
  print TXT $file;
  close (TXT);
}
