<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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


// This fonction convert a file (can be zipped) in a format readable by OO 
// into the XHTML+Lodel information.
// Use OO to convert original file into SXW and to convert the SXW.
// And use writer2latex to convert the SXW into the XHTML

require_once($home."func.php");

function XHTMLLodel ($uploadedfile,$msg=TRUE)

{
  $errfile="$uploadedfile.err";

  //
  // regarde si le fichier est zipper
  //

  list($issxw,$tounlink)=checkZippedFile($uploadedfile);


  if (!$issxw) {
    if ($msg) {
      echo "<h2>Conversions du fichier importe par OO</h2>";
      echo "<p>En cas d'arret avant la fin de la 2eme conversion veuillez envoyer les informations sur lodel-devel</p>";
      flush();
    }
    chmod($uploadedfile,0644); // temporaire, il faut qu'on gere le probleme des droits
    // cette partie n'est pas secur du tout. Il va falloir reflechir fort.
    if ($msg) {  echo "<li>1ere conversion format initial->SXW <br>\n";flush(); $time1=time(); }
    
    runDocumentConverter($uploadedfile,"sxw");

    if ($tounlink) unlink($uploadedfile); // ne detruit pas l'original
    // solution avec unzip, ca serait mieux avec libzip
    // dezip le fichier content

    if (!file_exists($uploadedfile.".sxw")) die("ERROR: first OO conversion failed");
  } else {
    // change le nom
    if (!@copy($uploadedfile,$uploadedfile.".sxw")) die("ERROR: copy failed of $uploadedfile failed");
    if ($tounlink) @unlink($uploadedfile); // detruit l'original
  }

  if ($msg) {
    echo "temps:",time()-$time1," s<br>";
    echo "<li>unzip le fichier SXW<br>\n";flush();
  }
  $tmpdir=$uploadedfile."_dir";
  mkdir($tmpdir,0700);

  // if the file is outputted by the OO user, it is not necessary writable.
#  if (!is_writeable($uploadedfile.".sxw")) {
    // copie pour avoir les droits d'ecriture
    if (!@copy($uploadedfile.".sxw",$uploadedfile."-second.sxw")) die("ERROR: copy failed of $uploadedfile.sxw failed");
    @unlink($uploadedfile.".sxw"); // efface si on peut.
    $uploadedfile.="-second";
#  }

  // uncompress content.xml and styles.xml
  exec_unzip("-q -d $tmpdir $uploadedfile.sxw content.xml styles.xml",$errfile);
  $content=file_get_contents("$tmpdir/content.xml");
  $styles=file_get_contents("$tmpdir/styles.xml");
#  die("$tmpdir    ".htmlentities($content));



  if ($msg) {
    echo "<br>";
    echo "<li>extraction des styles du fichier content.xml contenu dans le SXW et ajout dans styles.xml<br>\n";flush();
  }
  // modifie les fichiers content.xml et styles
  processcontentXHTML($content,$styles);

  // ecrit le fichier content.xml
  if (!writefile("$tmpdir/content.xml",$content)) die("ERROR: writing $tmpdir/content.xml file failed");
  if (!writefile("$tmpdir/styles.xml",$styles)) die("ERROR: writing $tmpdir/styles.xml file failed");

  if ($msg) {
    echo "<li>Reinsertion des nouveaux fichiers content.xml et styles.xml dans le SXW<br>\n";flush();
  }
  exec_zip("-q -j  $uploadedfile.sxw $tmpdir/content.xml $tmpdir/styles.xml",$errfile);
#  die("tmpdir:$tmpdir");
  unlink("$tmpdir/content.xml");
  unlink("$tmpdir/styles.xml");
  rmdir("$tmpdir");

  // conversion en XHTML avec writer2latex
  if ($msg) { echo "<br><li>2nd conversion SXW->HTML<br>\n";flush(); }
  $time2=time();

  runWriter2Latex($uploadedfile.".sxw","xhtml");
  unlink($uploadedfile.".sxw");
  if ($msg) {
    echo "temps:",time()-$time2," s<br>";
    echo "fin<br>\n";flush();
  }

  // postprocessing du fichier XHTML
  
  $xhtmlfile=$uploadedfile.".html";
  if (!file_exists($xhtmlfile)) die("ERROR: second conversion failed");


  #$xhtml=file_get_contents($xhtmlfile);
  $xhtmllines=file($xhtmlfile);
  array_walk($xhtmllines,create_function('&$text',' $text=strtr(ltrim($text),array("\n"=>"","\r"=>""));')); // ltrim each line
  $xhtml=join("",$xhtmllines);
  unset($xhtmllines); // save memory

  postprocesscontentXHTML($xhtml,$styles);
  $xhtmlfile=$uploadedfile.".xhtml";
  writefile($xhtmlfile,$xhtml);

  //
  // recupere le nom des images associees
  //
 
#  preg_match_all('/<img\s+src="('.preg_quote(basename($uploadedfile)).'-img\d+\.[^\."]+)"/',$xhtml,$results,PREG_PATTERN_ORDER);
  preg_match_all('/<img\s+src="('.preg_quote($uploadedfile,"/").'-img\d+\.[^\."]+)"/',$xhtml,$results,PREG_PATTERN_ORDER);

  $convertedfiles=array($xhtmlfile);
#  $dir=dirname($uploadedfile);
#  if (!$dir) $dir=".";

#  die ("ERROR: ".join(" ",$results[1]));

  if ($results[1]) {
    foreach ($results[1] as $imgfile) 
#      array_push($convertedfiles,$dir."/".$imgfile);
      array_push($convertedfiles,$imgfile);
  }
#  die("ERROR: $uploadedfile ".join(" ",$convertedfiles));

  return $convertedfiles;
}


