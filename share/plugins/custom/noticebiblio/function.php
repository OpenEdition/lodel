<?php 

//filtre permettant de supprimer les accents
function filter($valeur)
{
$accents = array('À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö',
'Ù','Ú','Û','Ü','Ý','à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ð','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ');
$sans = array('A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','O','O','O','O','O',
'U','U','U','U','Y','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','y','y');
return str_replace($accents, $sans, $valeur);
}



// permet de mettre le résultat sous forme de tableau
function ToArray ( $data )
{
  if (is_object($data)) $data = get_object_vars($data);
  return (is_array($data)) ? array_map(__FUNCTION__,$data) : $data;
}

//fonction permettant de récupérer les éléments de recherche pour amazon
function resultatsAmazon($tableau) 
    {
    // on fait une boucle qui lit les éléments du tableau
	static $i=0;
	static $auth=0;
	static $result=array();
	$ligne=array();
	static $tab=array();
    foreach ($tableau as $cle=>$valeur) 
        {
        // si l'un des éléments est lui même un tableau alors on applique la fonction à ce tableau
	     if(is_array($valeur)){
		 	 $auth=array();	
		  
		  if((string)$cle=="Author"){ //  cas de plusieurs auteurs
			for($p=0;$p<count($valeur);$p++){
				$auth[]=$valeur[$p];
			}
	   	  }
	        // ici se réalise la récursivité c'est à dire qu'on applique la fonction à l'élément en cours car c'est lui aussi un tableau
		     resultatsAmazon($valeur); 
           // on ferme la liste
        }
     	// si ce n'est pas un tableau alors on affiche le contenu de l'élément
        else
            {
			//récupération des champs nécessaires
				$ln=array();
					switch ($cle){
					//si titre
					case "DetailPageURL":
						if(strpos($valeur,'//')!=0){
							$ln["url"]= $valeur;
							$ligne=array_merge($ligne,$ln);
							$i++;
							
						}
						break;
					case "Author" :
						//$ligne["auteur"]= str_replace("'","\\",$valeur);
						$ligne["auteur"]= $valeur;
			break;
					case "EAN" :
						$ln["EAN"]= $valeur;
						$ligne=array_merge($ligne,$ln);
						
						break;
					case "ISBN" :
						$ln["ISBN"]= $valeur;
						$ligne=array_merge($ligne,$ln);
						break;
					case "PublicationDate" :
						$ln["datepublication"]= $valeur;
						$ligne=array_merge($ligne,$ln);
						break;
					case "Publisher" :
						//$ln["Publisher"]=str_replace("'","\\",$valeur);
						$ln["editeur"]=$valeur;
						$ligne=array_merge($ligne,$ln);
						break;
					case "Title" :
						if($i!=0){
						//$ln["titre"]=str_replace("'","\\",$valeur);// trim(str_replace("\n", "", ));
						$ln["titre"]=$valeur;
						$ligne=array_merge($ligne,$ln);
						
						}
						break;
					case "Content" :
						//$ln["ndlr"]= str_replace("'","\\",filter($valeur));
						$ln["ndlr"]= $valeur;
						$ligne=array_merge($ligne,$ln);
						break;	
					default : 
						break;
						
				}
				
				if(count($auth)>=1){  // cas de plusieurs auteurs
					for($p=0;$p<count($auth);$p++){
						$ln["auteur".$p]= $auth[$p];
						$ligne=array_merge($ligne,$ln);
					}
					$auth=array();
				}				
           } //fin else
		 //  afficher_tableau($ligne);
	  } //fin foreach
	  
	if($ligne){
		$result[]=$ligne;
	//	$ligne=array();
	}
	return $result;
}  


 

//permet l'afichage du tableau à plusieurs dimensions
/*function afficher_tableau($tableau) 
    {
    // on fait une boucle qui lit les éléments du tableau
   foreach ($tableau as $cle=>$valeur) 
        {
        // si l'un des éléments est lui même un tableau alors on applique la fonction à ce tableau
        if(is_array($valeur)) 
            {
            // on affiche le nom de la clé et le début d'une liste pour décaler le contenu vers la droite
 		   echo '<b>'.$cle.' </b>: <ul>'; 
           // ici se réalise la récursivité c'est à dire qu'on applique la fonction à l'élément en cours car c'est lui aussi un tableau
            afficher_tableau($valeur); 
             // on ferme la liste
           echo '</ul>'; 
       }
 		// si ce n'est pas un tableau alors on affiche le contenu de l'élément
        else{
			echo '<b>'.$cle.' </b>= '.$valeur.' <br>';  
		}
	} 
 }  */
 
 
 //affichage des lignes trouvées pour Amazon
 function wsLigne($tableau) 
	{			  					
		static $resultat=array();
		$ln=array();
		$auth=array();
		static $res=array();						
		foreach ($tableau as $cle=>$valeur) 
		 {
			// si l'un des éléments est lui même un tableau alors on applique la fonction à ce tableau
			if(is_array($valeur)){
				// ici se réalise la récursivité c'est à dire qu'on applique la fonction à l'élément en cours car c'est lui aussi un tableau
				wsLigne($valeur,$fr); 
			}
			// si ce n'est pas un tableau alors on affiche le contenu de l'élément
			else{
				if($cle=="url"){ // dernier élément d'une ligne
					 $ln[$cle]=$valeur;
					 $resultat=array_merge($resultat,$ln);
					 
					 $res[]=$resultat;
							
					// afficher_tableau($resultat); //affichage des résultats
					
							$ln=array();
							$resultat=array();
							$auth=array();
							$nbauth=0;
				}	
			 else{
			   	$ln[$cle]=$valeur;
				$resultat=array_merge($resultat,$ln);	
				
			  // } //fin else no author
			}//else no detailurl
		}//fin else
	} //fin foreach
	return $res;
}  //fin function



 							 

