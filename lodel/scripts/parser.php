<?php
/**
 * Fichier Parser
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

class Parser
{
	
	protected $infilename; // nom du template
	protected $signature;
	protected $variable_regexp = "[A-Z][A-Z_0-9]*(?:\.[A-Z][A-Z_0-9]*)*";
	protected $variablechar; // list of prefix for the variables

	protected $loops = array ();
	protected $funcs = array ();
	protected $macrocode = array ();

	protected $charset;

	protected $commands = array ();
	protected $codepieces = array (); // code piece definition
	protected $macros_txt;
	protected $fct_txt;

	#  var $wantedvars;
	protected $looplevel = 0;

	protected $arr;
	protected $countarr;
	protected $linearr;
	protected $currentline;
	protected $ind;
	public $refresh = "";
    	protected $sqlrefresh;
	protected $isphp = false; // the parser produce a code which produce either html, either php. In the latter, a sequence must be written at the beginning to inform the cache system.

	protected $id = "";
	protected $tpl; // actual name of tpl
	protected $blocks; // possibles blocks in template
	protected $isLoop; // is the parser called for a loop with refresh
	protected $conditions;
	protected $joinedconditions;

	protected function _errmsg($msg, $ind = 0)
	{
		// À REVOIR : déterminer le numéro de ligne dans le template
		//if ($ind)
		//$line = "line ".$this->linearr[$ind];
		//trigger_error("LODELSCRIPT ERROR line $line (".$this->infilename."): $msg", E_USER_ERROR);
		trigger_error("LODELSCRIPT ERROR in file ".$this->infilename." : $msg", E_USER_ERROR);
	}

	protected function parse_loop_extra(& $tables, & $tablesinselect, & $extrainselect, & $selectparts)
	{
	}

	public function parse_variable_extra($prefix, $name)
	{
		return false;
	}
	
	protected function parse_before($contents)
	{
	}
	
	protected function parse_after(&$contents)
	{
	}
	
	protected function decode_loop_content_extra($balise, & $content, & $options, $tables)
	{
	}

	protected function __construct()
	{ // constructor
		$this->commands = array ("USE", "MACRO", "FUNC", "LOOP", "IF", "LET", "ELSE", "ELSEIF", "DO", "DOFIRST", "DOLAST", "BEFORE", "AFTER", "ALTERNATIVE", "ESCAPE", "CONTENT", "SWITCH", "CASE", "BLOCK", "TEXT");
		$this->commandsline = join('|', $this->commands);
		$this->codepieces = array ('sqlfetchassoc' => "mysql_fetch_assoc(%s)", 'sqlquery' => "mysql_query(%s)", 'sqlerror' => "or mymysql_error(%s,%s, __LINE__, __FILE__)", 'sqlfree' => "mysql_free_result(%s)", 'sqlnumrows' => "mysql_num_rows(%s)");
		$this->conditions['sql'] = array('gt'=>'>','lt'=>'<','ge'=>'>=','le'=>'<=','eq'=>"=",'ne'=>'!=', 'and'=>'&&', 'or'=> '||', 'sne'=>'!==', 'seq'=>'===');
		$this->joinedconditions['sql'] = join('|',array_keys($this->conditions['sql']));
		$this->conditions['php'] = array('gt'=>'>','lt'=>'<','ge'=>'>=','le'=>'<=','eq'=>"==",'ne'=>'!=', 'and'=>'&&', 'or'=> '||', 'sne'=>'!==', 'seq'=>'===');
		$this->joinedconditions['php'] = join('|',array_keys($this->conditions['php']));
	}

	public function parse($in, $blockId=0, $cache_rep='', $loop=null)
	{
		if (!file_exists($in))
		{
			if (!headers_sent()) 
			{
				header("HTTP/1.0 400 Bad Request");
				header("Status: 400 Bad Request");
				header("Connection: Close");
				flush();
			}
			trigger_error("Unable to read file $in", E_USER_ERROR);
		}
		$this->infilename = $in;
		$this->charset = '';
		$this->tpl = basename($in, '.html');
		$this->base_rep = str_replace($this->tpl.'.html', '', $in);
		$this->cache_rep = $cache_rep;
		$this->blockid = (int)$blockId;
		$this->signature = preg_replace("/\W+/", "_", $in);
		$this->fct_txt = $this->macros_txt = $this->charset = $this->ind = $this->countarr = null;
        	$this->refresh = $this->looplevel = 0;
		$this->loops = $this->macrocode = $this->macrofunc = $this->translationform = $this->translationtags = $this->blocks = array();
		$this->isLoop = (bool)$loop;
		$this->sqlrefresh = $GLOBALS['sqlCacheTime'];
		// read the file
        	$contents = @file_get_contents($in);
       		if(false === $contents) $this->_errmsg("Unable to read file $in"); 

		$contents = stripcommentandcr($contents);

		$this->_split_file($contents, '', $this->blockid, $loop); // split the contents into commands
        
        	unset($contents);
		$this->parse_main(); // parse the commands
		
		if ($this->ind != $this->countarr)
			$this->_errmsg("this file contains more closing tags than opening tags");
        	$template = array();
        	$template['contents'] = join("", $this->arr); // recompose the file

		unset ($this->arr,$this->macros_txt, $this->loops, $this->macrocode, $this->macrofunc); // save memory now.
		$this->parse_after($template['contents']); // user defined parse function

		// remove  <DEFMACRO>.*?</DEFMACRO>
		$template['contents'] = preg_replace("/<DEF(MACRO|FUNC)\b[^>]*>.*?<\/DEF(\\1)>\s*\n*/s", "", $template['contents']);

		if ($this->fct_txt)
		{
			$template['contents'] = 
<<<PHP
<?php 
{$this->fct_txt}
 ?> 
{$template['contents']}
PHP;
		}
		unset($this->fct_txt);

		// clean the open/close php tags
		$template['contents'] = preg_replace(array ('/\?>[\r\t\n]*<\?(php\b)?/', '/<\?(php\b)?[\t\r\n]*\?>/'), array ("", ""), $template['contents']);

		if ($this->charset != 'utf-8') {
			#$t=microtime();
			if(!function_exists('convertHTMLtoUTF8'))
				include 'utf8.php'; // conversion des caracteres
			$template['contents'] = utf8_encode($template['contents']);
			convertHTMLtoUTF8($template['contents']);
		}
        
        	$template['refresh'] = $this->refresh;
        	return $template;
	}

	protected function parse_variable(& $text, $escape = 'php')
	{
		global $context;
		$i = strpos($text, '[');
		while ($i !== false) {
			$startvar = $i;
			++$i;
			if(!isset($text{$i})) // not a var, just a '['
			{
				$i = strpos($text, '[', $i);
				continue;
			}
			// parenthesis syntaxe [(
			if ($text {$i}	== '(') {
				$para = true;
				++$i;
			}	else {
				$para = false;
			}

			if (isset($text{$i}) && $text {$i} == '#' || $text {$i} == '%' || $text {$i} == $this->variablechar) 
			{
				$varchar = $text {$i};
				++$i;
				// look for the name of the variable now
				if (!isset($text{$i}) || $text {$i}	< 'A' || $text {$i}	> 'Z')
				{
					$i = strpos($text, '[', $i);
					continue; // not a variable
				}
				$varname = $text {$i};
				++$i;
				while (isset($text{$i}) && (($text {$i}	>= 'A' && $text {$i}	<= 'Z') || ($text {$i}	>= '0' && 
								$text {$i}	<= '9') || $text {$i}	== '_' || $text {$i}	== '.'))	{
					$varname .= $text {$i};
					++$i;
				}

				if(!isset($text{$i}))
				{
					$i = strpos($text, '[', $i);
					continue; // not a variable
				}

				if($text{$i} == '#' || $text{$i} == '%') { // syntaxe [#VAR.#VAR] pour les tableaux !
					do {
						$tmpvarname = '';
						$isvar = false;
						if($text{$i} == '#' || $text{$i} == '%') {
							$tmpvarname = '['.$text{$i};
							$isvar = true;
						}
						++$i;
						while (isset($text{$i}) && (($text {$i}	>= 'A' && $text {$i}	<= 'Z') || ($text {$i}	>= '0' && 
									$text {$i}	<= '9') || $text {$i}	== '_'))	{
							$tmpvarname .= $text {$i};
							++$i;
						}
						if($isvar) {
							$tmpvarname .= ']';
							$this->parse_variable($tmpvarname, false);
						}
						$varname .= $tmpvarname;
						if(isset($text{$i}) && $text{$i} == '.') $varname .= $text{$i};
					} while($text{$i} != ']' && $text{$i} != '|' && $text{$i} != ':');
				}

				$pipefunction = '';

				if (isset($text{$i}) && $text {$i} == ':')	{ // a lang
					$lang = '';
					++$i;
					if(!isset($text{$i}))
					{
						$i = strpos($text, '[', $i);
						continue; // not a variable
					}
					if ($text{$i} == '#') { // pour syntaxe LS [#RESUME:#SITELANG] et [#RESUME:#DEFAULTLANG.#KEY] d'une boucle foreach
						++$i;
						$is_var = true; // on a une variable derriere les ':'
						$is_array = false;
						while (($text{$i} >= 'A' && $text{$i} < 'Z') || $text {$i} == '.' || $text {$i} == '#' || $text {$i} == '_' || 
							($text {$i}	>= '0' && $text {$i}	<= '9')) {
							if ($text {$i} == '.') { $is_array = true; }
							$lang .= $text {$i};
							++$i;
						}
					} else { //pour syntaxe LS [#RESUME:FR]
						$is_var = false;
						while (($text{$i} >='A' && $text{$i} < 'Z')) {
							$lang .= $text {$i};
							++$i;
						}
					}
					$lang = strtolower($lang);
					if ($is_var === true) {
						if ($is_array === true) {
							// pour syntaxe LS [#RESUME:#DEFAULTLANG.#KEY] d'une boucle foreach
							// ou pour syntaxe LS [#RESUME:#OPTIONS.METADONNEESSITE.LANG]
							$tab = explode ('.', $lang);
							$value = '';
							foreach($tab as $t)
							{
								if('#' == $t{0}) 
								{
									$t = substr($t, 1);
									$value .= '[(isset($context[\''.$t.'\'])?$context[\''.$t.'\']:null)]';
								}
								else
								{
									$value .= '[\''.$t.'\']';
								}
							}
							$lang =  '(isset($context'.$value.')?$context'.$value.':null)';
						} else {
							$lang = '(isset($context[\''.$lang.'\'])?$context[\''.$lang.'\']:null)';
						}
						$pipefunction = '|multilingue('.$lang.')';
					} else	$pipefunction = '|multilingue(\''.$lang.'\')';
				}
				
				if (isset($text{$i}) && $text {$i} == '|')	{ // have a pipe function
					// look for the end of the variable
					$bracket = 1;
					$mustparse = false;
					while ($bracket) {
						switch ($text {$i})	{
						case '[' :
							++$bracket;
							$mustparse = true; // potentially a new variable
							break;
						case ']' :
							--$bracket;
							break;
						}
						if ($bracket > 0)
							$pipefunction .= $text {$i};
						++$i;
					}
					--$i; // comes back to the bracket.
					if ($para && substr($pipefunction, -1) == ')')	{
						$pipefunction = substr($pipefunction, 0, -1);
						--$i;
					}
					if ($mustparse)	{
						$this->parse_variable($pipefunction, false);
					}
				}

				if (!isset($text{$i}))
				{
					$i = strpos($text, '[', $i);
					continue; // not a variable
				}

				// look for a proper end of the variable
				if ($para && $text {$i}	== ')' && $text {$i +1}	== ']')	{
					$i += 2;
				}	elseif (!$para && $text {$i} = ']')	{
					++$i;
				}
				else
				{
					$i = strpos($text, '[', $i);
					continue; // not a variable
				}

				// build the variable code
				$varcode = $this->_make_variable_code($varchar, $varname, $pipefunction, $escape);
				$text = substr_replace($text, $varcode, $startvar, $i - $startvar);
				$i = $startvar +strlen($varcode); // move the counter
			} // we found a variable
			$i = strpos($text, '[', $i);
		} // while there are some variable
	}

	protected function _escapeVar($variable, $escape)
	{
		switch ($escape) {
			case 'php' :
				// traitement normal, php espace
				$variable = "<?php \$tmp={$variable};if(is_array(\$tmp)){\$isSerialized=true;echo serialize(\$tmp);}else{echo \$tmp;}unset(\$tmp);?>";
				break;
			case 'quote' :
					$variable = "\".{$variable}.\"";
				break;
			default : break;
		}
		return $variable;
	}

	protected function _make_variable_code($prefix, $name, $pipefunction, $escape)
	{
		static $vars = array();
        
		// in cache ?
		if(isset($vars[$prefix][$name][$pipefunction][$escape]))
		{
			return $vars[$prefix][$name][$pipefunction][$escape];
		}
		elseif(isset($vars[$prefix][$name][$pipefunction]['var'])) 
		{
            		$vars[$prefix][$name][$pipefunction][$escape] = $this->_escapeVar($vars[$prefix][$name][$pipefunction]['var'], $escape);
			return $vars[$prefix][$name][$pipefunction][$escape];
		}
        
		$variable = $this->parse_variable_extra($prefix, $name);
		if ($variable === false) { // has the variable being processed ?
			$code = '';
			$name = strtolower($name);
			if(false !== strpos($name, '.')) {
				$brackets = explode('.', $name);
				foreach($brackets as $bracket) {
					$code .= ('(' === $bracket{0}) ? '['.$bracket.']' : "['{$bracket}']";
				}
			} else {
				$code = "['{$name}']";
			}
			
           	 	if('%' === (string)$prefix) {
				$variable = 
<<<PHP
(isset(\$GLOBALS['context']{$code})?\$GLOBALS['context']{$code}:null)
PHP;
			} else {
				$variable = 
<<<PHP
(isset(\$context{$code})?\$context{$code}:null)
PHP;
			}
			unset($code);
		}

		# parse the filter
		if ($pipefunction) 
		{ // traitement particulier ?
			//echo $pipefunction."<br />";
			$length = strlen($pipefunction);
			$filter = $args = '';
			$currentQuote = false;
			$open = 0;
			$quote = false;
			$funcArray = array();
			$argsArray = array();
			$new = false;
			for($i=1;$i<$length;$i++)
			{
				if(!$new)
				{
					if(!(($pipefunction{$i} >= 'a' && $pipefunction{$i} <= 'z') || ($pipefunction{$i} >= 'A' && $pipefunction{$i} <= 'Z')))
						$this->_errmsg("The pipe functions \"$pipefunction\" are invalid");
					$new = true;
					$open = 0;
					$quote = false;
					$currentQuote = '';
				}
				elseif($open && '\\' !== $pipefunction{$i-1} && ('"' === $pipefunction{$i} || "'" === $pipefunction{$i}))
				{
					if(!$quote)
					{
						$quote = true;
						$currentQuote = $pipefunction{$i};
					}
					elseif($currentQuote === $pipefunction{$i})
					{
						$quote = false;
						$currentQuote = '';
					}
				}
				elseif('\\' !== $pipefunction{$i-1} && !$quote && '(' === $pipefunction{$i})
				{
					++$open;
					if($open === 1)
						continue;
				} 
				elseif('\\' !== $pipefunction{$i-1} && $open && !$quote && ')' === $pipefunction{$i})
				{
					--$open;
					if($open === 0)
						continue;
				}
				elseif('|' === $pipefunction{$i} && !$open)
				{
					$funcArray[] = $filter;
					$argsArray[] = $args;
					$filter = $args = '';
					$new = false;
					continue;
				}

				if(!$open)
					$filter .= $pipefunction{$i};
				else
					$args .= $pipefunction{$i};
			}
			$funcArray[] = $filter;
			$argsArray[] = $args;

			foreach($funcArray as $k=>$fct) {
				if(!$fct) continue;

				if ($fct == "false" || $fct == "true") {
					$fct .= "function";
				}
				elseif ($fct == "else") {
					$fct = "falsefunction";
				}
                
                		if('isset' == $fct || 'empty' == $fct)
                    			$fct = 'l'.$fct;
                
				if ($fct) {
					if('' !== $argsArray[$k])
					{
						$argsArray[$k] = ','.$argsArray[$k];
					}
					//si la variable est contenue dans les arguments :
					$variable = "{$fct}({$variable}{$argsArray[$k]})";
				}
			}
			
			unset($funcArray, $argsArray);
		}
        
		$vars[$prefix][$name][$pipefunction]['var'] = $variable; // caching
		$vars[$prefix][$name][$pipefunction][$escape] = $this->_escapeVar($variable, $escape);
       		return $vars[$prefix][$name][$pipefunction][$escape];
	}

	protected function countlines($ind)
	{
		if ($ind == 0) {
			$this->currentline += substr_count($this->arr[$ind], "\n");
		}	else {
			$this->linearr[$ind] = $this->currentline;
			$this->currentline += substr_count($this->arr[$ind +1], "\n") + substr_count($this->arr[$ind +2], "\n");
		}
	}

	protected function parse_main()
	{	
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
			if(!isset($siteroot)) $siteroot = defined('SITEROOT') ? SITEROOT : '';
            		if(!isset($sharedir)) $sharedir = C::get('sharedir', 'cfg');
            		if(!isset($home)) $home = C::get('home', 'cfg');
            
			$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
			if (isset($attrs['MACROFILE'])) 
			{
				$macrofilename = $attrs['MACROFILE'];
				if (file_exists("./tpl/".$macrofilename))	
				{
					$path = './tpl/';
				} 
				elseif(file_exists($this->base_rep.$macrofilename))
				{
					$path = $this->base_rep;
				}
				elseif (file_exists($sharedir."/macros/".$macrofilename)) 
				{
					$path = $sharedir."/macros/";
				} 
				elseif (file_exists($home."../tpl/".$macrofilename)) 
				{
					$path = $home."../tpl/";
				} 
				else 
				{
					$this->_errmsg("the macro file \"$macrofilename\" doesn't exist");
				}
				$macro = file_get_contents($path.$macrofilename);
				$this->macros_txt .= stripcommentandcr($macro);
				unset($macro);
				$this->_clearposition();
			} 
			elseif (isset($attrs['TEMPLATEFILE']))
			{
				$this->_clearposition();
				if (file_exists("./tpl/".$attrs['TEMPLATEFILE'].'.html'))   {
					$path = './tpl/';
				} elseif (file_exists($home."../tpl/".$attrs['TEMPLATEFILE'].'.html')) {
					$path = $home."../tpl/";
				} else {
					$this->_errmsg("the template file \"{$attrs['TEMPLATEFILE']}\" doesn't exist");
				}
				
				$refresh = false;
	
				if(isset($attrs['BLOCKID']))
				{
					$attrs['BLOCKID'] = (int)$attrs['BLOCKID'];
					if(preg_match("/<BLOCK\b\s+ID=\"{$attrs['BLOCKID']}\"[^>]+REFRESH=\"[^\"]+\"[^>]*>/", file_get_contents($path.$attrs['TEMPLATEFILE'].'.html')))
					{
						$refresh = true;	
					}
				}
				else
				{
					if(preg_match('/<(CONTENT|LOOP)\b\s+[^>]+REFRESH="([^"]+)"[^>]*\/?>/', file_get_contents($path.$attrs['TEMPLATEFILE'].'.html')))
					{
						$refresh = true;
					}
					$attrs['BLOCKID'] = 0;
				}
				
				$contents =
<<<PHP
<?php echo View::getView()->getIncTpl(\$context,"{$attrs['TEMPLATEFILE']}","{$this->cache_rep}","{$path}", "{$attrs['BLOCKID']}"); ?>
PHP;

				if($refresh)
				{
					$contents = 
<<<PHP
'<?php 
\$c = "'.\$c.'";
echo View::getView()->getIncTpl(\$c, "{$attrs['TEMPLATEFILE']}", "{$this->cache_rep}", "{$path}", "{$attrs['BLOCKID']}");
?>'
PHP;
            				$this->arr[$this->ind] =
<<<PHP
<?php 
\$c = base64_encode(serialize(\$context));
echo {$contents};
unset(\$c);
?>
PHP;
				}
				else
				{
					$this->arr[$this->ind] = $contents;
				}
                		unset($contents);
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
						$this->_errmsg("The closing tag ".$this->arr[$this->ind]." is malformed");
					return;
				}	else {
					$methodname = 'parse_'. $this->arr[$this->ind];
					if (method_exists($this, $methodname)) {
						$this->$methodname ();
						#call_user_func(array(&$this,$methodname));
					}	else {
						$this->_errmsg('Unexpected tags "'. htmlentities($this->arr[$this->ind]) . '" on ind ' . $this->ind. ". No method to call");
					}
				}
				break;
			}
			$this->ind += 3;
		}
	}

	protected function parse_LOOP()
	{
		$attrs = $this->arr[$this->ind + 1];

		$this->arr[$this->ind]     = '';
		$this->arr[$this->ind + 1] = '';

		$name        = '';
		$orders      = array ();
		$selectparts = array ();
		$selectparts['having'] = $selectparts['groupby'] = $selectparts['split'] = $selectparts['limit'] = $selectparts['select'] = $selectparts['order'] = $selectparts['where'] = '';
		$dontselect  = array ();
		$wheres      = array ();
		$tables      = array ();
		$arguments   = array ();

		$attrs_arr = $this->_decode_attributs($attrs, 'flat');

		// search the loop name and determin whether the loop is the definition of a SQL loop.
		$issqldef = false;
		foreach ($attrs_arr as $attr)
       		{
			if ($attr['name'] == 'NAME') 
            		{
				if (!empty($name)) $this->_errmsg("name already defined in loop $name", $this->ind);
				$name = $attr['value'];
			} 
			elseif ($attr['name'] == 'TABLE') 
			{
				$issqldef = true;
			} 
			elseif ($attr['name'] == 'REFRESH') 
			{
				if($this->isLoop) $this->_checkforrefreshattribut($attrs);
				else
				{// loop with refresh, the view will manage it like a block
					$contents = 
<<<PHP
'<?php 
\$c = "'.\$c.'";
echo View::getView()->getIncTpl(\$c, "{$this->tpl}", "{$this->cache_rep}", "{$this->base_rep}", 0, "{$name}");
?>'
PHP;
            				$this->arr[$this->ind] =
<<<PHP
<?php 
\$c = base64_encode(serialize(\$context));
echo {$contents};
unset(\$c);
?>
PHP;
					unset($contents);
					$inloop = 0;
					do {
						++$this->ind;
						if('LOOP' == $this->arr[$this->ind])
							++$inloop;
						elseif('/LOOP' == $this->arr[$this->ind+1] && $inloop)
						{
							--$inloop;
							$this->arr[$this->ind+1] = '';
						}
						
						$this->arr[$this->ind] = '';

					} while(isset($this->arr[$this->ind+1]) && $this->arr[$this->ind+1] != '/LOOP');
					++$this->ind;

					if(!isset($this->arr[$this->ind])) $this->errmsg('LOOP '.$name.' not closed', $this->ind);
					
					$this->arr[$this->ind] = '';

					return; // exit, the view will manage the loop like a block
				}
			}
		}

		if ($issqldef) 
        	{ // definition of a SQL loop.
			foreach ($attrs_arr as $attr)	
            		{
				$value = $attr['value'];
				$this->parse_variable($value, 'quote'); // parse the attributs
				switch ($attr['name'])	{
				case 'NAME' :
					break;
				case 'DATABASE' :
					// si la database est dynamique, on rajoute le préfix pour les tables
					$database = $value.'.';
					break;
				case 'WHERE' :
					$wheres[] = '('. $this->replace_conditions($value, 'sql'). ')';
					break;
				case 'TABLE' :
                    			$arr = array();
					if (is_array($value))	{ // multiple table attributs ?
						foreach ($value as $val)
							$arr = array_merge($arr, explode(',', $val));
					} else { // multiple table separated by comma
						$arr = explode(',', $value);
					}
					if (!empty($arr)) 
                    			{
						if(!empty($database))
						{
							$prefix = (false !== strpos($database, '$context') ? $this->prefix : '');
						}
						else
						{
							$prefix = $database = '';
						}
						
						foreach ($arr as $value) 
                        			{
							array_push($tables, $database.$prefix.trim($value));
						}
					}
					break;
				case 'ORDER' :
					$orders[] = $value;
					break;
				case 'LIMIT' :
					if (!empty($selectparts['split']))
						$this->_errmsg("Attribut SPLIT cannot be used with LIMIT", $this->ind);
					if (!empty($selectparts['limit']))
						$this->_errmsg("Attribut LIMIT should occur only once in loop $name", $this->ind);
					$selectparts['limit'] = $value;
					break;
				case 'SPLIT' :
					if (!empty($selectparts['limit']))
						$this->_errmsg("Attribut SPLIT cannot be used with LIMIT", $this->ind);
					if (!empty($selectparts['split']))
						$this->_errmsg("Attribut SPLIT should occur only once in loop $name", $this->ind);
					$selectparts['split'] = $value;
					break;
				case 'GROUPBY' :
					if (!empty($selectparts['groupby']))
						$this->_errmsg("Attribut GROUPY should occur only once in loop $name", $this->ind);
					$selectparts['groupby'] = $value;
					break;
				case 'HAVING' :
					if (!empty($selectparts['having']))
						$this->_errmsg("Attribut HAVING should occur only once in loop $name", $this->ind);
					$selectparts['having'] = $value;
					break;
				case 'SELECT' :
					if (!empty($dontselect))
						$this->_errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name", $this->ind);
					#$select=array_merge($select,preg_split("/\s*,\s*/",$value));
					if (!empty($selectparts['select']))
						$selectparts['select'] .= ",";
					$selectparts['select'] .= $value;
					break;
				case 'DONTSELECT' :
					if (!empty($selectparts['select']))
						$this->_errmsg("Attributs SELECT and DONTSELECT are exclusive in loop $name", $this->ind);
					$dontselect = array_merge($dontselect, explode(",", $value));
					break;
				case 'REQUIRE' :
					break;
				case 'SHOWSQL' :
					$options['showsql'] = true;
					break;
				default :
					$this->_errmsg("unknown attribut \"".$attr['name']."\" in the loop $name", $this->ind);
				}
			} // loop on the attributs
			// end of definition of a SQL loop
		}	else {
			// ok, this is a SQL loop call or a user loop
			// the attributs are put into $arguments.
			foreach ($attrs_arr as $attr) 
            		{
				if ($attr['name'] == 'NAME') continue;
                
				$this->parse_variable($attr['value'], 'quote'); // parse the attributs
				$arguments[strtolower($attr['name'])] = $attr['value'];
			}
		}
		unset($attrs_arr);
		#  echo "enter loop $name:",$this->ind,"<br>\n";

		if (empty($name)) {
			$this->_errmsg("the name of the loop on table(s) \"".join(" ", $tables)."\" is not defined", $this->ind);
		}

		$selectparts['where'] = join(" AND ", $wheres);
		$selectparts['order'] = join(',', $orders);
        	unset($where, $orders);
		// ce sont les tables qui seront demandees dans le select. Les autres tables de $tables ne seront pas demandees
		$tablesinselect = $tables; 
        	// texte pour gerer des champs supplementaires dans le select. Doit commencer par ,
		$extrainselect = ""; 

		if (empty($selectparts['where'])) $selectparts['where'] = '1';
		$this->parse_loop_extra($tables, $tablesinselect, $extrainselect, $selectparts);
		//
		foreach ($selectparts as $k => $v) {
			$selectparts[$k] = $this->prefixTablesInSQL($v);
		}
		$extrainselect = $this->prefixTablesInSQL($extrainselect);

		if (!isset($this->loops[$name]['type']))
		{
			$this->loops[$name]['type'] = 'def'; // toggle the loop as defined, if it is not already
			$issql = false;
		}
		else $issql = ($this->loops[$name]['type'] == 'sql'); // boolean for the SQL loops

		if (!empty($tables)) { // loop SQL
			// check if the loop is not already defined with a different contents.
			if ($issql && $attrs != $this->loops[$name]['attr']){
				$this->_errmsg("loop $name cannot be defined more than once", $this->ind);}

			// get the contents
			$looplevel = 1;
			$iclose = $this->ind;
			do {
				$iclose += 3;
				if ($this->arr[$iclose] == '/LOOP')
					--$looplevel;
				if ($this->arr[$iclose] == 'LOOP')
					++$looplevel;
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
				$this->_errmsg("loop $name cannot be defined more than once with different contents", $this->ind);
			}
			$code = 
<<<PHP
<?php loop_{$name}_{$this->signature}(\$context); ?>
PHP;
		} else {
			//
			if (!$issql) { // the loop is not defined yet, thus it is a user loop
				if(!isset($this->loops[$name]['id']))
					$this->loops[$name]['id'] = 1;
				else
					++$this->loops[$name]['id']; // increment the name count
				$newname = $name. '_'. $this->loops[$name]['id'].($this->blockid ? '_'.$this->blockid : ''); // change the name in order to be unique
				$this->decode_loop_content($name, $contents, $options);
				$this->make_userdefined_loop_code($newname, $contents, $arguments);
				// build the array for the arguments:
				$argumentsstr = '';
				foreach ($arguments as $k => $v) {
					$argumentsstr .= "'$k'=>\"$v\",";
				}
				// clean a little bit, the "" quote
				$argumentsstr = strtr($argumentsstr, array ('"".'=>'', '.""'=>''));
				// make the loop call
				$localtpl = $this->signature.'_';
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
					$this->_errmsg("loop $name cannot be defined more than once");
				$this->loops[$name]['recursive'] = true;
			}
		}
		if ($this->arr[$this->ind] != '/LOOP') {
			echo ":::: $this->ind ".$this->arr[$this->ind]."<br />\n";
			print_r($this->arr);
			$this->_errmsg("internal error in parse_loop. Report the bug");
		}
		$this->arr[$this->ind]     = '';
		$this->arr[$this->ind + 1] = $code;
	}

	protected function decode_loop_content($name, & $content, & $options, $tables = array ())
	{
		$balises = array ('DOFIRST' => 1, 'DOLAST' => 1, 'DO' => 1, 'AFTER' => 0, 'BEFORE' => 0, 'ALTERNATIVE' => 0);
		$content['DOFIRST'] = '';
		$content['DOLAST'] = '';
		$content['DO'] = '';
		$content['AFTER'] = '';
		$content['BEFORE'] = '';
		$content['ALTERNATIVE'] = '';
		$loopind = $this->ind;
		$state = '';
		do {
			$this->ind += 3;
			$this->parse_main();
			if (isset($balises[$this->arr[$this->ind]])) { // opening
				$state = $this->arr[$this->ind];
				if ($content[$state])
					$this->_errmsg("In loop $name, the block $state is defined more than once", $this->ind);
				$istart = $this->ind;
				$this->_clearposition();
			} elseif ($this->arr[$this->ind] == '/'. $state) { // closing
				for ($j = $istart; $j < $this->ind; $j += 3) {
					for ($k = $j; $k < $j +3; $k ++) {
						$content[$state] .= $this->arr[$k];
						$this->arr[$k] = '';
					}
				}

				$this->decode_loop_content_extra($state, $content, $options, $tables);
				$state = '';
				$this->_clearposition();
			}	elseif ($state == "" && $this->arr[$this->ind] == '/LOOP')	{ // closing the loop
				$isendloop = 1;
				break;
			}	elseif ($state)	{ // error
				$this->_errmsg("&lt;$state&gt; not closed in the loop $name", $this->ind);
			}	else { // another error
				$this->_errmsg("unexpected &lt;".$this->arr[$this->ind]."&gt; in the loop $name", $this->ind);
			}
		}	while ($this->ind < $this->countarr);

		if (!$isendloop)
			$this->_errmsg("end of loop $name not found", $this->ind);

		if (!empty($content['DO'])) {
			// check that the remaining content is empty
			for ($j = $loopind; $j < $this->ind; $j ++)
			{
				if (trim($this->arr[$j])) {
					$this->_errmsg("In the loop $name, a part of the content is outside the tag DO", $j);
				}
			}
		} else {
			for ($j = $loopind; $j < $this->ind; $j += 3) {
				for ($k = $j; $k < $j +3; $k ++) {
					$content['DO'] .= $this->arr[$k];
					$this->arr[$k] = '';
				}
			}
			$this->decode_loop_content_extra('DO', $content, $options, $tables);
		}
	}

	protected function make_loop_code($name, $tables, $tablesinselect, $extrainselect, $dontselect, $selectparts, $contents, $options)
	{
		if (!empty($selectparts['where']))
			$selectparts['where'] = 'WHERE '. $selectparts['where'];
		if (!empty($selectparts['order']))
			$selectparts['order'] = 'ORDER BY '. $selectparts['order'];
		if (!empty($selectparts['having']))
			$selectparts['having'] = 'HAVING '. $selectparts['having'];
		if (!empty($selectparts['groupby']))
			$selectparts['groupby'] = 'GROUP BY '. $selectparts['groupby']; // besoin de group by ?

		// special treatment for limit when only one value is given.
		if (!empty($selectparts['split'])) {
			$split = $selectparts['split'];
			$offsetname = 'offset_'. substr(md5($name), 0, 5);

			$preprocesslimit = 
<<<PHP
\$currentoffset=0;
if(isset(\$_REQUEST['{$offsetname}'])) { \$currentoffset=(int)\$_REQUEST['{$offsetname}']; }
PHP;
			$processlimit = 
<<<PHP
\$currenturl=basename(\$_SERVER['SCRIPT_NAME'])."?";
\$cleanquery=preg_replace(array("/(^|&){$offsetname}=\d+/","/(^|&)clearcache=[^&]+/"), "", \$_SERVER['QUERY_STRING']);
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
			$limit = !empty($selectparts['limit']) ? $selectparts['limit'] : '';
		}

		if ($limit)
			$limit = 'LIMIT '. $limit;

		# c est plus complique que ca ici, car parfois la table est prefixee par la DB.
		// reverse the order in order the first is select in the last.
		$tablesinselect = array_reverse(array_unique($tablesinselect));
		$tables = join(',', array_reverse(array_unique($tables)));

		$select = $selectparts['select'];
		if ($dontselect) { // DONTSELECT
			// at the moment, the dontselect should not be prefixed by the table name !
			$selectarr = array ();
			foreach ($tablesinselect as $t) {
				if (!isset($this->tablefields[$t]))
					continue;
				$selectforthistable = array_diff($this->tablefields[$t], $dontselect); // remove dontselect from $tablefields
				if ($selectforthistable) { // prefix with table name
					array_push($selectarr, "$t.".join(",$t.", $selectforthistable));
				}
			}
			$select = join(",", $selectarr);
            		unset($selectarr,$selectforthistable);
		} elseif (!$select && $tablesinselect) { // AUTOMATIQUE
			$select = join(".*,", $tablesinselect).".*";
		}
        	unset($tablesinselect);

		if (!$select)
			$select = '1';

		$select .= $extrainselect;
        	unset($extrainselect);

		if(isset($options['sqlfetchassoc']))
		{
			$sqlfetchassoc = sprintf($options['sqlfetchassoc'], '$result');
			$while = '!$result->EOF';
		}
		else
		{
			$sqlfetchassoc = '$result->fields';
			$while = '$result->MoveNext()';
		}
		//
		// genere le code pour parcourir la loop
		//
		$this->fct_txt .= 
<<<PHP
if(!function_exists('loop_{$name}')) {
	function loop_{$name}(\$context){
		if(!defined('INC_CONNECT')) include 'connect.php';
		global \$db;
PHP;
		if(isset($preprocesslimit))
			$this->fct_txt .=
<<<PHP
		{$preprocesslimit}
PHP;
		$this->fct_txt .=
<<<PHP
		\$query =	lq("SELECT {$select} 
					FROM {$tables} 
					{$selectparts['where']} 
					{$selectparts['groupby']} {$selectparts['having']} {$selectparts['order']} 
					{$limit}");
		
		\$queryCount =	lq("SELECT COUNT(*) AS nbresults 
					FROM {$tables} 
					{$selectparts['where']} 
					{$selectparts['groupby']} {$selectparts['having']}");
PHP;
		if(isset($options['showsql'])) { 
			$this->fct_txt .= 
<<<PHP
		echo htmlentities(\$query);
PHP;
		}
        	
		if($this->sqlrefresh > 0)
		{
			$this->fct_txt.=
<<<PHP
		\$result = \$db->CacheExecute({$this->sqlrefresh}, \$queryCount) or mymysql_error(\$queryCount,'{$name}',__LINE__,__FILE__);
PHP;
		}
		else
		{
			$this->fct_txt.=
<<<PHP
		\$result = \$db->Execute(\$queryCount) or mymysql_error(\$queryCount,'{$name}',__LINE__,__FILE__);
PHP;
		}

		if($selectparts['groupby']) {
			$this->fct_txt .= 
<<<PHP
		\$context['nbresultats'] = \$context['nbresults'] = \$context['nblignes'] = (int)\$result->RecordCount();
PHP;
		} else {
			$this->fct_txt .= 
<<<PHP
		\$context['nbresultats'] = \$context['nbresults'] = \$context['nblignes'] = (int)\$result->fields['nbresults'];
PHP;
		}

		unset($selectparts);

		$this->fct_txt .= 
<<<PHP
		\$result->Close();
PHP;

		if($this->sqlrefresh > 0)
		{
			$this->fct_txt .= 
<<<PHP
		\$result = \$db->CacheExecute({$this->sqlrefresh}, lq(\$query)) or mymysql_error(\$query,'{$name}',__LINE__,__FILE__);
PHP;
		}
		else
		{
			$this->fct_txt .= 
<<<PHP
		\$result = \$db->Execute(lq(\$query)) or mymysql_error(\$query,'{$name}',__LINE__,__FILE__);
PHP;
		}

		if(isset($processlimit))
		{
			$this->fct_txt .=
<<<PHP
		{$processlimit}
PHP;
		}
		$this->fct_txt .=
<<<PHP
		\$context['recordcount'] = (int)\$result->RecordCount();
		\$generalcontext=\$context;
		\$count=0;
		if(\$context['recordcount']) {?>{$contents['BEFORE']}<?php 
			do {
			\$context = array_merge(\$generalcontext,{$sqlfetchassoc});
			\$context['count'] = ++\$count;
PHP;
		// gere le cas ou il y a un premier
		if (!empty($contents['DOFIRST'])) {
			$this->fct_txt .= 
<<<PHP
			if(\$count===1) {?>{$contents['DOFIRST']}<?php continue; }
PHP;
		}
		// gere le cas ou il y a un dernier
		if (!empty($contents['DOLAST'])) {
			$this->fct_txt .= 
<<<PHP
			if(\$count===\$generalcontext['recordcount']) { ?>{$contents['DOLAST']}<?php break; }
PHP;
		}
		$this->fct_txt .= 
<<<PHP
?>{$contents['DO']}<?php } while ({$while}); ?>{$contents['AFTER']}<?php }
PHP;

		if(!empty($contents['ALTERNATIVE'])) {
			$this->fct_txt .= 
<<<PHP
		  else {?>{$contents['ALTERNATIVE']}<?php 
		}
PHP;
		}
		$this->fct_txt .= 
<<<PHP
		\$result->Close();
	}
}
PHP;
	}

	protected function make_userdefined_loop_code($name, $contents)
	{
		// cree la fonction loop
		#echo "infilename=".$this->infilename;
		$localtpl = $this->signature.'_';
		if (!empty($contents['DO'])) {
			$this->fct_txt .= 
<<<PHP
if(!function_exists('code_do_{$localtpl}{$name}')) {
	function code_do_{$localtpl}{$name}(\$context) { 
?>{$contents['DO']}<?php 
	}
}
PHP;
		}
		if (!empty($contents['BEFORE'])) { // genere le code de avant
			$this->fct_txt .= 
<<<PHP
if(!function_exists('code_before_{$localtpl}{$name}')) {
	function code_before_{$localtpl}{$name}(\$context) { 
?>{$contents['BEFORE']}<?php 
	} 
}
PHP;
		}
		if (!empty($contents['AFTER']))	{ // genere le code de apres
			$this->fct_txt .= 
<<<PHP
if(!function_exists('code_after_{$localtpl}{$name}')) {
	function code_after_{$localtpl}{$name}(\$context) { 
?>{$contents['AFTER']}<?php 
	} 
}
PHP;
		}
		if (!empty($contents['ALTERNATIVE']))	{ // genere le code de alternative
			$this->fct_txt .= 
<<<PHP
if(!function_exists('code_alter_{$localtpl}{$name}')) {
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
	protected function parse_FUNC()
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
	protected function parse_MACRO($tag = 'MACRO')
	{
		// decode attributs
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);

		$name = trim($attrs['NAME']);
		if (!$name) {
			$this->_errmsg("$tag without NAME attribut");
		}

		if (!isset ($this->macrocode[$name]))	{
			// search for the macro define
			$searchstr = '/<DEF'.$tag.'\s+NAME\s*=\s*"'.$attrs['NAME'].'"([^>]*)>(.*?)<\/DEF'.$tag.'>/s';

			#if (!preg_match_all($searchstr,$text,$defs,PREG_SET_ORDER)) 
			if (!preg_match_all($searchstr, $this->macros_txt, $defs, PREG_SET_ORDER)) {
				$this->_errmsg("the macro $name is not defined");
			}
			$def = array_pop($defs); // get the last definition of the macro
// 			$code = preg_replace("/(^\n|\n$)/", "", $def[2]); // remove first and last line break
			unset($defs);
			$this->macrocode[$name]['code'] = trim($def[2], "\n");
			$this->macrocode[$name]['attr'] = $def[1];
		} // caching

		if ($tag == 'FUNC') { // we have a function macro
			$defattr = $this->_decode_attributs($this->macrocode[$name]['attr']);
			if (!empty($defattr['REQUIRED'])) {
				$required = explode(',', strtoupper($defattr['REQUIRED']));
				//$optional=preg_split("/\s*,\s*/",strtoupper($defattr['OPTIONAL']));

				// check the validity of the call
				foreach ($required as $arg) 
                		{
                    			$arg = trim($arg);
					if(!$arg) continue;
					if (!isset ($attrs[$arg])) {
						$this->_errmsg("the macro $name required the attribut $arg");
					}
				}
			}
			$macrofunc = strtolower('macrofunc_'. $name.'_'. $this->signature);

			$this->_clearposition();
			// build the call
			unset ($attrs['NAME']);
			$args = '';
			foreach ($attrs as $attr => $val) {
				$this->parse_variable($val, 'quote');
				$args .= '"'. strtolower($attr). '"=>"'. $val. '",';
			}
			$this->arr[$this->ind] .= 
<<<PHP
<?php {$macrofunc}(\$context,array({$args})); ?>
PHP;
			if (!isset($this->funcs[$macrofunc])) 
            		{
				$this->funcs[$macrofunc] = true;
				// build the function 
				$tmpArr = $this->arr;
				$tmpInd = $this->ind;
				$tmpCountArr = $this->countarr;
				$this->arr = null;
				$this->_split_file($this->macrocode[$name]['code']);
				$this->parse_main();
				$this->arr = join('', $this->arr);
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

	protected function parse_BLOCK()
	{
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);

		if(!($attrs['ID'] = (int)$attrs['ID']))
			$this->_errmsg("Incorrect ID for block number ".count($this->blocks)+1);
		if(in_array($attrs['ID'], $this->blocks))
			$this->_errmsg("Duplicate ID for block number ".count($this->blocks)+1);
        	$this->blocks[] = $attrs['ID'];
		$this->_clearposition();
        
        if(isset($attrs['DISPLAY']) && !$attrs['DISPLAY'])
        {
            ++$this->ind;
            do {
                $this->arr[++$this->ind] = '';
            } while(isset($this->arr[$this->ind+1]) && $this->arr[$this->ind+1] != '/BLOCK');
            ++$this->ind;
            
            if(!isset($this->arr[$this->ind])) $this->errmsg('BLOCK '.$attrs['ID'].' not closed', $this->ind);
            
            $this->arr[$this->ind] = '';
            return;
        }
        
		if(isset($attrs['REFRESH']))
		{
			++$this->ind;
			$contents = 
<<<PHP
'<?php 
\$c = "'.\$c.'";
echo View::getView()->getIncTpl(\$c, "{$this->tpl}", "{$this->cache_rep}", "{$this->base_rep}", "{$attrs['ID']}");
unset(\$c);
?>'
PHP;
            		$this->arr[$this->ind] =
<<<PHP
<?php
\$c = base64_encode(serialize(\$context));
echo {$contents};
unset(\$c);
?>
PHP;
			unset($contents);
			do {
				$this->arr[++$this->ind] = '';
			} while(isset($this->arr[$this->ind+1]) && $this->arr[$this->ind+1] != '/BLOCK');
			++$this->ind;
			
			if(!isset($this->arr[$this->ind])) $this->errmsg('BLOCK '.$attrs['ID'].' not closed', $this->ind);
			
			$this->arr[$this->ind] = '';
		}
		else
		{
			$this->ind += 3;
			$this->parse_main();
			$this->arr[$this->ind] = '';
		}
	}

	/**
	 * Traite les conditions avec IF
	 */
	protected function parse_IF()
	{
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
		if (!$attrs['COND'])
			$this->_errmsg("Expecting a COND attribut in the IF tag");
		// do it first else it will break the engine if we compare LS var
		// replace "[#VAR] LIKE /regexp/" and "'something here' LIKE /regexp/"
		// ouch !
		if(false !== stripos($attrs['COND'], ' like '))
		{
			$cond = preg_replace(array( "/(\[#[\w'\[\]\|\$\\\]*?\]) like (\/.*\/)/i", 
					"/'([\w'\[\]\$\\\]*?)' like (\/.*\/)/i"), 
			array('preg_match("\\2is", \\1, $context[\'matches\'])',
				'preg_match("\\2is", "\\1", $context[\'matches\'])'), $attrs['COND']);
		}
		else $cond = $attrs['COND'];
        
		$this->parse_variable($cond, false); // parse the attributs
		$cond = $this->replace_conditions($cond, "php");

		$this->_clearposition();
		$this->arr[$this->ind + 1] = '<?php if ('.$cond.') { ?>';
		$isendif = false;
		do {
			$this->ind += 3;
			$this->parse_main();
			if ($this->arr[$this->ind] == "ELSE") 
            		{
				if (isset($elsefound))
					$this->_errmsg("ELSE found twice in IF condition", $this->ind);
				$elsefound = 1;
				$this->_clearposition();
				$this->arr[$this->ind + 1] = '<?php } else { ?>';
			}	
			elseif ($this->arr[$this->ind] == "ELSEIF") 
			{
				$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
				if (empty($attrs['COND']))
					$this->_errmsg("Expecting a COND attribut in the ELSEIF tag");
				// do it first else it will break the engine if we compare LS var
				// replace "[#VAR] LIKE /regexp/" and "'something here' LIKE /regexp/"
				// RE ouch !
				if(false !== stripos($attrs['COND'], ' like '))
				{
					$cond = preg_replace(array( "/(\[#[\w'\[\]\|\$\\\]*?\]) like (\/[^\/]*\/)/i", 
							"/'([\w'\[\]\$\\\]*?)' like (\/[^\/]*\/)/i"), 
					array('preg_match("\\2i", \\1, $context[\'matches\'])',
						'preg_match("\\2i", "\\1", $context[\'matches\'])'), $attrs['COND']);
				}
				else $cond = $attrs['COND'];
				$this->parse_variable($cond, false); // parse the attributs
				$cond = $this->replace_conditions($cond, "php");
				$this->_clearposition();
				$this->arr[$this->ind + 1] = '<?php } elseif ('.$cond.') { ?>';
			}	
			elseif ($this->arr[$this->ind] == "/IF") 
			{
				$isendif = 1;
			}	
			else $this->_errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in IF condition", $this->ind);
		}	
        	while (!$isendif && $this->ind < $this->countarr);

		if (!$isendif)
			$this->_errmsg("IF not closed", $this->ind);

		$this->_clearposition();
		$this->arr[$this->ind + 1] = '<?php } ?>';
	}

	/**
	 * Traite les conditions avec SWITCH
	 */
	protected function parse_SWITCH()
	{
		$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
		if (empty($attrs['TEST']))
			$this->_errmsg("Expecting a TEST attribut in the SWITCH tag");
		$test = quote_code($attrs['TEST']);
		$this->parse_variable($test, false); // parse the attributs
		$test = $this->replace_conditions($test, 'php');

		$this->_clearposition();
		$toput = $this->ind + 1;

		if (trim($this->arr[$this->ind + 2]))
			$this->_errmsg("Expecting a CASE tag after the SWITCH tag");
		// PHP ne veut aucun espace entre le switch et le premier case !
		$begin = true;
		$endswitch = false;
		do {
			$this->ind += 3;
			$this->parse_main();
			if ($this->arr[$this->ind] == 'DO') {
				$attrs = $this->_decode_attributs($this->arr[$this->ind + 1]);
				if (isset($attrs['CASE'])) {
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
				} elseif(isset($attrs['CASES'])) {
					// multiple case
					$cases = explode(',', $attrs['CASES']);
					$nbCases = count($cases)-1;
					$this->_clearposition();
					foreach($cases as $k=>$case) 
					{
						$case = trim($case);
						$this->parse_variable($case, false); // parse the attributs
						if($begin) {
                            				if('default' == $case)
                                				$this->arr[$toput] = '<?php switch ('.$test.') { default:'.($k==$nbCases ? ' { ?>' : ' ?>');
                            				else
							    $this->arr[$toput] = '<?php switch ('.$test.') { case "'.quote_code($case).'":'.($k==$nbCases ? ' { ?>' : ' ?>');
							$begin = false;
						} 
						else
						{
							if('default' == $case)
								$this->arr[$this->ind + 1] .= '<?php default:'.($k==$nbCases ? ' { ?>' : ' ?>');
							else
								$this->arr[$this->ind + 1] .= '<?php case "'.quote_code($case).'":'.($k==$nbCases ? ' { ?>' : ' ?>');
						}
					}
				} else {
					$this->_errmsg("missing attribute 'CASE(S)' in SWITCH condition", $this->ind);
				}
			} elseif ($this->arr[$this->ind] == "/DO") {
				$this->_clearposition();
				$this->arr[$this->ind + 1] = "<?php break; } ?>\n";
			} elseif ($this->arr[$this->ind] == "/SWITCH") {
				$endswitch = true;
			} else
				$this->_errmsg("incorrect tags \"".$this->arr[$this->ind]."\" in SWITCH condition", $this->ind);
		} while (!$endswitch && $this->ind < $this->countarr);

		if (!$endswitch)
			$this->_errmsg("SWITCH block is not closed", $this->ind);

		$this->_clearposition();
		$this->arr[$this->ind + 1] = '<?php } ?>';
	}
	
	/**
	 * Traite les LET
	 */
	protected function parse_LET()
	{
		if (!preg_match("/\b(VAR|ARRAY)\s*=\s*\"([^\"]*)\"(\s* GLOBAL=\"([^\"]*)\")?/", $this->arr[$this->ind + 1], $result))
			$this->_errmsg("LET have no VAR|ARRAY attribut");
		
		$regexp = 'ARRAY' == $result[1] ? "/^{$this->variable_regexp}(\[\]|(\.[#%{$this->variablechar}]{$this->variable_regexp})*)?$/i" : "/^{$this->variable_regexp}$/i";

		if (!preg_match($regexp, $result[2], $res))
			$this->_errmsg("Variable \"$result[2]\" in LET is not a valid variable", $this->ind);
		
        	$var = strtolower($res[0]);

		if('VAR' == $result[1]) {
			// commenté septembre 2008 par pierre-alain car pas d'utilité trouvée ?!?
			//$this->parse_variable($result[2], false); // parse the attributs
			$this->_clearposition();
			$this->arr[$this->ind + 1] = '<?php ob_start(); ?>';
	
			$this->ind += 3;
			#$this->parse_main2();
			$this->parse_main();
			if ($this->arr[$this->ind] != "/LET")
				$this->_errmsg("&lt;/LET&gt; expected, '".$this->arr[$this->ind]."' found", $this->ind);

			$this->_clearposition();
			$this->arr[$this->ind + 1] = !empty($result[4]) ? '<?php $GLOBALS[\'context\'][\''.$var.'\']=ob_get_clean();?>' : 
                                                                '<?php $context[\''.$var.'\']=ob_get_clean();?>';
		} else {
			$this->_clearposition();
			$this->arr[$this->ind + 1] = '<?php ob_start();$isSerialized=false;?>';
			$this->ind += 3;
			$this->parse_main();
			if ($this->arr[$this->ind] != "/LET")
				$this->_errmsg("&lt;/LET&gt; expected, '".$this->arr[$this->ind]."' found", $this->ind);
			
            		$this->_clearposition();
			// @ is bad, but we need to avoid notice error from PHP if multi dimensional array is not defined
			// be carefull on variable erasing !!
			$this->arr[$this->ind + 1] = '<?php $tmp=($isSerialized?unserialize(ob_get_clean()):ob_get_clean());$isSerialized=false;if(0!==$tmp&&empty($tmp)){$tmp=array();}';
			$this->arr[$this->ind + 1] .= (!empty($result[4]) ? '@$GLOBALS[\'context\']' : '$context');
			$add = $array = false;

			if(!empty($res[1]) && '[]' === $res[1])
			{
				$add = true;
				$var = substr($var, 0, -2);
			}

			if(false !== strpos($var, '.'))
			{
				$vars = explode('.', $var);
				foreach($vars as $v)
				{
					$c = substr($v, 0, 1);
					if('%' === $c || '#' === $c || $this->variablechar === $c)
					{
						$v = '['.strtoupper($v).']';
						$this->parse_variable($v, false);
						$this->arr[$this->ind + 1] .= '['.$v.']';
					}
					else
					{
						$this->arr[$this->ind + 1] .= '[\''.$v.'\']';
					}
				}
				$array = true;
			}
			else
			{
				$this->arr[$this->ind + 1] .= '[\''.$var.'\']';
			}
			
			if($add) $this->arr[$this->ind + 1] .= '[]=';
			elseif($array) $this->arr[$this->ind + 1] .= '=';
			else $this->arr[$this->ind + 1] .= '=(array)';

			$this->arr[$this->ind + 1] .= '$tmp;unset($tmp); ?>';
		}
	}

	/**
	 * Traite les ESCAPE
	 */
	protected function parse_ESCAPE()
	{
		$escapeind = $this->ind;
		$this->_clearposition();
		$this->isphp = true;
		$this->ind += 3;

		$this->parse_main();
		if ($this->arr[$this->ind] != "/ESCAPE")
			$this->_errmsg("&lt;/ESCAPE&gt; expected, ".$this->arr[$this->ind]." found", $this->ind);

		for ($i = $escapeind; $i < $this->ind; $i += 3)	{
			if (trim($this->arr[$i +2]))
				$this->arr[$i +2] = '<?php echo \''.quote_code($this->arr[$i +2]).'\'; ?>';
		}
		$this->_clearposition();
	}

	/**
	 * Accept an array or a string
	 *
	 * @access private
	 */
	protected function _checkforrefreshattribut($mixed)
	{
		if(empty($mixed))
			return;

		if (is_array($mixed))	{
			$attrs = $mixed;
		}	else {
			$attrs = $this->_decode_attributs($mixed);
		}

		if (!isset($attrs['REFRESH']))
			return;

		$refresh = trim($attrs['REFRESH']);
		$timere = "(?:\d+(:\d\d){0,2})"; // time regexp

		if (is_numeric($refresh) && ($this->refresh == 0 || $refresh < $this->refresh))
		{
			$this->refresh = $this->sqlrefresh = (int)$refresh;
		} 
		elseif (!is_numeric($refresh))
		{
			if(!preg_match("/^$timere(?:,$timere)*$/", $refresh))
				$this->_errmsg("Invalid refresh time \"".$refresh."\"");

			$refreshtime = null;
			$refreshtimes = explode(",", $refresh);
			$now = time();
			$date = getdate($now);
			foreach ($refreshtimes as $refreshtime) 
			{
				$refreshtime = explode(":", $refreshtime);
				if(!isset($refreshtime[1])) $refreshtime[1] = 0;
				if(!isset($refreshtime[2])) $refreshtime[2] = 0;
				$time=mktime((int)$refreshtime[0],(int)$refreshtime[1],(int)$refreshtime[2], $date['mon'], $date['mday'], $date['year']);
				if(!isset($refreshtime) || $time < $refreshtime) $refreshtime = $time;
			}
			if(($this->refresh == 0 || $refreshtime < $this->refresh))
			{
				$this->sqlrefresh = $this->refresh = (int)($now - $refreshtime);
			}
		}
	}

	protected function prefixTablesInSQL($sql) 
	{
		$inquote = false;
		$str = '';
		$str2 = '';
		$i=0;
        	$len = strlen($sql);
        	for($i=0;$i<$len;$i++)
		{
			$c = $sql {$i};
			if ($inquote) { // we are in a string
				$str2 = '';
				if ($c == $quotec && !$escaped) {
					$inquote = false;
				} else {
					$escaped = $c == "\\" && !$escaped;
				}
			} elseif ($c == '"' || $c == "'")	{ // quote ?
				$str2 = '';
				$inquote = true;
				$escaped = false;
				$quotec = $c;
			} elseif ($c == "." && $str2) { // table dot ?
				if('lodelmain' !== $str2) 
				{
					$prefixedtable = $this->prefixTableName($str2);
					if ($prefixedtable != $str2)	{
						// we have a table... let's prefix it
						$str = substr($str, 0, -strlen($str2)).$prefixedtable;
					}
					$str2 = '';
				} else $str2 .= $c;
			} elseif(($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ('_' == $c)) {
				$str2 .= $c;
			} else $str2 = '';
			$str .= $c;
		}
		return $str;
	}

	protected function _decode_attributs($text, $options = '')
	{
		// decode attributs
		$arr = explode('"', $text);
		$n = count($arr);
        	$ret = '';
		for ($i = 0; $i < $n; $i += 2) {
			$attr = trim(substr($arr[$i], 0, strpos($arr[$i], "=")));
			if (!$attr)
				continue;
			if ($options == "flat")	{
				$ret[] = array ("name" => $attr, "value" => trim($arr[$i +1]));
			}	else {
				$ret[$attr] = $arr[$i +1];
			}
		}
		return $ret;
	}

	protected function _clearposition()
	{
		$this->arr[$this->ind] = $this->arr[$this->ind + 1] = "";
// 		$this->arr[$this->ind + 2] = preg_replace(array("/^[\t\n]+/", "/[\t\n]+$/"), "", $this->arr[$this->ind + 2]);
	}

	// bug [#4454]
	protected function _checkSplit(&$arr, $nbArr)
	{
		for($i=2;$i<$nbArr;$i+=3) 
        	{
			$nbQuotes = substr_count($arr[$i], '"');
			if(0 === $nbQuotes) 
            		{
				$nbQuotes = substr_count($arr[$i], "'");
				if(0 === $nbQuotes) continue;
			}

			if($nbQuotes % 2) 
            		{
				$pos = strpos($arr[$i+1], '>');
				$arr[$i] .= '>'.substr($arr[$i+1], 0, $pos);
				$arr[$i+1] = substr_replace($arr[$i+1], '', 0, $pos+1);
			}
		}
	}

	/*
	réécrit suite bug signalé par François Lermigeaux sur lodel-devel
	*/
	protected function replace_conditions(&$text, $style)
	{
		$ret = '';
		$tmp = preg_split("/\b(".$this->joinedconditions[$style].")\b/i", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$open = false;
		
		foreach($tmp as $texte) 
		{
			$i=0;
			$nb = 0;
			$len = strlen($texte);
			for($i=0;$i<$len;$i++)
			{
				if($texte{$i} == "'" && ($i>0 && $texte{$i-1} == '\\')) ++$nb;
			}
			if($nb) $open = (true === $open) ? false : true;
			$t = strtolower(trim($texte));
			if(false === $open && isset($this->conditions[$style][$t])) {
				$ret .= $this->conditions[$style][$t];
				continue;
			}
			$ret .= $texte;
		}
		return $ret;
	}

	protected function _split_file($contents, $action = 'insert', $blockId=0, $loop=null)
	{
		if($blockId == 0 && !isset($loop)) 
		{
			$arr = preg_split("/<(\/?(?:".$this->commandsline."))\b([^>]*?)\/?>/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
            		unset($contents);
		}
		else
		{
			$block = $macros = '';
			// get the possible macros
			if(preg_match_all('/<DEF(FUNC|MACRO)\s+NAME\s*=\s*"[^"]+"[^>]*>.*?<\/DEF\\1>/s', $contents, $m))
                		$block .= join('',$m[0]);
			// get the possible macrofiles
			if(preg_match_all("/<USE\s+MACROFILE=\"([^\"]+)\"\s*\/?>/", $contents, $m))
			{
				$sharedir = C::get('sharedir', 'cfg');
				$home = C::get('home', 'cfg');
				foreach($m[1] as $f)
				{
					if (file_exists("./tpl/".$f))	
					{
						$path = './tpl/';
					} 
					elseif (file_exists($sharedir."/macros/".$f)) 
					{
						$path = $sharedir."/macros/";
					} 
					elseif (file_exists($home."../tpl/".$f)) 
					{
						$path = $home."../tpl/";
					} 
					else 
					{
						$this->_errmsg("the macro file \"$f\" doesn't exist");
					}
					$macrofile = file_get_contents($path.$f);
					$macros .= stripcommentandcr($macrofile);
					unset($macrofile);
				}
				$block .= join('',$m[0]);
			}

			unset($m);
			$regexp = $loop ? "/<LOOP\b\s+([^>]*NAME=\"{$loop}\"[^>]*)>(.*?)(<\/LOOP>)/s" : "/<BLOCK\b\s+(ID=\"{$blockId}\"[^>]*)>(.*?)(<\/BLOCK>)/s";

			if(!preg_match($regexp, $macros.$contents, $matches,  PREG_OFFSET_CAPTURE)) {
				$this->_errmsg($loop ? 	'No loop name '.$loop.' found in file '.$this->infilename : 
							'No block number '.$blockId.' found in file '.$this->infilename);
			}
            
            		// repair bad splitting
			$pos = 0;
			if($loop) 
			{ 
				while(substr_count($matches[0][0], '<LOOP') > substr_count($matches[0][0], '</LOOP>'))
				{
					$currPos = $matches[3][1]+7+$pos;
					$pos = strpos($macros.$contents, '</LOOP>', $currPos);
					$matches[0][0] .= substr($macros.$contents, $currPos, $pos-$currPos+7);
				}
			}
			else
			{
				$sub = false;
				while(substr_count($matches[0][0], '<BLOCK') > substr_count($matches[0][0], '</BLOCK>'))
				{
					$sub = true;
					$currPos = $matches[3][1]+8+$pos;
					$pos = strpos($macros.$contents, '</BLOCK>', $currPos);
					$matches[2][0] .= substr($macros.$contents, $currPos, $pos-$currPos+8);
				}
				if($sub) $matches[2][0] = substr($matches[2][0], 0, -8);
			}
			unset($contents, $macros, $sub);

			$block .= $loop ? $matches[0][0] : $matches[2][0];
			$attrs = $this->_decode_attributs($matches[1][0]);
			unset($matches);

			$this->_checkforrefreshattribut($attrs);
			if(isset($attrs['CHARSET'])) $this->charset = $attrs['CHARSET'];
			unset($attrs);
			$arr = preg_split("/<(\/?(?:".$this->commandsline."))\b([^>]*?)\/?>/", $block, -1, PREG_SPLIT_DELIM_CAPTURE);
            		unset($block);
		}

		unset($contents);
        	$nbArr = count($arr);
		// repair bad splitting
		$this->_checkSplit($arr, $nbArr);

		// parse the variables
		$this->parse_variable($arr[0]);
		
		for ($i = 3; $i < $nbArr; $i += 3) {
			$this->parse_variable($arr[$i]); // parse the content
		}
		if (empty($this->arr)) {
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
} // class Parser

function stripcommentandcr(& $text)
{
	return preg_replace(array ("/\r/", "/<!--\[\s*\]-->\s*\n?/s" ,"/<!--\[(?!if IE).*?\]-->\s*\n?/s"), "", $text);
}

function quote_code($text)
{
	return addcslashes($text, "'");
}
?>
