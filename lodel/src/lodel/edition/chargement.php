<?

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);

include("balises.php");


/* Ca marche pas pour le moment
error_reporting(E_ALL & ~E_NOTICE & ~E_ERROR);

function timeouthandler ()

{
   $text="timeout";
    writefile("/tmp/timeout",$text);
 
  if (connection_timeout()) {
#    header("location: chargement.php?erreur_timeout=1");
    $text="timeout";
    writefile("/tmp/timeout",$text);
  }
}

register_shutdown_function(timeouthandler);
*/

$context[id]=intval($id);
$context[tache]=$tache=intval($tache);

if ($htmlfile && $htmlfile!="none") {
  do {
    // verifie que la variable htmlfile n'a pas ete hackee
    if (strpos(realpath($htmlfile),getenv("TMPDIR"))!=0) die("Erreur interne");
    // charge le fichier
    $file=join("",file ($htmlfile));
    // cherche si le fichier est rtf
#    echo substr ($file,0,10);
    if (strpos($file,"{\\rtf")===0) { # fichier RTF
      list($newname,$style)=rtf($htmlfile);
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
      if ($style) { // le fichier est style, on y va
	// cree une tache
	$idtache=make_tache("Import $htmlfile_name",3,$row,$tache);
	header("Location: chkbalisage.php?id=$idtache");
      } else {
	$idtache=make_tache("Import $htmlfile_name",1,$row,$tache);
	header("Location: balisage.php?id=$idtache");
      }
      return;
    } else {
      $context[erreur_fichiernonrtf]=1;
      break;
//      die ("desuet");
//      if (!($newname=html($file))) {
//	$context[erreur_upload]=1;
//	break;
//      }
//      // cree une tache
//	$idtache=make_tache("Import $htmlfile_name",1,array("publication"=>$context[id],"fichier"=>$newname));
//      
//      header("Location: balisage.php?id=$idtache");
    } // fichier HTML
  } while (0); // exceptions
}


include ("$home/calcul-page.php");
calcul_page($context,"chargement");


/*
function html(&$file) { 

  global $home;
# nettoie le fichier des sorties word
  $file=strip_tags(
		     preg_replace (array("/[\1-\11\13\37]/","/\222/", # carateres speciaux
					 "/<p\b[^>]*>\s*(&nbsp;?|&#9;)*\s*<\/p\s*>/i", # enleve les <P> avec nbsp
					 "/(<p\b[^>]*>)\s*(&nbsp;?|&#9;)+/i" # enleve les &nbsp; apres les <P>)
					 ),
				   array("","'","","\\1"), $file),
		     "<I><B><TABLE><TR><TD><TH><TB><P><A><BODY><UL><IL><HEAD><HTML><TITLE>");
    // cherche un nom libre dans /tmp pour le fichier
    $newname=tempnam (getenv("TMPDIR"),"r2r");
    // converti en XML
    if (!($pipe=popen("$home/html2xhtml  >$newname","w")))  return FALSE;

    fputs($pipe,$file);
    pclose($pipe);

    // recupere le fichier
    $file=preg_replace(array( "/^.*<body\b[^>]*>/is", # efface tout ce qui est avant body
			      "/<\/body\b[^>]*>.*$/is", # efface tout ce qui est apres body
			     "/\(!--fichier--\)(.*?)\(!--eof--\)/si", # detecte les label !--fichier--
			     "/\(!--(\w+)--\)(.*?)\(!--\)/s", # detecte les balises XML-Like
			     "/\(!--(\w+)--\)/", # si elles ne sont pas fermee... on les detruits
			     "/\(!--\)/",
			     "/(<p\b[^>]*>)\s*(<r2r:[^>]*>)/i",
			      "/(<\/r2r:[^>]*>)\s*(<\/p>)/i",
			     "/<\/p>/i"),
		       array( "<r2r:article>",
			      "</r2r:article>",
			     "<r2r:fichier/>\\1</r2r:fichier>",
			     "<r2r:\\1>\\2</r2r:\\1>",
			     "",
			     "",
			     "\\2\\1",
			     "\\2\\1",
			      "\\0\n"
			      ),join("",file($newname)));

    include ("$home/func.php");
    if (!writefile($newname,$file))      return FALSE;
    return $newname;
}
*/


