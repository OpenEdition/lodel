<?

include_once($home."balises.php");


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
      //      if ($style) { // le fichier est style, on y va
	// cree une tache
	$idtache=make_tache("Import $htmlfile_name",3,$row,$tache);
	header("Location: chkbalisage.php?id=$idtache");

	// c'est la fin de balisage.php, snif! Les gens n'en veulent plus.
	//      } else {
	//$idtache=make_tache("Import $htmlfile_name",1,$row,$tache);
	//header("Location: balisage.php?id=$idtache");
	//}
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



function rtf($filename) { 

  global $home;
# nettoie le fichier: simplifie les paragraphes

  // cherche un nom libre dans /tmp pour le fichier
  do {
    $rand=rand();
    $basename="/tmp/r2r".$rand;
    $newname=$basename.".html";
  } while (file_exists($newname));
  // converti en XML

  system($home."Ted/Ted --saveTo $filename $newname 2>/dev/null >/dev/null");
  if (!file_exists($newname)) die ("Erreur dans Ted");

  rename ($filename,$basename.".rtf");

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


# resoud le probleme des balises intempestives inutiles dans les notes de bas de page
 array_push($search,"/<sup><small>(<[^>]+>)*?<\/small><\/sup>/i");
 array_push($rpl,"");

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
		      "description_auteur"=>"descriptionauteur",
		      "droits_auteur"=>"droitsauteur",
		      "type_document"=>"typedoc",
		      "langue"=>"langues",
			  "titre_illustration"=>"titreillustration",
			  "legende_illustration"=>"legendeillustration",
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
		     "/<r2r:(?:section|heading|titre)_(\d+\b([^>]*))>/i",
		     "/<\/r2r:(?:section|heading|titre)_(\d+)>/i");
  array_push($rpl,
		"<r2r:section\\1>",
		"</r2r:section\\1>");
  
# recupere les alignements dans les tableaux......

  array_push($search,
	     "/<TD\b([^>]*)>(\s*(?:<[^>]+>)*\s*<DIV\s+ALIGN=\"([^\"]+)\")/is",
	     "/<(\/?)(?:div|p)\b/i", # enleve les attributs des div et p et remplace par des p
	     "/<p\salign=\"(left|justify)\">/i", # enleve les alignements gauche et justify

	     "/<br>/i",   # XML is
	     "/<\/br>/i",	#efface
	     "/&nbsp;/",
	     "/<[^>]+>/e",
	     "/(<img\b[^>]+)border=\"?\d+\"?([^>]*>)/i", # efface les border
	     "/(<img\b[^>\/]+)\/?>/i", # met border="0"
	     "/<r2r:article>/"
	     );

  array_push($rpl,
	     "<td\\1 align=\"\\3\">\\2",
	     "<\\1p",
	     "<p>",
	     "<br/>",
	     " ",
             chr(160),
	     'strtolower("\\0")',
	     "\\1\\2",
	     "\\1border=\"0\"\/>",
	     '<r2r:article xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">'
	     );

  $file=traite_multiplelevel(
			     preg_replace ($search,$rpl,
					   join("",file($newname))
					   )
			     );
#  echo htmlentities($file); exit;

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
    if (!writefile($newname,$file))      return FALSE;

    // cherche si le document est tage...

    return array($basename,preg_match("/<r2r:(resume|auteurs|motcles|geographies|periodes|bibliographie|typedoc)\b[^>]*>/i",$file));
}


?>
