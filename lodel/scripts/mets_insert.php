<?php
/**
 * Fichier permettant d'insérer des entités dans la base de Lodel, à partir de fichiers METS et Dublin Core
 * Ne peut être utilisé qu'avec un ME spécifique
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cï¿½ou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
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
 * @author Sophie Malafosse
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 */

require_once ('simpleXML_extented.php');
require_once ('controler.php');
require_once ('PEAR/Log.php');

class mets_insert {

	private $error_levels = array (FATAL => PEAR_LOG_ERR,
					WARN => PEAR_LOG_WARNING,
					INFO => PEAR_LOG_INFO);

	/**
	 * Tableau des noms des éléments racines (partenaires)
	 * @var array
	 */
	public $partners = array();

	/**
	 * Tableau qui stocke les informations de la revue en cours de traitement
	 * @var array
	 */
	private $revue = array();


	/**
	 * Tableau destiné à stocker la requête envoyée à Lodel pour insérer une entité dans la base (numéro ou article)
	 * @var array
	 */
	private $request = array();

	/**
	 * Constructeur
	 */

	function __construct() {
		$this->file_log = SITEROOT . '/mets_insert.log';
		$this->_log_error(__METHOD__ . ' ----> Et zou !', INFO);
		if (!$this->partners = $this->get_partners()) { return false; }
		$this->_init_Lodel_request();
	}


	/**
	 * Insère les données issues du METS dans la base Lodel, pour une revue
	 * N.B : insertion et mise à jour des entités, mais PAS de suppression
	 * Appelé par lodel/admin/index.php
	 *
	 * @param array $revue initialisé dans lodel/admin/index.php
	 */

	public function parse_mets($revue) {
		$this->revue = $revue;

		//print_r($revue); exit;

		if (is_string($mets = $this->_get_revue_mets())) {
			
			if ($this->mets = simplexml_load_string($mets, 'SimpleXML_extended')) {
				$this->_get_namespaces($this->mets);

				// insertion racine mets (init $this->revue)
				$this->root = $this->mets->ListRecords->record[0]->metadata->children($this->namespaces['mets']);
				if ($this->_init_record($this->root, true)) {
					// insère dc et dcterms
					$this->_insert_data($this->root, $this->revue, true);

					// insère enfants
					$structMap = $this->root->mets->structMap->div;
					$fileSec = $this->root->mets->fileSec;
					$this->_insert_children($structMap, $fileSec, $this->revue['id'], $this->revue['idtype']);

				// insertion des autres records
					foreach($this->mets->ListRecords->record as $record) {
						$entity = $record->metadata->children($this->namespaces['mets']);

						if ($entity->mets->dmdSec instanceof SimpleXMLElement) {
							if ($this->_init_record($entity)) {
								// insère dc et dcterms
								$this->_insert_data($entity, $this->record);

								// insère enfants
								$structMap = $entity->mets->structMap->div;
								$fileSec = $entity->mets->fileSec;
								$this->_insert_children($structMap, $fileSec);
							} else {
								echo 'Pb avec le record';
							}
						}
					}
				} else {
					echo "Impossible d'insérer le record racine. Voir le xml dans le répertoire" . $revue['directory'];
				}
			} else {
				echo 'Impossible de charger avec SimpleXml le xml dans le répertoire ' . $revue['directory'];
			}
			
		} else {
			echo 'Aucun fichier à parser dans le répertoire' . $revue['directory'];
		}
	}


	/**
	 * Insère les données dublin core dans la base Lodel, pour une revue
	 * Utile pour récupérer le dc des articles de Persée (pas dans le mets)
	 * Cette fonction met à jour les entités déjà présentes dans Lodel, mais n'en ajoute pas
	 * Appelé par index.php à la racine du site
	 * Ne sert que pour Persée (pour l'instant)
	 * @param string $revue chemin du répertoire où sont stockés les fichiers xml
	 */

