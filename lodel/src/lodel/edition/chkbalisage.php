<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include ($home."func.php");

if ($cancel) include ("abandon.php");

$tache=get_tache($idtache);

include ($home."balises.php");

// ajoute les balises "entrees"
include ($home."connect.php");
$result=mysql_query("SELECT style,titre FROM $GLOBALS[tp]typeentrees WHERE statut>0");
while ($row=mysql_fetch_row($result)) { $balises[$row[0]]=$row[1]; }

// ajoute les balises "personnes"
$result=mysql_query("SELECT style,titre,styledescription,titredescription FROM $GLOBALS[tp]typepersonnes WHERE statut>0");
while ($row=mysql_fetch_row($result)) { 
  $balises[$row[0]]=$row[1];
  $balises[$row[2]]=$row[3];
}

// ajoute les balises "documents"
  $result=mysql_query("SELECT $GLOBALS[tp]champs.style,$GLOBALS[tp]champs.titre,$GLOBALS[tp]champs.type FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND  classe='documents' AND $GLOBALS[tp]champs.statut>0") or die (mysql_error());
while (list($style,$titre,$type)=mysql_fetch_row($result)) { 
  if ($type=="mltext") {
    require_once($home."champfunc.php");
    $styles=decode_mlstyle($style);
    foreach($styles as $lang => $style) {
      $balises[$style]=$titre." ($lang)";
    }
  } else {
    $balises[$style]=$titre;
  }
}


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



  $textorig=$text=join("",file($tache[fichier].".html"));

// cherche les sousbalises, retirent les de $balises et prepare le changement d'ecriture.
// une sous balises est definie par la presence d'une balise HTML (le caractere < en pratique) ou parce qu'elle est vide dans $balises
$srch=array(); $rpl=array();
foreach ($balises as $b=>$v) {
  if (!$v || strpos($v,"<")!==FALSE) { // sous balises
    array_push($srch,"/<r2r:$b>/si");array_push($rpl,$v); // balises ouvrante
    // balises fermantes, il faut inverser leur ordre
    preg_match_all("/<(\w+)\b[^>]*>/",$v,$result,PREG_PATTERN_ORDER); // recupere les balises html (et seulement les balises)
    $v="";
    while ($html=array_pop($result[1])) $v.="</$html>";// met les dans l'ordre inverse, et transforme les en balises fermantes
    array_push($srch,"/<\/r2r:$b>/si");array_push($rpl,$v);
    
    $balises[$b]=""; // supprime cette sousbalises (ca change rien normalement)
  }
}

array_push($srch,
#	     "/<\/?r2r:article>/",
#	     "/<r2r:([^>]+)>/e",
#	     "/<\/r2r:([^>]+)>/",
	     "/<r2rc:([^>]+)>/",
	     "/<\/r2rc:([^>]+)>/");

array_push($rpl,
#	     "",
#	     "'<tr valign=\"top\"><td classe=\"chkbalisagetdbalise\">'.\$balises[strtolower('\\1')].'</td><td classe=\"chkbalisagetdparagraphe\">'",	     
#	     "</td></tr>\n",
	     "<span style=\"background-color: #F3F3F3;\">",
	     "</span>");

$text=preg_replace($srch,$rpl,traite_separateur($text));

// detecte les zones balises
$arr=preg_split("/<(\/?r2r):([^>]+)>/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
$level=0;

$tablescontent=array(); // contient les contenus des tables
// on s'assure que la partie principale est la premiere
$part="main";

// recupere les balises de debut et de fin
$startbalise="<r2r:$arr[2]>";
$endbalise="</r2r:".$arr[count($arr)-2].">";

$arr=array_slice($arr,3,-3); // enleve les trois premiers et les trois derniers correspondant aux balises r2r:document

while ($arr) {
  $subtext=array_shift($arr);
  if ($subtext=="r2r") {
    // balise ouvrante
    $level++;
    $bal=array_shift($arr);
    if ($balisesdocumentassocie[$bal] || $bal==$styleforcss) { // change the part
      $part=$bal;
    } else { // others balises
      $textbal=$balises[strtolower(trim($bal))];
      if (!$textbal) $textbal='<div style="color: red">Style "'.$bal.'" non reconnu</div>';
      $tablescontent[$part].='<tr valign="top"><td class="chkbalisagetdbalise">'.$textbal.'</td><td class="chkbalisagetdparagraphe">';
    }
  } elseif ($subtext=="/r2r") {
    // balise fermante
    $level--;
    $bal=array_shift($arr);
    if ($balisesdocumentassocie[$bal] || $bal==$styleforcss) { // end of a part
      $part="main";
    } else {
      $tablescontent[$part].="</td></td>\n";
    }
  } elseif ($level==0 && trim($subtext)) {
    // pas bon, zone none reconnuee
    $tablescontent[$part].='<tr valign="top"><td class="chkbalisagetdbalise"><div style="color: red">PARAGRAPHE NON STYL&Eacute;</div></td><td class="chkbalisagetdparagraphe">'.$subtext."</td></tr>\n";
  } else {
    $tablescontent[$part].=$subtext;
  }
}

// backup the stylecss in the context
$context[$styleforcss]=$tablescontent[$styleforcss];
unset($tablescontent[$styleforcss]);

if (count($tablescontent)>1) { // ok il faut decouper le fichier

  $i=2;
  foreach (array_keys($balisesdocumentassocie) as $bal) {
    if (!preg_match_all("/<r2r:$bal>(.*?)<\/r2r:$bal>/s",$textorig,$results,PREG_PATTERN_ORDER)) continue;
    $text=$startbalise.join("",$results[1]).$endbalise; // construit un fichier propre
    $textorig=str_replace($results[0],"",$textorig); // supprime les zones

    $tache["fichierdecoupe$i"]="$tache[fichier]-$i";
    $tache["typedoc$i"]=$bal;
    $text=$startbalise.$text.$endbalise;
    writefile($tache["fichierdecoupe$i"].".html",$text);
    $i++;
  }

  $tache["fichierdecoupe1"]="$tache[fichier]-1";
  writefile("$tache[fichierdecoupe1].html",$textorig); // d'abord la partie principale

  // on est oblige de faire ca pour enregistrer en premier la partie principale pour recuperer l'id qui est le parent des s fichiers
#  die (htmlentities($texts[main]));

  
  update_tache_context($idtache,$tache);
}


if (preg_match("/<r2r:(titrenumero|nomnumero|typenumero)>/i",$text)) {
  $context[urlsuite]="importsommaire.php?idtache=$idtache";
} else {
  $context[urlsuite]="document.php?idtache=$idtache";
}


function loop_partie_fichier($context,$funcname)

{
  global $tablescontent,$balisesdocumentassocie;

  foreach ($tablescontent as $part => $context[tablecontent]) {
    if ($part=="main") continue;
    $context[part]=$balisesdocumentassocie[$part];
    call_user_func("code_do_$funcname",$context);
  }
  // partie principale maintenant

  $context[part]=count($texts)==1 ? "" : "main";
  $context[tablecontent]=$tablescontent[main];
  call_user_func("code_do_$funcname",$context);
}

$context[idtache]=$idtache;
require ($home."calcul-page.php");
calcul_page($context,"chkbalisage");




?>
