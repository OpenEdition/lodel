<?

require_once("$home/func.php");
require_once("$home/parserextra.php");


function parse ($in,$out)

{
  global $sharedir;
  if (!file_exists($in)) die ("impossible de lire $in");
  $file = join('',file($in));

  $contents=stripcommentandcr($file);

// cherche les fichiers a inclure
  preg_match_all("/<USE\s+MACROFILE\s*=\s*\"([^\"]+)\"\s*>\s*\n?/",$contents,$results,PREG_SET_ORDER);

  foreach($results as $result) {
    	$contents=str_replace($result[0],"",$contents); // efface le use
	$macrofile=$result[1];
	if (file_exists("tpl/".$macrofile)) {
	    $macros.=join('',file("tpl/".$macrofile));
	} elseif ($sharedir && file_exists($sharedir."/macros/".$macrofile)) {
	    $macros.=join('',file($sharedir."/macros/".$macrofile));
	} else {
	  die ("le fichier macros $result[1] n'existe pas");
	}
  }
  $macros=stripcommentandcr($macros);

  // parse les macros
  parse_macros($contents,$macros);
  // parse les let
  parse_let($contents);
  // parse les if
  parse_cond($contents);
  // parse les boucles
  parse_boucle($contents,$fct);
  // parse les variables
  if (strpos($contents,"[#META_")!==FALSE || (strpos($contents,"[(#META_")!==FALSE))  {
      $contents='<? $context=array_merge($context,unserialize($context[meta])); ?>'.$contents;
  }
  parse_variable($contents);
  parse_variable($fct);

  parse_texte($contents);

  $contents='<?
require_once ("$home/connect.php");
'.$fct.'?>'.$contents;

  $contents=preg_replace(array('/\?><\?/',
			       '/<\?[\s\n]*\?>/'),array("",""),$contents);

  $f=fopen ($out,"w");
  fputs($f,$contents);
  fclose($f); 
}



function parse_variable (&$text,$escape=TRUE)

{
  $balise_regexp="[A-Z][A-Z_0-9]*";
  $lang_regexp="[A-Z]{2}";
  $filtre_regexp="[A-Za-z][A-Za-z_0-9]*(?:\(.*?\))?";

# traite les sequences [...(#BALISE)...]

  while (preg_match("/(\[[^\[\]]*?)\((#$balise_regexp(?::$lang_regexp)?(?:\|$filtre_regexp)*)\)([^\[\]]*?\])/s",$text,$result)) {
    $expr=preg_replace("/^#($balise_regexp):($lang_regexp)/","#\\1_LANG\\2",$result[2]);

# parse les filtres
    if (preg_match("/^#($balise_regexp)((?:\|$filtre_regexp)*)$/",$expr,$subresult)) {
      $block=$subresult[0];

      $variable=parse_variable_extra($subresult[1]); // traitement particulier ?
      if ($variable===FALSE) { // non, traitement normal
	$variable="\$context[".strtolower($subresult[1])."]";
      }
      foreach(explode("|",$subresult[2]) as $fct) {
	if ($fct=="false" || $fct=="true") {
	  break;
	} elseif ($fct) {
	  // recupere les arguments de la fonction
	  if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)\((.*?)\)$/",$fct,$result2)) { $args=$result2[2].","; $fct=$result2[1]; } else { $args=""; }
	  $variable=$fct."($args$variable)";
	}
      }
    }
    $pre=substr($result[1],1);
    $post=substr($result[3],0,-1);
    if ($fct=="false") {
      $code='<? if (!('.$variable.')) { ?>'.$pre.$post.'<? } ?>';
    } elseif ($fct=="true") {
      $code='<? if ('.$variable.') { ?>'.$pre.$post.'<? } ?>';
    } else {
      $code='<? $tmpvar='.$variable.'; if ($tmpvar) { ?>'.$pre.'<? echo "$tmpvar"; ?>'.$post.'<? } ?>';
    }
    $text=str_replace($result[0],$code,$text);
  }

  if ($escape) {
    $pre='<? echo "'; $post='"; ?>';
  } else {
    $pre=""; $post="";
  }