	public function parse_dc($revue) {
		$files = $this->_get_revue_files('dc');
		foreach ($files as $file) {
			$xml = simplexml_load_file($file, 'SimpleXML_extended');
			$this->_get_namespaces($xml);
			foreach($xml->ListRecords->record as $record) {
				$mets_id = basename($record->header->identifier);
				$request = $this->_get_Lodel_entity($mets_id);
				$entity = $record->metadata->children($this->namespaces['oai_dc']);
				$this->_insert_data($entity, $request, $root=false, $format='dc');
				
			}
		}
	}

/******************** Insertion des données ********************/


	private function _get_Lodel_entity($mets_id) {
		global $db;
		
		if ($result = $db->execute(lq("SELECT  id, idparent, idtype, identifier FROM entities WHERE identifier = '$mets_id' LIMIT 1"))) {
			 if(!empty($result->fields)) {
				foreach ($result->fields as $key=>$val) {
					$request[$key] = $val;
				}
				$request['class'] = $this->_get_Lodel_class($request['idtype']);
			}
		}
		return $request;
	}

	/**
	 * Pour un record mets, cherche les correspondances entre les éléments mets et les champs Lodel
	 * Initialise $this->record avec les infos trouvées
	 *
	 * On part du principe que des informations concernant le record ont déjà été insérées dans Lodel,
	 * parce qu'on a déjà parsé la structMap du parent
	 * Tout record qui n'est pas trouvé dans la base est ignoré (à part le premier, l'élément racine = la revue)
	 *
	 * @param object le record en question
	 * @param bool true s'il s'agit de  la racine, false sinon
	 * @return bool true si on a les infos nécessaires, false sinon
	 * @todo reconnaissance en fonction du partenaire (cleo=dc:type ; persee=mets TYPE="serie")
	 */
	
	private function _init_record($record, $root=false) {
		
		if ($record instanceof SimpleXMLElement) {
			$this->record['type'] = $this->record['data']['type'] = $record->getAttribute('TYPE');// Persée
			$this->record['idtype'] = $this->_get_Lodel_idtype($this->record['type']);
			$this->record['class'] = $this->Lodel_class = $this->_get_Lodel_class($this->record['idtype']);
			$this->record['identifier'] = basename($record->getAttribute('OBJID'));
			//$this->record['data'][$this->mets_id_field] = $this->record['mets_id'];
			$this->record['id'] = $this->_get_Lodel_id($this->record['identifier'], $this->record['class']);

			if (is_numeric($this->revue['partner_Lodel_id']) && $root===true) {
				$this->record['idparent'] = $this->revue['partner_Lodel_id'];
				$this->revue = array_merge($this->revue, $this->record);
			} else {
				$this->record['idparent'] = $this->_get_Lodel_idparent($this->record['id']);
			}
			foreach($this->record as $key=>$val) {
				if (empty($val) && $val !='id') {
					echo "Pb avec $key"; print_r($this->record);
				}
			}
			return true;
		} else {
			// erreur, pas un record : enregistrer l'id OAI pour debug
			return false;
		}
	}


	/**
	 * Cherche la structure d'un record (<div> in structmap) = les entités enfants dans Lodel
	 * Pour chaque enfant : cherche infos dans les noeuds structmap et filesec du parent
	 * Insère ensuite ces enfants dans la base Lodel
	 *
	 * @param object $xml premier niveau de div dans le structmap
	 * @param int $idparent id Lodel de l'entité dont on cherche les enfants
	 */

