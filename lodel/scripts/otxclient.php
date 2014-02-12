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
	private $_neededRequest = array('sourceoriginale'=>true, 'site' => true, 'attachment'=>true, 'mode'=>true, 'schema'=>true);

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
     * @var array contient toutes les options de connexion
     * @access pricate
     */
    private $_options = array();

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
        if(isset($opts['otx.proxyhost']))
        {
            $options['proxy_host'] = $opts['otx.proxyhost'];
            $options['proxy_port'] = (int)$opts['otx.proxyport'];
            $options['proxy_login'] = $opts['otx.username'];
            $options['proxy_password'] = $opts['otx.passwd'];
        }

        $this->_instanciated = true;

        foreach($opts as $o=>$v)
        {
            $options[$o] = $v;
        }

        $this->_options = $options;
        unset($options);
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
				throw new Exception("Webotx client FaultError", 'ERROR: client has not been instanciated');

			$this->_checkRequest($request);
			// make the request and get tei result
			$data = array(
                'mode' => $request['mode'],
                'schema'=> $request['schema'],
                'site' => $request['site'],
                'sourceoriginale' => $request['sourceoriginale'],
            );

            $data['attachment'] = file_exists($request['attachment']) ? "@" . $request['attachment'] : $request['attachment'];

            $request = curl_init($this->_options['otx.url']);
            curl_setopt($request, CURLOPT_USERPWD, $this->_options['otx.username'].":".$this->_options['otx.passwd'] );
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request ,CURLOPT_POSTFIELDS, $data);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

            $req = curl_exec($request);

            if(empty($req))
				throw new Exception('ERROR: empty return from OTX');

            if(curl_errno($request))
                throw new Exception("ERROR: Unknow error happened: ". curl_error( $request ));

            if(curl_getinfo( $request )['http_code'] != 200 )
                throw new Exception("ERROR: $req");

            curl_close($request);

			foreach(json_decode($req, true) as $k=>$v)
            {
                if( ! ( $this->$k = base64_decode($v)) )
                    $this->$k = $v;
            }

		}
		catch (Exception $fault) {
			$this->error = true;
   			$this->status = $fault->getMessage();
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
					throw new Exception("Webotx FaultError: Missing parameter $k");
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
				throw new Exception("Webotx FaultError: Missing parameter $k");
			}
			else $r[$k] = $request[$k];
		}

		$request = $r;
	}
}
