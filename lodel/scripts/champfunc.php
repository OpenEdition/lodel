<?

function isvalidfield($nom)
{ return preg_match("/^[a-zA-Z0-9]+$/",$nom); }

function isvalidstyle($nom)
{ return preg_match("/^[a-zA-Z0-9]+$/",$nom); }

$GLOBALS[typechamps]=array("tinytext"=>"Texte court",
			   "text"=>"Texte",
			   "mltext"=>"Texte multilingue",
			   "image"=>"Image",
			   "fichier"=>"Fichier",
			   "url"=>"URL",
			   "date"=>"Date",
			   "datetime"=>"Date et Heure",
			   "time"=>"Heure",
			   "int"=>"Nombre entier",
			   "boolean"=>"Bool&eacute;en",
			   "number"=>"Nombre &agrave virgule",
			   "lang"=>"Langue",
			   "longtext"=>"Texte long",
			   );

$GLOBALS[sqltype]=array("tinytext"=>"tinytext",
			"text"=>"text",
			"mltext"=>"text",
			"image"=>"tinytext",
			"fichier"=>"tinytext",
			"url"=>"tinytext",
			"date"=>"date",
			"datetime"=>"datetime",
			"time"=>"time",
			"int"=>"int",
			"boolean"=>"tinyint",
			"number"=>"double precision",
			"lang"=>"tinytext",
			"longtext"=>"longtext",
			);


// le style doit etre parfaitement valide
function decode_mlstyle($style)

{
  $stylesarr=preg_split("/[\n,:]/",$style);
  if (!$stylesarr) return array();
  $count=count($stylesarr);
  for($i=0; $i<$count; $i+=2) {
    $k=trim($stylesarr[$i+1]);
    $stylesassoc[$k]=trim($stylesarr[$i]);
  }
  return $stylesassoc;
}


// le style doit etre parfaitement valide
function isvalidmlstyle($style)

{
  $stylesarr=preg_split("/([\n,:])/",$style,-1,PREG_SPLIT_DELIM_CAPTURE);
  if (!$stylesarr) return TRUE;
  $count=count($stylesarr);
  for($i=0; $i<$count; $i+=4) {
    if (!isvalidstyle(trim($stylesarr[$i]))) return FALSE; // le style 
    if ($stylesarr[$i+1]!=":") return FALSE; // le separateur
    if (!preg_match("/^\s*[a-z]{2}\s*$/",$stylesarr[$i+2])) return FALSE; // la langue
    if ($stylesarr[$i+3]==":") return FALSE; // les autres separateurs

    $k=trim($stylesarr[$i+1]);
    $stylesassoc[$k]=trim($stylesarr[$i+1]);
  }
  return TRUE;
}


?>
