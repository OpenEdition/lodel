<?

//
// $balises contient toutes les balises reconnues.
// la valeur de la balise definie l'affichage dans chkbalisage.php (et n'a aucune incidence ailleurs).
// les balises principales doivent etre associe a leur nom litteral
// les ss balises doivent etre associees a une/ou des balises html ou etre vide.

$balises=array ("-" => "-",
		"titre" => "Titre",
		"Titre 1" => "Titre 1",
		"Titre 2" => "Titre 2",
		"Titre 3" => "Titre 3",
		"Titre 4" => "Titre 4",
		"Titre 5" => "Titre 5",
		"Titre 6" => "Titre 6",
		"surtitre" => "Surtitre",
		"Surtitre" => "Surtitre",
		"soustitre" => "Sous-titre",
		"Sous-titre" => "Sous-titre",
		"auteurs" => "Auteurs",
		"Auteur" => "Auteur",
		"motcles" => "Mots Clés",
		"Mots Cles" => "Mots Clés",
		"periodes" => "Périodes",
		"geographies" => "Géographie",
		"Periode" => "Période",
		"Geographie" => "Géographie",
		"resume" => "Résumé",
		"Resume" => "Résumé",
		"Abstract" => "Abstract",
		"Extracto" => "Extracto",
		"Riassunto" => "Riassunto",
		"Zusammenfassung" => "Zusammenfassung",
		"texte" => "Texte",
		"citation" => "<blockquote>",
		"Citation" => "<blockquote>",
		"epigraphe" => "Epigraphe",
		"Epigraphe" => "Epigraphe",
		"notebaspage" => "Notes",
		"Notes bas de page" => "Notes de bas de page",
		"typedoc" => "Type de doc",
		"Type document" => "Type de document",
		"finbalise" => "fin",
		"bibliographie"=>"Bibliographie",
		"Bibliographie"=>"Bibliographie",
		"annexe"=>"Annexe",
		"Annexes"=>"Annexes",
		"section1"=>"<h1>",
		"Section 1"=>"<h1>",
		"section2"=>"<h2>",
		"Section 2"=>"<h2>",
		"section3"=>"<h3>",
		"Section 3"=>"<h3>",
		"section4"=>"<h4>",
		"Section 4"=>"<h4>",
		"Section 5"=>"<h5>",
		"Section 6"=>"<h6>",
		"titredoc"=>"Titre de document",
		"Titre Illustration"=>"Titre Illustration",
		"legendedoc"=>"Légende de document",
		"Legende Illustration"=>"Légende Illustration",
		"droitsauteur"=>"Droits d'auteurs",
		"Droits Auteur"=>"Droits d'auteurs",
		"erratum"=>"Erratum",
		"Erratum"=>"Erratum",
		"ndlr"=>"NDLR",
		"NDLR"=>"NDLR",
		"historique"=>"Historique",
		"Historique"=>"Historique",
		"pagination"=>"Pagination",
		"Pagination"=>"Pagination",
# champs auteurs
		"descriptionauteur"=>"Description de l'auteur précédent",
		"Description Auteur"=>"Description de l'auteur",
#		"affiliation"=>"<span class=\"affiliation\">",
#		"courriel"=>"<span class=\"courriel\">",
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
				  "Notes bas de page",
				  "annexe",
				  "Annexes",
				  "erratum",
				  "Erratum",
				  "ndlr",
				  "NDLR",
				  "historique",
				  "Historique"
				  );

    # balises qui sont toujours extraites, meme si le texte n'est pas publie.
$balisesdocument_nonlieautexte=array("resume",
					 "Resume",
					 "Abstract",
					 "Extracto",
					 "Riassunto",
					 "Zusammenfassung",
				     "droitsauteur",
					 "Droits Auteur",
				     "pagination",
					 "Pagination"
				     );

//
// balises a plusieurs niveaux
// voir les codes ci-dessous
$multiplelevel=array(
#		     "divbiblio"=>"bibliographie",
		     "citation"=>"texte",
		     "Citation"=>"texte",
		     "epigraphe"=>"texte",
		     "Epigraphe"=>"texte",
		     "titredoc"=>"texte",
		     "legendedoc"=>"texte",
			 "Titre Illustration"=>"texte",
			 "Legende Illustration"=>"texte",

		     // les styles description auteurs
# supprimer pour le moment tant que Ted ne lit pas les styles de caracteres
#		     "affiliation"=>"descriptionauteur",
#		     "courriel"=>"descriptionauteur",

		     // l'ordre est important ci-dessous (marche pas avec section\d+)
		     "section6"=>">*", // non utilise a priori
		     "Section 6"=>">*", // non utilise a priori
		     "section5"=>">*", // non utilise a priori
		     "Section 5"=>">*", // non utilise a priori
		     "section4"=>">*",
		     "Section 4"=>">*",
		     "section3"=>">*",
		     "Section 3"=>">*",
		     "section2"=>">*",
		     "Section 2"=>">*",
		     "section1"=>">*",
		     "Section 1"=>">*",
);


# utilise par chkbalises apres un balisage.php A supprimer si on supprime balisage.php
#$division="(section\d+|divbiblio)"; # balises qui ne sont pas des paragraphes
$division="(section\d+)"; # balises qui ne sont pas des paragraphes

# balises qui peuvent etre constituees de plusieurs paragraphes, donc ou chaque paragraphe sera agrege.
$multiparagraphe_tags="titre|surtitre|soustitre|resume|texte|citation|epigraphe|notebaspage|bibliographie|annexe|titredoc|legendedoc|droitsauteur|erratum|ndlr|historique|pagination|descriptionauteur";


# tags qui admettent des listes separees par des virgules.
$virgule_tags="auteurs|periodes|geographies|motcles";



#########################################################################

# ajoute les balises definies dans langues.php

include_once ("$home/langues.php");
$balises=array_merge($balises,$balisesmotcle,$balisesresume);

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
  return preg_replace (
		       array(
			     "/<\/r2r:($virgule_tags)>[\s\n\r]*<r2r:\\1(\s+[^>]+)?>/i",  # les tags a virgule
			     "/<\/r2r:($multiparagraphe_tags)>((?:<br>|[\s\n\r]+)*)<r2r:\\1\b[^>]*>/i", # les autres tags    
			     ),
		       array(
			     ",",
			     "\\2",
			     ),
		       $text);
}

?>
