<?php
//
// liste des langues lodel
//

$langues=array("fr"=>"fran\303\247ais",
	       "en"=>"anglais",
	       "es"=>"espagnol",
	       "de"=>"allemand",
	       "it"=>"italien",
	       "ru"=>"russe",
	       "el"=>"grec");

//
// conversion des styles de resume en langue
//



#$langresume=array("resume"=>"fr",
#		  "abstract"=>"en",
#		  "extracto"=>"es",
#		  "zusammenfassung"=>"de",
#		  "riassunto"=>"it",
#		  ""=>"ru",
#		  ""=>"el");

// les balises et leur signification en clair (pour chkbalises.php)

#$balisesresume=array("resume"=>"Résumé",
#		    "abstract"=>"Abstract",
#			"extracto"=>"Extracto",
#			"zusammenfassung"=>"Zusammenfassung",
#			"riassunto"=>"Riassunto"
#		    );
#
//
// conversion des styles de motcles
//

#$langmotcle=array("motcles"=>"fr",
#		  "keywords"=>array("en","Keywords"),
#		  ""=>"es",
#		  ""=>"de",
#		  ""=>"it",
#		  ""=>"ru",
#		  ""=>"el");
#
#// les balises et leur signification en clair (pour chkbalises.php)
#
#$balisesmotcle=array("motcles"=>"Mot Clés",
#		     "keywords"=>"Keywords"
#		     );
#

############################################################################
# exportation des variables

#$GLOBALS[langues]=$langues;
#$GLOBALS[balisesresume]=$balisesresume;
#$GLOBALS[langresume]=$langresume;
#$GLOBALS[balisesmotcle]=$balisesmotcle;

############################################################################
//
// ne rien modifier ici
//

function makeselectlangues($lang="lang") {
  global $context,$langues;
  
  echo "<option value=\"\">--</option>\n";
  foreach ($langues as $l=>$langue) {
    $selected=$context[$lang]==$l ? " selected" : "";
    echo "<option value=\"$l\"$selected>$langue</option>\n";
  }
}


function makeselectlangues_nevernil($lang="lang") {
  global $context,$langues;

  foreach ($langues as $l=>$langue) {
    $selected=$context[$lang]==$l ? " selected" : "";
    echo "<option value=\"$l\"$selected>$langue</option>\n";
  }
}

?>