function rtf($filename) { 

  global $home;
# nettoie le fichier: simplifie les paragraphes

//  while (1) { $i++; }
  // cherche un nom libre dans /tmp pour le fichier
  do {
    $rand=rand();
    $basename="/tmp/r2r".$rand;
    $newname=$basename.".html";
  } while (file_exists($newname));
  // converti en XML

  system("$home/Ted/Ted --saveTo $filename $newname 2>/dev/null >/dev/null");
  if (!file_exists($newname)) die ("Erreur dans Ted");

  rename ($filename,$basename.".rtf");

#  die ($newname);
#echo "<h1>Maintenance</h1> Revenez plus tard<br>"; 
   if ($GLOBALS[sortieted]) die (htmlentities(join('',file($newname))));

  $search=array(); $rpl=array();
  # construit le tableau de recherche et remplacement
# enleve les accents dans les balises R2R
  array_push($search,"/<\/?r2r:[^>]+>/ie");
  array_push($rpl,"strtr('\\0','йикав','eeeaa')");
# hack un peu sale pour les puces

 array_push($search,"/(<\/?r2r:\w+)_\([^\)]*\)/i",
		"/<td([^>]*)>\s*".chr(0xB7)."/i");
 array_push($rpl,"\\1",
		"<td\\1 align=\"right\">".chr(0xB7));

# resoud salement le probleme des & dans les url. Il faudrait le faire dans Ted
 array_push($search,"/(<a\b[^>]+href\s*=)\"([^\"]+)\"/ie");
 array_push($rpl,"'\\1\"'.strtr('\\2',array('&'=>'&amp;')).'\"'");

  # conversion de balises
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
		      );
  foreach ($translations as $k=>$v) {
    array_push($search,"/<r2r:$k(\b[^>]+)?>/i","/<\/r2r:$k>/i");
    if ($v) {
	array_push($rpl,"<r2r:$v\\1>","</r2r:$v>");
    } else {
	array_push($rpl,"","");
    } 
  }
  # conversion des balises avec publication
  array_push($search,
		     "/<r2r:(heading|titre)_(\d+)(\b[^>]+)?>/i",
		     "/<\/r2r:(heading|titre)_(\d+)>/i");
  array_push($rpl,
		"<r2r:section\\2\\3>",
		"</r2r:section\\2>");
  
# recupere les alignements dans les tableaux......

  
  array_push($search,
	     "/<TD\b([^>]*)>(\s*(?:<[^>]+>)*\s*<DIV\s+ALIGN=\"([^\"]+)\")/is",
	     "/<(\/?)(?:div|p)\b/i", # enleve les attributs des div et p et remplace par des p
	     "/<p\salign=\"(left|justify)\">/i", # enleve les alignements gauche et justify

	     "/<br>/i",   # XML is
	     "/<\/br>/i",	#efface
	     "/&nbsp;/",
	     "/<[^>]+>/e"
	     );

  array_push($rpl,
	     "<td\\1 align=\"\\3\">\\2",
	     "<\\1p",
	     "<p>",
	     "<br/>",
	     " ",
             chr(160),
	     'strtolower("\\0")'
	     );

  $file=traite_multiplelevel(
			     preg_replace ($search,$rpl,
				join("",file($newname))
				)
			     );
#  echo htmlentities($file); exit;

# verifie que le document est bien forme
    include ("$home/checkxml.php");
    if (!checkstring($file)) { echo "fichier: $newname"; echo htmlentities($file);
return FALSE; }

# traite les tableaux pour sortir les styles
    include ("$home/tableau.php");
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
    include_once ("$home/func.php");
    copy_images($file,"img_copy");

  // ecrit le fichier
    if (!writefile($newname,$file))      return FALSE;

    // cherche si le document est tage...

    return array($basename,preg_match("/<r2r:(resume|auteurs|motcles|geographies|periodes|bibliographie|typeart)\b[^>]*>/i",$file));
}

?>
















