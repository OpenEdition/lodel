<?

//
// liste des langues lodel
//

$langues=array("fr"=>"français",
	       "en"=>"anglais",
	       "es"=>"espagnol",
	       "de"=>"allemand",
	       "ru"=>"russe",
	       "el"=>"grec");

//
// conversion des styles de resume en langue
//

$langresume=array("resume"=>"fr",
		  "abstract"=>"en",
		  ""=>"es",
		  ""=>"de",
		  ""=>"ru",
		  ""=>"el");


//
// ne rien modifier ici
//

function makeselectlangues($lang="lang") {
  global $context,$langues;
    echo "<OPTION VALUE=\"\">--</OPTION>\n";
  foreach ($langues as $l=>$langue) {
    $selected=$context[$lang]==$l ? "SELECTED" : "";
    echo "<OPTION VALUE=\"$l\"$selected>$langue</OPTION>\n";
  }
}

?>