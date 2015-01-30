<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fonctions pour le mode debug du lodelscript
 */

function php_debug($php) {
	global $tmpfile;
	error_reporting(0);
	set_error_handler('php_debug_errors'); // errors
	register_shutdown_function('php_shutdown');

	$tmpdir = C::Get('cacheDir','cfg');
	$tmpfile = tempnam($tmpdir,'debug_php_lodel');
	file_put_contents($tmpfile, $php);
	defined('INC_LOOPS') || include 'loops.php';
	defined('INC_TEXTFUNC') || include 'textfunc.php';
	defined('INC_FUNC') || include 'func.php';
	ob_start();
		try {
			$context =& C::getC();
			include($tmpfile);
		}
		catch (Exception $e) {
			// mais bon, ça n'arrive jamais
			echo var_dump($e);
		}
	ob_end_clean();
	unlink($tmpfile);
}

function php_debug_errors($errno, $errstr='', $errfile='', $errline=0) {
	global $php_errors;
	$php_errors[] = array('line'=>$errline, 'message'=>$errstr, 'file'=>$errfile, 'code'=>$errno);
	error_log("ERREUR DEBBUG: ".var_export($php_errors,true));
// 	throw new LodelException($errstr, $errno, $errfile, $errline);
	return true;
}

function php_shutdown() {
	global $php_errors, $texte;
	$php_errors[] = error_get_last();
	if (ob_get_contents() !== False) {
		ob_end_clean();
	}
	echo show_html($texte);
}

function show_debug($text) {
	global $texte;
	$texte = $text."\n";
	
	php_debug($texte);
	return show_html($texte);
}

function show_html($texte) {
	global $tmpfile, $php_errors;
	if (is_file($tmpfile))
		unlink($tmpfile);

	$highlight = '';
	$errors = '';
	if (!empty($php_errors)) {
		$highlight = array();
		foreach ($php_errors as $err) {
			$errors .= "<p><a href='#line_".$err['line']."'>Erreur ligne ".$err['line']."</a>: ".$err['message']."</p>";
			$highlight[]= "#line_".$err['line'];
		}
		$highlight = implode(',', $highlight);
	}

	$includes = link_include_tpl($texte);
	$texte = php_hightlight($texte);
	$texte = number_lines($texte);
	$ret = "<html>".
		"<head>".
			"<style>body {font-family:monospace;} li {white-space: pre-wrap;} $highlight {background-color:lightpink;}</style>".
		"</head><body>";
	if ($errors) $ret .= "<div><h3>Erreurs</h3>$errors</div>";
	if ($includes) $ret .= "<div><h3>Templates inclus</h3>$includes</div>";
	$ret .= ''.$texte.'';
	$ret .= "</body></html>";

	return $ret;
}

function link_include_tpl($texte) {
	preg_match_all('/getIncTpl\(([^)]*),([^)]*),([^)]*),([^)]*),([^)]*)\)/', $texte, $m, PREG_SET_ORDER);
	$includes = array();
	if (!empty($m)) {
		parse_str($_SERVER['QUERY_STRING'], $qs);
		foreach ($m as $match) {
			$tpl = str_replace('"','',$match[2]);
			$qs['showphp'] = $tpl;
			$url = "?".http_build_query($qs);
			$includes[] = "<p><a href='$url'>$tpl</a> dans ".$match[4]."</p>";
		}
	}
	$includes = implode("\n", $includes);
	return $includes;
}

function php_hightlight($texte) {
	$texte = highlight_string($texte, true);
	$texte = str_replace(array('<code>','</code>',"\n"),'',$texte);
	$texte = str_replace('&nbsp;',' ',$texte);
	$texte = preg_replace('/<br \/>(\s)*<\/span>/','\1</span><br />',$texte); // c'est un début mais ça ne suffit pas pour bien couper les lignes sans casser les <span>
	$texte = str_replace('<br />',"\n",$texte);
	return $texte;
}

function number_lines($texte) {
// 	$texte = htmlspecialchars($texte, ENT_QUOTES|ENT_IGNORE, 'UTF-8');
	$text_array = explode("\n",$texte);
	array_walk($text_array, 'number_array');
	$texte = "<ol>".implode("",$text_array)."</ol>";
	return $texte;
}

function number_array(&$value, $key, $prefix='line') {
	$value = "<li id='${prefix}_".($key+1)."'>$value</li>\n";
}