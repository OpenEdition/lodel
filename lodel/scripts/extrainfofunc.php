<?


// regexp de reconnaissance des prefix de nom de personne

$prefixregexp="Pr\.|Dr\.";

include_once($home."langues.php");
include_once($home."connect.php");


//////////////////////////////////////////////////////////////////////////////

// assure le traitement du fichier lors de l'arrive dans extrainfo
function ei_pretraitement($filename,$row,&$context,&$text)

{
  global $langresume,$home;

  $text=join("",file ($filename.".html"));
  traitepersonnes($text);
  traiteentrees($text);

  //////////  // debut de gestion du bloc titre et meta
  // ceci pourrait etre dans une fonction plutot comme tags2tag, 
  // meme si ca ne permet pas plus de genericite.

  // extrait les balises et met les dans le context
  $lbalises=array("titre","soustitre","surtitre","typedoc");

  foreach ($lbalises as $b) {
    if (preg_match ("/<r2r:$b>\s*(.*?)\s*<\/r2r:$b>/si",$text,$result)) {
      $context[$b]=strip_tags($result[1],"<I><B><U>");
      $text=str_replace($result[0],"",$text);
    }
  }
  $text=preg_replace("/<\/r2r:article>/i",gr_titre($context).gr_meta($context)."\\0",$text);
  ////////// /// fin de gestion des bloc titre et meta

  // extrait les langues
  if (preg_match("/<r2r:texte\b[^>]+\blang\s*=\s*\"([^\"]+)\"/i",$text,$result)) {
    list($context[lang1],$context[lang2],$context[lang3])=explode(" ",$result[1]);
  } elseif (preg_match("/<r2r:langues>(.*?)<\/r2r:langues>/s",$text,$result)) { // cherche la balises langues
    list($context[lang1],$context[lang2],$context[lang3])=preg_split("/\s*,\s*/",strtolower(strip_tags($result[1])),3);
    $text=str_replace($result[0],"",$text);
  } elseif (preg_match("/<r2r:texte\b/i",$text)) { // langue par default, le francais. Il faudra aller chercher la langue par defaut dans la table de la revue...
    $context[lang1]="fr";

  }
  // transforme les balises resume
  $srch=array(); $rpl=array();
  foreach ($langresume as $bal=>$lang) {
    array_push($srch,"/<r2r:$bal>/i","/<\/r2r:$bal>/i");
    array_push($rpl,"<r2r:resume lang=\"$lang\">","</r2r:resume>");
  }

  // supprime le doctype
  array_push($srch,"/<!DOCTYPE\b[^>]*>/i");
  array_push($rpl,"");
  
  // supprime le < ?xml
  array_push($srch,"/<\?xml\b.*\?\>/i");
  array_push($rpl,"");

  $text=preg_replace($srch,$rpl,$text);

  // ajoute le doctype si necessaire

$text='<'.'?xml version="1.0" encoding="ISO-8859-1" ?'.'>
<!DOCTYPE article SYSTEM "r2r-xhtml-1.dtd">
'.$text;

  if (!writefile ($filename.".balise",$text)) die ("erreur d'ecriture du fichier $filename.balise");
  if ($row[iddocument]) { # le document existe
# on recupere la date de publication du texte
    $result=mysql_query("SELECT datepubli FROM $GLOBALS[tableprefix]documents WHERE id='$row[iddocument]'") or die (mysql_error());
    list($context[datepubli])=mysql_fetch_row($result);
  }
}


//
// verifie les entrees et enregistre dans la base sauf s'il y a erreur
//  ou si on souhaite ajouter une personne
//

function ei_edition($filename,$row,&$context,&$text,&$entrees,&$autresentrees,&$plus)

