<?

include_once("$home/func.php");


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
require_once("revueconfig.php");include_once ("$home/connect.php");
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
      #### VARIABLE SPECIALES
      if ($subresult[1]=="OFFLINE") {
	$variable="(\$context[status]<0)";
      } else { # cas general
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
    ###### VARIABLES SPECIALS
    if ($result[1]=="OFFLINE") {
      $variable=$pre.'($context[status]<0)'.$post;
    } elseif ($result[1]=="OKGROUPE") {
      $variable=$pre.'($GLOBALS[admin] || in_array($context[groupe],split(",",$GLOBALS[usergroupes])))'.$post;
    } else {
      if ($result[2]) $result[1].="_LANG".substr($result[2],1);
      $variable=$pre.'$context['.strtolower($result[1]).']'.$post;
      }
    $text=str_replace($result[0],$variable,$text);
  }
}


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




##### traite les conditions avec IF
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
  global $revue,$boucles;

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
	$cond=$value;
	if (strtolower($cond)=="trash") $cond="status<=0";
	if (strtolower($cond)=="ok") $cond="status>0";
	if (strtolower($cond)=="okgroupe") $cond='".($GLOBALS[admin] ? "1" : "(groupe IN ($GLOBALS[usergroupes]))")."';
	array_push($wheres,"(".$cond.")");
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

    // verifie le status
    if (!preg_match("/\bstatus\b/i",$where)) { // test que l'element n'est pas a la poubelle
      $teststatus=array();
      if ($where) array_push($teststatus,$where);
      foreach ($tables as $table) {
	if ($table=="session") continue;
	if ($table=="documents" || $table=="publications") {
	  $lowstatus='"-64".($GLOBALS[admin] ? "" : "*('.$GLOBALS[tableprefix].$table.'.groupe IN ($GLOBALS[usergroupes]))")';
	} else {
	  $lowstatus="-64";
	}
	array_push($teststatus,"($GLOBALS[tableprefix]$table.status>\".(\$GLOBALS[visiteur] ? $lowstatus : \"0\").\")");
      }
      $where=join(" AND ",$teststatus);
    }
    $where="WHERE ".$where;