# remplace les variables restantes
  while (preg_match("/\[\#($balise_regexp)(:$lang_regexp)?\]/",$text,$result)) {
    $variable=parse_variable_extra($result[1]); // traitement particulier ?
    if ($variable!==FALSE) { // traitement particulier
      $variable=$pre.$variable.$post;
    } else { // non traitement normal
      if ($result[2]) $result[1].="_LANG".substr($result[2],1);
      $variable=$pre.'$context['.strtolower($result[1]).']'.$post;
      }
    $text=str_replace($result[0],$variable,$text);
  }
}



//
// cette fonction contient des specificites a Lodel.
// il faut voir si on decide que les minitexte font partie de lodelscript ou de lodel.
//

function parse_texte(&$text)

{
  global $home,$editeur,$urlroot,$revue;
  preg_match_all("/<TEXT\s*NAME=\"([^\"]+)\"\s*>/",$text,$results,PREG_SET_ORDER);
#  print_r($results);
  foreach ($results as $result) {
    $nom=$result[1]; myquote($nom);
    if ($editeur) {       // cherche si le texte existe
      include_once("$home/connect.php");
      $result2=mysql_query("SELECT id FROM $GLOBALS[tableprefix]textes WHERE nom='$nom'") or die (mysql_error());
      if (!mysql_num_rows($result2)) { // il faut creer le texte
	mysql_query("INSERT INTO textes (nom,texte) VALUES ('$nom','')") or die (mysql_error());
      }
    }
    $urlbase=($GLOBALS[revueagauche] || !$revue) ? $urlroot : $urlroot.$revue."/";
    $text=str_replace ($result[0],'<? $result=mysql_query("SELECT id,texte FROM textes WHERE nom=\''.$nom.'\' AND status>0"); list($id,$texte)=mysql_fetch_row($result); if ($context[editeur]) { ?><A HREF="'."$urlbase".'lodel/admin/texte.php?id=<?=$id?>">[Modifier]</A><BR><? } echo $texte; ?>',$text);
  }
}


# traite les conditions avec IF
function parse_cond (&$text,$offset=0) {

  $tag_debut="<IF ";
  $tag_sinon="<ELSE/>";
  $tag_fin="</IF>";
  $lendebut=strlen($tag_debut);
  $lenfin=strlen($tag_fin);

  $debut = strpos($text,$tag_debut,$offset);
  while ($debut!==FALSE) {
    $offset=$debut+$lendebut;

    do {
# cherche le tag de fin
      $fin = strpos($text,$tag_fin,$offset);
      if ($fin===FALSE) { die ("erreur le IF ne se termine pas"); }
# cherche s'il y a un deuxieme IF a l'interieur
      $debut2=strpos($text,$tag_debut,$offset);
      $sndif=$debut2!==FALSE && $debut2<$fin;
      if ($sndif) parse_cond($text,$offset); // oui, on le traite d'abord
    } while($sndif);

    $if_txt=substr($text,$offset,$fin-$offset);

# ok, maintenant, on traite le if

# cherche la condition

	if (!preg_match("/^[^>]*COND\s*=\s*\"([^\"]+)\"[^>]*>/",$if_txt,$cond)) die ("erreur. La balise IF ne contient pas de condition");
    parse_variable($cond[1],FALSE);
    $cond[1]=preg_replace(array("/\bgt\b/i","/\blt\b/i","/\bge\b/i","/\ble\b/i","/\beq\b/i","/\bne\b/i","/\band\b/i","/\bor\b/i"),
		 array(">","<",">=","<=","==","!=","&&","||"),$cond[1]);

    $if_txt=substr($if_txt,strlen($cond[0])); // se place au debut du texte
# cherche le sinon
    $sinon=strpos($if_txt,$tag_sinon);
    if (!($sinon==FALSE)) {
      $else_txt=substr($if_txt,$sinon+strlen($tag_sinon));
      $if_txt=substr($if_txt,0,$sinon);
    } else {
      $else_txt="";
    }
# genere la code

    $code='<? if ('.$cond[1].') { ?>'.$if_txt.'<? }';
    if ($else_txt) $code.=' else { ?>'.$else_txt.'<? }';
    $code.='?>';

# fait le remplacement

    $text=substr($text,0,$debut).$code.substr($text,$fin+$lenfin);

    $debut = strpos($text,$tag_debut,$offset);
  }
}