	private function _insert_children($strucMap, $fileSec, $idparent=0, $idtypeparent='') {
		
		foreach ($strucMap->div as $div) {
			$request = $this->_parse_structmap($div);

			// entité parente = le div parent
			$parent = $this->_parse_structmap($strucMap);
			if ($idparent > 0) {
				$request['idparent'] = $idparent;
			} else {
				$request['idparent'] = $parent['id'];
				$idtypeparent = $parent['idtype']; //pour debug
				if ($parent['id'] > 0) {
					$request['idparent'] = $parent['id'];
				} else {
					$this->_init_record($this->root, true); // si la revue vient d'être insérée
					$request['idparent'] = $this->revue['id'];
				}
			}

			// dc.identifier (URL)
			if ($request['mets_file_id']) {
				//$mets_idparent = $parent['data'][$this->mets_id_field];
				$Lodel_field_url = $this->_get_Lodel_dc_field("dc.identifier", $request['class']);
				foreach ($fileSec->fileGrp as $fileGrp) {
					foreach ($fileGrp->file as $file) {
						if ($file->getAttribute('ID') == $request['mets_file_id']) {
							$node = $file->FLocat->attributes($this->namespaces['xlink']);
							$request['data'][$Lodel_field_url] = $node['href'];
						}
					}
				}
			}

			// insère les entités enfants, si les types sont compatibles avec le ME
			if ($this->_check_types_compatibility($request['idtype'], $idtypeparent)) {
				$request['origine'] = 'structmap ' . $request['idtype'] . " $idtypeparent";
				$this->_execute_Lodel_request($request);
				// parcours des éventuels div enfants
				if ($div instanceof SimpleXMLElement && $div->div) {
					$this->_insert_children($div, $fileSec);
				}
			} else {
				echo "Pb d'imbrication des types, editer le ME. Type parent = $idtypeparent, type enfant = " . $request['idtype'];
			}
		}
	}

	/**
	 * Cherche dans un <div> d'un structmap les informations à insérer dans Lodel, pour une entité
	 *
	 * @param object $xml le <div> à analyser
	 * @return array $request 
	 */

	private function _parse_structmap($xml) {
		// identifiants mets
		$mets_id = $xml->getAttribute('ID');
		$mets_id = preg_replace('/(long|short|TdM|DM)_(\w+)/', '\2', $mets_id);
		$request['identifier'] = $mets_id;
		if ($file = $xml->fptr) {
			$request['mets_file_id'] = $file->getAttribute('FILEID');
		}

		// type de l'entité : mets et Lodel
		$request['type'] = $xml->getAttribute('TYPE');
		if (empty($request['type'])) {
			$record = $this->_find_record($mets_id, '');
			$request['type'] = $record->mets->getAttribute('TYPE');
		}
		$request['idtype'] = $this->_get_Lodel_idtype($request['type']);
		if ($request['idtype'] == 0) {
			echo 'Petit souci : le type'. $request['type'] . "n'existe pas";
			return 'err_type:' . $request['type'];
		}

		// Champs Lodel : class, id, rank
		$request['class'] = $this->_get_Lodel_class($request['idtype']);
		$request['id'] = $this->_get_Lodel_id($mets_id, $request['class']);
		$request['rank'] = $xml->getAttribute('ORDER');

		// Initialisation du champ dc.title avec le LABEL pour les <div> qui n'ont pas de record
		$title = $this->_get_Lodel_dc_field('dc.title');
		$request['data'][$title] = $xml->getAttribute('LABEL');
		
		return ($request);
	}

	/**
	 * Trouve dans le mets (in fileSec) l'URL d'un fichier à partir de son identifiant (in structmap)
	 *
	 * @param string $mets_idparent identifiant mets du record (ex : cea_0008-0055_1960_num_1_2_3665)
	 * @param string $mets_file_id identifiant mets du fichier (ex : FID1)
	 */

	private function _get_file_location($mets_file_id) {
		$record = $this->_find_record($mets_idparent, ''); echo " *$mets_file_id / $mets_idparent* ";
		if ($record->mets->fileSec instanceof SimpleXMLElement) {
			foreach ($record->mets->fileSec->fileGrp as $fileGrp) {
				foreach ($fileGrp->file as $file) {
					if ($file->getAttribute('ID') == $mets_file_id) {
						$node = $file->FLocat->attributes($this->namespaces['xlink']);
						return $node['href'];
					}
				}
			}
		}
	}


