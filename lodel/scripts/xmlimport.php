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


$GLOBALS['prefixregexp']="Pr\.|Dr\.";


class XMLImportParser {

  var $commonstyles;
  var $contextstyles;
  var $cstyles;

  /**
   * class of the document
   */
  var $mainclass;

//
// import XMLLodelBasic file in the database
//
  function XMLImportParser() {}

  function init($class)
  {
    global $home;

    require_once($home."dao.php");
    if (!$this->commonstyles) {
      // get internal styles
      $dao=getDAO("internalstyles");
      $iss=$dao->findMany("status>0");
      foreach ($iss as $is) {
	// analyse the styles
	foreach (preg_split("/[,;]/",$is->style) as $style) {
	  $this->commonstyles[trim($style)]=$is;
	}
      }
      
      // get characterstyles
      $dao=getDAO("characterstyles");
      $css=$dao->findMany("status>0");
      foreach ($css as $cs) {
	// analyse the styles
	foreach (preg_split("/[,;]/",$cs->style) as $style) {
	  $this->commonstyles[trim($style)]=$cs;
	}
      }
    }

    $this->_init_class($class);
    $this->_init_class("entries","class='entries' or class='entities_entries'");
    $this->_init_class("persons","class='persons' or class='entities_persons'");

    $this->mainclass=$class;

    #print_r($this->commonstyles);
  }


  /**
   * parse the $string and send the data to the $handler object
   *
   */

  function parse($string,&$handler)

  {
    $this->handler=$handler; // non-reentrant

    $arr=preg_split("/<(\/?r2r:\w+)>/",$string,-1,PREG_SPLIT_DELIM_CAPTURE);
    $n=count($arr);

    unset($string); // save memory

    // process the internalstyles
    // this is an hard piece of code doing no so much... but I find no better way.
    for($i=1; $i<$n; $i+=2) {
      $opening=$arr[$i]{1}=="/";
      $style=substr($arr[$i],strpos($arr[$i],":")+1);

      $obj=$this->commonstyles[$style];
      if (!$obj) continue;

      // style synonyme. take the first one
      $style=preg_replace("/[:,;].*$/","",$obj->style);
      $arr[$i]=($opening ? "" : "/")."r2r:".$style; // rebuild the style
      
      if (!$obj || !$opening) continue;

      $class=get_class($obj);

      if (!$isave && $class="internalstyles") {
	$forcenext=false;
	if ($obj->surrounding=="-*") {
	  // check what is the previous on.
	  if ($arr[$i-2]{1}!="/" && get_class($this->commonstyles[substr($arr[$i-2],strpos($arr[$i-2],":")+1)])=="tablefields") {
	    // good, put the closing tag further
	    $closing=array_splice($arr,$i-2,1);
	    array_splice($arr,$i,0,$closing);
	  } else {
	    $forcenext=true;
	  }
	  
	} else 
        if ($forcenext || $obj->surrounding=="*-") {
	  $isave=$i; // where to insert the next opening
	} else {
	  // surounding is a proper tag
	  array_splice($arr,$i,0,array("r2r:".$obj->surounding,"")); // opening tag
	  $i+=2; $n+=2;
	  array_splice($arr,$i+2,0,array("/r2r:".$obj->surounding,"")); // closing tag
	  $n+=2;
	}
      } else
	if ($class="tablefields" && $isave) {
	  // put the opening at $isave
	  $opening=array_splice($arr,$i,1);
	  array_splice($arr,$isave,0,$opening);
	  $isave=false;
	} else
	if ($isave) {
	  // problem, the group at $isave has to be attached with above.
	  if ($arr[$isave-2]{1}=="\/" && get_class($this->commonstyles[substr($arr[$isave-2],strpos($arr[$isave-2],":")+1)])=="tablefields") {
	    $closing=array_splice($arr,$isave-2,1);
	    array_splice($arr,$i,0,$closing);	    
	    $isave=false;
	  } else {
	    // don't know what to do, there is nothing before or after
	  }
	}
    } // for

    // proper parser. Launch the handlers

    $datastack=array();
    $classstack=array($this->mainclass);
    $cstyles=&$this->contextstyles[$classstack[0]];

    $handler->openClass($classstack[0]);
    
    $i=0;
    do {
      if ($i % 2 == 0) { // data
	$larr=preg_split("/<(\/?r2rc:\w+)>/",$arr[$i],-1,PREG_SPLIT_DELIM_CAPTURE);
	$datastack[0].=$larr[0];

	$nj=count($larr);
	for($j=1; $j<$nj; $j+=2) {
	  $this->_parseOneStep($larr,$j,$datastack,$classstack,$cstyles);
	}
      } else { // style
	$this->_parseOneStep($arr,$i,$datastack,$classstack,$cstyles);
      }
      $i++;
    } while ($i<$n);

    // close the last tags
    while($classstack) {
      $handler->closeClass(array_shift($classstack));
    }
  } // function parser


