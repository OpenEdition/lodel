<?php
/**
 * Fichier de la classe view.
 *
 * PHP 5
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
 * @author Sophie Malafosse
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 */


/**
 * Classe gérant la partie 'vue' du modèle MVC. Cette classe est un singleton.
 * 
 * Exemple d'utilisation de ce singleton :
 * <code>
 * $view =& getView();
 * $view->render($context,$tpl);
 * </code>
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Sophie Malafosse
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajoutée depuis la version 0.8
 * @see logic.php
 * @see controler.php
 */


require_once 'cachefunc.php';
require_once 'func.php';

class View
{
	/** 
	 * Le nom du fichier de cache
	 * @var string 
	 */
	private $_cachedfile;

	/** 
	 * Les options du cache
	 * @var array
	 */
	private $_cacheOptions;

	/**
	 * Instance du singleton
	 * @var object
	 */
	private static $_instance;


	/** 
	 * Constructeur privé
	 * @access private
	 */
	private function View() {
		global $cacheOptions;
		$this->_cacheOptions = $cacheOptions;
	}

	/**
	 * 'Getter' de ce singleton.
	 * Cette fonction évite l'initialisation inutile de la classe si une instance de celle-ci existe
	 * déjà.
	 *
	 * @return object l'instance de la classe view
	 */
	public static function getView()
	{
		if (!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		return self::$_instance;
	}

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
	public function back($back = 1)
	{
		global $db, $idsession;

		$offset = $back-1;
		usemaindb();
		// selectionne les urls dans la pile grâce à l'idsession et suivant la
		// la profondeur indiquée (offset)
		$result = $db->selectLimit(lq("SELECT id, url FROM #_MTP_urlstack WHERE url!='' AND idsession='$idsession' ORDER BY id DESC"), 1, $offset) or dberror();
		$row = $result->fetchRow();

		$id = $row['id'];	
		$newurl = $row['url'];
		
		if ($id) {
			$db->execute(lq("DELETE FROM #_TP_urlstack WHERE id>='{$id}' AND idsession='{$idsession}'")) or dberror();
			$newurl = 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : ''). $newurl;
		} else {
				$newurl = "index.". ($GLOBALS['extensionscripts'] ? $GLOBALS['extensionscripts'] : 'php');
		}

		if (!headers_sent()) {
			header("Location: ".$newurl);
			exit;
		} else { // si probleme
			echo "<h2>Warnings seem to appear on this page. You may go on anyway by following <a href=\"$go\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
			exit;
		}
	}

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
	public function render(&$context, $tpl, $caching = false)
	{
		global $site, $home;
		// if we are in lodel/edition or lodel/admin or /lodeladmin
		// we need to specify the tpl name
		// because logic is not all time specified in uri
		if(defined('backoffice'))
			$this->_makeCachedFileName($tpl);
		else
			$this->_makeCachedFileName();
		if(!class_exists('Cache_Lite'))
			require 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);