	/**
	 * Trouve dans le mets un record à partir de son identifiant
	 *
	 * @param string $mets_id identifiant du record (ex : cea_0008-0055_1960_num_1_2_3665)
	 * @param string $type type du record (ex : numero, article, serie)
	 */

	private function _find_record($mets_id, $type) {
		if (empty($type)) $type = '\w*';
		$pattern = '#oai:persee:' . $type . '/' . $mets_id . '#';
		
		foreach ($this->mets->ListRecords->record as $oai_record) {
			if (preg_match($pattern, $oai_record->header->identifier)) {
				$record = $oai_record->metadata->children($this->namespaces['mets']);
				return $record;
			}
		}
	}

	/**
	 * Trouve les informations (dc ou dcterms) correspondant au record (=édition d'une entité dans Lodel)
	 * Construit la requête en récupérant les noms des champs du ME qui correspondent aux éléments dc
	 * Puis appelle _execute_Lodel_request pour l'insertion dans Lodel
	 * 
	 * @return bool false si l'édition de l'entité a échoué, true sinon
	 */

	private function _insert_data($record, $request, $root=false, $format='mets') {

		if (!is_object($record->mets->dmdSec->mdWrap->xmlData)) {
			// erreur : pas de dc dans la dmdSec
		}
		
		switch ($format) {
			case 'mets' :
				$dc = $record->mets->dmdSec->mdWrap->xmlData->children($this->namespaces['dc']);
				$dcterm = $record->mets->dmdSec->mdWrap->xmlData->children($this->namespaces['dcterms']);
				break;
			case 'dc' :
				$dc = $record->children($this->namespaces['dc']);
				$dcterm = $record->children($this->namespaces['dcterms']);
				break;
			default :
				echo "erreur : format $format non reconnu : _insert_data";
		}

		// cherche les données (définies par les équivalents dc dans Lodel)
		foreach ($dc as $key=>$val) { // DC
			if ($Lodel_field_name = $this->_get_Lodel_dc_field("dc.$key", $request['class'])) {
				$request['data'][$Lodel_field_name] = $val;
				
			}
		}

		foreach ($dcterm as $key=>$val) { // DCTERMS
			if ($Lodel_field_name = $this->_get_Lodel_dc_field("dcterms.$key", $request['class'])) {
				$request['data'][$Lodel_field_name] = $val;
			}
		}
		
		// champ Lodel dc.identifier
		$Lodel_field_url = $this->_get_Lodel_dc_field("dc.identifier", $request['class']);
		if ($root === true) { //record racine (correspondant à la revue)
			$request['data'][$Lodel_field_url] = $dc->identifier;
		} else {
			// récupère la valeur du champ dc.identifier dans la base Lodel, pour ne pas l'écraser
			if ($format == 'mets') {
				$request['data'][$Lodel_field_url] = $this->_get_Lodel_field_value($Lodel_field_url, $request['id'], $request['class']);
			}
		}

		// insère le tout dans Lodel
		//$request['origine'] = 'dmdSec'; //pour debug
		$this->_execute_Lodel_request($request);
	}



	/**
	 * Trouve le nom du champ dans la base de Lodel qui correspond à l'équivalent dc de $dc_field
	 * 
	 * @param string $dc_field nom de l'élément dc
	 * @param string $class nom de la classe (=la table) dans Lodel
	 * @return string $Lodel_field s'il est trouvé, false sinon
	 */

	private function _get_Lodel_dc_field($dc_field, $class='') {
		if (empty($class)) $class = $this->Lodel_class;
		global $db;
		if ($Lodel_field = $db->getOne(lq("SELECT name FROM tablefields WHERE class='$class' AND g_name='$dc_field'"))) {
			return $Lodel_field;
		} else {
			return false;
		}
	}


