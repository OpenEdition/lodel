<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include ($home."func.php");

$context[id]=intval($id);
$context[tache]=$tache=intval($tache);

if ($htmlfile && $htmlfile!="none") {
  do {
    // verifie que la variable htmlfile n'a pas ete hackee
    if (strpos(realpath($htmlfile),getenv("TMPDIR"))!=0) die("Erreur interne");

    //
    // regarde si le fichier est zipper
    //
    $fp=fopen($htmlfile,"r") or die("le fichier $htmlfile ne peut etre ouvert");
    $cle=fread($fp,2);
    if ($cle=="PK") {
      if ($oo2) echo "extract<br>"; flush();
      system("/usr/bin/unzip -j -p $htmlfile >$htmlfile.extracted");
      $htmlfile.=".extracted";
    }
    fclose($fp);
    //
    //
    //

    include_once($home."balises.php");
    if ($sortieoo2 || $sortiexmloo2 || $sortie2) $oo2=TRUE;
    if ($oo2) {
      $newname=OO2($htmlfile,$context);
    } else {
      $newname=OO($htmlfile);
    }
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
    if ($oo2) {
      echo '<a href="chkbalisage.php?id='.$idtache.'">continue</a>';
      return;
    }
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
  system("$javapath/bin/java -classpath \"$openofficepath/program/classes/jurt.jar:$openofficepath/program/classes/unoil.jar:$openofficepath/program/classes/ridl.jar:$openofficepath/program/classes/sandbox.jar:$openofficepath/program/classes/juh.jar:$home/oo/classes\" DocumentConverter \"$uploadedfile\" \"swriter: HTML (StarWriter)\" \"html\" \"$openofficepath/program/soffice \" 2>$errfile");

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
  array_push($rpl,"removeaccentsandspaces('<\\1r2r:\\2>')");
  

#"mots?_cles?"=>"motcles",
  $translations=array("notes_de_bas_de_page"=>"notebaspage",
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
		      "langue"=>"langues",
		      "titre_illustration"=>"titreillustration",
		      "legende_illustration"=>"legendeillustration",
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
	     "/<\/body>.*/s",
	     "/(<(col)\b[^>]*?)\/?>/i" # balise seule, il faut les fermer
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
	     "</r2r:article>",
	     "\\1 />"
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


////////////////////// nouvelle version /////////////////////////


function OO2 ($uploadedfile,&$context)


{
  global $home;

  $errfile="$uploadedfile.err";
  chmod($uploadedfile,0644); // temporaire, il faut qu'on gere le probleme des droits
  # cette partie n'est pas secur du tout. Il va falloir reflechir fort.
  echo "1ere conversion<br>\n";flush();
  $time1=time();
  runDocumentConverter($uploadedfile,"sxw");
  // solution avec unzip, ca serait mieux avec libzip
  // dezip le fichier content
  echo "temps:",time()-$time1,"<br>";
  echo "unzip process and write<br>\n";flush();
  $tmpdir=$uploadedfile."_dir";
  mkdir("$tmpdir",0700);

  copy($uploadedfile.".sxw",$uploadedfile."-second.sxw"); // copie pour avoir les droits d'ecriture
  $uploadedfile.="-second";

  system("/usr/bin/unzip -d $tmpdir $uploadedfile.sxw content.xml 2>$errfile") or die("probleme avec unzip<br>".@join("",@file($errfile)));
  $content=join("",file("$tmpdir/content.xml"));
  if ($GLOBALS[sortiexmloo2]) { echo htmlentities($content); exit(); }
  echo "<br>";
  // lit et modifie le fichier content.xml
  processcontent($content);

  // ecrit le fichier content.xml
  writefile("$tmpdir/content.xml",$content);
  system("/usr/bin/zip -j $uploadedfile.sxw $tmpdir/content.xml 2>$errfile") or die("probleme avec zip<br>".@join("",@file($errfile)));

  echo "<br>";
  // conversion en HTML
  echo "2nd conversion<br>\n";flush();
  $time2=time();
  runDocumentConverter($uploadedfile.".sxw","html");
  echo "temps:",time()-$time2,"<br>";
  echo "fin<br>\n";flush();


  $file=str_replace("\n","",join('',file("$uploadedfile.sxw.html")));

  if ($GLOBALS[sortieoo2]) { // on veut la sortie brute
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
  array_push($rpl,"removeaccentsandspaces('<\\1r2r:'.strtolower('\\2').'>')");  

  $translations=array("notesdebasdepage"=>"notebaspage",
		      "title"=>"titre","subtitle"=>"soustitre",
		      "document"=>"article","resume"=>"resume","auteur"=>"auteurs",
		      "footnotetext"=>"notebaspage",
		      "corpsdetexte\w*"=>"texte","bodytext"=>"texte",
		      "introduction"=>"texte","conclusion"=>"texte",
		      "normal"=>"texte", "normal\s*(web)"=>"texte",
		      "puces?"=>"texte",
		      "bloccitation"=>"citation",
		      "periode"=>"periodes",
		      "geographie"=>"geographies",
		      "descriptionauteur"=>"descriptionauteur",
		      "droitsauteur"=>"droitsauteur",
		      "typedocument"=>"typedoc",
		      "langue"=>"langues",
		      "titreillustration"=>"titreillustration",
		      "legendeillustration"=>"legendeillustration",
		      );
  
  foreach ($translations as $k=>$v) {
    array_push($srch,"/<r2r:$k(\b[^>]+)?>/","/<\/r2r:$k>/");
    if ($v) {
	array_push($rpl,"<r2r:$v\\1>","</r2r:$v>");
    } else {
	array_push($rpl,"","");
    } 
  }

  // conversion des balises avec publication
  array_push($srch,
	     "/<r2r:(?:section|heading|titre)(\d+\b([^>]*))>/",
	     "/<\/r2r:(?:section|heading|titre)(\d+)>/");
  array_push($rpl,
	     "<r2r:section\\1>",
	     "</r2r:section\\1>");

  // traitement un peu sale des footnote. On efface les paragraphes marques footnote et on remet sur la base du div
  array_push($srch,"/<\/?r2r:notebaspage>/","/<div id=\"sdfootnote\d+\">.*?<\/div>/is");
  array_push($rpl,"","<r2r:notebaspage>\\0</r2r:notebaspage>");

  array_push($srch,"/((?:<\w+[^>]*>\s*)+)\s*<r2r:([^>]+)>(.*?)<\/r2r:\\2>\s*((?:<\/\w+[^>]*>\s*)*)/s");
  array_push($rpl,"<r2r:\\2>\\1\\3\\4</r2r:\\2>");


  // remonte les balises. Base sur la presence du P (accepte un lien a l'interieur... ca permet de gerer les footnotes
#  array_push($srch,"/(<p\b[^>]*>(?:\s*<\w+[^>]*>)*)\s*<r2r:([^>]+)>(.*?)<\/r2r:\\2>(\s*(?:<\/\w+[^>]*>\s*)*<\/p>)/is");
#  array_push($rpl,"<r2r:\\2>\\1\\3\\4</r2r:\\2>");


  // autre chgt

  array_push($srch,
	     "/.*<body\b[^>]*>/si",
	     "/<\/body>.*/si",
	     "/<span\s*lang=\"[^\"]*\">(.*?)<\/span>/i", # enleve les span
	     "/(<a\b[^>]*)sdfixed>/i",
	     "/<div type=(?:header|footer)>.*?<\/div>/is",
	     "/<\w[^>]*>/e", // balises ouvrantes
	     "/<\/[^>]+>/e", // balises fermantes
	     "/<p\salign=\"(left|justify)\"(\s+[^>]*)>/", # enleve les alignements gauche et justify ....... surement inutile maintenant avec OO
	     "/<br\b([^>]*)>/",   # XML is
	     "/<\/br>/",	#efface
	     "/<li>/",
	     "/(<img\b[^>]+)border=\"?\d+\"?([^>]*>)/", # efface les border
	     "/(<img\b[^>\/]+)\/?>/i", # met border="0"
	     "/(<(col)\b[^>]*?)\/?>/i", # balise seule, il faut les fermer
	     "/(<p\b[^>]*>\s*<br\/><\/p>\s*)(<r2r:[^>]+>)/" // gere les sauts de ligne
	     );

  array_push($rpl,
	     '<r2r:article xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">',
	     "</r2r:article>",
	     "\\1",
	     "\\1>",
	     "",
	     'quote_attribut_strtolower("\\0")', // ouvrantes
	     'strtolower("\\0")',                // fermentes
	     "<p\\2>",
	     "<br\\1 />",
	     " ",
	     "<li />",
	     "\\1\\2",
	     "\\1border=\"0\"/>",
	     "\\1 />",
	     "\\2\\1"
	     );

  $time=time();
  $file=preg_replace ($srch,$rpl,$file);
  if (!traite_tableau2($file)) {     $context[erreur_stylestableaux]=1;
  return FALSE; }
  $file=traite_multiplelevel($file);
  echo "temps regexp: ".(time()-$time)."<br>\n";

  //echo htmlentities($file); exit;

# verifie que le document est bien forme
    include ($home."checkxml.php");
    if (!checkstring($file)) { echo "fichier: $newname"; echo htmlentities($file);
return FALSE; }



# enleve les couples de balises r2r.
    $file=traite_couple($file);

   if ($GLOBALS[sortie2]) die (htmlentities($file));

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

  echo "total:",time()-$time1,"<br>"; flush();

  return $newname;
}


function quote_attribut_strtolower($text)

{ 
  $i=strpos($text,"=");
  if ($i===FALSE) return strtolower($text);

  do {
    // attribut suivant
    $i++;
    $c=substr($text,$i,1);
    $indoublequote=0;
    $adddoublequote=0;
    while ($c!="") {
      if ($c=='\\') { $i+=2;   $c=substr($text,$i,1); next; } // quote
      if ($c==" " || $c==">") {
	if ($adddoublequote) {	  
	  $newtext.=substr($text,0,$i).'"';
	  break;
	} else {
	  $i++; // saute l'espace
	  $c=substr($text,$i,1); 
	  next; // espace
	}
      }
      if ($c=='"') { // toggle le doublequoteflags
	if ($indoublequote) {
	  // on sort, donc on cherche l'attribut suivant.
	  $newtext.=substr($text,0,$i);
	  break;
	} else {
	  $newtext.=strtolower(substr($text,0,$i)); 
	  $text=substr($text,$i);
	  $i=0; 
	  $indoublequote=1;	  
	}
      } elseif (!$indoublequote) { // il manque le quote
	$newtext.=strtolower(substr($text,0,$i)).'"'; 
	$text=substr($text,$i);
	$i=0; $indoublequote=1; $adddoublequote=1;
      }
      $i++;
      $c=substr($text,$i,1);
    }
    $text=substr($text,$i);
    $i=strpos($text,"=");
  } while ($i!==FALSE);

  return $newtext.strtolower($text);
}

////////////////////////////////////////////////////////



function convertHTMLtoUTF8 (&$text)

{

  $hash=array(
	      "eacute"=>'Ã©',
	      "Eacute"=>'Ã‰',
	      "iacute"=>'Ã­',
	      "Iacute"=>'Ã',
	      "oacute"=>'Ã³',
	      "Oacute"=>'Ã“',
	      "aacute"=>'Ã¡',
	      "Aacute"=>'Ã',
	      "uacute"=>'Ãº',
	      "Uacute"=>'Ãš',

	      "egrave"=>'Ã¨',
	      "Egrave"=>'Ãˆ',
	      "agrave"=>'Ã ',
	      "Agrave"=>'Ã€',
	      "ugrave"=>'Ã¹',
	      "Ugrave"=>'Ã™',
	      "ograve"=>'Ã²',
	      "Ograve"=>'Ã’',

	      "ecirc"=>'Ãª',
	      "Ecirc"=>'ÃŠ',
	      "icirc"=>'Ã®',
	      "Icirc"=>'ÃŽ',
	      "ocirc"=>'Ã´',
	      "Ocirc"=>'Ã”',
	      "acirc"=>'Ã¢',
	      "Acirc"=>'Ã‚',
	      "ucirc"=>'Ã»',
	      "Ucirc"=>'Ã›',

	      "Atilde"=>'Ãƒ',
	      "Auml"=>'Ã„',
	      "AElig"=>'Ã†',
	      "Ccedil"=>'Ã‡',
	      "Euml"=>'Ã‹',
	      "Igrave"=>'ÃŒ',
	      "Ntilde"=>'Ã‘',
	      "Iuml"=>'Ã',
	      "Ograve"=>'Ã’',
	      "Oacute"=>'Ã“',
	      "Ocirc"=>'Ã”',
	      "Otilde"=>'Ã•',
	      "Ouml"=>'Ã–',
	      "Uuml"=>'Ãœ',

	      "atilde"=>'Ã£',
	      "auml"=>'Ã¤',
	      "aelig"=>'Ã¦',
	      "ccedil"=>'Ã§',
	      "euml"=>'Ã«',
	      "igrave"=>'Ã¬',
	      "iuml"=>'Ã¯',
	      "ntilde"=>'Ã±',
	      "ograve"=>'Ã²',
	      "otilde"=>'Ãµ',
	      "ouml"=>'Ã¶',
	      "uuml"=>'Ã¼',
	      "yacute"=>'Ã½',
	      "yuml"=>'Ã¿',

		  "Aring" =>'\303\205',
		  "aring" =>'\303\245',
		  "curren"=>'\302\244',
		  "micro"=> '\302\265',
		  "Oslash"=>'\303\230',
  		  "cent"=>'\302\242',
		  "pound"=>'\302\243',
		  "ordf"=>'\302\252',
  		  "copy"=>'\302\251',
		  "para"=>'\303\266',
    	  "plusmm"=>'\302\261',
		  "THORN"=>'\303\236',
		  "shy"=>'\302\255',
 		  "not"=>'\302\254',


# ces trois derniers sont a verifier
	      "laquo"=>'Â«',
	      "raquo"=>'Â»',
	      "deg"=>'Â°',
	      "nbsp"=>'Â'.chr(160)
	      );

  $text=preg_replace("/&(\w+);/e",'$hash[\\1] ? $hash[\\1] : "\\0"',$text);
}


function removeaccentsandspaces($string){
return strtr(
 strtr(utf8_decode(preg_replace("/[\s_]/","",$string)),
  '¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
  'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
  '¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}



function processcontent(&$text)

{
  // recupere les conversions de system
  $convstyle=array();
  preg_match_all('/<style:style style:name="([^\"]+)"[^>]+?style:parent-style-name="([^\"]+)"/',$text,$results,PREG_SET_ORDER);
  foreach ($results as $result) $convstyle[$result[1]]=$result[2];

  // solution avec split
  $closere='<\/text:[ph]>';
  $arr=preg_split("/(<text:[ph]\b[^>]*>|$closere)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
#  echo count($arr),"<br>";
#  print_r($arr);
#  exit();
  $count=count($arr);
  $stylestack=array();
  for ($i=1; $i<$count; $i+=2) { // passe tous les delimiteurs
    if (preg_match("/$closere/",$arr[$i])) {
      $style=array_pop($stylestack); // recupere la balise fermante
      $arr[$i]=$style.$arr[$i]; // ajoute la balise fermante
    } else { // ok c'est un open alors
      if (preg_match('/\btext:style-name="([^"]+)"[^\/>]*>/',$arr[$i],$result)) { // est-ce qu'il y a un style
	$style=$result[1];
	if ($convstyle[$style]) $style=$convstyle[$style];
	$arr[$i].='[!--R2R:'.$style.'--]';
	array_push($stylestack,'[!/--R2R:'.$style.'--]');
      } else { // non pas de style.
	array_push($stylestack,"");
      }
    }
  }
#  print_r($arr);
#  exit();
  $text=join("",$arr);
#  die(htmlentities($text));
}


function runDocumentConverter($filename,$extension)

{
  global $home;

  # configuration (a mettre dans lodelconfig.php plus tard).
  $javapath="/usr/java/j2sdk1.4.1_02";
  $openofficepath="/usr/local/OpenOffice.org1.0.3";

  $errfile="$filename.err";

  if ($extension=="sxw") {
    $format="StarOffice XML (Writer)";
  } elseif ($extension=="html") {
    $format="HTML (StarWriter)";
  } else die ("probleme interne");

  system("$javapath/bin/java -classpath \"$openofficepath/program/classes/jurt.jar:$openofficepath/program/classes/unoil.jar:$openofficepath/program/classes/ridl.jar:$openofficepath/program/classes/sandbox.jar:$openofficepath/program/classes/juh.jar:$home/oo/classes\" DocumentConverterSimple \"$filename\" \"swriter: $format\" \"$extension\" \"$openofficepath/program/soffice \" 2>$errfile");

  $errcontent=join('',file($errfile));
  if ($errcontent) {
    echo "Erreur de lancement d'execution du script java:\n";
    echo "$errcontent\n";
    return;
  }
}



function traite_tableau2(&$text)

{
  // nouveaux traitement des tableaux.
  // decoupe les tableaux
  $arr=preg_split("/(<table\b|<\/table>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr); $level=0; $err=0;
  #echo "count=$count<br>";
  if ($count==1) return TRUE;
  for($i=1; $i<$count; $i+=2) {
    if ($arr[$i]=="</table>") { // ferme
      $level--;
      if ($level==0) $laststyle=""; // on vient de sortir du tableau
    } else { // on ouvre
      $level++;
    }
    if ($level && preg_match_all("/<\/r2r:(\w+)>/",$arr[$i+1],$results,PREG_SET_ORDER)) {
      foreach($results as $result) { // cherche si c'est partout le meme style
	if ($laststyle && $laststyle!=$result[1]) { $err=1; break 2; }
	$laststyle=$result[1];
      }
      $arr[$i+1]=preg_replace("/<\/?r2r:$laststyle>/","",$arr[$i+1]); // ok, on efface alors
    }
  }
  if ($err) {    return FALSE;  }

  $text=join("",$arr);
  return TRUE;
}



?>
