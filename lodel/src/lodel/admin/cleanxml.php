<?
// script de nettoyage des fichiers XML.
// utile lorsque les versions se succede.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$search=array();
$rpl=array();

array_push($search,"/<sup><small>(<[^>]+>)*?<\/small><\/sup>/i");
array_push($rpl,"");

$dirname="../txt";
if ($dir=@opendir ($dirname)) {
  while (($file = readdir($dir)) !== false) {
    $file=$dirname."/".$file;
    if (!preg_match("/.xml$/",$file) || !is_file($file)) continue; // passe si ce n'est pas un fichier standart
    echo "$file";
    $text=join("",file($file));
    $newtext=preg_replace($search,$rpl,$text);
    if ($newtext!=$text) {
      echo "...cleaned";
      writefile($file,$newtext);
    }
    echo "<br>";
  }  
  closedir($dir);
} else {
  die ("impossible d'ouvrir le repertoire ../txt");
}



?>
