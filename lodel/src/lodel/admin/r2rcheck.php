<?

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN);

include ("$home/checkxml.php");

$rep="$home/../r2r/tmptxt";
$d=dir($rep) or die ("impossible d'ouvrir $rep");
while ($entry=$d->read()) {
  if (!preg_match("/^\d+-\d+$/",$entry)) continue;
  echo "$entry<br>";
  $d2=dir("$rep/$entry") or die ("impossible d'ouvrir $entry");
  $files=array();
  while ($entry2=$d2->read()) if (preg_match("/\.xml$/",$entry2)) array_push($files,$entry2);
  sort($files);
  $d2->close();
  foreach($files as $entry2) {
    echo "<u>$rep/$entry/$entry2</u>:<br>";
    if (!checkfile("$rep/$entry/$entry2")) die("");
  }
}
$d->close();


?>
