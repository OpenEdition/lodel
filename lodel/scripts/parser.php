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


function parse($in,$out)

{
  $parser=new LodelParser;
  $parser->parse($in,$out);
}


class Parser {

  var $infilename;
  var $signature;
  var $variable_regexp="[A-Z][A-Z_0-9]*";
  var $loops=array();
  var $fct_txt;

#  var $wantedvars;
  var $looplevel=0;

  var $arr;
  var $countarr;
  var $linearr;
  var $currentline;
  var $ind;
  var $refresh="";

  var $isphp; // the parser produce a code which produce either html, either php. In the latter, a sequence must be written at the beginning to inform the cache system.

  var $id="";


function errmsg ($msg,$ind=0) { 
  if ($ind) $line="line ".$this->$linearr[$ind];
  die("LODELSCRIPT ERROR line $line (".$this->infilename."): $msg");
}

function parse_loop_extra(&$tables,
			  &$tablesinselect,&$extrainselect,
			  &$select,&$where,&$ordre,&$groupby,&$having) {}
function parse_variable_extra ($nomvar) { return FALSE; }
function decode_loop_content_extra ($balise,&$content,&$options,$tables) {}
function prefix_tablename ($tablename) { return $tablename; }



function parse ($in,$out)

{
  global $sharedir;

  $this->infilename=$in;
  if (!file_exists($in)) $this->errmsg ("Unable to read file $in");
  $this->signature=preg_replace("/\W+/","_",$out);

  // read the file
  if (!function_exists("file_get_contents")) {
    $fp=fopen ($in,"r"); 
    while (!feof($fp)) $file.=fread($fp,1024);
    fclose($fp);
  } else {
    $file = file_get_contents($in);
  }

  $contents=stripcommentandcr($file);

  // search the CONTENT tag
  if (preg_match("/<CONTENT\b([^>]*)>/",$contents,$result)) {
    // attribut charset
    if (preg_match("/\bCHARSET\s*=\s*\"([^\"]+)\"/",$result[1],$result2)) {
      $charset=$result2[1];
    } else {
      $charset="iso-8859-1";
    }
    // attribut refresh
    $this->checkforrefreshattribut($result[1]);

    $contents=str_replace($result[0],"",$contents); // efface la balise
  } else {
    $charset="iso-8859-1";
  }

  // look for MACROFILE to be included
  preg_match_all("/<USE\s+MACROFILE\s*=\s*\"([^\"]+)\"\s*>\s*\n?/",$contents,$results,PREG_SET_ORDER);

  // delete the </USE>
  $contents=str_replace("</USE>","",$contents);

  foreach($results as $result) {
    $contents=str_replace($result[0],"",$contents); // efface le use
    $macrofile=$result[1];
    if (file_exists("tpl/".$macrofile)) {
      $macros.=join('',file("tpl/".$macrofile));
    } elseif ($sharedir && file_exists($sharedir."/macros/".$macrofile)) {
      $macros.=join('',file($sharedir."/macros/".$macrofile));
    } else {
      $this->errmsg ("the macro file \"$result[1]\" doesn't exist");
    }
  }
  $macros=stripcommentandcr($macros);

  // parse  macros
  $this->parse_macros($contents,$macros);

  $contents = preg_replace(array("/<USE\s+TEMPLATEFILE\s*=\s*\"([^\"]+)\"\s*>/",
				 "/^\s+/m"),
			   array('<?php insert_template(\$context,"\\1"); ?>',
				 ""),
				 $contents);


  $commands="LOOP|IF|LET|ELSE|DO|DOFIRST|DOLAST|BEFORE|AFTER|ALTERNATIVE|ESCAPE";
  $this->arr=preg_split("/<(\/?(?:$commands))\b([^>]*)>/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);
  $this->ind=0;
  $this->currentline=0;
  $this->countarr=count($this->arr);
  $this->fct_txt="";

  // parse les variables
  $this->parse_variable($this->arr[0]);
  for($i=1; $i<$this->countarr; $i+=3) {
    $this->parse_variable($this->arr[$i+1],FALSE); // parse the attributs
    $this->parse_variable($this->arr[$i+2]); // parse the content
  }
  // fin

  $this->parse_main();

  if ($this->ind!=$this->countarr) $this->errmsg("this file contains more closing tags than opening tags");

  $contents=join("",$this->arr);

  $this->parse_texte($contents);

  if ($this->fct_txt) {
    $contents='<?php 
'.$this->fct_txt.'?>'.$contents;
  }


  //
  // refresh manager
  //
  #die("::".$this->refresh);

  if ($this->refresh) {
    $code='<'.'?php if ($GLOBALS[cachedfile] && !$dontcheckrefresh) { $cachetime=filemtime($GLOBALS[cachedfile].".php"); ';

    // refresh period in second
    if (preg_match("/^\d+$/",$this->refresh)) {
      $code.=' if($cachetime+'.$this->refresh.'<time()) return "refresh"; ';

    // refresh time
    } else {
      $code.='$now=time(); $date=getdate($now);';

      $refreshtimes=preg_split("/,/",$this->refresh);
      foreach ($refreshtimes as $refreshtime) {
	$refreshtime=preg_split("/:/",$refreshtime);
	$code.='$refreshtime=mktime('.intval($refreshtime[0]).','.intval($refreshtime[1]).','.intval($refreshtime[2]).',$date[mon],$date[mday],$date[year]);';
	$code.='if ($cachetime<$refreshtime && $now>$refreshtime) return "refresh"; ';
      }
    }
    $code.='} ?'.'>';
    $contents='<'.'?php echo \''.quote_code($code).'\'; ?>
'.$contents;
    $this->isphp=FALSE;
  }
  
  if ($this->isphp) {
    $contents='<?php if ($GLOBALS[cachedfile]) echo \'<?php ?>\'; ?>'.$contents; // this is use to check if the output is a must be evaluated as a php or a raw file.
  }

  // clean the open/close php tags
  $contents=preg_replace(array('/\?><\?(php\b)?/',
			       '/<\?[\s\n]*\?>/'),array("",""),$contents);

  if ($charset!="utf-8") {
    #$t=microtime();
    require_once(TOINCLUDE."utf8.php"); // conversion des caracteres
    $contents=utf8_encode($contents);
    convertHTMLtoUTF8($contents);
  }


  @unlink($out); // detruit avant d'ecrire.
  $fp=fopen ($out,"w") or $this->errmsg("cannot write file $out");
  fputs($fp,$contents);
  fclose($fp); 
  if ($GLOBALS[filemask]) chmod ($out,0666 & octdec($GLOBALS[filemask]));

  return $ret;
}




//
// cette fonction contient des specificites a Lodel.
// il faut voir si on decide que les minitexte font partie de lodelscript ou de lodel.
//

function parse_texte(&$text)

{
  global $droitediteur;
  preg_match_all("/<TEXT\s*NAME=\"([^\"]+)\"\s*>/",$text,$results,PREG_SET_ORDER);
#  print_r($results);
  foreach ($results as $result) {
    $nom=addslashes(stripslashes($result[1]));
    if ($droitediteur) {       // cherche si le texte existe
      require_once(TOINCLUDE."connect.php");
      $result2=mysql_query("SELECT id FROM $GLOBALS[tp]textes WHERE nom='$nom'") or $this->errmsg (mysql_error());
      if (!mysql_num_rows($result2)) { // il faut creer le texte
	mysql_query("INSERT INTO $GLOBALS[tp]textes (nom,texte) VALUES ('$nom','')") or $this->errmsg (mysql_error());
      }
    }
    $text=str_replace ($result[0],'<?php require_once("'.TOINCLUDE.'connect.php"); $result=mysql_query("SELECT id,texte FROM $GLOBALS[tp]textes WHERE nom=\''.$nom.'\' AND statut>0"); list($id,$texte)=mysql_fetch_row($result); if ($context[droitediteur]) { ?><p><a href="lodel/admin/texte.php?id=<?php echo $id; ?>">[Modifier]</a></p> <?php } echo preg_replace("/(\r\n?\s*){2,}/","<br />",$texte); ?>',$text);
  }
}


function parse_variable (&$text,$escape="php")

{
  $lang_regexp="[A-Za-z]{2}";
  $filtre_regexp="[A-Za-z][A-Za-z_0-9]*(?:\(.*?\))?";

# traite les sequences [...(#BALISE)...]

  while (preg_match("/(\[[^\[\]]*?)\((#$this->variable_regexp(?::$lang_regexp)?(?:\|$filtre_regexp)*)\)([^\[\]]*?\])/s",$text,$result)) {
####    $expr=preg_replace("/^#($this->variable_regexp):($lang_regexp)/","#\\1_LANG\\2",$result[2]);

  // remplace la langue
    $expr=preg_replace("/^#($this->variable_regexp):($lang_regexp)/","#\\1|multilingue('\\2')",$result[2]);

# parse les filtres
    if (preg_match("/^#($this->variable_regexp)((?:\|$filtre_regexp)*)$/",$expr,$subresult)) {
      $block=$subresult[0];

      $variable=$this->parse_variable_extra($subresult[1]); // traitement particulier ?
      if ($variable===FALSE) { // non, traitement normal
	$variable="\$context[".strtolower($subresult[1])."]";
      }
      foreach(explode("|",$subresult[2]) as $fct) {
	if ($fct=="false" || $fct=="true" || $fct=="else") {
	  break;
	} elseif ($fct) {
	  // recupere les arguments de la fonction
	  if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)\((.*?)\)$/",$fct,$result2)) { $args=",".$result2[2]; $fct=$result2[1]; } else { $args=""; }
	  $variable=$fct."(".$variable.$args.")";
	}
      }
    }
    $pre=substr($result[1],1);
    $post=substr($result[3],0,-1);
    if ($fct=="false") {
      $code='<?php if (!('.$variable.')) { ?>'.$pre.$post.'<?php } ?>';
    } elseif ($fct=="true") {
      $code='<?php if ('.$variable.') { ?>'.$pre.$post.'<?php } ?>';
    } elseif ($fct=="else") {
      if ($escape!="php") $this->errmsg("ERROR: else pipe function can't eb used in this context");
      $code='<?php $tmpvar='.$variable.'; if ($tmpvar) { echo "$tmpvar"; } else { ?>'.$pre.$post.'<?php } ?>';
    } elseif ($escape=="php") { // traitement normal, php espace
      $code='<?php $tmpvar='.$variable.'; if ($tmpvar) { ?>'.$pre.'<?php echo "$tmpvar"; ?>'.$post.'<?php } ?>';
    } elseif ($escape=="quote") { // normal processing. quotemark esapce.
      $code="&lodelparserquot;.$variable.&lodelparserquot;";
    } else { // normal processing. no espace
      $code=$variable;
    }
    $text=str_replace($result[0],$code,$text);
  } // while variables with pipe function

  if ($escape=="php") {
    $pre='<?php echo '; $post='; ?>';
  } elseif ($escape=="quote")  {
    $pre="&lodelparserquot;."; $post=".&lodelparserquot;";
  } else {
    $pre=""; $post="";
  }
# search for variables without pipe function
  while (preg_match("/\[\#($this->variable_regexp)(:$lang_regexp)?\]/",$text,$result)) {
    $variable=$this->parse_variable_extra($result[1]); // traitement particulier ?
    if ($variable!==FALSE) { // traitement particulier
      $variable=$pre.$variable.$post;
    } else { // non traitement normal
      if ($result[2]) { // langue
	$pre.="multilingue(";
	$post=",'".substr($result[2],1)."')".$post;
      }
      $variable=$pre.'$context['.strtolower($result[1]).']'.$post;
    }
    $text=str_replace($result[0],$variable,$text);
  }
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

/*
function parse_main2()

{
  if ($this->ind==0) {
    $this->ind=1;
  }
  while ($this->ind<$this->countarr) {
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
*/


function parse_main()

{
  if ($this->ind==0) {
    $this->ind=1;
  }
  while ($this->ind<$this->countarr) {
    if (substr($this->arr[$this->ind],0,1)=="/") {
      if ($this->arr[$this->ind+1]) $this->errmsg("The closing tag ".$this->arr[$this->ind]." is malformed");
      return;
    }
    switch($this->arr[$this->ind]) {
    case "LOOP" : $this->parse_loop();
      break;
    case "IF" : $this->parse_condition();
      break;
    case "LET" : $this->parse_let();
      break;
    case "ESCAPE" : $this->parse_escape_code();
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
  $groupby="";
  $dontselect=array();
  $wheres=array();
  $tables=array();
  $arguments=array();

  preg_match_all("/\s*(\w+)=\"(.*?)\"/",$attrs,$results,PREG_SET_ORDER);

  // search the loop name and determin whether the loop is the definition of a SQL loop.
  $issqldef=FALSE;
  foreach ($results as $result) {
    if ($result[1]=="NAME") {
      if ($name) $this->errmsg("name already defined in loop $name",$this->ind);
      $name=trim($result[2]);
    } elseif ($result[1]=="TABLE") {
      $issqldef=TRUE;
    } elseif ($result[1]=="REFRESH") {
	$this->checkforrefreshattribut($result[0]);
    }
  }

  if ($issqldef) { // definition of a SQL loop.
    foreach ($results as $result) {
      $value=lodelparserunquote($result[2]);
      switch ($result[1]) {
      case "NAME":
	break;
      case "WHERE" :
	array_push($wheres,"(".trim(replace_conditions($value,"sql")).")");
	break;
      case "TABLE" :
	$arr=preg_split("/,/",$value);
	if ($arr) {
	  foreach ($arr as $value) {
	    array_push($tables,$GLOBALS['tableprefix'].trim($value));
	  }
	}
	break;
      case "ORDER" :
	array_push($orders,$value);
	break;
      case "LIMIT" :
	if ($limit) $this->errmsg("Attribut LIMIT should occur only once in loop $name",$this->ind);
	$limit=$value;
	break;
      case "GROUPBY" :
	if ($groupby) $this->errmsg("Attribut GROUPY should occur only once in loop $name",$this->ind);
	$groupby=$value;
	break;
      case "HAVING" :
	if ($having) $this->errmsg("Attribut HAVING should occur only once in loop $name",$this->ind);
	$having=$value;
	break;
      case "SELECT" :
	if ($dontselect) $this->errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name",$this->ind);
	#$select=array_merge($select,preg_split("/\s*,\s*/",$value));
	if ($select) $select.=",";
	$select.=$value;
	break;
      case "DONTSELECT" :
	if ($select) $this->errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name",$this->ind);
	$dontselect=array_merge($dontselect,preg_split("/\s*,\s*/",$value));
	break;
      case "REQUIRE":
	break;
      default:
	$this->errmsg ("unknown attribut \"$result[1]\" in the loop $name",$this->ind);
      }
    } // loop on the attributs
    // end of definition of a SQL loop
  } else {
    // ok, this is a SQL loop call or a user lopp
    // the attributs are put into $arguments.
    foreach ($results as $result) {
      if ($result[1]=="NAME") continue;
      $arguments[strtolower($result[1])]=lodelparserunquote($result[2]);
    }
  }


#  echo "enter loop $name:",$this->ind,"<br>\n";


  if (!$name) {
    $this->errmsg("the name of the loop on table(s) \"".join(" ",$tables)."\" is not defined",$this->ind);
  }

  $where=$this->prefix_tablename(join(" AND ",$wheres));
  $order=$this->prefix_tablename(join(",",$orders));

  //
  $tablesinselect=$tables; // ce sont les tables qui seront demandees dans le select. Les autres tables de $tables ne seront pas demandees
  $extrainselect=""; // texte pour gerer des champs supplementaires dans le select. Doit commencer par ,

  if (!$where) $where="1";
  $this->parse_loop_extra($tables,
			  $tablesinselect,$extrainselect,
			  $select,$where,$order,$groupby,$having);
  //


  if (!$this->loops[$name][type]) $this->loops[$name][type]="def"; // toggle the loop as defined, if it is not already
  $issql=$this->loops[$name][type]=="sql"; // boolean for the SQL loops


  if ($tables) { // loop SQL
    // check if the loop is not already defined with a different contents.
    if ($issql && $attrs!=$this->loops[$name][attr]) $this->errmsg ("loop $name cannot be defined more than once",$this->ind);

    // the loop is not defined yet, let's define.
    if (!$issql) { // the loop has to be defined
      $this->loops[$name][ind]=$this->ind; // save the index position
      $this->loops[$name][attr]=$attrs; // save an id
      $this->loops[$name][type]="sql"; // marque la loop comme etant une loop sql

      $this->decode_loop_content($name,$contents,$options,$tablesinselect);
      $this->make_loop_code($name.'_'.($this->signature),$tables,
			    $tablesinselect,$extrainselect,
			    $select,$dontselect,
			    $where,$order,$limit,$groupby,$having,
			    $contents,$options);
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
    $code='<?php loop_'.$name.'_'.($this->signature).'($context); ?>';
  } else {
    //
    if (!$issql) {// the loop is not defined yet, thus it is a user loop
      //
      $this->loops[$name][id]++; // increment the name count
      $newname=$name."_".$this->loops[$name][id]; // change the name in order to be unique
      $this->decode_loop_content($name,$contents,$options);
      $this->make_userdefined_loop_code ($newname,$contents,$arguments);
      // build the array for the arguments:
      $argumentsstr="";
      foreach ($arguments as $k=>$v) { $argumentsstr.="'$k'=>\"$v\",";}
      // clean a little bit, the "" quote
      $argumentsstr=preg_replace(array('/""\./','/\.""/'),array('',''),$argumentsstr);
      // make the loop call
      $code='<?php loop_'.$name.'($context,"'.$newname.'",array('.$argumentsstr.')); ?>';
      //
    } else { // the loop is an sql recurrent loop
      //
      $code='<?php loop_'.$name.'_'.($this->signature).'($context); ?>';
      $this->ind+=3;
      if ($this->arr[$this->ind]!="/LOOP") $this->errmsg ("loop $name cannot be defined more than once");
      $this->loops[$name][recursive]=TRUE;
#      // copy the wanted variables from the original definition
#      print_r($this->wantedvars);
#      exit();
#      // we should remove from the wanted level 1, the provided variables
    }
  }
  if ($this->arr[$this->ind]!="/LOOP") {
    echo ": $this->ind ".$this->arr[$this->ind]."<br>\n";
    print_r($this->arr);
    $this->errmsg ("internal error in parse_loop. Report the bug");
  }
  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]=$code;
}


function decode_loop_content ($name,&$content,&$options,$tables=array())

{
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
    #$this->parse_main2();
    $this->parse_main();

    #echo "decode loop content $this->ind ",$this->arr[$this->ind]," $state<br>\n";
    if (isset($balises[$this->arr[$this->ind]])) { // opening
      $state=$this->arr[$this->ind];
      if ($content[$state]) $this->errmsg ("In loop $name, the block $state is defined more than once",$this->ind);
      $istart=$this->ind;
      $this->arr[$this->ind]="";
      $this->arr[$this->ind+1]="";

    } elseif ($this->arr[$this->ind]=="/".$state) { // closing
      for($j=$istart; $j<$this->ind; $j+=3) {
	for ($k=$j; $k<$j+3; $k++) {
	  $content[$state].=$this->arr[$k];
	  $this->arr[$k]="";
	}
#	  echo ":$loopind $j nwantedvars=",count($wantedvars[$j]),"\n";

/*	if ($this->wantedvars[$j]) { // transfer variables to the upper loop
	  $this->wantedvars[$loopind][$balises[$state]]=
	    array_merge($this->wantedvars[$loopind][$balises[$state]],
			$this->wantedvars[$j][0],
			$this->wantedvars[$j][1]);
	  unset($this->wantedvars[$j]);
	  }*/
	}

      $this->decode_loop_content_extra($state, $content,$options,$tables);
      $state="";
      $this->arr[$this->ind]="";
      $this->arr[$this->ind+1]="";
    } elseif ($state=="" && $this->arr[$this->ind]=="/LOOP") { // closing the loop
      $isendloop=1; break;
    }  elseif ($state) { // error
      $this->errmsg("&lt;$state&gt; not closed in the loop $name",$this->ind);
    } else { // another error
      $this->errmsg("unexpected &lt;".$this->arr[$this->ind]."&gt; in the loop $name",$this->ind);
    }
  } while ($this->ind<$this->countarr);



  if (!$isendloop) $this->errmsg ("end of loop $name not found",$this->ind);

  if ($content["DO"]) {
    // check that the remaining content is empty
    for($j=$loopind; $j<$this->ind; $j++) if (trim($this->arr[$j])) { $this->errmsg("In the loop $name, a part of the content is outside the tag DO",$j); }
  } else {
    for($j=$loopind; $j<$this->ind; $j+=3) {
      for ($k=$j; $k<$j+3; $k++) {
	$content["DO"].=$this->arr[$k];
	$this->arr[$k]="";
      }
#      if ($j>$loopind && $this->wantedvars[$j]) { // transfer variables to the upper loop
#	$this->wantedvars[$loopind][$balises["DO"]]=
#	  array_merge($this->wantedvars[$loopind][$balises["DO"]],
#		      $this->wantedvars[$j][0],
#		      $this->wantedvars[$j][1]);
#	unset($this->wantedvars[$j]);
#      }
    }
    $this->decode_loop_content_extra ("DO", $content,$options,$tables);
  }
}


function make_loop_code ($name,$tables,
			 $tablesinselect,$extrainselect,
			 $select,$dontselect,
			 $where,$order,$limit,$groupby,$having,
			 $contents,$options)

{
  static $tablefields; // charge qu'une seule fois

  if ($where) $where="WHERE ".$where;
  if ($order) $order="ORDER BY ".$order;
  if ($having) $having="HAVING ".$having;
  if ($groupby) $groupby="GROUP BY ".$groupby; // besoin de group by ?

  // special treatment for limit when only one value is given.
  if ($limit && strpos($limit,",")===false) {
    $preprocesslimit='
     $currentoffset=intval(($_REQUEST[\'offset_'.$name.'\'])/'.$limit.')*'.$limit.';';
    $processlimit='
    $currenturl=basename($_SERVER[\'SCRIPT_NAME\'])."?";
    if ($_REQUEST[\'QUERY_STRING\']) $currenturl.=$_SERVER[\'QUERY_STRING\']."&";
 if ($context[nbresultats]>'.$limit.') { 
$context[nexturl]=$currenturl."offset_'.$name.'=".($currentoffset+'.$limit.');
$context[nbresultats]--;
} else {
$context[nexturl]="";
}
$context[previousurl]=$currentoffset>='.$limit.' ? $currenturl."offset_'.$name.'=".($currentoffset-'.$limit.') : "";
 ';
    $limit='".$currentoffset.",'.($limit+1);
  }
  if ($limit) $limit="LIMIT ".$limit;

  // traitement particulier additionnel

  # c'est plus complique que ca ici, car parfois la table est prefixee par la DB.

  // reverse the order in order the first is select in the last.
  $tablesinselect=array_reverse(array_unique($tablesinselect));
  $table=join (', ',array_reverse(array_unique($tables)));

  if ($dontselect) { // DONTSELECT
    // at the moment, the dontselect should not be prefixed by the table name !
    if (!$tablefields) require(TOINCLUDE."tablefields.php");
    if (!$tablefields) die("ERROR: internal error in decode_loop_content: table $table");

    $selectarr=array();
    foreach ($tablesinselect as $t) {
      $selectforthistable=array_diff($tablefields[$t],$dontselect); // remove dontselect from $tablefields
      if ($selectforthistable) { // prefix with table name
	array_push($selectarr,"$t.".join(",$t.",$selectforthistable));
      }
    }
    $select=join(",",$selectarr);
  } elseif (!$select && $tablesinselect) { // AUTOMATIQUE
    $select=join(".*,",$tablesinselect).".*";
  } 
#elseif ($select) { // SELECT
#    if (!$tablefields) require(TOINCLUDE."tablefields.php");
#    if (!$tablefields) die("ERROR: internal error in decode_loop_content: table $table");
#    // on prefix
#    $selectarr=array();
#    foreach ($tablesinselect as $t) {
#      // take only the fields which are in $select
#      $selectforthistable=array_intersect($tablefields[$t],$select);
#      if ($selectforthistable) { // prefix with table name
#	array_push($selectarr,"$t.".join(",$t.",$selectforthistable));
#      }
#    }
#    $select=join(",",$selectarr);
#  }
  if (!$select) $select="1";
#  echo "$select : $extrainselect<br />\n";
  $select.=$extrainselect;


  // fetch_assoc_func
  if (!$options[fetch_assoc_func]) $options[fetch_assoc_func]="mysql_fetch_assoc(";

#### $t=microtime();  echo "<br>requete (".((microtime()-$t)*1000)."ms): $query <br>";

//
// genere le code pour parcourir la loop
//
  $this->fct_txt.='function loop_'.$name.' ($context)
{'.$preprocesslimit.'
 $query="SELECT '.$select.' FROM '."$table $where $groupby $having $order $limit".'"; #echo htmlentities($query);
 $result=mysql_query($query) or mymysql_error($query,$name);
'.$postmysqlquery.'
 $context[nbresultats]=mysql_num_rows($result);
 '.$processlimit.' 
 $generalcontext=$context;
 $count=0;
 if ($row='.$options[fetch_assoc_func].'$result)) {
?>'.$contents['BEFORE'].'<?php
    do {
      $context=array_merge ($generalcontext,$row);
      $count++;
      $context[count]=$count;';
  // gere le cas ou il y a un premier
  if ($contents['DOFIRST']) {
    $this->fct_txt.=' if ($count==1) { '.$contents['PRE_DOFIRST'].' ?>'.$contents['DOFIRST'].'<?php continue; }';
  }
  // gere le cas ou il y a un dernier
  if ($contents['DOLAST']) {
    $this->fct_txt.=' if ($count==$context[nbresultats]) { '.$contents['PRE_DOLAST'].'?>'.$contents['DOLAST'].'<?php continue; }';
  }    
    $this->fct_txt.=$contents['PRE_DO'].' ?>'.$contents['DO'].'<?php    } while ($count<$generalcontext[nbresultats] && $row='.$options['fetch_assoc_func'].'$result));
?>'.$contents['AFTER'].'<?php  } ';

  if ($contents['ALTERNATIVE']) $this->fct_txt.=' else {?>'.$contents['ALTERNATIVE'].'<?php }';

    $this->fct_txt.='
 mysql_free_result($result);
}
';
}


function make_userdefined_loop_code ($name,$contents)

{

// cree la fonction loop
  if ($contents['DO']) {
    $this->fct_txt.='function code_do_'.$name.' ($context) { ?>'.$contents['DO'].'<?php }';
  }
  if ($contents['BEFORE']) { // genere le code de avant
    $this->fct_txt.='function code_before_'.$name.' ($context) { ?>'.$contents['BEFORE'].'<?php }';
  }
  if ($contents['AFTER']) {// genere le code de apres
    $this->fct_txt.='function code_after_'.$name.' ($context) { ?>'.$contents['AFTER'].'<?php }';
  }
  if ($contents['ALTERNATIVE']) {// genere le code de alternative
    $this->fct_txt.='function code_alter_'.$name.' ($context) { ?>'.$contents['ALTERNATIVE'].'<?php }';
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
  // supprime les <DEFMACRO>.*?</DEFMACRO> et les </MACRO>
  $text=preg_replace(array("/<DEFMACRO\b[^>]*>.*?<\/DEFMACRO>\s*\n?/s","/<\/MACRO>/"),"",$text);

}


# traite les conditions avec IF
function parse_condition () 

{
  if (!preg_match("/\bCOND\s*=\s*\"([^\"]+)\"/",$this->arr[$this->ind+1],$cond)) $this->errmsg ("IF have no COND attribut",$this->ind);
  $cond[1]=replace_conditions($cond[1],"php");

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php if ('.$cond[1].') { ?>';

  do {
    $this->ind+=3;
    #$this->parse_main2();
    $this->parse_main();
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

  if (!preg_match("/\bVAR\s*=\s*\"([^\"]*)\"/",$this->arr[$this->ind+1],$result)) $this->errmsg ("LET have no VAR attribut");
  if (!preg_match("/^$this->variable_regexp$/i",$result[1])) $this->errmsg ("Variable \"$result[1]\"in LET is not a valide variable",$this->ind);
  $var=strtolower($result[1]);

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php ob_start(); ?>';

  $this->ind+=3;
  #$this->parse_main2();
  $this->parse_main();
  if ($this->arr[$this->ind]!="/LET") $this->errmsg("&lt;/LET&gt; expected, $this->arr[$this->ind] found",$this->ind);

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]='<?php $context['.$var.']=ob_get_contents();  ob_end_clean(); ?>';
}


function parse_escape_code()

{
  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]="";
  $this->arr[$this->ind+2]='<? if ($GLOBALS[cachedfile]) { echo \''.quote_code($this->arr[$this->ind+2]).'\'; } else {?>'.$this->arr[$this->ind+2].'<?php } ?>';
  $this->ind+=3;
  if ($this->arr[$this->ind]!="/ESCAPE") $this->errmsg("&lt;/ESCAPE&gt; expected, $this->arr[$this->ind] found",$this->ind);
  $this->arr[$this->ind]="";

  $this->isphp=TRUE;
}



function checkforrefreshattribut($text)

{
  if (preg_match("/\bREFRESH\s*=\s*\"([^\"]+)\"/",$text,$result2)) {
    $refresh=trim($result2[1]);
    $timere="(?:\d+(:\d\d){0,2})"; // time regexp
    if (!is_numeric($refresh) && !preg_match("/^$timere(?:,$timere)*$/",$refresh)) $this->errmsg("Invalid refresh time \"".$refresh."\"");
  }
  if (!$this->refresh || 
      (is_numeric($refresh) && 
       is_numeric($this->refresh) &&
       $refresh < $this->refresh)
      ) {
    $this->refresh=$refresh;
  } elseif (!is_numeric($refresh) && !is_numeric($this->refresh)) {
    $this->refresh.=",".$refresh;
  }
}

} // clase Parser

function replace_conditions($text,$style)

{
  return preg_replace(
	       array("/\bgt\b/i","/\blt\b/i","/\bge\b/i","/\ble\b/i","/\beq\b/i","/\bne\b/i","/\band\b/i","/\bor\b/i"),
	       array(">","<",">=","<=",($style=="sql" ? "=" : "=="),"!=","&&","||"),$text);
}


function lodelparserunquote($text) {
  return str_replace("&lodelparserquot;","\"",$text);
}

function stripcommentandcr(&$text)

{
  return preg_replace (array("/\r/","/<!--\[.*?\]-->\s*\n?/s"),
		       array("",""),
		       $text);
#  return preg_replace (array("/\r/",
#			     "/(<SCRIPT\b[^>]*>[\s\n]*)<!--+/i",
#			     "/--+>([\s\n]*<\/SCRIPT>)/i",
#			     "/<!--.*?-->\s*\n?/s",
#			     "/<SCRIPT\b[^>]*>/i",
#			     "/<\/SCRIPT>/i"
#			     ),
#		       array("",
#			     "\\1",
#			     "\\1",
#			     "",
#			     "\\0<!--",
#			     "-->\\0")
#		       ,$text);
}


function quote_code($text) {
  return addcslashes($text,"'");
}


?>
