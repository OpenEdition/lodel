<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/


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
	      if ($result3[2]) $result3[2]=$result3[2].",";
	      $func=create_function('$x','return '.$result3[1].'('.$result3[2].'$x);');
	      $value=$func($value);
	    }
	  }
	} // processing

	// enleve les styles de caracteres
	$value=addslashes(trim(preg_replace("/<\/?r2rc:[^>]+>/","",$value)));

	// now record the $value
	if ($type=="mltext") {
	  $localcontext[entite][$nom][$lang]=$value;
	} else {
	  $localcontext[entite][$nom]=$value;
	}
      } // if found style found in the text
    } // foreach styles for mltext
  } // foreach fields.

  if (!$localcontext[idtype]) {
    // check if the document exists, if not we really need the type
    if (!$localcontext[id]) die("Preciser un type in xmlimport.php");
    // get the idtype
    $result=mysql_query("SELECT idtype FROM $GLOBALS[tp]entites WHERE id='$localcontext[id]'") or die(mysql_error());
    if (!mysql_num_rows($result)) die("Internal ERROR: The entites $localcontext[id] should exists.");
    list($localcontext[idtype])=mysql_fetch_row($result);
  }

  enregistre_personnes_from_xml(&$localcontext,$text);
  enregistre_entrees_from_xml(&$localcontext,$text);

#  print_r($localcontext);

#  print_r($localcontext);

  $id=enregistre_entite (&$localcontext,0,$classe,"",FALSE); // on ne genere pas d'erreur... Tant pis !

  // ok, maintenant, il faut rechercher les images et corriger leur location.

  function mv_image($imgfile,$ext,$count,$id) {
    $dir="docannexe/$id";
    if (!is_dir("../../".$dir)) mkdir("../../".$dir,0700);
    $newfile="$dir/img-$count.$ext";
    copy($imgfile,"../../".$newfile);
    @unlink($imgfile);
    return $newfile;
  }
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]$classe WHERE identite='$id'") or die (mysql_error());
  $row=mysql_fetch_assoc($result);
  require_once($home."func.php");
  copy_images($row,"mv_image",$id);
  myaddslashes($row);
  foreach ($row as $field=>$value) { $row[$field]=$field."='".$value."'"; }
  mysql_query("UPDATE $GLOBALS[tp]$classe SET ".join(",",$row)." WHERE identite='$id'") or die (mysql_error());
  // fin du deplacement des images


  return $id;
}




function enregistre_personnes_from_xml (&$localcontext,$text)

{
  if (!$localcontext[idtype]) die("Internal ERROR: probleme in enregistre_personnes_from_xml");

  $result=mysql_query("SELECT id,style,styledescription FROM $GLOBALS[tp]typepersonnes,$GLOBALS[tp]typeentites_typepersonnes WHERE statut>0 AND idtypepersonne=id AND idtypeentite='$lodelcontext[idtype]'") or die (mysql_error());
  while (list($idtype,$style,$styledescription)=mysql_fetch_row($result)) {
    // accouple les balises personnes et description
    // non, on ne fait plus comme ca. $text=preg_replace ("/(<\/r2r:$style>)\s*(<r2r:description>.*?<\/r2r:description>)/si","\\2\\1",$text);
    // cherche toutes les balises de personnes
    preg_match_all ("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$results2,PREG_SET_ORDER);
    // cherche toutes les balises de description de personnes
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
  global $home;

  if (!$localcontext[idtype]) die("Internal ERROR: probleme in enregistre_personnes_from_xml");

  $result=mysql_query("SELECT id,style FROM $GLOBALS[tp]typeentrees,$GLOBALS[tp]typeentites_typeentrees WHERE statut>0 AND idtypeentree=id AND idtypeentree='$lodelcontext[idtype]'") or die (mysql_error());
  require_once($home."champfunc.php");

  while (list($idtype,$style)=mysql_fetch_row($result)) {
    // decode the multilingue style.
    $styles=decode_mlstyle($style);
    foreach($styles as $lang => $style) { // foreach multilingue style

      preg_match_all ("/<r2r:$style>\s*(.*?)\s*<\/r2r:$style>/si",$text,$results2,PREG_SET_ORDER);
      $i=0;
      foreach ($results2 as $result2) {
	$val=strip_tags($result2[1]);
	$tags=preg_split ("/[,;]/",strip_tags($val));
	foreach($tags as $tag) {
	  if ($lang && $lang!="--") { // is the language really defined ?
	    $localcontext[entrees][$idtype][$i][lang]=$lang;
	    $localcontext[entrees][$idtype][$i][nom]=trim($tag);
	  } else {
	    $localcontext[entrees][$idtype][$i]=trim($tag);
	  }
	  $i++;
	}
      }
    }
  }
}


?>
