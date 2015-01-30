<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour la gestion du cache
 */

/**
 * Nettoyage du cache
 * Vide le cache de toute l'installation lodel, si $all est à True.
 *
 * @param bool $all
 * @see func.php -> function update()
 */
function clearcache( $all = false )
{
	$cache   = getCacheObject();

	if( $all ){
		$cache->delete_all();
	}else{
		$site    = C::get('site','cfg') ? C::get('site','cfg') : 'general';
		$session = $cache->get("session_{$site}") + 1;
		$cache->set("session_{$site}", $session);
	}
	if(defined('VARNISHED') && TRUE === VARNISHED){
		function_exists('proxy_purge') || require_once('func.php');
		proxy_purge();
	}
}

/**
 * Try to get serialized datas from cache
 *
 * @param string $name
 * @return boolean or cache contents
 */
function cache_get($name, $generate_cacheid = true)
{
	$cache = getCacheObject();

	$cachename = $generate_cacheid ? getCacheIdFromId($name) : $name;

	if($datas = $cache->get($cachename)){
		if($content = @unserialize(base64_decode($datas))) return $content;
		else return $datas;
	}else
		return false;
}

function cache_delete($name){
	$cache = getCacheObject();

	return $cache->delete(getCacheIdFromId($name));
}

function getCacheIdFromId( $id ){
	static $sessionsite = null;
	$cache = getCacheObject();
	$cache_config = cache_get_config();
	$site  = C::get('site','cfg') ? C::get('site','cfg') : 'general';
	$env   = defined('backoffice-lodelindex') ? 'lodelindex' : defined('backoffice-admin') ? 'admin' : defined('backoffice-edition') ? 'edition' : 'site' ;
	if ($sessionsite === null || C::get('clearcache'))
		$sessionsite = $cache->get( "session_{$site}", 0 );

	return  ( isset($cache_config['prefix']) ? "{$cache_config['prefix']}_" : "" ) 
			. "{$site}_{$env}_{$sessionsite}_{$id}" 
			. ( C::get('sitelang') ? "_" . C::get('sitelang') : null );
}

function getCacheObject(){
	$config = cache_get_config();
	return Cache::instance('lodel', $config);
}

function cache_exists($cacheid){
	$cache = getCacheObject();
	if($cache->get($cacheid)) return true;
	else return false;
}

function cache_include($cacheid){
	$cache = getCacheObject();
	if($content = $cache->get($cacheid)){
		eval ($content);
	}
}

function cache_get_path( $name ) {
	$cache_path = C::get('cacheDir', 'cfg') . DIRECTORY_SEPARATOR . $name;

	if(!is_readable($cache_path)){
		mkdir($cache_path, octdec(C::get('filemask', 'cfg')), true);
	}
	return realpath($cache_path);
}

function cache_get_config(){
	$config = array
		(
		'memcache' => array(
			'driver'             => 'memcache',
			'default_expire'     => 3600,
			'compression'        => FALSE,              // Use Zlib compression (can cause issues with integers)
			'servers'            => array(
				array(
					'host'             => 'localhost',  // Memcache Server
					'port'             => 11211,        // Memcache port number
					'persistent'       => FALSE,        // Persistent connection
					'weight'           => 1,
					'timeout'          => 1,
					'retry_interval'   => 15,
					'status'           => TRUE,
				),
			),
			'instant_death'      => TRUE,               // Take server offline immediately on first fail (no retry)
		),
		'memcachetag' => array(
			'driver'             => 'memcachetag',
			'default_expire'     => 3600,
			'compression'        => FALSE,              // Use Zlib compression (can cause issues with integers)
			'servers'            => array(
				array(
					'host'             => 'localhost',  // Memcache Server
					'port'             => 11211,        // Memcache port number
					'persistent'       => FALSE,        // Persistent connection
					'weight'           => 1,
					'timeout'          => 1,
					'retry_interval'   => 15,
					'status'           => TRUE,
				),
			),
			'instant_death'      => TRUE,
		),
		'apc'      => array(
			'driver'             => 'apc',
			'default_expire'     => 3600,
		),
		'wincache' => array(
			'driver'             => 'wincache',
			'default_expire'     => 3600,
		),
		'sqlite'   => array(
			'driver'             => 'sqlite',
			'default_expire'     => 3600,
			'database'           => sys_get_temp_dir() . DIRECTORY_SEPARATOR .'lodel-cache.sql3',
			'schema'             => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
		),
		'eaccelerator'           => array(
			'driver'             => 'eaccelerator',
		),
		'xcache'   => array(
			'driver'             => 'xcache',
			'default_expire'     => 3600,
		),
		'file'    => array(
			'driver'             => 'file',
			'cache_dir'          => sys_get_temp_dir(),
			'default_expire'     => 3600,
			'ignore_on_delete'   => array(
				'.gitignore',
				'.git',
				'.svn'
			)
		)
	);
	$localconfig = C::get('cacheOptions', 'cfg');
	return array_merge($config[$localconfig['driver']], $localconfig);
}
