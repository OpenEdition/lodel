<?php 
require 'ClasseWS.php';
include 'function.php';

		 $url="";
			if(isset($_GET['type'])){
					if(isset($_GET['frs'])){
						switch ($_GET['frs']){
							case "aws" : // amazon
								if($_GET['type']=="_title"){
									$url = 	ClasseWS::createRequest('ItemSearch', array(
											'ResponseGroup'=>'Medium',
											'Title'=> $_GET['title'],
											'SearchIndex' => 'Books'));
									}// if titre
								 else{
									$url = 	ClasseWS::createRequest('ItemLookup', array(
											'ResponseGroup'=>'Medium',
											'ItemId'=> $_GET['isbn']));	
									} // else isbn
									$res = wsLigne(resultatsAmazon(toArray(simplexml_load_string(file_get_contents($url), 'SimpleXMLElement', LIBXML_NOCDATA))));
									echo json_encode($res);
								
								break;
							
							/*case "wcs" : //worldcat basic api
								if($_GET['type']=="_title"){
									$url = 	ClasseWS::createWRequest(array('q'=> $_GET['title']));
								}// if titre
								else{
									$url = 	ClasseWS::createWRequest(array('q'=> $_GET['isbn']));	
								} // else isbn
							 
								echo json_encode((array) simplexml_load_string(file_get_contents($url)));
// 								$tab=ToArray($xml);
// 								afficher_tableau($tab);
							break;*/
							
							case "wxisbn" : // worldcat xisbn
							
								if($_GET['type']=="_isbn"){
									echo json_encode(tableau_xisbn(simplexml_load_string(file_get_contents(ClasseWS::createXRequest($_GET['isbn'])))));
								}// if titre
								else{
									echo json_encode(array('error' => 'Que l\'ISBN est autorisé pour le service XISBN de WorldCat'));
								 } // else isbn
							break;	
							
							case "dws": //decitre
							
							$o=ClasseWS::createDecitreRequest($_GET['isbn'],$_GET['title'],"");
							$xml = simplexml_load_string($o->getCatalogueResult);
							
							$decitre=array();
							$i=0;
							
							foreach ($xml->PRODUCT as $product) {
									$decitre[$i]=array();
									$p=ToArray(json_decode(json_encode($product->URL_IMAGE)));
									$decitre[$i]['url']=$p[0];
									$p=ToArray(json_decode(json_encode($product->EAN)));
									$decitre[$i]['ean']=$p[0];
									$p=ToArray(json_decode(json_encode($product->ISBN)));
									$decitre[$i]['isbn']=$p[0];
									$decitre[$i]['titre']=utf8_decode($product->TITRE);
									$decitre[$i]['soustitre']=utf8_decode($product->SOUS_TITRE);
									
									$j=0;
									foreach ($product->AUTEURS->AUTEUR as $auteur) {
										$p=ToArray(json_decode(json_encode($auteur->NOM)));
										$decitre[$i]['auteur'.$j]=$p[0];
										$j++;
									}
									
									$decitre[$i]['edition']=utf8_decode($product->EDITION);
									$decitre[$i]['editeur']=utf8_decode($product->EDITEUR);
									$decitre[$i]['collection']=utf8_decode($product->COLLECTION);
									$p=ToArray(json_decode(json_encode($product->DATE_CREATION)));
									$decitre[$i]['dateparution']=$p[0]; 
									$i++;
									
							}
							
							echo json_encode($decitre);

							break;
							
							default :
								break;
						} //swtich
					} //if frs
				}// if type
?>