{
  global $home;

  $balisefilename=$filename.".balise";
  if (file_exists($balisefilename) && filemtime($balisefilename)>=filemtime($filename.".html")) {
    $text=join("",file($balisefilename));
  } else {
    $text=join("",file($filename.".html"));
  }

  extract_post();
  // suppression des slashes
  $time=microtime();
  mystripslashes($context);
  mystripslashes($autresentrees);  
  mystripslashes($entrees);

  // verifie que le titre est present
  if (!$context[titre]) $err=$context[erreur_titre]=1;
  if ($context[datepubli]) {
    include ($home."date.php");
    $row[datepubli]=mysqldate($context[datepubli]);
    if (!$row[datepubli]) { $context[erreur_datepubli]=$err=1; }
    // fin de la validation
  }
  //
  // recherche les differents type d'entrees
  //
  include_once($home."connect.php");
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tableprefix]typeentrees WHERE status>0") or die (mysql_error());
  $groupeentree="<r2r:grentree>";
  while ($row=mysql_fetch_assoc($result)) {
    $groupeentree.=gr_entrees($context,$entrees[$row[id]],$autresentrees[$row[id]],$row[nom]);
  }
  $groupeentree.="</r2r:grentree>";
  //
  // recherche les differents type de personnes
  //
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tableprefix]typepersonnes WHERE status>0") or die (mysql_error());
  $groupepersonne="<r2r:grpersonne>";
  while ($row=mysql_fetch_assoc($result)) {
    $groupepersonne.=gr_personne($context,$row[id],$plus[$row[id]],$row[nom]);
  }
  $groupepersonne.="</r2r:grpersonne>";

  //
  // efface les groupes
  //
  $text=preg_replace ("/<r2r:(grentree|grtitre|meta|grpersonne)\b[^>]*>(.*?)<\/r2r:\\1>/si",
		      "",$text);
  //
  // ajoute les groupes a la fin
  //
  $text=preg_replace("/<\/r2r:article>/i",
		     $groupepersonne.
		     $groupeentree.
		     gr_titre($context).
		     gr_meta($context)."\\0",
		     $text);
  // change la langue du texte
  $lang=$context[lang1];
  if (preg_match("/<r2r:texte\b/i",$text) && !$lang) {
    $context[erreur_textesanslangue]=$err=1; 
  } else {
    if ($context[lang2]) $lang.=" ".$context[lang2];
    if ($context[lang3]) $lang.=" ".$context[lang3];

    $text=preg_replace(array("/(<r2r:texte\b[^>]+)\blang\s*=\s*\"[^\"]*\"/i","/(<r2r:texte\b[^>]*?)\s*>/i"),
		       array("\\1",$lang ? "\\1 lang=\"$lang\">" : "\\1>"),$text);
  }

  if ($err || $context[plus]) {
    writefile ($balisefilename,$text);
    return FALSE;
  }
  return TRUE;
}

function ei_enregistrement($filename,$row,&$context,&$text)

{
  global $home;
  //
  // enregistre
  //
  if ($row[iddocument]) { # efface d'abord
    include_once($home."managedb.php");
    // recupere les metas et le status
    $result=mysql_query("SELECT meta,status FROM $GLOBALS[tableprefix]documents WHERE id='$row[iddocument]'") or die (mysql_error());
    list($row[meta],$status)=mysql_fetch_row($result);
    if (!$row[statusdocument]) $row[statusdocument]=$status; // recupere le status si necessaire
    supprime_document($row[iddocument],TRUE,FALSE);
  } else { # Il n'existe pas, alors on calcule la date
    $context[duree]=intval($context[duree]);
    $time=localtime();
    if ($context[dateselect]=="jours") $time[3]+=$context[duree];
    if ($context[dateselect]=="mois") $time[4]+=$context[duree];
    if ($context[dateselect]=="année") $time[5]+=$context[duree];
    $row[datepubli]=date("Y-m-d",mktime(0,0,0,$time[4]+1,$time[3],$time[5]));
  }
  // enregistre dans la base
  include_once ($home."dbxml.php");
  $iddocument=enregistre($row,$text);

  // change le nom des images
  if (!function_exists("img_rename")) {
    function img_rename($imgfile,$ext,$count) {
      global $iddocument;
      
      $newimgfile="docannexe/r2r-img-$iddocument-$count.$ext";
      if ($imgfile!=$newimgfile) {
	rename ($imgfile,"../../$newimgfile") or die ("impossible de renomer l'image $imgfile en $newimgfile");
	chmod ("../../$newimgfile",0644) or die ("impossible de chmod'er le ../../$newimagefile");
      }
      return $newimgfile;
    }
  }
  copy_images($text,"img_rename");

  // copie le fichier balise en lieu sur !
  if (!writefile("../txt/r2r-$iddocument.xml",$text)) die ("Erreur lors de l' ecriture du fichier. Contactez l'administrateur: ../txt/r2r-$iddocument.xml");
  // et le rtf s'il existe
  $rtfname="$filename.rtf";
  if (file_exists($rtfname)) { 
    $dest="../rtf/r2r-$iddocument.rtf";
    copy ($rtfname,$dest);
    chmod($dest,0644) or die ("impossible de chmod'er $dest");
  }
  // efface le fichier balise
  if (file_exists($balisefilename)) unlink($balisefilename);

  return $iddocument; // ok on a finit correctement
}


  ///////////////////////////////////////////////////////////
