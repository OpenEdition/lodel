<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe view.
 */


/**
 * Classe gérant la partie 'vue' du modèle MVC. Cette classe est un singleton.
 * 
 * Exemple d'utilisation de ce singleton :
 * <code>
 * $view = getView();
 * $view->render($tpl);
 * OU
 * View::getView->render($tpl);
 * </code>
 *
 * @see logic.php
 * @see controler.php
 */

class View
{

	/** 
	 * Les options du cache
	 * @var array
	 */
	private $_cacheOptions;

	/**
	 * $this->_eval() a-t-elle été déjà appellée ?
	 * @var bool
	 */
	private $_evalCalled;
	
	/**
	 * Instance du singleton
	 * @var object
	 */
	private static $_instance;
    
	/**
	* site courant
	* @var string
	*/
	private $_site;

	/**
	* lien relatif vers le répertoire lodel/scripts/
	* @var string
	*/
	private $_home;
	
    	/**
	* timestamp correspondant à l'appel de la vue
	* @var int
	*/
	static public $time;

    	/**
	* micro time correspondant à l'appel de la vue
	* @var int
	*/
	static public $microtime;

	/**
	* instance of Cache_Lite
	* @var object
	*/
	private $_cache;

	/**
	* debugMode to show lodelparser result
	* @var bool
	*/
	private $_showphp;

	/**
	 * page which will be displayed
	 * cached for trigger postview
	 * @var string
	 */
	static public $page;
    
	/**
	* no cache
	* used to indicates that we must NOT use cache at all (read/save)
	* @var bool
	*/
	static public $nocache;
    
	/**
	 * no indent
	 * used to disable autoindentation of template result
	 * @var bool
	 */
	static public $noindent;
	/** 
	 * Constructeur privé
	 * @access private
	 */
	private function __construct() 
	{
		$this->_evalCalled = false;
		$this->_cache = null;
		$this->_site = C::get('site', 'cfg');
		$this->_home = C::get('home', 'cfg');
		$this->_showphp = (bool)C::get('showphp') && C::get('admin', 'lodeluser');
		self::$time = time();
		self::$microtime = microtime(true);
		self::$nocache = (bool)(C::get('nocache') || $this->_showphp || 0 < C::get('debugMode', 'cfg') || C::get('isPost', 'cfg') || C::get('translationmode', 'lodeluser')=="interface" || (!defined('backoffice') && !defined('backoffice-lodeladmin') && C::get('translationmode', 'lodeluser')=="site"));
		self::$noindent = (bool) C::get('nocache') ? true : false;
	}

