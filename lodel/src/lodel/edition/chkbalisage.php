<?
require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include ($home."func.php");

if ($cancel) include ("abandon.php");

$tache=get_tache($id);

include ($home."balises.php");

// ajoute les balises "entrees"
include ($home."connect.php");
$result=mysql_query("SELECT style,titre FROM $GLOBALS[prefixtable]typeentrees WHERE status>0");
while ($row=mysql_fetch_row($result)) { $balises[$row[0]]=$row[1]; }

//
### cette zone n'est plus inutilisee
#if ($line) { // on vient de balisage, il faut modifier les balises
#  // lit le fichier lined
#  $lines=explode("<!--r2rline=",join("",file($tache[fichier].".lined")));
#
#  $text="";
#  $close="";
#  $context[fichier]="";
#  foreach ($lines as $txt) {
#    if (preg_match("/^(\d+)-->(.*?)$/s",$txt,$match) && $line[$match[1]]!="-") {
#      $b=$match[1];
#      if ($line[$b]=="fin") {
#	$text.=$close;
#	$close="";
#	break;
#      } elseif ($line[$b]=="finbalise") {
#	$text.=$close;
#	$close="";
#      } else {
#	$bal=$line[$b];
#	$val=$match[2];
#	$context[fichier].="<tr valign=\"top\"><td>".$balises[$bal]."</td><td>$val\n";
#
#	if (preg_match("/^$division$/",$bal)) { // ferme tout de suite
#	  $text.="<r2r:".$bal.">".$val."</r2r:".$bal.">\n";
#	  $context[fichier].="<tr valign=\"top\"><td>&nbsp;</td><td>\n";
#	} else {
#	  $text.=$close."<r2r:".$bal.">".$val;
#	  $close="</r2r:".$bal.">\n";
#	}
#
#      }
#    } elseif ($close) {
#      $context[fichier].="$match[2]\n";
#      $text.=$match[2]."\n";
#    }
#  }
#  $text="<r2r:article>".$text.$close."</r2r:article>";
##  $text=traite_couple(traite_multiplelevel($text));
#  $text=traite_couple($text);
#
#  writefile ($tache[fichier].".html",$text);
#} else { // lines est non defini, on doit donc lire le fichier xml et l'afficher
  $text=join("",file($tache[fichier].".html"));

  // cherche les sousbalises, retirent les de $balises et prepare le changement d'ecriture.
  // une sous balises est definie par la presence d'une balise HTML (le caractere < en pratique)ou parce qu'elle est vide dans $balises
  $srch=array(); $rpl=array();
  foreach ($balises as $b=>$v) {
    if (!$v || strpos($v,"<")!==FALSE) { // sous balises
      array_push($srch,"/<r2r:$b>/si");array_push($rpl,$v); // balises ouvrante
      // balises fermante:
      preg_match_all("/<(\w+)\b[^>]*>/",$v,$result,PREG_PATTERN_ORDER); // recupere les balises html (et seulement les balises)
      $v="";
      while ($html=array_pop($result[1])) $v.="</$html>";// met les dans l'ordre inverse, et transforme les en balises fermantes
      array_push($srch,"/<\/r2r:$b>/si");array_push($rpl,$v); // balises ouvrante

      $balises[$b]=""; // supprime cette sousbalises (ca change rien normalement)
    }
  }

  array_push($srch,
	     "/<\/?r2r:article>/si",
	     "/<r2r:([^>]+)>/sie",
	     "/<\/r2r:([^>]+)>/si");

  array_push($rpl,
	     "",
	     "'<tr valign=\"top\"><td class=\"chkbalisagetdbalise\">'.\$balises[strtolower('\\1')].'</td><td class=\"chkbalisagetdparagraphe\">'",	     
	     "</td></tr>");
  
  $context[fichier]=preg_replace($srch,$rpl,$text);
#}

if (preg_match("/<r2r:(titrenumero|nomnumero|typenumero)>/i",$text)) {
  $context[urlsuite]="importsommaire.php?id=$id";
} else {
  $context[urlsuite]="extrainfo.php?id=$id";
}


$context[id]=$id;
include ($home."calcul-page.php");
calcul_page($context,"chkbalisage");

?>