	/**
	 * Trouve la valeur d'un champ dans la base de Lodel, pour une classe donnée
	 * 
	 * @param string $field nom du champ
	 * @param int $id de l'entité
	 * @param string $class nom de la classe (=la table) dans Lodel
	 * @return string val. du champ si elle est trouvée, false sinon
	 */

	private function _get_Lodel_field_value($field, $id, $class) {
		if (empty($class)) $class = $this->Lodel_class;
		global $db;
		if ($Lodel_field = $db->getOne(lq("SELECT $field FROM $class WHERE identity=$id"))) {
			return $Lodel_field;
		} else {
			return false;
		}
	}


	/**
	 * Trouve l'id d'un type (Lodel) à partir de son nom
	 * 
	 * @param string nom du type
	 * @return int $Lodel_idtype
	 */

	private function _get_Lodel_idtype($type) {
		global $db;
		if ($Lodel_idtype = $db->getOne(lq("SELECT id FROM types WHERE type='$type'"))) {
			return $Lodel_idtype;
		} else {
			return 0;
		}
	}

	/**
	 * Trouve l'id d'un type (Lodel) à partir de son nom
	 * 
	 * @param string nom du type
	 * @return int $Lodel_idtype
	 */

	private function _get_Lodel_class($idtype) {
		global $db;
		if ($class = $db->getOne(lq("SELECT class FROM types WHERE id='$idtype'"))) {
			return $class;
		} else {
			return 0;
		}
	}

	/**
	 * Retourne l'id Lodel d'une entité à partir de l'identifiant mets
	 * 
	 * @param string $identifier valeur de l'identifiant mets
	 * @return int $Lodel_id id Lodel si l'entité a déjà été créée, 0 sinon
	 */

	private function _get_Lodel_id($identifier, $class) {
		global $db;
		//if ($Lodel_id = $db->getOne(lq("SELECT identity FROM $class WHERE " . $this->mets_id_field . "= '$identifier'"))) {
		if ($Lodel_id = $db->getOne(lq("SELECT id FROM entities WHERE identifier= '$identifier'"))) {
			return $Lodel_id;
		} else {
			return 0;
		}
		
	}

	/**
	 * Retourne l'id mets d'une entité à partir de l'identifiant Lodel
	 * 
	 * @param int $Lodel_id valeur de l'id Lodel
	 * @return string $mets_id valeur de l'identifiant mets
	 */

	private function _get_mets_id($Lodel_id, $class) {
		global $db;

		//if ($mets_id = $db->getOne(lq("SELECT " . $this->mets_id_field . " FROM $class WHERE identity = '$Lodel_id'"))) {
			if ($mets_id = $db->getOne(lq("SELECT identifier FROM entities WHERE id = '$Lodel_id'"))) {
			return $mets_id;
		} else {
			return '';
		}
	}

	/**
	 * Retourne l'id du parent d'une entité
	 * 
	 * @param int $Lodel_id valeur de l'id Lodel
	 * @return int $idparent id du parent
	 */

	private function _get_Lodel_idparent($Lodel_id) {
		global $db;

		if ($idparent = $db->getOne(lq("SELECT idparent FROM entities WHERE id = $Lodel_id"))) {
			return $idparent;
		} else {
			return '';
		}
	}


	/**
	 * Cherche le type Lodel correspondant à un noeud mets
	 * 
	 * @param string $identifier valeur de l'identifiant mets
	 * @return int $Lodel_id id Lodel si l'entité a déjà été créée, 0 sinon
	 */

	private function _get_entity_type($div) {
		if (is_string($type = $div->getAttribute('TYPE'))) {
			return $id=$this->_get_Lodel_idtype($type);
		} else { return false; }
	}


	private function _check_types_compatibility($idtype, $idtypeparent) {
		global $db;
		$result = $db->getOne(lq("SELECT count(*) FROM entitytypes_entitytypes WHERE identitytype='$idtype' AND identitytype2='$idtypeparent'"));
		if ($result == 1) {
			return true;
		} else {
			return false;
		}
	}

