<?

include ("lodelconfig.php");
$rep="";
include ("$home/connect.php");

$sql=preg_split ("/;/",join('',file("init.sql")));

#ifndef LODELLIGHT
$sql=str_replace("_PREFIXTABLE_","",$sql);
#endif

?>
<H1>Initialisation SQL</H1>

<?

foreach ($sql as $cmd) {
  $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
  if ($cmd) {
    print "$cmd<BR>\n";
    mysql_query($cmd) or print ("<font COLOR=red>".mysql_error()."</font><br>");
    print "<BR>\n";
  }
}

?>

<H1>Initialisation des repertoires</H1>

<?

# essaie d'ecrire dans differents repertoires.

$dirs=array("CACHE","admin/CACHE");
foreach ($dirs as $dir) {
  $file="../$dir/ess.tmp";
  $f=@fopen ($file,"w");
  if (!$f) {
    print ("<font COLOR=red>Impossible d'ecrire dans le repertoire $dir. Faire: chmod 770 $dir ou chmod 777 $dir</font><br>");
  } else {
    fclose ($f);
    unlink ($file);
  }
}

?>