##### traite les conditions avec SWITCH
####function parse_switch (&$text,$offset=0) {
####
####  $tag_debut="<SWITCH ";
####  $tag_case="<CASE>";
####  $tag_fin="</SWITCH>";
####  $lendebut=strlen($tag_debut);
####  $lenfin=strlen($tag_fin);
####
####  $debut = strpos($text,$tag_debut,$offset);
####  while ($debut!==FALSE) {
####    $offset=$debut+$lendebut;
####
####    do {
##### cherche le tag de fin
####      $fin = strpos($text,$tag_fin,$offset);
####      if ($fin===FALSE) { die ("erreur le IF ne se termine pas"); }
##### cherche s'il y a un deuxieme IF a l'interieur
####      $debut2=strpos($text,$tag_debut,$offset);
####      $sndif=$debut2!==FALSE && $debut2<$fin;
####      if ($sndif) parse_cond($text,$offset); // oui, on le traite d'abord
####    } while($sndif);
####
####    $switch_txt=substr($text,$offset,$fin-$offset);
####
##### ok, maintenant, on traite le switch
####
##### cherche la variable
####
####    if (!preg_match("/^[^>]*VAR\s*=\s*\"(.*?)\"[^>]*>/",$switch_txt,$var)) die ("erreur. La balise IF ne contient pas de condition");
####
####    parse_variable($cond[1],FALSE);
####    $cond[1]=preg_replace(array("/\bgt\b/i","/\blt\b/i","/\bge\b/i","/\ble\b/i","/\beq\b/i","/\band\b/i","/\bor\b/i"),
####		 array(">","<",">=","<=","==","&&","||"),$cond[1]);
####
####    $if_txt=substr($if_txt,strlen($cond[0])); // se place au debut du texte
##### cherche le sinon
####    $sinon=strpos($if_txt,$tag_sinon);
####    if (!($sinon==FALSE)) {
####      $else_txt=substr($if_txt,$sinon+strlen($tag_sinon));
####      $if_txt=substr($if_txt,0,$sinon);
####    } else {
####      $else_txt="";
####    }
##### genere la code
####
####    $code='<'.'? if ('.$cond[1].') { ?'.'>'.$if_txt.'<'.'? }';
####    if ($else_txt) $code.=' else { ?'.'>'.$else_txt.'<'.'? }';
####    $code.='?'.'>';
####
##### fait le remplacement
####
####    $text=substr($text,0,$debut).$code.substr($text,$fin+$lenfin);
####
####    $debut = strpos($text,$tag_debut,$offset);
####  }
####}
####

function parse_boucle (&$text,&$fct_txt,$offset=0) 

