<?


function convertHTMLtoUTF8 (&$text)

{
  $hash=array(
	      "eacute"=>'é',
	      "Eacute"=>'É',
	      "iacute"=>'í',
	      "Iacute"=>'Í',
	      "oacute"=>'ó',
	      "Oacute"=>'Ó',
	      "aacute"=>'á',
	      "Aacute"=>'Á',
	      "uacute"=>'ú',
	      "Uacute"=>'Ú',

	      "egrave"=>'è',
	      "Egrave"=>'È',
	      "agrave"=>'à',
	      "Agrave"=>'À',
	      "ugrave"=>'ù',
	      "Ugrave"=>'Ù',
	      "ograve"=>'ò',
	      "Ograve"=>'Ò',

	      "ecirc"=>'ê',
	      "Ecirc"=>'Ê',
	      "icirc"=>'î',
	      "Icirc"=>'Î',
	      "ocirc"=>'ô',
	      "Ocirc"=>'Ô',
	      "acirc"=>'â',
	      "Acirc"=>'Â',
	      "ucirc"=>'û',
	      "Ucirc"=>'Û',

	      "Atilde"=>'Ã',
	      "Auml"=>'Ä',
	      "AElig"=>'Æ',
	      "OElig"=>"\305\222",
	      "oelig"=>"\305\223",
	      "Ccedil"=>'Ç',
	      "Euml"=>'Ë',
	      "Igrave"=>'Ì',
	      "Ntilde"=>'Ñ',
	      "Iuml"=>'Ï',
	      "Ograve"=>'Ò',
	      "Oacute"=>'Ó',
	      "Ocirc"=>'Ô',
	      "Otilde"=>'Õ',
	      "Ouml"=>'Ö',
	      "Uuml"=>'Ü',

	      "atilde"=>'ã',
	      "auml"=>'ä',
	      "aelig"=>'æ',
	      "ccedil"=>'ç',
	      "euml"=>'ë',
	      "igrave"=>'ì',
	      "iuml"=>'ï',
	      "ntilde"=>'ñ',
	      "ograve"=>'ò',
	      "otilde"=>'õ',
	      "ouml"=>'ö',
	      "uuml"=>'ü',
	      "yacute"=>'ý',
	      "yuml"=>'ÿ',
	      "Aring" =>"\303\205",
	      "aring" =>"\303\245",
	      "curren"=>"\302\244",
	      "micro"=> "\302\265",
	      "Oslash"=>"\303\230",
	      "cent"=>"\302\242",
	      "pound"=>"\302\243",
	      "ordf"=>"\302\252",
	      "copy"=>"\302\251",
	      "para"=>"\303\266",
	      "plusmm"=>"\302\261",
	      "THORN"=>"\303\236",
	      "shy"=>"\302\255",
	      "not"=>"\302\254",
	      "hellip"=>"\342\200\246",
	      "laquo"=>'«',
	      "raquo"=>'»',
	      "lsquo"=>"\342\200\230",
	      "rsquo"=>"\342\200\231",
	      "ldquo"=>"\342\200\234",
	      "rdquo"=>"\342\200\235",
	      "deg"=>'°',
	      "nbsp"=>"�\240",
	      "mdash"=>"\342\200\224",
	      "ndash"=>"\342\200\223",

	      "reg"=>"\302\256",
	      "sect"=>"\302\247"
	      );

  $text=preg_replace("/&(\w+);/e",'$hash[\\1] ? $hash[\\1] : "\\0"',$text);
}


?>
