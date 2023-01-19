<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe context.
 */

/**
 * Classe gérant les accès en lecture/écriture dans le context
 * This class manages the read/write from/into the context
 * 
 * Exemples :
 * Examples :
 * <code>
 * // la configuration ne peut être mise qu'une seule fois 
 * // et DOIT absolument doit être appellée afin que l'objet s'instancie
 * // configuration can only be set one time and HAS to be called first, it inits the object
 * $conf = array('dbusername'=>'user', 'dbpasswd'=>'passwd');
 * C::setCfg($conf); 
 * // setter
 * C::set('charset', 'utf-8');
 * // getter
 * echo C::get('charset'); // outputs 'utf-8'
 * echo C::get('dbusername', 'cfg'); // outputs 'user'
 * // set and get user vars
 * $lodeluser = array('rights', LEVEL_ADMINLODEL);
 * C::setUser($lodeluser); // will set the user and also copy values into the context
 * echo C::get('rights', 'lodeluser');
 * </code>
 *
 */

/**
 * Classe gérant le context
 */
class C
{
	/**
	 * Class instance
	 * @var object 
	 */
	static private $_instance;
	/**
	 * HTMLPurifier instance
	 * @var object 
	 */
	static public $filter;
	/** 
	 * array containing configuration values
	 * @var array
	 */
	static private $_cfg = array();
	/** 
	 * array containing user informations (rights, session, etc..)
	 * @var array
	 */
	static private $_lodeluser = array();
	/** 
	 * array containing the current request
	 * @var array
	 */
    	static private $_context = array();
	/** 
	 * backup of the current request if re-setting context
	 * @var array
	 */
    	static private $_backupC = array();
	/** 
	 * array containing all necessary informations about triggers
	 * @var array
	 */
	static private $_triggers = array();

	/**
	 * Private constructor called by self::setCfg
	 * Will instantiate the class and set the config values
	 *
	 * @param array $cfg the config vars passed by reference
	 */
	private function __construct(array &$cfg)
	{
		if(!headers_sent()) header("Content-Type: text/html; charset=UTF-8");
		self::$filter = null;
		self::$_cfg = $cfg; // set the config vars
		self::$_cfg['https'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? true : false);
		$GLOBALS['tp'] = $GLOBALS['tableprefix'] = $cfg['tableprefix'];
		defined('SITEROOT') || define('SITEROOT', '');
		function_exists('cache_get') || include 'cachefunc.php';
		spl_autoload_register(array('self', '__autoload'));
	}

	/**
	 * Cloning is not allowed :-)
	 */
	public function __clone()
	{
		trigger_error('Cloning the context is not allowed.', E_USER_ERROR);
	}

	/**
	 * Class instantiation (singleton mode)
	 * 
	 * @param array $cfg the config vars passed by reference
	 * @access public
	 * @static
	 */
	static public function setCfg(array &$cfg=array())
	{
		if(!isset(self::$_instance))
		{
			$c = __CLASS__;
			self::$_instance = new $c($cfg);
			$cfg = null; // reset
		}
		else trigger_error('Config already has been set !', E_USER_ERROR);
	}

	/**
	 * Autoload of internal classes
	 *
	 * @param string $class the class name
	 * @access public
	 * @static
	 */
	static public function __autoload($class)
	{
		if(class_exists($class, false)) return true;
		
		$c = strtolower($class);

		if('logic' !== $c && 'genericlogic' !== $c && FALSE !== strpos($c,'logic')) // logic
		{
			$file = self::$_cfg['home'].'logic'.DIRECTORY_SEPARATOR.'class.'.substr($c, 0, -5).'.php';
		}
		elseif('genericdao' !== $c && 'dao' !== $c && FALSE !== strpos($c, 'dao')) // dao
		{
			$file = self::$_cfg['home'].'dao'.DIRECTORY_SEPARATOR.'class.'.substr($c, 0, -3).'.php';
		}
		elseif('vo' == substr($c, -2)) // VO (=dao)
		{
			$file = self::$_cfg['home'].'dao'.DIRECTORY_SEPARATOR.'class.'.substr($c, 0, -2).'.php';
		}
		else
		{
			$count = 0;
			$file = self::$_cfg['home'].str_replace('_', DIRECTORY_SEPARATOR, $class, $count).'.php';
			if(!$count) $file = strtolower($file);
		}

		$file = realpath($file);
		if(!$file) return false;

		include $file;

		return class_exists($class, false);
	}

