<?php

class ClasseWS{
 
 
 //************************* AMAZON *****************************
	public static $secretKey = 'aUY4H0M1S9koEyyjgbbqSwCoTaPbf5Nrzj8Uwlr7';
	public static $publicKey = 'AKIAIYIRF2R7IBZO53YQ';
	private static $_key;
	private static $_hashAlgorithm;
 
	/**
	 * Permet de creer une url pour les services amazon
	 * @param string $operation le paramètre opération de la requete
	 * @param array $options les autres paramètres.
	 * @return string l'url
	 */
    public static function createRequest($operation, array $options)
    {
        $options['AWSAccessKeyId'] = self::$publicKey;
        $options['Service'] ='AWSECommerceService';
//	   $options['Service']        = 'AWSECommerceService'; 'AmazonSimpleDB
        $options['Operation']      = (string) $operation;
        
        $baseUri = 'http://webservices.amazon.fr';
 
        if(self::$secretKey !== null) {
            $options['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");;
            ksort($options);
            $options['Signature'] = self::computeSignature($baseUri, self::$secretKey, $options);
        }
 
        return 'http://webservices.amazon.fr/onca/xml?'.http_build_query($options, null, '&');
    }
// 
    static public function computeSignature($baseUri, $secretKey, array $options)
    {
        $signature = self::buildRawSignature($baseUri, $options);
        return base64_encode(self::compute($secretKey, 'sha256', $signature, 'binary')
		/*base64_encode — Encode une chaîne en MIME base64
		 Cet encodage est fait pour permettre aux informations binaires d'être manipulées 
		 par les systèmes qui ne gèrent pas correctement les 8 bits, comme les corps de mail.
		Une chaîne encodée base64 prend environ 33 % de plus que les données initiales. */
        );
    }
// 
   static public function buildRawSignature($baseUri, $options)
    {
        ksort($options); /* ksort — Trie un tableau suivant les clés 
		Trie le tableau array suivant les clés, en maintenant la correspondance entre les clés et les valeurs. Cette fonction est pratique pour les tableaux associatifs. */
        $params = array();
        foreach($options AS $k => $v) {
            $params[] = $k."=".rawurlencode($v);// rawurlencode — Encode une chaîne en URL, selon la RFC 1738
        }
 
        return sprintf("GET\n%s\n/onca/xml\n%s",
            str_replace('http://', '', $baseUri),
            implode("&", $params)
        ); ////implode — Rassemble les éléments d'un tableau en une chaîne
    }
 //
    public static function compute($key, $hash, $data, $output)
    {
        // set the key
        if (!isset($key) || empty($key)) {
            throw new Exception('provided key is null or empty');
        }
        self::$_key = $key;
 
        // set the hash
        self::_setHashAlgorithm($hash);
 
        // perform hashing and return
        return self::_hash($data, $output);
    }
 //
   protected static function _setHashAlgorithm($hash)
    {
        if (!isset($hash) || empty($hash)) {
            throw new Exception('provided hash string is null or empty');
        }
 
        $hash = strtolower($hash);
        $hashSupported = false;
 
        if (function_exists('hash_algos') && in_array($hash, hash_algos())) { //hash_algos : Retourne un tableau indexé numériquement contenant la liste des algorithmes de hachage supportés. 
            $hashSupported = true;
        }
 
        if ($hashSupported === false) {
            throw new Exception('hash algorithm provided is not supported on this PHP installation; please enable the hash or mhash extensions');
        }
        self::$_hashAlgorithm = $hash;
    }
 //
    protected static function _hash($data, $output = 'string', $internal = false)
    {
        if (function_exists('hash_hmac')) {
            if ($output == 'binary') {
                return hash_hmac(self::$_hashAlgorithm, $data, self::$_key, 1); // hash_hmac : Génère une valeur de clé de hachage en utilisant la méthode HMAC
            }
            return hash_hmac(self::$_hashAlgorithm, $data, self::$_key);
        }
 
        if (function_exists('mhash')) { // Vérifie la liste des fonctions définies par l'utilisateur afin d'y trouver mhash
            if ($output == 'binary') {
                return mhash(self::_getMhashDefinition(self::$_hashAlgorithm), $data, self::$_key); //mhash : applique la fonction de hash (_getMhashDefinition(self::$_hashAlgorithm)) hash aux 		données data. 
            }
            $bin = mhash(self::_getMhashDefinition(self::$_hashAlgorithm), $data, self::$_key);
            return bin2hex($bin); //bin2hex: Retourne la chaîne en paramètre dont tous les caractères sont représentés par leur équivalent hexadécimal
        }
    }
 
 //
    protected static function _getMhashDefinition($hashAlgorithm)
    {
        for ($i = 0; $i <= mhash_count(); $i++) //mhash_count():Récupère l'identifiant maximal de hash
        {
            $types[mhash_get_hash_name($i)] = $i; //mhash_get_hash_name: Retourne le nom du hash spécifié
        }
        return $types[strtoupper($hashAlgorithm)]; // strtoupper(string) : retourne string, après avoir converti tous les caractères alphabétiques en majuscules
    }



//********************************** WORDLCAT ************************************


   private static $_wkey='vjMGVv4guhp7Qj2dl8PX6dx36DTz2tGHxCdkjmeJEEKhEwx6Wzv7QprV9qp8v8qY3mGpdzbUkfGgl4KS';
 
	/**
	 * Permet de creer une url pour les services worldcat
	 * @param array $options pour les paramètres.
	 * @return string l'url
	 */
    public static function createWRequest(array $options)
    {
        
        $options['format'] ='atom';
		$options['wskey'] = self::$_wkey;
       // $options['Operation']      = (string) $operation;
       
       return 'http://www.worldcat.org/webservices/catalog/search/opensearch?'.http_build_query($options, null, '&');
    }
	
	
	 public static function createXRequest($isbn)
    {
        $url='http://xisbn.worldcat.org/webservices/xid/isbn/'.$isbn.'?';
		$options['format'] ='xml';
        $options['method'] ='getEditions';
		$options['fl'] = '*';
        $url.=http_build_query($options, null, '&');
      
	   return $url;
    }
	
//************************************************************ DECITRE *****************************************************************************

	public function createDecitreRequest($isbn,$titre,$auteur){
				
	 try 
		{
						// Set authentication header parameters
						$auth_param = array(
										'UserName'    =>    'revuesdotorg',
										'Password'    =>    'nrYe68Hv3m'); 
						
						// Generate header
						$header=new SoapHeader("http://www.decitre.fr/webservices/revuesdotorg/revuesdotorg",
												   "revuesdotorgAuth",
												   $auth_param);
						  
						// New soapClient instance
						$client = new SoapClient('http://www.decitre.fr/webservices/revuesdotorg/revuesdotorg.asmx?wsdl', array('trace' => 1, 'soap_version'  => SOAP_1_1, 'encoding'=>'ISO-8859-1'));
						
						// Set the method parameters
						$params = array(
							"EANISBN" => $isbn,
							"Titre" => $titre,
							"Auteur" => $auteur,
							"NumPage" => "1" ,
							"NbResParPage" => "1000000" 
						);
						
						// Set soap headers
						$client->__setSoapHeaders(array($header));
						
						// Call the webservice method getCatalogue
						$o = $client->getCatalogue($params);
					
						// Display result
						//print_r("[" . $o->getCatalogueResult . "]<br />");
					} 
	catch (SoapFault $fault) 
	{
		//echo "Soap error : " . $fault->faultstring;
	}
	
	return $o;		
  }

  
	
	}
	
	
 
?> 