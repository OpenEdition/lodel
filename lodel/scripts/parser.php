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


function parse($in,$out)

{
  $parser=new LodelParser;
  $parser->parse($in,$out);
}


class Parser {

  var $infilename;
  var $signature;
  var $variable_regexp="[A-Z][A-Z_0-9]*(?:\.[A-Z][A-Z_0-9]*)*";
  var $variablechar; // list of prefix for the variables

  var $loops=array();
  var $funcs=array();
  var $macrocode=array();

  var $charset;

  var $commands=array();
  var $codepieces=array(); // code piece definition
  var $macros_txt;
  var $fct_txt;


#  var $wantedvars;
  var $looplevel=0;

  var $arr;
  var $countarr;
  var $linearr;
  var $currentline;
  var $ind;
  var $refresh="";

  var $isphp=false; // the parser produce a code which produce either html, either php. In the latter, a sequence must be written at the beginning to inform the cache system.

  var $id="";


  function errmsg ($msg,$ind=0) { 
    if ($ind) $line="line ".$this->$linearr[$ind];
    die("LODELSCRIPT ERROR $line (".$this->infilename."): $msg");
  }

  function parse_loop_extra(&$tables,
			    &$tablesinselect,&$extrainselect,
			    &$selectparts) {}

  function parse_variable_extra ($prefix,$name) { return FALSE; }
  function parse_before($contents) {}
  function parse_after($contents) {}
  function decode_loop_content_extra ($balise,&$content,&$options,$tables) {}


 function Parser() { // constructor
   $this->commands=array("USE","MACRO","FUNC","LOOP","IF","LET","ELSE",
			 "DO","DOFIRST","DOLAST","BEFORE",
			 "AFTER","ALTERNATIVE","ESCAPE","CONTENT",
			 "SWITCH","CASE");

   $this->codepieces=array('sqlfetchassoc'=>"mysql_fetch_assoc(%s)",
			   'sqlquery'=>"mysql_query(%s)",
			   'sqlerror'=>"or mymysql_error(%s,%s)",
			   'sqlfree'=>"mysql_free_result(%s)",
			   'sqlnumrows'=>"mysql_num_rows(%s)"
			   );
 }



function parse ($in,$out)

{
  global $sharedir;

  $this->infilename=$in;
  if (!file_exists($in)) $this->errmsg ("Unable to read file $in");
  $this->signature=preg_replace("/\W+/","_",$out);
  $this->fct_txt="";


  // read the file
  if (!function_exists("file_get_contents")) {
    $fp=fopen ($in,"r"); 
    while (!feof($fp)) $file.=fread($fp,1024);
    fclose($fp);
  } else {
    $file = file_get_contents($in);
  }
  $contents=stripcommentandcr($file);

  $this->_split_file($contents); // split the contents into commands
  $this->parse_main();           // parse the commands

  if ($this->ind!=$this->countarr) $this->errmsg("this file contains more closing tags than opening tags");

  $contents=join("",$this->arr); // recompose the file
  unset($this->arr); // save memory now.
  $this->parse_after($contents); // user defined parse function

  // remove  <DEFMACRO>.*?</DEFMACRO>
  $contents=preg_replace("/<DEF(MACRO|FUNC)\b[^>]*>.*?<\/DEF(MACRO|FUNC)>\s*\n?/s","",$contents);

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
  } elseif ($this->isphp) {
    $contents='<?php if ($GLOBALS[cachedfile]) echo \'<?php #--# ?>\'; ?>'.$contents; // this is use to check if the output is a must be evaluated as a php or a raw file.
  }

  // clean the open/close php tags
  $contents=preg_replace(array('/\?><\?(php\b)?/',
			       '/<\?php[\s\n]*\?>/'),array("",""),$contents);

  if (!$this->charset) $this->charset="iso-8859-1";
  if ($this->charset!="utf-8") {
    #$t=microtime();
    require_once(TOINCLUDE."utf8.php"); // conversion des caracteres
    $contents=utf8_encode($contents);
    convertHTMLtoUTF8($contents);
  }
  @unlink($out); // detruit avant d'ecrire.
  $fp=fopen ($out,"w") or $this->errmsg("cannot write file $out");
  fputs($fp,$contents);
  fclose($fp); 
  if ($GLOBALS['filemask']) chmod ($out,0666 & octdec($GLOBALS['filemask']));

