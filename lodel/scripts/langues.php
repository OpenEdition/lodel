<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

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

function makeselectlangues($lang="") {
  global $context,$langues;
  
  echo "<option value=\"\">--</option>\n";
  foreach ($langues as $l=>$langue) {
    $selected=$lang==$l ? " selected" : "";
    echo "<option value=\"$l\"$selected>$langue</option>\n";
  }
}


function makeselectlangues_nevernil($lang="") {
  global $context,$langues;

  foreach ($langues as $l=>$langue) {
    $selected=$lang==$l ? " selected" : "";
    echo "<option value=\"$l\"$selected>$langue</option>\n";
  }
}

?>