#ifndef LODELLIGHT
    if ($revue) {
#endif
      ///////// CODE SPECIFIQUE -- gere les tables croisees
      if (in_array("taches",$tables) && in_array("publications",$tables)) $where.=" AND publication=r2r_publications.id";

      if (in_array("documents",$tables) && in_array("publications",$tables)) {
	$where.=" AND publication=$GLOBALS[tableprefix]publications.id";
      }

#ifndef LODELLIGHT
      if (in_array("documentsannexes",$tables)) {
	$where=preg_replace(array("/\btype_lienfichier\b/i",
				  "/\btype_liendocument\b/i",
				  "/\btype_lienpublication\b/i",
				  "/\btype_lienexterne\b/i",
				  "/\btype_lieninterne\b/i"),
			    array("type='".TYPE_LIENFICHIER."'",
				  "type='".TYPE_LIENDOCUMENT."'",
				  "type='".TYPE_LIENPUBLICATION."'",
				  "type='".TYPE_LIENEXTERNE."'",
				  "(type='".TYPE_LIENDOCUMENT."' OR type='".TYPE_LIENPUBLICATION."')"),
			    $where);
      }

      // auteurs
     if (in_array("auteurs",$tables) && strpos($where,"iddocument")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_auteurs");
	$where.=" AND idauteur=auteurs.id";
      }
      if (in_array("documents",$tables) && strpos($where,"idauteur")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_auteurs");
	$where.=" AND iddocument=documents.id";
      }

      // indexhs
      if (in_array("indexhs",$tables) && strpos($where,"iddocument")!==FALSE) {
	// on a besoin de la table croise documents_indexhs
	array_push($tables,"documents_indexhs");
	$where.=" AND idindexh=indexhs.id";
      }

      if (in_array("documents",$tables) && strpos($where,"idindexh")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_indexhs");
	$where.=" AND iddocument=documents.id";
      }
      if (in_array("indexhs",$tables)) {
	$where=preg_replace(array("/type_periode/i","/type_geographie/i"),
			    array("type='".TYPE_PERIODE."'","type='".TYPE_GEOGRAPHIE."'"),
			    $where);
      }
#endif

      if (in_array("indexls",$tables) && strpos($where,"iddocument")!==FALSE) {
	// on a besoin de la table croise documents_indexls
	array_push($tables,"documents_indexls");
	$where.=" AND idindexl=$GLOBALS[tableprefix]indexls.id";
      }
      if (in_array("documents",$tables) && strpos($where,"idindexl")!==FALSE) {
	// on a besoin de la table croise documents_auteurs
	array_push($tables,"documents_indexls");
	$where.=" AND iddocument=$GLOBALS[tableprefix]documents.id";
      }
      if (in_array("groupes",$tables) && strpos($where,"iduser")!==FALSE) {
	// on a besoin de la table croise users_groupes
	array_push($tables,"users_groupes");
	$where.=" AND idgroupe=$GLOBALS[tableprefix]groupes.id";
      }
      if (in_array("indexls",$tables)) {
	$where=preg_replace(array("/\btype_motcle_permanent\b/i",
				  "/\btype_motcle\b/i",
				  "/\btype_tous_motcles\b/i"),
			    array("type='".TYPE_MOTCLE_PERMANENT."'",
				  "type='".TYPE_MOTCLE."'",
				  "(type='".TYPE_MOTCLE_PERMANENT."' OR type='".TYPE_MOTCLE."')"),
			    $where);
      }
      if (in_array("users",$tables) && in_array("session",$tables)) {
	$where.=" AND iduser=$GLOBALS[tableprefix]users.id";
      }
#ifndef LODELLIGHT
    } // revue
#endif
    /////////

    if ($order) { $order="ORDER BY ".substr(prefix_tablename($order),0,-3); } // enelve le , a la fin
    if ($limit) { parse_variable($limit,FALSE); $limit="LIMIT ".$limit; }

    if ($where) parse_variable($where,FALSE);

    if (!$nom) {
      srand ((double) microtime() * 1000000);
      $nom="number".rand();
    }

    if (!$boucles[$nom][type]) $boucles[$nom][type]="def"; # marque la boucle comme definie, s'il elle ne l'ai pas deja
    $issql=$boucles[$nom][type]=="sql";

    ////// c'est inutile de faire une boucle, il faut juste faire un passage, la boucle est dans parse_boucle.
    # ici attr contient la fin du fichier.
    # on cherche s'il y a des boucles interieures
#    do {
## cherche le tag de fin
#      $fin = strpos($text,$tag_fin,$offset);
#      if ($fin===FALSE) { die ("erreur: la boucle ne se termine pas"); }
## cherche s'il y a une deuxieme boucle a l'interieur
#      $debut2=strpos($text,$tag_debut,$offset);
#      $sndbcl=!($debut2===FALSE) && $debut2<$fin;
#      if ($sndbcl)	parse_boucle($text,$fct_txt,$offset); // oui, on le traite d'abord
#    } while($sndbcl);
#
#


# cherche le tag de fin
      $fin = strpos($text,$tag_fin,$offset);
      if ($fin===FALSE) { die ("erreur: la boucle ne se termine pas"); }