function OpenOfficeorg($uploadedfile,$extension,$msg=TRUE)

{
  //
  // regarde si le fichier est zipper
  //

  list($issxw,$tounlink)=checkZippedFile($uploadedfile);

  if ($msg) {
    echo "<h2>Conversions du fichier importe par OO</h2>";
    echo "<p>En cas d'arret avant la fin de la 2eme conversion veuillez envoyer les informations sur lodel-devel</p>";
    flush();
  }
  chmod($uploadedfile,0644); // temporaire, il faut qu'on gere le probleme des droits
  // cette partie n'est pas secur du tout. Il va falloir reflechir fort.
  if ($msg) {  echo "<li>conversion au format $extension<br>\n";flush(); $time1=time(); }
    
  runDocumentConverter($uploadedfile,$extension);
  if ($tounlink) unlink($uploadedfile); // ne detruit pas l'original
  // solution avec unzip, ca serait mieux avec libzip
  // dezip le fichier content

  $uploadedfile.=".".$extension;
  
  if (!file_exists($uploadedfile)) die("ERROR: first OO conversion failed");

  return array($uploadedfile);
}



//
// fonction qui lance le java qui communique avec OO
//


function runDocumentConverter($filename,$extension)

{
  global $home,
    $servoohost,
    $servooport,
    $openofficeclassespath,
    $javapath;

  $errfile="$filename.err";

  if ($extension=="sxw") {
#    $format="swriter: StarOffice XML (Writer)";
#    $format="writer_web_StarOffice_XML_Writer";
    $format="auto";
  } elseif ($extension=="html") {
    $format="swriter: HTML (StarWriter)";
  } elseif ($extension=="doc") {
    $format="swriter: MS Word 97";
  } elseif ($extension=="rtf") {
    $format="swriter: Rich Text Format";
  } elseif ($extension=="pdf") {
    $format="writer_pdf_Export";
  } else die ("probleme interne");

#  myexec("$javapath/bin/java -classpath \"$openofficeclassespath/jurt.jar:$openofficeclassespath/unoil.jar:$openofficeclassespath/ridl.jar:$openofficeclassespath/sandbox.jar:$openofficeclassespath/juh.jar:".$home."oo/classes\" DocumentConverterSimple \"$filename\" \"$format\" \"$extension\" \"$servoohost\" \"$servooport\"",$errfile,"java script DocumentConverter failed");

  $cmd="$javapath/bin/java -classpath \"$openofficeclassespath/jurt.jar:$openofficeclassespath/unoil.jar:$openofficeclassespath/ridl.jar:$openofficeclassespath/sandbox.jar:$openofficeclassespath/juh.jar:".$home."oo/classes\" DocumentConverterSimple \"$filename\" \"$format\" \"$extension\" \"$servoohost\" \"$servooport\"";


  $descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("file", $errfile, "w") // stderr is a file to write to
			);


  $process = proc_open($cmd, $descriptorspec, $pipes);
  if (!is_resource($process)) die ("ERROR: java execution fails");

  fclose($pipes[0]);  // we don't need to write

  while (!feof($pipes[1])) {
    $ret=stream_select($r = array($pipes[1]), $w = NULL , $e = NULL, DOCUMENTCONVERTER_TIMEOUT);
    if ($ret===FALSE) die("ERROR: cannot stream_select");
    if ($ret===0) {
      // timeout !!
      // stop the java process
      //proc_terminate($process);
      if (defined("DOCUMENTCONVERTER_LOGFILE"))
	error_log("Java/OO timeout on port $servooport\n", 3, DOCUMENTCONVERTER_LOGFILE);
      die("ERROR: java execution timeout");
    }
    fgets($pipes[1], 1024); // fgets but everything is going to the stderr
  }

  fclose($pipes[1]);
  $return_value = proc_close($process);

  if ($return_value) die ("ERROR: java execution fails");
}

