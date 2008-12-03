<?php
/**
 * Fichier Parser
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */


function parse($in, $out)
{
	$parser = new LodelParser;
	$parser->parse($in, $out);
}

class Parser
{
	
	var $infilename; // nom du template
	var $signature;
	var $variable_regexp = "[A-Z][A-Z_0-9]*(?:\.[A-Z][A-Z_0-9]*)*";
	var $variablechar; // list of prefix for the variables

	var $loops = array ();
	var $funcs = array ();
	var $macrocode = array ();

	var $charset;

	var $commands = array ();
	var $codepieces = array (); // code piece definition
	var $macros_txt;
	var $fct_txt;

	#  var $wantedvars;
	var $looplevel = 0;

	var $arr;
	var $countarr;
	var $linearr;
	var $currentline;
	var $ind;
	var $refresh = "";
	var $denied = array();
	var $isphp = false; // the parser produce a code which produce either html, either php. In the latter, a sequence must be written at the beginning to inform the cache system.

	var $id = "";
	var $tpl; // actual name of tpl
	var $blocks; // possibles blocks in template

	function errmsg($msg, $ind = 0)
	{
		// À REVOIR : déterminer le numéro de ligne dans le template
		//if ($ind)
		//$line = "line ".$this->linearr[$ind];
		//die("LODELSCRIPT ERROR line $line (".$this->infilename."): $msg");
		if (!headers_sent()) {
			header("HTTP/1.0 403 Internal Error");
			header("Status: 403 Internal Error");
			header("Connection: Close");
		}
		die("LODELSCRIPT ERROR in file ".$this->infilename." : $msg");
	}

	function parse_loop_extra(& $tables, & $tablesinselect, & $extrainselect, & $selectparts)
	{
	}

	function parse_variable_extra($prefix, $name)
	{
		return FALSE;
	}
	
	function parse_before($contents)
	{
	}
	
	function parse_after($contents)
	{
	}
	
	function decode_loop_content_extra($balise, & $content, & $options, $tables)
	{
	}

	function Parser()
	{ // constructor
		$this->commands = array ("USE", "MACRO", "FUNC", "LOOP", "IF", "LET", "ELSE", "ELSEIF", "DO", "DOFIRST", "DOLAST", "BEFORE", "AFTER", "ALTERNATIVE", "ESCAPE", "CONTENT", "SWITCH", "CASE", "BLOCK");

		$this->codepieces = array ('sqlfetchassoc' => "mysql_fetch_assoc(%s)", 'sqlquery' => "mysql_query(%s)", 'sqlerror' => "or mymysql_error(%s,%s, __LINE__, __FILE__)", 'sqlfree' => "mysql_free_result(%s)", 'sqlnumrows' => "mysql_num_rows(%s)");
		$this->denied = array('options'=>'','lodeluser'=>'','version'=>'','shareurl'=>'','extensionscripts'=>'','currenturl'=>'','siteroot'=>'','site'=>'','charset'=>'','langcache'=>'','clearcacheurl'=>'');
		$this->blocks = array();
	}

	function parse($in, $include, $blockId=0, $cache_rep='')
	{
		global $sharedir;
		if (!file_exists($in))
			$this->errmsg("Unable to read file $in");
		$this->infilename = $in;
		preg_match("/^(.*?\/)?([^\/]+)\.html$/", $in, $m);
		$this->tpl = $m[2];
		$this->base_rep = $m[1];
		$this->cache_rep = $cache_rep;
		unset($m);
		$blockId = (int)$blockId;
		$this->blockid = $blockId;
		$this->signature = preg_replace("/\W+/", "_", $in);
		$this->fct_txt = "";

		// read the file
		$contents = stripcommentandcr(file_get_contents($in));

		$this->_split_file($contents, '', $blockId); // split the contents into commands
		$this->parse_main(); // parse the commands
		
		if ($this->ind != $this->countarr)
			$this->errmsg("this file contains more closing tags than opening tags");

		$contents = join("", $this->arr); // recompose the file

		unset ($this->arr,$this->macros_txt); // save memory now.
		$this->parse_after($contents); // user defined parse function

		// remove  <DEFMACRO>.*?</DEFMACRO>
		$contents = preg_replace("/<DEF(MACRO|FUNC)\b[^>]*>.*?<\/DEF(MACRO|FUNC)>\s*\n*/s", "", $contents);

		if ($this->fct_txt)	{
			$contents = 
<<<PHP
<?php 
{$this->fct_txt}
 ?> 
{$contents}
PHP;
		}
		unset($this->fct_txt);
		//
		// refresh manager
		//

		if ($this->refresh)	{

			if(!$include) {
				$code = 
<<<PHP
<?php 
if (\$GLOBALS['cachedfile'] && (\$cachetime=myfilemtime(\$GLOBALS['cachedfile']))) { 
PHP;
				// refresh period in second
				if (is_numeric($this->refresh)) {
					$code .= 
<<<PHP
	if((\$cachetime + {$this->refresh}) < time()) return "refresh";
PHP;
					
				} else { // refresh time
					$code .= 
<<<PHP
	\$now = time(); \$date = getdate(\$now);
PHP;
					$refreshtimes = explode(",", $this->refresh);
					foreach ($refreshtimes as $refreshtime) {
						$refreshtime = explode(":", $refreshtime);
						$refreshtime = array_map("intval", $refreshtime);
						if(!isset($refreshtime[2])) $refreshtime[2] = 0;
						$code .= 
<<<PHP
	\$refreshtime=mktime({$refreshtime[0]},{$refreshtime[1]},{$refreshtime[2]},\$date['mon'],\$date['mday'],\$date['year']);
	if (\$cachetime<\$refreshtime && \$refreshtime<\$now) return "refresh";
PHP;
					}
				}
				$code .= 
<<<PHP
} 
?>
PHP;
				$code = quote_code($code);
				$contents = 
<<<PHP
<?php echo '{$code}'; ?>
{$contents}
PHP;
			} else {
				// the view manage the template refresh
				$code = 
<<<PHP
#LODELREFRESH {$this->refresh}#
{$contents}
PHP;
				$code = quote_code($code);
	                        $contents =
<<<PHP
<?php echo '{$code}'; ?>
PHP;
			}
			unset($code, $tmpcode, $refreshtimes);
		} elseif ($this->isphp)	{
			// this is use to check if the output is a must be evaluated as a php or a raw file.
			$contents = 
<<<PHP
<?php if(\$GLOBALS['cachedfile']) echo '<?php #--# ?>'; ?>
{$contents}
PHP;
		}

		// clean the open/close php tags
		$contents = preg_replace(array ('/\?>[\r\t\n]*<\?(php\b)?/', '/<\?(php\b)?[\t\r\n]*\?>/'), array ("", ""), $contents);

		if (!$this->charset || $this->charset != 'utf-8') {
			#$t=microtime();
			if(!function_exists('convertHTMLtoUTF8'))
				require 'utf8.php'; // conversion des caracteres
			$contents = utf8_encode($contents);
			convertHTMLtoUTF8($contents);
		}
		return $contents;
	}

