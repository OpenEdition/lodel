<?
include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include ("$home/func.php");

if ($cancel) include ("abandon.php");

$row=get_tache($id);

include ("$home/balises.php");
if ($line) { // on vient de balise, il faut modifier les balises
  // lit le fichier lined
  $lines=explode("<!--r2rline=",join("",file($row[fichier].".lined")));

  $text="";
  $close="";
  $context[fichier]="";
  foreach ($lines as $txt) {
    if (preg_match("/^(\d+)-->(.*?)$/s",$txt,$match) && $line[$match[1]]!="-") {
      $b=$match[1];
      if ($line[$b]=="fin") {
	$text.=$close;
	$close="";
	break;
      } elseif ($line[$b]=="finbalise") {
	$text.=$close;
	$close="";
      } else {
	$bal=$line[$b];
	$val=$match[2];
	$context[fichier].="<tr valign=\"top\"><td>".$balises[$bal]."</td><td>$val\n";

	if (preg_match("/^$division$/",$bal)) { // ferme tout de suite
	  $text.="<r2r:".$bal.">".$val."</r2r:".$bal.">\n";
	  $context[fichier].="<tr valign=\"top\"><td>&nbsp;</td><td>\n";
	} else {
	  $text.=$close."<r2r:".$bal.">".$val;
	  $close="</r2r:".$bal.">\n";
	}

      }
    } elseif ($close) {
      $context[fichier].="$match[2]\n";
      $text.=$match[2]."\n";
    }
  }
  $text="<r2r:article>".$text.$close."</r2r:article>";
#  $text=traite_couple(traite_multiplelevel($text));
  $text=traite_couple($text);

  writefile ($row[fichier].".html",$text);
} else { // lines est non defini, on doit donc lire le fichier xml et l'afficher
  $text=join("",file($row[fichier].".html"));
  $context[fichier]=preg_replace(array("/<\/?r2r:article>/si",
#				       "/<(\/?)r2r:section(\d+)>/si",
#				       "/<(\/?)r2r:divbiblio>/si",
#				       "/<(\/?)r2r:citation>/si",
				       "/<r2r:([^>]+)>/sie",
				       "/<\/r2r:([^>]+)>/si"),
				   array("",
#					 "<\\1h\\2>",
#					 "<\\1h2>",
#					"<\\1blockquote>",
					 "'<tr valign=\"top\"><td class=\"chkbalisagetdbalise\">'.\$balises[strtolower('\\1')].'</td><td class=\"chkbalisagetrparagraphe\">'",
					 "</td></tr>"),
				 $text);
}

if (preg_match("/<r2r:(titrenumero|nomnumero)>/i",$text)) {
  $context[urlsuite]="importsommaire.php?id=$id";
} else {
  $context[urlsuite]="extrainfo.php?id=$id";
}


$context[id]=$id;
include ("$home/calcul-page.php");
calcul_page($context,"chkbalisage");

?>
