<?php
function parse($in,$out)

{
  $parser=new LodelParser;
  $parser->parse($in,$out);
}


class Parser {

  var $variable_regexp="[A-Z][A-Z_0-9]*";
  var $loops=array();
  var $fct_txt;

  var $wantedvars;
  var $looplevel=0;

  var $arr;
  var $countarr;
  var $linearr;
  var $currentline;
  var $ind;

  var $id="";


function errmsg ($msg,$ind=0) { 
  if ($ind) $line="line ".$this->$linearr[$ind];
  die("ERROR $line: $msg");
}

function parse_loop_extra(&$tables,
			  &$tablesinselect,&$extrainselect,
			  &$where,&$ordre,&$groupby) {}
function parse_variable_extra ($nomvar) { return FALSE; }
function decode_loop_content_extra ($balise,&$ret,$tables) {}
function prefix_tablename ($tablename) { return $tablename; }


function parse ($in,$out)

{
  global $sharedir;
  if (!file_exists($in)) $this->errmsg ("Unable to read file $in");

  // read the file
  if (!function_exists("file_get_contents")) {
    $fp=fopen ($in,"r"); 
    while (!feof($fp)) $file.=fread($fp,1024);
    fclose($fp);
  } else {
    $file = file_get_contents($in);
  }

  $contents=stripcommentandcr($file);

  $contents = preg_replace("/<USE\s+TEMPLATEFILE\s*=\s*\"([^\"]+)\"\s*>/",
			   '<?php insert_template(\$context,"\\1"); ?>',$contents);

  // search the CONTENT tag
  if (preg_match("/<CONTENT\b([^>]*)>/",$contents,$result)) {
    if (preg_match("/CHARSET\s*=\s*\"[^\"]+\"/",$result[1],$result2)) {
      $charset=$result[1];
    } else {
      $charset="iso-8859-1";
    }
    $contents=str_replace($result[0],"",$contents); // efface la balise
  } else {
    $charset="iso-8859-1";
  }

  // look for MACROFILE to be included
  preg_match_all("/<USE\s+MACROFILE\s*=\s*\"([^\"]+)\"\s*>\s*\n?/",$contents,$results,PREG_SET_ORDER);

  foreach($results as $result) {
    $contents=str_replace($result[0],"",$contents); // efface le use
    $macrofile=$result[1];
    if (file_exists("tpl/".$macrofile)) {
      $macros.=join('',file("tpl/".$macrofile));
    } elseif ($sharedir && file_exists($sharedir."/macros/".$macrofile)) {
      $macros.=join('',file($sharedir."/macros/".$macrofile));
    } else {
      $this->errmsg ("the macro file \"$result[1]\" does exist");
    }
  }
  $macros=stripcommentandcr($macros);

  // parse  macros
  $this->parse_macros($contents,$macros);

  $commands="LOOP|IF|LET|ELSE|DO|DOFIRST|DOLAST|BEFORE|AFTER|ALTERNATIVE";
  $this->arr=preg_split("/<(\/?(?:$commands))\b([^>]*)>/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);
  $this->ind=0;
  $this->currentline=0;
  $this->countarr=count($this->arr);
  $this->fct_txt="";


  $this->parse_main1();
#  echo "----\n";
#  print_r($this->arr);
#  print_r($this->wantedvars);
#  echo "____\n";
#  $res=array_count_values($this->arr);
#  echo ":",$res["LOOP"]," ",$res["/LOOP"],"   ",$res["DO"]," ",$res["/DO"],"\n<br>";


  $this->parse_main2();
  if ($this->ind!=$this->countarr) $this->errmsg("too many closing tags at the end of the file");

  $contents=join("",$this->arr);

  $this->parse_texte($contents);

  $contents='<?php 
require_once ($home."connect.php");
'.$this->fct_txt.'?>'.$contents;

  $contents=preg_replace(array('/\?><\?(php\b)?/',
			       '/<\?[\s\n]*\?>/'),array("",""),$contents);

  if ($charset!="utf-8") {
    #$t=microtime();
    require_once($home."utf8.php"); // conversion des caracteres
    $contents=utf8_encode($contents);
    convertHTMLtoUTF8(&$contents);
  }
#  echo "out&:$out";

  $fp=fopen ($out,"w") or $this->errmsg("cannot write file $out");
  fputs($fp,$contents);
  fclose($fp); 
}




//
// cette fonction contient des specificites a Lodel.
// il faut voir si on decide que les minitexte font partie de lodelscript ou de lodel.
//

function parse_texte(&$text)

{
  global $home,$editeur,$urlroot,$site;
  preg_match_all("/<TEXT\s*NAME=\"([^\"]+)\"\s*>/",$text,$results,PREG_SET_ORDER);
#  print_r($results);
  foreach ($results as $result) {
    $nom=addslashes(stripslashes($result[1]));
    if ($editeur) {       // cherche si le texte existe
      include_once($home."connect.php");
      $result2=mysql_query("SELECT id FROM $GLOBALS[tp]textes WHERE nom='$nom'") or $this->errmsg (mysql_error());
      if (!mysql_num_rows($result2)) { // il faut creer le texte
	mysql_query("INSERT INTO textes (nom,texte) VALUES ('$nom','')") or $this->errmsg (mysql_error());
      }
    }
    $urlbase=($GLOBALS[siteagauche] || !$site) ? $urlroot : $urlroot.$site."/";
    $text=str_replace ($result[0],'<?php $result=mysql_query("SELECT id,texte FROM textes WHERE nom=\''.$nom.'\' AND status>0"); list($id,$texte)=mysql_fetch_row($result); if ($context[editeur]) { ?><A HREF="'."$urlbase".'lodel/admin/texte.php?id=<?php echo $id; ?>">[Modifier]</A><BR><?php } echo $texte; ?>',$text);
  }
}

///////////// PARSE 1 /////////////////
//
// parse les variables

function parse_main1()

{
  $this->countlines(0);
  $this->parse_variable($this->arr[0]);
  $ind=1;
  $this->parse_main1_rec(&$ind,0);
}

// parse to do a array containg the wanted variables by each loop, either internal (level=1) either extrenal (level=0)
function parse_main1_rec(&$ind,$loopind)

{
  $level=1;
  while ($ind<$this->countarr) {
    switch($this->arr[$ind]) {
    case "LOOP" : 
      $this->countlines($ind);
      $this->wantedvars[$ind][0]=$this->parse_variable($this->arr[$ind+1],FALSE);
      if (preg_match('/\bNAME\s*=\s*"([^"]+)"/i',$this->arr[$ind+1],$result)) {
	$funcname="loop_".$result[1]."_require";
	if (function_exists($funcname)) $this->wantedvars[$ind][0]=array_merge($this->wantedvars[$ind][0],call_user_func($funcname));
      }
      $this->wantedvars[$ind][1]=$this->parse_variable($this->arr[$ind+2]);
      $ind+=3;
      $this->parse_main1_rec($ind,$ind-3);
      break;
    case "/LOOP" : return;
    case "DO" :
    case "/DO" :
    case "DOFIRST" :
    case "/DOFIRST" :
    case "DOLAST" :
    case "/DOLAST" :
    case "/AFTER" :
    case "/BEFORE" :
    case "/ALTERNATIVE" :
      $level=1;
      break;
    case "AFTER" :
    case "BEFORE" :
    case "ALTERNATIVE" :
      $level=0;
      break;
    }
    $this->countlines($ind);
    $this->wantedvars[$loopind][$level]=
      array_merge($this->wantedvars[$loopind][$level],
		  $this->parse_variable($this->arr[$ind+1],FALSE), // parse the attributs
		  $this->parse_variable($this->arr[$ind+2])); // parse the content
    $ind+=3;
  }
}


function parse_variable (&$text,$escape=TRUE)

{
  $lang_regexp="[A-Z]{2}";
  $filtre_regexp="[A-Za-z][A-Za-z_0-9]*(?:\(.*?\))?";

# traite les sequences [...(#BALISE)...]

  while (preg_match("/(\[[^\[\]]*?)\((#$this->variable_regexp(?::$lang_regexp)?(?:\|$filtre_regexp)*)\)([^\[\]]*?\])/s",$text,$result)) {
    $expr=preg_replace("/^#($this->variable_regexp):($lang_regexp)/","#\\1_LANG\\2",$result[2]);

# parse les filtres
    if (preg_match("/^#($this->variable_regexp)((?:\|$filtre_regexp)*)$/",$expr,$subresult)) {
      $block=$subresult[0];

      $variable=$this->parse_variable_extra($subresult[1]); // traitement particulier ?
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
      $code='<?php if (!('.$variable.')) { ?>'.$pre.$post.'<?php } ?>';
    } elseif ($fct=="true") {
      $code='<?php if ('.$variable.') { ?>'.$pre.$post.'<?php } ?>';
    } elseif ($escape) {
      $code='<?php $tmpvar='.$variable.'; if ($tmpvar) { ?>'.$pre.'<?php echo "$tmpvar"; ?>'.$post.'<?php } ?>';
    } else {
      $code=$variable;
    }
    $text=str_replace($result[0],$code,$text);
  } // while variables with pipe function

  if ($escape) {
    $pre='<?php echo "'; $post='"; ?>';
  } else {
    $pre=""; $post="";
  }
# search for variables without pipe function
  while (preg_match("/\[\#($this->variable_regexp)(:$lang_regexp)?\]/",$text,$result)) {
    $variable=$this->parse_variable_extra($result[1]); // traitement particulier ?
    if ($variable!==FALSE) { // traitement particulier
      $variable=$pre.$variable.$post;
    } else { // non traitement normal
      if ($result[2]) $result[1].="_LANG".substr($result[2],1);
      $variable=$pre.'$context['.strtolower($result[1]).']'.$post;
      }
    $text=str_replace($result[0],$variable,$text);
  }

  // search for wanted variables
  
  if (preg_match_all('/\$context\[('.strtolower($this->variable_regexp).')\]/',$text,$result,PREG_PATTERN_ORDER)) {
    return $result[1];
  }

  return array();
}


function countlines($ind)

{
  if ($ind==0) {
    $this->currentline+=substr_count($this->arr[$ind],"\n");
  } else {
    $this->linearr[$ind]=$this->currentline;
    $this->currentline+=
      substr_count($this->arr[$ind+1],"\n")+
      substr_count($this->arr[$ind+2],"\n");
  }
}


///////////// PARSE 2 /////////////////
//
// parse les instructions


function parse_main2()

{
  if ($this->ind==0) {
    $this->ind=1;
  }
#  print_r($this->arr);
#  exit();
#  if ($this->countarr==119) { print_r($this->arr); exit(); }
  while ($this->ind<$this->countarr) {
#    echo "$i $this->arr[$this->ind]<br>";
    if (substr($this->arr[$this->ind],0,1)=="/") return;
    switch($this->arr[$this->ind]) {
    case "LOOP" : $this->parse_loop($this->arr,$this->ind);
      break;
    case "IF" : $this->parse_condition($this->arr,$this->ind);
      break;
    case "LET" : $this->parse_let($this->arr,$this->ind);
      break;
      // returns
    case "ELSE" : return;
    case "DO" : return;
    case "DOFIRST" : return;
    case "DOLAST" : return;
    case "AFTER" : return;
    case "BEFORE" : return;
    case "ALTERNATIVE" : return;
      break;
    default:
      echo "$this->ind ".$this->arr[$this->ind];
      $this->errmsg("internal error in parse_main. Report the bug");
    }
    $this->ind+=3;
  }
}


function parse_loop()

{
  $attrs=$this->arr[$this->ind+1];

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]="";

  $name="";
  $orders=array();
  $limit="";
  $select="";
  $wheres=array();
  $tables=array();
  preg_match_all("/\s*(\w+)=\"(.*?)\"/",$attrs,$results,PREG_SET_ORDER);

  foreach ($results as $result) {
    $value=$result[2];
    switch ($result[1]) {
    case "WHERE" :
      array_push($wheres,"(".trim(replace_conditions($value)).")");
      break;
    case "TABLE" :
      array_push($tables,$value);
      break;
    case "ORDER" :
      array_push($orders,$value);
      break;
    case "LIMIT" :
      if ($limit) $this->errmsg("limit already defined in loop $name",$this->ind);
      $limit=$value;
      break;
    case "NAME":
      if ($name) $this->errmsg("name already defined in loop $name",$this->ind);
      $name=$value;
      break;
    case "SELECT" :
      $select=$value;
      break;
    case "REQUIRE":
      break;
    default:
      $this->errmsg ("unknow attribut \"$result[1]\" in the loop $name",$this->ind);
    }
  } // loop sur les attributs

#  echo "enter loop $name:",$this->ind,"<br>\n";


  if (!$name) {
    $this->errmsg("the name of the loop on table(s) \"".join(" ",$tables)."\" is not defined",$this->ind);
  }

  $where=$this->prefix_tablename(join(" AND ",$wheres));
  $order=$this->prefix_tablename(join(",",$orders));

  //
  $tablesinselect=$tables; // ce sont les tables qui seront demandees dans le select. Les autres tables de $tables ne seront pas demandees
  $extrainselect=""; // texte pour gerer des champs supplementaires dans le select. Doit commencer par ,
  $groupby="";

  $this->parse_loop_extra(&$tables,
			  &$tablesinselect,&$extrainselect,
			  &$where,&$order,&$groupby);
    //

  if ($where) {
    $where="WHERE ".$where;
  }
  if ($order) {
    $order="ORDER BY ".$order;
  }
  if ($limit) {
    $limit="LIMIT ".$limit;
  }


  if (!$this->loops[$name][type]) $this->loops[$name][type]="def"; # marque la loop comme definie, s'il elle ne l'ai pas deja
  $issql=$this->loops[$name][type]=="sql";


  if ($tables) { // loop SQL
    // verifie que la loop n'a pas ete defini sous le meme name avec un contenu different
    if ($issql && $attrs!=$this->loops[$name][attr]) $this->errmsg ("loop $name cannot be defined more than once",$this->ind);
    if (!$issql) { // the loop has to be defined
      $this->loops[$name][ind]=$this->ind; // save the index position
      $this->loops[$name][attr]=$attrs; // save an id
      $this->loops[$name][type]="sql"; // marque la loop comme etant une loop sql

      $contents=$this->decode_loop_content($name,$tablesinselect,$select!="*");
      $this->make_loop_code($name,$tables,
			    $tablesinselect,$extrainselect,
			    $where,$order,$limit,$groupby,$contents);
    } else { // boucle redefinie identiquement (enfin on espere)
      // on passe le contenu... on le connait deja
      $looplevel=1;
      do {
	$this->arr[$this->ind]="";
	$this->arr[$this->ind+1]="";
	$this->arr[$this->ind+2]="";
	$this->ind+=3;
	if ($this->arr[$this->ind]=="/LOOP") $looplevel--;
	if ($this->arr[$this->ind]=="LOOP") $looplevel++;
      } while ($this->ind<$this->countarr && $looplevel);
    }
    $code='<?php loop_'.$name.'($context); ?>';
  } else {
    #echo "ici issql",$issql;
    if (!$issql) {// la loop n'est pas deja definie...alors c'est une loop utilisateur
      $this->loops[$name][id]++; // increment le compteur de name de loop
      $newname=$name."_".$this->loops[$name][id]; // change le name pour qu'il soit unique
      $contents=$this->decode_loop_content($name);
      $this->make_userdefined_loop_code ($newname,$contents);
      $code='<?php loop_'.$name.'($context,"'.$newname.'"); ?>';
    } else {
      // loop sql recurrente
      $code='<?php loop_'.$name.'($context); ?>';
      $this->ind+=3;
      if ($this->arr[$this->ind]!="/LOOP") $this->errmsg ("loop $name cannot be defined more than once");
      // copy the wanted variables from the original definition
      $this->loops[$name][recursive]=TRUE;
#      print_r($this->wantedvars);
#      exit();
      // we should remove from the wanted level 1, the provided variables
    }
  }
  if ($this->arr[$this->ind]!="/LOOP") {
    echo ": $this->ind ".$this->arr[$this->ind]."<br>\n";
    print_r($this->arr);
    $this->errmsg ("internal error in parse_loop. Report the bug");
  }
#  echo "end loop $name: $this->ind\n<br>";
  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]=$code;
}


