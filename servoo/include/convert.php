<?

// This fonction convert a file (can be zipped) in a format readable by OO 
// into the XHTML+Lodel information.
// Use OO to convert original file into SXW and to convert the SXW.
// And use writer2latex to convert the SXW into the XHTML

function XHTMLLodel ($uploadedfile,$msg=TRUE)

{
  $errfile="$uploadedfile.err";

  //
  // regarde si le fichier est zipper
  //
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
  mkdir("$tmpdir",0700);

  // if the file is outputted by the OO user, it is not necessary writable.
  if (!is_writeable($uploadedfile.".sxw")) {
    // copie pour avoir les droits d'ecriture
    if (!@copy($uploadedfile.".sxw",$uploadedfile."-second.sxw")) die("ERROR: copy failed of $uploadedfile.sxw failed");
    @unlink($uploadedfile.".sxw"); // efface si on peut.
    $uploadedfile.="-second";
  }

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
  processcontentXHTML(&$content,&$styles);

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
  $uploadedfile.=".xhtml";
  if (!file_exists($uploadedfile)) die("ERROR: second conversion failed");


  $xhtml=file_get_contents($uploadedfile);
  postprocesscontentXHTML(&$xhtml,$styles);
  writefile($uploadedfile,$xhtml);


#  die ("ERROR: ici: $uploadedfile ".join("",file($uploadedfile)));


  //
  // recupere le nom des images associees
  //
  $htmlfile=join("",file($uploadedfile));
  $uploadedfilebasename=basename($uploadedfile);
  preg_match_all("/src=\"($uploadedfilebasename\_[^\"]+)\"/i",$htmlfile,$results,PREG_PATTERN_ORDER);
  $uploadedfiledirname=dirname($uploadedfile);
  $files=array($uploadedfile);
  foreach ($results[1] as $imgfile) array_push($files,$uploadedfiledirname."/".$imgfile);
  return $files;
}


// This fonction convert a file (can be zipped) in a format readable by OO 
// into the HTML+Lodel information.
// Use OO to convert original file into SXW and to convert the SXW into the HTML

function HTMLLodel ($uploadedfile,$msg=TRUE)

