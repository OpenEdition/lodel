<?
include_once("$home/extrainfofunc.php");
//
// bloc principale d'extrainfo
// ce bloc peut etre appele par plusieurs scripts.

if ($edit || $plusauteurs) {
  $balisefile=$filename.".balise";
  if (file_exists($balisefile) && filemtime($balisefile)>filemtime($filename.".html")) {
    $text=join("",file ($balisefile));
  } else {
    $text=join("",file ($filename.".html"));
  }
#die ("$text");
  do {
    extract_post();
    // suppression des slashes
    foreach($context as $key=>$val) {
      $context[$key]=stripslashes($val);
    }

    // verifie que le titre est present
    if (!$context[titre]) $err=$context[erreur_titre]=1;
    if ($context[datepubli]) {
      include ("$home/date.php");
      $row[datepubli]=mysqldate($context[datepubli]);
      if (!$row[datepubli]) { $context[erreur_datepubli]=$err=1; }
      // fin de la validation
    }

    // efface les groupes
    $text=preg_replace ("/<r2r:(grmotcle|grperiode|grgeographie|grtitre|meta|grauteur)\b[^>]*>(.*?)<\/r2r:\\1>/si", // efface les champs auteurs et auteur
			"",$text);

    // ajoute les groupes a la fin
    $text=preg_replace("/<\/r2r:article>/i",
		       gr_auteur($context,$plusauteurs).
		       gr_motcle($context,$motcles).
		       gr_indexh($context,$periodes,"periode").
		       gr_indexh($context,$geographies,"geographie").
		       gr_titre($context).
		       gr_meta($context)."\\0",
		       $text);
    // change la langue du texte
    $lang=$context[lang1];
    if ($context[lang2]) $lang.=" ".$context[lang2];
    if ($context[lang3]) $lang.=" ".$context[lang3];

    $text=preg_replace(array("/(<r2r:texte\b[^>]+)\blang\s*=\s*\"[^\"]*\"/i","/(<r2r:texte\b[^>]*?)\s*>/i"),
		       array("\\1",$lang ? "\\1 lang=\"$lang\">" : "\\1>"),$text);

    if ($err || $plusauteurs) {
      writefile ($balisefile,$text);
      break;
    }

    ///////////// enregistre /////////////
    if ($row[iddocument]) { # efface d'abord
      include_once("$home/managedb.php");
      // recupere les metas et le status
      $result=mysql_query("SELECT meta,status from documents WHERE id='$row[iddocument]'") or die (mysql_error());
      list($row[meta],$status)=mysql_fetch_row($result);
      if (!$row[status]) $status=$row[status]; // recupere le status si necessaire
      supprime_document($row[iddocument]);
    } else { # Il n'existe pas, alors on calcule la date
      $context[duree]=intval($context[duree]);
      $time=localtime();
      if ($context[dateselect]=="jours") $time[3]+=$context[duree];
      if ($context[dateselect]=="mois") $time[4]+=$context[duree];
      if ($context[dateselect]=="année") $time[5]+=$context[duree];
      $row[datepubli]=date("Y-m-d",mktime(0,0,0,$time[4]+1,$time[3],$time[5]));
    }
    // enregistre dans la base
    //    echo htmlentities($text); return;
    include ("$home/dbxml.php");
    $iddocument=enregistre($row,$text);

    // change le nom des images
    function img_rename($imgfile,$ext,$count) {
	global $iddocument;

	$newimgfile="docannexe/r2r-img-$iddocument-$count.$ext";
	if ($imgfile!=$newimgfile) {
	  rename ($imgfile,"../../$newimgfile") or die ("impossible de renomer l'image $imgfile en $newimgfile");
	  chmod ("../../$newimgfile",0644) or die ("impossible de chmod'er le ../../$newimagefile");
	}
	return $newimgfile;
    }
    copy_images($text,"img_rename");

    // copie le fichier balise en lieu sur !
    if (!writefile("../txt/r2r-$iddocument.xml",$text)) die ("Erreur lors de l' ecriture du fichier. Contactez l'administrateur");
    // et le rtf s'il existe
    $rtfname="$filename.rtf";
    if (file_exists($rtfname)) { 
      $dest="../rtf/r2r-$iddocument.rtf";
      copy ($rtfname,$dest);
      chmod($dest,0644) or die ("impossible de chmod'er $dest");
    }
    // efface le fichier balise
    if (file_exists($balisefile)) unlink($balisefile);

    //
    // termine en redirigeant correctement
    // 
    if ($sommaireimport) {
      return;
    } elseif ($ajouterdocannexe) {
      $redirect="docannexe.php?iddocument=$iddocument";
    } elseif ($visualiserdocument) {
      $redirect="../../document.html?id=$iddocument";
    } else {
      $redirect="";
    }
    // clot la tache et renvoie sur au bon endroit
    include ("abandon.php");
    return;
  } while (0); // exception
} // edit
else {
  $text=join("",file ($filename.".html"));
  auteurs2auteur($text);
  if (!$context[option_pasdeperiode]) tags2tag("periode",$text);
  if (!$context[option_pasdemotcle]) tags2tag("motcle",$text);
  if (!$context[option_pasdegeographie]) tags2tag("geographie",$text);

  // extrait les balises et met les dans le context
  $lbalises=array("titre","soustitre","surtitre","typedoc");

  foreach ($lbalises as $b) {
    if (preg_match ("/<r2r:$b>\s*(.*?)\s*<\/r2r:$b>/si",$text,$result)) {
      $context[$b]=strip_tags($result[1],"<I><B><U>");
      $text=str_replace($result[0],"",$text);
    }
  }
  // extrait les langues
  if (preg_match("/<r2r:texte\b[^>]+\blang\s*=\s*\"([^\"]+)\"/i",$text,$result)) {
    list($context[lang1],$context[lang2],$context[lang3])=explode(" ",$result[1]);
  }
  // transforme les balises resume
  $srch=array(); $rpl=array();
  foreach ($langresume as $bal=>$lang) {
    array_push($srch,"/<r2r:$bal>/i","/<\/r2r:$bal>/i");
    array_push($rpl,"<r2r:resume lang=\"$lang\">","</r2r:resume>");
  }
  $text='<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>
<!DOCTYPE article SYSTEM "r2r-xhtml-1.dtd">
'.preg_replace($srch,$rpl,$text);
      
  writefile ($filename.".balise",$text);
  if ($row[iddocument]) { # le document existe
# on recupere la date de publication du texte
    $result=mysql_query("SELECT datepubli from documents WHERE id='$row[iddocument]'") or die (mysql_error());
    list($context[datepubli])=mysql_fetch_row($result);
  }
}

$balises_sstag=array("typedoc");
foreach ($balises_sstag as $b) {
  $context[$b]=strip_tags($context[$b]);
}

posttraitement($context);

?>
