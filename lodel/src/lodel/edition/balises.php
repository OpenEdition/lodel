<?
$balises=array ("-" => "-",
		"titre" => "Titre",
		"soustitre" => "Sous titre",
		"auteurs" => "Auteurs",
		"motcles" => "Mots Clé",
		"periodes" => "Périodes",
		"geographies" => "Géographie",
		"resume" => "Résumé",
		"abstract" => "Abstract",
		"texte" => "Texte",
		"citation" => "Citation",
		"notebaspage" => "Notes",
		"typeart" => "Type de doc",
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
		);

$multiplelevel=array("section\d+"=>"texte",
		     "divbiblio"=>"bibliographie",
		     "citation"=>"texte",
		     "titredoc"=>"texte",
		     "legendedoc"=>"texte");

$division="(section\d+|divbiblio)"; # balises qui ne sont pas des paragraphes

$virgule_tags="(auteurs|periodes|geographies|motcles)";


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