	/**
	 * This function set self::$_backupC as current context
	 * It is used by the controller if called by a script
	 * @access public
	 * @static
	 */
	static public function reset()
	{
		self::$_context = self::$_backupC;
		$GLOBALS['context'] =& self::$_context;
		self::$_backupC = array();
	}

	/**
	 * This function set the current request into the context after cleaning it
	 *
	 * @param array $request the request (can be null if inside call, see auth.php)
	 * @param boolean $controller is it a script call or an inside call ? (by default, inside call)
	 * @access public
	 * @static
	 */
	static public function setRequest(array &$request=array(), $controller = false)
	{
		if($controller)
		{
			// inside call, backup the original context for the following
			self::$_backupC = self::$_context;
		}
		else
		{
			$uri = '';
			if(!empty($_GET))
			{
				foreach($_GET as $k=>$v)
				{
					if('clearcache' !== $k && !('id' === $k && (int)$v === 0))
					{
						if(is_array($v))
						{
							foreach($v as $kk => $vv)
								$uri .= $k[$kk]."=".$vv.'&';
						}
						else $uri .= $k."=".$v.'&';
					}
				}
			}
		
			self::$_cfg['qs'] = $uri; // query string for template engine
			unset($uri);
		}
		
		self::$_context = $GLOBALS['context'] = array(); // (re)init context

		if(empty($request))
		{
			// POST only
			unset($_GET['login'], $_GET['passwd'], $_GET['passwd2'], $_GET['old_passwd']);

			self::cleanRequest($_GET);
			foreach($_GET as $k=>$v)
			{
				if (is_array($v)) {
					foreach($v as $value) {
						if ($k === 'site' && (preg_match('/[^a-z0-9-]+/', $value) === 1)) {
							$value = null;
							continue 1; 
						}

						if ((strpos($value, '<?php') === false) && (strpos($value, '%3E?php') === false)) {
							self::$_context[$k][] = $value;
						}
					}
				}else {

					if ($k === 'site' && (preg_match('/[^a-z0-9-]+/', $v) === 1)) {
						$v = null;
						continue; 
					}


					if ((strpos($v, '<?php') === false) && (strpos($v, '%3E?php') === false )) {
						self::$_context[$k] = $v;
					}
				}
			}
			if (!empty($_POST)) 
			{
				self::$_cfg['isPost'] = true; // needed for template engine (save or not calculed page)
				self::cleanRequest($_POST);
				foreach($_POST as $k=>&$v)
				{
                    if (is_array($v)) {
                        self::$_context[$k] = array();
                        foreach($v as $key =>& $value) {
                            if (!preg_match("/(<|\%3C)?\?(php)?.*(php)?\?(>|\%3E)?/", $value)) {
                                self::$_context[$k][$key] =& $value;
                            }
                        }
                        self::$_context[$k] =& $v;
                    }elseif (!preg_match("/(<|\%3C)?\?(php)?.*(php)?\?(>|\%3E)?/", $v)) {
						self::$_context[$k] =& $v;
					}
				}
                unset($v);
                unset($value);
			}

			// ids. Warning: don't remove this, the security in the following rely on these ids are real int !!
			foreach (array('id', 'idgroup', 'idclass', 'idparent', 'idtype', 'identity') as $var) 
			{
				if(isset($_POST[$var]))
				{
					self::$_context[$var] =& $_POST[$var];
					unset($_GET[$var]);
				}
				elseif(isset($_GET[$var]))
				{
					self::$_context[$var] = $_GET[$var];
				}
				else
				{
					self::$_context[$var] = 0;
					continue;
				}
				if('id' == $var || 'idtype' == $var)
				{
					if(preg_match('/^([a-z0-9\-]+)\.(\d+)$/', self::$_context[$var], $m))
					{
						self::$_context[$var] = (int)$m[2];
						self::$_cfg['site_ext'] = self::$_context['site_ext'] = (string)$m[1];
						continue;
					}
				}
				self::$_context[$var] = (int)self::$_context[$var];
			} 
		}
		else
		{
			self::cleanRequest($request);
			foreach($request as $k=>$v)
			{
				self::$_context[$k] = $v;
			}

			// ids. Warning: don't remove this, the security in the following rely on these ids are real int !!
			foreach (array('id', 'idgroup', 'idclass', 'idparent', 'idtype', 'identity') as $var) 
			{
				if(isset($request[$var]))
				{
					self::$_context[$var] = $request[$var];
				}
				else
				{
					self::$_context[$var] = 0;
					continue;
				}
				if('id' == $var || 'idtype' == $var)
				{
					if(preg_match('/^([a-z0-9\-]+)\.(\d+)$/', self::$_context[$var], $m))
					{
						self::$_context[$var] = (int)$m[2];
						self::$_context['site_ext'] = (string)$m[1];
						continue;
					}
				}
				self::$_context[$var] = (int)self::$_context[$var];
			}
		}

		// valid the request
		if (isset(self::$_context['do']) && !preg_match("/^(_[a-zA-Z0-9]+_)?[a-zA-Z0-9]+$/", self::$_context['do']))
			trigger_error("ERROR: invalid action", E_USER_ERROR);

		foreach (array('class', 'classtype', 'type', 'textgroups') as $var) 
		{
			if (isset(self::$_context[$var]) && self::$_context[$var])
			{
				// get the various common parameters
				function_exists('validfield') || include 'validfunc.php';
				if(!validfield(self::$_context[$var], $var)) 
					trigger_error("ERROR: a valid $var name is required", E_USER_ERROR);
			}
		}

		// dir
		if (isset(self::$_context['dir']) && !(self::$_context['dir'] == 'up' || self::$_context['dir'] == 'down' || is_numeric(self::$_context['dir'])
				|| self::$_context['dir'] == 'asc' || self::$_context['dir'] == 'desc'))
		{
			unset(self::$_context['dir']);
		}
		
		if(isset(self::$_context['url_retour']))
			self::$_context['url_retour'] = strip_tags(html_entity_decode(self::$_context['url_retour'], ENT_COMPAT, 'UTF-8'));

		if(!$controller)
		{ // if not called by the controller, let's init template needed vars
			self::$_context['version'] = self::$_cfg['version'];
			self::$_context['shareurl'] = self::$_cfg['shareurl'];
			self::$_context['extensionscripts'] =self::$_cfg['extensionscripts'];
			self::$_context['currenturl'] = isset($_SERVER['SERVER_NAME']) ? 'http'.(self::$_cfg['https'] ? 's' : '').'://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). $_SERVER['REQUEST_URI'] : '';
			self::$_context['siteroot'] = (defined('SITEROOT') ? SITEROOT : '');
			self::$_context['sharedir'] = self::$_cfg['sharedir'];
			self::$_context['tp'] = self::$_context['tableprefix'] = self::$_cfg['tableprefix'];
			self::$_context['base_rep'] = array();
			self::$_context['charset'] = 'utf-8';
			if(!defined('backoffice-lodeladmin')) 
				self::$_context['site'] = self::$_cfg['site'];
			// get all the triggers in self::$_triggers

			/* Get the site URL */
			defined('INC_CONNECT') || include 'connect.php';
			defined('INC_FUNC') || include 'func.php';
			global $db;
			self::$_context['siteurl'] = rtrim( $db->getOne(lq('SELECT url FROM #_MTP_sites WHERE name = "' . addslashes(C::get('site','cfg')) . '"')), '/');

			self::_getTriggers();
		}

		$GLOBALS['context'] =& self::$_context; // needed by template engine
	}

