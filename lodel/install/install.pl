#!/usr/bin/perl

# ASSURE LA SECURITE DANS LODEL. LANCER CE SCRIPT DANS LE REPERTOIRE LODEL PAR: install/install.pl

htaccess ("CACHE");
htaccess ("tpl");
htaccess ("admin/CACHE");
htaccess ("admin/tpl");
htaccess ("revue");
htaccess ("install");
htaccess ("scripts");
htaccess ("r2r") if -e "r2r";

sub htaccess {
  $dir=shift;
  open(HT,">$dir/.htaccess") or die "Impossible d'ecrire dans $dir";
  print HT "deny from all\n";
  close (HT);
}