{
  static $boucles;

  $tag_debut="<LOOP ";
  $tag_fin="</LOOP>";
  $lendebut=strlen($tag_debut);
  $lenfin=strlen($tag_fin);

  $debut = strpos($text,$tag_debut,$offset);
  $fin = strpos($text,$tag_fin,$offset);
  // la comparaison $debut<$fin permet de finir quand on depasse le scope de l'appelant
  while ($debut!==FALSE && $debut<$fin) {

    $offset=$debut+$lendebut;

# cherche les attributs de la boucle
    $attr=substr($text,$offset);

    $nom=$order=$limit="";
    $wheres=array();
    $tables=array();
    while (preg_match("/^\s*(\w+)=\"(.*?)\"/",$attr,$result)) {
      $matchlen=strlen($result[0]);
      $offset+=$matchlen;
      $attr=substr($attr,$matchlen); // attribut suivant
      $value=trim($result[2]);
          
      switch ($result[1]) {
      case "WHERE" :
	array_push($wheres,"(".trim($value).")");
	break;
      case "TABLE" :
	array_push($tables,$value);
	break;
      case "ORDER" :
	$order.=$value." , ";
	break;
      case "LIMIT" :
	$limit=$value;
	break;
      case "NAME":
	$nom=$value;
	break;
      default:
	die ("erreur, attribut inconnu dans la boucle $nom");
      }
    } // boucle sur les attributs
    # cherche le > de fin
    if (preg_match("/^\s*\>/",$attr,$result)) {
      $matchlen=strlen($result[0]);
      $offset+=$matchlen;
      $attr=substr($attr,$matchlen); // attribut suivant
    } else {
      die ("erreur: le tag de la boucle $nom ne se ferme pas normalement");
    }

    $where=prefix_tablename(join(" AND ",$wheres));

    //
    // traitement specifique
    parse_boucle_extra(&$tables,&$where);
    //

    if ($where) {
      parse_variable($where,FALSE);
      $where="WHERE ".$where;
    }

    if ($order) $order="ORDER BY ".substr(prefix_tablename($order),0,-3); // enelve le , a la fin (pas propre, faire un tableau)
    if ($limit) { parse_variable($limit,FALSE); $limit="LIMIT ".$limit; }


    if (!$nom) {
      srand ((double) microtime() * 1000000);
      $nom="number".rand();
    }

    if (!$boucles[$nom][type]) $boucles[$nom][type]="def"; # marque la boucle comme definie, s'il elle ne l'ai pas deja
    $issql=$boucles[$nom][type]=="sql";

# cherche le tag de fin
      $fin = strpos($text,$tag_fin,$offset);
      if ($fin===FALSE) die ("erreur: la boucle ne se termine pas");
# cherche s'il y a une deuxieme boucle a l'interieur
      $debut2=strpos($text,$tag_debut,$offset);
      $sndbcl=!($debut2===FALSE) && $debut2<$fin;
      if ($sndbcl) {
	parse_boucle($text,$fct_txt,$offset); // oui, on le traite d'abord
	$fin = strpos($text,$tag_fin,$offset);
	if ($fin===FALSE) die ("erreur: la boucle ne se termine pas (2)");
      }
    # content
    $attr=substr($text,$offset,$fin-$offset);

    if ($tables) { // boucle SQL
      // cree un identifiant "presque" unique pour cette boucle
      $md5boucle=md5($tables.$where.$order.$limit.$attr);
      // verifie que la boucle n'a pas ete defini sous le meme nom avec un contenu different
      if ($issql && $md5boucle!=$boucles[$nom][id]) die ("Impossible de redefinir la boucle $nom avec un code ou des arguments differents");
      if (!$issql) { // on la definit
	make_boucle_code($nom,$tables,$where,$order,$limit,$attr,$fct_txt);
	$boucles[$nom][id]=$md5boucle; // enregistre l'identifiant qui caracterise la boucle
	$boucles[$nom][type]="sql"; // marque la boucle comme etant une boucle sql
      }
      $code='<? boucle_'.$nom.'($context); ?>';
    } else {
      if (!$issql) {// la boucle n'est pas deja definie... alors c'est une boucle utilisateur
	$boucles[$nom][id]++; // increment le compteur de nom de boucle
	$newnom=$nom."_".$boucles[$nom][id]; // change le nom pour qu'il soit unique
	make_userdefined_boucle_code ($newnom,$attr,$fct_txt);
	$code='<? boucle_'.$nom.'($context,"'.$newnom.'"); ?>';
      } else {
	// boucle sql recurrente
	$code='<? boucle_'.$nom.'($context); ?>';
      }
    }
    $text=substr($text,0,$debut).$code.substr($text,$fin+$lenfin);

    $fin = strpos($text,$tag_fin,$debut);
    $debut = strpos($text,$tag_debut,$debut);
  } // while
}



function decode_content ($content,$tables=array())

