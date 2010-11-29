<?php
/**
 * Fichier de la classe permettant de communiquer avec OTX via le protole SOAP
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
 * @package lodel
 * @since Fichier ajouté depuis la version 1.0
 */

if(C::get('debugMode', 'cfg'))
{ // pas de cache WSDL si en mode débug
	ini_set('soap.wsdl_cache_enabled', false);
	ini_set('soap.wsdl_cache_enabled', '0');
	ini_set('soap.wsdl_cache_ttl', '60');
}

/**
 * Classe permettant de communiquer avec OTX
 *
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @since Classe ajoutée depuis la version 1.0
 */
class OTXClient extends SoapClient
{
	/**
	 * @var array liste des paramètres autorisés à l'envoi à OTX
	 * @access private
	 */
	private $_authorizedParams = array(
					'otx.username' => true,
					'otx.passwd' => true,
					'otx.url' => true,
					'otx.proxyhost' => false,
					'otx.proxyport' => false,
					'lodel_user' => true,
					'lodel_site' => true);

	/**
	 * @var array liste des paramètres obligatoires pour l'envoi à OTX
	 * @access private
	 */
	private $_neededRequest = array('request'=>true, 'attachment'=>true, 'mode'=>true, 'schema'=>true);

	/**
	 * @var string status retourné par OTX
	 * @access public
	 */
	public $status = '';

	/**
	 * @var boolean si une erreur intervient
	 * @access public
	 */
	public $error = false;

	/**
	 * @var boolean a-t-on déjà initié une connexion + authentification à OTX ?
	 * @access private
	 */
	private $_instanciated = false;

	/**
	 * Constructeur
	 *
	 * @access public
	 */
	public function __construct()
	{
	}

	/**
	 * Initialisation de la connexion à OTX
	 * Cette méthode test si OTX est joignable, puis essaye de s'identifier
	 *
	 * @access public
	 * @param array &$opts tableau des options SOAP
	 */
	public function instantiate(&$opts)
	{
		$this->error = false;
		$options = array();
		$options['trace'] = TRUE;
		$options['soap_version'] = SOAP_1_2;
		$options['exceptions'] = TRUE;
		$options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5;
		$options['encoding'] = SOAP_LITERAL;
		$options['authentication'] = SOAP_AUTHENTICATION_BASIC;

		try {
			$this->_checkParams($opts);

			$options['location'] = $opts['otx.url'];
			$options['login'] = $opts['otx.username'];
			$options['password'] = $opts['otx.passwd'];
			if(isset($opts['otx.proxyhost']))
			{
				$options['proxy_host'] = $opts['otx.proxyhost'];
				$options['proxy_port'] = (int)$opts['otx.proxyport'];
				$options['proxy_login'] = $opts['otx.username'];
				$options['proxy_password'] = $opts['otx.passwd'];
			}

			$wsdl = $opts['otx.url'].'?wsdl';
			$h = get_headers($wsdl, 1);
			if($h[0] === 'HTTP/1.1 200 OK' && $h['Content-Type'] === 'application/xml; charset=UTF-8' && $h['Content-Length'] > 0)
				parent::__construct($wsdl, $options);
			else throw new SoapFault("Webotx FaultError", //faultcode
						'Invalid url '.$wsdl.' return headers '.print_r($h, 1), //faultstring
						'', // faultactor, TODO ?
						"Soap Creation",  // detail
						"UTF-8" // faultname
						/*$headerfault // headerfault */ );

			// get the token for this session
			$sessionToken = $this->otxToken()->sessionToken;

			// add the header for auth
			$this->__setSoapHeaders(array(new SoapHeader('urn:otx', 'otxAuth', new SoapVar(array(
				'login' => $opts['otx.username'],
				'password' => md5(md5($opts['otx.username'] . ":" . $opts['otx.passwd']).$sessionToken),
				'lodel_user' => $opts['lodel_user'],
				'lodel_site' => $opts['lodel_site']), SOAP_ENC_OBJECT))));

			unset($options, $opts, $passwd, $sessionToken); // cleaning memory

			$this->_instanciated = true;
		}
		catch (SoapFault $fault) {
			$this->error = $fault->faultcode;
			$this->status = !empty($fault->detail) ? "On ".$fault->detail.': '.$fault->faultstring : $fault->faultstring;
		}
	}

