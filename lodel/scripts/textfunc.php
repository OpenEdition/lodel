<?

# fonction largement reprises de SPIP



#function cleanurl ($texte)
#
#{
#  $texte=strtr(strtolower($texte),
#		"ÈÉÊËèéêëÀÁÂÃÄÅÆàáâãäåæÌÍÎÏìíîïÒÓÔÕÖØðòóôõöøÙÚÛÜùúûüÇçýÿÝÑñ.,?!' ",
#		"eeeeeeeeaaaaaaaaaaaaaaiiiiiiiioooooooooooouuuuuuuuccyyynn-------");
#  $texte=preg_replace (array("/[\200-\377]/","/ß/","/^-/","/--+/"),
#			array("","ss","","-"),$texte);
#
#  return urlencode($texte);
#}
#

function pluriel($texte)

{ return intval($texte)>1 ? "s" : ""; }


function lettrine(&$texte)

{ 
  if (preg_match("/^\s*(?:<[^>]+>)*\s*([\w\"])/",$texte,$result)) return $result[1];
 return "";
} 

function avantlettrine(&$texte)

{ 
  if (preg_match("/^(\s*(?:<[^>]+>)*\s*)[\w\"]/",$texte,$result)) return $result[1];
 return "";
} 


function apreslettrine (&$texte)

{ return preg_replace("/^\s*(?:<[^>]+>)*\s*[\w\"]/","",$texte); }


function multiline($width,&$texte)

{ return wordwrap($texte,$width); }

function nbsp($texte) 

{ return $texte ? $texte : "&nbsp;"; }


function majuscules($texte) {
	$suite = htmlentities($texte);
	$suite = ereg_replace('&amp;', '&', $suite);
	$suite = ereg_replace('&lt;', '<', $suite); 
	$suite = ereg_replace('&gt;', '>', $suite); 
	$texte = '';
	if (ereg('^(.*)&([A-Za-z])([a-zA-Z]*);(.*)$', $suite, $regs)) {     
		$texte .= majuscules($regs[1]);
		$suite = $regs[4];
		$carspe = $regs[2];
		$accent = $regs[3];
		if (ereg('^(acute|grave|circ|uml|cedil|slash|caron|ring|tilde|elig)$', $accent))
			$carspe = strtoupper($carspe); 
		if ($accent == 'elig') $accent = 'Elig';
		$texte .= '&'.$carspe.$accent.';';
	}
	$texte .= strtoupper($suite);
	return $texte;
}

function justifier($letexte) {
	$letexte = eregi_replace("^<p([[:space:]][^>]*)?".">", "", trim($letexte));
	if ($letexte)
		$letexte = "<p align='justify'>".eregi_replace("<p([[:space:]][^>]*)?".">", "<p\\1 align='justify'>", $letexte);
	return $letexte;
}

function aligner_droite($letexte) {
	$letexte = eregi_replace("^<p([[:space:]][^>]*)?".">", "", trim($letexte));
	if ($letexte)
		$letexte = "<p align='right'>".eregi_replace("<p([[:space:]][^>]*)?".">", "<p\\1 align='right'>",$letexte)."</p>";
	return $letexte;
}

function aligner_gauche($letexte) {
	$letexte = eregi_replace("^<p([[:space:]][^>]*)?".">", "", trim($letexte));
	if ($letexte)
		$letexte = "<p align='left'>".eregi_replace("<p([[:space:]][^>]*)?".">", "<p\\1 align='left'>",$letexte);
	return $letexte;
}

function centrer($letexte) {
	$letexte = eregi_replace("^<p([[:space:]][^>]*)?".">", "", trim($letexte));
	if ($letexte)
		$letexte = "<p align='center'>".eregi_replace("<p([[:space:]][^>]*)?".">", "<p\\1 align='center'>",$letexte);
	return $letexte;
}

function textebrut($letexte) {
#	$letexte = ereg_replace("[\n\r]+", " ", $letexte);
#	$letexte = ereg_replace("(<[^>]+>|&nbsp;| )+", " ", $letexte);
	$letexte = preg_replace("/(<[^>]+>|&nbsp;|[\n\r\t])+/", " ", $letexte);
	return $letexte;
}

function couper($long,$texte) {
	$texte2 = substr($texte, 0, $long * 2); /* heuristique pour prendre seulement le necessaire */
	if (strlen($texte2) < strlen($texte)) $plus_petit = true;
	$texte = ereg_replace("\[([^\[]*)->([^]]*)\]","\\1", $texte2);

	// supprimer les notes
	$texte = ereg_replace("\[\[([^]]|\][^]])*\]\]", "", $texte);

	$texte2 = substr($texte." ", 0, $long);
	$texte2 = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $texte2);
	if ((strlen($texte2) + 3) < strlen($texte)) $plus_petit = true;
	if ($plus_petit) $texte2 .= ' (...)';
	return $texte2;
}

function couperpara($long,$texte) {

	$pos=-1;
	do {
		$pos=strpos($texte,"</p>",$pos+1);
		$long--;
	} while ($pos!==FALSE && $long>0);

	return $pos>0 ? substr($texte,0,$pos+4) : $texte;
}




function traite_raccourcis ($letexte)