{
  global $home,$balisesdocument_lieautexte,$balisesdocument_nonlieautexte;
  $ret=array();

# cherche s'il y a un avant
#  $balises=array("avant","apres","premier","dernier","corps");
  $balises=array("BEFORE","AFTER","DOFIRST","DOLAST","DO","ALTERNATIVE");
  
  foreach ($balises as $balise) {
    if (strpos($content,"<$balise>")!==FALSE) {
      if (!preg_match ("/<$balise>(.*?)<\/$balise>/s",$content,$result)) { die ("la balise $balise n'est pas fermee dans la boucle $nom"); }
      $ret[$balise]=$result[1];
      $content=str_replace($result[0],"",$content); // enleve le bloc avant
    }
  }

  if ($ret["DO"]) {
    if (trim($content)) die("Une partie du contenu de la boucle $nom n'est pas dans l'une des balises &lt;corps&gt;  &lt;avant&gt;  &lt;apres&gt; &lt;premier&gt; &lt;dernier&gt;<br>");
  } else {
    $ret["DO"]=$content;
  }
  // OPTIMISATION
  // cherche les META et les extract
  $balises=array("DOFIRST","DOLAST","DO");

  foreach ($balises as $balise) {
    //
    // meta
    //
    if (strpos($ret[$balise],"[#META_")!==FALSE || strpos($ret[$balise],"[(#META_")!==FALSE)  {
      $ret["META_".$balise]='$context=array_merge($context,unserialize($context[meta]));';
    }

#ifndef LODELLIGHT
    // partie privee et specifique pour le decodage du contenu.
    decode_content_extra ($balise, &$ret, $tables);
#endif

  } // foreach
  return $ret;
}



function make_boucle_code ($nom,$tables,$where,$order,$limit,$content,&$fct_txt)