  /**
   * do one step of the parser.
   * 1/ call the handler corresponding to the current style/tag/object
   * 2/ change the context if required
   * 3/ feed the datastack
   */

  function _parseOneStep (&$arr,&$i,&$datastack,&$classstack,&$cstyles)

  {
    $opening=$arr[$i]{1}=="\/";
    $style=substr($arr[$i],strpos($arr[$i],":")+1);

    #echo $style,"--<br>";

    if (!$opening && $style==$arr[$i+2]) {
      // current closing equals next opening... merge
      $i+=2; // jump to the next one
      return;
    }

    $obj=$cstyles[$style];
    if (!$obj) $this->commonstyles[$style];

    if (!$obj) {
      // unknow style
      if (!$obj) {
	$this->handler->unknownParagraphStyle($style,$data);
	return;
      }
    }

    $class=substr(get_class($obj),0,-2); // remove the VO at the end
    switch($class) {
    case "internalstyles" :
    case "characterstyles" :
      if ($opening) {
	array_unshift($datastack,"");
      } else {
	$call="process".$class;
	$data=array_shift($datastack);	
	$datastack[0].=$this->handler->$call($obj,$data); // call the method associated with the object class
      }
      break;
    case "tablefields" :
      if (!$cstyles[$style]) { // context change	 ?
	$this->handler->closeClass($classstack[0]);

	if (!$this->contextstyles[array_shift($classstack)][$style]) {
	  // must be in the context below
	  // if not... problem.
	  }
	$cstyles=&$this->contextstyles[$classstack[0]];
	// new context
      }
      if ($opening) {
	$datastack[0]="";
      } else {
	$this->handler->processTableFields($obj,$datastack[0]); // call the method associated with the object class
	}
      break;
    case "entrytypes" :
    case "persontypes" :
      if ($opening) { // opening. Switch the context
	$this->styles=$this->commonstyles[$class][$obj->name];
      } else {
	// change the context
	array_unshift($datastack,$class="entrytypes" ? "entries" : "persons");
	$cstyles=&$this->contextstyles[$classstack[0]];
	
	$this->handler->openClass($classstack[0],$obj);
	
	$call="process".$class;
	$this->handler->$class($obj,$this->characterStylesParser($datastack[0])); // call the method associated with the object class
	$datastack[0]="";
      }
      break;
    default:
      die("ERROR: internal error in XMLImportParser::parse. Unknown class $class");
    }
    $datastack[0].=$this->handler->processData($arr[$i+1]);
  }

  /**
   * init.
   * Gather information from tablefield to know what to do with the various styles.
   * class is the context and criteria is the where to select the tablefields
   *
   */

  function _init_class($class,$criteria="") 

  {
    if ($this->contextstyles[$class]) return; // already done

    // get all the information from the database for all the fields
    $dao=getDAO("tablefields");
    if (!$criteria) $criteria="class='".$class."'";
    $tfs=$dao->findMany($criteria." AND status>0 AND style!=''");

    // create an assoc array style => tf information
    foreach ($tfs as $tf) {
      // is it an index ?
      if ($tf->type=="entries" || $tf->type=="persons") {
	// yes, it's an index. Get the object
	$dao=getDAO($tf->type=="entries" ? "entrytypes" : "persontypes");
	$tf=$dao->find("type='".$tf->name."'");
      }
      // analyse the styles
      foreach (preg_split("/[,;]/",$tf->style) as $style) {
	list($style,$tf->lang)=explode(":",$style);      
	$style=trim($style);
	$this->contextstyles[$class][$style]=$tf;
	$this->commonstyles[$style]=$tf;
      }
    }
  }


} // class XMLImportParser