	private function _execute_Lodel_request($request='') {
		$request = array_merge($this->request, $request);
		if ($request['idparent'] > 0) {
			echo memory_get_usage() .'<p>';
			//print_r($request);
			$controleur = new controler (array('entities_edition'), 'entities_edition', $request);
			unset($request);
		} else {
			return;
		}
	}


	private function _log_error($txt, $level) {
		$conf = array('mode' => 0600);
		$logfile = &Log::singleton('file', $this->file_log, '', $conf);
		$logfile->log(utf8_encode($txt), $this->error_levels[$level]);
	}

	private function _log_dberror($from) {
		global $db;
		$this->_log_error($from . ': ' . $db->errormsg(), FATAL);
		die('Database error');
	}


/******************** Initialisation (Lodel, METS) ********************/

	/**
	 * Paramètres pour l'insertion d'une entité dans Lodel
	 *
	 * @return array 
	 */

	private function _init_Lodel_request() {
		$this->request['do'] = 'edit';
		$this->request['lo']= 'entities_edition';
		$this->request['creationmethod'] = 'importXML;multidoc';
		$this->request['creationinfo'] = 'mets';
		$this->request['multidoc'] = true;	
		$this->request['edit'] = 1;
		$this->request['next_entity'] = 'yes';
	}


	/**
	 * Détection des espaces de noms utilisés dans le XML : retourne normalement au moins mets + dc + dcterms
	 * ex : $ns = array (
	 *	'mets' => 'http://www.loc.gov/METS/',
	 *	'dc' => 'http://purl.org/dc/elements/1.1/',
	 *	'dcterms' => 'http://purl.org/dc/terms/');
	 * @param object $mets_file le mets à analyser
	 * @todo vérifier que les 3 (mets, dc, dcterms) sont disponibles dans le fichier
	 */

	private function _get_namespaces($mets_file) {
		$this->namespaces = $mets_file->getNamespaces(true);
	}


/******************** FIN initialisation (Lodel, METS) ********************/


/******************** Gestion des fichiers ********************/
	/**
	 * Liste les partenaires : classe Lodel 'partner' (la seule dont les types peuvent être insérés à la racine)
	 * Les partenaires doivent être au préalable insérés via l'interface de Lodel, à la racine du site
	 *
	 * @todo utiliser une variable pour stocker le nom de la table (partner) OU considérer que c'est le seul type autorisé à la racine
	 * @return array info
	 */

	public function get_partners() {
		global $db;

		$result = $db->execute(lq("SELECT identity, importdirectory, metsdirectory, dcdirectory FROM #_TP_entities e JOIN #_TP_relations r ON e.id=r.id2 JOIN #_TP_partner p ON e.id=p.identity WHERE r.id1=0 AND r.degree=1")) or $this->_log_dberror(__METHOD__);
		$racines = array();

		while (!$result->EOF) {
			$identity = $result->fields['identity'];
			$racines[$identity]['Lodel_id'] = $result->fields['identity'];
			$racines[$identity]['import_directory'] = $result->fields['importdirectory'];
			$racines[$identity]['mets_directory'] = $result->fields['metsdirectory'];
			$racines[$identity]['dc_directory'] = $result->fields['dcdirectory'];
			$result->MoveNext();
		}
		
		if (!empty($racines)) { return $racines; }
		else {
			$this->_log_error(__METHOD__ . 'Aucun partenaire à la racine du site', FATAL);
			return false;
			}
	}


	/**
	 * Liste les répertoires contenus dans le dossier du partenaire (un répertoire = une revue)
	 *
	 * @param string $partner_dir chemin absolu du répertoire du partenaire
	 * @return array liste des répertoires accessibles en lecture s'il y en a
	 * @return bool false  si aucun répertoire accessible en lecture
	 */

