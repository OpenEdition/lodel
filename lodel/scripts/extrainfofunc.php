<?
//
// fonctions de traitement
// specifiques a extrainfo
//


function gr_auteur(&$context,$plusauteurs)

{
    $i=1;
    $rpl="<r2r:grauteur>";
    while ($context["nomfamille$i"] || $context["prenom$i"] || $context["affiliation$i"] || $context["courriel$i"] || $context["prefix$i"]) {
      $rpl.="<r2r:auteur ordre=\"$i\">";
      // nompersonne
      $rpl.="<r2r:nompersonne>\n".
	writetag("prefix",$context["prefix$i"]).
	writetag("nomfamille",$context["nomfamille$i"]).
	writetag("prenom",$context["prenom$i"]).
	"</r2r:nompersonne>\n";

      // affiliation
      $rpl.=writetag("affiliation",$context["affiliation$i"]);
      // courriel
      $rpl.=writetag("courriel",$context["courriel$i"]);

      $rpl.="</r2r:auteur>\n";
      $i++;
    }
    if ($plusauteurs) $rpl.="<r2r:auteur></r2r:auteur>";

    $rpl.="</r2r:grauteur>";

    return $rpl;
}

function gr_motcle(&$context,&$motcles)

{
  // traite les motcles
  if (!$context[option_motclefige]) $motcles=array_merge($motcles,preg_split ("/\s*[,;]\s*/",$context[autresmotcles]));
  $rpl="<r2r:grmotcle>";
  if ($motcles) {
    foreach ($motcles as $p) {
      $rpl.=writetag("motcle",strip_tags(rmscript(trim($p))));
    }
  }
  $rpl.="</r2r:grmotcle>";
  return $rpl;
}


//function gr_periode(&$periodes)
//
//{
//    // traite les periodes
//    $rpl="<r2r:grperiode>";
//
//    if ($periodes) {
//	foreach ($periodes as $p) {
//	  $rpl.=writetag("periode",strip_tags(rmscript(trim($p))));
//	}
//    }
//    $rpl.="</r2r:grperiode>\n";
//    return $rpl;
//}

function gr_indexh(&$context,&$indexhs,$bal)

{
  $bal=strtolower($bal);
  // traite les indexhs
  $rpl="<r2r:gr$bal>";

  if ($indexhs) {
    foreach ($indexhs as $p) {
      $rpl.=writetag($bal,strip_tags(rmscript(trim($p))));
    }
  }
  $rpl.="</r2r:gr$bal>\n";
  return $rpl;
}


function gr_titre(&$context)

{
    return "<r2r:grtitre>".
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

function makeselectperiodes()
{ makeselectindexhs("periode",TYPE_PERIODE); }
function makeselectgeographies()
{ makeselectindexhs("geographie",TYPE_GEOGRAPHIE); }


function makeselectindexhs ($bal,$type)
{
  global $text;
  # extrait les periodes du texte
  preg_match_all("/<r2r:$bal\b[^>]*>(.*?)<\/r2r:$bal>/is",$text,$indexhs,PREG_PATTERN_ORDER);
#  print_r($indexhs[1]);

  makeselectindexhs_rec(0,"",$indexhs,$type);
}

function makeselectindexhs_rec($parent,$rep,$indexhs,$type)

{
  $result=mysql_query("SELECT id, abrev, nom FROM indexhs WHERE status>=-1 AND parent='$parent' AND type='$type' ORDER BY ordre") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=in_array($row[abrev],$indexhs[1]) ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[abrev]\"$selected>$rep$row[nom]</OPTION>\n";
    makeselectindexhs_rec($row[id],$rep.$row[nom]."/",$indexhs,$type);
  }
}


function makeselectmotcles()

