<?php

#echo "server:";
#print_r($_SERVER);
#echo "header:"; print_r(getallheaders());
#echo "post:"; print_r($HTTP_POST_VARS);

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include ($home."func.php");
require_once($home."utf8.php"); // conversion des caracteres

$context[idparent]=intval($idparent);
$context[iddocument]=intval($iddocument);
$context[idtache]=$idtache=intval($idtache);


if ($file1 && $file1!="none") {
  do {
    // verifie que la variable file1 n'a pas ete hackee
    if (!is_uploaded_file($file1)) die(utf8_encode("Le fichier n'est pas un fichier chargé"));

    $t=time();
    $file1converted=$file1.".converted";
    $ret=convert($file1,$file1converted);
    $source=$file1."-source";
    move_uploaded_file($file1,$source);
    $sourceoriginale=$HTTP_POST_FILES['file1']['name'];


    if ($ret) {
      $context[erreur_upload]=utf8_encode("Erreur renvoyée par le serveur OO: \"$ret\"");
      break;
    }

    if ($msg) { echo "convert: ",time()-$t; flush(); }
    //
    // regarde si le fichier est zipper
    //
    $fp=fopen($file1converted,"r") or die("le fichier $file1converted ne peut etre ouvert");
    $cle=fread($fp,2);
    if ($cle=="PK") {
      if ($msg) { echo "<li>Decompresse le fichier zippe<br>"; flush(); }
      system("/usr/bin/unzip -j -p $file1converted >$file1converted.extracted");
      unlink($file1converted);
      $file1converted.=".extracted";
    }
    fclose($fp);
    //
    //
    //

    require_once($home."balises.php");
    if ($sortieoo || $sortiexmloo || $sortie) $oo=TRUE;
    
    $newname=OO($file1converted,$context);
    if (!$newname) {
      $context[erreur_upload]="Erreur dans la fonction OO";
      break;
    }
    if ($idtache) { // document ancien ?
      $row=get_tache($idtache);
      $row[fichier]=$newname;
      $row[source]=$source;
      $row[sourceoriginale]=$sourceoriginale;
    } else {
      $row=array("fichier"=>$newname,
		 "source"=>$source,
		 "sourceoriginale"=>$sourceoriginale);

      if ($context[iddocument]) {
	$row[iddocument]=$context[iddocument];
    /*  } elseif ($context[idparent]>=0) {
	$row[idparent]=$context[idparent];
      } else {
	die("probleme dans l'interface, aucune information pour attacher le document ou la publication");
      }*/
     } else {
       $row[idparent]=$context[idparent];
     }
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
  global $home,$serveuroourl,$serveuroousername,$serveuroopasswd,$unzipcmd;

  $cmds.="DWL file1; CVT HTMLLodel-1.0; ZIP all; RTN convertedfile;";

  require ($home."serveurfunc.php");
  $ret=upload($serveuroourl,
	      array("username"=>$serveuroousername,
		    "passwd"=>$serveuroopasswd,
		    "commands"=>$cmds),
	      array($uploadedfile), # fichier a uploaded
	      0, # cookies
	      $destfile
	      );
  if ($ret) { # erreur
    return $ret;
  }

  return FALSE;
}


function OO ($convertedfile,&$context)

{
  global $home,$msg;

  $time1=time();

  $file=strtr(join('',file($convertedfile)),"\n\r","  ");

  if ($GLOBALS[sortieoo]) { // on veut la sortie brute
    echo htmlentities($file);
    return;
  }

  convertHTMLtoUTF8($file);

  // tableau search et replace
  $srch=array(); $rpl=array();
  // convertir les caracteres html &xxx en UTF-8

  // convertir les balises (et supprimer les lettres avec accents, et les espaces diviennent des _)

  array_push($srch,"/.*<body\b[^>]*>/si","/<\/body>.*/si");
  array_push($rpl,"","");
  
  array_push($srch,"/\[!(\/?)--R2R(C?):([^\]]+)--\]/e");
  array_push($rpl,"removeaccentsandspaces(strtolower('<\\1r2r\\2:\\3>'))");  

  // styles transparents
  // on efface tout ce qu'il y a entre.
  array_push($srch,"/<(r2rc?:$GLOBALS[stylestransparents])\b([^>]*)>.*?<\/\\1>/");
  array_push($rpl,"");
  

  $translations=array("notesdebasdepage"=>"notebaspage",
#		      "title"=>"titre","subtitle"=>"soustitre",
		      "document"=>"article",
#                     "auteur"=>"auteurs",
#		      "footnote(?:text)?"=>"notebaspage",
#		      "endnote"=>"notefin",
		      "footnote(?:text)?"=>"",
		      "endnote(?:text)?"=>"",
		      "corpsdetexte\w*"=>"texte","bodytext"=>"texte",
		      "introduction"=>"texte","conclusion"=>"texte",
		      "normal"=>"texte", "normal\s*(web)"=>"texte",
		      "puces?"=>"texte",
		      "bloccitation"=>"citation",
		      "typedocument"=>"typedoc",
#		      "langue"=>"langues",
		      );

  
  foreach ($translations as $k=>$v) {
    array_push($srch,"/<r2r:$k\b([^>]*)>/","/<\/r2r:$k\b([^>]*)>/");
    if ($v) {
	array_push($rpl,"<r2r:$v\\1>","</r2r:$v\\1>");
    } else {
	array_push($rpl,"","");
    } 
  }

  // conversion des balises avec publication
  array_push($srch,
	     "/<r2r:(?:heading|titre)(\d+\b([^>]*))>/",
	     "/<\/r2r:(?:heading|titre)(\d+)>/");
  array_push($rpl,
	     "<r2r:section\\1>",
	     "</r2r:section\\1>");

  // traitement un peu sale des footnote et les endnote. On efface les paragraphes marques footnote et on remet sur la base du div
  array_push($srch,"/<\/?r2r:notebaspage>/","/<div id=\"sdfootnote\d+\">.*?<\/div>/i");
  array_push($rpl,"","<r2r:notebaspage>\\0</r2r:notebaspage>");
  array_push($srch,"/<\/?r2r:notefin>/","/<div id=\"sdendnote\d+\">.*?<\/div>/i");
  array_push($rpl,"","<r2r:notefin>\\0</r2r:notefin>");

  // remonte les balises r2r
  array_push($srch,"/((?:<\w+[^>]*>\s*)+)<r2r:([^>]+)>(.*?)<\/r2r:\\2>\s*((?:<\/\w+[^>]*>\s*)+)/");
  array_push($rpl,"<r2r:\\2>\\1\\3\\4</r2r:\\2>");

  // autre chgt

  array_push($srch,
	     "/<span\s*lang=\"[^\"]*\">(.*?)<\/span>/i", # enleve les span
	     "/(<a\b[^>]*)sdfixed>/i",
	     "/<div type=(?:header|footer)>.*?<\/div>/is",
	     "/<\w[^>]*>/e", // balises ouvrantes
	     "/<\/[^>]+>/e", // balises fermantes
	     "/<p\salign=\"(left|justify)\"(\s+[^>]*)>/", # enleve les alignements gauche et justify ....... surement inutile maintenant avec OO
	     "/<br\b([^>]*)>/",   # XML is
	     "/<\/br>/",	#efface
	     "/<li>/",
	     "/(<img\b[^>]+)border=\"?\d+\"?([^>]*>)/", # efface les border
	     "/(<img\b[^>\/]+)\/?>/i", # met border="0"
	     "/(<(col)\b[^>]*?)\/?>/i", # balise seule, il faut les fermer
	     "/(<p\b[^>]*>\s*<br\s*\/>\s*<\/p>\s*)(<r2r:[^>]+>)/" // gere les sauts de ligne
	     );

  array_push($rpl,
	     "\\1",
	     "\\1>",
	     "",
	     'quote_attribut_strtolower("\\0")', // ouvrantes
	     'strtolower("\\0")',                // fermentes
	     "<p\\2>",
	     "<br\\1 />",
	     " ",
	     "<li />",
	     "\\1\\2",
	     "\\1border=\"0\"/>",
	     "\\1 />",
	     "\\2\\1"
	     );

  $time=time();

  $file=preg_replace ($srch,$rpl,$file);
  if (!traite_tableau2($file)) {     $context[erreur_stylestableaux]=1;
  return FALSE; }
  $file=traite_multiplelevel($file);

  if ($msg) { echo "<li>temps regexp: ".(time()-$time)." s<br>\n"; }

  //echo htmlentities($file); exit;

  // desuet
  // enleve les couples de balises r2r.
  $file=traite_couple($file);

  // recupere les styles conteneurs (ceux qui ont des parentheses)
  $file=preg_replace (array("/(<r2r:\w+)\((\w+)\)>/","/(<\/r2r:\w+)\((\w+)\)>/"),
		      array("<r2r:\\2>\\1>","\\1></r2r:\\2>"),
		      $file);
  

# ajoute le debut et la fin
    $file='<r2r:article xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">'.$file.'</r2r:article>';

# verifie que le document est bien forme
    include ($home."checkxml.php");
    if (!checkstring($file)) { echo "fichier: $newname"; return FALSE; }

   if ($GLOBALS[sortie]) die (htmlentities($file));

    function img_copy($imgfile,$ext,$count,$rand) {
      $newimgfile="../../docannexe/tmp".$rand."_".$count.".".$ext;
      copy ("/tmp/$imgfile",$newimgfile) or die ("impossible de copier l'image $newimgfile");
      return $newimgfile;
    }
    include_once ($home."func.php");
    copy_images($file,"img_copy",rand());


  // ecrit le fichier
  $newname="$convertedfile-".rand();
  if (!writefile("$newname.html",$file)) return FALSE;

  if ($msg) { echo "Temps total:",time()-$time1,"<br><br>"; flush(); }

  return $newname;
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



function removeaccentsandspaces($string){
return strtr(
 strtr(utf8_decode(preg_replace("/[\s_\r]/","",$string)),
  '¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
  'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
  '¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}



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



function traite_couple(&$text)

{
  global $home;

  // determine les $virgule_tags
  require_once($home."connect.php");
  $result=mysql_query("SELECT style FROM typeentrees WHERE statut>0") or die(mysql_error());
  while (list($style)=mysql_fetch_row($result)) $virgule_tags_arr[]=$style;
  $result=mysql_query("SELECT style FROM typepersonnes WHERE statut>0") or die(mysql_error());
  while (list($style)=mysql_fetch_row($result)) $virgule_tags_arr[]=$style;


  // determine les $multiparagraphe_tags
  $result=mysql_query("SELECT style FROM champs WHERE statut>0") or die(mysql_error());
  while (list($style)=mysql_fetch_row($result)) $multiparagraphe_tags_arr[]=$style;

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




?>
