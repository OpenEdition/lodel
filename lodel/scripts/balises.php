<?
$balises=array ("-" => "-",
		"titre" => "Titre",
		"surtitre" => "Surtitre",
		"soustitre" => "Sous titre",
		"auteurs" => "Auteurs",
		"motcles" => "Mots Clé",
		"periodes" => "Périodes",
		"geographies" => "Géographie",
		"resume" => "Résumé",
		"texte" => "Texte",
		"citation" => "Citation",
		"epigraphe" => "Epigraphe",
		"notebaspage" => "Notes",
		"typedoc" => "Type de doc",
		"finbalise" => "fin",
		"bibliographie"=>"Bibliographie",
		"annexe"=>"Annexe",
		"section1"=>"Section 1",
		"section2"=>"Section 2",
		"section3"=>"Section 3",
		"section4"=>"Section 4",
		"titredoc"=>"Titre de document",
		"legendedoc"=>"Légende de document",
		"droitsauteur"=>"Droits d'auteurs",
		"erratum"=>"Erratum",
		"ndlr"=>"NDLR",
		"historique"=>"Historique",
		"pagination"=>"Pagination",
#
# balises pour l'import de sommaire

		"regroupement"=>"Regroupement",
		"titrenumero"=>"Titre du numéro",
		"nomnumero"=>"Nom du numéro"
		);

#
# dans les deux tableaux ci-dessous on a la liste des balises qui apparaissent dans les documents, mais qui ne sont pas dans la base de donnee.
#
    # balises qui ne sont extraites que lorsque le texte est publie.
$balisesdocument_lieautexte=array("texte",
				  "notebaspage",
				  "annexe",
				  "erratum",
				  "ndlr",
				  "historique",
				  );

    # balises qui sont toujours extraites, meme si le texte n'est pas publie.
$balisesdocument_nonlieautexte=array("resume",
				     "droitsauteur",
				     "pagination",
				     );


# balises a plusieurs niveaux

$multiplelevel=array("section\d+"=>"texte",
		     "divbiblio"=>"bibliographie",
		     "citation"=>"texte",
		     "epigraphe"=>"texte",
		     "titredoc"=>"texte",
		     "legendedoc"=>"texte");


# utilise par chkbalises apres un balisage.php A supprimer si on supprime balisage.php
$division="(section\d+|divbiblio)"; # balises qui ne sont pas des paragraphes

# tags qui admettent des listes separees par des virgules.
$virgule_tags="(auteurs|periodes|geographies|motcles)";



#########################################################################

# ajoute les balises definies dans langues.php

include ("$home/langues.php");
$balises=array_merge($balises,$balisesmotcle,$balisesresume);

#########################################################################
# export les variables dans le scope global

$GLOBALS[balises]=$balises;
$GLOBALS[balisesdocument_lieautexte]=$balisesdocument_lieautexte;
$GLOBALS[balisesdocument_nonlieautexte]=$balisesdocument_nonlieautexte;

#########################################################################


function traite_multiplelevel(&$text)

{
  global $multiplelevel;

  $search=array(); $rpl=array();

  foreach ($multiplelevel  as $k=>$v) {
    array_push($search,"/<r2r:$k(\b[^>]+)?>/i","/<\/r2r:$k>/i");
    array_push($rpl,"<r2r:$v>\\0","\\0</r2r:$v>");
  }
  return preg_replace ($search,$rpl,$text);
}


function traite_couple(&$text)

{
  global $virgule_tags;
  return preg_replace (
		       array(
			     "/<\/r2r:$virgule_tags>[\s\n\r]*<r2r:\\1(\s+[^>]+)?>/i",  # les tags a virgule
			     "/<\/r2r:([^>]+)>((?:<br>|\s|\n|\r)*)<r2r:\\1(\s+[^>]+)?>/i", # les autres tags    
			     ),
		       array(
			     ",",
			     "\\2",
			     ),
		       $text);
}

?>