{
  global $unzipcmd;
  //
  // regarde si le fichier est zipper
  //
  $fp=fopen($uploadedfile,"r") or die("le fichier $uploadedfile ne peut etre ouvert");
  $cle=fread($fp,2);
  fclose($fp);

  if ($cle=="PK") {
    // verifie ce qu'il y a dedans
    $f=escapeshellcmd($uploadedfile);
#    die("ERROR: unzipinfo $uploadedfile <br>::".(substr_count(`$unzipcmd -Z -1 $f`,"\n")));
    if (substr_count(`$unzipcmd -Z -1 $f`,"\n")==1) { // combien de fichier dans l'archive ?
      // s'il n'y a q'un fichier alors on le dezippe. S'il y en a plusieurs c'est surement un sxw
      if ($msg) { echo "<li>Decompresse le fichier zippe<br>"; flush(); }
      system("$unzipcmd -j -p $uploadedfile >$uploadedfile.extracted 2>/dev/null"); 
      // normalement, il ne doit pas y avoir d'erreur parce qu'on a deja fait un unzip
      // en fait c'est pas vrai, on peut avoir des problemes de disque (permission ou disk space).
      $uploadedfile.=".extracted";
      $tounlink=1;
    } // sinon, on y touche pas
  }

  if ($msg) {
    echo "<h2>Conversions du fichier importe par OO</h2>";
    echo "<p>En cas d'arret avant la fin de la 2eme conversion veuillez envoyer les informations sur lodel-devel</p>";
    flush();
  }
  $errfile="$uploadedfile.err";
  chmod($uploadedfile,0644); // temporaire, il faut qu'on gere le probleme des droits
  # cette partie n'est pas secur du tout. Il va falloir reflechir fort.
  if ($msg) {  echo "<li>1ere conversion format initial->SXW <br>\n";flush(); $time1=time(); }
  
  runDocumentConverter($uploadedfile,"sxw");
  if ($tounlink) unlink($uploadedfile); // ne detruit pas l'original
  // solution avec unzip, ca serait mieux avec libzip
  // dezip le fichier content
  if ($msg) {
    echo "temps:",time()-$time1," s<br>";
    echo "<li>unzip le fichier SXW<br>\n";flush();
  }
  $tmpdir=$uploadedfile."_dir";
  mkdir("$tmpdir",0700);

  if (!file_exists($uploadedfile.".sxw")) die("ERROR: first OO conversion failed");
  if (!is_writeable($uploadedfile.".sxw")) {
    // copie pour avoir les droits d'ecriture
    if (!@copy($uploadedfile.".sxw",$uploadedfile."-second.sxw")) die("ERROR: copy failed of $uploadedfile.sxw failed");
    @unlink($uploadedfile.".sxw"); // efface si on peut.
    $uploadedfile.="-second";
  }

  if (!$GLOBALS[unzipcmd]) die ("ERROR: unzip undefined");
  system("$GLOBALS[unzipcmd] -q -d $tmpdir $uploadedfile.sxw content.xml 2>$errfile");
  if (filesize($errfile)>0) {
    @unlink("$tmpdir/content.xml");
    die("ERROR: unzip failed<br>".str_replace("\n","<br>",htmlentities(@join("",@file($errfile)))));
  }
  $content=join("",file("$tmpdir/content.xml"));

  if ($msg) {
    echo "<br>";
    echo "<li>extraction des styles du fichier content.xml contenu dans le SXW<br>\n";flush();
  }
  // lit et modifie le fichier content.xml
  processcontentHTML($content);

  // ecrit le fichier content.xml
  if (!writefile("$tmpdir/content.xml",$content)) die("ERROR: writing $tmpdir/content.xml file failed");

  if ($msg) {
    echo "<li>Reinsertion du nouveaux fichier content.xml dans le SXW<br>\n";flush();
  }
  if (!$GLOBALS[zipcmd]) die ("ERROR: zip undefined");
  system("$GLOBALS[zipcmd] -q -j  $uploadedfile.sxw $tmpdir/content.xml 2>$errfile");
  if (filesize($errfile)>0) die("ERROR: zip failed<br>".@join("",@file($errfile)));
  unlink("$tmpdir/content.xml");
  rmdir("$tmpdir");

  // conversion en HTML
  if ($msg) { echo "<br><li>2nd conversion SXW->HTML<br>\n";flush(); }
  $time2=time();
  runDocumentConverter($uploadedfile.".sxw","html");
  unlink("$uploadedfile.sxw");
  if ($msg) {
    echo "temps:",time()-$time2," s<br>";
    echo "fin<br>\n";flush();
  }

  $uploadedfile.=".sxw.html";
  if (!file_exists($uploadedfile)) die("ERROR: second conversion failed");

  //recupere les images associees
  $htmlfile=join("",file($uploadedfile));
  $uploadedfilebasename=basename($uploadedfile);
  preg_match_all("/src=\"($uploadedfilebasename\_[^\"]+)\"/i",$htmlfile,$results,PREG_PATTERN_ORDER);
  $uploadedfiledirname=dirname($uploadedfile);
  $files=array($uploadedfile);
  foreach ($results[1] as $imgfile) array_push($files,$uploadedfiledirname."/".$imgfile);
  return $files;
}


//
// fonction qui lance le java qui communique avec OO
//


function runDocumentConverter($filename,$extension)

{
  global $home,$openofficepath,$javapath;

  $errfile="$filename.err";

  if ($extension=="sxw") {
    $format="StarOffice XML (Writer)";
  } elseif ($extension=="html") {
    $format="HTML (StarWriter)";
  } else die ("probleme interne");

  myexec("$javapath/bin/java -classpath \"$openofficepath/program/classes/jurt.jar:$openofficepath/program/classes/unoil.jar:$openofficepath/program/classes/ridl.jar:$openofficepath/program/classes/sandbox.jar:$openofficepath/program/classes/juh.jar:".$home."oo/classes\" DocumentConverterSimple \"$filename\" \"swriter: $format\" \"$extension\" \"$openofficepath/program/soffice \"",$errfile,"java script DocumentConverter failed");
}


//
// fonction qui lance le writer2latex
//

function runWriter2Latex($filename,$extension)

{
  global $home,$javapath;

  $errfile="$filename.err";
#  echo "$errfile $home";

  // malheureusement pour le moment, il faut faire comme ca:
  $cwd=getcwd();

  $tmpdir=substr($filename,0,strrpos($filename,"/"));
  chdir ($tmpdir);
  myexec("$javapath/bin/java  -jar $cwd/$home/writer2latex/writer2latex.jar -xhtml -config $cwd/$home/writer2latex/default-config.xml $filename 1>&2",$errfile,"java script Writer2Latex failed");
  chdir($cwd);
}



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