	/**
	 * Envoi d'une requête à OTX
	 *
	 * @access public
	 * @param array &$request la requête à envoyer
	 */
	public function request(&$request)
	{
		try {
			if(!$this->_instanciated)
				throw new SoapFault("Webotx client FaultError", 'ERROR: client has not been instanciated');

			$this->_checkRequest($request);
			// make the request and get tei result
			$req = $this->otxRequest(array('request'=>$request['request'], 'mode'=>$request['mode'], 'attachment'=>$request['attachment'], 'schema'=>$request['schema']));
			if(empty($req))
				throw new SoapFault("Webotx client FaultError", 'ERROR: empty return from OTX');

			foreach($req as $k=>$v)
				$this->$k = $v;
		}
		catch (SoapFault $fault) {
			$this->error = true;
   			$this->status = !empty($fault->detail) ? "On ".$fault->detail.': '.$fault->faultstring : $fault->faultstring;
		}
	}

	/**
	 * Sélection d'un OTX
	 *
	 * @access public
	 * @param int $i le numéro de l'OTX à contacter
	 * @return mixed un tableau avec les données de connexion ou false en cas d'échec
	 */
	public function selectServer($i=2)
	{
		if(0 === $i) $i = '';
		$options = array();
		$options=getoption(array("otx$i.url","otx$i.username","otx$i.passwd",
				"otx$i.proxyhost","otx$i.proxyport"),"");

		if ((!$options || empty($options['otx.url'])) && C::get('otxurl'.$i, 'cfg')) { // get form the lodelconfig file
			$options['otx.url']=C::get('otxurl'.$i, 'cfg');
			$options['otx.username']=C::get('otxusername'.$i, 'cfg');
			$options['otx.passwd']=C::get('otxpasswd'.$i, 'cfg');
		}

		// proxy
		if (empty($options['otx.proxyhost']) && C::get('proxyhost'.$i, 'cfg')) $options['otx.proxyhost']=C::get('proxyhost'.$i, 'cfg');
		if (!empty($options['otx.proxyhost'])) {
			if (empty($options['otx.proxyport']))
			{
				if(C::get('proxyport'.$i, 'cfg'))
					$options['otx.proxyport']=C::get('proxyport'.$i, 'cfg');
				else $options['otx.proxyport']="8080";
			}
		}
		if(!empty($options['otx.url']) && !empty($options['otx.username']) && !empty($options['otx.passwd'])) {
			return $options;
		} else {
			return FALSE;
		}
	}

	/**
	 * Vérification des options avant envoi à OTX
	 *
	 * @access private
	 * @param array &$opts tableau des options validées
	 */
	private function _checkParams(&$opts)
	{
		$r = array();
		foreach($this->_authorizedParams as $k=>$v)
		{
			if(!isset($opts[$k]))
			{
				if($v)
				{
					throw new SoapFault("Webotx FaultError", //faultcode
					'Missing parameter '.$k, //faultstring
					'', // faultactor, TODO ?
					"Soap authentification",  // detail
					"UTF-8" // faultname
					/*$headerfault // headerfault */ );
				}
			}
			else $r[$k] = $opts[$k];
		}

		$opts = $r;
	}

	/**
	 * Vérification de la requête avant envoi à OTX
	 *
	 * @access private
	 * @param array &$request la requête à valider
	 */
	private function _checkRequest(&$request)
	{
		$r = array();
		foreach($this->_neededRequest as $k=>$v)
		{
			if(!isset($request[$k]))
			{
				throw new SoapFault("Webotx FaultError", //faultcode
				'Missing parameter '.$k, //faultstring
				'', // faultactor, TODO ?
				"Soap request",  // detail
				"UTF-8" // faultname
				/*$headerfault // headerfault */ );
			}
			else $r[$k] = $request[$k];
		}

		$request = $r;
	}
}
