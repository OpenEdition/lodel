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
		  "abstract","en",
		  ""=>"es",
		  ""=>"de",
		  ""=>"ru",
		  ""=>"el");

// les balises et leur signification en clair (pour chkbalises.php)

$balisesresume=array("resume"=>"Résumé",
		    "abstract"=>"Abstract"
		    );

//
// conversion des styles de motcles
//

$langmotcle=array("motcles"=>"fr",
		  "keywords"=>array("en","Keywords"),
		  ""=>"es",
		  ""=>"de",
		  ""=>"ru",
		  ""=>"el");

// les balises et leur signification en clair (pour chkbalises.php)

$balisesmotcle=array("motcles"=>"Mot Clés",
		     "keywords"=>"Keywords"
		     );


############################################################################
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


function makeselectlangues_nevernil($lang="lang") {
  global $context,$langues;

  foreach ($langues as $l=>$langue) {
    $selected=$context[$lang]==$l ? "SELECTED" : "";
    echo "<OPTION VALUE=\"$l\"$selected>$langue</OPTION>\n";
  }
}

?>