//
// fonction de transformation des style automatique en style statique
// Transformation du XHTML

function processcontentXHTML(&$content,&$styles)

{
  // cherche les styles P ou T

  preg_match_all("/<style:style\s+[^>]*style:name=\"[PT]\d+\"[^>\/]*(?:\/>|>.*?<\/style:style>)/s",$content,$results,PREG_PATTERN_ORDER);

#  print_r($results);
#  echo "\n";
#  echo $content;

#  preg_replace("/<text:p text:style-name=\"([PT]\d+)\">/e",
#	       ""

##<style:style style:name="P1" style:family="paragraph" style:parent-style-name="Title" style:master-page-name="Standard">
  if (!$results[0]) return;

  $content=str_replace($results[0],"",$content);

  $styles=preg_replace("/<style:style\s+[^>]*style:name=\"[PT]\d+\"[^>\/]*(?:\/>|>.*?<\/style:style>)/s","",$styles);
  $styles=str_replace("</office:styles>",join("",$results[0])."</office:styles>",$styles);
}


//
// fonction qui recupere le nom original des styles P et T

function postprocesscontentXHTML(&$xhtml,$styles)

{
  // cherche les styles P ou T
  preg_match_all("/<style:style\s+[^>]*style:name=\"([PT]\d+)\"[^>]*style:parent-style-name=\"([^\"]*)\"/s",$styles,$results,PREG_SET_ORDER);

#  print_r($results);
  foreach($results as $result) {
    $name=removeaccentsandspaces(strtolower($result[2]));
    if ($name) $stylename[$result[1]]=$name;
  }

  $arr=preg_split("/(<\/?)(p|span)\b([^>]*>)/",$xhtml,-1,PREG_SPLIT_DELIM_CAPTURE);

  $count=count($arr);
  $stack=array();
  for($i=1;$i<$count; $i+=4) {
    #echo $arr[$i],":",$arr[$i+1],":",$arr[$i+2],"<br>\n";
    if ($arr[$i]=="</") { // balise fermante
      $arr[$i+2].=array_pop($stack);
    } elseif (preg_match("/\bclass=\"([^\"]+)\"/",$arr[$i+2],$result)) {
      $class=$result[1];
      $ns=$arr[$i+1]=="p" ? "r2r" : "r2rc";
      if ($stylename[$class]) {
	$class=$stylename[$class];
      } elseif ($ns=="r2r") {
	$class=strtolower($class);
      } else {
	$class="";
      }
      if ($class) {
	$arr[$i]="<$ns:$class>".$arr[$i]; // ajoute au debut
	if (substr($arr[$i+2],-2,1)=="/") { // balise ouvrante/fermante
	  $arr[$i+2].="</$ns:$class>";
	} else {
	  array_push($stack,"</$ns:$class>");
	}
      } else {
	array_push($stack,"");
      }
    } elseif (substr($arr[$i+2],-2,1)!="/") {
      // c'est une ouvrante non fermante
      array_push($stack,"");
    }
  }

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



function exec_zip($cmd,$errfile)

{
  if (!$GLOBALS[zipcmd]) die ("ERROR: zip command not configured");
  myexec("$GLOBALS[zipcmd] $cmd",$errfile,"zip failed");
}

function exec_unzip($cmd,$errfile)

{
  if (!$GLOBALS[unzipcmd]) die ("ERROR: unzip command not configured");
  myexec("$GLOBALS[unzipcmd] $cmd",$errfile,"unzip failed");
}

function myexec($cmd,$errfile,$errormsg)

{
  system($cmd."  2>$errfile");
  if (filesize($errfile)>0) die("ERROR: $errormsg<br />".str_replace("\n","<br>",htmlentities(@join("",@file($errfile)))));
}



if (!function_exists("writefile")) {
function writefile ($filename,&$text)

{
 //echo "nom de fichier : $filename";
   if (file_exists($filename)) 
   { 
     if (! @unlink($filename) ) die ("ERROR: $filename can not be deleted. Please contact OO server administrator");
   }
   return ($f=fopen($filename,"w")) && fputs($f,$text) && fclose($f) && chmod ($filename,0644);
}
}


?>
