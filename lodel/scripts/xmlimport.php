<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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


function enregistre_entite_from_xml($context,$text,$class)

{
  global $home;

  $localcontext=$context;

  $result=mysql_query("SELECT $GLOBALS[tp]fields.name,style,type,traitement FROM $GLOBALS[tp]fields,$GLOBALS[tp]fieldgroups WHERE idgroup=$GLOBALS[tp]fieldgroups.id AND class='$class' AND $GLOBALS[tp]fields.status>0 AND $GLOBALS[tp]fieldgroups.status>0 AND style!=''") or die (mysql_error());

  $sets=array();
  while (list($name,$style,$type,$traitement)=mysql_fetch_row($result)) {
    require_once($home."textfunc.php");

    if ($type=="mltext") { // text multilingue
      require_once($home."champfunc.php");
      $stylesarr=decode_mlstyle($style);
    } else {
      $stylesarr=array($style);
    }
    if ($localcontext[entite][$name]) die ("Error: Two fields have the same name. Please correct in admin/champs.php");
    foreach ($stylesarr as $lang=>$style) {
      // look for that tag
#    echo "$name $style $type $traitement<br>";
      if (preg_match("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$result2)) {
	$value=$result2[1];

	// type speciaux
	/* done in entitefunc.php
	if ($type=="date") { // date
	  require_once($home."date.php");
	  $value=mysqldate(strip_tags($value));
	}
	*/
	#echo "traitement:$traitement";
	if ($traitement) { // processing ?
	  $traitements=preg_split("/\|/",$traitement);
	  foreach ($traitements as $traitement) {
#echo "trait: $traitement";
	    if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*)\))?$/",$traitement,$result3)) { 
	      if ($result3[2]) $result3[2]=",".$result3[2]; // arguments
	      $func=create_function('$x','return '.$result3[1].'($x'.$result3[2].');');
	      $value=$func($value);
	    }
	  }
	} // processing

	// enleve les styles de caracteres
	$value=addslashes(trim(preg_replace("/<\/?r2rc:[^>]+>/","",$value)));

	// now record the $value
	if ($type=="mltext") {
	  $localcontext[entite][$name][$lang]=$value;
	} else {
	  $localcontext[entite][$name]=$value;
	}
      } // if found style found in the text
    } // foreach styles for mltext
  } // foreach fields.

  if (!$localcontext[idtype]) {
    // check if the document exists, if not we really need the type
    if (!$localcontext[id]) die("Preciser un type in xmlimport.php");
    // get the idtype
    $result=mysql_query("SELECT idtype FROM $GLOBALS[tp]entities WHERE id='$localcontext[id]'") or die(mysql_error());
    if (!mysql_num_rows($result)) die("Internal ERROR: The entites $localcontext[id] should exists.");
    list($localcontext[idtype])=mysql_fetch_row($result);
  }

  enregistre_personnes_from_xml($localcontext,$text);
  enregistre_entrees_from_xml($localcontext,$text);

#  print_r($localcontext);

#  print_r($localcontext);

  $id=enregistre_entite ($localcontext,0,$class,"",FALSE); // on ne genere pas d'error... Tant pis !

  // ok, now, search for the image, and place them in a safe place

  function mv_image($imgfile,$ext,$count,$id) {
    $dir="docannexe/image/$id";
    if (!is_dir(SITEROOT.$dir)) {
      mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
      @chmod(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
    }
    $newfile="$dir/img-$count.$ext";
    copy($imgfile,SITEROOT.$newfile);
    @unlink($imgfile);
    return $newfile;
  }
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]$class WHERE identity='$id'") or die (mysql_error());
  $row=mysql_fetch_assoc($result);
  require_once($home."func.php");
  copy_images($row,"mv_image",$id);
  myaddslashes($row);
  foreach ($row as $field=>$value) { $row[$field]=$field."='".$value."'"; }
  mysql_query("UPDATE $GLOBALS[tp]$class SET ".join(",",$row)." WHERE identity='$id'") or die (mysql_error());
  // fin du deplacement des images


  return $id;
}