//
// fonction qui lance le writer2latex
//

function runWriter2Latex($filename,$extension)

{
  global $home,$javapath;

  $errfile="$filename.err";
#  echo "$errfile $home";

//  // malheureusement pour le moment, il faut faire comme ca:
//  $cwd=getcwd();
//
//  $tmpdir=substr($filename,0,strrpos($filename,"/"));
//  chdir ($tmpdir);
//  myexec("$javapath/bin/java  -jar $cwd/$home/writer2latex/writer2latex.jar -xhtml -config $cwd/$home/writer2latex/default-config.xml $filename 1>&2",$errfile,"java script Writer2Latex failed");
  //  chdir($cwd);


  myexec("$javapath/bin/java  -jar $home/writer2latex/writer2latex.jar -xhtml -config $home/writer2latex/default-config.xml $filename 1>&2",$errfile,"java script Writer2Latex failed");
}


/*
//
// fonction de transformation des styles en balise intermediaire
// Transformation du HTML

function processcontentHTML(&$text)

{
  // recupere les conversions de system
  $convstyle=array();
  preg_match_all('/<style:style style:name="(P[^\"]+)"[^>]+?style:parent-style-name="([^\"]+)"/',$text,$results,PREG_SET_ORDER);
  foreach ($results as $result) $convstyle[$result[1]]=$result[2];

  // la solution est un peu lourdre. Il faudrait regarder si un text:p peut contenir ou pas d'uatre text:p ... ca allegerait bcp bcp le travail

  // solution avec split
  $closere='<\/text:[ph]>';
  $arr=preg_split("/(<text:[ph]\b[^>]*>|$closere)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
#  echo count($arr),"<br>";
#  print_r($arr);
#  exit();
  $count=count($arr);
  $stylestack=array();
  for ($i=1; $i<$count; $i+=2) { // passe tous les delimiteurs
    if (preg_match("/$closere/",$arr[$i])) {
      $style=array_pop($stylestack); // recupere la balise fermante
      $arr[$i]=$style.$arr[$i]; // ajoute la balise fermante
    } else { // ok c'est un open alors
      if (preg_match('/\btext:style-name="([^"]+)"[^\/>]*>/',$arr[$i],$result)) { // est-ce qu'il y a un style
	$style=$result[1];
	if ($convstyle[$style]) $style=$convstyle[$style];
	$arr[$i].='[!--R2R:'.$style.'--]';
	array_push($stylestack,'[!/--R2R:'.$style.'--]');
      } else { // non pas de style.
	array_push($stylestack,"");
      }
    }
  }
#  print_r($arr);
#  exit();
  $text=join("",$arr);

  //
  // process les styles de caracteres
  //
  // recupere les conversion de styles de caracteres
  
  $convstyle=array();
  preg_match_all('/<style:style style:name="(T[^\"]+)"[^>]+?style:parent-style-name="([^\"]+)"/',$text,$results,PREG_SET_ORDER);
  foreach ($results as $result)
    if ($result[2]!="footnote reference")
      $convstyle[$result[1]]=$result[2];

  $srch=array();
  $rpl=array();

  if ($convstyle) {
    // traite les caracteres en T\d
    $stylecre=join("|",array_keys($convstyle));
    array_push($srch,"/(<text:span text:style-name=\"($stylecre)\">)(.*?)(<\/text:span>)/ei");
    array_push($rpl,'"\\1[!--R2RC:".$convstyle["\\2"]."--]\\3[!/--R2RC:".$convstyle["\\2"]."--]\\4"');
  }

  array_push($srch,"/(<text:span text:style-name=\"(?!T\d+\")(\w+)\">)(.*?)(<\/text:span>)/i");
  array_push($rpl,"\\1[!--R2RC:\\2--]\\3[!/--R2RC:\\2--]\\4");


  $text=preg_replace($srch,$rpl,$text);
}

*/