{
  // traitement particulier additionnel
  list ($premysqlquery,$postmysqlquery,$extrafield)=make_boucle_code_extra($tables);

  $table=$GLOBALS[tableprefix].join (', $GLOBALS[tableprefix]',array_reverse(array_unique($tables)));


  $contents=decode_content($content,$tables);
# genere le code pour parcourir la boucle
  $fct_txt.='function boucle_'.$nom.' ($context)
{
 $generalcontext=$context;
'.$premysqlquery.'; $result=mysql_query("SELECT *'.$extrafield.' FROM '."$table $where $order $limit".'") or die (mysql_error());
 $nbrows=mysql_num_rows($result);
 $count=0;
 if ($row=mysql_fetch_assoc($result)) {
?>'.$contents[BEFORE].'<?
    do {
      $context=array_merge ($generalcontext,$row);
      $context[count]=$count;
      $count++;';
  // gere le cas ou il y a un premier
  if ($contents[DOFIRST]) {
    $fct_txt.=' if ($count==1) { '.$contents[META_DOFIRST].$contents[EXTRACT_DOFIRST].' ?>'.$contents[DOFIRST].'<? continue; }';
  }
  // gere le cas ou il y a un dernier
  if ($contents[DOLAST]) {
    $fct_txt.=' if ($count==$nbrows) { '.$contents[META_DOLAST].$contents[EXTRACT_DOLAST].'?>'.$contents[DOLAST].'<? continue; }';
  }    
    $fct_txt.=$contents[META_DO].$contents[EXTRACT_DO].' ?>'.$contents["DO"].'<?    } while ($row=mysql_fetch_assoc($result));
?>'.$contents[AFTER].'<?  } ';

  if ($ret[ALTERNATIVE]) $fct_txt.=' else {?>'.$ret[ALTERNATIVE].'<?}';

    $fct_txt.='
 mysql_free_result($result);
}
';
}


function make_userdefined_boucle_code ($nom,$content,&$fct_txt)

{
  $contents=decode_content($content);

// cree la fonction boucle
  if ($contents["DO"]) {
    $fct_txt.='function code_boucle_'.$nom.' ($context) { ?>'.$contents["DO"].'<? }';
  }

  if ($contents[BEFORE]) { // genere le code de avant
    $fct_txt.='function code_avant_'.$nom.' ($context) { ?>'.$contents[BEFORE].'<? }';
  }

  if ($contents[AFTER]) {// genere le code de apres
  $fct_txt.='function code_apres_'.$nom.' ($context) { ?>'.$contents[AFTER].'<? }';
 }
}


function parse_macros(&$text,&$macros)

{
  while (preg_match("/<MACRO(\s+NAME\s*=\s*\"(\w+)\")\s*>/",$text,$result)) {
    if (!$result[2]) { die ("erreur: une balise macro est mal formee"); }
    // cherche la define
    $search="/<DEFMACRO\s+NAME\s*=\s*\"$result[2]\"\s*>(.*?)<\/DEFMACRO>/s";
    if (!preg_match_all($search,$text,$defs,PREG_SET_ORDER)) 
      if (!preg_match_all($search,$macros,$defs,PREG_SET_ORDER)) { die ("erreur: la macro $result[2] n'est pas definie"); }
    $def=array_pop($defs); // recupere la derniere definission
    $def[1]=preg_replace("/(^\n|\n$)/","",$def[1]); // enleve le premier saut de ligne et le dernier
    $text=str_replace($result[0],$def[1],$text);
  }
  $text=preg_replace("/<DEFMACRO\b[^>]*>.*?<\/DEFMACRO>\s*\n?/s","",$text);
}



function parse_let (&$text,$offset=0) {

  $tag_debut="<LET ";
  $tag_fin="</LET>";
  $lendebut=strlen($tag_debut);
  $lenfin=strlen($tag_fin);

  $debut = strpos($text,$tag_debut,$offset);
  while (!($debut===FALSE)) {
    $offset=$debut+$lendebut;
# cherche l'attribut
    $let_txt=substr($text,$offset);
    if (!preg_match("/^\s*VAR=\"(\w+)\"[^>]*>/",$let_txt,$result)) die ("erreur la variable n'est pas definie dans le let");
    $var=strtolower($result[1]);
    $offset+=strlen($result[0]);

  do {
# cherche le tag de fin
    $fin = strpos($text,$tag_fin,$offset);
    if ($fin===FALSE) { die ("erreur le LET ne se termine pas"); }
# cherche s'il y a un deuxieme IF a l'interieur
      $debut2=strpos($text,$tag_debut,$offset);
      $sndlet=!($debut2===FALSE) && $debut2<$fin;
      if ($sndlet) parse_let($text,$offset); // oui, on le traite d'abord
    } while($sndlet);
    $let_txt=substr($text,$offset,$fin-$offset);

# ok, maintenant, on traite le let

# genere la code

    $code='<? ob_start(); ?>'.$let_txt.'<? $context['.$var.']=ob_get_contents();  ob_end_clean(); ?>';

# fait le remplacement

    $text=substr($text,0,$debut).$code.substr($text,$fin+$lenfin);

    $debut = strpos($text,$tag_debut,$debut);
  }
}

function stripcommentandcr(&$text)

{
#  return $text;
#  preg_replace_all("/<!--.*?-->/s","",$text,$results,PREG_PATTERN_ORDER);
#  foreach ($results[0] as $comment) {
#    if (!preg_match("/javascript/i",$comment)) str_replace
#  }


  return preg_replace (array("/\r/",
			     "/(<SCRIPT\b[^>]*>[\s\n]*)<!--+/i",
			     "/--+>([\s\n]*<\/SCRIPT>)/i",
			     "/<!--.*?-->\s*\n?/s",
			     "/<SCRIPT\b[^>]*>/i",
			     "/<\/SCRIPT>/i"
			     ),
		       array("",
			     "\\1",
			     "\\1",
			     "",
			     "\\0<!--",
			     "-->\\0")
		       ,$text);


#### MARCHE PAS  return preg_replace ("/(<!--.*?-->)/se","preg_match('/language=\"javascript\"/i','\\1') ?  '\\1' : ''; ",$text);
}

?>

