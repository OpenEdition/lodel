<?

//
// $balises contient toutes les balises reconnues.
// la valeur de la balise definie l'affichage dans chkbalisage.php (et n'a aucune incidence ailleurs).
// les balises principales doivent etre associe a leur nom litteral
// les ss balises doivent etre associees a une/ou des balises html ou etre vide.

$balises=array ("-" => "-",
		"titre" => "Titre",
		"surtitre" => "Surtitre",
		"soustitre" => "Sous-titre",
		"auteurs" => "Auteurs",
#		"motcles" => "Mots Clés",
#		"periodes" => "Périodes",
#		"geographies" => "Géographie",
		"resume" => "Résumé",
		"texte" => "Texte",
		"citation" => "<blockquote>",
		"epigraphe" => "Epigraphe",
		"notebaspage" => "Notes de bas de page",
		"notefin" => "Notes de fin de document",
		"typedoc" => "Type de document",
		"finbalise" => "fin",
		"bibliographie"=>"Bibliographie",
		"annexe"=>"Annexe",
		"section1"=>"<h1>",
		"section2"=>"<h2>",
		"section3"=>"<h3>",
		"section4"=>"<h4>",
		"section5"=>"<h5>",
		"section6"=>"<h6>",
		"titredoc"=>"<i>",
		"legendedoc"=>"<i>",
		"titreillustration"=>"<i>",
		"legendeillustration"=>"<i>",
		"droitsauteur"=>"Droits d'auteur",
		"erratum"=>"Erratum",
		"ndlr"=>"NDLR",
		"historique"=>"Historique",
		"pagination"=>"Pagination",
		"langues"=>"Langues",
# champs auteurs
		"descriptionauteur"=>"Description de l'auteur précédent",
#		"affiliation"=>"<span classe=\"affiliation\">",
#		"courriel"=>"<span classe=\"courriel\">",
#
# balises pour l'import de sommaire

		"regroupement"=>"Regroupement",
		"titrenumero"=>"Titre de la publication",
		"nomnumero"=>"Nom de la publication",
		"typenumero"=>"Type de la publication"
		);


#
# dans les deux tableaux ci-dessous on a la liste des balises qui apparaissent dans les documents, mais qui ne sont pas dans la base de donnee.
#
# balises qui ne sont extraites que lorsque le texte est publie.
$balisesdocument_lieautexte=array("texte",
				  "notebaspage",
				  "notefin",
				  "bibliographie",
				  "annexe",
				  "erratum",
				  "ndlr",
				  "historique",
				  );

# balises qui sont toujours extraites, meme si le texte n'est pas publie.
$balisesdocument_nonlieautexte=array("resume",
				     "droitsauteurs",
				     "pagination"
				     );


$balisesdocumentassocie=array("objetdelarecension"=>"Objet de la recension",
			      "traduction"=>"de la traduction");

//
// balises a plusieurs niveaux
// voir les codes ci-dessous
$multiplelevel=array(
#		     "divbiblio"=>"bibliographie",
		     "citation"=>"texte",
		     "epigraphe"=>"texte",
		     "titredoc"=>"texte",
		     "legendedoc"=>"texte",
	 	     "titreillustration"=>"texte",
	    	 "legendeillustration"=>"texte",


		     // les styles description auteurs
# supprimer pour le moment tant que Ted ne lit pas les styles de caracteres
#		     "affiliation"=>"descriptionauteur",
#		     "courriel"=>"descriptionauteur",

		     // l'ordre est important ci-dessous (marche pas avec section\d+)
		     "section6"=>">*", // non utilise a priori
		     "section5"=>">*", // non utilise a priori
		     "section4"=>">*",
		     "section3"=>">*",
		     "section2"=>">*",
		     "section1"=>">*",
		     "separateur"=>">*"
);


# utilise par chkbalises apres un balisage.php A supprimer si on supprime balisage.php
#$division="(section\d+|divbiblio)"; # balises qui ne sont pas des paragraphes
$division="(section\d+)"; # balises qui ne sont pas des paragraphes