//
// fonction de transformation des style automatique en style statique
// Transformation du XHTML

function processcontentXHTML(&$content,&$styles)

{
  // cherche les styles P ou T

  preg_match_all("/<style:style\s+[^>]*style:name=\"[PT]\d+\"[^>\/]*(?:\/>|>.*?<\/style:style>)/s",$content,$results,PREG_PATTERN_ORDER);


#  echo "\n";
#  echo $content;

#  preg_replace("/<text:p text:style-name=\"([PT]\d+)\">/e",
#	       ""

##<style:style style:name="P1" style:family="paragraph" style:parent-style-name="Title" style:master-page-name="Standard">
  if (!$results[0]) return;

  $content=str_replace($results[0],"",$content);

  $styles=preg_replace("/<style:style\s+[^>]*style:name=\"[PT]\d+\"[^>\/]*(?:\/>|>.*?<\/style:style>)/s","",$styles);
  $styles=str_replace("</office:styles>",join("",$results[0])."</office:styles>",$styles);

  // transforme les heading en paragraph
  $content=preg_replace(array("/<text:h\s([^>]*)text:level=\"\d+\"([^>]*>)/",
			      "/(<\/?text:)h\b([^>]*>)/"),
			array("<text:p \\1\\2",
			      "\\1p\\2",
			      ),$content);
}


//
// fonction qui recupere le nom original des styles P et T

function postprocesscontentXHTML(&$xhtml,$styles)