		if($_REQUEST['clearcache']) {
			clearcache(true);
		} elseif($caching && !$context['nocache'] && 
		myfilemtime(getCachedFileName("tpl_{$tpl}", 'tpl', $this->_cacheOptions)) >= myfilemtime('./tpl/'.$tpl.'.html') && 
		$content = $cache->get($this->_cachedfile, $site)) {
			if(FALSE !== ($content = $this->_iscachevalid($content, $context))) {
				$content = $this->_eval($content, $context, true);
				echo $content;
				flush();
				return;
			}
		} else {
			$cache->remove("tpl_{$tpl}", 'tpl', true);
		}
		// pas de fichier dispo dans le cache ou fichier cache à recompiler
		// on le calcule, l'enregistre, l'execute et affiche le résultat
		$content = $this->_calcul_page($context, $tpl);
		if(empty($_POST)) {
			$cache->save($content, $this->_cachedfile, $site);
		}
		$content = $this->_eval($content, $context, true);
		echo $content;
		flush();
		return;	
	}

	/**
	 * Fonction qui affiche une page déjà en cache
	 * 
	 * Alternative à la fonction render.
	 *
	 * @param array $context Le tableau de toutes les variables du contexte
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @return retourne la même chose que la fonction render
	 * @see render()
	 */
	public function renderCached(&$context, $tpl)
	{
		return $this->render($context, $tpl, true);
	}

	/**
	 * Fonction qui affiche une page mise en cache lorsqu'il n'y a pas de données en $_POST
	 * 
	 * @return bool : true si page en cache, false sinon
	 */
	public function renderIfCacheIsValid() 
	{
		global $site, $context;
		// only when not in debug mode and no clearcache asked
		// else render() will do the job to check for tpl mtime
		if($GLOBALS['debugMode'] || $_REQUEST['clearcache'] || $context['nocache']) 
			return false;
		$this->_makeCachedFileName();
		if(!class_exists('Cache_Lite'))
			require 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);
		if($content = $cache->get($this->_cachedfile, $site)) {
			if(FALSE !== ($content = $this->_iscachevalid($content, $context))) {
				$content = $this->_eval($content, $context, true);
				echo $content;
				flush();
				return true;
			}
		}
		return false;
	}

	/**
	* Fonction qui affiche un template inclus en LodelScript
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers répertoire cache si différent de ./CACHE/
	* @param string $base_rep chemin vers répertoire tpl
	* @param bool $escRefresh appel de la fonction par le refresh manager
	* @param string $refreshTime (optionnel) temps de refresh pour le manager
	*/
	public function renderTemplateFile($context, $tpl, $cache_rep='', $base_rep='./tpl/', $escRefresh, $refreshTime=0) 
	{
		global $site, $lodeluser, $home;

		$cachedTemplateFileName = str_replace('?id=0', '',
					preg_replace(array("/#[^#]*$/", "/[\?&]clearcache=[^&]*/"), "", $_SERVER['REQUEST_URI'])
					). "//". $GLOBALS['lang'] . "//". $lodeluser['name']. "//". $lodeluser['rights']."//".$tpl;
		if(!class_exists('Cache_Lite'))
			require 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);

		if(!$base_rep)
			$base_rep = './tpl/';
		if (!file_exists("tpl/$tpl". ".html") && file_exists($home. "../tpl/$tpl". ".html")) {
			$base_rep = $home. '../tpl/';
		}
		$tplFile = $base_rep. $tpl. '.html';

		if(myfilemtime(getCachedFileName("tpl_{$tpl}", 'TemplateFile', $this->_cacheOptions)) <= myfilemtime($tplFile) ||
			$escRefresh || !($content = $cache->get($cachedTemplateFileName, 'TemplateFile'))) {

			$cache->remove("tpl_{$tpl}", 'TemplateFile', true);
			$content = $this->_calcul_page($context, $tpl, $cache_rep, $base_rep, true);
			$cache->save($content, $cachedTemplateFileName, 'TemplateFile');
		}

		if(empty($refreshTime) && preg_match("/#LODELREFRESH ([^#]+)#/", $content, $m)>0) {
			$refreshTime = $m[1];
		}
		$content = $this->_eval($content, $context, true);
		if(empty($refreshTime)) return $content;

		if (!is_numeric($refreshTime)) {
			$refreshtimes = explode(",", $refreshTime);
			foreach ($refreshtimes as $k=>$refreshtim) {
				$refreshtim = explode(":", $refreshtim);
				$tmpcode .= '$refreshtime'.$k.'=mktime('.intval($refreshtim[0]).','.intval($refreshtim[1]).','.intval($refreshtim[2]).',$date[mon],$date[mday],$date[year]);';
				$code.= ($k>0 ? ' || ' : '').'($cachetime && $cachetime<$refreshtime'.$k.' && $refreshtime'.$k.'<$now)';
				
			}
		} else {
			$code = '($cachetime + '.($refreshTime).') < ($now + 10)';
		}
		
		$code = '
