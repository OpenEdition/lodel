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
// $balises contient toutes les balises reconnues.
// la valeur de la balise definie l'affichage dans chkbalisage.php (et n'a aucune incidence ailleurs).
// les balises principales doivent etre associe a leur nom litteral
// les ss balises doivent etre associees a une/ou des balises html ou etre vide.

$balises=array ("-" => "-",
#		"titre" => "Titre",
#		"surtitre" => "Surtitre",
#		"soustitre" => "Sous-titre",
#		"auteurs" => "Auteurs",
#		"motcles" => "Mots Clés",
#		"periodes" => "Périodes",
#		"geographies" => "Géographie",
#		"resume" => "Résumé",
#		"texte" => "Texte",
		"citation" => "<blockquote>",
		"epigraphe" => "Epigraphe",
#		"notebaspage" => "Notes de bas de page",
#		"notefin" => "Notes de fin de document",
		"typedoc" => "Type de document",
		"finbalise" => "fin",
#		"bibliographie"=>"Bibliographie",
#		"annexe"=>"Annexe",
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
#		"droitsauteur"=>"Droits d'auteur",
#		"erratum"=>"Erratum",
#		"ndlr"=>"NDLR",
#		"historique"=>"Historique",
#		"pagination"=>"Pagination",
		"langues"=>"Langues",
# champs auteurs
		"description"=>"Description de l'auteur précédent",
#		"affiliation"=>"<span classe=\"affiliation\">",
#		"courriel"=>"<span classe=\"courriel\">",
#
# balises pour l'import de sommaire

		"regroupement"=>"Regroupement",
		"titrenumero"=>"Titre de la publication",
		"nomnumero"=>"Nom de la publication",
		"typenumero"=>"Type de la publication"
		);

//
// transparent style . Useful for the PDF export
//

$stylestransparents="paragraphetransparent|caracteretransparent";


#
# dans les deux tableaux ci-dessous on a la liste des balises qui apparaissent dans les documents, mais qui ne sont pas dans la base de donnee.
#
# balises qui ne sont extraites que lorsque le texte est publie.

# CHANGE 14/09/03 c'est maintenant la responsabilite du template
#$balisesdocument_lieautexte=array("texte",
#				  "notebaspage",
#				  "notefin",
#				  "bibliographie",
#				  "annexe",
#				  "erratum",
#				  "ndlr",
#				  "historique",
#				  );

# balises qui sont toujours extraites, meme si le texte n'est pas publie.
#$balisesdocument_nonlieautexte=array("resume",
#				     "droitsauteurs",
#				     "pagination"
#				     );


$balisesdocumentassocie=array("objetdelarecension"=>"Objet de la recension",
			      "traduction"=>"de la traduction");



//
// balises a plusieurs niveaux
// voir les codes ci-dessous
$multiplelevel[texte]=array(
#		     "divbiblio"=>"bibliographie",
		     "citation"=>"texte",
		     "epigraphe"=>"texte",
		     "titredoc"=>"texte",
		     "legendedoc"=>"texte",
	 	     "titreillustration"=>"texte",
		     "legendeillustration"=>"texte");

$multiplelevel[speciaux]=array(
			       "puce"=>"<*",
			       "puces"=>"<*",
			       "separateur"=>">*"
			       );


$multiplelevel[sections]=array(
		     // l'ordre est important ci-dessous (marche pas avec section\d+)
		     "section6"=>">*", // non utilise a priori
		     "section5"=>">*", // non utilise a priori
		     "section4"=>">*",
		     "section3"=>">*",
		     "section2"=>">*",
		     "section1"=>">*"
);


# utilise par chkbalises apres un balisage.php A supprimer si on supprime balisage.php
#$division="(section\d+|divbiblio)"; # balises qui ne sont pas des paragraphes
$division="(section\d+)"; # balises qui ne sont pas des paragraphes

# balises qui peuvent etre constituees de plusieurs paragraphes, donc ou chaque paragraphe sera agrege.
# CHANGE: 07/10/03 gerer par la DB
#$multiparagraphe_tags="titre|surtitre|soustitre|texte|citation|epigraphe|notebaspage|notefin|bibliographie|annexe|titredoc|legendedoc|titreillustration|legendeillustration|droitsauteur|erratum|ndlr|historique|pagination|descriptionauteur";


# tags qui admettent des listes separees par des virgules.
# CHANGE: 07/09/03 gerer par la DB.
#$virgule_tags="auteurs|periodes|geographies|motcles|langues";



#########################################################################

# ajoute les balises definies dans langues.php

include_once ($home."langues.php");
$balises=array_merge($balises,$balisesmotcle,$GLOBALS[balisesresume]);
#$multiparagraphe_tags.="|".join("|",array_keys($GLOBALS[langresume]));

#########################################################################
# export les variables dans le scope global

$GLOBALS[balises]=$balises;
$GLOBALS[balisesdocument_lieautexte]=$balisesdocument_lieautexte;
$GLOBALS[balisesdocument_nonlieautexte]=$balisesdocument_nonlieautexte;

#########################################################################


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
