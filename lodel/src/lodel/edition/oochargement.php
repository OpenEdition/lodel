<?php
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




require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include ($home."func.php");
require_once($home."utf8.php"); // conversion des caracteres

$context[idparent]=intval($idparent);
$context[iddocument]=intval($iddocument);
$context[idtache]=$idtache=intval($idtache);
$context[idtype]=intval($idtype);
$context[lodeltags]=intval($lodeltags);

if ($_FILES['file1'] && $_FILES['file1']['tmp_name'] && $_FILES['file1']['tmp_name']!="none") {
  do {
    $file1=$_FILES['file1']['tmp_name'];

    // verifie que la variable file1 n'a pas ete hackee
    if (!is_uploaded_file($file1)) die(utf8_encode("Le fichier n'est pas un fichier chargé"));

    $t=time();
    $tmpdir=tmpdir(); // use here and later.
    $source=$tmpdir."/".basename($file1)."-source";
    move_uploaded_file($file1,$source); // move first because some provider does not allow operation in the upload dir

    $fileconverted=$source.".converted";
    $sourceoriginale=$_FILES['file1']['name'];

    list($ret,$convertretvar)=convert($source,$fileconverted);

    // the ServOO should return nothing, if it return, it's an ERROR or a SAY comment.
    if ($ret) {
      if ($ret=="noservoo") {
	$context['erreur']="Aucun ServOO n'est configur&eacute; pour r&eacute;aliser la conversion. Vous pouvez faire la configuration dans les options du site (Administrer/Options)";
      } else {
	$context['erreur']=utf8_encode("Erreur renvoyée par le ServOO: \"$ret\"");
      }
      break;
    }

    if ($msg) { echo "convert: ",time()-$t; flush(); }
    //
    // regarde si le fichier est zipper
    //
    $fp=fopen($fileconverted,"r") or die("le fichier $fileconverted ne peut etre ouvert");
    $cle=fread($fp,2);
    if ($cle=="PK") {
      if ($msg) { echo "<li>Decompresse le fichier zippe<br>"; flush(); }

      if ($unzipcmd && $unzipcmd!="pclzip") { // unzip cmd
	$filesinarchive=preg_split("/\s*\r?\n\s*/",`$unzipcmd -Z -1 $fileconverted`,-1,PREG_SPLIT_NO_EMPTY);
	#die("la: ".join(" ",$filesinarchive));
      } else { // pclzip solution
	require($home."pclzip.lib.php");
	$archive=new PclZip($fileconverted);
	$archivecontent=$archive->listContent();
	$filesinarchive=array();
	foreach ($archivecontent as $file) { 
	  if ($file['status']=="ok") array_push($filesinarchive,$file['filename']); 
	}
      }
      $extractfiles=array();
      // look for the files. Check the extension.
      $xhtmlfile="";
      foreach ($filesinarchive as $file) {
	if (preg_match("/\.xhtml$/",$file)) { // xhtml
	  $xhtmlfile=$tmpdir."/".basename($file);
	  array_push($extractfiles,$file); 
	}
	if (preg_match("/\.(jpg|gif|png)$/",$file)) 	  // image
	  array_push($extractfiles,$file);
      }
      if (!$xhtmlfile) die("ERROR: xhtml file not found in the archive");

      if ($unzipcmd && $unzipcmd!="pclzip") { // unzip cmd
	$filestoextract=escapeshellcmd("$fileconverted ".join(" ",$extractfiles));
	$ret=`$unzipcmd -qjo -d $tmpdir $filestoextract 2>&1`;
	if ($ret) { $context[erreur]=utf8_encode("Erreur renvoyée par la commande unzip: $ret"); break; }
      } else {
	$archive->extract(PCLZIP_OPT_PATH,$tmpdir,PCLZIP_OPT_REMOVE_ALL_PATH);
      }


      @unlink($fileconverted);
      // setup the rights even if it's temporary
      $fileconverted=$xhtmlfile;
      foreach ($extractfiles as $file) {
	chmod($tmpdir."/".basename($file),0666 & octdec($GLOBALS[filemask])); 
      }
    }
    fclose($fp);
    //
    //
    //

    require_once($home."balises.php");
    if ($sortieoo || $sortiexmloo || $sortie) $oo=TRUE;
    
    $err=OO_XHTML($fileconverted,$context);
    if ($err) {
      $context[erreur]="Erreur dans la fonction OO";
      break;
    }
    if ($idtache) { // reimportation of an existing document ?
      $row=get_tache($idtache);
    } else {
      $row=array();
    }
      
    $row[fichier]=$fileconverted;
    $row[source]=$source;
    $row[sourceoriginale]=$sourceoriginale;
    // build the import
    $row[importversion]=addslashes($convertretvar[version])."; oochargement $version;";

    if (!$idtache) {
      if ($context[iddocument]) {
	$row[iddocument]=$context[iddocument];
      } else {
	$row[idparent]=$context[idparent];
      }
      $row[idtype]=$context[idtype];
    }
    $idtache=make_tache("Import $file1_name",3,$row,$idtache);

    if ($msg) {
      echo '<br><a href="chkbalisage.php?id='.$idtache.'"><font size="+1">Continuer</font></a>';
      return;
    }

    header("Location: chkbalisage.php?idtache=$idtache");
    return;
  } while (0); // exceptions
}