{
  global $text,$context;

  if (!$context[option_motclefige]) $critere="type=".TYPE_MOTCLE." OR";
  $result=mysql_query("SELECT mot FROM indexls WHERE status>=-1 AND ($critere type=".TYPE_MOTCLE_PERMANENT.") GROUP BY mot ORDER BY mot") or die (mysql_error());

  # extrait les motcles du texte
  preg_match_all("/<r2r:motcle\b[^>]*>(.*?)<\/r2r:motcle\s*>/is",$text,$motcles,PREG_PATTERN_ORDER);

  $motclestrouves=array();

  while ($row=mysql_fetch_assoc($result)) {
    $selected=in_array($row[mot],$motcles[1]) ? " selected" : "";
    if ($selected) array_push($motclestrouves,$row[mot]);
    echo "<option value=\"$row[mot]\"$selected>$row[mot]</option>\n";
  }
#  print_r($motcles[1]);
#  print_r($motclestrouves);
#  print_r(array_diff($motcles[1],$motclestrouves));
  if (!$context[option_motclefige]) $GLOBALS[context][autresmotcles]=join(", ",array_diff($motcles[1],$motclestrouves));
}



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


function boucle_auteurs(&$generalcontext,$funcname)
{
  global $text;

  $balises="(prefix|nomfamille|prenom|courriel|affiliation)";

  preg_match_all("/<r2r:auteur\b[^>]*>(.*?)<\/r2r:auteur\s*>/is",$text,$results,PREG_SET_ORDER);
  foreach ($results as $auteur) {
    preg_match_all("/<r2r:$balises\b[^>]*>(.*?)<\/r2r:\\1\s*>/is",$auteur[1],$result,PREG_SET_ORDER);

    $ind++;
    $lcontext=array( ind => $ind);
    if ($result) {
      foreach ($result as $champ) { $lcontext[strtolower($champ[1])]=htmlspecialchars(stripslashes(strip_tags($champ[2]))); }
    }
    $context=array_merge($generalcontext,$lcontext);
	call_user_func("code_boucle_$funcname",$context);
  }
}


//////////////////////// function de transformation des balises 's ///////////

function auteurs2auteur (&$text)

{
  // traitements speciaux:
  if (preg_match ("/<r2r:auteurs>\s*(.*?)\s*<\/r2r:auteurs>/si",$text,$result)) {
    $val=strip_tags($result[1]);
    $auteurs=preg_split ("/\s*[,;]\s*/",$val);
    $val="<r2r:grauteur>";
    $i=1;
    foreach($auteurs as $auteur) {
      // affiliation
      if (preg_match("/\[([^\]]+)\]/",$auteur,$result2)) {
	$auteur=str_replace($result2[0],"",$auteur);
	$affiliation=$result2[1];
      } else {
	$affiliation="";
      }
      // essaie de deviner le nom et le prenom. Le nom est en majuscule
      $nom=$auteur;
      while ($nom && strtoupper($nom)!=$nom) { $nom=substr(strstr($nom," "),1);}
      if ($nom) {
	$prenom=str_replace($nom,"",$auteur);
      } else { // sinon coupe apres le premiere espace
	preg_match("/^\s*(.*)\s+([^\s]+)\s*$/i",$auteur,$result2);
	$prenom=$result2[1]; $nom=$result2[2];
      }
      $val.="<r2r:auteur ordre=\"$i\"><r2r:nompersonne><r2r:nomfamille>$nom</r2r:nomfamille><r2r:prenom>$prenom</r2r:prenom></r2r:nompersonne>";
      if ($affiliation) $val.="<r2r:affiliation>$affiliation</r2r:affiliation>";
      $val.="</r2r:auteur>";
      $i++;
    }
    $val.="</r2r:grauteur>\n";
    $text=str_replace($result[0],$val,$text);
  } // fin du traitement speciale des auteurs
}


function tags2tag ($bal,&$text)

{
  $bals=$bal."s";
  $bal=strtolower($bal);

  if (preg_match ("/<r2r:$bals>\s*(.*?)\s*<\/r2r:$bals>/si",$text,$result)) {
    $val=$result[1];
    $tags=preg_split ("/[,;]/",preg_replace(
					    array("/^\s*<(p|div)\b[^>]*>/si","/<\/(p|div)\b[^>]*>$/si","/\s+/"),
					    array("",""," "),$val));
    $val="<r2r:gr$bal>\n";
    foreach($tags as $tag) {
      # enlever le strip_tages
      $val.="<r2r:$bal>".trim(strip_tags($tag))."</r2r:$bal>";
    }
    $val.="</r2r:gr$bal>\n";
    $text=str_replace($result[0],$val,$text);
  }
}

?>
