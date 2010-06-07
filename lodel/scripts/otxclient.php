<?php
ini_set('soap.wsdl_cache_enabled', false);
ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '60');

class OTXClient extends SoapClient
{
	private $_authorizedParams;
	private $_neededRequest;
	public $contents;
	public $status;
	public $error;
	private $_instanciated = false;

	public function __construct() 
	{
		$this->status = null;
		$this->error = 0;
		$this->_authorizedParams = array('servoo.username'=>true,'servoo.passwd'=>true,'servoo.url'=>true, 'servoo.proxyhost'=>false, 'servoo.proxyport'=>false, 'lodel_user'=>true,'lodel_site'=>true, );
		$this->_neededRequest = array('request'=>true, 'attachment'=>true, 'mode'=>true, 'schema'=>true);
	}

	public function instantiate(&$opts)
	{
		$this->error = 0;
		$options = array();
		$options['trace'] = TRUE;
		$options['soap_version'] = SOAP_1_2;
		$options['exceptions'] = TRUE;
		$options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5;
		$options['encoding'] = SOAP_LITERAL;

		$options['login'] = "otx";
		$options['password'] = "5e41921ba44c61090abef994fff2cc0d";

		try {
			$this->_checkParams($opts);

			$options['location'] = $opts['servoo.url'];

			// hash the password
			$passwd = md5($opts['servoo.username'].$opts['servoo.passwd']);
			
			if(isset($opts['servoo.proxyhost']))
			{
				$options['proxy_host'] = $opts['servoo.proxyhost'];
				$options['proxy_port'] = (int)$opts['servoo.proxyport'];
				$options['proxy_login'] = $opts['servoo.username'];
				$options['proxy_password'] = $opts['servoo.passwd'];
			}

			$opts['servoo.passwd'] = null; // cleaning memory

			$wsdl = $opts['servoo.url'].'?wsdl';
			$h = get_headers($wsdl, 1);
			if($h[0] === 'HTTP/1.1 200 OK' && $h['Content-Type'] === 'application/xml; charset=UTF-8' && $h['Content-Length'] > 0)
				parent::__construct($wsdl, $options);
			else throw new SoapFault("WebServOO FaultError", //faultcode
						'Invalid url '.$wsdl.' return headers '.print_r($h, 1), //faultstring
						'', // faultactor, TODO ?
						"Soap Creation",  // detail
						"UTF-8" // faultname
						/*$headerfault // headerfault */ );

			// get the token for this session
			$sessionToken = $this->webservooToken();

			// add the header for auth
			$header = new SoapVar(array('login'=>$opts['servoo.username'], 'password'=>md5($passwd.$sessionToken->sessionToken), 'lodel_user'=>$opts['lodel_user'], 'lodel_site'=>$opts['lodel_site']), SOAP_ENC_OBJECT);
			unset($options, $passwd, $sessionToken); // cleaning memory

			$this->__setSoapHeaders(array(new SoapHeader('urn:webservoo', 'webservooAuth', $header)));
			unset($header, $webservooHeader); // cleaning memory
			$this->_instanciated = true;
		}
		catch (SoapFault $fault) {
			$this->error = true;
			$this->status = !empty($fault->detail) ? "On ".$fault->detail.': '.$fault->faultstring : $fault->faultstring;
		}
	}

	public function request(&$request)
	{
		try {
			$this->_checkRequest($request);
			// make the request and get tei result
			$req = $this->webservooRequest(array('request'=>$request['request'], 'mode'=>$request['mode'], 'attachment'=>$request['attachment'], 'schema'=>$request['schema']));
			if(empty($req))
				throw new SoapFault("WebServOO client FaultError", 'ERROR: empty return from OTX');
			foreach($req as $k=>$v)
				$this->$k = $v;
		}
		catch (SoapFault $fault) {
			$this->error = true;
   			$this->status = !empty($fault->detail) ? "On ".$fault->detail.': '.$fault->faultstring : $fault->faultstring;
		}
	}

	public function selectServer($i=2) 
	{
		if(0 === $i) $i = '';
		$options = array();
		$options=getoption(array("servoo$i.url","servoo$i.username","servoo$i.passwd",
				"servoo$i.proxyhost","servoo$i.proxyport"),"");

		if ((!$options || empty($options['servoo.url'])) && C::get('servoourl'.$i, 'cfg')) { // get form the lodelconfig file
			$options['servoo.url']=C::get('servoourl'.$i, 'cfg');
			$options['servoo.username']=C::get('servoousername'.$i, 'cfg');
			$options['servoo.passwd']=C::get('servoopasswd'.$i, 'cfg');
		}

		// proxy
		if (empty($options['servoo.proxyhost']) && C::get('proxyhost'.$i, 'cfg')) $options['servoo.proxyhost']=C::get('proxyhost'.$i, 'cfg');
		if (!empty($options['servoo.proxyhost'])) {
			if (empty($options['servoo.proxyport'])) 
			{
				if(C::get('proxyport'.$i, 'cfg'))
					$options['servoo.proxyport']=C::get('proxyport'.$i, 'cfg');
				else $options['servoo.proxyport']="8080";
			}
		}
		if(!empty($options['servoo.url']) && !empty($options['servoo.username']) && !empty($options['servoo.passwd'])) {
			return $options;
		} else {
			return FALSE;
		}
	}

	private function _checkParams(&$opts)
	{
		$r = array();
		foreach($this->_authorizedParams as $k=>$v)
		{
			if(!isset($opts[$k])) 
			{
				if($v) 
				{
					throw new SoapFault("WebServOO FaultError", //faultcode
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

	private function _checkRequest(&$request)
	{
		$r = array();
		foreach($this->_neededRequest as $k=>$v)
		{
			if(!isset($request[$k])) 
			{
				throw new SoapFault("WebServOO FaultError", //faultcode
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

?>
