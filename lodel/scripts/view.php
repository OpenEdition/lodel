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
		$this->_cacheOptions['cacheDir'] = getCachePath();
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

		$this->_makeCachedFileName($tpl);
		$included = get_included_files();
		if(!in_array(realpath($home.'Cache/Lite.php'), $included))
			require_once 'Cache/Lite.php';
		if(!in_array(realpath($home.'func.php'), $included))
			require_once 'func.php';	
		$cache = new Cache_Lite($this->_cacheOptions);
        $base = $tpl.($format ? '_'.$format : '');

		// efface le cache si demandé
		if($_REQUEST['clearcache']) {
			clearcache();
		} elseif (!$caching) { 
			clearcache(false);
		} elseif($content = $cache->get($this->_cachedfile, $site)) {
			if(FALSE !== ($content = $this->_iscachevalid($content, $context))
			   && $cache->lastModified() > @filemtime('./tpl/'.$base.'.html')
			   && $cache->lastModified() > strtotime($context['upd'])
			    ) {
				$content = $this->_eval($content, $context, true);
				echo _indent($content);
				flush();
				return;
			}
		}
		
		/* on vide le cache SQL, s'il s'agit de la generation */
		global $db;
		if(isset($db)) $db->CacheFlush();
		
		// pas de fichier dispo dans le cache ou fichier cache à recompiler
		// on le calcule, l'enregistre, l'execute et affiche le résultat
		$content = $this->_calcul_page($context, $tpl);
		if(empty($_POST)) {
			$cache->save($content, $this->_cachedfile, $site);
		}
		$content = $this->_eval($content, $context, true);
		echo _indent($content);
		flush();
		return;	
	}

	/**
	 * Fonction qui affiche le résultat si le cache est valide
	 * 
	 * Alternative à la fonction render.
	 *
	 * @return boolean true ou false si le cache est valide ou non
	 */
	public function renderIfCacheIsValid()
	{
		global $site, $context, $home;

		if ($_REQUEST['clearcache']) {
			clearcache();
			return false;
		}
		$this->_makeCachedFileName();
		$included = get_included_files();
		if(!in_array(realpath($home.'Cache/Lite.php'), $included))
			require_once 'Cache/Lite.php';
				
		$cache = new Cache_Lite($this->_cacheOptions);
		if($content = $cache->get($this->_cachedfile, $site)) {
			// on vérifie le refresh du template
			if(FALSE !== ($content = $this->_iscachevalid($content, $context))) {
				// refresh d'un template inclus en lodelscript ?
				// on tente d'évaluer de nouveau le code pour être sur
				$content = $this->_eval($content, $context, true);
				if(!in_array(realpath($home.'func.php'), $included))
					require_once 'func.php';
				echo _indent($content);
				flush();
				return true;
			}
		}
			
		return false;
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
	* Fonction qui affiche un template inclus en LodelScript
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers répertoire cache si différent de ./CACHE/
	* @param string $base_rep chemin vers répertoire tpl
	* @param bool $escRefresh appel de la fonction par le refresh manager
	* @param int $refreshTime (optionnel) temps de refresh pour le manager
	*/
	public function renderTemplateFile($context, $tpl, $cache_rep='', $base_rep='tpl/', $escRefresh, $refreshTime=0) {
		global $site, $lodeluser, $home;
		$lName = isset($lodeluser['name']) ? $lodeluser['name'] : '';
		$lRights = isset($lodeluser['rights']) ? $lodeluser['rights'] : ''; 
		$cachedTemplateFileName = str_replace('?id=0', '',
					preg_replace(array("/#[^#]*$/", "/[\?&]clearcache=[^&]*/"), "", $_SERVER['REQUEST_URI'])
					). "//". $GLOBALS['lang'] ."//".$tpl. "//". $lName. "//". $lRights;
		if(!in_array(realpath($home.'Cache/Lite.php'), get_included_files()))
			require_once 'Cache/Lite.php';
		$cache = new Cache_Lite($this->_cacheOptions);
		
		if(!($content = $cache->get($cachedTemplateFileName, 'TemplateFile')) || $escRefresh) {
			if(!$base_rep)
				$base_rep = './tpl/';
			if (!file_exists("tpl/$tpl". ".html") && file_exists($home. "../tpl/$tpl". ".html")) {
				$base_rep = $home. '../tpl/';
			}
			$cache->remove("tpl_{$tpl}", 'TemplateFile', true);
			$content = $this->_calcul_page($context, $tpl, $cache_rep, $base_rep, true);
			$cache->save($content, $cachedTemplateFileName, 'TemplateFile');
		}
 		if(!$escRefresh) {
			$content = $this->_eval($content, $context, true);
				
			if($refreshTime > 0) {
				$code = '
<'.'?php 
$cachetime=myfilemtime(getCachedFileName("'.$cachedTemplateFileName.'", "TemplateFile", $GLOBALS[cacheOptions]));
if($cachetime && ($cachetime + '.($refreshTime).') < (time()+10)){ 
	insert_template($context, "'.$tpl.'", "'.$cache_rep.'", "'.$base_rep.'", true, '.($refreshTime).'); 
}else{ ?>';
$code .= $content . '
<'.'?php } ?'.'>';
				$content = $code;
				unset($code);		
			} elseif(FALSE !== strpos($content, '#LODELREFRESH')) {
				$refreshTime = preg_split("/(#LODELREFRESH \d+#)/", $content, -1, PREG_SPLIT_DELIM_CAPTURE);
				$content = '';
				while(list(, $text) = each($refreshTime)) {
					if((FALSE !== strpos($text, '#LODELREFRESH'))) {
						if(($tmpRefresh = intval(substr($text, 14, -1))) > $refresh) {
							$refresh = $tmpRefresh;
						}
					} else {
						$content .= $text;
					}
				}
				if(is_int($refresh)) {
					$code = '
<'.'?php 
$cachetime=myfilemtime(getCachedFileName("'.$cachedTemplateFileName.'", "TemplateFile", $GLOBALS[cacheOptions]));
if($cachetime && ($cachetime + '.($refresh).') < (time()+10)){ 
	insert_template($context, "'.$tpl.'", "'.$cache_rep.'", "'.$base_rep.'", true, '.($refresh).'); 
}else{ ?>';
$code .= $content . '
<'.'?php } ?'.'>';
					$content = $code;
					unset($code);
				}
			}
				
		}
		
		$GLOBALS['TemplateFile'][$tpl] = true;
		return $content;	
	}

	/**
	 * Modifie le nom du fichier à utiliser pour mettre en cache
	 * @param string $tpl (optionnel) nom du template
	 */
	private function _makeCachedFileName($tpl='') {
		global $lodeluser, $site;
		// Calcul du nom du fichier en cache
		$this->_cachedfile = str_replace('?id=0', '',
					preg_replace(array("/#[^#]*$/", "/[\?&]clearcache=[^&]*/"), "", $_SERVER['REQUEST_URI'])
					). "//". $GLOBALS['lang'] ."//". $tpl ."//". $lodeluser['name']. "//". $lodeluser['rights'];
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
	private function _eval($content, &$context, $escapeRefreshManager=false) {
		if(FALSE !== strpos($content, '<?php')) { // on a du PHP, on l'execute
			global $home;
			$included = get_included_files();
			require_once 'loops.php';
			require_once 'textfunc.php';

			if(!checkCacheDir('require_caching')) {
				$this->_error("CACHE directory is not writeable.", __FUNCTION__);
			}

			$tmpFileName = getCachePath("require_caching/".md5(uniqid(mt_rand(0, 999999999999).mt_rand(0, 999999999999), true)));
			if((FALSE || 0) === file_put_contents($tmpFileName, $content, LOCK_EX))
				$this->_error("Error while writing CACHE required file.", __FUNCTION__);

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
			$content = preg_replace("/#LODELREFRESH (\d+)#/", "", $content);
		}
		return $content;
	}

	/**
	* Fonction qui vérifie qu'il ne faut pas rafraichir le template
	*
	* @param string $content contenu à évaluer
	* @param array $context le context
	*/
	private function _iscachevalid($content, $context) {
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
	* @param string $cache_rep chemin vers répertoire cache si différent du répertoire cache d'origine
	* @param string $base_rep chemin vers répertoire tpl
	* @param bool $include appel de la fonction par une inclusion de template (défaut à false)
	*/
	private function _calcul_template(&$context, $base, $cache_rep = '', $base_rep = 'tpl/', $include=false) {

		global $home;
		if(!empty($cache_rep))
			$this->_cacheOptions['cacheDir'] = $cache_rep . $this->_cacheOptions['cacheDir'];
        else
			$this->_cacheOptions['cacheDir'] = getCachePath();
		$group = $include ? 'TemplateFile' : 'tpl';

		if ($_REQUEST['clearcache']) {
			clearcache();
		}
	
		$template_cache = "tpl_$base";
		$tpl = $base_rep. $base. '.html';
		if (!file_exists($tpl)) {
			if (!headers_sent()) {
				header("HTTP/1.0 403 Internal Error");
				header("Status: 403 Internal Error");
				header("Connection: Close");
			}
			$this->_error("<code>The <span style=\"border-bottom : 1px dotted black\">$base</span> template does not exist</code>", __FUNCTION__);
		}

		$cache = new Cache_Lite($this->_cacheOptions);

		if(myfilemtime(getCachePath(getCachedFileName($template_cache, $group, $this->_cacheOptions))) <= myfilemtime($tpl) || !$cache->get($template_cache, $group)) {
			// le tpl caché n'existe pas ou n'est pas à jour comparé au fichier de maquette
			if(!in_array(realpath($home.'lodelparser.php'), get_included_files()))
				require 'lodelparser.php';
			$parser = new LodelParser;
			$contents = $parser->parse($tpl, $include);
			$cache->save($contents, $template_cache, $group);
		}
		// si jamais le path a été modifié on remet par défaut
		$this->_cacheOptions['cacheDir'] = getCachePath();
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

	   checkCacheDir();
        
		$group = $include ? 'TemplateFile' : 'tpl';
		
		if ($format && !preg_match("/\W/", $format)) {
			$base .= "_$format";
		}
		$template_cache = "tpl_$base";
		$i=0;
		$cache = new Cache_Lite($this->_cacheOptions);
		// on va essayer 5 fois de récupérer ou générer le fichier mis en cache
		do {
			$content = $cache->get($template_cache, $group);
			if(is_string($content) && strlen($content)>0)
				break;
			$this->_calcul_template($context, $base, $cache_rep, $base_rep, $include);
			$i++;
		} while (5>$i);

		$format = ''; // en cas de nouvel appel a calcul_page
		if(!$content || is_object($content)) {	
			// si cache_lite est configuré en 'pearErrorMode' => CACHE_LITE_ERROR_RETURN, on récupère l'erreur générée par raiseError()
			include_once 'PEAR/PEAR.php';
			$msg = 'Impossible to get cached TPL. Is the cache directory accessible ? (read/write)';
			if(PEAR::isError($content))
				$msg .= ". Cache_Lite says: ".$content->getMessage();
			$this->_error($msg, __FUNCTION__);
		} else {
			// si jamais le path a été modifié on remet par défaut
			$this->_cacheOptions['cacheDir'] = getCachePath();

			// execute le template php
			if ($GLOBALS['showhtml'] && $GLOBALS['lodeluser']['visitor']) {
				require_once 'showhtml.php';
				// on affiche la source
				$content = $this->_eval($content, $context);
				return show_html($content);
			}
			if (!$context['charset'] || $context['charset'] == 'utf-8') {
				// utf-8 c'est le charset natif, donc on sort directement la chaine.
				$content = $this->_eval($content, $context);
				return $content;
			} else {
				// isolatin est l'autre charset par defaut
				$content = $this->_eval(utf8_decode($content), $context);
				return $content;
			}
			$this->_error('Calculating page failed', __FUNCTION__);
		}
	}

	/**
	 * Fonction gérant les erreurs
	 * Affiche une erreur limité si non loggé
	 * Accessoirement, on nettoie le cache
	 *
	 * @param string $msg message d'erreur
	 * @param string $func nom de la fonction générant l'erreur
	 * @see _eval()
	 */
	private function _error($msg, $func) {
		global $lodeluser, $db, $home, $site;
		// erreur on peut avoir enregistré n'importe quoi dans le cache, on efface les pages.
		clearcache();
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
 * @param int $refreshTime temps après lequel le tpl est à recompiler
 */
function insert_template($context, $tpl, $cache_rep = '', $base_rep='tpl/', $escRefresh=false, $refreshTime=0) {
	$view =& View::getView();
	$content = $view->renderTemplateFile($context, $tpl, $cache_rep, $base_rep, $escRefresh, intval($refreshTime));
	echo _indent($content);
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
			$contenu = "Erreur de requete sur la page http://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$_SERVER['REQUEST_URI']." \n\nQuery : ". $query . "\n\nErreur : ".mysql_error()."\n\nBacktrace :\n\n".print_r(debug_backtrace(), true);
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
		mkdir($dir, 0777 & octdec($GLOBALS['filemask']));
		chmod($dir, 0777 & octdec($GLOBALS['filemask']));
	}

	writefile($file, '<'.'?php if (!$GLOBALS[\'langcache\'][\''. $lang. '\']) $GLOBALS[\'langcache\'][\''. $lang. '\']=array(); $GLOBALS[\'langcache\'][\''. $lang. '\']+=array('. $txt. '); ?'. '>');
}

?>
