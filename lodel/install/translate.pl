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
# changement pour passer en version 5
#  $change+= $file=~s/(<\/?)boucle\b/$1LOOP/gi;
#  $change+= $file=~s/(<\/?)avant\b/$1BEFORE/gi;
#  $change+= $file=~s/(<\/?)apres\b/$1AFTER/gi;
#  $change+= $file=~s/(<\/?)premier\b/$1DOFIRST/gi;
#  $change+= $file=~s/(<\/?)dernier\b/$1DOLAST/gi;
#  $change+= $file=~s/(<\/?)first\b/$1DOFIRST/gi;
#  $change+= $file=~s/(<\/?)last\b/$1DOLAST/gi;
#  $change+= $file=~s/(<\/?)corps\b/$1DO/gi;
#  $change+= $file=~s/(<\/?)sinon\b/$1ALTERNATIVE/gi;
#  $change+= $file=~s/<ALTERNATIVE\/>(.*?)<\/LOOP>/$a=$1; if ($a=~m!<LOOP>!) { $&; print STDERR "Attention un alternatif n'a pas ete gere\n"; } else { "<ALTERNATIVE>$a<\/ALTERNATIVE><\/LOOP>"} /gise;
#  $change+= $file=~s/(<\/?)texte\b/$1TEXT/gi;
#  $file=~s/<if\s+([^>]+)\s*>/$b=$&; $a=$1; if ($a=~m!COND=!) { $b; } else {  $change++; $a=~y!\"!'!; "<IF COND=\"$a\">"; }/gei;

#
  $change+=$file=~s/(WHERE\s*=\s*\"[^\"]*)type_periode([^\"]*\")/$1type='periode'$2/g;
  $change+=$file=~s/(WHERE\s*=\s*\"[^\"]*)type_geographie([^\"]*\")/$1type='geographie'$2/g;
  $change+=$file=~s/(TABLE=\"indexls\")/TABLE=\"entrees\" WHERE=\"type='motcle'\"/g;
  $change+=$file=~s/\[\#MOT\]/[\#NOM]/g;

# chgt de auteur en personne
  $change+=$file=~s/(TABLE=\"auteurs\")/TABLE=\"personnes\" WHERE=\"type='auteur'\"/g;
  $change+=$file=~s/(<LOOP[^>]+id)auteur([^>]+>)/$1personne$2/g;
# chgt du a la fusion publications documents
  $change+=$file=~s/\[\#PUBLICATION\]/[\#IDPARENT]/g;
####  $change+=$file=~s/\[\#PARENT\]/[\#IDPARENT]/g;
  $change+=$file=~s/(<LOOP[^>]+)\bparent\b([^>]+>)/$1idparent$2/g;
  $change+=$file=~s/(<LOOP[^>]+TABLE\s*=\s*\"documents\"[^>]+)\bpublication\b([^>]+>)/$1idparent$2/g;


  $change+=$file=~s/\[\#SUPERADMIN\]/[\#ADMINLODEL]/g;
  $change+=$file=~s/\[GIF_VISAGE_SUPERADMIN\]/[GIF_VISAGE_ADMINLODEL]/g;

  next unless $change;
  print "$filename:",$change,"\n";

# ecriture du fichier
  open (TXT,">$filename");
  print TXT $file;
  close (TXT);
}