<'.'?php 
$cachetime=myfilemtime(getCachedFileName("'.$cachedTemplateFileName.'", "TemplateFile", $GLOBALS[cacheOptions]));
$now = time(); $date = getdate($now);'.(isset($tmpcode) ? $tmpcode : '').'
if($cachetime && ('.$code.') && !$escapeRefreshManager){ 
	insert_template($context, "'.$tpl.'", "'.$cache_rep.'", "'.$base_rep.'", true, "'.($refreshTime).'"); 
}else{ ?>
'. $content . '
<'.'?php } ?'.'>';
		$content = $code;
		unset($code);	
		return $content;	
	}

	/**
	 * Modifie le nom du fichier à utiliser pour mettre en cache 
	 *
	 * Cette fonction calcule le nom du fichier mis en cache uniquement pour la page principale
	 * et non pour les templates inclus dynamiquement
	 * @param string $tpl nom du template appellé, optionnel
	 * @see render()
	 * @see renderIfCacheIsValid()
	 */
	private function _makeCachedFileName($tpl='') 
	{
		global $lodeluser, $site;
		// Calcul du nom du fichier en cache
		$this->_cachedfile = str_replace('?id=0', '',
					preg_replace(array("/#[^#]*$/", "/[\?&]clearcache=[^&]*/"), "", $_SERVER['REQUEST_URI'])
					). "//". $GLOBALS['lang'] ."//". $lodeluser['name']. "//". $lodeluser['rights'].($tpl!='' ? '//'.$tpl : '');
		$GLOBALS['cachedfile'] = getCachedFileName($this->_cachedfile, $site, $this->_cacheOptions);
	}

	/**
	* Fonction qui execute le code PHP (si présent)
	*
	* @param string $content contenu à évaluer
	* @param array $context le context
	* @param bool $escapeRefreshManager utilisé pour virer les balises de refresh si jamais page recalculée à la volée
	* @return le contenu du code évalué
	*/
	private function _eval($content, &$context, $escapeRefreshManager=false) 
	{
		global $home;
		static $called = false;
		if(!$called) {
			if(!function_exists('loop_errors'))
				require 'loops.php';
			if(!function_exists('textebrut'))
				require 'textfunc.php';
			$called = true;
		}
		if(FALSE !== strpos($content, '<?php')) { // on a du PHP, on l'execute
			if(!file_exists("./CACHE/require_caching/") && !mkdir("./CACHE/require_caching/", 0777 & octdec($GLOBALS['filemask']))) {
				$this->_error("CACHE directory is not writeable.", __FUNCTION__, false);
			}
			$tmpFileName = "./CACHE/require_caching/".md5(uniqid(mt_rand(0, 999999999999).mt_rand(0, 999999999999), true));
			if(!file_put_contents($tmpFileName, $content, LOCK_EX))
				$this->_error("Error while writing CACHE required file.", __FUNCTION__, false);
			ob_start();
			$refresh = require $tmpFileName;
			$ret = ob_get_contents();
			ob_end_clean();
			unlink($tmpFileName);
			if('refresh' == $refresh) {
				return $refresh;
			} 
			$content = $ret;
			$ret = null;
		}
		if(TRUE === $escapeRefreshManager && (FALSE !== strpos($content, '#LODELREFRESH'))) {
			$content = preg_replace("/#LODELREFRESH [^#]+#/", "", $content);
		}
		return _indent($content);
	}

	/**
	* Fonction qui vérifie qu'il ne faut pas rafraichir le template
	*
	* @param string $content contenu à évaluer
	* @param array $context le context
	*/
	private function _iscachevalid($content, $context) 
	{
		if(FALSE !== strpos($content, '<?php')) {
			$content = $this->_eval($content, $context);
			if('refresh' == $content) {
				return false;
			}
		}
		return $content;	
	}

	/**
	* Fonction de calcul d'un template
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers répertoire cache si différent de ./CACHE/
	* @param string $base_rep chemin vers répertoire tpl
	* @param bool $include appel de la fonction par une inclusion de template (défaut à false)
	*/
	private function _calcul_template(&$context, $base, $cache_rep = '', $base_rep = './tpl/', $include=false) 
	{
		global $home;
		if(!empty($cache_rep))
			$this->_cacheOptions['cacheDir'] = $cache_rep . $this->_cacheOptions['cacheDir'];

		$group = $include ? 'TemplateFile' : 'tpl';

		$template_cache = "tpl_$base";
		$tpl = $base_rep. $base. '.html';
		if (!file_exists($tpl)) {
			if (!headers_sent()) {
				header("HTTP/1.0 403 Internal Error");
				header("Status: 403 Internal Error");
				header("Connection: Close");
			}
			$this->_error("<code>The <span style=\"border-bottom : 1px dotted black\">$base</span> template does not exist</code>", __FUNCTION__, false);
		}

		$cache = new Cache_Lite($this->_cacheOptions);

		if(myfilemtime(getCachedFileName($template_cache, $group, $this->_cacheOptions)) <= myfilemtime($tpl) || !$cache->get($template_cache, $group)) {
			// le tpl caché n'existe pas ou n'est pas à jour comparé au fichier de maquette
			if(!class_exists('LodelParser'))
				require 'lodelparser.php';
			$parser = new LodelParser;
			$contents = $parser->parse($tpl, $include);
			$cache->save($contents, $template_cache, $group);
		}
		// si jamais le path a été modifié on remet par défaut
		$this->_cacheOptions['cacheDir'] = "./CACHE/";
	}

	/**
	* Fonction de calcul d'une page
	*
	* Cette fonction sort de l'utf-8 par défaut. Sinon c'est de l'iso-latin1 (méthode un peu
	* dictatoriale)
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers répertoire cache si différent de ./CACHE/
	* @param string $base_rep chemin vers répertoire tpl
	* @param bool $include appel de la fonction par une inclusion de template (défaut à false)
	*/
	private function _calcul_page(&$context, $base, $cache_rep = '', $base_rep = 'tpl/', $include=false)
	{
		global $format;

		if(!empty($cache_rep))
			$this->_cacheOptions['cacheDir'] = $cache_rep . $this->_cacheOptions['cacheDir'];	

		$group = $include ? 'TemplateFile' : 'tpl';
		
		if ($format && !preg_match("/\W/", $format)) {
			$base .= "_$format";
		}
		$template_cache = "tpl_$base";
		$i=0;
		$cache = new Cache_Lite($this->_cacheOptions);
		// on va essayer 10 fois (!!!) de récupérer ou générer le fichier mis en cache
		do {
			$content = $cache->get($template_cache, $group);
			if(is_string($content) && strlen($content)>0)
				break;
			$this->_calcul_template($context, $base, $cache_rep, $base_rep, $include);
			$i++;
		} while (10>$i);

		$format = ''; // en cas de nouvel appel a calcul_page
		if(!$content || is_object($content)) {	
			// si cache_lite est configuré en 'pearErrorMode' => CACHE_LITE_ERROR_RETURN, on récupère l'erreur générée par raiseError()
			include_once 'PEAR/PEAR.php';
			$msg = 'Impossible to get cached TPL. Is the cache directory accessible ? (read/write)';
			if(PEAR::isError($content))
				$msg .= ". Cache_Lite says: ".$content->getMessage();
			$this->_error($msg, __FUNCTION__, true);
		} else {
			// si jamais le path a été modifié on remet par défaut
			$this->_cacheOptions['cacheDir'] = "./CACHE/";

			// execute le template php
			if ($GLOBALS['showhtml'] && $GLOBALS['lodeluser']['visitor']) {
				require_once 'showhtml.php';
				// on affiche la source
				$content = $this->_eval($content, $context);
				return show_html($content);
			}

			// utf-8 c'est le charset natif, donc on sort directement la chaine.
			$content = $this->_eval($content, $context);
			return $content;
		}
	}

	/**
	 * Fonction gérant les erreurs
	 * Affiche une erreur limité si non loggé
	 * Accessoirement, on nettoie le cache
	 *
	 * @param string $msg message d'erreur
	 * @param string $func nom de la fonction générant l'erreur
	 * @param bool $clearcache a-t-on besoin de nettoyer le cache ?
	 * @see _eval()
	 */
	private function _error($msg, $func, $clearcache) 
	{
		global $lodeluser, $db, $home, $site;
		// erreur on peut avoir enregistré n'importe quoi dans le cache, on efface les pages.
		if($clearcache)
			clearcache(true);
		$error = "Error: " . $msg . "\n";
		$err = $error."\nBacktrace:\n function '".$func."' in file '".__FILE__."' (requested page ' ".$_SERVER['REQUEST_URI']." ' by ip address ' ".$_SERVER["REMOTE_ADDR"]." ')\n";
		if($db->errorno())
			$err .= "SQL Errorno ".$db->errorno().": ".$db->errormsg()."\n";
		if($lodeluser['rights'] > LEVEL_VISITOR || $GLOBALS['debugMode']) {
			echo nl2br($err."\n\n\n");
		} else {
			echo "<code>An error has occured during the calcul of this page. We are sorry and we are going to check the problem</code>";
		}
		
		if($GLOBALS['contactbug']) {
			$sujet = "[BUG] LODEL - ".$GLOBALS['version']." - ".$GLOBALS['currentdb']." / ".$site;
			@mail($GLOBALS['contactbug'], $sujet, $err);
		}
		
		die();
	}

} // end class


