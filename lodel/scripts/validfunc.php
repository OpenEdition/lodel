<?

function isvalidtype($nom) 
{return preg_match("/^[a-zA-Z0-9_-]+$/",$nom);}

function isvalidfield($nom) 
{return preg_match("/^[a-zA-Z0-9]+$/",$nom);}

function isvalidstyle($nom)
{ return preg_match("/^[a-zA-Z0-9]+$/",$nom); }


function isvalidmlstyle($style)

{
  $stylesarr=preg_split("/([\n,:])/",$style,-1,PREG_SPLIT_DELIM_CAPTURE);
  if (!$stylesarr) return TRUE;
  $count=count($stylesarr);
  for($i=0; $i<$count; $i+=4) {
    if (!isvalidstyle(trim($stylesarr[$i]))) return FALSE; // le style 
    if ($stylesarr[$i+1]!=":") return FALSE; // le separateur
    if (!preg_match("/^\s*([a-z]{2}|--)\s*$/",$stylesarr[$i+2])) return FALSE; // la langue
    if ($stylesarr[$i+3]==":") return FALSE; // les autres separateurs

    $k=trim($stylesarr[$i+1]);
    $stylesassoc[$k]=trim($stylesarr[$i+1]);
  }
  return TRUE;
}


?>