//
// fonctions de traitement
// specifiques a extrainfo
//


function gr_personne(&$context,$idtype,$plus,$type)

{
    $i=1;
    while ($context[nomfamille][$idtype][$i] || $context[prenom][$idtype][$i]) {
      $rpl.="<r2r:personne type=\"$type\" ordre=\"$i\">".
	// nompersonne
	"<r2r:nompersonne>\n".
	writetag("prefix",$context[prefix][$idtype][$i]).
	writetag("nomfamille",$context[nomfamille][$idtype][$i]).
	writetag("prenom",$context[prenom][$idtype][$i]).
	"</r2r:nompersonne>\n".
	writetag("fonction",$context[fonction][$idtype][$i]).
	writetag("affiliation",$context[affiliation][$idtype][$i]).
	writetag("courriel",$context[courriel][$idtype][$i]).
	writetag("description",$context[description][$idtype][$i]).
	"</r2r:personne>\n";
      $i++;
    }
    if ($plus) $rpl.="<r2r:personne type=\"$type\"></r2r:personne>"; // hack un peu sale !
    return $rpl;
}

#function gr_motcle(&$context,&$motcles)
#
#{
#  // traite les motcles
#  if (!$context[option_motclefige]) $motcles=array_merge($motcles,preg_split ("/\s*[,;]\s*/",$context[resmotcles]));
#  $rpl="<r2r:grmotcle>";
#  if ($motcles) {
#    foreach ($motcles as $p) {
#      $rpl.=writetag("motcle",strip_tags(rmscript(trim($p))));
#    }
#  }
#  $rpl.="</r2r:grmotcle>";
#  return $rpl;
#}

function gr_entrees(&$context,$entrees,$autresentrees,$type)

{
  $bal=strtolower($bal);
  // traite les entreess
  $entrees=array_merge($entrees,preg_split ("/\s*[,;]\s*/",strip_tags(rmscript($autresentrees))));

  if ($entrees) {
    foreach ($entrees as $p) {
      $rpl.="<r2r:entree type=\"$type\">".trim($p)."</r2r:entree>";
    }
  }
  return $rpl;
}


function gr_titre(&$context)

{
    return "<r2r:grtitre>".
      writetag("surtitre",strip_tags(rmscript(trim($context[surtitre])),"<I>")).
      writetag("titre",strip_tags(rmscript(trim($context[titre])),"<I>")).
      writetag("soustitre",strip_tags(rmscript(trim($context[soustitre])),"<I>")).
      "</r2r:grtitre>\n";
}

function gr_meta(&$context)

{
  return "<r2r:meta><r2r:infoarticle>".
    writetag("typedoc",strip_tags(rmscript(trim($context[typedoc])))).
    "</r2r:infoarticle></r2r:meta>\n";
}

function writetag($name,$content,$attr="")

{
  if (!$content) return "";
  if ($attr) return "<r2r:$name $attr>$content</r2r:$name>\n";
  return "<r2r:$name>$content</r2r:$name>\n";
}

###################### functions ##################

function makeselecttypedoc()

