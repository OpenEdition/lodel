<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);


$context[id]=intval($id);
$context[tache]=$tache=intval($tache);

if ($htmlfile && $htmlfile!="none") {
  do {
    // verifie que la variable htmlfile n'a pas ete hackee
    if (strpos(realpath($htmlfile),getenv("TMPDIR"))!=0) die("Erreur interne");
    include_once($home."balises.php");
    $newname=OO($htmlfile);
    if (!$newname) {
      $context[erreur_upload]=1;
      break;
    }
    if ($tache) { // document ancien ?
      $row=get_tache($tache);
      $row[fichier]=$newname;
    } else {
      $row=array("publication"=>$context[id],"fichier"=>$newname);
    }
    $idtache=make_tache("Import $htmlfile_name",3,$row,$tache);
    header("Location: chkbalisage.php?id=$idtache");
    return;
  } while (0); // exceptions
}



include ($home."calcul-page.php");
calcul_page($context,"oochargement");



function OO ($uploadedfile)


{
  global $home;

  # configuration (a mettre dans lodelconfig.php plus tard).
  $javapath="/usr/java/j2sdk1.4.1_02";
  $openofficepath="/usr/local/OpenOffice.org1.0.3";


  $errfile="$uploadedfile.err";
  chmod($uploadedfile,0644); // temporaire, il faut qu'on gere le probleme des droits
  # cette partie n'est pas secur du tout. Il va falloir reflechir fort.
  system("$javapath/bin/java -classpath \"$openofficepath/program/classes/jurt.jar:$openofficepath/program/classes/unoil.jar:$openofficepath/program/classes/ridl.jar:$openofficepath/program/classes/sandbox.jar:$openofficepath/program/classes/juh.jar:$home/oo/classes\" DocumentConverter \"$uploadedfile\" \"swriter: HTML (StarWriter)\" \"html\" \"$openofficepath/program/soffice -invisible\" 2>$errfile");

  $errcontent=join('',file("$errfile"));
  if ($errcontent) {
    echo "Erreur de lancement d'execution du script java:\n";
    echo "$errcontent\n";
    return;
  }

  $file=join('',file("$uploadedfile.html"));

  if ($GLOBALS[sortieoo]) { // on veut la sortie brute
    echo htmlentities($file);
    return;
  } elseif ($GLOBALS[sortieoohtml]) {
    echo $file;
    return;
  }

  convertHTMLtoUTF8($file);

  // tableau search et replace
  $srch=array(); $rpl=array();
  // convertir les caracteres html &xxx en UTF-8

  // convertir les balises (et supprimer les lettres avec accents, et les espaces diviennent des _)
  
  array_push($srch,"/\[!(\/?)--R2R:([^\]]+)--\]/e");
  array_push($rpl,"strtr('<\\1r2r:\\2>','����� \n','eeeaa__')");
  


  $translations=array("mots?_cles?"=>"motcles","notes_de_bas_de_page"=>"notebaspage",
		      "title"=>"titre","subtitle"=>"soustitre",
		      "document"=>"article","resume"=>"resume","auteur"=>"auteurs",
		      "footnote_text"=>"notebaspage",
		      "corps_de_texte\w*"=>"texte","body_text"=>"texte",
		      "introduction"=>"texte","conclusion"=>"texte",
		      "normal"=>"texte", "normal\s*(web)"=>"texte",
		      "puces?"=>"texte",
		      "bloc_citation"=>"citation",
		      "periode"=>"periodes",
		      "geographie"=>"geographies",
		      "description_auteur"=>"descriptionauteur",
		      "droits_auteur"=>"droitsauteur",
		      "type_document"=>"typedoc",
		      "langue"=>"langues"
		      );
  
  foreach ($translations as $k=>$v) {
    array_push($srch,"/<r2r:$k(\b[^>]+)?>/i","/<\/r2r:$k>/i");
    if ($v) {
	array_push($rpl,"<r2r:$v\\1>","</r2r:$v>");
    } else {
	array_push($rpl,"","");
    } 
  }

  // conversion des balises avec publication
  array_push($srch,
	     "/<r2r:(?:section|heading|titre)_(\d+\b([^>]*))>/i",
	     "/<\/r2r:(?:section|heading|titre)_(\d+)>/i");
  array_push($rpl,
	     "<r2r:section\\1>",
	     "</r2r:section\\1>");

  // remonte les balises. Base sur la presence du P
  array_push($srch,"/(<p\b[^>]*>(?:\s*<\w+[^>]*>)*)\s*<r2r:([^>]+)>(.*?)<\/r2r:\\2>(\s*(?:<\/\w+[^>]*>\s*)*<\/p>)/is");
  array_push($rpl,"<r2r:\\2>\\1\\3\\4</r2r:\\2>");

  // traitement un peu sale des footnote. L'autres solution serait de faire le marquage directement dans OO.

  array_push($srch,"/<div id=\"sdfootnote\d+\">.*?<\/div>/is");
  array_push($rpl,"<r2r:notebaspage>\\0</r2r:notebaspage>");

  // autre chgt

  array_push($srch,
	     "/<[^>]+>/e",
#	     "/<TD\b([^>]*)>(\s*(?:<[^>]+>)*\s*<DIV\s+ALIGN=\"([^\"]+)\")/is",
#	     "/<(\/?)(?:div|p)\b/i", # enleve les attributs des div et p et remplace par des p
	     "/<p\salign=\"(left|justify)\"(\s+[^>]*)>/", # enleve les alignements gauche et justify
	     "/<br\b([^>]*)>/i",   # XML is
	     "/<\/br>/i",	#efface
	     "/(<img\b[^>]+)border=\"?\d+\"?([^>]*>)/", # efface les border
	     "/(<img\b[^>\/]+)\/?>/i", # met border="0"
	     "/.*<body\b[^>]*>/s",
	     "/<\/body>.*/s"
	     );

  array_push($rpl,
	     'quote_attribut(strtolower("\\0"))',
#	     "<td\\1 align=\"\\3\">\\2",
#	     "<\\1p",
	     "<p\\2>",
	     "<br\\1/>",
	     " ",
	     "\\1\\2",
	     "\\1border=\"0\"/>",
	     '<r2r:article xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">',
	     "</r2r:article>"
	     );

  $file=traite_multiplelevel(preg_replace ($srch,$rpl,$file));

  //echo htmlentities($file); exit;

# verifie que le document est bien forme
    include ($home."checkxml.php");
    if (!checkstring($file)) { echo "fichier: $newname"; echo htmlentities($file);
return FALSE; }

# traite les tableaux pour sortir les styles
    include ($home."tableau.php");
    $file=traite_tableau($file);

# enleve les couples de balises r2r.
    $file=traite_couple($file);

   if ($GLOBALS[sortie]) die (htmlentities($file));

    function img_copy($imgfile,$ext,$count) {
      global $rand;

      $newimgfile="../../docannexe/tmp".$rand."_".$count.".".$ext;
      copy ("/tmp/$imgfile",$newimgfile) or die ("impossible de copier l'image $newimgfile");
      return $newimgfile;
    }
    include_once ($home."func.php");
    copy_images($file,"img_copy");


  // ecrit le fichier
    $newname="$uploadedfile-".rand();
  if (!writefile("$newname.html",$file)) return FALSE;

  return $newname;
}


function quote_attribut($text)

{
 # quote les arguments 

  return preg_replace("/(\w+\s*=)(\w+)/","\\1\"\\2\"",$text);
}

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

# ces trois derniers sont a verifier
	      "laquo"=>'«',
	      "raquo"=>'»',
	      "deg"=>'°',
	      "nbsp"=>'�'.chr(160)
	      );

  $text=preg_replace("/&(\w+);/e",'$hash[\\1] ? $hash[\\1] : "\\0"',$text);
}

?>