  return $ret;
}


 function parse_variable (&$text,$escape="php")

{
  $i=strpos($text,"[");

  while ($i!==false) {
    $startvar=$i;
    $i++;

    // parenthesis syntaxe [(
    if ($text{$i}=="(") {
      $para=true;
      $i++;
    } else {
      $para=false;
    }

    if ($text{$i}=="#" || strpos($text{$i},$this->variablechar)!==false) { // 
      $varchar=$text{$i}; $i++;
      // look for the name of the variable now
      if ($text{$i}<'A' || $text{$i}>'Z') continue; // not a variable
      $varname=$text{$i}; $i++;
      while (($text{$i}>='A' && $text{$i}<='Z') || 
	     ($text{$i}>='0' && $text{$i}<='9') || 
	     $text{$i}=="_" || $text{$i}==".") {
	$varname.=$text{$i}; $i++;
      }
      $pipefunction="";

      if ($text{$i}==":") { // a lang
	$lang=""; $i++;
	while ($text{$i}>='A' && $text{$i}<'Z') { $lang.=$text{$i}; $i++; }
	$pipefunction='multilingue("'.$lang.'")|';
      }

      if ($text{$i}=="|") { // have a pipe function
	// look for the end of the variable
	$bracket=1;
	$mustnewparse=false;
	while ($bracket) {
	  switch($text{$i}) {
	  case "[" : $bracket++;
	    $mustparse=true; // potentially a new variable
	    break;
	  case "]" : $bracket--;
	    break;
	  }
	  if ($bracket>0) $pipefunction.=$text{$i};
	  $i++;
	}
	$i--; // comes back to the bracket.
	if ($para && $pipefunction{strlen($pipefunction)-1}==")") {
	  $pipefunction=substr($pipefunction,0,-1);
	  $i--;
	}
	if ($mustparse) {
	  #$this->parse_variable($pipefunction,"quote");
	  $this->parse_variable($pipefunction,false);
	}
      }
      // look for a proper end of the variable
      if ($para && $text{$i}==")" && $text{$i+1}=="]") {
	$i+=2;
      } elseif (!$para && $text{$i}="]") {
	$i++;
      } else continue;// not a variable
      
      // build the variable code
      $varcode=$this->_make_variable_code($varchar,$varname,$pipefunction,$escape);
      $text=substr_replace($text,$varcode,$startvar,$i-$startvar);
      $i=$startvar+strlen($varcode); // move the counter
    } // we found a variable
    $i=strpos($text,"[",$i);
  } // while there are some variable
}




 function _make_variable_code ($prefix,$name,$pipefunction,$escape) {

   $variable=$this->parse_variable_extra($prefix,$name);
   if ($variable===false) { // has the variable being processed ?     
     $variable="\$context['". str_replace(".","']['",strtolower($name)) ."']";
   }


# parse the filter
   if ($pipefunction) { // traitement particulier ?
     foreach(explode("|",$pipefunction) as $fct) { 
       // note that explode is a little bit radical. It should be more advance parser
       if ($fct=="false" || $fct=="true" || $fct=="else") $fct.="function";
       if ($fct=="elsefunction") $fct="falsefunction";
       if ($fct) {
	 // get the args if any 
	 if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)\((.*?)\)$/",$fct,$result)) {
	   $args=",".$result[2]; $fct=$result[1]; 
	 } elseif (preg_match("/^([A-Za-z][A-Za-z_0-9]*)$/",$fct)) { 
	   $args=""; 
	 } else {
	   // error
	   $this->errmsg("The name of the pipe function \"$fct\" is invalid");
	 }
       } else continue;
       $variable=$fct."(".$variable.$args.")";
     }
   }

   switch($escape) {     
   case 'php' :
     // traitement normal, php espace
     $testcode=' echo '.$variable.';';
     $code='<'.'?php '.$testcode.' ?'.'>';

     break;
   case 'quote' :
     $code='".'.$variable.'."';
     $testcode=' echo "'.$code.'";';
     break;
   default:
     $code=$variable;
   }

   // unable to test the code.... 
   // must use the PEAR::PHP_Parser

   return $code;
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



function parse_main()