{
  global $context;

  $result=mysql_query("SELECT nom FROM typedocs WHERE status>0") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[typedoc]==$row[nom] ? " selected" : "";
    echo "<option value=\"$row[nom]\"$selected>$row[nom]</option>\n";
  }
}


function makeselectentrees (&$context)
     // le context doit contenir les informations sur le type a traiter
{
  global $text;
  // recupere les styles
  $entreere=preg_quote($context[nom]);
  preg_match_all("/<r2r:entree\s[^>]*?type=\"(?:$entreere)\"[^>]*>(.*?)<\/r2r:entree>/s",$text,$entrees,PREG_PATTERN_ORDER);

#echo "entrees:";  print_r($entrees);

  $entreestrouvees=array();
  makeselectentrees_rec(0,"",$entrees,$context,&$entreestrouvees);
#  echo "la:";
#  print_r($entrees[1]);
#  echo "la2:";
#  print_r($entreestrouvees);
  $context[autresentrees]=join(", ",array_diff($entrees[1],$entreestrouvees));
#  echo "autresnetrees  $context[autresentrees]";
}

function makeselectentrees_rec($parent,$rep,$entrees,&$context,&$entreestrouvees)

{
  $result=mysql_query("SELECT id, abrev, nom FROM $GLOBALS[tableprefix]entrees WHERE status>=-1 AND parent='$parent' AND idtype='$context[id]' ORDER BY $context[tri]") or die (mysql_error());
#  print_r($entrees[1]);

  while ($row=mysql_fetch_assoc($result)) {
    $selected=(in_array($row[abrev],$entrees[1]) || in_array($row[nom],$entrees[1])) ? " selected" : "";
   if ($selected) array_push($entreestrouvees,$row[nom],$row[abrev]);
   $value=$context[useabrev] ? $row[abrev] : $row[nom];
    echo "<option value=\"$value\"$selected>$rep$row[nom]</option>\n";
    makeselectentrees_rec($row[id],$rep.$row[nom]."/",$entrees,$context,&$entreestrouvees);
  }
}


#function makeselectmotcles()
#
#{
#  global $context,$text;
#
#  if (!$context[option_motclefige]) $critere="type=".TYPE_MOTCLE." OR";
#  $result=mysql_query("SELECT mot FROM indexls WHERE status>=-1 AND ($critere type=".TYPE_MOTCLE_PERMANENT.") GROUP BY mot ORDER BY mot") or die (mysql_error());
#
#  # extrait les motcles du texte
#  preg_match_all("/<r2r:motcle\b[^>]*>(.*?)<\/r2r:motcle\s*>/is",$text,$motcles,PREG_PATTERN_ORDER);
#
#  $motclestrouves=array();
#
#  while ($row=mysql_fetch_assoc($result)) {
#    $selected=in_array($row[mot],$motcles[1]) ? " selected" : "";
#    if ($selected) array_push($motclestrouves,$row[mot]);
#    echo "<option value=\"$row[mot]\"$selected>$row[mot]</option>\n";
#  }
##  print_r($motcles[1]);
##  print_r($motclestrouves);
##  print_r(array_diff($motcles[1],$motclestrouves));
#  if (!$context[option_motclefige]) $GLOBALS[context][autresmotcles]=join(", ",array_diff($motcles[1],$motclestrouves));
#}



function makeselectdate() {
  global $context;

  foreach (array("maintenant",
		 "jours",
		 "mois",
		 "années") as $date) {
    $selected=$context[dateselect]==$date ? "selected" : "";
    echo "<option value=\"$date\"$selected>$date</option>\n";
  }
}

$balisespersonnes=array("prefix","nomfamille","prenom","description","courriel","affiliation","fonction");


function boucle_personnes(&$context,$funcname)