{
  $puce="<IMG SRC=\"Images/smallpuce.gif\">";
  // Harmoniser les retours chariot
  $letexte = ereg_replace ("\r\n?", "\n",$letexte);

  // Corriger HTML
  $letexte = eregi_replace("</?p>","\n\n\n",$letexte);

  //
  // Raccourcis liens
  //
  $regexp = "\[([^][]*)->([^]]*)\]";
  $texte_a_voir = $letexte;
  $texte_vu = '';
  while (ereg($regexp, $texte_a_voir, $regs)){
    $lien_texte = $regs[1];
    $lien_url = trim($regs[2]);
    $compt_liens++;
    $lien_interne = false;

    $insert = "<a href=\"$lien_url\">".$lien_texte."</a>";
    $zetexte = split($regexp,$texte_a_voir,2);
    $texte_vu .= $zetexte[0].$insert;
    $texte_a_voir = $zetexte[1];
  }
  $letexte = $texte_vu.$texte_a_voir; // typo de la queue du texte

  //
  // Ensemble de remplacements implementant le systeme de mise
  // en forme (paragraphes, raccourcis...)
  //
  $letexte = trim($letexte);
  $cherche1 = array(
		    /* 1 */		"/\n(----+|____+)/",
		    /* 2 */		"/^-/",
		    /* 3 */		"/\n-/",
		    /* 4*/		"/(( *)\n){2,}/",
		    /* 5 */		"/\{\{\{/",
		    /* 6 */		"/\}\}\}/",
		    /* 7 */		"/\{\{/",
		    /* 8 */		"/\}\}/",
		    /* 9 */		"/\{/",
		    /* 10 */	"/\}/",
		    /* 11 */	"/(<br>){2,}/",
		    /* 12 */	"/<p>([\n]*)(<br>)+/",
		    /* 13 */	"/<p>/"
					);
  $remplace1 = array(
		     /* 1 */ 	"\n<hr>\n",
		     /* 2 */ 	"$puce ",
		     /* 3 */ 	"\n<br>$puce ",
		     /* 4 */		"\n<p>",
		     /* 5 */ 	"$debut_intertitre",
		     /* 6 */ 	"$fin_intertitre",
		     /* 7 */ 	"<b>",
		     /* 8 */ 	"</b>",
		     /* 9 */ 	"<i>",
		     /* 10 */ 	"</i>",
		     /* 11 */ 	"\n<p>",
		     /* 12 */ 	"\n<p>",
		     /* 13 */	"<p>"
				);
  $letexte = preg_replace($cherche1, $remplace1, $letexte);
  return $letexte;
}


function propre($letexte) {
	return traite_raccourcis(trim($letexte));
}


function humandate($s)

{ # verifie que la date est sous forme mysql
 if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)$/",$s,$result)) {
   if ($result[1]>9000) return "jamais";
   if ($result[1]==0) return "";
   $mois[1]="janvier";
   $mois[2]="février";
   $mois[3]="mars";
   $mois[4]="avril";
   $mois[5]="mai";
   $mois[6]="juin";
   $mois[7]="juillet";
   $mois[8]="août";
   $mois[9]="septembre";
   $mois[10]="octobre";
   $mois[11]="novembre";
   $mois[12]="décembre";
   return intval($result[3])." ".$mois[intval($result[2])]." ".intval($result[1]);
 } else {
   return $s;
 }
}

function toc(&$text)

{
	preg_match_all("/<(H\d)>.*?<\/\\1>/i",$text,$result,PREG_PATTERN_ORDER);
	foreach ($result[0] as $titre) {
		$i++;
		$toc.="<A HREF=\"#to$i\" NAME=\"from$i\">$titre</A>\n";
	}
	return $toc;
}

function tocable(&$text)

{
	return preg_replace("/<(H\d)>.*?<\/\\1>/ie","'<A HREF=\"#from'.(++\$i).'\" NAME=\"to'.\$i.'\">\\0</A>'",$text);
}


function makeurl ($rep)
{
if ($GLOBALS[revueagauche]) return "http://$rep.revues.org";
return "/$rep";
}


function vignette($width,$text)

{
  if (!preg_match("/^(.*)\.([^\.]+)$/",$text,$result)) return;
  $vignettefile=$result[1]."-small$width.".$result[2];
  if (file_exists($vignettefile) && filemtime($vignettefile)>=filemtime($text)) return $vignettefile;
  // creer la vignette (de largeur width ou de hauteur width en fonction de la forme
  include_once($home."images.php");
  resize_image($width,$text,$vignettefile,"+");
  return $vignettefile;
}

# renvoie les attributs pour une image
function sizeattributs($text)

{
  $result=getImageSize($text);
  return $result[3];
}

function paranumber (&$texte)

{
  return preg_replace("/(<p\b[^>]*>\s*)+/ie",'"<SPAN CLASS=\"paranum\">".(++$count)."</SPAN>"',$texte);

    /*
  $p=strpos($texte,"<p>");
  while ($p!==FALSE) {
    $count++;
    
    $p=strpos($texte,"<p>",$p);
  }
    */
}

// Fonction permettant de supprimer les appels de notes d'un texte.
function removefootnotes(&$text)
{
        return preg_replace("/<a\b[^>]+>\s*<sup>\s*<small>.*?<\/small>\s*<\/sup>\s*<\/a>/is","",$text);
}

// Fonction qui dit si une date est vide ou non
function isadate(&$text)
{
  return $text!="0000-00-00";
}

// Fonction qui remplace les guillemets d'un texte par leur nom d'entité (&quot;)
function replacequotationmark(&$text)
{
        return str_replace("\"","&quot;",$text);
}

// fonction utiliser pour les options.
function yes ($texte)

{ return $texte ? "checked" : "";}

function no ($texte)

{ return !$texte ? "checked" : "";}

function eq($str,$texte)

{ return $texte==$str ? "checked" : ""; }

?>