class XmlImportHandler {


  function processData($data) {
    return $data; #echo $data;
  }

  function processTableFields($obj,$data) 
  {
    echo "<tr><td>".$obj->name."</td><td>".$data."</td></tr>";
  }

  function processEntryTypes($obj,$data) 
  {
    echo "<tr><td style=\"background-color: red;\">".$obj->name."</td><td>".$data."</td></tr>";
  }

  function openClass($class,$obj=null) 
  {
    echo "<tr><td colspan=\"2\" style=\"background-color: green;\">".$class."    ".($obj ? $obj->type : "")."</td></tr>";
  }
  function closeClass($class) 
  {
    echo "<tr><td colspan=\"2\" style=\"background-color: green;\">-- fin $class --</td></tr>";
  }

  function processPersonTypes($obj,$data) 
  {
    echo "<tr><td style=\"background-color: blue;\">".$obj->name."</td><td>".$data."</td></tr>";
  }
  function openPersonTypes($obj) 
  {
    echo "<tr><td colspan=\"2\" style=\"background-color: blue;\">".$obj->type."</td></tr>";
  }
  function closePersonTypes() 
  {
    echo "<tr><td colspan=\"2\" style=\"background-color: blue;\">-- fin --</td></tr>";
  }

  function characterStyles($obj,$data) 

  {
    return "<span style=\"background-color: gray;\">".$data."</span>";
  }

  function internalStyles($obj,$data) 

  {
    return "--internalstyle--".$obj->style."--".$data."--";
  }

  function unknownParagraphStyle($style,$data) {
    echo "<tr><td>Style inconnu: ".$style."</td><td>".$data."</td></tr>";
  }

  function unknownCharacterStyle($style,$data) {
    return $data;
  }
}



//class XMLImportHandler {
//
//  /**
//   * ignore unknown paragraph and character style
//   */
//  function unknownParagraphStyle($style,$data) {}
//  function unknownCharacterStyle($style,$data) {}
//
//  
//  /**
//   * process basic characterstyles
//   */
//  function processCharacterStyles(&$obj,$data)
//
//  {
//    if ($obj->conversion) {
//      return '<span class="'.$obj->style.'>'.$data.'</span>';
//    } else {
//      // basic currently. Should be more evoluated.
//      return $obj->conversion.$data.join("><",array_reverse(explode("><",str_replace("<","</",$obj->conversion))));
//    }
//    return ;
//  }
//
//
//  /**
//   * process basic internalstyles
//   */
//  function processInternalStyles(&$obj,$data)
//
//  {}
//
//  /**
//   * processTableFields
//   */
//
//  function processTableFields($obj,$data)
//
//  {
//    //
//    if ($obj->processing) { // processing ?
//      $processings=preg_split("/\|/",$obj->processing);
//      foreach ($processings as $processing) {
//	if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*)\))?$/",$processing,$result3)) { 
//	  if ($result3[2]) $result3[2]=",".$result3[2]; // arguments
//	  $func=create_function('$x','return '.$result3[1].'($x'.$result3[2].');');
//	  $data=$func($data);
//	}
//      }
//    } // processing
//
//    $data=addslashes(trim($data));
//
//  function mv_image($imgfile,$ext,$count,$id) {
//    $dir="docannexe/image/$id";
//    if (!is_dir(SITEROOT.$dir)) {
//      mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
//      @chmod(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
//    }
//    $newfile="$dir/img-$count.$ext";
//    copy($imgfile,SITEROOT.$newfile);
//    @unlink($imgfile);
//    return $newfile;
//  }
//  $row=getrow(lq("SELECT * FROM #_TP_$class WHERE identity='$id'"));
//  if ($row===false) dberror();
//
//  require_once($home."func.php");
//  copy_images($row,"mv_image",$id);
//
//
//
//    // multilingual text
//    if ($obj->type='mltext') {
//      $data=array("lang"=>$obj->lang,"text"=>$data);
//    }
//
//
//  }
//}



