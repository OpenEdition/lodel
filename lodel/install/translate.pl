#!/usr/bin/perl
#
# Usage: versionning.pl liste de fichier
#



foreach $filename (@ARGV) {

#lecture du fichier
  open (TXT,$filename);
  $file=join '',<TXT>;
  close (TXT);

  $change=0;
  $change+= $file=~s/(<\/?)boucle\b/$1LOOP/gi;
  $change+= $file=~s/(<\/?)avant\b/$1BEFORE/gi;
  $change+= $file=~s/(<\/?)apres\b/$1AFTER/gi;
  $change+= $file=~s/(<\/?)premier\b/$1FIRST/gi;
  $change+= $file=~s/(<\/?)dernier\b/$1LAST/gi;
  $change+= $file=~s/(<\/?)sinon\b/$1ALTERNATIVE/gi;
  $change+= $file=~s/(<\/?)texte\b/$1TEXT/gi;
#  $change+= $file=~s/<if\s+([^>]+)\s*>/$a=$1; if ($a=~?COND=?) { $&; } else { $a=~y?\"?'?; "<IF COND=\"$a\">"; }/gei;


  $file=~s/<if\s+([^>]+)\s*>/$b=$&; $a=$1; if ($a=~m!COND=!) { $b; } else {  $change++; $a=~y!\"!'!; "<IF COND=\"$a\">"; }/gei;

  next unless $change;
  print "$filename:",$change,"\n";

# ecriture du fichier
  open (TXT,">$filename");
  print TXT $file;
  close (TXT);
}
