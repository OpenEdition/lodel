<?

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
      // s'il n'y a q'un fichier alors on le dezippe. S'il y en a plusieur c'est surement un sxw
      if ($msg) { echo "<li>Decompresse le fichier zippe<br>"; flush(); }
      system("$unzipcmd -j -p $uploadedfile >$uploadedfile.extracted 2>/dev/null"); // normalement, il ne doit pas y avoir d'erreur parce qu'on a deja fait un unzip
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
  processcontent($content);

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

  system("$javapath/bin/java -classpath \"$openofficepath/program/classes/jurt.jar:$openofficepath/program/classes/unoil.jar:$openofficepath/program/classes/ridl.jar:$openofficepath/program/classes/sandbox.jar:$openofficepath/program/classes/juh.jar:".$home."oo/classes\" DocumentConverterSimple \"$filename\" \"swriter: $format\" \"$extension\" \"$openofficepath/program/soffice \" 2>$errfile");

  if (filesize($errfile)>0) die("ERROR: java script failed<br>".@join("",@file($errfile)));
}

//
// fonction de transformation des styles en balise intermediaire
//

function processcontent(&$text)

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
