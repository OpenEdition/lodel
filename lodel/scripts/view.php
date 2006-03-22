<?php
/**
 * Fichier de la classe view.
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */
include_once 'func.php';

// {{{ class
/**
 * Classe gérant la partie 'vue' du modèle MVC. Cette classe est un singleton.
 * 
 * Exemple d'utilisation de ce singleton :
 * <code>
 * $view = &getView();
 * $view->render($context,$tpl);
 * </code>
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajoutée depuis la version 0.8
 * @see logic.php
 * @see controler.php
 */

class View
{
	// {{{ properties
	/**#@+
	 * @access private
	 */
	/** 
	 * Le nom du fichier de cache
	 * @var string 
	 */
	 var $_cachedfile;

	/** 
	 * L'extension du fichier de cache
	 * @var string 
	 */
   var $_extcachedfile;

	/** 
	 * Un booléen qui indique si le cache est valide ou non
	 * @var boolean 
	 */
   var $_iscachevalid;
		
	/**#@-*/
	// }}}
	
	// {{{ private methods
	/** 
	 * Constructeur privé
	 * @access private
	 */
	function View() {}
	// }}}

	/**
	 * 'Getter' de ce singleton.
	 * Cette fonction évite l'initialisation inutile de la classe si une instance de celle-ci existe
	 * déjà.
	 *
	 * @return object l'instance de la classe view
	 */
	function &getView()
	{
		static $instance;
		if(!$instance)
			$instance = new View;
		return $instance;
	}

	// {{{ public methods
	