#$context[sessionname]=$sessionname;
#$context[session]=$session;
#$context[commands]="DWL file1; CVT HTMLLodel-1.0; ZIP all; UPL;";
$context[url]="oochargement.php";


include ($home."calcul-page.php");
calcul_page($context,"oochargement");



// schema 1 de conversion
function convert ($uploadedfile,$destfile)

{
  global $home;

  require_once($home."serveurfunc.php");

  $err=contact_servoo("DWL file1; CVT XHTMLLodel-1.0; ZIP converted; RTN convertedfile;"
		      ,$uploadedfile,$destfile);
  if ($err) return $err;

  return FALSE;
}

function OO_XHTML ($convertedfile,&$context)

{
/**************************
IMPORTANT NOTICE

This function is an horrible hack. Don't modify it.
Will be reimplemented using a proper XML parser.

***************************/

  global $home,$msg;

  $time1=time();

  $file=file_get_contents($convertedfile);

  if ($GLOBALS[sortieoo]) { // on veut la sortie brute
    echo htmlentities($file);
    return true;
  }

  convertHTMLtoUTF8($file);

  // get the CSS definition
  preg_match("/<style\b[^>]*type=\"text\/css\"[^>]*>(.*?)<\/style>/",$file,$result);
  $css=$result[1];


  // tableau search et replace
  $srch=array(); $rpl=array();

  array_push($srch,
	     "/.*<body\b[^>]*>/s",
	     "/<\/body>.*/s");
  array_push($rpl,"","");
  
  // styles transparents
  // on efface tout ce qu'il y a entre.
  array_push($srch,"/<(r2rc?:$GLOBALS[stylestransparents])\b([^>]*)>.*?<\/\\1>/");
  array_push($rpl,"");

  // remove empty paragraph, and the empty tafs
  array_push($srch,"/<p\b[^>]*\/>/","/<r2r:([^>]+)>\s*<\/r2r:\\1>/");
  array_push($rpl,"","");


  // transform FAB
  if ($context[lodeltags]) {
    array_push($srch,"/<\/?r2rc?:[^>]+>/","/\[(\/?)!--LODEL:(\w+)--\]/");
    array_push($rpl,"","<\\1r2r:\\2>");
  }

  // remove ww-styles
  array_push($srch,"/(<\/?r2r:)ww-([^>]+>)/");
  array_push($rpl,"\\1\\2");


  //
  // all the regexp in the following should be rewritten in a proper parser !
  //
  
  $translations=array("r2r:notesdebasdepage"=>"r2r:notebaspage",
		      "r2r:footnote(?:text)?"=>"r2r:notebaspage",
		      "r2r:endnote(?:text)?"=>"r2r:notefin",
		      "r2r:corpsdetexte\w*"=>"r2r:texte",
		      "r2r:bodytext"=>"r2r:texte",
		      "r2r:textbody"=>"r2r:texte",
		      "r2r:introduction"=>"r2r:texte",
		      "r2r:conclusion"=>"r2r:texte",
		      "r2r:normal"=>"r2r:texte",
		      "r2r:default"=>"r2r:texte",
		      "r2r:normal\s*(web)"=>"r2r:texte",
		      "r2r:standard"=>"r2r:texte", 
		      "r2r:bloccitation"=>"r2r:citation",
		      "r2r:quotations"=>"r2r:citation",
		      "r2r:titleuser"=>"r2r:title",
		      "r2r:subtitleuser"=>"r2r:subtitle",
		      "r2r:heading"=>"r2r:title",

		      "r2rc:titre\d+"=>""
		      );

  
  foreach ($translations as $k=>$v) {
    array_push($srch,"/<$k\b([^>]*)>/","/<\/$k\b([^>]*)>/");
    if ($v) {
	array_push($rpl,"<$v\\1>","</$v\\1>");
    } else {
	array_push($rpl,"","");
    } 
    if (substr($k,0,4)=="r2r:") {
      array_push($srch,"/(<p\b[^>]+class\s*=\s*)\"".substr($k,4)."\"/i");
      array_push($rpl,"\\1\"".substr($v,4)."\"");
    }
  }

  // conversion des balises de sections
  array_push($srch,
	     "/<r2r:(?:heading|titre|title)(\d+\b([^>]*))>/",
	     "/<\/r2r:(?:heading|titre|title)(\d+)>/");
#	     "/<h(\d+)>/",
#	     "/<\/h(\d+)>/");

  array_push($rpl,
	     "<r2r:section\\1>",
	     "</r2r:section\\1>");
#	     "<r2r:section\\1>\\0",
#	     "\\0</r2r:section\\1>");
  // clean the section


/*
  // first part of the puces processing
  array_push($srch,
	     "/<li>\s+(<r2r:puces?".">)/", # remove space between li and puces (need for the looking before)
	     "/(?<!<li>)(<r2r:puces?".">)/", # look for puce not preceded by a <li>
	     "/(<\/r2r:puces?".">)(?!\s*<\/li>)/"); # look for puce not followed by a <li>
  array_push($rpl,
	     "<li>\\1",
	     "<li>\\1",
	     "\\1</li>");
 


#  // traitement un peu sale des footnote et les endnote. On efface les paragraphes marques footnote et on remet sur la base du div
#  array_push($srch,"/<\/?r2r:notebaspage>/","/<div id=\"sdfootnote\d+\">.*?<\/div>/i");
#  array_push($rpl,"","<r2r:notebaspage>\\0</r2r:notebaspage>");
#  array_push($srch,"/<\/?r2r:notefin>/","/<div id=\"sdendnote\d+\">.*?<\/div>/i");
#  array_push($rpl,"","<r2r:notefin>\\0</r2r:notefin>");

  // remonte les balises r2r
#### array_push($srch,"/((?:<\w+[^>]*>\s*)+)<r2r:([^>]+)>(.*?)<\/r2r:\\2>\s*((?:<\/\w+[^>]*>\s*)+)/");
####  array_push($rpl,"<r2r:\\2>\\1\\3\\4</r2r:\\2>");

  // second part of the puces processing
  // remonte les balises r2r au dessus des ul et li
  array_push($srch,"/((?:<(?:ul|li|ol)\b[^>]*>\s*)+)<r2r:([^>]+)>(.*?)<\/r2r:\\2>\s*((?:<\/(?:ul|li|ol)>\s*)+)/");
  array_push($rpl,"<r2r:\\2>\\1\\3\\4</r2r:\\2>");


  // third part of the puces processing
  array_push($srch,
	     "/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
	     "/(<r2r:puces?".">)(?!<ul\b[^>]*>)/", // puces not followed by ul
	     "/<\/ul>\s+(<\/r2r:puces?".">)/", // remove space between puce and closing ul (for the next regexp)
	     "/(?<!<\/ul>)(<\/r2r:puces?".">)/" // puces not precessed by a ul
	     );
  array_push($rpl,
	     "",
	     "\\1<ul>",
	     "</ul>\\1",
	     "</ul>\\1"
	     );
*/

#  $file=preg_replace ($srch,$rpl,$file);
#  echo htmlentities($file); die();


  // modifie les styles avec (user) ou (web)
  array_push($srch,"/(<\/?r2r:[^>]+?)\((user|web)\)/","/(<p [^>]*class=\"[^\"]+?)\((user|web)\)(\"[^>]*)>/");
  array_push($rpl,"\\1","\\1\\3>");

#  $file=preg_replace ($srch,$rpl,$file);
#  echo htmlentities($file); exit;


  //
  // standardize the foot and end notes.
  //
  // footnotes definition
  array_push($srch,'/(?:<r2r:\w+>\s*)?<p\b[^>]*>\s*<span\b[^>]*>\s*<a\s+([^>]*?)\s+class="FootnoteSymbol"([^>]*)>(.*?)<\/a>\s*<\/span>(.*?)<\/p>(?:\s*<\/r2r:\w+>)?/s'); # declaration of the footnote
  array_push($rpl,'<r2r:notebaspage><div class="footnotebody"><a class="footnotedefinition" \\1 \\2>\\3</a>\\4</div></r2r:notebaspage>'); # declaration of the footnote

  // endnotes definition
  array_push($srch,
	     '/(?:<r2r:\w+>\s*)?<p\b[^>]*>\s*<span\b[^>]*>\s*<a\s+([^>]*?)\s+class="EndnoteSymbol"([^>]*)>(.*?)<\/a>\s*<\/span>(.*?)<\/p>(?:\s*<\/r2r:\w+>)?/s'); # declaration of the endnote

  array_push($rpl,'<r2r:notefin><div class="endnotebody"><a class="endnotedefinition" \\1 \\2>\\3</a>\\4</div></r2r:notefin>'); # declaration of the endnote


#  array_push($srch,
#	     '/<p\b[^>]*>\s*<span\b[^>]*>\s*<a\s+(href="[^"]*"\s+id="[^"]*")\s+class="FootnoteSymbol">(.*?)<\/a>\s*<\/span>(.*?)<\/p>/s', # declaration of the footnote
#	     '/<span\b[^>]*class="footnotereference"[^>]*>\s*<span class="Footnoteanchor">\s*<a\s*(href="[^"]*"\s+id="[^"]*")>(.*?)<\/a>\s*<\/span>\s*<\/span>/' ); # call to the footnote
#  array_push($rpl,
#	     '<div class="footnotebody"><a class="footnotedefinition" \\1>\\2</a>\\3</div>', # declaration of the footnote
#	     '<a class="footnotecall" \\1>\\2</a>'); # call to the footnote
#  // endnotes
#  array_push($srch,
#	     '/<p\b[^>]*>\s*<span\b[^>]*>\s*<a\s+(href="[^"]*"\s+id="[^"]*")\s+class="EndnoteSymbol">(.*?)<\/a>\s*<\/span>(.*?)<\/p>/s', # declaration of the endnote
#	     '/<span\b[^>]*class="endnotereference"[^>]*>\s*<span class="Endnoteanchor">\s*<a\s*(href="[^"]*"\s+id="[^"]*")>(.*?)<\/a>\s*<\/span>\s*<\/span>/' ); # call of the endnote
#  array_push($rpl,
#	     '<div class="endnotebody"><a class="endnotedefinition" \\1>\\2</a>\\3</div>', # declaration of the endnote
#	     '<a class="endnotecall" \\1>\\2</a>'); # call of the endnote
#

  // autre chgt
  array_push($srch,
	     "/<object>.*?<\/object>/is",
#	     "/<span\s*lang=\"[^\"]*\">(.*?)<\/span>/i", # enleve les span lang
	     "/(<a\s[^>]*)\/>/",
	     "/<a\s+id=\"Image[^\"]+\"[^>]*\/>/i",
#	     "/(<img\b[^>]+)border=\"?\d+\"?([^>]*>)/", # efface les border
#	     "/(<img\b[^>\/]+)\/?".">/i", # met border="0"
	     "/(<img\b[^>\/]+style=\"[^\"]*)width:\s*1.0ch;height:\s*1.0ch;?/", # remove the 1.0ch 1.0ch produce by some OO (?? not clear).
	     "/<\/?col\b[^>]*>/",
	     "/(<[^>]+)\sxml:lang=\"[^\"]*\"/"
	     );

  array_push($rpl,
	     "",
#	     "\\1",
	     "\\1></a>",	     
	     "",
#	     "\\1\\2",
#	     "\\1border=\"0\" />",
	     "\\1",
	     "",
	     "\\1"
	     );


  $time=time();
  // let's apply all these changes
  $file=preg_replace ($srch,$rpl,$file);
#  echo htmlentities($file); exit;


  // function for cleaning the sections and the links
  function cleanPandSPAN($result) {
#    print_r($result);
#    echo "----<br/>\n";
    // check for p and span tags
    foreach(array("p","span") as $tags) {
      if (preg_match("/^\s*<$tags\b[^>]*>(.*)<\/$tags>\s*$/",$result[3],$result2)) {
	if (!preg_match("/<\/$tags>.*<$tags\b[^>]*>/",$result2[1])) { // well, there is not close and open inside.
	  $result[3]=$result2[1];
	}
      }
    }
    return "<$result[1]$result[2]>$result[3]</$result[1]>";
  } // end of function for cleaning the sections and the links

  // let's clean the sections
  $file=preg_replace_callback(array("/<(r2r:section\d+)()>(.*?)<\/\\1>/s",
				    "/<(a)\b([^>]*)>(.*?)<\/\\1>/s"),
			      "cleanPandSPAN",$file);

  // let's clean the list
  $file=cleanList($file);
  // ok, cleaned

  //  die(htmlentities($file));

  if (!traite_tableau2_xhtml($file)) {     $context[erreur_stylestableaux]=1; return true; }
  $file=traite_multiplelevel($file,$GLOBALS[multiplelevel]);


  // supprime les puces?
  $file=preg_replace ("/<\/?r2r:puces?>/","",$file);

  if ($msg) { echo "<li>temps regexp: ".(time()-$time)." s<br>\n"; }

  // desuet
  // enleve les couples de balises r2r.
  $file=traite_couple($file);

  // recupere les styles conteneurs (ceux qui ont des parentheses)
  $file=preg_replace (array("/(<r2r:\w+)\((\w+)\)>/","/(<\/r2r:\w+)\((\w+)\)>/",
			    "/(<\/?r2rc:\w+)\(\w+\)[^>]*>/"),
		      array("<r2r:\\2>\\1>","\\1></r2r:\\2>","\\1>"),
		      $file);


  // remove any wrong caracteres in the r2r
  $file=preg_replace ("/(<\/?r2rc?:)\w*(&amp;\w*)+>/","\\1invalidcharacters>",$file);

  //
  // add the "official" beginning and end of the xml file
  //
    $file='<r2r:document xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">'.$file.'</r2r:document>';


    // clean the CSS class by inlining the definition
    //
    if ($css) {
      $styles=array();
      // build a table of the style
      $cssarr=preg_split("/\{([^\}]*)\}/",$css,-1,PREG_SPLIT_DELIM_CAPTURE);
      for($i=0; $i<count($cssarr); $i+=2) {	
	$style=trim($cssarr[$i]);
	if (preg_match("/^\w+\.[PT]\d+$/",$style)) $styles[strtolower($style)]=$cssarr[$i+1]." "; // add a space to be sure it is TRUE.
      }
      #print_r($styles);
      if ($styles) {
	// look for the style in the file, and replace by the style
	$file=preg_replace('/((<(\w+)\b[^>]+)class="([PT]\d+)")/e','($styles[strtolower("\\3.\\4")]) ? "\\2style=\"".trim($styles[strtolower("\\3.\\4")])."\"" : "\\1" ',$file);
      }
    }

    //
#    $file=preg_replace(array('/(<(?:p|span)\s+lang="ar-\w\w"\s+style=")/',
#			     '/(<(?:p|span)\s+lang="ar-\w\w")(?!\s+style=")/'),
#		       array('\\1direction: rtl;align: right;',
#			     '\\1 style="direction: rtl;align: right;"'),$file);
		 


    //
    // convert graphical styles in XHTML tags
    // and do other cleaning:
    // 1/ remove lang attribute
    // 2/ treat the footnote (that's not easy)
    //
    $arr=preg_split("/(<\/?span\b[^>]*>)/",$file,-1,PREG_SPLIT_DELIM_CAPTURE);
    $count=count($arr);
    $stack=array();
    $innote="";$nbspan=0;
    for($i=1; $i<$count; $i+=2) {
      #echo htmlentities($arr[$i]),"</br>";
      if ($arr[$i]=="</span>") { // closing span
	if ($innote) { // in a note ?
	  $nbspan--; // let decrease the number of span we are in
	  #if ($nbspan==0) { $arr[$i-1].="[[OUTNOTE]]"; $innote=""; }// out of the note.
	  if ($nbspan==0) $innote="";// out of the note.
	}
	$arr[$i]=array_pop($stack);
      } elseif (!$innote) { // opening span
	$attributs=preg_split('/"/',substr($arr[$i],5,-1)); // there should not be any espaced "
	#print_r($attributs);
	$close="";
	$open="";
	$newattributs="";
	for($j=0; $j<count($attributs)-1; $j+=2) {
	  // attribut style
	  if (preg_match('/^\s*style\s*=\s*$/',$attributs[$j])) {
	    // there is a style to process.
	    // there is really a style to processed
	    list($moreopen,$moreclose,$style)=convertCSSstyle($attributs[$j+1]);
	    $open.=$moreopen;
	    $close=$moreclose.$close;
	    if ($style) { // still some remaining styles ?
	      $newattributs.=' style="'.$style.'"';
	    }
	  } // end of attribut style
	  // attribut lang
	  elseif (preg_match("/^\s*lang\s*=\s*$/",$attributs[$j])) {
	    // delete
	    // nothing to do
	  } // end of attribut lang
	  // attribut class
	  elseif (preg_match("/^\s*class\s*=\s*$/",$attributs[$j])) {
	    if (preg_match("/^\s*((?:foot|end)note)/i",$attributs[$j+1],$result)) { // on a une footnote !
	      $innote=strtolower($result[1]);
	      $close=""; $open="";
	      $nbspan=1;
	      $newattributs="";
	      #$arr[$i+1]="[[ENTER NOTE]]".$arr[$i+1];

	      break; // go out, this span must be removed.
	    }
	  } // end of attribut class
	  else {
	    $newattributs.=" ".$attributs[$j].'="'.$attributs[$j+1].'"';
	  }
	} // process all the attributs
	if ($newattributs) {
	  $arr[$i]="<span $newattributs>";
	  $close.="</span>";
	} else {
	  $arr[$i]="";
	}
	$arr[$i].=$open;
	array_push($stack,$close);
      }// opening span and note innote
      else { // opening span and innote
	// delete the span
	$arr[$i]="";
	array_push($stack,"");
	$nbspan++;
      }
      if ($innote) { // are we in a note ?
	// look for <a> tags
	#$arr[$i+1].="[[INNOTE$nbspan]";
	if ($arr[$i+1]) {
	  $arr[$i+1]=preg_replace("/<a\b([^>]+>)/",
				  '<a class="'.$innote.'call"\\1',$arr[$i+1]);
	}
      }
    }
    $file=join("",$arr);


    //
    // check the document is well-formed
    //
    include ($home."checkxml.php");
    if (!checkstring($file)) { echo "fichier: $fileconverted"; return true; }

    function img_copy($imgfile,$ext,$count,$rand) {
      global $tmpdir;
      if (!file_exists($tmpdir."/".basename($imgfile))) return false;

      $newimgfile="../../docannexe/tmp".$rand."_".$count.".".$ext;
      rename($tmpdir."/".basename($imgfile),$newimgfile) or die ("impossible de copier l'image $imgfile dans $newimgfile");
      return $newimgfile;
    }
    include_once ($home."func.php");
    copy_images($file,"img_copy",rand());

    if ($GLOBALS[sortie]) die (htmlentities($file));

  // ecrit le fichier
  //$newname="$convertedfile-".rand();
  //if (!writefile("$newname.html",$file)) return FALSE;
    if (!writefile($convertedfile,$file)) return true; // ecrase le fichier sortie de la conversion.

  if ($msg) { echo "Temps total:",time()-$time1,"<br><br>"; flush(); }

  return false; // ok
  //  return $newname;
}