	public function get_revues_dir($partner_dir) {
		if (is_dir($partner_dir) && is_readable($partner_dir)) {
			$list_files = @scandir($partner_dir);
			$revues = array();

			foreach ($list_files as $file) {
				if (is_dir("$partner_dir/$file") && is_readable("$partner_dir/$file") && $file != '.' && $file != '..'){
					$revues[] = "$partner_dir/$file";
				}
			}
			if (empty($revues)) {
				$this->_log_error("Aucun répertoire dans $partner_dir : répertoire partenaire ignoré", WARN);
			}
			return $revues;
		} else {
			$this->_log_error("Impossible de scanner $partner_dir : répertoire partenaire ignoré", WARN);
			return false;
		}
	}


	/**
	 * Retourne la liste des fichiers XML d'une revue, en fonction du format xml demandé
	 * Chaque répertoire de revue doit contenir un répertoire par format.
	 * Les noms des répertoires sont renseignés dans Lodel (édition du partenaire), et identiques pour toutes les revues d'un même partenaire
	 *
	 * @param string $format format xml (mets, dc, etc.)
	 * @return array liste des fichiers xml accessibles en lecture
	 */

	private function _get_revue_files($format) {
		$revue_dir = $this->revue['directory'] . '/' . $this->revue[$format];
		
		if (is_dir($revue_dir) && is_readable($revue_dir)) {
			$list_files = @scandir($revue_dir);
			$files = array();
			//$pattern = '#^\w*_mets\w*\.xml#';
			foreach ($list_files as $key=>$value) {
				if (is_file("$revue_dir/$value") === true && is_readable("$revue_dir/$value")
					&& $value != '.' && $value != '..'){
					$files[] = "$revue_dir/$value";
				}
			}
			natsort($files);
			$list_file = array_values($files);
			if (!empty($list_file)) {
				return $list_file;
			} else {
				$this->_log_error("Aucun fichier $format dans $revue_dir : répertoire revue ignoré", WARN);
				return false;
			}
		} else {
			$this->_log_error("Impossible de scanner $revue_dir : répertoire revue ignoré", WARN);
			return false;
		}
	}


	/**
	 * Retourne dans une chaîne le contenu des fichiers XML-METS d'une revue
	 *
	 * @param string $revue_dir chemin du répertoire où sont stockés les fichiers xml
	 * @return string Concaténation du contenu des fichiers
	 */

	private function _get_revue_mets($oai = true) {
		
		if (is_array($files = $this->_get_revue_files('mets')) && !empty($files)) {
			$files_count = count($files);
			
			if ($files_count > 2) {

				// premier fichier
				$mets = file_get_contents($files[0]);
				if (($end = stripos($mets, '<resumptionToken')) != false) {
					$mets = substr($mets, 0, $end);

					// fichiers intermédiaires
					for ($i=1; $i<$files_count-1; $i++) {//echo $files[$i] . '<p>';
						$content = file_get_contents($files[$i]);
						if (($debut = stripos($content, '<record>')) != false
							&& ($end = stripos($content, '<resumptionToken')) != false) {
							$lenght = $end - $debut;
							$content = substr($content, $debut, $lenght);
							$mets .= $content;
						} else { 
							echo 'erreur dans le fichier' . $files[$i] . "$debut -- $end<p>";
							return false;
						}
					}
				
					// dernier fichier
					$content = file_get_contents($files[$files_count-1]);
					if (($debut = stripos($content, '<record>')) != false) {
						$content = substr($content, $debut);
						$mets .= $content;
					} else { echo 'erreur pour tronquer début du dernier fichier :' . $files[$files_count];
						return false;
						
					}
				//echo $mets;
				return $mets;

				} else { echo 'erreur pour tronquer fin du premier fichier';
					return false; 
				}
			} else {
				// todo : traiter les cas où $files_count =< 2
			}
		} else {
			return false;
		}
	}

/******************** FIN gestion des fichiers ********************/



}

?>