	function parse_variable(& $text, $escape = 'php')
	{
		global $context;
		$i = strpos($text, '[');
		while ($i !== false) {
			$startvar = $i;
			$i ++;
			// parenthesis syntaxe [(
			if ($text {$i}	== '(') {
				$para = true;
				$i ++;
			}	else {
				$para = false;
			}

			if ($text {$i} == '#' || $text {$i} == '%' || strpos($text {$i }, $this->variablechar) !== false) { // 
				$varchar = $text {$i};
				$i ++;
				// look for the name of the variable now
				if ($text {$i}	< 'A' || $text {$i}	> 'Z')
					continue; // not a variable
				$varname = $text {$i};
				$i ++;
				while (($text {$i}	>= 'A' && $text {$i}	<= 'Z') || ($text {$i}	>= '0' && 
								$text {$i}	<= '9') || $text {$i}	== '_' || $text {$i}	== '.')	{
					$varname .= $text {$i};
					$i ++;
				}

				if($text{$i} == '#' || $text{$i} == '%') { // syntaxe [#VAR.#VAR] pour les tableaux !
					do {
						$tmpvarname = '';
						$isvar = false;
						if($text{$i} == '#' || $text{$i} == '%') {
							$tmpvarname = '['.$text{$i};
							$isvar = true;
						}
						$i++;
						while (($text {$i}	>= 'A' && $text {$i}	<= 'Z') || ($text {$i}	>= '0' && 
									$text {$i}	<= '9') || $text {$i}	== '_')	{
							$tmpvarname .= $text {$i};
							$i ++;
						}
						if($isvar) {
							$tmpvarname .= ']';
							$this->parse_variable($tmpvarname, false);
						}
						$varname .= $tmpvarname;
						if($text{$i} == '.') $varname .= $text{$i};
					} while($text{$i} != ']' && $text{$i} != '|' && $text{$i} != ':');
				}

				$pipefunction = '';

				if ($text {$i}	== ':')	{ // a lang
					$lang = '';
					$i ++;
					if ($text{$i} == '#') { // pour syntaxe LS [#RESUME:#SITELANG] et [#RESUME:#DEFAULTLANG.#KEY] d'une boucle foreach
						$i++;
						$is_var = true; // on a une variable derriere les ':'
						$is_array = false;
						while (($text{$i} >= 'A' && $text{$i} < 'Z') || $text {$i} == '.' || $text {$i} == '#') {
							if ($text {$i} == '.') { $is_array = true; }
							$lang .= $text {$i};
							$i ++;
						}
					} else { //pour syntaxe LS [#RESUME:FR]
						$is_var = false;
						while (($text{$i} >='A' && $text{$i} < 'Z')) {
							$lang .= $text {$i};
							$i ++;
						}
					}
					if ($is_var === true) {
						$lang = strtolower($lang);
						if ($is_array === true) {
							$tab = explode ('.', $lang);
							if(strpos($lang, '#') !== false) {
								// pour syntaxe LS [#RESUME:#DEFAULTLANG.#KEY] d'une boucle foreach
								$val = str_replace('#', '', $tab[1]);
								$lang = '$context[\''.$tab[0].'\'][\'$context['.$val.']\']';
							} else {
								//pour syntaxe LS [#RESUME:#OPTIONS.METADONNEESSITE.LANG]
								$lang = '$context[\''.$tab[0].'\'][\''.$tab[1].'\'][\''.$tab[2].'\']';
							}
						} else {
							$lang = '$context[\''.$lang.'\']';
						}
					}
					$pipefunction = '|multilingue('.$lang.')';
				}
				
				if ($text {$i}	== '|')	{ // have a pipe function
					// look for the end of the variable
					$bracket = 1;
					$mustparse = false;
					while ($bracket) {
						switch ($text {$i})	{
						case '[' :
							$bracket ++;
							$mustparse = true; // potentially a new variable
							break;
						case ']' :
							$bracket --;
							break;
						}
						if ($bracket > 0)
							$pipefunction .= $text {$i};
						$i ++;
					}
					$i --; // comes back to the bracket.
					if ($para && $pipefunction {strlen($pipefunction) - 1 }	== ')')	{
						$pipefunction = substr($pipefunction, 0, -1);
						$i --;
					}
					if ($mustparse)	{
						$this->parse_variable($pipefunction, false);
					}
				}

				// look for a proper end of the variable
				if ($para && $text {$i}	== ')' && $text {$i +1}	== ']')	{
						$i += 2;
				}	elseif (!$para && $text {$i} = ']')	{
					$i ++;
				}
				else
					continue; // not a variable
				// build the variable code
				
				$varcode = $this->_make_variable_code($varchar, $varname, $pipefunction, $escape);
				$text = substr_replace($text, $varcode, $startvar, $i - $startvar);
				$i = $startvar +strlen($varcode); // move the counter
			} // we found a variable
			$i = strpos($text, '[', $i);
		} // while there are some variable
	}