function quote_attribut_strtolower($text)

{ 
  $i=strpos($text,"=");
  if ($i===FALSE) return strtolower($text);

  do {
    // attribut suivant
    $i++;
    $c=substr($text,$i,1);
    $indoublequote=0;
    $adddoublequote=0;
    while ($c!="") {
      if ($c=='\\') { $i+=2;   $c=substr($text,$i,1); next; } // quote
      if ($c==" " || $c==">") {
	if ($adddoublequote) {	  
	  $newtext.=substr($text,0,$i).'"';
	  break;
	} else {
	  $i++; // saute l'espace
	  $c=substr($text,$i,1); 
	  next; // espace
	}
      }
      if ($c=='"') { // toggle le doublequoteflags
	if ($indoublequote) {
	  // on sort, donc on cherche l'attribut suivant.
	  $newtext.=substr($text,0,$i);
	  break;
	} else {
	  $newtext.=strtolower(substr($text,0,$i)); 
	  $text=substr($text,$i);
	  $i=0; 
	  $indoublequote=1;	  
	}
      } elseif (!$indoublequote) { // il manque le quote
	$newtext.=strtolower(substr($text,0,$i)).'"'; 
	$text=substr($text,$i);
	$i=0; $indoublequote=1; $adddoublequote=1;
      }
      $i++;
      $c=substr($text,$i,1);
    }
    $text=substr($text,$i);
    $i=strpos($text,"=");
  } while ($i!==FALSE);

  return $newtext.strtolower($text);
}