	/**
	 * Fonction qui redirige l'utilisateur vers la page précédente
	 * 
	 * <p>Cette fonction selectionne l'URL précédente dans la pile des URL (table urlstack). Ceci est
	 * fait suivant le niveau de profondeur choisi (par défaut 1).<br />
	 * Si une URL est trouvée, toutes les autres URLS de l'historique (pour la session en cours) sont
	 * supprimées et une redirection est faite sur cette page.<br />
	 * Si aucune URL n'est trouvée alors la redirection est faite sur l'accueil (index.php).</p>
	 * @param integer $back le nombre de retour en arrière qu'il faut faire. Par défaut est égal à 1.
	 */
	function back($back = 1)
	{
		#echo "back=$back";
		global $db, $idsession;
		#     $url=preg_replace("/[\?&]clearcache=[^&]*/","",$_SERVER['REQUEST_URI']);
		#     if (get_magic_quotes_gpc()) $url=stripslashes($url);
		#     $myurl=$db->qstr($url);
		$offset = $back-1;
		usemaindb();
		// selectionne les urls dans la pile grâce à l'idsession et suivant la
		// la profondeur indiquée (offset)
		$result = $db->selectLimit(lq("SELECT id, url FROM #_MTP_urlstack WHERE url!='' AND idsession='$idsession' ORDER BY id DESC"), 1, $offset) or dberror();
		$row = $result->fetchRow();
		#print_r($row);
		$id = $row['id'];	$newurl = $row['url'];
		
		if ($id) {
			$db->execute(lq("DELETE FROM #_TP_urlstack WHERE id>='$id' AND idsession='$idsession'")) or dberror();
			$newurl = 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : ''). $newurl;
		} else {
				$newurl = "index.". ($GLOBALS['extensionscripts'] ? $GLOBALS['extensionscripts'] : 'php');
		}
		#echo "newurl=$newurl";exit;
		if (!headers_sent()) {
			header("location: ".$newurl);
			exit;
		} else { // si probleme
			echo "<h2>Warnings seem to appear on this page. You may go on anyway by following <a href=\"$go\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
			exit;
		}
	//usecurrentdb();
	}//end of back function

	/**
	 * Fonction Render
	 *
	 * Affiche une page particulière en utilisant le contexte (tableau $context) et le nom du template
	 * passé en argument.
	 * Cette fonction gère la mise en cache et le recalcule si nécessaire. C'est-à-dire si celui-ci
	 * n'existe pas, si celui-ci n'est plus à jour, n'est plus valide,...
	 * 
	 * @param array $context Le tableau de toutes les variables du contexte
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @param boolean $cache Si on doit utiliser le cache ou non (par défaut à false)
	 *
	 */
	function render(&$context, $tpl, $cache = false)
	{

		global $home;
		if (!$cache) { // calcul la page si le cache n'existe pas

			include_once 'calcul-page.php';
			calcul_page($context, $tpl);
			return;
		}
		// si le fichier de mise-a-jour est plus recent
		if (!isset($this->_iscachevalid)) {
			$this->_iscachevalid();
		}
		if (!$this->_iscachevalid) {
			include_once 'calcul-page.php';
			$this->_calculateCacheAndOutput($context, $tpl);
			// the cache is valid... do we have a php file ?
		} else {
			if ($this->_extcachedfile == 'php') {
				$ret = include $this->_cachedfile. '.php';
				// c'est etrange ici, un require ne marche pas. Ca provoque des plantages lourds !
				if ($ret == 'refresh') { // does php say we must refresh ?
					include_once 'calcul-page.php';
					$this->_calculateCacheAndOutput($context, $tpl);
				}
			} else { // no, we have a proper html, let read it.
				// sinon affiche le cache.
				readfile($this->_cachedfile. '.html');
			}
		}
	}

	/**
	 * Fonction qui affiche le résultat si le cache est valide
	 * 
	 * Alternative à la fonction render.
	 *
	 * @return boolean true ou false si le cache est valide ou non
	 * @see render
	 */
	function renderIfCacheIsValid()
	{
		if (!$this->_iscachevalid()) {
			return false;
		}
		if ($this->_extcachedfile == 'php') {
			$ret = include $this->_cachedfile. '.php';
			if ($ret == 'refresh') return false; // does php say we must refresh ?
		} else { // no, we have a proper html, let read it.
			// sinon affiche le cache.
			readfile($this->_cachedfile. '.html');
		}
		return true;
	}

	/**
	 * Fonction qui affiche une page déjà en cache
	 * 
	 * Alternative à la fonction render.
	 *
	 * @param array $context Le tableau de toutes les variables du contexte
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @return retourne la même chose que la fonction render
	 * @see render
	 */
	function renderCached(&$context, $tpl)
	{
		return $this->render($context, $tpl, true);
	}
	// }}}


	// {{{ private methods
	/**
	 * Vérifie si le cache est valide
	 *
	 * This function check if the cache is valid at the first level.
	 * if the file is php, we'll know the validity only once the file
	 * has been executed. This function should therefore not be used
	 * (it is private)
	 *
	 * @access private
	 * @return boolean true si le cache est valide, false sinon.
	 *
	 */
	function _iscachevalid()
	{
		global $lodeluser;
		//if ($GLOBALS['right']['visitor']) {
		//  $this->_iscachevalid=false;
		//  return false;
		//}
		include_once 'func.php';
		if (defined('SITEROOT')) {
			$maj = myfilemtime(SITEROOT. 'CACHE/maj');
		} else {
			$maj = myfilemtime('CACHE/maj');
		}

		// Calcul du nom du fichier en cache
		$this->_cachedfile = substr(rawurlencode(
			str_replace('?id=0', '',
				preg_replace(array("/#[^#]*$/", "/[\?&]clearcache=[^&]*/"), "",
				$_SERVER['REQUEST_URI'])). "//". $lodeluser['name']. "//". $lodeluser['rights']), 0, 255);
		//chaque fichier de cache est stocké dans un répertoire
		$cachedir = substr(md5($this->_cachedfile), 0, 1);
		if ($GLOBALS['context']['charset'] != 'utf-8') {
			$cachedir = "il1.$cachedir";
		}

		if (!file_exists("CACHE/$cachedir")) {
			mkdir("CACHE/$cachedir", 0777 & octdec($GLOBALS['filemask']));
		}
		$this->_cachedfile = "CACHE/$cachedir/". $this->_cachedfile;
		$this->_extcachedfile = file_exists($this->_cachedfile. '.php') ? 'php' : 'html';

		// The variable $cachedfile must exist and be visible in the global scope
		// The compiled file need it to know if it must produce cacheable output or direct output.
		// An object should be created in order to avoid the global scope pollution.
		$GLOBALS['cachedfile'] = $this->_cachedfile;
		if ($_REQUEST['clearcache']) {
			return false; //force la recompilation du cache
		}
		if ($maj < myfilemtime($this->_cachedfile. '.'. $this->_extcachedfile)) {
			$this->_iscachevalid = true;
			return true;
		}
		$this->_iscachevalid = false;
		return false;
	}

	/**
	 * Calcul le cache et l'affiche
	 *
	 * Cette fonction privée est utilisée par toutes la fonction render.
	 * Elle calcule le résultat PHP à mettre en cache en coordonnant les données (tableau $context)
	 * et le template (fichier représenté par le nom $tpl).
	 * Cette fonction utilise les fonctions PHP de bufferisation de sortie (empêche l'envoi de données
	 * durant le calcul du cache).
	 *
	 * @param array $context Le tableau de toutes les variables du contexte
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @access private
	 *
	 */
	function _calculateCacheAndOutput($context, $tpl)
	{
		global $home;
		ob_start();
		$this->_extcachedfile = calcul_page($context, $tpl);
		$content = ob_get_contents();
		ob_end_clean();

		$this->_extcachedfile = substr($content, 0, 5)=='<'. '?php' ? 'php' : 'html';
		if ($this->_extcachedfile == 'html') {
			echo $content; // send right now the html. Do other thing later. 
			flush(); // That may save few milliseconde !
			@unlink($this->_cachedfile. '.php'); // remove if the php file exists because it has the precedence above.
		}
		// write the file in the cache
		$f = fopen($this->_cachedfile. '.'. $this->_extcachedfile, 'w');
		fputs($f, $content);
		fclose($f);
		@chmod($dir, 0666 & octdec($GLOBALS['filemask']));
		if ($this->_extcachedfile == 'php') { 
			$dontcheckrefresh = 1;
			include $this->_cachedfile. '.php'; 
		}
	}
	// end of public methods}}}
}
// end of class}}}