	/**
	 * Get all the triggers and set self::$_triggers
	 * Will try to reach the cache
	 * @access private
	 * @static
	 */
	static private function _getTriggers()
	{
		if(defined('backoffice-lodeladmin')) return true; // no plugins in lodeladmin

		$cache      = getCacheObject();
		$cache_name = getCacheIdFromId('triggers');
		if(!(self::$_triggers = @unserialize($cache->get($cache_name))))
		{
			defined('INC_CONNECT') || include 'connect.php';
			global $db;
			$triggers = Plugins::getTriggers();
			self::$_triggers = array();
			foreach($triggers as $trigger)
			{
				self::$_triggers['trigger_'.$trigger] = array();
			}
			
			$trigObj = $db->Execute(lq('
				SELECT * 
					FROM #_MTP_mainplugins
					WHERE status > 0')) 
					or trigger_error($db->ErrorMsg(), E_USER_ERROR);
			while(!$trigObj->EOF)
			{
				$plug = $db->getOne(lq('
				SELECT config
					FROM #_TP_plugins
					WHERE name="'.addslashes($trigObj->fields['name']).'" AND status > 0'));
				
				if($db->ErrorNo()) trigger_error($db->ErrorMsg(), E_USER_ERROR);

				if($plug)
				{
					$trigger = $trigObj->fields;
					unset($trigger['upd']); // dont care about that
					self::$_triggers[$trigger['name']] = $trigger;
					foreach($triggers as $trig)
					{
						if(!empty($trigger['trigger_'.$trig]))
							self::$_triggers['trigger_'.$trig][$trigger['name']] = $trigger['hooktype'];
					}
					self::$_triggers[$trigger['name']]['config'] = unserialize($plug);
				}
				$trigObj->MoveNext();
			}
			$trigObj->Close();
			$cache->set($cache_name, serialize(self::$_triggers));
		}

		// bootstrap for all activated plugins
		foreach(self::$_triggers as $name=>$values)
		{
			if('trigger_' === substr($name, 0, 8)) continue;
			
			$file = realpath(self::$_cfg['sharedir'].'/plugins/custom/'.$name.'/'.$name.'.php');
			if(!$file)
			{
				trigger_error('ERROR: invalid plugin '.$name, E_USER_WARNING);
				continue;
			}
			include $file;
		}
	}

	/**
	 * Call all plugins that have the trigger $name
	 *
	 * @param string $name the trigger name
	 * @access public
	 * @static
	 */
	static public function trigger($name)
	{
		// no plugins in lodeladmin
		if(defined('backoffice-lodeladmin') || empty(self::$_triggers['trigger_'.$name])) return true;

		foreach(self::$_triggers['trigger_'.$name] as $trigger=>$hooktype)
		{
			switch($hooktype)
			{
				case 'class':
					if(!method_exists($trigger, $name))
						trigger_error('Invalid trigger : '.$trigger.'::'.$name, E_USER_ERROR);
					Plugins::get($trigger)->{$name}(self::$_context);
					break;
				
				case 'func':
					$func = $trigger.'_'.$name;
					if(!function_exists($func))
						trigger_error('Invalid trigger : '.$func, E_USER_ERROR);
					$func(self::$_context);
					break;
			}
		}
	}

	/**
	 * Gets a value.
	 * This function supports multi-dimensionnal arrays : echo C::get('options.language'); // == self::$_context['options']['language']
	 *
	 * @param string $v the name of the var to get
	 * @param string $arr the array to search into
	 * @access public
	 * @static
	 */
	static public function get($v=null, $arr=null)
	{
		if(isset($arr))
		{
			if(!isset(self::${"_{$arr}"})) return false;
			if(!isset($v))	return self::${"_{$arr}"};

			if(isset(self::${"_{$arr}"}[$v])) return self::${"_{$arr}"}[$v];

			if(false === strpos($v, '.')) return false;

			$vars = explode('.', $v);
			$return = self::${"_{$arr}"};
			foreach($vars as $var)
			{
				if(!is_array($return) || !isset($return[$var])) return false;
				$return = $return[$var];
			}

			return $return;
		}
		elseif(isset($v))
		{
			if(isset(self::$_context[$v])) return self::$_context[$v];

			if(false === strpos($v, '.')) return false;

			$vars = explode('.', $v);
			$return = self::$_context;
			foreach($vars as $var)
			{
				if(!is_array($return) || !isset($return[$var])) return false;
				$return = $return[$var];
			}
			
			return $return;
		}
		
		return self::$_context;
	}

	/**
	 * Gets the context.
	 * Warning : the returned array is a reference to self::$_context
	 * @access public
	 * @static
	 */
	static public function &getC()
	{
		return self::$_context;
	}

	/**
	 * Merge the passed array into the current context
	 * Warning : by default, we assume that the values have already been sanitized !!
	 *
	 * @param array $datas the datas to merge the context with
	 * @param bool $clean do we have to clean the inputs ?
	 * @access public
	 * @static
	 */
	static public function mergeC(array &$datas, $clean=false)
	{
		if($clean) self::clean($datas);
		return ((bool)(self::$_context = array_merge($datas, self::$_context)));
	}

	/**
	 * Sets a value ONLY into self::$_context.
	 * This function supports multi-dimensionnal arrays : C::set('options.language', 'fr'); // == self::$_context['options']['language'] = 'fr';
	 *
	 * @param string $n the name of the var to set
	 * @param mixed $v the value
	 * @access public
	 * @static
	 */
	static public function set($n, $v)
	{
		if(false === strpos($n, '.')) return ((bool)('lodeluser' !== (string)$n ? (self::$_context[$n] = $v) : false));

		$vars = explode('.', $n);
		if('lodeluser' === (string)$vars[0]) return false; // haha
		
		$set =& self::$_context;
		foreach($vars as $var)
		{
			if(!is_array($set))
			{
				$set=array();
				$set[$var] = array();
			}
			elseif(!isset($set[$var])) $set[$var] = array();
			$set =& $set[$var];
		}
		
		return ((bool)($set = $v));
	}

	/**
	 * Set the user vars
	 *
	 * @param array $v the values
	 * @param string $n the array to set
	 * @access public
	 * @static
	 */
	static public function setUser($v=null, $n=null)
	{
		if(!isset($n))
		{
			if(!isset($v)) return ((bool)(self::$_lodeluser = self::$_context['lodeluser'] = null));
			elseif(empty(self::$_lodeluser))
			{
				self::$_lodeluser = self::$_context['lodeluser'] = $v;
				// don't want to have access to the session id or name in templates
				self::$_context['lodeluser']['session'] = self::$_context['lodeluser']['idsession'] = null;
				return true;
			}
			else return false;
		}

		$n = (string)$n;

		if(false === strpos($n, '.'))
		{
			if(isset(self::$_lodeluser[$n]))
			{
				return ((bool) (!isset($v) ? (self::$_lodeluser[$n] = self::$_context['lodeluser'][$n] = $v) : false));
			}
			else 
			{
				self::$_lodeluser[$n] = array();
				self::$_context['lodeluser'][$n] = array();
				self::$_lodeluser[$n] = $v;
				// don't want to have access to the session id or name in templates
				return ((bool)(('idsession' === $n || 'session' === $n) ? true : (self::$_context['lodeluser'][$n] = $v)));
			}
		}
		else
		{
			$vars = explode('.', $n);
			$set =& self::$_lodeluser;
			foreach($vars as $var)
			{
				if(!is_array($set))
				{
					$set = array();
					$set[$var] = array();
				}
				elseif(!isset($set[$var])) $set[$var] = array();
				$set =& $set[$var];	
			}
			
			return ((bool)($set = $v));
		}
		return false;
	}

	/**
	 * Public function to clean input from users (GET and POST)
	 *
	 * @param mixed $data the value to sanitize (can be either a string or an array)
	 * @access public
	 * @static
	 */
	static public function cleanRequest(&$data)
	{
		if(is_array($data))
			array_walk_recursive($data, array('self', 'cleanRequest'));
		else {
 			// rejects overly long 2 byte sequences, as well as characters above U+10000
			$data = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.'|(?<=^|[\x00-\x7F])[\x80-\xBF]+'.'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/','�', $data);
			// removes overly long 3 byte sequences and UTF-16 surrogates
			$data = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.'|\xED[\xA0-\xBF][\x80-\xBF]/S','�', $data );
		}

		return $data;
	}

	/**
	 * Public function to clean input datas
	 *
	 * @param mixed $data the value to sanitize (can be either a string or an array)
	 * @access public
	 * @static
	 */
	static public function clean(&$data)
	{
		if(is_array($data)) 
		{
			array_walk_recursive($data, array('self', '_sanitize'));
		}
		else 
		{
			self::_sanitize($data);
		}

		return $data;
	}

	/**
	 * Private function to clean input datas
	 * Uses HTMLPurifier
	 *
	 * @param mixed $data the value to sanitize (has to be string)
	 * @access private
	 * @static
	 */	
	static private function _sanitize(&$data)
	{
		if(empty($data) || !is_string($data) || is_numeric($data)) return true; // useless on boolean, integer, objects or other types..

		if(!isset(self::$filter))
		{
			$cache = cache_get_path('htmlpurifier');

			if (!class_exists('HTMLPurifier', false)) {
				include 'vendor/autoload.php';
			}
			$config = HTMLPurifier_Config::createDefault();
		
			$filters = array();
			// custom Lodel filters
			!file_exists(self::$_cfg['home'].'htmlpurifierFilters.php') || include 'htmlpurifierFilters.php';
			// custom personnal filters
			!file_exists(self::$_cfg['home'].'htmlpurifierFilters_local.php') || include 'htmlpurifierFilters_local.php';

			if(!empty($filters)) $config->set('Filter.Custom', $filters);

			$config->set('Core.Encoding', 'UTF-8');
			$config->set('Core.LexerImpl', 'DirectLex');
			$config->set('HTML.TidyLevel', 'heavy' );
			$config->set('Attr.EnableID', true);
			$config->set('Cache.SerializerPath', $cache );
			$config->set('HTML.Doctype', 'XHTML 1.0 Strict'); // replace with your doctype
                        $config->set('HTML.DefinitionID', 'r2r:ml no namespaces allowed');
                        $config->set('HTML.DefinitionRev', 1);

			// L'option HTML.SafeObject est très très gourmande, on ne l'utilise que si c'est nécessaire (pareil pour HTML.SafeEmbed just in case)
			if (stripos("<object", $data))
				$config->set('HTML.SafeObject', true);
			if (stripos("<embed", $data))
				$config->set('HTML.SafeEmbed', true);
			$config->set('CSS.AllowTricky',true);
			if ($def = $config->maybeGetRawHTMLDefinition()){
				$def->addAttribute('a', 'rel', 'CDATA'); // allow any rel attribute
				$r2r = $def->addElement(
					'r2r',   // name
					'Block',  // content set
					'Flow', // allowed children
					'IL8N', // attribute collection
					array( // attributes
					'lang' => 'CDATA')
				);
				$r2r->excludes = array('r2r' => true);
			}
			self::$filter = new HTMLPurifier($config);
		}
	
		// htmlpurifier does not support namespaces
		$data = strtr(trim($data), array('<r2r:ml '=>'<r2r ', '</r2r:ml>'=>'</r2r>'));
		$data = self::$filter->purify($data);
		$data = strtr($data, array('<r2r '=>'<r2r:ml ', '</r2r>'=>'</r2r:ml>')); // TODO Ceci ne marche pas < est transformé en &lt; juste avant…
		return true;
	}
}