//
//function enregistre_entite_from_xml($context,$text,$class)
//
//{
//  global $home,$db;
//
//  $localcontext=$context;
//
//  $result=$db->execute(lq("SELECT #_TP_tablefields.name,style,type,traitement FROM #_TP_tablefields,#_TP_tablefieldgroups WHERE idgroup=#_TP_tablefieldgroups.id AND class='$class' AND #_TP_tablefields.status>0 AND #_TP_tablefieldgroups.status>0 AND style!=''")) or dberror();
//
//  $sets=array();
//  while (!$result->EOF) {
//    list($name,$style,$type,$traitement)=$result->fields;
//    require_once($home."textfunc.php");
//
//    if ($type=="mltext") { // text multilingue
//      require_once($home."champfunc.php");
//      $stylesarr=decode_mlstyle($style);
//    } else {
//      $stylesarr=array($style);
//    }
//    if ($localcontext[entite][$name]) die ("Error: Two fields have the same name. Please correct in admin/champs.php");
//    foreach ($stylesarr as $lang=>$style) {
//      // look for that tag
//#    echo "$name $style $type $traitement<br>";
//      if (preg_match("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$result2)) {
//	$value=$result2[1];
//
//	// type speciaux
//	/* done in entitefunc.php
//	if ($type=="date") { // date
//	  require_once($home."date.php");
//	  $value=mysqldate(strip_tags($value));
//	}
//	*/
//	#echo "traitement:$traitement";
//	if ($traitement) { // processing ?
//	  $traitements=preg_split("/\|/",$traitement);
//	  foreach ($traitements as $traitement) {
//#echo "trait: $traitement";
//	    if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*)\))?$/",$traitement,$result3)) { 
//	      if ($result3[2]) $result3[2]=",".$result3[2]; // arguments
//	      $func=create_function('$x','return '.$result3[1].'($x'.$result3[2].');');
//	      $value=$func($value);
//	    }
//	  }
//	} // processing
//
//	// enleve les styles de caracteres
//	$value=addslashes(trim(preg_replace("/<\/?r2rc:[^>]+>/","",$value)));
//
//	// now record the $value
//	if ($type=="mltext") {
//	  $localcontext['entite'][$name][$lang]=$value;
//	} else {
//	  $localcontext['entite'][$name]=$value;
//	}
//      } // if found style found in the text
//    } // foreach styles for mltext
//    $result->MoveNext();
//  } // for each  fields.
//
//  if (!$localcontext['idtype']) {
//    // check if the document exists, if not we really need the type
//    if (!$localcontext['id']) die("Preciser un type in xmlimport.php");
//    // get the idtype
//    $localcontext['idtype']=$db->getone(lq("SELECT idtype FROM #_TP_entities WHERE id='$localcontext[id]'"));
//    if ($db->errorno()) dberror();
//    if (!$localcontext['idtype']) die("ERROR: The entites $localcontext[id] should exists.");
//  }
//
//  enregistre_personnes_from_xml($localcontext,$text);
//  enregistre_entrees_from_xml($localcontext,$text);
//
//#  print_r($localcontext);
//
//#  print_r($localcontext);
//
//  $id=enregistre_entite ($localcontext,0,$class,"",FALSE); // on ne genere pas d'error... Tant pis !
//
//  // ok, now, search for the image, and place them in a safe place
//
//  function mv_image($imgfile,$ext,$count,$id) {
//    $dir="docannexe/image/$id";
//    if (!is_dir(SITEROOT.$dir)) {
//      mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
//      @chmod(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
//    }
//    $newfile="$dir/img-$count.$ext";
//    copy($imgfile,SITEROOT.$newfile);
//    @unlink($imgfile);
//    return $newfile;
//  }
//  $row=getrow(lq("SELECT * FROM #_TP_$class WHERE identity='$id'"));
//  if ($row===false) dberror();
//
//  require_once($home."func.php");
//  copy_images($row,"mv_image",$id);
//  myaddslashes($row);
//  foreach ($row as $field=>$value) { $row[$field]=$field."='".$value."'"; }
//  $db->execute(lq("UPDATE #_TP_$class SET ".join(",",$row)." WHERE identity='$id'")) or dberror();
//  // fin du deplacement des images
//
//
//  return $id;
//}
//
//function mystrip_tags($x,$y) { return strip_tags($y,$x); }
//
//
//
//function enregistre_personnes_from_xml (&$localcontext,$text)
//
//{
//  global $db;
//
//  if (!$localcontext[idtype]) die("Internal ERROR: probleme in enregistre_personnes_from_xml");
//
//  $result=$db->execute(lq("SELECT id,style,styledescription FROM #_TP_persontypes,#_TP_entitytypes_persontypes WHERE status>0 AND idpersontype=id AND identitytype='$localcontext[idtype]'")) or dberror();
//
//  while (!$result->EOF) {
//    list($idtype,$style,$styledescription)=$result->fields;
//    // accouple les balises personnes et description
//    // non, on ne fait plus comme ca. $text=preg_replace ("/(<\/r2r:$style>)\s*(<r2r:description>.*?<\/r2r:description>)/si","\\2\\1",$text);
//    // cherche toutes les balises de personnes
//    preg_match_all ("/<r2r:$style>(.*?)<\/r2r:$style>/s",$text,$results2,PREG_SET_ORDER);
//    // cherche toutes les balises de description de personnes
//    preg_match_all ("/<r2r:$styledescription>(.*?)<\/r2r:$styledescription>/s",$text,$results2description,PREG_SET_ORDER);
//#    echo "result2: style=$style";
//#    echo htmlentities($text);
//#    print_r($results2);
//
//    $i=1;
//
//    while ($result2=array_shift($results2)) { // parcours les resultats.
//      $val=trim($result2[1]);
//      // description ?
//      $result2description=array_shift($results2description); // parcours les descriptions.
//      // cherche s'il y a un bloc description
//      $descrpersonne=$result2description ? $result2description[1] : "";
//
//
//#    echo htmlentities($descrpersonne)."<br><br>\n\n";
//      $personnes=preg_split ("/\s*[,;]\s*/",strip_tags($val,"<r2rc:prenom><r2rc:prefix><r2rc:name>"));
//
//      while (($personne=array_shift($personnes))) {
//
//	list ($prefix,$prenom,$name)=decodepersonne($personne);
//	#echo "personne: $personne ; $name<br>\n";
//
//	$localcontext[nomfamille][$idtype][$i]=$name;
//	$localcontext[prefix][$idtype][$i]=$prefix;
//	$localcontext[prenom][$idtype][$i]=$prenom;
//
//	// est-ce qu'on a une description et est-ce qu'elle est pour cet personne ?
//	if ($descrpersonne && !$personnes)  { // oui, c'est le dernier personne de cette liste, s'il y a un bloc description, alors c'est pour lui !
//	  // on recupere les balises du champ description
//	  $balises=array("fonction","affiliation","courriel");
//	  foreach ($balises as $balise) {
//	    if (preg_match("/<r2rc:$balise>(.*?)<\/r2rc:$balise>/s",$descrpersonne,$result4)) {
//	      $localcontext[$balise][$idtype][$i]=trim($result4[1]);
//	    }
//	  } // foreach
//	  
//	  // on efface tous les styles de caracteres
//	  $localcontext[description][$idtype][$i]=preg_replace("/<\/?r2rc:[^>]+>/","",$descrpersonne);
//	} // ok, on a traite la description
//	$i++;
//      }
//    } // parcourt les resultats
//    $result->MoveNext();
//  } // type de personne
//}
//
//
//function decodepersonne($personne) 
//
//{
//  // on regarde s'il y a un prefix
//  // d'abord on cherche s'il y a un style de caractere, sinon, on cherche les prefix classiques definis dans la variables prefixregexp.
//  if (preg_match_all("/<r2rc:prefix>(.*?)<\/r2rc:prefix>/",$personne,$results,PREG_SET_ORDER)) {
//    $prefix="<r2r:prefix>";
//    foreach($results as $result) {
//      $prefix.=$result[1];
//      $personne=str_replace($result[0],"",$personne); //nettoie le champ personne
//    }
//    $prefix.="</r2r:prefix>";
//  } elseif (preg_match("/^\s*($GLOBALS[prefixregexp])\s/",$personne,$result2)) {
//    $prefix="$result2[1]";
//    $personne=str_replace($result2[0],"",$personne); // a partir de php 4.3.0 il faudra utiliser OFFSET_CAPTURE.
//  } else {
//    $prefix="";
//  }
//  // ok on le prefix
//
//
//  // on cherche maintenant si on a le prenom
//  $have_prenom=0; $have_nom=0;
//  if (preg_match_all("/<r2rc:prenom>(.*?)<\/r2rc:prenom>/",$personne,$results,PREG_SET_ORDER)) {
//    $prenoms=array(); // tableau pour les prenoms
//    foreach($results as $result) {
//      array_push($prenoms,trim($result[1]));
//      $personne=str_replace($result[0],"",$personne); //nettoie l'personne
//    }
//    $prenom=join(" ",$prenoms); // join les prenoms
//    $name=$personne; // c'est le reste
//    $have_prenom=1;
//  }      
//  // on cherche maintenant si on a le name
//  if (preg_match_all("/<r2rc:name>(.*?)<\/r2rc:name>/",$personne,$results,PREG_SET_ORDER)) {
//    $noms=array(); // tableau pour les noms
//    foreach($results as $result) {
//      array_push($noms,trim($result[1]));
//      $personne=str_replace($result[0],"",$personne); //nettoie l'personne
//    }
//    $name=join(" ",$noms); // join les noms
//    if (!$have_prenom) $prenom=$personne; // le reste c'est le prenom sauf si on a deja detecte le prenom
//    $have_nom=1;
//  }
//  // si on a pas de style de caractere, alors on essaie de deviner !
//  if (!$have_prenom && !$have_nom) {
//    // ok, on cherche maintenant a separer le name et le prenom
//    $name=$personne;
//    while ($name && strtoupper($name)!=$name) { $name=substr(strstr($name," "),1);}
//    if ($name) {
//      $prenom=str_replace($name,"",$personne);
//    } else { // sinon coupe apres le premiere espace
//      if (preg_match("/^(.*?)\s+([^\s]+)$/i",trim($personne),$result)) {
//	$prenom=$result[1]; $name=$result[2];
//      } else $name=$personne;
//    }
//  }
//  return array($prefix,$prenom,$name);
//}
//
//
//function enregistre_entrees_from_xml (&$localcontext,$text)
//
//{
//  global $home;
//
//  if (!$localcontext[idtype]) die("Internal ERROR: probleme in enregistre_personnes_from_xml");
//
//  $result=$db->execute(lq("SELECT id,style FROM #_TP_entrytypes,#_TP_entitytypes_entrytypes WHERE status>0 AND identrytype=id AND identitytype='$localcontext[idtype]'")) or dberror();
//  require_once($home."champfunc.php");
//
//  while (!$result->EOF) {
//    list($idtype,$style)=$result->fields;
//    // decode the multilingue style.
//    $styles=decode_mlstyle($style);
//#    echo $idtype," ",$style,"<br/>";
//    $i=0;
//    foreach($styles as $lang => $style) { // foreach multilingue style
//#      echo "=>$lang $style";
//      preg_match_all ("/<r2r:$style>\s*(.*?)\s*<\/r2r:$style>/si",$text,$results2,PREG_SET_ORDER);
//      foreach ($results2 as $result2) {
//	$val=strip_tags($result2[1]);
//	$tags=preg_split ("/[,;]/",strip_tags($val));
//	foreach($tags as $tag) {
//	  if ($lang && $lang!="--") { // is the language really defined ?
//	    $localcontext[entrees][$idtype][$i][lang]=$lang;
//	    $localcontext[entrees][$idtype][$i][name]=trim($tag);
//	  } else {
//	    $localcontext[entrees][$idtype][$i]=trim($tag);
//	  }
//	  $i++;
//	}
//      }
//    }
//    $result->MoveNext();
//  }
//#  print_r($localcontext);
//}
//

?>