// REMARQUE : Les fonctions suivantes n'ont rien à faire ici il me semble

/**
 * Appelle la bonne fonction makeSelect suivant la logique appelée
 * Cette fonction est utilisée dans le calcul de la page
 *
 * @param array $context Le tableau de toutes les variables du contexte
 * @param string $varname Le nom de la variable du select
 * @param string $lo Le nom de la logique appelée
 * @param string $edittype Le type d'édition (par défaut vide)
 */
function makeSelect(&$context, $varname, $lo, $edittype = '')
{
	$logic = &getLogic($lo);
	$logic->makeSelect($context, $varname, $edittype);
}


/**
 * Affiche le tag HTML <option> pour les select normaux et multiples
 * Cette fonction positionne l'attribut selected="selected" das tags options d'un select suivant
 * les éléments qui sont effectivements sélectionnés.
 *
 * @param array $arr la liste des options
 * @param array $selected la liste des éléments sélectionnés.
 */
function renderOptions($arr, $selected)
{
	$multipleselect = is_array($selected);
	foreach ($arr as $k=>$v) {
		if ($multipleselect) {
			$s = in_array($k, $selected) ? "selected=\"selected\"" : "";
		} else {
			$s = $k == $selected ? "selected=\"selected\"" : "";
		}
		$k = htmlentities($k);
		echo '<option value="'. htmlentities($k). '" '. $s. '>'. $v. "</option>\n";
	}
}

/**
 * Genère le fichier de CACHE d'une page dans une autre langue.
 *
 * @param string $lang la langue dans laquelle on veut générer le cache
 * @param string $file le fichier de cache
 * @param array $tags la liste des tags à internationaliser.
 *
 */
function generateLangCache($lang, $file, $tags)
{
	foreach($tags as $tag) {
		$dotpos = strpos($tag, '.');
		$group  = substr($tag, 0, $dotpos);
		$name   = substr($tag, $dotpos+1);

		$txt.= "'". $tag. "'=>'". addslashes(getlodeltextcontents($name, $group, $lang)). "',";
	}
	$dir = dirname($file);
	if (!is_dir($dir)) {
		@mkdir($dir, 0777 & octdec($GLOBALS['filemask']));
		@chmod($dir, 0777 & octdec($GLOBALS['filemask']));
	}
	#include_once 'func.php'; //ce require n'est pas forcément utile mais on sait jamais
	writefile($file, '<'.'?php if (!$GLOBALS[\'langcache\'][\''. $lang. '\']) $GLOBALS[\'langcache\'][\''. $lang. '\']=array(); $GLOBALS[\'langcache\'][\''. $lang. '\']+=array('. $txt. '); ?'. '>');
}
?>