//affichage des lignes trouvés par XISBN
/*function wsXLigne($tableau) 
 {
	// on fait une boucle qui lit les éléments du tableau
	static $resultat=array();
	$ln=array();
	static $nbauth=0;	
	static $res=array();							
	foreach ($tableau as $cle=>$valeur) 
	 {  
	 	$tab=array();
	   // si l'un des éléments est lui même un tableau alors on applique la fonction à ce tableau
		 if(is_array($valeur)) 
		  {
			// ici se réalise la récursivité c'est à dire qu'on applique la fonction à l'élément en cours car c'est lui aussi un tableau
			 wsXLigne($valeur); 
		  }
			// si ce n'est pas un tableau alors on affiche le contenu de l'élément
		else
		 {
		 	  if($cle=="lang"){ // dernier élément d'une ligne pour xisbn
				$ln[$cle]=$valeur;
				$resultat=array_merge($resultat,$ln);
		
				
				$res[]=$resultat;	  
				
				$ln=array();
				$resultat=array();
				$nbauth=0;
			  }	
	  		else{
			
				if((string)$cle=='auteur') {
				   $ln['auteur'.$nbauth]=$valeur;
				   $resultat=array_merge($resultat,$ln);
				   $nbauth++;
			 	}
		   	 	else{
					$ln[$cle]=$valeur;
					$resultat=array_merge($resultat,$ln);	
				}//fin else author	
			  }//else lang
			}//fin else
		} //fin foreach
		return $res;
	}  //fin function
	*/						 
				
//fonction permettant de récupérer les attributs de la réponse xml envoyée
function tableau_xisbn($xml){

	$result=array();
	$ln=array();
	$tab=ToArray($xml);
	unset($tab['@attributes']);
	$i=0;
	//lecture des attributs renvoyé par le résultat
	foreach ($xml->isbn as $isbn) {
		$auth=array();
		 $ln['isbn']=$tab['isbn'][$i];
		 $ln['titre']=(string)$isbn['title']; 
		 
		  $tabA=preg_split('~\band\b~i',(string)$isbn['author']);
		 if(count($tabA)>1){
		 	
		 	for($i=0;$i<count($tabA);$i++){
				$ln['auteur'.$i]=str_replace("by","",$tabA[$i]);
			}
					
		 }
		 $ln['publisher']=(string)$isbn['publisher'];
		 $ln['villeedition']=(string)$isbn['city'];
		 $ln['url']=(string)$isbn['url'];
		 $ln['informationscomplementaires']=(string)$isbn['lang'];
		 $result[]=$ln;
		 $ln=array();
		 $i++;
	}
	return $result;	
}

							 
							 

//fonction transformant un tableau PHP, même mutli-dimensionnel, en un tableau JS 

/* function php2js( $php_array, $js_array_name ) {
// contrôle des parametres d'entrée
 if( !is_array( $php_array ) ) {
 trigger_error( "php2js() => 'array' attendu en parametre 1, '".gettype($array)."' fourni !?!");
 return false;
 }
 if( !is_string( $js_array_name ) ) {
 trigger_error( "php2js() => 'string' attendu en parametre 2, '".gettype($array)."' fourni !?!");
 return false;
 }

 // Création du tableau en JS
 $script_js = "var $js_array_name = new Array();\n";

 // on rempli le tableau JS à partir des valeurs de son homologue PHP
 foreach( $php_array as $key => $value ) {

 // pouf, on tombe sur une dimension supplementaire
 if( is_array($value) ) {
 // On va demander la création d'un tableau JS temporaire
 $temp = uniqid('temp_'); // on lui choisi un nom bien barbare
 $t = php2js( $value, $temp ); // et on creer le script JS
 // En cas d'erreur, remonter l'info aux récursions supérieures
 if( $t===false ) return false;

 // Ajout du script de création du tableau JS temporaire
 $script_js.= $t;
 // puis on applique ce tableau temporaire à celui en cours de construction
 $script_js.= "{$js_array_name}['{$key}'] = {$temp};\n";
 }

 // Si la clef est un entier, pas de guillemets
 elseif( is_int($key) ) $script_js.= "{$js_array_name}[{$key}] = '{$value}';\n";

 // sinon avec les guillemets
 else $script_js.= "{$js_array_name}['{$key}'] = '{$value}';\n";
 }

 // Et retourn le script JS
 return $script_js;
 } 			*/				 