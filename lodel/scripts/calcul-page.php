<?php
/**
 * Fichier utilitaire pour gérer le calcul des pages templates
 *
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

#require_once 'func.php';

/**
 * Fonction de calcul d'une page
 *
 * Cette fonction sort de l'utf-8 par défaut. Sinon c'est de l'iso-latin1 (méthode un peu
 * dictatoriale)
 *
 * NOTA : le $ret ne sert a rien, mais s'il n'est pas la, la version de php n'aime pas (4.3.x):
 * bug eratique.
 *
 */
function calcul_page(&$context, $base, $cache_rep = '', $base_rep = 'tpl/')
{
	global $home, $format;
	if ($_REQUEST['clearcache'])	{
		include_once 'cachefunc.php';
		clearcache();
		$_REQUEST['clearcache'] = false; // to avoid to erase the CACHE again
	}

	if ($format && !preg_match("/\W/", $format)) {
		$base .= "_$format";
	}
	$format = ''; // en cas de nouvel appel a calcul_page

	$template_cache = $cache_rep. "CACHE/tpl_$base.php";
	$base = $base_rep. $base. '.html';
	if (!file_exists($base)) {
		die("<code><strong>Error!</strong>  The <span style=\"border-bottom : 1px dotted black\">$base</span> template does not exist.</code>");
	}

	$template_time = myfilemtime($template_cache);
	if (($template_time <= myfilemtime($base)))	{
		if ($GLOBALS['lodeluser']['admin']) {
			$context['templatesrecompiles'] .= "$base | ";
		}
		if (!defined("TOINCLUDE")) {
			define("TOINCLUDE", $home);
		}

		require_once 'lodelparser.php';
		$parser = new LodelParser;
		$parser->parse($base, $template_cache);

	}
	
	#include 'connect.php';
	// execute le template php
	include_once 'textfunc.php';
		
	if ($GLOBALS['showhtml'] && $GLOBALS['lodeluser']['visitor'])	{
		ob_start();
		require $template_cache;
		$content = ob_get_contents();
		ob_end_clean();
		require_once 'showhtml.php';
		echo _indent(show_html($content));
		return;
	}
	include_once 'loops.php';

	if ($context['charset'] == 'utf-8')	{ // utf-8 c'est le charset natif, donc on sort directement la chaine.
		#$start = microtime();
		ob_start();
		if(is_readable($template_cache))
			require $template_cache;
		$contents = ob_get_contents();
		ob_end_clean();
		echo _indent($contents);
		#$end = microtime();
		#echo "temps : ". ($end - $start);
	}
	else
	{
		// isolatin est l'autre charset par defaut
		ob_start();
		if(is_readable($template_cache))
			require $template_cache;
		$contents = ob_get_contents();
		ob_end_clean();
		echo _indent(utf8_decode($contents));
	}
		
}

/**
 *  Insertion d'un template dans le context
 *
 * @param array $context le context
 * @param string $filename le nom du fichier template
 */
function insert_template($context, $filename)
{
	if (file_exists("tpl/$filename". ".html")) {
		calcul_page($context, $filename);
	}	elseif (file_exists($GLOBALS['home']. "../tpl/$filename". ".html")) {
		calcul_page($context, $filename, "", $GLOBALS['home']. '../tpl/');
	} else {
		die("<code><strong>Error!</strong> Unable to find the file <span style=\"border-bottom : 1px dotted black\">$filename.html</span></code>");
	}
}

/**
 * Fonction qui permet d'envoyer les erreurs lors du calcul des templates
 *
 * @param string $query la requete SQL
 * @param string $tablename le nom de la table SQL (par défaut vide)
 */
function mymysql_error($query, $tablename = '')
{
	if ($GLOBALS['lodeluser']['editor']) {
		if ($tablename) {
			$tablename = "LOOP: $tablename ";
		}
		die("</body>".$tablename."QUERY: ". htmlentities($query)."<br /><br />".mysql_error());
	}	else {
		if ($GLOBALS['contactbug']) {
			@mail($GLOBALS['contactbug'], "[BUG] LODEL - $GLOBALS[version] - $GLOBALS[database]", "Erreur de requete sur la page ".$_SERVER['REQUEST_URI']."<br />". htmlentities($query). "<br /><br />".mysql_error());
		}
		die("<code><strong>Error!</strong> An error has occured during the calcul of this page. We are sorry and we are going to check the problem</code>");
	}
}
?>