{
  while ($this->ind<$this->countarr) {
    switch($this->arr[$this->ind]) {
    case "CONTENT" :     
      $attrs=$this->_decode_attributs($arr[$this->ind+1]);
      $this->charset=$attrs['CHARSET'] ? $attrs['CHARSET'] : "iso-8859-1";
      // attribut refresh
      $this->_checkforrefreshattribut($attrs);
      $this->_clearposition();
      break;
    case "USE" :
      $attrs=$this->_decode_attributs($this->arr[$this->ind+1]);
      if ($attrs['MACROFILE']) {

	$macrofilename=$attrs['MACROFILE'];
	if (file_exists("tpl/".$macrofilename)) {
	  $contents=file_get_contents("tpl/".$macrofilename);
	} elseif ($GLOBALS['sharedir'] && file_exists($GLOBALS['sharedir']."/macros/".$macrofilename)) {
	  $contents=file_get_contents($GLOBALS['sharedir']."/macros/".$macrofilename);
	} elseif (file_exists($GLOBALS['home']."../tpl/".$macrofilename)) {
	  $contents=file_get_contents($GLOBALS['home']."../tpl/".$macrofilename);
	} else {
	  $this->errmsg ("the macro file \"$macrofilename\" doesn't exist");
	}
	$this->macros_txt.=stripcommentandcr($contents);
	$this->_clearposition();
      } elseif ($attrs['TEMPLATEFILE']) {
	$this->_clearposition();
	$this->arr[$this->ind]='<?php insert_template($context,"'.basename($attrs['TEMPLATEFILE']).'"); ?>';
      }
      break;
      // returns
    case "ELSE" :
    case "DO" :
    case "DOFIRST" :
    case "DOLAST" :
    case "AFTER" :
    case "BEFORE" :
    case "ALTERNATIVE":
    case "CASE":
      return;
    case "/MACRO" :
    case "/FUNC" :
      $this->_clearposition();
      break;
    default:
      if ($this->arr[$this->ind]{0}=="/") {
	// closing tag ?
	if ($this->arr[$this->ind+1]) $this->errmsg("The closing tag ".$this->arr[$this->ind]." is malformed");
	return;
      } else {
	$methodname="parse_".$this->arr[$this->ind];
	if (method_exists($this,$methodname)) {
	  $this->$methodname();
	  #call_user_func(array(&$this,$methodname));
	} else {
	  $this->errmsg("Unexpected tags ".$this->arr[$this->ind].". No method to call");
	}
      }
      break;
    }
    $this->ind+=3;
  }
}


function parse_LOOP()