{
  global $balisespersonnes;

  $balisesre=join("|",$balisespersonnes);
  // le context doit contenir le nom du type a traiter
  global $text;

  // supprime les entrees existantes dans le context
  $localcontext=$context;
  foreach($balisespersonnes as $b) unset($localcontext[$b]);

  $personnere=preg_quote($context[nom]);
  preg_match_all("/<r2r:personne\b[^>]*?type=\"$personnere\"[^>]*>(.*?)<\/r2r:personne\s*>/is",$text,$results,PREG_SET_ORDER);
  foreach ($results as $personne) {
    preg_match_all("/<r2r:($balisesre)\b[^>]*>(.*?)<\/r2r:\\1\s*>/is",$personne[1],$results2,PREG_SET_ORDER);
    $ind++;
    $localcontext2=$localcontext;
    $localcontext2[ind]=$ind;
    if ($results2) {
      foreach ($results2 as $result2) {
	$localcontext2[$result2[1]]=
	  trim(htmlspecialchars(stripslashes($result2[2]))); 
      }
    }
    call_user_func("code_boucle_$funcname",$localcontext2);
  }
}

//////////////////////
//
//  function de transformation des styles en balises XML LODEL
//

function traitepersonnes (&$text)

{
  $groupe="<r2r:grpersonne>";

  $oldtags=array(); // pour la suppression dans le texte

  $result1=mysql_query("SELECT style,nom FROM $GLOBALS[tableprefix]typepersonnes WHERE status>0 ORDER BY ordre") or die (mysql_error());
  while ($typepersonne=mysql_fetch_assoc($result1)) {
    $style=strtolower($typepersonne[style]);
    // accouple les balises personnes et description
    $text=preg_replace ("/(<\/r2r:$style>)\s*(<r2r:description>.*?<\/r2r:description>)/s","\\2\\1",$text);
    // cherche toutes les balises de personnes
    preg_match_all ("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$results,PREG_SET_ORDER);

    $i=1;

    while ($result=array_shift($results)) { // parcours les resultats.
      // cherche s'il y a un bloc description
      if (preg_match("/^(.*?)(<r2r:description>.*?<\/r2r:description>)/si",$result[1],$result2)) { // il y a un bloc description, donc on a une description pour le dernier personne.
	$val=trim($result2[1]);
	// remplace descriptionauteur en description. 
#### a supprimer
#      $descrauteur=preg_replace("/(<\/?r2r:description)auteur>/i","\\1>",$result2[2]);
	$descrpersonne=$result2[2];
	
      } else { // pas description des personnes
	$val=trim($result[1]);
	$descrpersonne="";
    }
#    echo htmlentities($descrpersonne)."<br><br>\n\n";
      $personnes=preg_split ("/\s*[,;]\s*/",strip_tags($val,"<r2rc:prenom><r2rc:prefix><r2rc:nom>"));

      while (($personne=array_shift($personnes))) {

	list ($prefix,$prenom,$nom)=decodepersonne($personne);

	$groupe.="<r2r:personne type=\"$typepersonne[nom]\" ordre=\"$i\"><r2r:nompersonne><r2r:nomfamille>$nom</r2r:nomfamille><r2r:prenom>$prenom</r2r:prenom>$prefix</r2r:nompersonne>";
	// est-ce qu'on a une description et est-ce qu'elle est pour cet personne ?
	if ($descrpersonne && !$personnes)  { // oui, c'est le dernier personne de cette liste, s'il y a un bloc description, alors c'est pour lui !
	  // on recupere les balises du champ description
	  $balises=array("fonction","affiliation","courriel");
	  foreach ($balises as $balise) {
	    if (preg_match("/<r2rc:$balise>(.*?)<\/r2rc:$balise>/s",$descrpersonne,$result2)) {
	      $groupe.=writetag($balise,trim($result2[1]));
	    }
	  } // foreach
	  
	  // on efface tous les styles de caracteres
	  $groupe.=preg_replace("/<\/?r2rc:[^>]+>/","",$descrpersonne);
	} // ok, on a traite la description
	$groupe.="</r2r:personne>";
	$i++;
      }
      array_push($oldtags,$result[0]);
    } // parcourt les resultats
  } // type de personne
  $groupe.="</r2r:grpersonne>\n";

  $text=str_replace($oldtags,array($groupe),$text); // remplace le premier tag par $groupe et les autres par rien
}


function decodepersonne($personne) 

{
  // on regarde s'il y a un prefix
  // d'abord on cherche s'il y a un style de caractere, sinon, on cherche les prefix classiques definis dans la variables prefixregexp.
  if (preg_match_all("/<r2rc:prefix>(.*?)<\/r2rc:prefix>/",$personne,$results,PREG_SET_ORDER)) {
    $prefix="<r2r:prefix>";
    foreach($results as $result) {
      $prefix.=$result[1];
      $personne=str_replace($result[0],"",$personne); //nettoie le champ personne
    }
    $prefix.="</r2r:prefix>";
  } elseif (preg_match("/^\s*($GLOBALS[prefixregexp])\s/",$personne,$result2)) {
    $prefix="<r2r:prefix>$result2[1]</r2r:prefix>";
    $personne=str_replace($result2[0],"",$personne); // a partir de php 4.3.0 il faudra utiliser OFFSET_CAPTURE.
  } else {
    $prefix="";
  }
  // ok on le prefix


  // on cherche maintenant si on a le prenom
  $have_prenom=0; $have_nom=0;
  if (preg_match_all("/<r2rc:prenom>(.*?)<\/r2rc:prenom>/",$personne,$results,PREG_SET_ORDER)) {
    $prenoms=array(); // tableau pour les prenoms
    foreach($results as $result) {
      array_push($prenoms,trim($result[1]));
      $personne=str_replace($result[0],"",$personne); //nettoie l'personne
    }
    $prenom=join(" ",$prenoms); // join les prenoms
    $nom=$personne; // c'est le reste
    $have_prenom=1;
  }      
  // on cherche maintenant si on a le nom
  if (preg_match_all("/<r2rc:nom>(.*?)<\/r2rc:nom>/",$personne,$results,PREG_SET_ORDER)) {
    $noms=array(); // tableau pour les noms
    foreach($results as $result) {
      array_push($noms,trim($result[1]));
      $personne=str_replace($result[0],"",$personne); //nettoie l'personne
    }
    $nom=join(" ",$noms); // join les noms
    if (!$have_prenom) $prenom=$personne; // le reste c'est le prenom sauf si on a deja detecte le prenom
    $have_nom=1;
  }
  // si on a pas de style de caractere, alors on essaie de deviner !
  if (!$have_prenom && !$have_nom) {
    // ok, on cherche maintenant a separer le nom et le prenom
    $nom=$personne;
    while ($nom && strtoupper($nom)!=$nom) { $nom=substr(strstr($nom," "),1);}
    if ($nom) {
      $prenom=str_replace($nom,"",$personne);
    } else { // sinon coupe apres le premiere espace
      preg_match("/^\s*(.*)\s+([^\s]+)\s*$/i",$personne,$result);
      $prenom=$result[1]; $nom=$result[2];
    }
  }
  return array($prefix,$prenom,$nom);
}


function traiteentrees (&$text)

{
  $groupe="<r2r:grentree>";

  $oldtags=array(); // pour la suppression dans le texte

  $result1=mysql_query("SELECT style,nom FROM $GLOBALS[tableprefix]typeentrees WHERE status>0 ORDER BY ordre") or die (mysql_error());
  while ($row=mysql_fetch_assoc($result1)) {
    $style=strtolower($row[style]);
    preg_match_all ("/<r2r:$style>\s*(.*?)\s*<\/r2r:$style>/si",$text,$results,PREG_SET_ORDER);
    foreach ($results as $result) {
      $val=strip_tags($result[1]);
      $tags=preg_split ("/[,;]/",strip_tags($val));
      foreach($tags as $tag) {
	// enlever le strip_tages
	$groupe.="<r2r:entree type=\"$row[nom]\">".trim($tag)."</r2r:entree>";
      }
      array_push($oldtags,$result[0]);
    }
  }
  $groupe.="</r2r:grentree>\n";
  $text=str_replace($oldtags,array($groupe),$text); // remplace le premier tag par $groupe et les autres par rien
#  echo "entree:$groupe";
}
    
?>
