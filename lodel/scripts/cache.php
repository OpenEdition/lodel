<?

include_once ("lodelconfig.php");
include_once ("$home/func.php");


if (!function_exists("authenticate")) {
  include ("$home/auth.php");
  authenticate();
}



if ($visiteur) {
  include ("$home/calcul-page.php");
  if ($format && !preg_match("/\W/",$format)) $base.="_".$format;
  calcul_page($context,$base);
  return;
}

if (!$maj) $maj=myfilemtime("CACHE/maj");


// Calcul du nom du fichier cache

$cache = substr(rawurlencode(preg_replace("/#[^#]*$/","",$REQUEST_URI)), 0, 255);
$rep_cache = substr(md5($cache), 0, 1);
if (!file_exists("CACHE/$rep_cache")) {
  mkdir("CACHE/$rep_cache", 0777);
#ifndef LODELLIGHT
  chmod("CACHE/$rep_cache", 0777); 
#endif
}
$cache = "CACHE/$rep_cache/$cache";

///////////$recalcul_templates=1;

////// Decommenter la ligne suivante pour desactive le cache
///$maj=myfilemtime($cache)+10;

// si le fichier de mise-a-jour est plus recent
if ($maj>=myfilemtime($cache)) {
  include ("$home/calcul-page.php");
  ob_start();
  if ($format && !preg_match("/\W/",$format)) $base.="_".$format;
  calcul_page($context,$base);
  $content=ob_get_contents();
  ob_end_clean();
  if ($visiteur) echo "MAJ";
  echo $content;

  // ecrit la premiere ligne et le reste
  $f = fopen($cache, "w");
  fputs($f,$content);
  fclose($f);

  exit();
}
// sinon affiche la cache.
 $f = fopen($cache, "r");
 fpassthru($f);
# fclose($f);

?>
