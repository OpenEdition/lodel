<?

function isvalidfield($nom)

{ return preg_match("/^[a-zA-Z0-9]+$/",$nom); }

$GLOBALS[typechamps]=array("tinytext"=>"Texte court",
			   "text"=>"Texte",
			   "image"=>"Image",
			   "fichier"=>"Fichier",
			   "url"=>"URL",
			   "date"=>"Date",
			   "datetime"=>"Date et Heure",
			   "time"=>"Heure",
			   "int"=>"Nombre entier",
			   "number"=>"Nombre &agrave virgule",
			   "lang"=>"Langue",
			   "longtext"=>"Texte long",
			   );

$GLOBALS[sqltype]=array("tinytext"=>"tinytext",
			"text"=>"text",
			"image"=>"tinytext",
			"fichier"=>"tinytext",
			"url"=>"tinytext",
			"date"=>"date",
			"datetime"=>"datetime",
			"time"=>"time",
			"int"=>"int",
			"number"=>"double precision",
			"lang"=>"tinytext",
			"longtext"=>"longtext",
			);


?>