	/**
	 * Surcharge de la fonction clone()
	 * @see getView()
	 */ 
	public function __clone()
	{ 
		return self::getView();
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
		if (!isset(self::$_instance)) 
		{
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
		global $db;

		$idsession = C::get('idsession', 'lodeluser');
		$offset = $back-1;
		usemaindb();
		// selectionne les urls dans la pile grâce à l'idsession et suivant la
		// la profondeur indiquée (offset)
		$result = $db->selectLimit(lq("
              SELECT id, url 
                FROM #_MTP_urlstack 
                WHERE url!='' AND idsession='{$idsession}' AND site='".$this->_site."' 
                ORDER BY id DESC"), 1, $offset) 
            		or trigger_error('SQL ERROR :<br />'.$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$row = $result->fetchRow();
        	$result->Close();
		$id = $row['id'];	
		$newurl = $row['url'];
		
		if ($id) {
			$db->execute(lq("
                 DELETE FROM #_TP_urlstack 
                    WHERE id>='{$id}' AND idsession='{$idsession}' AND site='".$this->_site."'")) 
                		or trigger_error('SQL ERROR :<br />'.$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			$newurl = 'http'.(C::get('https', 'cfg') ? 's' : '').'://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : ''). $newurl;
		} else {
			$ext = defined('backoffice') || defined('backoffice-lodeladmin') ? 'php' : C::get('extensionscripts');
			$newurl = "index.". $ext;
		}

		if (!headers_sent()) {
			header("Location: ".$newurl);
			exit;
		} else { // si probleme
			echo "<h2>Warnings seem to appear on this page. You may go on anyway by following <a href=\"{$newurl}\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
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
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @param boolean $caching Si on doit utiliser le cache ou non (par défaut à false)
	 *
	 */
	public function render($tpl, $caching = false, $gzip = true)
	{
		C::set('view.tpl', $tpl);
		$format = C::get('format');
		C::set('view.format', $format);

		C::trigger('preview');
		$tpl = C::get('view.tpl');
		$format = C::get('view.format');
		$base = $tpl.($format ? '_'.$format : '');
	
		$context =& C::getC();

		// we try to reach the cache only if asked and no POST datas
		if($caching && !self::$nocache) {
			if(!isset($this->_cache)) {
				$this->_cache = getCacheObject();
			}
			$contents = $this->_cache->get(getCacheIdFromId($this->page_cache_id()));

			if($contents) {
				$template = $this->unzip_cached_template($contents);
				if(0 === $template['refresh'] || $template['expire'] > self::$time) {
					self::$page = $this->_eval($template['contents'], C::getC());
					$this->_print();
					return true;
				}
				unset($template);
			}
			unset($contents);
		}

		/* Si c'est de la re-génération, on vide le cache SQL */
		if(!$caching || self::$nocache){
			global $db;
			if(isset($db)) $db->CacheFlush();
		}

		// empty cache, let's calculate and display it
		if ($this->_showphp)
			self::$page = $this->_calcul_page($context, $tpl); // no eval for debug
		else
			self::$page = $this->_eval($this->_calcul_page($context, $tpl, $caching), $context);
		
		$this->_print($gzip);

		return true;
	}

	/**
	 * Fonction qui affiche une page déjà en cache
	 * 
	 * Alternative à la fonction render.
	 *
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @return retourne la même chose que la fonction render
	 * @see render()
	 */
	public function renderCached($tpl)
	{
		return $this->render($tpl, true);
	}

	/**
	* Print the page 
	* This function tries to compress the page with gz_handler
	* It also call the trigger postview
	*/
	private function _print( $gzip = true )
	{
		C::trigger('postview');
		// try to gzip the page
		$encoding = false;
		if( $gzip && extension_loaded('zlib') && !ini_get('zlib.output_compression'))
		{
			if(function_exists('ob_gzhandler') && @ob_start('ob_gzhandler'))
				$encoding = 'gzhandler';
			elseif(!headers_sent())
			{
				if(strpos(@$_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) 
				{
					$encoding = 'x-gzip';
				} 
				elseif(strpos(@$_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false) 
				{
					$encoding = 'gzip';
				}
			}
		}

		if ((C::get('showhtml') && C::get('visitor', 'lodeluser')) || $this->_showphp) {
			// on affiche la source
			include_once('debug_func.php');
			if ($this->_showphp) {
					self::$page = show_debug(self::$page);
			} else
					self::$page = show_html(self::$page);
		}

		switch($encoding)
		{
			case 'gzhandler':
				@ob_implicit_flush(0);
				echo self::$page;
				@ob_end_flush();
				break;
			case 'gzip':
			case 'x-gzip':
				header('Content-Encoding: ' . $encoding);
				echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
				$size = strlen(self::$page);
				$content = gzcompress(self::$page, 6);
				$content = substr($content, 0, $size);
				echo $content;
				flush();
				unset($content);
			default:
				echo self::$page;
				flush();
				break;
		}

		self::$page = null; // memory
	}

	/**
	* Fonction essayant de retourner le cache si celui-ci est valide
	* utilisée uniquement côté site
	*/
	public function renderIfCacheIsValid()
	{
		if(self::$nocache) return false;
		C::trigger('preview');

		if(!isset($this->_cache))
		{
			$this->_cache = getCacheObject();
		}
		$contents = $this->_cache->get(getCacheIdFromId($this->page_cache_id()));
		if(!$contents) return false;

		$template = $this->unzip_cached_template($contents);
		if(0 === $template['refresh'] || $template['expire'] > self::$time) {
			self::$page = $this->_eval($template['contents'], C::getC());
			$this->_print();
			return true;
		}
		return false;
	}


	/**
	 * Fonction qui affiche une template inclus
	 * 
	 * @param array $context le contexte passé par référence
	 * @param string $tpl Le nom du template utilisé pour l'affichage
	 * @param string $cache_rep répertoire cache (optionnel)
	 * @param string $base_rep lien vers le répertoire contenant le tpl
	 * @param int $blockId numéro du block (optionnel)
	 * @param string $loopName nom de la loop (optionnel)
	 * @return string le template html
	 */
	public function getIncTpl(&$context, $tpl, $cache_rep='', $base_rep='tpl/', $blockId=0, $loopName=null)
	{
		$sum = null;
		if(is_string($context))
		{
			$sum = crc32($context);
			$context = unserialize(base64_decode($context));
		}elseif( $blockId > 0 ){
			$sum = crc32(base64_encode(serialize($context)));
 		}


		if(!$base_rep) $base_rep = './tpl/';
		if (!file_exists("tpl/{$tpl}.html") && file_exists($this->_home. "../tpl/{$tpl}.html")) {
			$base_rep = $this->_home. '../tpl/';
		}
	
		$tplFile = $base_rep. $tpl. '.html';
		$blockId = (int) $blockId;
		$idcontext = (int) (isset($context['id']) ? $context['id'] : 0);
		$recalcul = true;

		if(!self::$nocache)
		{
			if($blockId > 0)
			{
				$template_cache = $tpl.'_'.$idcontext.'_'.C::get('sitelang') ."_". 
					C::get('name', 'lodeluser'). "_". C::get('rights', 'lodeluser').'_'.
					$blockId.$sum.'_'.C::get('qs', 'cfg');
			}
			elseif(isset($loopName))
			{
				$template_cache = $tpl.'_'.$idcontext.'_'.C::get('sitelang') ."_". 
					C::get('name', 'lodeluser'). "_". C::get('rights', 'lodeluser').'_'.
					$loopName.$sum.'_'.C::get('qs', 'cfg');
			}
			else $template_cache = $tpl.'_'.$idcontext.'_'.C::get('sitelang') ."_". 
					C::get('name', 'lodeluser'). "_". C::get('rights', 'lodeluser').'_'.
					C::get('qs', 'cfg');

			if(!isset($this->_cache))
			{
				$this->_cache = getCacheObject();
			}

			$template_cache = getCacheIdFromId($template_cache, $this->_cache);

			$recalcul = false;

			if($contents = $this->_cache->get($template_cache)) {
				$template = $this->unzip_cached_template($contents);
				$contents = $template['contents'];
				if(0 !== $template['refresh'] && self::$time > $template['expire'])
					$recalcul = true;
			} else {
				$recalcul = true;
			}
		}

		if($recalcul)
		{
			$template = $this->_calcul_template($tpl, $cache_rep, $base_rep, $blockId, $loopName);
			if ($this->_showphp)
				$template['contents'] = $template['contents']; // no eval for debug
			else
				$template['contents'] = $this->_eval($template['contents'], $context);
			if(!self::$noindent && (!isset($template['noindent']) || !$template['noindent'])) $template['contents'] = _indent($template['contents']);

			if(!self::$nocache && ($template['refresh'] === 0 || $template['refresh'] > 60)) {
				$this->cache_template($template, $template_cache);
			}
			$contents = $template['contents'];
			unset($template);
		}
		return $contents;
	}

	/**
	 * Enregistre le résultat du parser dans le cache
	 * 
	 * @param array $template résultat du parser
	 * @param string $cacheId nom du cache
	 * @return void
	 */
	private function cache_template($template, $cacheId) {
		if(!isset($this->_cache))
			$this->_cache = getCacheObject();

		$template_contents = $template['contents'];
		unset($template['contents']);

		$template['refresh'] = 0 !== $template['refresh'] ? $template['refresh'] : 0;
        $template['expire'] = self::$time + $template['refresh'];
		$template_options = serialize($template);

		$this->_cache->set($cacheId, $template_options."\n".$template_contents);
	}

	/**
	 * retourne toutes les options du template mis en cache
	 * 
	 * @param string $contents le contenu du template en cache
	 * @return array $template résultat du parser
	 */
	private function unzip_cached_template($contents) {
		$pos = strpos($contents, "\n");
		$template_options = substr($contents, 0, $pos);
		if (is_numeric($template_options)) // compatibilité: avant juste l'option refresh était enregistrée
			$template['refresh'] = $template_options;
		else
			$template = unserialize($template_options);

		$template['contents'] = substr($contents, $pos+1);
		return $template;
	}

	/**
	 * Calcul de l'identifiant du cache de la page principale
	 * 
	 * @return string l'id du cache de ce template, utilisant: la langue, l'utilisateur et les paramètres
	 */
	private function page_cache_id() {
		return $_SERVER['PHP_SELF'].'_'.C::get('sitelang') ."_". 
			C::get('name', 'lodeluser'). "_". C::get('rights', 'lodeluser').'_'. // ne pas risquer qu'un admin enregistre du cache visiteur
			C::get('qs', 'cfg');
	}

	/**
	* Fonction qui execute le code PHP (si présent)
    	* Evaluate the contents only if PHP code inside
	*
	* @param string $contents contenu à évaluer
	* @param array $context le context
	* @return le contenu du code évalué
	*/
	private function _eval($contents, &$context) 
	{
		if(false !== strpos($contents, '<?php')) 
		{ // PHP to be evaluated
			if(!$this->_evalCalled) 
			{
				// needed funcs
				defined('INC_LOOPS') || include 'loops.php';
				defined('INC_TEXTFUNC') || include 'textfunc.php';
				defined('INC_FUNC') || include 'func.php';
				$this->_evalCalled = true;
			}
			
			ob_start();
			//var_dump($contents);
			eval("?>" . $contents);
			$contents = ob_get_clean();
		}
		return $contents;
	}

	/**
	* Fonction de calcul d'un template
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param string $cache_rep chemin vers répertoire cache si différent du cache
	* @param string $base_rep chemin vers répertoire tpl
	* @param bool $include appel de la fonction par une inclusion de template (defaut a false)
	* @param int $blockId (optionnel) numero du block
	* @param string $loopName (optionnel) nom de la loop
	*/
	private function _calcul_template($base, $cache_rep = '', $base_rep = './tpl/', $blockId=0, $loopName=null) 
	{
		$tplFormat = $tpl = $base_rep . DIRECTORY_SEPARATOR . $base. '.html';
                $tplPage = $base_rep . DIRECTORY_SEPARATOR . C::get('view.tpl') . '.html';
                
		if (!file_exists($tpl)) 
		{
			$base_rep = C::get('view.base_rep.'.$base);
			$plugin_base_rep = C::get('sharedir', 'cfg').'/plugins/custom/';
			if(!$base_rep || !file_exists($tpl = $plugin_base_rep.$base_rep.'/tpl/'.$base.'.html'))
			{       
                                /**
                                 * Is it a 400 or 404 error
                                 */
				if(!file_exists($tplPage))
                                {
                                    cache_delete($this->page_cache_id());
                                    $this->_error("<code>The <span style=\"border-bottom : 1px dotted black\">$base</span> template does not exist</code>", __FUNCTION__, E_USER_LODEL_NOT_FOUND);
                                }else{
                                    cache_delete($this->page_cache_id());
                                    $this->_error("<code>The <span style=\"border-bottom : 1px dotted black\">$base</span> format does not exist</code>", __FUNCTION__, E_USER_LODEL_BAD_REQUEST);
                                }
				
			}
		}

		if(!isset($this->_cache))
		{
			$this->_cache = getCacheObject();
		}

		$contents = false;

		if(!self::$nocache)
		{

			if($blockId>0)
			{
				$template_cache = getCacheIdFromId("tpl_{$base}_block_{$blockId}", $this->_cache);
			}
			elseif(isset($loopName))
			{
				$template_cache = getCacheIdFromId("tpl_{$base}_loop_{$loopName}", $this->_cache);
			}
			else
			{
				$template_cache = getCacheIdFromId("tpl_{$base}", $this->_cache);
			}

			$contents = $this->_cache->get($template_cache);
		}

		if($contents && 1 > C::get('debugMode', 'cfg')) {
			$template = $this->unzip_cached_template($contents);
		} else {
			// le tpl cache n'existe pas ou n'est pas a jour compare au fichier de maquette
			class_exists('LodelParser') || include 'lodelparser.php';
			$template = LodelParser::getParser()->parse($tpl, $blockId, $cache_rep, $loopName);
			if(!self::$nocache)
				$this->cache_template($template, $template_cache);
		}
		unset($contents);

		return $template;
	}

	/**
	* Fonction de calcul d'une page
	*
	* Cette fonction sort de l'utf-8
	*
	* @param array $context le context
	* @param string $base le nom du fichier template
	* @param boolean $caching Si on doit utiliser le cache ou non (par défaut à true)
	*/
	private function _calcul_page(&$context, $base, $caching = true)
	{
		$format = C::get('format');

		if ($format && !preg_match("/\W/", $format)) 
		{
			$base .= "_{$format}";
		}
		C::set('format', null); // en cas de nouvel appel a calcul_page

		$template_cache = "tpl_{$base}";

		$template = $this->_calcul_template($base);

		if ($this->_showphp) {
			if (c::get('showphp') != 'oui') {
				$base = basename(c::get('showphp'));
				$template = $this->_calcul_template($base);
			}
			$template['contents'] = $template['contents']; // no eval for debug
		} else
			$template['contents'] = $this->_eval($template['contents'], $context);
		
		if(!self::$noindent && (!isset($template['noindent']) || !$template['noindent'])) $template['contents'] = _indent($template['contents']);

		if($caching && !self::$nocache && (0 === $template['refresh'] || $template['refresh'] > 60)) { // if refresh < 60s we don't save
			$this->cache_template($template, getCacheIdFromId($this->page_cache_id()));
		}

		return $template['contents'];
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
	private function _error($msg, $func, $error_type, $clearcache = false) 
	{
		// we are maybe buffering, so clear it
		if(!C::get('redactor', 'lodeluser') || 1 > C::get('debugMode', 'cfg'))
			while(@ob_end_clean());
		
		global $db;
		// erreur on peut avoir enregistré n'importe quoi dans le cache, on efface les pages si demandé
		if($clearcache)
		{
			clearcache();
		}
		$err = "ERROR:\nFunction '".$func."' in file '".__FILE__."' ";
		$err .= "(requested page ' ".$_SERVER['REQUEST_URI']." ' by ip address ' ".$_SERVER["REMOTE_ADDR"]." ') :\n";
		$err .= $msg."\n";
		if(is_object($db) && $db->ErrorMsg())
			$err .= "SQL ERROR ".$db->ErrorMsg()."\n";
                if($error_type === E_USER_LODEL_BAD_REQUEST)
                {
                    LodelException::exception_handler(new LodelException($msg, E_USER_LODEL_BAD_REQUEST, "view.php", 0, 400));
                    //trigger_error($err, E_USER_LODEL_BAD_REQUEST);
                }elseif($error_type === E_USER_LODEL_NOT_FOUND) 
                {
                    LodelException::exception_handler(new LodelException($msg, E_USER_LODEL_NOT_FOUND, "view.php", 0, 404));
                    //trigger_error($err, E_USER_LODEL_NOT_FOUND);
                }else
                {
                    trigger_error($err, E_USER_ERROR);
                }
	}

	/**
	* Fonction qui permet d'envoyer les erreurs lors du calcul des templates
	*
	* @param string $query la requete SQL
	* @param string $tablename le nom de la table SQL (par défaut vide)
	* @param string $line ligne contenant l'erreur
	* @param string $file fichier contenant l'erreur (par défaut dans le cache require_caching/)
	*/
	public function myMysqlError($query, $tablename = '', $line, $file)
	{
		global $db;
		// we are maybe buffering, so clear it
		if(!C::get('redactor', 'lodeluser') || 1 > C::get('debugMode', 'cfg'))
			while(@ob_end_clean());
		// on efface le cache on a pu enregistre tout et n'importe quoi
		clearcache();

		if ($tablename) {
			$tablename = "<br/>LOOP: $tablename;<br/>";
		}
		trigger_error("</body><br/>Internal error in file {$file} on line {$line};<br/> ".$tablename."<br/>QUERY: ". htmlentities($query)."<br /><br />MYSQL ERROR: ".$db->ErrorMsg(), E_USER_ERROR);
	}
} // end class


/**
 * Insertion d'un template dans le context
 * wrapper de la fonction View::getIncTpl
 *
 * @param array $context le context
 * @param string $tpl le nom du fichier template
 * @param string $cache_rep chemin vers repertoire cache si different du cache
 * @param string $base_rep chemin vers repertoire tpl
 * @param int $blockId (optionnel) numero d'un block de template
 * @param string $loopName (optionnel) nom de la loop
 */
function insert_template(&$context, $tpl, $cache_rep = '', $base_rep='tpl/', $blockId=0, $loopName=null) 
{
	echo View::getView()->getIncTpl($context, $tpl, $cache_rep, $base_rep, $blockId, $loopName);
}

/**
 * Fonction qui permet d'envoyer les erreurs lors du calcul des templates
 * Wrapper de la fonction View::mymysql_error
 *
 * @param string $query la requete SQL
 * @param string $tablename le nom de la table SQL (par defaut vide)
 * @param int $line ligne de l'erreur
 * @param string $file nom du fichier declenchant l'erreur
 */
function mymysql_error($query, $tablename = '', $line, $file)
{
	View::getView()->myMysqlError($query, $tablename, $line, $file);
}

// REMARQUE : Les fonctions suivantes n'ont rien a faire ici il me semble
/**
 * Appelle la bonne fonction makeSelect suivant la logique appelee
 * Cette fonction est utilisee dans le calcul de la page
 *
 * @param array $context Le tableau de toutes les variables du contexte
 * @param string $varname Le nom de la variable du select
 * @param string $lo Le nom de la logique appelee
 * @param string $edittype Le type d'edition (par defaut vide)
 */
function makeSelect(&$context, $varname, $lo, $edittype = '')
{
	getLogic($lo)->makeSelect($context, $varname, $edittype);
}


/**
 * Affiche le tag HTML <option> pour les select normaux et multiples
 * Cette fonction positionne l'attribut selected="selected" das tags options d'un select suivant
 * les elements qui sont effectivements selectionnes.
 *
 * @param array $arr la liste des options
 * @param array $selected la liste des elements selectionnes.
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
		
		// si la cle commence par optgroup, on genere une balise <optgroup>
		// Cf. la fonction makeSelectEdition($value), in commonselect.php
		if(substr($k, 0, 8) == "OPTGROUP") { echo "<optgroup label=\"$v\">";}
		elseif (substr($k, 0, 11) == "ENDOPTGROUP") { echo '</optgroup>';}
		//sinon on genere une balise <option>
		else { echo '<option value="'. $k. '" '. $s. '>'. $v. '</option>'; }
	}
}

/**
 * Genere le fichier de CACHE d'une page dans une autre langue.
 *
 * @param string $lang la langue dans laquelle on veut generer le cache
 * @param string $file le fichier de cache
 * @param array $tags la liste des tags a internationaliser.
 *
 */
function generateLangCache($lang, $file, $tags)
{
	$txt = array();
	foreach($tags as $tag) {
		$dotpos = strpos($tag, '.');
		$group  = substr($tag, 0, $dotpos);
		$name   = substr($tag, $dotpos+1);

		$txt[$tag] = getlodeltextcontents($name, $group, $lang);
	}

	$cache = getCacheObject();
	$cache->set(getCacheIdFromId($file), $txt);
	return $txt;
}

/**
 * Indentation de code HTML, XML
 *
 * @param string $source le code a indenter
 * @param string $indenter les caracteres a utiliser pour l'indentation. Par defaut deux espaces.
 * @return le code indente proprement
 */
function _indent($source, $indenter = '  ')
{

	if(!preg_match("/<[^>]+>/", $source)) { // no tags
		return _indent_xhtml($source,$indenter);
	}

	$tab = '';
	// inline tags
	$inline = array('a'=>true, 'strong'=>true, 'b'=>true, 'em'=>true, 'i'=>true, 'abbr'=>true, 'acronym'=>true, 'code'=>true, 'cite'=>true, 
			'span'=>true, 'sub'=>true, 'sup'=>true, 'u'=>true, 's'=>true, 'br'=>true, 'textarea'=>true, 'img'=>true, 'del'=>true,
			'A'=>true, 'STRONG'=>true, 'B'=>true, 'EM'=>true, 'I'=>true, 'ABBR'=>true, 'ACRONYM'=>true, 'CODE'=>true, 'CITE'=>true, 
			'SPAN'=>true, 'SUB'=>true, 'SUP'=>true, 'U'=>true, 'S'=>true, 'BR'=>true, 'PRE'=>true, 'TEXTAREA'=>true, 'IMG'=>true);
	$noIndent = array('textarea'=>true, 'pre'=>true, 'TEXTAREA'=>true, 'script'=>true, 'SCRIPT'=>true, 'noscript'=>true, 'NOSCRIPT'=>true, 'style'=>true, 'STYLE'=>true);
	$nbIndent = strlen($indenter);
	$isInline = false;
	$escape = false;

	// c'est parti on indente
	$arr = preg_split("/(?:[\n\t\r]*)((<(?:[\/!]?))(?:\w+:)?([\w-]+)(?:\s[^>]*?)?(\/?>))(?:[\n\t\r]*)/", 
			trim($source), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$source = '';
	if(!isset($arr[1])) {
		if(trim($arr[0]))
			$source .= $arr[0];
		return $source;
	}
	
	$i = -1;
	$closingTag = false;
	while(isset($arr[++$i]))
	{
		$current =& $arr[$i];
		if(!isset($current{0})) continue;
		if($current{0} === '<')
		{
			$prefix = isset($arr[$i+1]) ? $arr[$i+1] : '';
			$tag = isset($arr[$i+2]) ? $arr[$i+2] : '';
			$suffix = isset($arr[$i+3]) ? $arr[$i+3] : '';
		}
		else
		{
			$prefix = $tag = $suffix = '';
		}

		if(isset($current{1}) && '<?' === $current{0}.$current{1})
		{ // php/xml code
			$closingTag = false;
			$source .= "\n".$current."\n";
		}
		elseif('<!' === $prefix)
		{ // <!DOCTYPE or <!--
			$closingTag = false;
			$source .= $current;
			if($tag && ('DOCTYPE' === $tag || '--' === $tag))
				$i += 3;
		}
		elseif('/>' === $suffix)
		{ // <\w+/>
			$closingTag = false;
			if($tag && isset($inline[$tag]))
			{
				$source .= $current;
				$isInline = true;
			}
			else
			{
				$source .= $isInline ? $current : "\n".$tab.$current."\n".$tab;
				$closingTag = $isInline ? false : true;
			}
			$i += 3;
		}
		elseif('</' === $prefix)
		{ // </\w+>
			if($tag)
			{
				if(isset($noIndent[$tag])) $escape = false;
				if(isset($inline[$tag]))
				{
					$source .= $current;
					$i += 3;
					continue;
				}
				$isInline = isset($inline[$arr[$i-2]]) || isset($inline[$arr[$i-3]]);
			}
			$tab = substr($tab, $nbIndent);
			$source .= $isInline || !$closingTag ? $current : "\n".$tab.$current;
			$closingTag = true;
			$isInline = false;
			$i += 3;
		}
		elseif('<' === $prefix)
		{ // <\w+
			$closingTag = false;
			if($tag)
			{
				if(isset($noIndent[$tag])) $escape = true;
				if(isset($inline[$tag]))
				{
					$isInline = true;
					$source .= $current;
					$i += 3;
					continue;
				}
			}

			$source .= $isInline ? $current : "\n".$tab.$current;
			$tab .= "$indenter";
			$isInline = false;
			$i += 3;
		}
		else
		{ // contents
			$closingTag = false;
			$escape || $arr[$i] = str_replace("\n", '', $arr[$i]);// remove any \n, only if we are NOT in <textarea>
			$source .= $current;
		}
	}

	// we trim and remove empty lines
	return trim(preg_replace("/^\s*\n/m", '', $source));
}

// Function to seperate multiple tags one line (used by function _indent_xhtml)
function fix_newlines_for_clean_html($fixthistext)
{
	$fixthistext_array = explode("\n", $fixthistext);
	$fixedtext_array = array();
	foreach ($fixthistext_array as $unfixedtextkey => $unfixedtextvalue) {

 		// Exception for fckeditor
		if (preg_match("/fck_.+editor/", $unfixedtextvalue))
		{
			$fixedtext_array[$unfixedtextkey] = $unfixedtextvalue;
		}
		
		//Makes sure empty lines are ignores
		else if (!preg_match("/^(\s*)$/", $unfixedtextvalue))
		{
			$fixedtextvalue = preg_replace("/>(\s|\t)*</U", ">\n<", $unfixedtextvalue);
			$fixedtext_array[$unfixedtextkey] = $fixedtextvalue;
		}
		
	}

	if (!empty($fixedtext_array)) {
		return implode("\n", $fixedtext_array);
	} else {
		return false;
	}
}

/**
 * Indentation de code XHTML
 *
 * @param string $uncleanhtml le code a indenter
 * @param string $indent les caracteres a utiliser pour l'indentation. Par defaut deux espaces.
 * @return le code indente proprement
 */
function _indent_xhtml ($uncleanhtml, $indent = "  ")
{
	//Set wanted indentation
	//$indent = "    ";
	//Uses previous function to seperate tags
	if ($fixed_uncleanhtml = fix_newlines_for_clean_html($uncleanhtml)) {

		$uncleanhtml_array = explode("\n", $fixed_uncleanhtml);
	
		//Sets no indentation
		$indentlevel = 0;
		foreach ($uncleanhtml_array as $uncleanhtml_key => $currentuncleanhtml)
		{
			//Removes all indentation
			$currentuncleanhtml = preg_replace("/\t+/", "", $currentuncleanhtml);
			$currentuncleanhtml = preg_replace("/^\s+/", "", $currentuncleanhtml);
		
			$replaceindent = "";
		
			//Sets the indentation from current indentlevel
			for ($o = 0; $o < $indentlevel; $o++)
			{
				$replaceindent .= $indent;
			}
		
			//If self-closing tag, simply apply indent
			if (preg_match("/<(.+)\/>/", $currentuncleanhtml))
			{ 
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If doctype declaration, simply apply indent
			else if (preg_match("/<!(.*)>/", $currentuncleanhtml))
			{ 
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If opening AND closing tag on same line, simply apply indent
			else if (preg_match("/<[^\/](.*)>/", $currentuncleanhtml) && preg_match("/<\/(.*)>/", $currentuncleanhtml))
			{ 
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If closing HTML tag or closing JavaScript clams, decrease indentation and then apply the new level
			else if (preg_match("/<\/(.*)>/", $currentuncleanhtml) || preg_match("/^(\s|\t)*\}{1}(\s|\t)*$/", $currentuncleanhtml))
			{
				$indentlevel--;
				$replaceindent = "";
				for ($o = 0; $o < $indentlevel; $o++)
				{
					$replaceindent .= $indent;
				}
			
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			//If opening HTML tag AND not a stand-alone tag, or opening JavaScript clams, increase indentation and then apply new level
			else if ((preg_match("/<[^\/](.*)>/", $currentuncleanhtml) && !preg_match("/<(link|meta|base|br|img|hr)(.*)>/", $currentuncleanhtml)) || preg_match("/^(\s|\t)*\{{1}(\s|\t)*$/", $currentuncleanhtml))
			{
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			
				$indentlevel++;
				$replaceindent = "";
				for ($o = 0; $o < $indentlevel; $o++)
				{
					$replaceindent .= $indent;
				}
			}
			else
			//Else, only apply indentation
			{$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;}
		}
		//Return single string seperated by newline

		return implode("\n", $cleanhtml_array);	
	} else {
			return '';
		}
}