function mystrip_tags($x,$y) { return strip_tags($y,$x); }



function enregistre_personnes_from_xml (&$localcontext,$text)

{
  if (!$localcontext[idtype]) die("Internal ERROR: probleme in enregistre_personnes_from_xml");

  $result=mysql_query("SELECT id,style,styledescription FROM $GLOBALS[tp]persontypes,$GLOBALS[tp]entitytypes_persontypes WHERE status>0 AND idpersontype=id AND identitytype='$localcontext[idtype]'") or die (mysql_error());
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
      $personnes=preg_split ("/\s*[,;]\s*/",strip_tags($val,"<r2rc:prenom><r2rc:prefix><r2rc:name>"));

      while (($personne=array_shift($personnes))) {

	list ($prefix,$prenom,$name)=decodepersonne($personne);
	#echo "personne: $personne ; $name<br>\n";

	$localcontext[nomfamille][$idtype][$i]=$name;
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
    $name=$personne; // c'est le reste
    $have_prenom=1;
  }      
  // on cherche maintenant si on a le name
  if (preg_match_all("/<r2rc:name>(.*?)<\/r2rc:name>/",$personne,$results,PREG_SET_ORDER)) {
    $noms=array(); // tableau pour les noms
    foreach($results as $result) {
      array_push($noms,trim($result[1]));
      $personne=str_replace($result[0],"",$personne); //nettoie l'personne
    }
    $name=join(" ",$noms); // join les noms
    if (!$have_prenom) $prenom=$personne; // le reste c'est le prenom sauf si on a deja detecte le prenom
    $have_nom=1;
  }
  // si on a pas de style de caractere, alors on essaie de deviner !
  if (!$have_prenom && !$have_nom) {
    // ok, on cherche maintenant a separer le name et le prenom
    $name=$personne;
    while ($name && strtoupper($name)!=$name) { $name=substr(strstr($name," "),1);}
    if ($name) {
      $prenom=str_replace($name,"",$personne);
    } else { // sinon coupe apres le premiere espace
      if (preg_match("/^(.*?)\s+([^\s]+)$/i",trim($personne),$result)) {
	$prenom=$result[1]; $name=$result[2];
      } else $name=$personne;
    }
  }
  return array($prefix,$prenom,$name);
}


function enregistre_entrees_from_xml (&$localcontext,$text)

{
  global $home;

  if (!$localcontext[idtype]) die("Internal ERROR: probleme in enregistre_personnes_from_xml");

  $result=mysql_query("SELECT id,style FROM $GLOBALS[tp]entrytypes,$GLOBALS[tp]entitytypes_entrytypes WHERE status>0 AND identrytype=id AND identitytype='$localcontext[idtype]'") or die (mysql_error());
  require_once($home."champfunc.php");

  while (list($idtype,$style)=mysql_fetch_row($result)) {
    // decode the multilingue style.
    $styles=decode_mlstyle($style);
#    echo $idtype," ",$style,"<br/>";
    $i=0;
    foreach($styles as $lang => $style) { // foreach multilingue style
#      echo "=>$lang $style";
      preg_match_all ("/<r2r:$style>\s*(.*?)\s*<\/r2r:$style>/si",$text,$results2,PREG_SET_ORDER);
      foreach ($results2 as $result2) {
	$val=strip_tags($result2[1]);
	$tags=preg_split ("/[,;]/",strip_tags($val));
	foreach($tags as $tag) {
	  if ($lang && $lang!="--") { // is the language really defined ?
	    $localcontext[entrees][$idtype][$i][lang]=$lang;
	    $localcontext[entrees][$idtype][$i][name]=trim($tag);
	  } else {
	    $localcontext[entrees][$idtype][$i]=trim($tag);
	  }
	  $i++;
	}
      }
    }
  }
#  print_r($localcontext);
}


?>