# balises qui peuvent etre constituees de plusieurs paragraphes, donc ou chaque paragraphe sera agrege.
$multiparagraphe_tags="titre|surtitre|soustitre|texte|citation|epigraphe|notebaspage|notefin|bibliographie|annexe|titredoc|legendedoc|titreillustration|legendeillustration|droitsauteur|erratum|ndlr|historique|pagination|descriptionauteur";


# tags qui admettent des listes separees par des virgules.
$virgule_tags="auteurs|periodes|geographies|motcles|langues";



#########################################################################

# ajoute les balises definies dans langues.php

include_once ($home."langues.php");
$balises=array_merge($balises,$balisesmotcle,$GLOBALS[balisesresume]);
$multiparagraphe_tags.="|".join("|",array_keys($GLOBALS[langresume]));

#########################################################################
# export les variables dans le scope global

$GLOBALS[balises]=$balises;
$GLOBALS[balisesdocument_lieautexte]=$balisesdocument_lieautexte;
$GLOBALS[balisesdocument_nonlieautexte]=$balisesdocument_nonlieautexte;

#########################################################################


// les balises multiplelevel. Restructure la stylisation plate de Word en une structure a plusieurs niveaux (2 niveaux en general)

// > signifie que cette balise se ratache avec celle d'apres
// rien signifie que cette balise s'entoure de la balises donner dans le tableau

// * signifie toutes les balises
// balise: signifie que cette balises

function traite_multiplelevel(&$text)

{
  global $multiplelevel;

  $search=array(); $rpl=array();

  foreach ($multiplelevel  as $k=>$v) {
    $balouvrante="<r2r:$k(?:\b[^>]+)?>";
    $balfermante="<\/r2r:$k>";

    // determine ce qu'il faut faire
//    if (preg_replace("/^>/","",$v)) { $dir="apres"; } 
//    elseif (preg_replace("/^</","",$v)) { $dir="avant"; }
//    else { $dir=""; };
    if (substr($v,0,1)==">") { $dir="apres"; $v=substr($v,1); } 
    elseif (substr($v,0,1)=="<") { $dir="avant"; $v=substr($v,1); } 
    else { $dir=""; };

    if ($v=="*") $v="\w+";

    if ($dir=="apres") { // entoure par la balise qui suit
      array_push($search,"/($balouvrante.*?$balfermante)[\s\n\r]*(<r2r:$v(?:\b[^>]+)?>)/is");
      array_push($rpl,"\\2\\1"); // permute le bloc avec la balise qui suit
    } elseif ($dir=="avant") {
      array_push($search,"/(<r2r:$v(?:\b[^>]+)?>)[\s\n\r]*($balouvrante.*?$balfermante)/is");
      array_push($rpl,"\\2\\1"); // permute le bloc avec la balise qui precede
    } else { // entoure par la balise donne dans $v
      array_push($search,"/$balouvrante/i","/$balfermante/i");
      array_push($rpl,"<r2r:$v>\\0","\\0</r2r:$v>");
    }
  }
  //die (join(" ",$search)."<br>".join(" ",$rpl));
  return preg_replace ($search,$rpl,$text);
}


function traite_couple(&$text)

{
  global $virgule_tags,$multiparagraphe_tags;

  $balisere="(?:$multiparagraphe_tags)(?:\(\w+\))?"; # gere les cas avec parenthese
  return preg_replace (
		       array(
			     "/<\/r2r:($virgule_tags)>[\s\r]*<r2r:\\1(\s+[^>]+)?>/i",  # les tags a virgule
			     "/<\/r2r:($balisere)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|[\s\r])*)<r2r:\\1(?:\s[^>]*)?>/is", # les autres tags    
			     ),
		       array(
			     ",",
			     "",
			     ),
		       $text);
}

//
// traite les separateurs
//

function traite_separateur($text) {
  return preg_replace_callback("/<r2r:separateur>(.*?)<\/r2r:separateur>/","convertseparateur",$text);
}

//
// fonction callback de conversion des separateurs en balises HTML
//

function convertseparateur($result) {
  $text=trim(strip_tags($result[1]));
  if ($text=="*") return "<hr width=\"30%\" \ >";
  if ($text=="**") return "<hr width=\"50%\" \ >";
  if ($text=="***") return "<hr width=\"80%\" \ >";
  if ($text=="****") return "<hr \ >";
  return "";
}


?>