{
  static $tablefields;
  $attrs=$this->arr[$this->ind+1];

  $this->arr[$this->ind]="";
  $this->arr[$this->ind+1]="";

  $name="";
  $orders=array();
  $selectparts=array();

  $dontselect=array();
  $wheres=array();
  $tables=array();
  $arguments=array();

  $attrs_arr=$this->_decode_attributs($attrs,"flat");

  // search the loop name and determin whether the loop is the definition of a SQL loop.
  $issqldef=false;
  foreach ($attrs_arr as $attr) {
    if ($attr['name']=="NAME") {
      if ($name) $this->errmsg("name already defined in loop $name",$this->ind);
      $name=trim($attr['value']);
    } elseif ($attr['name']=="TABLE") {
      $issqldef=true;
    } elseif ($attr['name']=="REFRESH") {
	$this->_checkforrefreshattribut($attrs);
    }
  }

  if ($issqldef) { // definition of a SQL loop.
    foreach ($attrs_arr as $attr) {
      $value=$attr['value'];
      $this->parse_variable($value,"quote"); // parse the attributs
      switch ($attr['name']) {
      case "NAME":
	break;
      case "DATABASE":
	$database=trim($value).".";
	break;
      case "WHERE" :
	$wheres[]="(".replace_conditions($value,"sql").")";
	break;
      case "TABLE" :
	if (is_array($value)) { // multiple table attributs ?
	  $arr=array();
	  foreach ($value as $val) $arr=array_merge($arr,explode(",",$value));
	} else { // multiple table separated by comma
	  $arr=explode(",",$value);
	}
	if ($arr) {
	  foreach ($arr as $value) {
	    array_push($tables,$database.trim($value));
	  }
	}
	break;
      case "ORDER" :
	$orders[]=$value;
	break;
      case "LIMIT" :
	if ($selectparts['limit']) $this->errmsg("Attribut LIMIT should occur only once in loop $name",$this->ind);
	$selectparts['limit']=$value;
	break;
      case "GROUPBY" :
	if ($selectparts['groupby']) $this->errmsg("Attribut GROUPY should occur only once in loop $name",$this->ind);
	$selectparts['groupby']=$value;
	break;
      case "HAVING" :
	if ($selectparts['having']) $this->errmsg("Attribut HAVING should occur only once in loop $name",$this->ind);
	$selectparts['having']=$value;
	break;
      case "SELECT" :
	if ($dontselect) $this->errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name",$this->ind);
	#$select=array_merge($select,preg_split("/\s*,\s*/",$value));
	if ($selectparts['select']) $selectparts['select'].=",";
	$selectparts['select'].=$value;
	break;
      case "DONTSELECT" :
	if ($selectparts['select']) $this->errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name",$this->ind);
	$dontselect=array_merge($dontselect,preg_split("/\s*,\s*/",$value));
	break;
      case "REQUIRE":
	break;
      case "SHOWSQL":
	$options['showsql']=true;
	break;
      default:
	$this->errmsg ("unknown attribut \"".$attr['name']."\" in the loop $name",$this->ind);
      }
    } // loop on the attributs
    // end of definition of a SQL loop
  } else {
    // ok, this is a SQL loop call or a user lopp
    // the attributs are put into $arguments.
    foreach ($attrs_arr as $attr) {
      if ($attr['name']=="NAME") continue;
      $this->parse_variable($attr['value'],"quote"); // parse the attributs
      $arguments[strtolower($attr['name'])]=$attr['value'];
    }
  }


#  echo "enter loop $name:",$this->ind,"<br>\n";


  if (!$name) {
    $this->errmsg("the name of the loop on table(s) \"".join(" ",$tables)."\" is not defined",$this->ind);
  }

  $selectparts['where']=join(" AND ",$wheres);
  $selectparts['order']=join(",",$orders);
  //
  $tablesinselect=$tables; // ce sont les tables qui seront demandees dans le select. Les autres tables de $tables ne seront pas demandees
  $extrainselect=""; // texte pour gerer des champs supplementaires dans le select. Doit commencer par ,

  if (!$selectparts['where']) $selectparts['where']="1";
  $this->parse_loop_extra($tables,
			  $tablesinselect,$extrainselect,
			  $selectparts);
  //
  foreach($selectparts as $k=>$v) { $selectparts[$k]=$this->prefixTablesInSQL($v); }
  $extrainselect=$this->prefixTablesInSQL($extrainselect);

  if (!$this->loops[$name]['type']) $this->loops[$name]['type']="def"; // toggle the loop as defined, if it is not already
  $issql=$this->loops[$name]['type']=="sql"; // boolean for the SQL loops


  if ($tables) { // loop SQL
    // check if the loop is not already defined with a different contents.
    if ($issql && $attrs!=$this->loops[$name]['attr']) $this->errmsg ("loop $name cannot be defined more than once",$this->ind);

    // get the contents
    $looplevel=1;
    $iclose=$this->ind;
    do {
      $iclose+=3;
      if ($this->arr[$iclose]=="/LOOP") $looplevel--;
      if ($this->arr[$iclose]=="LOOP") $looplevel++;
    } while ($iclose<$this->countarr && $looplevel);
    $md5contents=md5(join(array_slice($this->arr,$this->ind,$iclose-$this->ind)));
    // ok, we have the content, now we can decide what to do.

    // the loop is not defined yet, let's define.
    if (!$issql) { // the loop has to be defined
      $this->loops[$name]['ind']=$this->ind; // save the index position
      $this->loops[$name]['attr']=$attrs; // save an id
      $this->loops[$name]['type']="sql"; // marque la loop comme etant une loop sql
      $this->loops[$name]['md5contents']=$md5contents; // set the contents md5

      $this->decode_loop_content($name,$contents,$options,$tablesinselect);
      $this->make_loop_code($name.'_'.($this->signature),$tables,
			    $tablesinselect,$extrainselect,$dontselect,
			    $selectparts,
			    $contents,$options);
    } elseif ($this->loops[$name]['md5contents']==$md5contents) { // boucle redefinie identiquement
      // on passe le contenu... on le connait deja
      do {
	$this->arr[$this->ind]="";
	$this->arr[$this->ind+1]="";
	$this->arr[$this->ind+2]="";
	$this->ind+=3;
      } while ($this->ind<$iclose);
    } else {
      $this->errmsg ("loop $name cannot be defined more than once with different contents",$this->ind);
    }
    $code='<?php loop_'.$name.'_'.($this->signature).'($context); ?>';
  } else {
    //
    if (!$issql) {// the loop is not defined yet, thus it is a user loop
      //
      $this->loops[$name]['id']++; // increment the name count
      $newname=$name."_".$this->loops[$name]['id']; // change the name in order to be unique
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
      $this->loops[$name]['recursive']=true;
#      // copy the wanted variables from the original definition
#      print_r($this->wantedvars);
#      exit();
#      // we should remove from the wanted level 1, the provided variables
    }
  }
  if ($this->arr[$this->ind]!="/LOOP") {
    echo ":::: $this->ind ".$this->arr[$this->ind]."<br>\n";
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
			 $tablesinselect,$extrainselect,$dontselect,
			 $selectparts,
			 $contents,$options)

{
  static $tablefields; // charge qu'une seule fois

  if ($selectparts['where']) $selectparts['where']="WHERE ".$selectparts['where'];
  if ($selectparts['order']) $selectparts['order']="ORDER BY ".$selectparts['order'];
  if ($selectparts['having']) $selectparts['having']="HAVING ".$selectparts['having'];
  if ($selectparts['groupby']) $selectparts['groupby']="GROUP BY ".$selectparts['groupby']; // besoin de group by ?

  // special treatment for limit when only one value is given.
  $limit=$selectparts['limit'];

if ($limit && strpos($limit,",")===false) {
  
   $offsetname="offset_".substr(md5($name),0,5);
   
   $preprocesslimit='
    $currentoffset=intval(($_REQUEST[\''.$offsetname.'\'])/'.$limit.')*'.$limit.';';
   $processlimit='
   $currenturl=basename($_SERVER[\'SCRIPT_NAME\'])."?";
   $cleanquery=preg_replace("/(^|&)'.$offsetname.'=\d+/","",$_SERVER[\'QUERY_STRING\']);
   if ($cleanquery[0]=="&") $cleanquery=substr($cleanquery,1); 
   if ($cleanquery) $currenturl.=$cleanquery."&";
if ($context[nbresults]>'.$limit.') {
$context[nexturl]=$currenturl."'.$offsetname.'=".($currentoffset+'.$limit.');
//$context[nbresultats]--;$context[nbresults]--;
} else {
$context[nexturl]="";
}'.
'$context[offsetname] ='.$offsetname.';'.
'$context[limitinfo] = '.$limit.';'.
'$context[previousurl]=$currentoffset>='.$limit.' ? $currenturl."'.$offsetname.'=".($currentoffset-'.$limit.') : "";
';
   $limit='".$currentoffset.",'.($limit);
 } 
  if ($limit) $limit="LIMIT ".$limit;

  // traitement particulier additionnel

  # c est plus complique que ca ici, car parfois la table est prefixee par la DB.

  // reverse the order in order the first is select in the last.
  $tablesinselect=array_reverse(array_unique($tablesinselect));
  $table=join (', ',array_reverse(array_unique($tables)));

  $select=$selectparts['select'];
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
  if (!$select) $select="1";
  $select.=$extrainselect;

  foreach(array("sqlfetchassoc","sqlquery","sqlerror","sqlfree","sqlnumrows") as $piece) {
    if (!isset($options[$piece])) $options[$piece]=$this->codepieces[$piece];
  }

#### $t=microtime();  echo "<br>requete (".((microtime()-$t)*1000)."ms): $query <br>";

//
// genere le code pour parcourir la loop
//

	

  $this->fct_txt.='function loop_'.$name.' ($context)
{'.$preprocesslimit.'
 $query="SELECT count(*) as nbresults FROM '.$table.' '.$selectparts['where'].' '.$selectparts['groupby'].' '.$selectparts['having'].'";' .
 '$result ='.sprintf($options['sqlquery'],'$query').sprintf($options['sqlerror'],'$query','$name').';'.
 $postmysqlquery.
 '$row='.sprintf($options['sqlfetchassoc'],'$result').';'.
 '$context[nbresultats]=$context[nbresults] = $row[nbresults] ;'.
 
 	'$query="SELECT '.$select.' FROM '.$table." ".$selectparts['where']." ".$selectparts['groupby']." ".$selectparts['having']." ".$selectparts['order']." ".$limit.'"; '.($options['showsql'] ? 'echo htmlentities($query);' : '').'
  $query ; $result='.sprintf($options['sqlquery'],'$query').sprintf($options['sqlerror'],'$query','$name').';
'.$postmysqlquery.'
 //$context[nbresultats]=$context[nbresults]='.sprintf($options['sqlnumrows'],'$result').';
 '.$processlimit.' 
 $generalcontext=$context;
 $count=0;
 if ($row='.sprintf($options['sqlfetchassoc'],'$result').') {
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
    $this->fct_txt.=' if ($count==$context[nbresults]) { '.$contents['PRE_DOLAST'].'?>'.$contents['DOLAST'].'<?php continue; }';
  }
    $this->fct_txt.=$contents['PRE_DO'].' ?>'.$contents['DO'].'<?php    } while ($count<$generalcontext[nbresults] && $row='.sprintf($options['sqlfetchassoc'],'$result').');
?>'.$contents['AFTER'].'<?php  } ';

  if ($contents['ALTERNATIVE']) $this->fct_txt.=' else {?>'.$contents['ALTERNATIVE'].'<?php }';

    $this->fct_txt.='
 '.sprintf($options['sqlfree'],'$result').';
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

function parse_FUNC() { $this->parse_MACRO("FUNC"); }

function parse_MACRO($tag="MACRO")

{
  // decode attributs
  $attrs=$this->_decode_attributs($this->arr[$this->ind+1]);

  $name=trim($attrs['NAME']);
  if (!$name) { $this->errmsg ("$tag without NAME attribut"); }

  if (!isset($this->macrocode[$name])) {
    // search for the macro define
    $searchstr='/<DEF'.$tag.'\s+NAME\s*=\s*"'.$attrs['NAME'].'"([^>]*)>(.*?)<\/DEF'.$tag.'>/s';

    #if (!preg_match_all($searchstr,$text,$defs,PREG_SET_ORDER)) 
      if (!preg_match_all($searchstr,$this->macros_txt,$defs,PREG_SET_ORDER)) { $this->errmsg ("the macro $name is not defined"); }
    $def=array_pop($defs); // get the last definition of the macro
    $code=preg_replace("/(^\n|\n$)/","",$def[2]); // remove first and last line break

    $this->macrocode[$name]['code']=$code;
    $this->macrocode[$name]['attr']=$def[1];
  } // caching
  
  if ($tag=="FUNC") { // we have a function macro
    $defattr=$this->_decode_attributs($this->macrocode[$name]['attr']);
    if ($defattr['REQUIRED']) {
      $required=preg_split("/\s*,\s*/",strtoupper($defattr['REQUIRED']));
      //$optional=preg_split("/\s*,\s*/",strtoupper($defattr['OPTIONAL']));

      // check the validity of the call
      foreach ($required as $arg) {
	if (!isset($attrs[$arg]))  { $this->errmsg ("the macro $name required the attribut $arg"); }
      }
    }
    $macrofunc=strtolower("macrofunc_".$name."_".$this->signature);
    
    $this->_clearposition();
    // build the call
    unset($attrs['NAME']);
    $args=array();
    foreach ($attrs as $attr => $val) {
      $this->parse_variable($val,"quote");
      $args[]='"'.strtolower($attr).'"=>"'.$val.'"';
    }
    $this->arr[$this->ind].='<?php '.$macrofunc.'($context,array('.join(",",$args).')); ?>';
    //

    if (!($this->funcs[$macrofunc])) {
      $this->funcs[$macrofunc]=true;
      // build the function 
      $code='<?php function '.$macrofunc.'($context,$args) {
         $context=array_merge($context,$args); ?>
'.$this->macrocode[$name]['code'].'
<?php  } ?>';
      $this->_split_file($code,"add");
    }
  } else { // normal MACRO
    $this->_split_file($this->macrocode[$name]['code'],"insert");
    $this->_clearposition();
  }
}


# traite les conditions avec IF
function parse_IF () 

{
  $attrs=$this->_decode_attributs($this->arr[$this->ind+1]);
  if (!$attrs['COND']) $this->errmsg("Expecting a COND attribut in the IF tag");
  $cond=$attrs['COND'];
  $this->parse_variable($cond,false); // parse the attributs
  $cond=replace_conditions($cond,"php");

  $this->_clearposition();
  $this->arr[$this->ind+1]='<?php if ('.$cond.') { ?>';

  do {
    $this->ind+=3;
    #$this->parse_main2();
    $this->parse_main();
    if ($this->arr[$this->ind]=="ELSE") {
      if ($elsefound) $this->errmsg ("ELSE found twice in IF condition",$this->ind);
      $elsefound=1;
      $this->_clearposition();
      $this->arr[$this->ind+1]='<?php } else { ?>';
    } elseif ($this->arr[$this->ind]=="/IF") {
      $isendif=1;
    } else $this->errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in IF condition",$this->ind);
  } while (!$isendif && $this->ind<$this->countarr);

  if (!$isendif) $this->errmsg("IF not closed",$this->ind);

  $this->_clearposition();
  $this->arr[$this->ind+1]='<?php } ?>';  
}


function parse_SWITCH ()

{
  // decode attributs
  $attrs=$this->_decode_attributs($this->arr[$this->ind+1]);
  if (!$attrs['TEST']) $this->errmsg("Expecting a TEST attribut in the SWITCH tag");
  $test=$attrs['TEST'];
  $this->parse_variable($test,false); // parse the attributs
  $test=replace_conditions($test,"php");

  $this->_clearposition();
  $this->arr[$this->ind+1]='<?php sitwch ('.$test.') { ';
  if (trim($this->arr[$this->ind+2])) $this->errmsg("Expecting a CASE tag after the SWITCH tag");

  do {
    $this->ind+=3;

    $this->parse_main();

    if ($this->arr[$this->ind]=="DO") {
      $attrs=$this->_decode_attributs($this->arr[$this->ind+1]);
      if ($attrs['CASE']) {
	$this->parse_variable($attrs['CASE'],false); // parse the attributs

	$this->_clearposition();
	$this->arr[$this->ind+1]='case '.$attrs['CASE'].': ?>';
      } else {
	die("ERROR: multiple choice case not implemented yet");
	// multiple case
      }
      $this->_clearposition();
      $this->arr[$this->ind+1]='case :';
    } elseif ($this->arr[$this->ind]=="/DO") {
      $this->_clearposition();
      $this->arr[$this->ind+1]="break;\n";
    } elseif ($this->arr[$this->ind]=="/SWITCH") {
      $endswitch=true;
    } else $this->errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in SWITCH condition",$this->ind);
  } while (!$endswitch && $this->ind<$this->countarr);

  if (!$endswitch) $this->errmsg("SWITCH block is not closed",$this->ind);

  $this->_clearposition();
  $this->arr[$this->ind+1]='<?php } ?>';
}



function parse_LET () {

  if (!preg_match("/\bVAR\s*=\s*\"([^\"]*)\"/",$this->arr[$this->ind+1],$result)) $this->errmsg ("LET have no VAR attribut");
  if (!preg_match("/^$this->variable_regexp$/i",$result[1])) $this->errmsg ("Variable \"$result[1]\"in LET is not a valide variable",$this->ind);
  $this->parse_variable($result[1],false); // parse the attributs
  $var=strtolower($result[1]);

  $this->_clearposition();
  $this->arr[$this->ind+1]='<?php ob_start(); ?>';

  $this->ind+=3;
  #$this->parse_main2();
  $this->parse_main();
  if ($this->arr[$this->ind]!="/LET") $this->errmsg("&lt;/LET&gt; expected, ".$this->arr[$this->ind]." found",$this->ind);

  $this->_clearposition();
  $this->arr[$this->ind+1]='<?php $context[\''.$var.'\']=ob_get_contents();  ob_end_clean(); ?>';
}


function parse_ESCAPE()

{
  $escapeind=$this->ind;
  $this->_clearposition();
  $this->isphp=TRUE;
  $this->ind+=3;

  $this->parse_main();
  if ($this->arr[$this->ind]!="/ESCAPE") $this->errmsg("&lt;/ESCAPE&gt; expected, ".$this->arr[$this->ind]." found",$this->ind);

  for($i=$escapeind; $i< $this->ind; $i+=3) {
    if (trim($this->arr[$i+2]))
      $this->arr[$i+2]='<? if ($GLOBALS[\'cachedfile\']) { echo \''.quote_code($this->arr[$i+2]).'\'; } else {?>'.$this->arr[$i+2].'<?php } ?>';    
  }
  $this->_clearposition();
}

/**
 * Accept an array or a string
 *
 */

function _checkforrefreshattribut($mixed)

{
  if (is_array($mixed)) {
    $attrs=$mixed;
  } else {
    $attrs=$this->_decode_attributs($mided);
  }

  if (!$attrs['REFRESH']) return;

  $refresh=trim($attrs['REFRESH']);
  $timere="(?:\d+(:\d\d){0,2})"; // time regexp
  if (!is_numeric($refresh) && !preg_match("/^$timere(?:,$timere)*$/",$refresh)) $this->errmsg("Invalid refresh time \"".$refresh."\"");

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


function prefixTablesInSQL ($sql)

{
  if (!method_exists($this,"prefixTableName")) return $sql;
  ##echo $sql,"<br>";
  $n=strlen($sql);

  $inquote=false;

  for ($i=0; $i < $n; $i++ ) {
    $c=$sql{$i};

    if ($inquote) { // we are in a string
      if ($c==$quotec && !$escaped) {
	$inquote = false;
      } else {
	$escaped = $c=="\\" && !$escaped;
      }
    } elseif ($c=='"' || $c=="'") { // quote ?
      $inquote=true;
      $escaped=false;
      $quotec=$c;
    } elseif ($c=="." && preg_match("/\b((?:\w+\.)?\w+)$/",$str,$result)) { // table dot ?
      $prefixedtable=$this->prefixTableName($result[1]);
      if ($prefixedtable!=$result[1]) {
	// we have a table... let's prefix it
	$ntablename=strlen($result[1]);
	$str=substr($str,0,-$ntablename).$prefixedtable;
      }
    }
    $str.=$c;
  }
  #echo "to:",$str,"<br>";
  return $str;
}


 function _decode_attributs($text,$options="")

 {
   // decode attributs
   $arr=explode('"',$text);
   $n=count($arr);
   for($i=0; $i<$n; $i+=2) {
     $attr=trim(substr($arr[$i],0,strpos($arr[$i],"=")));
     if (!$attr) continue;
     if ($options=="flat") {
       $ret[]=array("name"=>$attr,"value"=>$arr[$i+1]);
     } else {
       $ret[$attr]=$arr[$i+1];
     }
   }
   return $ret;
 }


 function _clearposition()

 {
   $this->arr[$this->ind]=$this->arr[$this->ind+1]="";
   $this->arr[$this->ind+2]=preg_replace("/^(\s*\n)/","",$this->arr[$this->ind+2]);
 }


 function _split_file($contents,$action="insert") {
   $arr=preg_split("/<(\/?(?:".join("|",$this->commands)."))\b([^>]*?)\/?>/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);

   // parse the variables
   $this->parse_variable($arr[0]);
   for($i=1; $i<count($arr); $i+=3) {
     $this->parse_variable($arr[$i+2]); // parse the content
   }

   if (!$this->arr) {
     $this->ind=0;
     $this->currentline=0;
     $this->arr=$arr;
   } elseif ($action=="insert") {
     $this->arr[$this->ind+2]=$arr[count($arr)-1].$this->arr[$this->ind+2];
     array_splice($this->arr,$this->ind+2,0,array_slice($arr,0,-1));
   } elseif ($action=="add") {
     $this->arr[count($this->arr)-1].=$arr[0];
     $this->arr=array_merge($this->arr,array_slice($arr,1));
   }
   $this->countarr=count($this->arr);
   if (!$this->ind) $this->ind=1;
 }
} // clase Parser

function replace_conditions($text,$style)

{
  return preg_replace(
	       array("/\bgt\b/i","/\blt\b/i","/\bge\b/i","/\ble\b/i","/\beq\b/i","/\bne\b/i","/\band\b/i","/\bor\b/i"),
	       array(">","<",">=","<=",($style=="sql" ? "=" : "=="),"!=","&&","||"),$text);
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



if (!function_exists("file_get_contents")) {
  function file_get_contents($file) 
  {
    $fp=fopen($file,"r") or die("Impossible to read the file $file");
    while(!feof($fp)) $res.=fread($fp,2048);
    fclose($fp);
    return $res;
  }
}


?>
