<?

$GLOBALS[prefixregexp]="Pr\.|Dr\.";
//
// fonction d'import d'un fichier en XMLLodelBasic dans la base
//

require_once($home."entitefunc.php");


function enregistre_entite_from_xml($context,$text,$classe)

{
  global $home;

  $localcontext=$context;
  //xml_parse_into_struct_ns($text,&$vals,&$index);

  $result=mysql_query("SELECT $GLOBALS[tp]champs.nom,style,type,traitement FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND classe='$classe' AND $GLOBALS[tp]champs.statut>0 AND $GLOBALS[tp]groupesdechamps.statut>0 AND style!=''") or die (mysql_error());

  $sets=array();
  while (list($nom,$style,$type,$traitement)=mysql_fetch_row($result)) {
    require_once($home."textfunc.php");

    if ($type=="mltext") { // text multilingue
      require_once($home."champfunc.php");
      $stylesarr=decode_mlstyle($style);
    } else {
      $stylesarr=array($style);
    }
    if ($localcontext[entite][$nom]) die ("Error: Two fields have the same name. Please correct in admin/champs.php");
    foreach ($stylesarr as $lang=>$style) {
      // look for that tag
#    echo "$nom $style $type $traitement<br>";
      if (preg_match("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$result2)) {
	$value=$result2[1];

	// type speciaux
	if ($type=="date") { // date
	  require_once($home."date.php");
	  $value=mysqldate(strip_tags($value));
	}

	if ($traitement) { // processing ?
	  $traitements=preg_split("/\|/",$traitement);
	  foreach ($traitements as $traitement) {
#echo "trait: $traitement";
	    if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*?)\))?$/",$traitement,$result3)) { 
	      if ($result3[2]) $result3[2]=",".$result3[2];
	      $func=create_function('$x','return '.$result3[1].'($x'.$result3[2].');');
	      $value=$func($value);
	    }
	  }
	} // processing
	
	// enleve les <P> s'ils sont aux extremites, et qu'il n'y en a pas dedans
	// ainsi que les styles de caracteres
	$value=addslashes(trim(preg_replace(array("/<\/?(P|BR)>/i","/<\/?r2rc:[^>]+>/"),"",$value)));

	// now record the $value
	if ($type=="mltext") {
	  $localcontext[entite][$nom][$lang]=$value;
	} else {
	  $localcontext[entite][$nom]=$value;
	}
      } // if found style found in the text
    } // foreach styles for mltext
  } // foreach fields.

  // recupere les informations sur le type
  $style=$classe=="documents" ? "typedoc" : "type"; // temporaire.

  $type="";
  if ($context[$style]) {
    $type=addslashes($context[$style]);
  } elseif (preg_match("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$result)) {
    $type=addslashes(trim(strip_tags($result[1])));
  }
  if ($type) {
    // recherche l'id du type
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$type' AND classe='$classe'") or die (mysql_error());
    list($idtype)=mysql_fetch_row($result);

    if (!$idtype) {
      // on fait rien, mais c'est peut etre pas une bonne idee
    }
  } else {
    $idtype=0;
  }
  $localcontext[idtype]=$idtype;

  enregistre_personnes_from_xml(&$localcontext,$text);
  enregistre_entrees_from_xml(&$localcontext,$text);

#  print_r($localcontext);

#  print_r($localcontext);

  return enregistre_entite (&$localcontext,0,$classe,"",FALSE); // on ne genere pas d'erreur... Tant pis !
}




function enregistre_personnes_from_xml (&$localcontext,$text)

{
  // il faudrait ajouter ici un test sur le type... mais bon, c'est pas facile parce qu'on ne connait pas encore le type !!!
  $result=mysql_query("SELECT id,style,styledescription FROM $GLOBALS[tp]typepersonnes WHERE statut>0") or die (mysql_error());
  while (list($idtype,$style,$styledescription)=mysql_fetch_row($result)) {
    // accouple les balises personnes et description
    // non, on ne fait plus comme ca. $text=preg_replace ("/(<\/r2r:$style>)\s*(<r2r:description>.*?<\/r2r:description>)/si","\\2\\1",$text);
    // cherche toutes les balises de personnes
    preg_match_all ("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$results2,PREG_SET_ORDER);
    // cherche toutes les balises de decsription de personnes
    preg_match_all ("/<r2r:$styledescription>(.*?)<\/r2r:$styledescription>/s",$text,$results2description,PREG_SET_ORDER);
#    echo "result2: style=$style";
#    echo htmlentities($text);
#    print_r($results2);

    $i=1;

    while ($result2=array_shift($results2)) { // parcours les resultats.
      $val=trim($result2[1]);
      // description ?
      $result2description=array_shift($results2description); // parcours les descriptions.
      // cherche s'il y a un bloc description
      $descrpersonne=$result2description ? $result2description[1] : "";


#    echo htmlentities($descrpersonne)."<br><br>\n\n";
      $personnes=preg_split ("/\s*[,;]\s*/",strip_tags($val,"<r2rc:prenom><r2rc:prefix><r2rc:nom>"));

      while (($personne=array_shift($personnes))) {

	list ($prefix,$prenom,$nom)=decodepersonne($personne);
	#echo "personne: $personne ; $nom<br>\n";

	$localcontext[nomfamille][$idtype][$i]=$nom;
	$localcontext[prefix][$idtype][$i]=$prefix;
	$localcontext[prenom][$idtype][$i]=$prenom;

	// est-ce qu'on a une description et est-ce qu'elle est pour cet personne ?
	if ($descrpersonne && !$personnes)  { // oui, c'est le dernier personne de cette liste, s'il y a un bloc description, alors c'est pour lui !
	  // on recupere les balises du champ description
	  $balises=array("fonction","affiliation","courriel");
	  foreach ($balises as $balise) {
	    if (preg_match("/<r2rc:$balise>(.*?)<\/r2rc:$balise>/s",$descrpersonne,$result4)) {
	      $localcontext[$balise][$idtype][$i]=trim($result4[1]);
	    }
	  } // foreach
	  
	  // on efface tous les styles de caracteres
	  $localcontext[description][$idtype][$i]=preg_replace("/<\/?r2rc:[^>]+>/","",$descrpersonne);
	} // ok, on a traite la description
	$i++;
      }
    } // parcourt les resultats
  } // type de personne
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
    $prefix="$result2[1]";
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
      if (preg_match("/^\s*(.*?)\s+([^\s]+)\s*$/i",$personne,$result)) {
	$prenom=$result[1]; $nom=$result[2];
      } else $nom=$personne;
    }
  }
  return array($prefix,$prenom,$nom);
}


function enregistre_entrees_from_xml (&$localcontext,$text)

{
  // il faudrait ajouter ici un test sur le type... mais bon, c'est pas facile parce qu'on ne connait pas encore le type !!!

  $result=mysql_query("SELECT id,style FROM $GLOBALS[tp]typeentrees WHERE statut>0") or die (mysql_error());
  while (list($idtype,$style)=mysql_fetch_row($result)) {
    preg_match_all ("/<r2r:$style>\s*(.*?)\s*<\/r2r:$style>/si",$text,$results2,PREG_SET_ORDER);
    $i=0;
    foreach ($results2 as $result2) {
      $val=strip_tags($result2[1]);
      $tags=preg_split ("/[,;]/",strip_tags($val));
      foreach($tags as $tag) {
	$localcontext[entrees][$idtype][$i]=trim($tag);
	$i++;
      }
    }
  }
}


?>