/**
 *  Insertion d'un template dans le context
 *
 * @param array $context le context
 * @param string $tpl le nom du fichier template
 * @param string $cache_rep chemin vers répertoire cache si différent de ./CACHE/
 * @param string $base_rep chemin vers répertoire tpl
 * @param bool $escRefresh appel de la fonction par le refresh manager (défaut à false)
 * @param string $refreshTime temps après lequel le tpl est à recompiler
 */
function insert_template($context, $tpl, $cache_rep = '', $base_rep='tpl/', $escRefresh=false, $refreshTime=0) {
	$view =& View::getView();
	$content = $view->renderTemplateFile($context, $tpl, $cache_rep, $base_rep, $escRefresh, $refreshTime);
	echo $content;
}


// REMARQUE : Les fonctions suivantes n'ont rien à faire ici il me semble
/**
 * Fonction qui permet d'envoyer les erreurs lors du calcul des templates
 *
 * @param string $query la requete SQL
 * @param string $tablename le nom de la table SQL (par défaut vide)
 */
function mymysql_error($query, $tablename = '')
{
	if ($GLOBALS['lodeluser']['editor'] || $GLOBALS['debugMode']) {
		if ($tablename) {
			$tablename = "LOOP: $tablename ";
		}
		die("</body>".$tablename."QUERY: ". htmlentities($query)."<br /><br />".mysql_error());
	}	else {
		if ($GLOBALS['contactbug']) {
			$sujet = "[BUG] LODEL - ".$GLOBALS['version']." - ".$GLOBALS['currentdb'];
			$contenu = "Erreur de requete sur la page http://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$_SERVER['REQUEST_URI']." (' ".$_SERVER["REMOTE_ADDR"]." ')\n\nQuery : ". $query . "\n\nErreur : ".mysql_error()."\n\nBacktrace :\n\n".print_r(debug_backtrace(), true);
			@mail($GLOBALS['contactbug'], $sujet, $contenu);
		}
		die("<code>An error has occured during the calcul of this page. We are sorry and we are going to check the problem</code>");
	}
}

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
		
		// si la clé commence par optgroup, on génère une balise <optgroup>
		// Cf. la fonction makeSelectEdition($value), in commonselect.php
		if(substr($k, 0, 8) == "OPTGROUP") { echo "<optgroup label=\"$v\">";}
		elseif (substr($k, 0, 11) == "ENDOPTGROUP") { echo '</optgroup>';}
		//sinon on génère une balise <option>
		else { echo '<option value="'. $k. '" '. $s. '>'. $v. '</option>'; }
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

		$txt.= "'". $tag. "'=>'". str_replace("'", "\'",(getlodeltextcontents($name, $group, $lang))). "',";
	}
	$dir = dirname($file);
	if (!is_dir($dir)) {
		@mkdir($dir, 0777 & octdec($GLOBALS['filemask']));
		@chmod($dir, 0777 & octdec($GLOBALS['filemask']));
	}

	writefile($file, '<'.'?php if (!$GLOBALS[\'langcache\'][\''. $lang. '\']) $GLOBALS[\'langcache\'][\''. $lang. '\']=array(); $GLOBALS[\'langcache\'][\''. $lang. '\']+=array('. $txt. '); ?'. '>');
}

?>