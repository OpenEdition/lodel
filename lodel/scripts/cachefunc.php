<?

function removefilesincache()

{
  // cette fonction pourrait etre ecrite de facon bcp plus simple avec de la recurrence. Pour des raisons de securite/risque de bugs, elle est doublement proteger. 
  // On ajoute le repertoire CACHE dans le code, ce qui empeche de detruire le contenu d'un autre repertoire. On ne se propage pas de facon recurrente.
  // 
  foreach(func_get_args() as $rep) {
    $rep.="/CACHE";
    $fd=opendir($rep) or die ("Impossible d'ouvrir $rep");
    while ($file=readdir($fd)) {
	  if ((substr($file,0,1)==".") || ($file=="CVS")) continue;
      $file=$rep."/".$file;
      if (is_dir($file)) {
	$rep2=$file;
	$fd2=opendir($rep2) or die ("Impossible d'ouvrir $file");
	while ($file=readdir($fd2)) {
	  if (substr($file,0,1)==".") continue;
	  $file=$rep2."/".$file;
	  if (is_file($file)) {
	    unlink($file);
	  }
	}
	closedir($fd2);
      } elseif (is_file($file)) {
	unlink($file);
      }
    }
    closedir($fd);
  }
}

?>