	function _make_variable_code($prefix, $name, $pipefunction, $escape)
	{
		$variable = $this->parse_variable_extra($prefix, $name);
		if ($variable === false) { // has the variable being processed ?
			$name = strtolower($name);
			if(substr_count($name, '.')) {
				$brackets = explode('.', $name);
				foreach($brackets as $bracket) {
					$code .= ($bracket{0} == '$') ? '['.$bracket.']' : "['{$bracket}']";
				}
			} else {
				$code = ($name{0} == '$') ? '['.$name.']' : "['{$name}']";
			}
			//$code = str_replace(".", "']['", $name);
			if('%' === (string)$prefix) {
				$variable = 
<<<PHP
\$GLOBALS['context']{$code}
PHP;
			} else {
				$variable = 
<<<PHP
\$context{$code}
PHP;
			}
			unset($code);
// 			$variable = ('%' === (string)$prefix) ? "\$GLOBALS['context']['".str_replace(".", "']['", strtolower($name))."']" : "\$context['".str_replace(".", "']['", strtolower($name))."']";
		}

		# parse the filter
		if ($pipefunction) { // traitement particulier ?
			//echo $pipefunction."<br />";
			
			$array = preg_split('/(?=\|[a-z]*[^\'|^\"]+)/',$pipefunction);

			foreach($array as $fct) {
			//foreach (explode("|", $pipefunction) as $fct)	{
				if($fct[0] == '|') {
					$fct = substr($fct, 1);
				}
				#foreach (preg_split ('/(?<!")(\|)(?!")/', $pipefunction) as $fct) {
				// note that explode is a little bit radical. It should be more advanced parser
				if ($fct == "false" || $fct == "true" || $fct == "else") {
					$fct .= "function";
				}
				if ($fct == "elsefunction") {
					$fct = "falsefunction";
				}
				if ($fct) {
					// get the args if any 
					if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)\((.*?)\)$/", $fct, $result))	{
						$args = ','. $result[2];
						$fct = $result[1];
					} elseif (preg_match("/^([A-Za-z][A-Za-z_0-9]*)$/", $fct)) {
						$args = '';
					} else {
						// error
						$this->errmsg("The name of the pipe function \"$fct\" is invalid");
					}
					//$variable = $fct. '('. $variable.$args. ')';
					//si la variable est contenue dans les arguments :
					$variable = "{$fct}({$variable}{$args})";
				}
			}
			unset($array);
		}
		switch ($escape) {
		case 'php' :
			// traitement normal, php espace
			$code = 
<<<PHP
<?php echo {$variable}; ?>
PHP;
			break;
		case 'quote' :
				$code = 
<<<PHP
".{$variable}."
PHP;
			break;
		default :
			$code =& $variable;
			break;
		}
		// unable to test the code.... 
		// must use the PEAR::PHP_Parser

		return $code;
	}

	function countlines($ind)
	{
		if ($ind == 0) {
			$this->currentline += substr_count($this->arr[$ind], "\n");
		}	else {
			$this->linearr[$ind] = $this->currentline;
			$this->currentline += substr_count($this->arr[$ind +1], "\n") + substr_count($this->arr[$ind +2], "\n");
		}
	}

	function parse_main()
	{	
		global $home;
		while ($this->ind < $this->countarr) {
			switch ($this->arr[$this->ind])	{
			case 'CONTENT' :
			$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
			$this->charset = isset($attrs['CHARSET']) ? $attrs['CHARSET'] : "iso-8859-1";
			// attribut refresh
			$this->_checkforrefreshattribut($attrs);
			$this->_clearposition();
			break;
			case 'USE' :
			$siteroot = defined('SITEROOT') ? SITEROOT : '';
			$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
			if ($attrs['MACROFILE']) {
				$macrofilename = $attrs['MACROFILE'];
				if (file_exists("./tpl/".$macrofilename))	{
					$path = './tpl/';
				} elseif ($GLOBALS['sharedir'] && file_exists($siteroot.$GLOBALS['sharedir']."/macros/".$macrofilename)) {
					$path = $siteroot.$GLOBALS['sharedir']."/macros/";
				} elseif (file_exists($GLOBALS['home']."../tpl/".$macrofilename)) {
					$path = $GLOBALS['home']."../tpl/";
				} else {
					$this->errmsg("the macro file \"$macrofilename\" doesn't exist");
				}
				$this->macros_txt .= stripcommentandcr(file_get_contents($path.$macrofilename));
				$this->_clearposition();
			} elseif ($attrs['TEMPLATEFILE'])	{
				$this->_clearposition();
				$attrs['TEMPLATEFILE'] = basename($attrs['TEMPLATEFILE']);
				$attrs['BLOCKID'] = (int)$attrs['BLOCKID'];

				if($attrs['BLOCKID']) {
					$path = './tpl/';
					if (!file_exists("{$path}{$attrs['TEMPLATEFILE']}.html") && file_exists($home. "../tpl/{$attrs['TEMPLATEFILE']}.html")) {
						$path = $home. '../tpl/';
					}
					preg_match("/<BLOCK\b ([^>]*ID=\"".$attrs['BLOCKID']."\"[^>]*)>(.*?)<\/BLOCK>/s", file_get_contents("{$path}{$attrs['TEMPLATEFILE']}.html"), $block);
					$this->arr[$this->ind] = $this->parse_BLOCK($block);
					unset($m);
				} else {
					$this->arr[$this->ind] = 
<<<PHP
<?php 
if(!is_null(\$this)) {
	echo \$this->renderTemplateFile(\$context, '{$attrs['TEMPLATEFILE']}', "{$this->cache_rep}");
} else {
	\$context['noreset'] = true;
	insert_template(\$context, '{$attrs['TEMPLATEFILE']}', "{$this->cache_rep}");
}
?>
PHP;
				}
			}
			break;
			// returns
			case 'ELSE' :
			case 'ELSEIF':
			case 'DO' :
			case 'DOFIRST' :
			case 'DOLAST' :
			case 'AFTER' :
			case 'BEFORE' :
			case 'ALTERNATIVE' :
			case 'CASE' :
				return;
			case '/MACRO' :
			case '/FUNC' :
				$this->_clearposition();
				break;
			default :
				if ($this->arr[$this->ind]{0}	== '/')	{
					// closing tag ?
					if ($this->arr[$this->ind + 1])
						$this->errmsg("The closing tag ".$this->arr[$this->ind]." is malformed");
					return;
				}	else {
					$methodname = 'parse_'. $this->arr[$this->ind];
					if (method_exists($this, $methodname)) {
						$this->$methodname ();
						#call_user_func(array(&$this,$methodname));
					}	else {
						$this->errmsg('Unexpected tags "'. htmlentities($this->arr[$this->ind]) . '" on ind ' . $this->ind. ". No method to call");
					}
				}
				break;
			}
			$this->ind += 3;
		}
	}

	function parse_LOOP()
	{
		static $tablefields;
		$attrs = $this->arr[$this->ind + 1];

		$this->arr[$this->ind]     = '';
		$this->arr[$this->ind + 1] = '';

		$name        = '';
		$orders      = array ();
		$selectparts = array ();
		$dontselect  = array ();
		$wheres      = array ();
		$tables      = array ();
		$arguments   = array ();

		$attrs_arr = $this->_decode_attributs($attrs, 'flat');

		// search the loop name and determin whether the loop is the definition of a SQL loop.
		$issqldef = false;
		foreach ($attrs_arr as $attr)	{
			if ($attr['name'] == 'NAME') {
				if ($name)
					$this->errmsg("name already defined in loop $name", $this->ind);
				$name = trim($attr['value']);
			} elseif ($attr['name'] == 'TABLE') {
				$issqldef = true;
			} elseif ($attr['name'] == 'REFRESH') {
				$this->_checkforrefreshattribut($attrs);
			}
		}

		if ($issqldef) { // definition of a SQL loop.
			foreach ($attrs_arr as $attr)	{
				$value = $attr['value'];
				$this->parse_variable($value, 'quote'); // parse the attributs
				switch ($attr['name'])	{
				case 'NAME' :
					break;
				case 'DATABASE' :
					// si la database est dynamique, on rajoute le préfix pour les tables
					$database = '`'.trim($value).'`.';
					break;
				case 'WHERE' :
					$wheres[] = '('. replace_conditions($value, 'sql'). ')';
					break;
				case 'TABLE' :
					if (is_array($value))	{ // multiple table attributs ?
						$arr = array ();
						foreach ($value as $val)
							$arr = array_merge($arr, explode(',', $value));
					} else { // multiple table separated by comma
						$arr = explode(',', $value);
					}
					if ($arr) {
						$prefix = (FALSE !== strpos($database, '$context') ? $GLOBALS['tableprefix'] : '');
						foreach ($arr as $value) {
							array_push($tables, $database.$prefix.trim($value));
						}
					}
					break;
				case 'ORDER' :
					$orders[] = $value;
					break;
				case 'LIMIT' :
					if ($selectparts['split'])
						$this->errmsg("Attribut SPLIT cannot be used with LIMIT", $this->ind);
					if ($selectparts['limit'])
						$this->errmsg("Attribut LIMIT should occur only once in loop $name", $this->ind);
					$selectparts['limit'] = $value;
					break;
				case 'SPLIT' :
					if ($selectparts['limit'])
						$this->errmsg("Attribut SPLIT cannot be used with LIMIT", $this->ind);
					if ($selectparts['split'])
						$this->errmsg("Attribut SPLIT should occur only once in loop $name", $this->ind);
					$selectparts['split'] = $value;
					break;
				case 'GROUPBY' :
					if ($selectparts['groupby'])
						$this->errmsg("Attribut GROUPY should occur only once in loop $name", $this->ind);
					$selectparts['groupby'] = $value;
					break;
				case 'HAVING' :
					if ($selectparts['having'])
						$this->errmsg("Attribut HAVING should occur only once in loop $name", $this->ind);
					$selectparts['having'] = $value;
					break;
				case 'SELECT' :
					if ($dontselect)
						$this->errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name", $this->ind);
					#$select=array_merge($select,preg_split("/\s*,\s*/",$value));
					if ($selectparts['select'])
						$selectparts['select'] .= ",";
					$selectparts['select'] .= $value;
					break;
				case 'DONTSELECT' :
					if ($selectparts['select'])
						$this->errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name", $this->ind);
					$dontselect = array_merge($dontselect, preg_split("/\s*,\s*/", $value));
					break;
				case 'REQUIRE' :
					break;
				case 'SHOWSQL' :
					$options['showsql'] = true;
					break;
				default :
					$this->errmsg("unknown attribut \"".$attr['name']."\" in the loop $name", $this->ind);
				}
			} // loop on the attributs
			// end of definition of a SQL loop
		}	else {
			// ok, this is a SQL loop call or a user lopp
			// the attributs are put into $arguments.
			foreach ($attrs_arr as $attr) {
				if ($attr['name'] == 'NAME')
					continue;
				$this->parse_variable($attr['value'], 'quote'); // parse the attributs
				$arguments[strtolower($attr['name'])] = $attr['value'];
			}
		}
		unset($attrs_arr);
		#  echo "enter loop $name:",$this->ind,"<br>\n";

		if (!$name)	{
			$this->errmsg("the name of the loop on table(s) \"".join(" ", $tables)."\" is not defined", $this->ind);
		}

		$selectparts['where'] = join(" AND ", $wheres);
		$selectparts['order'] = join(',', $orders);
		//
		$tablesinselect = $tables; // ce sont les tables qui seront demandees dans le select. Les autres tables de $tables ne seront pas demandees
		$extrainselect = ""; // texte pour gerer des champs supplementaires dans le select. Doit commencer par ,

		if (!$selectparts['where'])
			$selectparts['where'] = '1';
		$this->parse_loop_extra($tables, $tablesinselect, $extrainselect, $selectparts);
		//
		foreach ($selectparts as $k => $v) {
			$selectparts[$k] = $this->prefixTablesInSQL($v);
		}
		$extrainselect = $this->prefixTablesInSQL($extrainselect);

		if (!$this->loops[$name]['type'])
			$this->loops[$name]['type'] = 'def'; // toggle the loop as defined, if it is not already
		$issql = $this->loops[$name]['type'] == 'sql'; // boolean for the SQL loops

		if ($tables) { // loop SQL
			// check if the loop is not already defined with a different contents.
			if ($issql && $attrs != $this->loops[$name]['attr']){
				$this->errmsg("loop $name cannot be defined more than once", $this->ind);}

			// get the contents
			$looplevel = 1;
			$iclose = $this->ind;
			do {
				$iclose += 3;
				if ($this->arr[$iclose] == '/LOOP')
					$looplevel --;
				if ($this->arr[$iclose] == 'LOOP')
					$looplevel ++;
			}	while ($iclose < $this->countarr && $looplevel);
			$md5contents = md5(join(array_slice($this->arr, $this->ind, $iclose - $this->ind)));
			// ok, we have the content, now we can decide what to do.

			// the loop is not defined yet, let's define.
			if (!$issql) { // the loop has to be defined
				$this->loops[$name]['ind']         = $this->ind; // save the index position
				$this->loops[$name]['attr']        = $attrs; // save an id
				$this->loops[$name]['type']        = 'sql'; // marque la loop comme etant une loop sql
				$this->loops[$name]['md5contents'] = $md5contents; // set the contents md5

				$this->decode_loop_content($name, $contents, $options, $tablesinselect);
				$this->make_loop_code($name. '_'. ($this->signature), $tables, $tablesinselect, $extrainselect, $dontselect, $selectparts, $contents, $options);
			} elseif ($this->loops[$name]['md5contents'] == $md5contents) { // boucle redefinie identiquement
				// on passe le contenu... on le connait deja
				do {
					$this->arr[$this->ind]     = '';
					$this->arr[$this->ind + 1] = '';
					$this->arr[$this->ind + 2] = '';
					$this->ind += 3;
				} while ($this->ind < $iclose);
			} else {
				$this->errmsg("loop $name cannot be defined more than once with different contents", $this->ind);
			}
			$code = 
<<<PHP
<?php loop_{$name}_{$this->signature}(\$context); ?>
PHP;
		} else {
			//
			if (!$issql) { // the loop is not defined yet, thus it is a user loop
				$this->loops[$name]['id']++; // increment the name count
				$newname = $name. '_'. $this->loops[$name]['id'].($this->blockid ? '_'.$this->blockid : ''); // change the name in order to be unique
				$this->decode_loop_content($name, $contents, $options);
				$this->make_userdefined_loop_code($newname, $contents, $arguments);
				// build the array for the arguments:
				$argumentsstr = '';
				foreach ($arguments as $k => $v) {
					$argumentsstr .= "'$k'=>\"$v\",";
				}
				// clean a little bit, the "" quote
				$argumentsstr = preg_replace(array ('/""\./', '/\.""/'), array ('', ''), $argumentsstr);
				// make the loop call
				$localtpl = str_replace(array('.','-'),array('_','_'),basename($this->infilename)). '_';
				$code = 
<<<PHP
<?php loop_{$name}(\$context,"{$localtpl}{$newname}",array({$argumentsstr})); ?>
PHP;
			} else	{ // the loop is an sql recurrent loop
				$code = 
<<<PHP
<?php loop_{$name}_{$this->signature}(\$context); ?>
PHP;
				$this->ind += 3;
				if ($this->arr[$this->ind] != '/LOOP')
					$this->errmsg("loop $name cannot be defined more than once");
				$this->loops[$name]['recursive'] = true;
			}
		}
		if ($this->arr[$this->ind] != '/LOOP') {
			echo ":::: $this->ind ".$this->arr[$this->ind]."<br />\n";
			print_r($this->arr);
			$this->errmsg("internal error in parse_loop. Report the bug");
		}
		$this->arr[$this->ind]     = '';
		$this->arr[$this->ind + 1] = $code;
	}

	function decode_loop_content($name, & $content, & $options, $tables = array ())
	{
		$balises = array ('DOFIRST' => 1, 'DOLAST' => 1, 'DO' => 1, 'AFTER' => 0, 'BEFORE' => 0, 'ALTERNATIVE' => 0);
		$loopind = $this->ind;
		do {
			$this->ind += 3;
			$this->parse_main();
			if (isset ($balises[$this->arr[$this->ind]])) { // opening
				$state = $this->arr[$this->ind];
				if ($content[$state])
					$this->errmsg("In loop $name, the block $state is defined more than once", $this->ind);
				$istart = $this->ind;
				$this->arr[$this->ind] = '';
				$this->arr[$this->ind + 1] = '';
			} elseif ($this->arr[$this->ind] == '/'. $state) { // closing
				for ($j = $istart; $j < $this->ind; $j += 3) {
					for ($k = $j; $k < $j +3; $k ++) {
						$content[$state] .= $this->arr[$k];
						$this->arr[$k] = '';
					}
				}

				$this->decode_loop_content_extra($state, $content, $options, $tables);
				$state = '';
				$this->arr[$this->ind]     = '';
				$this->arr[$this->ind + 1] = '';
			}	elseif ($state == "" && $this->arr[$this->ind] == '/LOOP')	{ // closing the loop
				$isendloop = 1;
				break;
			}	elseif ($state)	{ // error
				$this->errmsg("&lt;$state&gt; not closed in the loop $name", $this->ind);
			}	else { // another error
				$this->errmsg("unexpected &lt;".$this->arr[$this->ind]."&gt; in the loop $name", $this->ind);
			}
		}	while ($this->ind < $this->countarr);

		if (!$isendloop)
			$this->errmsg("end of loop $name not found", $this->ind);

		if ($content['DO']) {
			// check that the remaining content is empty
			for ($j = $loopind; $j < $this->ind; $j ++)
				if (trim($this->arr[$j])) {
					$this->errmsg("In the loop $name, a part of the content is outside the tag DO", $j);
				}
		} else {
			for ($j = $loopind; $j < $this->ind; $j += 3) {
				for ($k = $j; $k < $j +3; $k ++) {
					$content["DO"] .= $this->arr[$k];
					$this->arr[$k] = '';
				}
			}
			$this->decode_loop_content_extra('DO', $content, $options, $tables);
		}
	}

	function make_loop_code($name, $tables, $tablesinselect, $extrainselect, $dontselect, $selectparts, $contents, $options)
	{
		static $tablefields; // charge qu'une seule fois
		if ($selectparts['where'])
			$selectparts['where'] = 'WHERE '. $selectparts['where'];
		if ($selectparts['order'])
			$selectparts['order'] = 'ORDER BY '. $selectparts['order'];
		if ($selectparts['having'])
			$selectparts['having'] = 'HAVING '. $selectparts['having'];
		if ($selectparts['groupby'])
			$selectparts['groupby'] = 'GROUP BY '. $selectparts['groupby']; // besoin de group by ?

		// special treatment for limit when only one value is given.
		if ($selectparts['split']) {
			$split = $selectparts['split'];
			$offsetname = 'offset_'. substr(md5($name), 0, 5);

			$preprocesslimit = 
<<<PHP
\$currentoffset=(int)\$_REQUEST['{$offsetname}'];
PHP;
			$processlimit = 
<<<PHP
\$currenturl=basename(\$_SERVER['SCRIPT_NAME'])."?";
\$cleanquery=preg_replace("/(^|&){$offsetname}=\d+/","",\$_SERVER['QUERY_STRING']);
if(\$cleanquery[0]=="&") \$cleanquery=substr(\$cleanquery,1);
if(\$cleanquery) \$currenturl.=\$cleanquery."&";
if(\$context['nbresults']>{$split}) {
	\$context['nexturl']=\$currenturl."{$offsetname}=".(\$currentoffset + {$split});
} else {
	\$context['nexturl']="";
}
\$context['offsetname'] = '{$offsetname}';
\$context['limitinfo'] = {$split};
\$context['previousurl']= (\$currentoffset>={$split}) ? \$currenturl."{$offsetname}=".(\$currentoffset - {$split}) : "";
PHP;
			$limit = 
<<<PHP
".\$currentoffset.", {$split}
PHP;
		}	else {
			$limit = $selectparts['limit'];
		}

		if ($limit)
			$limit = 'LIMIT '. $limit;

		# c est plus complique que ca ici, car parfois la table est prefixee par la DB.

		// reverse the order in order the first is select in the last.
		$tablesinselect = array_reverse(array_unique($tablesinselect));
		$table = join(', ', array_reverse(array_unique($tables)));

		$select = $selectparts['select'];
		if ($dontselect) { // DONTSELECT
			// at the moment, the dontselect should not be prefixed by the table name !
			if (!$tablefields)
				require 'tablefields.php';
			if (!$tablefields)
				die("ERROR: internal error in decode_loop_content: table $table");

			$selectarr = array ();
			foreach ($tablesinselect as $t) {
				if (!$tablefields[$t])
					continue;
				$selectforthistable = array_diff($tablefields[$t], $dontselect); // remove dontselect from $tablefields
				if ($selectforthistable) { // prefix with table name
					array_push($selectarr, "$t.".join(",$t.", $selectforthistable));
				}
			}
			$select = join(",", $selectarr);
		} elseif (!$select && $tablesinselect) { // AUTOMATIQUE
			$select = join(".*,", $tablesinselect).".*";
		}
		if (!$select)
			$select = '1';
		$select .= $extrainselect;

// 		foreach (array ('sqlfetchassoc', 'sqlquery', 'sqlerror', 'sqlfree', 'sqlnumrows') as $piece) {
// 			if (!isset ($options[$piece]))
// 				$options[$piece] = $this->codepieces[$piece];
// 		}

		$sqlfetchassoc = !is_null($options['sqlfetchassoc']) ? sprintf($options['sqlfetchassoc'], '$result') : sprintf($this->codepieces['sqlfetchassoc'], '$result');
		#### $t=microtime();  echo "<br>requete (".((microtime()-$t)*1000)."ms): $query <br>";
		//
		// genere le code pour parcourir la loop
		//
		$this->fct_txt .= 
<<<PHP
if(!function_exists("loop_{$name}")) {
	function loop_{$name}(\$context)
	{
		{$preprocesslimit}
		\$query =	"SELECT count(*) as nbresults 
					FROM {$table} 
					{$selectparts['where']} 
					{$selectparts['groupby']} {$selectparts['having']}";
		\$result = mysql_query(\$query) or mymysql_error(\$query,'{$name}',__LINE__,__FILE__);
PHP;
		if($selectparts['groupby']) {
			$this->fct_txt .= 
<<<PHP
		\$context['nbresults'] = 0;
		while(\$row = {$sqlfetchassoc}) { \$context['nbresults']++; }
		\$context['nbresultats'] = \$context['nbresults'];
PHP;
		} else {
			$this->fct_txt .= 
<<<PHP
		\$row = {$sqlfetchassoc};
		\$context['nbresultats'] = \$context['nbresults'] = \$row['nbresults'];
PHP;
		}
		$this->fct_txt .= 
<<<PHP
		\$context['nblignes']=mysql_num_rows(\$result);
		mysql_free_result(\$result);
		\$query =	"SELECT {$select} 
					FROM {$table} 
					{$selectparts['where']} 
					{$selectparts['groupby']} {$selectparts['having']} {$selectparts['order']} 
					{$limit}";
PHP;
		if($options['showsql']) { 
			$this->fct_txt .= 
<<<PHP
		echo htmlentities(\$query);
PHP;
		}
		$this->fct_txt .= 
<<<PHP
		\$result=mysql_query(\$query) or mymysql_error(\$query, '{$name}', __LINE__, __FILE__);
		{$processlimit}
		\$generalcontext=\$context;
		\$count=0;
		if(\$row={$sqlfetchassoc}) {?>{$contents['BEFORE']}<?php 
			do {
			\$context=array_merge (\$generalcontext,\$row);
			\$context['count'] = ++\$count;
PHP;
		// gere le cas ou il y a un premier
		if ($contents['DOFIRST'])	{
			$this->fct_txt .= 
<<<PHP
			if(\$count==1) {{$contents['PRE_DOFIRST']}?>{$contents['DOFIRST']}<?php 
				continue;
			}
PHP;
		}
		// gere le cas ou il y a un dernier
		if ($contents['DOLAST']) {
			$this->fct_txt .= 
<<<PHP
			if(\$count==\$generalcontext['nbresults']) {{$contents['PRE_DOLAST']}?>{$contents['DOLAST']}<?php 
				continue;
			}
PHP;
		}
		$this->fct_txt .= 
<<<PHP
{$contents['PRE_DO']}?>{$contents['DO']}<?php 
			} while (\$count<\$generalcontext['nbresults'] && \$row={$sqlfetchassoc}); 
?>{$contents['AFTER']}<?php 
		}
PHP;

		if($contents['ALTERNATIVE']) {
			$this->fct_txt .= 
<<<PHP
		  else {?>{$contents['ALTERNATIVE']}<?php 
		}
PHP;
		}
		$this->fct_txt .= 
<<<PHP
		mysql_free_result(\$result);
	}
}
PHP;
	}

	function make_userdefined_loop_code($name, $contents)
	{
		// cree la fonction loop
		#echo "infilename=".$this->infilename;
		$localtpl = str_replace(array('.','-'),array('_','_'),basename($this->infilename)). '_';
		if ($contents['DO']) {
			$this->fct_txt .= 
<<<PHP
if(!function_exists("code_do_{$localtpl}{$name}")) { 
	function code_do_{$localtpl}{$name}(\$context) { 
?>{$contents['DO']}<?php 
	}
}
PHP;
		}
		if ($contents['BEFORE']) { // genere le code de avant
			$this->fct_txt .= 
<<<PHP
if(!function_exists("code_before_{$localtpl}{$name}")) { 
	function code_before_{$localtpl}{$name}(\$context) { 
?>{$contents['BEFORE']}<?php 
	} 
}
PHP;
		}
		if ($contents['AFTER'])	{ // genere le code de apres
			$this->fct_txt .= 
<<<PHP
if(!function_exists("code_after_{$localtpl}{$name}")) { 
	function code_after_{$localtpl}{$name}(\$context) { 
?>{$contents['AFTER']}<?php 
	} 
}
PHP;
		}
		if ($contents['ALTERNATIVE'])	{ // genere le code de alternative
			$this->fct_txt .= 
<<<PHP
if(!function_exists("code_alter_{$localtpl}{$name}")) { 
	function code_alter_{$localtpl}{$name}(\$context) { 
?>{$contents['ALTERNATIVE']}<?php 
	} 
}
PHP;
		}
		// fin ajout
	}

	/**
	 * Parse les fonctions Lodelscript.
	 *
	 * Un simple appel à parse_MACRO.
	 * @see parse_MACRO
	 */
	function parse_FUNC()
	{
		$this->parse_MACRO('FUNC');
	}

	/**
	 * Parse les macros Lodelscript.
	 *
	 * Un simple appel à parse_MACRO.
	 *
	 * @param string $tag définit la macro ou la func Lodelscript
	 * @see parse_MACRO
	 */
	function parse_MACRO($tag = 'MACRO')
	{
		// decode attributs
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);

		$name = trim($attrs['NAME']);
		if (!$name) {
			$this->errmsg("$tag without NAME attribut");
		}

		if (!isset ($this->macrocode[$name]))	{
			// search for the macro define
			$searchstr = '/<DEF'.$tag.'\s+NAME\s*=\s*"'.$attrs['NAME'].'"([^>]*)>(.*?)<\/DEF'.$tag.'>/s';

			#if (!preg_match_all($searchstr,$text,$defs,PREG_SET_ORDER)) 
			if (!preg_match_all($searchstr, $this->macros_txt, $defs, PREG_SET_ORDER)) {
				$this->errmsg("the macro $name is not defined");
			}
			$def = array_pop($defs); // get the last definition of the macro
			$code = preg_replace("/(^\n|\n$)/", "", $def[2]); // remove first and last line break

			$this->macrocode[$name]['code'] = $code;
			$this->macrocode[$name]['attr'] = $def[1];
		} // caching

		if ($tag == 'FUNC') { // we have a function macro
			$defattr = $this->_decode_attributs($this->macrocode[$name]['attr']);
			if ($defattr['REQUIRED']) {
				$required = preg_split("/\s*,\s*/", strtoupper($defattr['REQUIRED']));
				//$optional=preg_split("/\s*,\s*/",strtoupper($defattr['OPTIONAL']));

				// check the validity of the call
				foreach ($required as $arg) {
					if (!isset ($attrs[$arg])) {
						$this->errmsg("the macro $name required the attribut $arg");
					}
				}
			}
			$macrofunc = strtolower('macrofunc_'. $name.'_'. $this->signature);

			$this->_clearposition();
			// build the call
			unset ($attrs['NAME']);
			$args = array ();
			foreach ($attrs as $attr => $val) {
				$this->parse_variable($val, 'quote');
				$args[] = '"'. strtolower($attr). '"=>"'. $val. '"';
			}
			$args = join(",", $args);
			$this->arr[$this->ind] .= 
<<<PHP
<?php {$macrofunc}(\$context,array({$args})); ?>
PHP;
			if (!($this->funcs[$macrofunc])) {
				$this->funcs[$macrofunc] = true;
				// build the function 
				$tmpArr = $this->arr;
				$tmpInd = $this->ind;
				$tmpCountArr = $this->countarr;
				$this->arr = null;
				$this->_split_file($this->macrocode[$name]['code']);
				$this->parse_main();
				$this->arr = trim(join('', $this->arr));
				$this->fct_txt .= 
<<<PHP
if(!function_exists('{$macrofunc}')) { 
	function {$macrofunc}(\$context,\$args) {
		\$context=array_merge(\$context,\$args); 
?>{$this->arr}<?php 
	}
}
PHP;
				$this->arr = $tmpArr;
				$this->ind = $tmpInd;
				$this->countarr = $tmpCountArr;
				unset($tmpArr, $tmpInd, $tmpCountArr);
			}
			
		} else { // normal MACRO
			$this->_split_file($this->macrocode[$name]['code'], 'insert');
			$this->_clearposition();
		}
	}

	function parse_BLOCK($block=array())
	{
		if(!empty($block)) {
			$attrs = $this->_decode_attributs($block[1]);
		} else {
			$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
		}
		if(!($attrs['ID'] = (int)$attrs['ID']))
			$this->errmsg("Incorrect ID for block number ".count($this->blocks)+1);
		if(empty($block)) {
			if(in_array($attrs['ID'], $this->blocks))
				$this->errmsg("Duplicate ID for block number ".count($this->blocks)+1);
			$this->blocks[] = $attrs['ID'];
			$this->_clearposition();
		}
		if(isset($attrs['REFRESH'])) {
			if (!is_numeric($attrs['REFRESH'])) {
				$refreshtimes = explode(",", $attrs['REFRESH']);
				foreach ($refreshtimes as $k=>$refreshtim) {
					$refreshtim = explode(":", $refreshtim);
					$tmpcode .= '$refreshtime['.$k.']=mktime('.(int)$refreshtim[0].','.(int)$refreshtim[1].','.(int)$refreshtim[2].',$date[\'mon\'],$date[\'mday\'],$date[\'year\']);';
					$code.= ($k>0 ? ' || ' : '').'($cachetime<$refreshtime['.$k.'] && $refreshtime['.$k.']<$now)';
				}
				$tmpcode = '$now=time();$date=getdate($now);'.$tmpcode;
				unset($refreshtimes, $refreshtim);
			} else {
				$code = '($cachetime + '.$attrs['REFRESH'].') < time()';
			}
			// $escapeRefreshManager
			// @see View->_eval()
			$code = 
<<<PHP
<?php 
{$tmpcode}
\$cachetime=myfilemtime(getCachedFileName("{$cachedTemplateFileName}", \$GLOBALS['site']."_TemplateFileEvalued", \$GLOBALS['cacheOptions']));
if(({$code}) && !\$escapeRefreshManager){ 
	if(!is_null(\$this)) {
		echo \$this->renderTemplateFile(\$context, "{$this->tpl}", "{$this->cache_rep}", "{$this->base_rep}", true, "{$attrs['REFRESH']}", '{$attrs['ID']}');
	} else {
		\$context['noreset'] = true;
		insert_template(\$context, "{$this->tpl}", "{$this->cache_rep}", "{$this->base_rep}", true, "{$attrs['REFRESH']}", '{$attrs['ID']}');
	}
}else{ ?>
PHP;
		}
		if(empty($block)) {
			$this->arr[$this->ind] = $code;
			unset($code);
			$this->ind += 3;
	
			$this->parse_main();
			if ($this->arr[$this->ind] != "/BLOCK")
				$this->errmsg("&lt;/BLOCK&gt; expected, ".$this->arr[$this->ind]." found", $this->ind);
	
			$this->_clearposition();
			if(isset($attrs['REFRESH'])) {
				$this->arr[$this->ind] = '<?php } ?>';
			}
		} else {
			$tmpArr = $this->arr;
			$tmpInd = $this->ind;
			$tmpCountArr = $this->countarr;
			$this->arr = null;
			$this->_split_file($block[2]);
			$this->parse_main();
			$this->arr = trim(join('', $this->arr));
			$code .= 
<<<PHP
{$this->arr}
PHP;
			if(isset($attrs['REFRESH'])) {
				$code .= '<?php } ?>';
			}
			$this->arr = $tmpArr;
			$this->ind = $tmpInd;
			$this->countarr = $tmpCountArr;
			unset($tmpArr, $tmpInd, $tmpCountArr);
			return $code;
		}
	}

	/**
	 * Traite les conditions avec IF
	 */
	function parse_IF()
	{
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
		if (!$attrs['COND'])
			$this->errmsg("Expecting a COND attribut in the IF tag");
		$cond = $attrs['COND'];
		$this->parse_variable($cond, false); // parse the attributs
		$cond = replace_conditions($cond, "php");

		$this->_clearposition();
		$this->arr[$this->ind + 1] = '<?php if ('.$cond.') { ?>';

		do {
			$this->ind += 3;
			$this->parse_main();
			if ($this->arr[$this->ind] == "ELSE") {
				if ($elsefound)
					$this->errmsg("ELSE found twice in IF condition", $this->ind);
				$elsefound = 1;
				$this->_clearposition();
				$this->arr[$this->ind + 1] = '<?php } else { ?>';
			}	elseif ($this->arr[$this->ind] == "ELSEIF") {
				$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
				if (!$attrs['COND'])
					$this->errmsg("Expecting a COND attribut in the ELSEIF tag");
				$cond = $attrs['COND'];
				$this->parse_variable($cond, false); // parse the attributs
				$cond = replace_conditions($cond, "php");
				$this->_clearposition();
				$this->arr[$this->ind + 1] = '<?php } elseif ('.$cond.') { ?>';
			}	elseif ($this->arr[$this->ind] == "/IF") {
				$isendif = 1;
			}	else
				$this->errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in IF condition", $this->ind);
		}	while (!$isendif && $this->ind < $this->countarr);

		if (!$isendif)
			$this->errmsg("IF not closed", $this->ind);

		$this->_clearposition();
		$this->arr[$this->ind + 1] = '<?php } ?>';
	}

	/**
	 * Traite les conditions avec SWITCH
	 */
	function parse_SWITCH()
	{
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
		if (!$attrs['TEST'])
			$this->errmsg("Expecting a TEST attribut in the SWITCH tag");
		$test = quote_code($attrs['TEST']);
		$this->parse_variable($test, false); // parse the attributs
		$test = replace_conditions($test, 'php');

		$this->_clearposition();
		$toput = $this->ind + 1;

		if (trim($this->arr[$this->ind + 2]))
			$this->errmsg("Expecting a CASE tag after the SWITCH tag");
		// PHP ne veut aucun espace entre le switch et le premier case !
		$begin = true;
		do {
			$this->ind += 3;
			$this->parse_main();
			if ($this->arr[$this->ind] == 'DO') {
				$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
				if ($attrs['CASE']) {
					$this->_clearposition();
					// condition par défaut
					if('default' == $attrs['CASE']) {
						if($begin) {
							$this->arr[$toput] = '<?php switch ('.$test.') { default: { ?>';
							$begin = false;
						} else
							$this->arr[$this->ind + 1] = '<?php default: { ?>';
					} else {
						$this->parse_variable($attrs['CASE'], false); // parse the attributs
						if($begin) {
							$this->arr[$toput] = '<?php switch ('.$test.') { case "'.quote_code($attrs['CASE']).'": { ?>';
							$begin = false;
						} else
							$this->arr[$this->ind + 1] = '<?php case "'.quote_code($attrs['CASE']).'": { ?>';
					}
				} elseif($attrs['CASES']) {
					// multiple case
					$cases = explode(',', $attrs['CASES']);
					$nbCases = count($cases)-1;
					$this->_clearposition();
					foreach($cases as $k=>&$case) {
						$case = trim($case);
						$this->parse_variable($case, false); // parse the attributs
						if($begin) {
							$this->arr[$toput] = '<?php switch ('.$test.') { case "'.quote_code($case).'":'.($k==$nbCases ? ' { ?>' : ' ?>');
							$begin = false;
						} else
							$this->arr[$this->ind + 1] .= '<?php case "'.quote_code($case).'":'.($k==$nbCases ? ' { ?>' : ' ?>');
					}
				} else {
					$this->errmsg("missing attribute 'CASE(S)' in SWITCH condition", $this->ind);
				}
			} elseif ($this->arr[$this->ind] == "/DO") {
				$this->_clearposition();
				$this->arr[$this->ind + 1] = "<?php break; } ?>\n";
			} elseif ($this->arr[$this->ind] == "/SWITCH") {
				$endswitch = true;
			} else
				$this->errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in SWITCH condition", $this->ind);
		} while (!$endswitch && $this->ind < $this->countarr);

		if (!$endswitch)
			$this->errmsg("SWITCH block is not closed", $this->ind);

		$this->_clearposition();
		$this->arr[$this->ind + 1] = '<?php } ?>';
	}
	
	/**
	 * Traite les LET
	 * Le context est protégé, il ne peut être modifié que localement
	 * pendant l'évaluation des templates
	 * @see View::resetContext()
	 */
	function parse_LET()
	{
		if (!preg_match("/\b(VAR|ARRAY)\s*=\s*\"([^\"]*)\"(\s* GLOBAL=\"([^\"]*)\")?/", $this->arr[$this->ind + 1], $result))
			$this->errmsg("LET have no VAR|ARRAY attribut");
		$regexpVarName = ('ARRAY' == $result[1]) ? '('.$this->variable_regexp.')(\[[a-zA-Z0-9_]*\])?' : '('.$this->variable_regexp.')';
		if (!preg_match("/^{$regexpVarName}$/i", $result[2], $res))
			$this->errmsg("Variable \"$result[2]\" in LET is not a valid variable", $this->ind);
		
		$var = strtolower($res[1]);
		if(isset($this->denied[$var]))
			$this->errmsg("Variable '{$var}' is not accessible in templates scope in function ".__FUNCTION__);
		if('VAR' == $result[1]) {
			// commenté septembre 2008 par pierre-alain car pas d'utilité trouvée ?!?
			//$this->parse_variable($result[2], false); // parse the attributs
			$this->_clearposition();
			$this->arr[$this->ind + 1] = '<?php ob_start(); ?>';
	
			$this->ind += 3;
			#$this->parse_main2();
			$this->parse_main();
			if ($this->arr[$this->ind] != "/LET")
				$this->errmsg("&lt;/LET&gt; expected, '".$this->arr[$this->ind]."' found", $this->ind);

			$this->_clearposition();
			$this->arr[$this->ind + 1] = $result[4] ? '<?php $GLOBALS[\'context\'][\''.$var.'\']=ob_get_contents();ob_end_clean(); ?>' : '<?php $context[\''.$var.'\']=ob_get_contents();ob_end_clean(); ?>';
		} else {
			$this->_clearposition();
			$this->ind += 2;
			preg_match_all("/(<\?php\s+echo\s+(.*?);\s+\?>|[^,]+)*/", $this->arr[$this->ind], $values);
			$vars = $originalVars = array();
			foreach($values[0] as $k=>$value) {
				if('' === (string)$value) continue;
				if($values[2][$k]) { // variable LS déjà parsée
					if(!isset($originalVar)) $originalVar = $values[2][$k];
					$vars[$k] = "array_values((array){$values[2][$k]})";
				} else {
					$value = quote_code(trim($value));
					if(!isset($originalVar)) $originalVar = "'{$value}'";
					$vars[$k] = "(array)'{$value}'";
				}
			}
			
			$this->arr[$this->ind] = '';
			$this->ind++;
			if ($this->arr[$this->ind] != "/LET")
				$this->errmsg("&lt;/LET&gt; expected, '".$this->arr[$this->ind]."' found", $this->ind);
			$this->_clearposition();
			$merge = (count($vars)>1 || !$res[2]) ? 'array_merge('.join(',',$vars).')' : $originalVar;
			unset($values,$originalVar, $vars);
			$this->arr[$this->ind + 1] = $result[4] ? 
				'<?php $GLOBALS[\'context\'][\''.$var.'\']'.$res[2].'='.$merge.'; ?>' : 
				'<?php $context[\''.$var.'\']'.$res[2].'='.$merge.'; ?>';
		}
	}

	/**
	 * Traite les ESCAPE
	 */
	function parse_ESCAPE()
	{
		$escapeind = $this->ind;
		$this->_clearposition();
		$this->isphp = TRUE;
		$this->ind += 3;

		$this->parse_main();
		if ($this->arr[$this->ind] != "/ESCAPE")
			$this->errmsg("&lt;/ESCAPE&gt; expected, ".$this->arr[$this->ind]." found", $this->ind);

		for ($i = $escapeind; $i < $this->ind; $i += 3)	{
			if (trim($this->arr[$i +2]))
				$this->arr[$i +2] = '<?php if ($GLOBALS[\'cachedfile\']) { echo \''.quote_code($this->arr[$i +2]).'\'; } else {?>'.$this->arr[$i +2].'<?php } ?>';
		}
		$this->_clearposition();
	}

	/**
	 * Accept an array or a string
	 *
	 * @access private
	 */
	function _checkforrefreshattribut($mixed)
	{
		if(empty($mixed))
			return;

		if (is_array($mixed))	{
			$attrs = $mixed;
		}	else {
			$attrs = $this->_decode_attributs($mixed);
		}

		if (!$attrs['REFRESH'])
			return;

		$refresh = trim($attrs['REFRESH']);
		$timere = "(?:\d+(:\d\d){0,2})"; // time regexp
		if (!is_numeric($refresh) && !preg_match("/^$timere(?:,$timere)*$/", $refresh))
			$this->errmsg("Invalid refresh time \"".$refresh."\"");

		if (!$this->refresh || (is_numeric($refresh) && is_numeric($this->refresh) && $refresh < $this->refresh)) {
			$this->refresh = $refresh;
		} elseif (!is_numeric($refresh) && !is_numeric($this->refresh))	{
			$this->refresh .= ",".$refresh;
		}
	}

	function prefixTablesInSQL($sql) 
	{
		if (!method_exists($this, 'prefixTableName'))
			return $sql;
		##echo $sql,"<br>";
		$n       = strlen($sql);
		$inquote = false;

		for ($i = 0; $i < $n; $i ++) {
			$c = $sql {$i};

			if ($inquote) { // we are in a string
				if ($c == $quotec && !$escaped) {
					$inquote = false;
				} else {
					$escaped = $c == "\\" && !$escaped;
				}
			} elseif ($c == '"' || $c == "'")	{ // quote ?
				$inquote = true;
				$escaped = false;
				$quotec = $c;
			} elseif ($c == "." && preg_match("/((?:`?\w+`?\.)?\w+)$/", $str, $result))	{ // table dot ?
				$prefixedtable = $this->prefixTableName($result[1]);
				if ($prefixedtable != $result[1])	{
					// we have a table... let's prefix it
					$ntablename = strlen($result[1]);
					$str = substr($str, 0, - $ntablename).$prefixedtable;
				}
			}
			$str .= $c;
		}
		#echo "to:",$str,"<br>";
		return $str;
	}

	function _decode_attributs($text, $options = '')
	{
		// decode attributs
		$arr = explode('"', $text);
		$n = count($arr);
		for ($i = 0; $i < $n; $i += 2) {
			$attr = trim(substr($arr[$i], 0, strpos($arr[$i], "=")));
			if (!$attr)
				continue;
			if ($options == "flat")	{
				$ret[] = array ("name" => $attr, "value" => $arr[$i +1]);
			}	else {
				$ret[$attr] = $arr[$i +1];
			}
		}
		return $ret;
	}

	function _clearposition()
	{
		$this->arr[$this->ind] = $this->arr[$this->ind + 1] = "";
		$this->arr[$this->ind + 2] = preg_replace(array("/^[\t\n]+/", "/[\t\n]+$/"), "", $this->arr[$this->ind + 2]);
	}

	// bug [#4454]
	function _checkSplit(&$arr, $nbArr)
	{
		for($i=2;$i<$nbArr;$i+=3) {
			$nbQuotes = substr_count($arr[$i], '"');
			if(0 === $nbQuotes) {
				$nbQuotes = substr_count($arr[$i], "'");
				if(0 === $nbQuotes) continue;
			}

			if($nbQuotes % 2) {
				$pos = strpos($arr[$i+1], '>');
				$arr[$i] .= '>'.substr($arr[$i+1], 0, $pos);
				$arr[$i+1] = substr_replace($arr[$i+1], '', 0, $pos+1);
			}
		}
	}

	function _split_file($contents, $action = 'insert', $blockId=0)
	{
		if($blockId>0) {
			if(!preg_match("/<BLOCK\b ([^>]*ID=\"".$blockId."\"[^>]*)>.*?<\/BLOCK>/s", $contents, $matches)) {
				$this->errmsg('No block number '.$blockId.' found in file '.$this->infilename);
			}
			$attrs = $this->_decode_attributs($matches[1]);
			$this->_checkforrefreshattribut($attrs);
			if(isset($attrs['CHARSET'])) $this->charset = $attrs['CHARSET'];

			// get the possible macros
			preg_match_all('/<DEF(FUNC|MACRO)\s+NAME\s*=\s*"[^"]+"[^>]*>.*?<\/DEF\\1>/s', $contents, $m);
			// get the possible macrofiles
			preg_match_all("/<USE\s+MACROFILE=\"[^\"]+\"\s*\/?>/", $contents, $mm);

			$contents = join('',$m[0]) . join('',$mm[0]) . $matches[0];
			unset($matches, $mm,$m,$attrs);
			$arr = preg_split("/<(\/?(?:".join("|", $this->commands)."))\b([^>]*?)\/?>/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
		} else {
			$arr = preg_split("/<(\/?(?:".join("|", $this->commands)."))\b([^>]*?)\/?>/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
		}

		$nbArr = count($arr);
		// repair bad splitting
		$this->_checkSplit($arr, $nbArr);
		
		// parse the variables
		$this->parse_variable($arr[0]);
		
		for ($i = 3; $i < $nbArr; $i += 3) {
			$this->parse_variable($arr[$i]); // parse the content
		}
		if (!$this->arr) {
			$this->ind = 0;
			$this->currentline = 0;
			$this->arr = $arr;
		} elseif ($action == 'insert') {
			$this->arr[$this->ind + 2] = $arr[count($arr) - 1].$this->arr[$this->ind + 2];
			array_splice($this->arr, $this->ind + 2, 0, array_slice($arr, 0, -1));
		} elseif ($action == 'add') {
			$this->arr[count($this->arr) - 1] .= $arr[0];
			$this->arr = array_merge($this->arr, array_slice($arr, 1));
		}
		$this->countarr = count($this->arr);
		if (!$this->ind)
			$this->ind = 1;
	}
} // clase Parser

/*
réécrit suite bug signalé par François Lermigeaux sur lodel-devel
*/
function replace_conditions($text, $style)
{
	$conditions = array('gt'=>'>','lt'=>'<','ge'=>'>=','le'=>'<=','eq'=>($style == "sql" ? "=" : "=="),'ne'=>'!=', 'and'=>'&&', 'or'=> '||', 'sne'=>'!==', 'seq'=>'===');
	$tmp = preg_split("/\b(".join('|',array_keys($conditions)).")\b/i", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	$open = false;
	foreach($tmp as $texte) {
		if(preg_match_all("/(?<!\\\)'/", $texte, $m) % 2) {
			$open = (TRUE === $open) ? false : true;
		}
		$t = strtolower(trim($texte));
		if(FALSE === $open && isset($conditions[$t])) {
			$ret .= $conditions[$t];
			continue;
		}
		$ret .= $texte;
	}
	return preg_replace("/([\w'\[\]\$]*) like (\/[^\/]*\/)/i", 'preg_match("\\2i", \\1, $context[\'matches\'])', $ret);
}

function stripcommentandcr(& $text)
{
	return preg_replace(array ("/\r/", "/<!--\[\s*\]-->\s*\n?/s" ,"/<!--\[(?!if IE).*?\]-->\s*\n?/s"), "", $text);
}

function quote_code($text)
{
	return addcslashes($text, "'");
}
?>