function decode_loop_content ($name,$tables=array(),$optimise=TRUE)

{
  global $home;

  $balises=array("DOFIRST"=>1,"DOLAST"=>1,"DO"=>1,"AFTER"=>0,"BEFORE"=>0,"ALTERNATIVE"=>0);
#  if ($this->ind==349) {
#    echo "loop $name ind=$loopind\n";
#    print_r($this->wantedvars);
#    echo "--\n";
#    print_r($this->arr);
#  }

  $loopind=$this->ind;
  do {
    $this->ind+=3;
    $this->parse_main2();

    #echo "decode loop content $this->ind ",$this->arr[$this->ind]," $state<br>\n";
    if (isset($balises[$this->arr[$this->ind]])) { // ouverture
      $state=$this->arr[$this->ind];
      if ($ret[$state]) $this->errmsg ("In loop $name, the block $state is defined more than once",$this->ind);
      $istart=$this->ind;
      $this->arr[$this->ind]="";
      $this->arr[$this->ind+1]="";

    } elseif ($this->arr[$this->ind]=="/".$state) {
      for($j=$istart; $j<$this->ind; $j+=3) {
	for ($k=$j; $k<$j+3; $k++) {
	  $ret[$state].=$this->arr[$k];
	  $this->arr[$k]="";
	}
#	  echo ":$loopind $j nwantedvars=",count($wantedvars[$j]),"\n";

	if ($this->wantedvars[$j]) { // transfer variables to the upper loop
	  $this->wantedvars[$loopind][$balises[$state]]=
	    array_merge($this->wantedvars[$loopind][$balises[$state]],
			$this->wantedvars[$j][0],
			$this->wantedvars[$j][1]);
	  unset($this->wantedvars[$j]);
	}
      }
      $this->decode_loop_content_extra ($balise, $tables, &$ret);
      $state="";
      $this->arr[$this->ind]="";
      $this->arr[$this->ind+1]="";
    } elseif ($state=="" && $this->arr[$this->ind]=="/LOOP") {
      $isendloop=1; break;
    }  else $this->errmsg("&lt;$state&gt; not closed in the loop $name",$this->ind);
  } while ($this->ind<$this->countarr);



#  if ($loopind==133) {   echo "ret="; print_r($ret); echo "ind=",$this->ind,"arr="; print_r($this->arr);  }
#  echo "loop: $name $loopind ",count($tables),"\n"; print_r($this->wantedvars); echo "-----\n\n";

  if (!$isendloop) $this->errmsg ("end of loop $name not found",$this->ind);

  if ($ret["DO"]) {
    // check that the remaining content is empty
#    echo "DO: $this->ind";
#    print_r($this->arr);
    for($j=$loopind; $j<$this->ind; $j++) if (trim($this->arr[$j])) { $this->errmsg("In the loop $name, a part of the content is outside the tag DO",$j); }
  } else {
    for($j=$loopind; $j<$this->ind; $j+=3) {
      for ($k=$j; $k<$j+3; $k++) {
	$ret["DO"].=$this->arr[$k];
	$this->arr[$k]="";
      }
      if ($j>$loopind && $this->wantedvars[$j]) { // transfer variables to the upper loop
	$this->wantedvars[$loopind][$balises["DO"]]=
	  array_merge($this->wantedvars[$loopind][$balises["DO"]],
		      $this->wantedvars[$j][0],
		      $this->wantedvars[$j][1]);
	unset($this->wantedvars[$j]);
      }
    }
    $this->decode_loop_content_extra ($balise, $tables, &$ret);
  }

#  echo "debut: $loopind end:$this->ind\n<br>";
#  if ($loopind==133) {   echo "ret="; print_r($ret); echo "ind=",$this->ind,"arr="; print_r($this->arr);  }
#  echo "loop $name ind=$loopind\n";
#  print_r($this->wantedvars);

  
  // partie privee et specifique pour le decodage du contenu.

  foreach ($balises as $balise => $level) {

  }

  if (!$tables) return $ret;
  // OPTIMISATION
#  echo "OPT:";

  // cherche les variables a extraire. Ceci permet d'optimiser le select dans le cas ou la base de donnee contient des gros champs. Ajoute ces variables dans les wantedvars au niveau present ou au dessus en fonction de la balise dans laquelle sont les variables.

  $vars=$this->wantedvars[$loopind][1];
#  echo "opt:$name"; print_r($this->loops[$name]);
  if ($this->loops[$name][recursive]) {
    $vars=array_merge($vars,$this->wantedvars[$loopind][0]);
  }
#  echo "OPT: $name ind=",$loopind," nvars=",count($vars),"\n";
  // is there variables to treat a our level ?
  if (!$vars) return $ret;

  if (!(@include_once("CACHE/tablefields.php")) || !$GLOBALS[tablefields]) require_once($home."tablefields.php");
  if (!$GLOBALS[tablefields]) die("ERROR: internal error in decode_loop_content: table $table");

  $selects=array();
  $knowvars=array();

#  print_r($vars);

  foreach($tables as $table) {
    if (!$GLOBALS[tablefields][$table]) {
      require_once($home."tablefields.php");
      if (!$GLOBALS[tablefields][$table]) die ("ERROR: unknown table $table");
    }
    $varstoselect=$optimize ? array_intersect($GLOBALS[tablefields][$table],$vars) : $GLOBALS[tablefields][$table];
    $knownvars=array_merge($knownvars,$vartoselect);
    foreach($varstoselect as $vartoselect) array_push($selects,"$table.$vartoselect");
  }

  // compute the vars we don't know at level 1
  $diff=array_diff($this->wantedvars[$loopind][1],$knownvars);
  // if any, transfer these variables at level 0
  if ($diff) {
    $this->wantedvars[$loopind][0]=array_merge($this->wantedvars[$loopind][0],$diff);
  }
  // no more a that level anyway.
  $this->wantedvars[$loopind][1]=array();

  $ret[select]=join(",",$selects);
#  echo "select:$name $ret[select]<br>";

  return $ret;
}