{
  // cherche les styles P ou T
  preg_match_all("/<style:style\s+[^>]*style:name=\"([PT]\d+)\"[^>]*style:parent-style-name=\"([^\"]*)\"/s",$styles,$results,PREG_SET_ORDER);

#  print_r($results);
#  die("\n");
  foreach($results as $result) {
    $name=removeaccentsandspaces(strtolower($result[2]));
    if ($name) {
      if ($name=="wwcitation" || $name=="citationcar") $name="citation";
      $stylename[$result[1]]=$name;
    }
  }

  $arr=preg_split("/(<\/?)(p|span)\b([^>]*>)/",$xhtml,-1,PREG_SPLIT_DELIM_CAPTURE);

  $count=count($arr);
  $stack=array();

  for($i=1;$i<$count; $i+=4) {
#    echo $arr[$i],":",$arr[$i+1],":",$arr[$i+2]," ",(substr($arr[$i+2],-2,1)=="/"),"--------",join("//",$stack),"\n";
#    echo $arr[$i+3],"\n";
    $singletags=substr($arr[$i+2],-2,1)=="/";

    if ($arr[$i]=="</") { // balise fermante
#      echo "fermante\n";
      $arr[$i+2].=array_pop($stack);
#      echo join(" ",$stack),"\n";
    } elseif (preg_match('/\bclass\s*=\s*"\s*([^"]+)\s*"/',$arr[$i+2],$result)) {
      $class=$result[1];
#echo "$class ".$stylename[$class]."<br/>";
      $ns=$arr[$i+1]=="p" ? "r2r" : "r2rc";
      
      if ($stylename[$class]) {
	$class=$stylename[$class];
	// for Got's pleasure:
	if ($arr[$i+1]=="p") $arr[$i+2]=str_replace($result[0],'class="'.$class.'"',$arr[$i+2]);
      } else {
	$class=strtolower($class);
	if (!preg_match("/^[tp]\d+$/",$class)) { // required to ensure the compatibility
	  if ($class=="wwcitation" || $class=="citationcar") $class="citation";
	  $arr[$i+2]=str_replace($result[0],'class="'.$class.'"',$arr[$i+2]); // lowercase the class
	}
      }
      if ($arr[$i+1]=="span" && preg_match("/^(t\d+|footnote.*|endnote.*|internetlink)$/",$class)) { # on fait comme ca maintenant... je ne sais pas ce que ca va donner !
	$class="";
      }
      if ($arr[$i+1]=="span") {
	$arr[$i+2]=preg_replace("/(style=\"font-size\s*:\s*\d+%;\s+vertical-align)\s*:\s*-\d+%/","\\1:sub",$arr[$i+2]);
      }


      if ($class) {
	$arr[$i]="<$ns:$class>".$arr[$i]; // ajoute au debut
	if ($singletags) { // balise ouvrante/fermante
	  $arr[$i+2].="</$ns:$class>";
	} else {
	  array_push($stack,"</$ns:$class>");
	}
      } elseif (!$singletags) {
	array_push($stack,"");
      }
    } elseif (!$singletags) {
      // c'est une ouvrante non fermante
      array_push($stack,"");
    }
  }
  #die("");
  $xhtml=join("",$arr);
  
#  // P
#  $xhtml=preg_replace("/(<p\s[^>]*class=\"([^\"]+)\"[^>\/]*(?:>.*?<\/p>|\/>))/es",
#		      '$stylename["\\2"] ? "<r2r:".$stylename["\\2"].">\\1</r2r:".$stylename["\\2"].">" : "<r2r:".strtolower("\\2").">\\1</r2r:".strtolower("\\2").">"',$xhtml);
#
#  // SPAN
#  $xhtml=preg_replace("/(<span\s[^>]*class=\"(T\d+)\"[^>]*>.*?<\/span>)/es",
#		      '$stylename["\\2"] ? "<r2rc:".$stylename["\\2"].">\\1</r2rc:".$stylename["\\2"].">" : "\\1"',$xhtml);
#
  // recupere le titre
# pas bon en fait  $xhtml=preg_replace("/(<h1>.*?<\/h1>)/s","<r2r:title>\\1</r2r:title>",$xhtml);


#  $startcss=strpos($xhtml,"<style");
#  $endcss=strpos($xhtml,"</style");
#  $css=substr($xhtml,$startcss,$endcss-$startcss);
#  $css=preg_replace("/\bp\.(P\d+)\s*{/es",'"p.".$stylename["\\1"]." {"',$css);
#  $css=preg_replace("/\bspan\.(T\d+)\s*{/es",'"span.".$stylename["\\1"]." {"',$css);
#  $xhtml=substr($xhtml,0,$startcss).$css.substr($xhtml,$endcss);
}


function checkZippedFile(&$uploadedfile)

{
  $fp=fopen($uploadedfile,"r") or die("le fichier $uploadedfile ne peut etre ouvert");
  $cle=fread($fp,2);
  fclose($fp);

  $issxw=0;

  if ($cle=="PK") {
    // verifie ce qu'il y a dedans
    $f=escapeshellcmd($uploadedfile);
    $filesinarchive=`$GLOBALS[unzipcmd] -Z -1 $f`;
    if (substr_count($filesinarchive,"\n")==1) {
      // combien de fichier dans l'archive ?
      // s'il n'y a q'un fichier alors on le dezippe. 
      // S'il y en a plusieurs c'est surement un sxw
      if ($msg) { echo "<li>Decompresse le fichier zippe<br>"; flush(); }
      exec_unzip("-j -p $uploadedfile >$uploadedfile.extracted",$errfile);
      // normalement, il ne doit pas y avoir d'erreur parce qu'on a deja fait un unzip
      // en fait c'est pas vrai, on peut avoir des problemes de disque (permission ou disk space).
      $uploadedfile.=".extracted";
      $tounlink=1;
    } else {
      // regarde si c'est un SXW
      $arr=preg_split("/\n/",$filesinarchive);
      if (in_array("content.xml",$arr) && 
	  in_array("styles.xml",$arr) &&
	  in_array("meta.xml",$arr)) $issxw=1;
      // sinon ne fait rien
    }
  }
  return array($issxw,$tounlink);
}


?>