////////////////////////////////////////////////////////


/* plus utiliser ici => servoo
function removeaccentsandspaces($string){
return strtr(
 strtr(utf8_decode(preg_replace("/[\s_\r]/","",$string)),
  '¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
  'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
  '¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}
*/


function traite_tableau2_xhtml(&$text)

{
  // nouveaux traitement des tableaux.
  // decoupe les tableaux
  $arr=preg_split("/(<table\b|<\/table>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr); $level=0; $err=0; $istart=0;
  #echo "count=$count<br>";
  if ($count==1) return TRUE;
  for($i=1; $i<$count; $i+=2) {
    if ($arr[$i]=="</table>") { // ferme
      $level--;
      if ($level==0) {
	$arr[$i].="</r2r:$laststyle>";
	$arr[$istart]="<r2r:$laststyle>".$arr[$istart];
	$laststyle=""; // on vient de sortir du tableau
      }
    } else { // on ouvre
      if ($level==0) $istart=$i;
      $level++;
    }

//    // before we raised an error, now... we don't care.
//    if ($level && preg_match_all("/<\/r2r:(\w+)>/",$arr[$i+1],$results,PREG_SET_ORDER)) {
//      foreach($results as $result) { // cherche si c'est partout le meme style
//	if ($laststyle && $laststyle!=$result[1]) { 
//	  $err=1;
//	  #die($laststyle." ".$result[1]);
//	  break 2;
//	}
//	$laststyle=$result[1];
//	break
//      }
//      $arr[$i+1]=preg_replace("/<\/?r2r:$laststyle>/","",$arr[$i+1]); // ok, on efface alors
//    }
//    // before we raised an error, now... we don't care.
    if ($level && preg_match("/<\/r2r:(\w+)>/",$arr[$i+1],$result)) {
      if (!$laststyle) $laststyle=$result[1];
      $arr[$i+1]=preg_replace("/<\/?r2r:\w+>/","",$arr[$i+1]); // erase all the styles in the table.
    }
  }

  if ($err) {    return FALSE;  }

  $text=join("",$arr);

  return TRUE;
}


/*
function traite_tableau2(&$text)

{
  // nouveaux traitement des tableaux.
  // decoupe les tableaux
  $arr=preg_split("/(<table\b|<\/table>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr); $level=0; $err=0;
  #echo "count=$count<br>";
  if ($count==1) return TRUE;
  for($i=1; $i<$count; $i+=2) {
    if ($arr[$i]=="</table>") { // ferme
      $level--;
      if ($level==0) $laststyle=""; // on vient de sortir du tableau
    } else { // on ouvre
      $level++;
    }
    if ($level && preg_match_all("/<\/r2r:(\w+)>/",$arr[$i+1],$results,PREG_SET_ORDER)) {
      foreach($results as $result) { // cherche si c'est partout le meme style
	if ($laststyle && $laststyle!=$result[1]) { $err=1; break 2; }
	$laststyle=$result[1];
      }
      $arr[$i+1]=preg_replace("/<\/?r2r:$laststyle>/","",$arr[$i+1]); // ok, on efface alors
    }
  }
  if ($err) {    return FALSE;  }

  $text=join("",$arr);
  return TRUE;
}
*/


function traite_couple(&$text)

{
  global $home;

  // determine les $virgule_tags
  require_once($home."connect.php");
  $result=mysql_query("SELECT style FROM $GLOBALS[tp]typeentrees WHERE statut>0") or die(mysql_error());
  while (list($style)=mysql_fetch_row($result)) $virgule_tags_arr[]=$style;
  $result=mysql_query("SELECT style FROM $GLOBALS[tp]typepersonnes WHERE statut>0") or die(mysql_error());
  while (list($style)=mysql_fetch_row($result)) $virgule_tags_arr[]=$style;


  // determine les $multiparagraphe_tags
  $result=mysql_query("SELECT style,type FROM $GLOBALS[tp]champs WHERE statut>0") or die(mysql_error());
  $multiparagraphe_tags_arr=array();
  while (list($style,$type)=mysql_fetch_row($result)) {
    if ($type=="mltext") { // text multilingue
      require_once($home."champfunc.php");
      $multiparagraphe_tags_arr=
	array_merge($multiparagraphe_tags_arr,decode_mlstyle($style));
    } else {
      array_push($multiparagraphe_tags_arr,$style);
    }
  }

  $virgule_tags=join("|",$virgule_tags_arr);
  $multiparagraphe_tags=join("|",$multiparagraphe_tags_arr);

  $balisere="(?:$multiparagraphe_tags)(?:\(\w+\))?"; # gere les cas avec parenthese
  return preg_replace (
		       array(
			     "/<\/r2r:($virgule_tags)>[\s\r]*<r2r:\\1(\s+[^>]+)?>/i",  # les tags a virgule
			     "/<\/r2r:($balisere)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|[\s\r])*)<r2r:\\1(?:\s[^>]*)?>/is", # les autres tags    
			     ),
		       array(
			     ",",
			     "",
			     ),
		       $text);
}



function convertCSSstyle ($style) {

  $styles=preg_split("/\s*;\s*/",$style);
  $count=count($styles);

  $simpleconversions=array(
		       "font-style:italic"=>"em",
		       "font-weight:bold"=>"strong",
		       #"text-decoration:underline"=>"u", pas XHTML strict
		       #"text-decoration:line-through"=>"strike"
		       );

  for($j=0; $j<$count; $j++) {
    // simple conversion
    if ( ($t=$simpleconversions[$styles[$j]]) ) {
      $open.="<$t>";
      $close="</$t>".$close;
      $styles[$j]="";
    }
    // indice and superscript
    if (preg_match("/^font-size:(\d\d?%|\d+(\.\d)?pt)$/",$styles[$j]) && $styles[$j+1]) {
      if ($styles[$j+1]=="vertical-align:sub") {
	$open.="<sub>";
	$close.="</sub>".$close;
	$styles[$j]=$styles[$j+1]="";
      } elseif (preg_match("/vertical-align:(super|\d\d?%)/",$styles[$j+1])) {
	$open.="<sup>";
	$close="</sup>".$close;
	$styles[$j]=$styles[$j+1]="";
      }
    }
    if ($styles[$j]) $styles[$j].=";";
  }
#  print_r($styles);
#    echo "<br/>";
  return array($open,$close,trim(join(" ",$styles)));
}


// les balises multiplelevel. Restructure la stylisation plate de Word en une structure a plusieurs niveaux (2 niveaux en general)

// > signifie que cette balise se ratache avec celle d'apres
// rien signifie que cette balise s'entoure de la balises donner dans le tableau

// * signifie toutes les balises
// balise: signifie que cette balises

function traite_multiplelevel($text,$multiplelevel)

{
  $search=array(); $rpl=array();

  foreach ($multiplelevel  as $k=>$v) {
    if (is_array($v)) {
      $text=traite_multiplelevel($text,$v);
      continue;
    }

    $balouvrante="<r2r:$k(?:\b[^>]+)?>";
    $balfermante="<\/r2r:$k>";

    // determine ce qu'il faut faire
//    if (preg_replace("/^>/","",$v)) { $dir="apres"; } 
//    elseif (preg_replace("/^</","",$v)) { $dir="avant"; }
//    else { $dir=""; };
    if ($v[0]==">") { $dir="apres"; $v=substr($v,1); } 
    elseif ($v[0]=="<") { $dir="avant"; $v=substr($v,1); } 
    else { $dir=""; };

    if ($v=="*") $v="\w+";

    if ($dir=="apres") { // entoure par la balise qui suit
      array_push($search,"/((?:$balouvrante.*?$balfermante"."[\s\n\r]*)*)(<r2r:$v\b[^>]*>)/is");
      array_push($rpl,"\\2\\1"); // permute le bloc avec la balise qui suit
    } elseif ($dir=="avant") {
      array_push($search,"/(<\/r2r:$v\b[^>]*>)[\s\n\r]*($balouvrante.*?$balfermante)/is");
      array_push($rpl,"\\2\\1"); // permute le bloc avec la balise qui precede
    } else { // entoure par la balise donne dans $v
      array_push($search,"/$balouvrante/i","/$balfermante/i");
      array_push($rpl,"<r2r:$v>\\0","\\0</r2r:$v>");
    }
  }
  //die (join(" ",$search)."<br>".join(" ",$rpl));
  return preg_replace ($search,$rpl,$text);
}


function cleanList($text)

{
  $arr=preg_split("/(<\/?(?:ul|ol)\b[^>]*>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr);
  $arr[0]=addList($arr[0]);
  $inlist=0; $start=0;
  for($i=1; $i<$count; $i+=2) {
    if ($arr[$i][1]=="/") { // closing
      $inlist--;
      if ($inlist==0) { $arr[$i].="</r2r:puces>"; } // end of a list
    } else { // opening
      if ($inlist==0) { $arr[$i]="<r2r:puces>".$arr[$i]; } // beginning of a list
      $inlist++;
    }
    if ($inlist>0) { // in a list
      //      $arr[$i+1]=preg_replace("/<\/?(?:p|div|r2r:puces?)\b[^>]*>/"," ",$arr[$i+1]);
      $arr[$i+1]=preg_replace("/<\/?r2r:puces?\b[^>]*>/"," ",$arr[$i+1]);
    } else { // out of any list
      $arr[$i+1]=addList($arr[$i+1]);
    }
  }
  $text=join("",$arr);

  return preg_replace("/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
		      "",$text);
}

function addList($text)

{ // especially for RTF file where there are some puces but no li
  return preg_replace(array(
			   "/<r2r:(puces?)>(.*?)<\/r2r:\\1>/", // put li
			   "/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
			   "/(<r2r:puces?>)/",  // add ul
			   "/(<\/r2r:puces?>)/" // add /ul
			   ),
		     array("<r2r:\\1><li>\\2</li></r2r:\\1>",
			   "",
			   "\\1<ul>",
			   "</ul>\\1"
			   ),$text);

}


?>