function make_loop_code ($name,$tables,
			   $tablesinselect,$extrainselect,
			   $where,$order,$limit,$groupby,$contents)

{
  // traitement particulier additionnel

  $table=$GLOBALS[tp].join (', $GLOBALS[tp]',array_reverse(array_unique($tables)));
  if ($groupby) $groupby="GROUP BY ".$groupby; // besoin de group by ?

  if (!$contents[select]) {
#    echo "loop: $name $ind=",$this->ind,"<br>\n";
    $this->errmsg("no variable is used in the loop $name. Is this loop really useful ?",$this->ind);
  }
  #$select=join(".*,",$tablesinselect).".*".$extrainselect;
  $select=$contents[select].$extrainselect; // optimised

#### $t=microtime();  echo "<br>requete (".((microtime()-$t)*1000)."ms): $query <br>";

# genere le code pour parcourir la loop
  $this->fct_txt.='function loop_'.$name.' ($context)
{
 $generalcontext=$context;
'.$premysqlquery.' $query="SELECT '.$select.' FROM '."$table $where $groupby $order $limit".'"; #echo htmlentities($query);
 $result=mysql_query($query) or mymysql_error($query,$name);
'.$postmysqlquery.'
 $nbrows=mysql_num_rows($result);
 $count=0;
 if ($row=mysql_fetch_assoc($result)) {
?>'.$contents[BEFORE].'<?php
    do {
      $context=array_merge ($generalcontext,$row);
      $count++;
      $context[count]=$count;';
  // gere le cas ou il y a un premier
  if ($contents[DOFIRST]) {
    $this->fct_txt.=' if ($count==1) { '.$contents[PRE_DOFIRST].' ?>'.$contents[DOFIRST].'<?php continue; }';
  }
  // gere le cas ou il y a un dernier
  if ($contents[DOLAST]) {
    $this->fct_txt.=' if ($count==$nbrows) { '.$contents[PRE_DOLAST].'?>'.$contents[DOLAST].'<?php continue; }';
  }    
    $this->fct_txt.=$contents[PRE_DO].' ?>'.$contents["DO"].'<?php    } while ($row=mysql_fetch_assoc($result));
?>'.$contents[AFTER].'<?php  } ';

  if ($contents[ALTERNATIVE]) $this->fct_txt.=' else {?>'.$contents[ALTERNATIVE].'<?php }';

    $this->fct_txt.='
 mysql_free_result($result);
}
';
}


function make_userdefined_loop_code ($name,$contents)

{

// cree la fonction loop
  if ($contents["DO"]) {
    $this->fct_txt.='function code_do_'.$name.' ($context) { ?>'.$contents["DO"].'<?php }';
  }
  if ($contents[BEFORE]) { // genere le code de avant
    $this->fct_txt.='function code_before_'.$name.' ($context) { ?>'.$contents[BEFORE].'<?php }';
  }
  if ($contents[AFTER]) {// genere le code de apres
    $this->fct_txt.='function code_after_'.$name.' ($context) { ?>'.$contents[AFTER].'<?php }';
  }
  if ($contents[ALTERNATIVE]) {// genere le code de alternative
    $this->fct_txt.='function code_alter_'.$name.' ($context) { ?>'.$contents[ALTERNATIVE].'<?php }';
  }
 // fin ajout
}


function parse_macros(&$text,&$macros)

{
  while (preg_match("/<MACRO(\s+NAME\s*=\s*\"(\w+)\")\s*>/",$text,$result)) {
    if (!$result[2]) { $this->errmsg ("MACRO tag malformed"); }
    // cherche la define
    $search="/<DEFMACRO\s+NAME\s*=\s*\"$result[2]\"\s*>(.*?)<\/DEFMACRO>/s";
    if (!preg_match_all($search,$text,$defs,PREG_SET_ORDER)) 
      if (!preg_match_all($search,$macros,$defs,PREG_SET_ORDER)) { $this->errmsg ("the macro $result[2] is not defined"); }
    $def=array_pop($defs); // recupere la derniere definission
    $def[1]=preg_replace("/(^\n|\n$)/","",$def[1]); // enleve le premier saut de ligne et le dernier
    $text=str_replace($result[0],$def[1],$text);
  }
  $text=preg_replace("/<DEFMACRO\b[^>]*>.*?<\/DEFMACRO>\s*\n?/s","",$text);
}


# traite les conditions avec IF
function parse_condition () 

{
  if (!preg_match("/\bCOND\s*=\s*\"([^\"]+)\"/",$this->arr[$this->ind+1],$cond)) $this->errmsg ("IF have no COND attribut",$this->ind);
  $cond[1]=replace_conditions($cond[1]);

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php if ('.$cond[1].') { ?>';

  do {
    $this->ind+=3;
    $this->parse_main2();
    if ($this->arr[$this->ind]=="ELSE") {
      if ($elsefound) $this->errmsg ("ELSE found twice in IF condition",$this->ind);
      $elsefound=1;
      $this->arr[$this->ind]="";
      $this->arr[$this->ind+1]='<?php } else { ?>';
    } elseif ($this->arr[$this->ind]=="/IF") {
      $isendif=1;
    } else $this->errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in IF condition",$this->ind);
  } while (!$isendif && $this->ind<$this->countarr);

  if (!$isendif) $this->errmsg("IF not closed",$this->ind);

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php } ?>';  
}



function parse_let () {

#  echo ":",$this->arr[$this->ind+1],"<br>\n";
  if (!preg_match("/\bVAR\s*=\s*\"([^\"]*)\"/",$this->arr[$this->ind+1],$result)) $this->errmsg ("LET have no VAR attribut");
  if (!preg_match("/^$this->variable_regexp$/i",$result[1])) $this->errmsg ("Variable \"$result[1]\"in LET is not a valide variable",$this->ind);
  $var=strtolower($result[1]);

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php ob_start(); ?>';

  $this->ind+=3;
  $this->parse_main2();
  if ($this->arr[$this->ind]!="/LET") $this->errmsg("&lt;LET&gt; expected; $this->arr[$this->ind] found",$this->ind);

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php $context['.$var.']=ob_get_contents();  ob_end_clean(); ?>';
}

}



function replace_conditions($text)

{
  return preg_replace(
	       array("/\bgt\b/i","/\blt\b/i","/\bge\b/i","/\ble\b/i","/\beq\b/i","/\bne\b/i","/\band\b/i","/\bor\b/i"),
	       array(">","<",">=","<=","==","!=","&&","||"),$text);
}

function stripcommentandcr(&$text)

{
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
}


?>