# cherche s'il y a une deuxieme boucle a l'interieur
      $debut2=strpos($text,$tag_debut,$offset);
      $sndbcl=!($debut2===FALSE) && $debut2<$fin;
      if ($sndbcl) {
	parse_boucle($text,$fct_txt,$offset); // oui, on le traite d'abord
	$fin = strpos($text,$tag_fin,$offset);
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


function prefix_tablename ($tablename)

{
#ifndef LODELLIGHT
  return $tablename;
#else
#  preg_match_all("/\b(\w+)\./",$tablename,$result);
#  foreach ($result[1] as $tbl) {
#    $tablename=preg_replace ("/\b$tbl\./",$GLOBALS[tableprefix].$tbl.".",$tablename);
#  }
#  //  echo $tablename,"<br>";
#  return $tablename;
#endif
}
    



function decode_content ($content,$tables=array())

{
  global $home,$balisesdocument_lieautexte,$balisesdocument_nonlieautexte;
  $ret=array();

# cherche s'il y a un avant
#  $balises=array("avant","apres","premier","dernier","corps");
  $balises=array("BEFORE","AFTER","FIRST","LAST","CORPS");
  
  foreach ($balises as $balise) {
    if ((strpos($content,"<$balise>")!==FALSE || strpos($content,"<".strtoupper($balise).">")!==FALSE)) {
      if (!preg_match ("/<$balise>(.*?)<\/$balise>/s",$content,$result)) { die ("la balise $balise n'est pas fermee dans la boucle $nom"); }
      $ret[$balise]=$result[1];
      $content=str_replace($result[0],"",$content); // enleve le bloc avant
    }
  }

  // cherche s'il y a un sinon
  $ret[ALTERNATIVE]="";
  $alter="<ALTERNATIVE/>";
  $sinonpos=strpos($content,$alter); // en majuscules
  if ($sinonpos!==FALSE) {
    $ret[ALTERNATIVE]='<? else {?>'.substr($content,$sinonpos+strlen($alter)).'<?}?>'; // recupere le bloc sinon
    $content=substr($content,0,$sinonpos); // recupere le bloc avant sinon
  }
  if ($ret[CORPS]) {
    if (trim($content)) die("Une partie du contenu de la boucle $nom n'est pas dans l'une des balises &lt;corps&gt;  &lt;avant&gt;  &lt;apres&gt; &lt;premier&gt; &lt;dernier&gt;<br>");
  } else {
    $ret[CORPS]=$content;
  }
  // OPTIMISATION
  // cherche les META et les extract
  $balises=array("FIRST","LAST","CORPS");

  foreach ($balises as $balise) {
    //
    // meta
    //
    if (strpos($ret[$balise],"[#META_")!==FALSE || strpos($ret[$balise],"[(#META_")!==FALSE)  {
      $ret["META_".$balise]='$context=array_merge($context,unserialize($context[meta]));';
    }
    //
    // est-ce qu'on veut le texte ?
    //
#ifndef LODELLIGHT
    if (in_array("documents",$tables)) {
      include_once("$home/balises.php");

      # as-t-on besoin des balises liees au texte ?
      if (preg_match_all("/\[\(?#(".join("|",$balisesdocument_lieautexte).")\b/i",$ret[$balise],$result,PREG_PATTERN_ORDER)) {
	$withtextebalises='"'.join('","',$result[1]).'"';
      } else {
	$withtextebalises="";
      }

      # as-t-on besoin de balises non liees au texte
      if (preg_match_all("/\[\(?#(".join("|",$balisesdocument_nonlieautexte).")\b/i",$ret[$balise],$result,PREG_PATTERN_ORDER))  {
	$ret["EXTRACT_".$balise]='$filename="lodel/txt/r2r-$context[id].xml";
if (file_exists($filename)) {
include_once ("$GLOBALS[home]/xmlfunc.php");
$text=join("",file($filename));
$arr=array("'.join('","',$result[1]).'");';
	if ($withtextebalises) { // on a aussi besoin des balises liees au texte
	  $ret["EXTRACT_".$balise].='if ($context[textepublie] || $GLOBALS[visiteur]) array_push ($arr,'.$withtextebalises.');';
	}
	$ret["EXTRACT_".$balise].='$context=array_merge($context,extract_xml($arr,$text)); }';
      } elseif ($withtextebalises) { // les balises liees au texte seulement... ca permet d'optimiser un minimum. On evite ainsi d'appeler le parser xml quand le texte n'est pas publie.
	$ret["EXTRACT_".$balise]='if ($context[textepublie] || $GLOBALS[visiteur]) {
$filename="lodel/txt/r2r-$context[id].xml";
if (file_exists($filename)) {
include_once ("$GLOBALS[home]/xmlfunc.php");
$text=join("",file($filename));
$context=array_merge($context,extract_xml('.$withtextbalises.',$text));
}}';
      }
    } // table documents ?
#endif
    //
    // est-ce qu'on veut le prev et next publication ?
    //
    if (in_array("publications",$tables) && preg_match("/\[\(?#(PREV|NEXT)PUBLICATION\b/",$ret[$balise])) {
      $ret["EXTRACT_".$balise]='include_once("$GLOBALS[home]/func.php"); export_prevnextpublication(&$context);';
    }
  } // foreach
  return $ret;
}



function make_boucle_code ($nom,$tables,$where,$order,$limit,$content,&$fct_txt)

{
#ifndef LODELLIGHT
  if (in_array("revue",$tables) || in_array("session",$tables)) {
    $mysqlquery='mysql_db_query($GLOBALS[database],';
    $postquery='mysql_select_db($GLOBALS[currentdb]);';
  } else {
    $mysqlquery='mysql_query(';
  }

  // traitement particulier
  if (in_array("documents",$tables)) {
    $extrafield=",(datepubli<=NOW()) as textepublie";
  }
#else
#    $mysqlquery='mysql_query(';
#endif

  $table=$GLOBALS[tableprefix].join (', $GLOBALS[tableprefix]',array_reverse(array_unique($tables)));



  $contents=decode_content($content,$tables);
# genere le code pour parcourir la boucle
  $fct_txt.='function boucle_'.$nom.' ($context)
{
 $generalcontext=$context;
 $result='.$mysqlquery.'"SELECT *'.$extrafield.' FROM '."$table $where $order $limit".'") or die (mysql_error());
'.$postquery.'
 $nbrows=mysql_num_rows($result);
 $count=0;
 if ($row=mysql_fetch_assoc($result)) {
?>'.$contents[BEFORE].'<?
    do {
      $context=array_merge ($generalcontext,$row);
      $context[count]=$count;
      $count++;';
  // gere le cas ou il y a un premier
  if ($contents[FIRST]) {
    $fct_txt.=' if ($count==1) { '.$contents[META_FIRST].$contents[EXTRACT_FIRST].' ?>'.$contents[FIRST].'<? continue; }';
  }
  // gere le cas ou il y a un dernier
  if ($contents[LAST]) {
    $fct_txt.=' if ($count==$nbrows) { '.$contents[META_LAST].$contents[EXTRACT_LAST].'?>'.$contents[LAST].'<? continue; }';
  }    
    $fct_txt.=$contents[META_CORPS].$contents[EXTRACT_CORPS].' ?>'.$contents[CORPS].'<?    } while ($row=mysql_fetch_assoc($result));
?>'.$contents[AFTER].'<?  } ?>'.$contents[ALTERNATIVE].'<?
 mysql_free_result($result);
}
';
}


function make_userdefined_boucle_code ($nom,$content,&$fct_txt)

{
  $contents=decode_content($content);

// cree la fonction boucle
  $fct_txt.='function code_boucle_'.$nom.' ($context) { ?>'.$contents[CORPS].'<? }';

  if ($contents[BEFORE]) { // genere le code de avant
  $fct_txt.='function code_avant_'.$nom.' ($context) { ?>'.$contents[BEFORE].'<? }';
  }

  if ($contents[AFTER]) {// genere le code de apres
  $fct_txt.='function code_apres_'.$nom.' ($context) { ?>'.$contents[AFTER].'<? }';
 }

//
//  if ($contents[sinon]) {// genere le code de sinon
//  $fct_txt.='function code_sinon_'.$nom.' ($context) { ?'.'>'.$contents[sinon].'<'.'? }';
// }
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

