<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */



/**
 * Classe permettant de communiquer avec OTX
 */
class OTXClient
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
			throw new Exception("Webotx client FaultError: client has not been instanciated");
		$this->_checkRequest($request);
		// make the request and get tei result
		$data = array(
                	'mode' => $request['mode'],
                	'schema'=> $request['schema'],
                	'site' => $request['site'],
                	'sourceoriginale' => $request['sourceoriginale'],
            	);

            	if(function_exists('curl_file_create')){
                	$data['attachment'] = file_exists($request['attachment']) ? curl_file_create($request['attachment']) : $request['attachment'];
            	}else{
                	$data['attachment'] = file_exists($request['attachment']) ? "@" . $request['attachment'] : $request['attachment'];
            	}

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

		foreach(json_decode($req, true) as $k=>$v){
			if(is_string($v))
                		if(!($this->$k = base64_decode($v, true))) $this->$k = $v;
			#else $this->$k = $v;
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
