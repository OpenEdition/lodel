<?php
/**
 * Fichier de la classe Controler
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
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
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

require_once 'auth.php';

// {{{ class
/**
 * Classe gérant la partie contrôleur du modèle MVC utilisé par Lodel 0.8
 * 
 * 
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @see logic.php
 * @see view.php
 */
class Controler 
{

	/**
	 * Constructeur de la classe Controler.
	 *
	 * Ce constructeur se charge du nettoyage des variables $_POST, $_GET dans un premier temps. Puis
	 * suivant la logique appelée et l'action demandée il se charge d'appeler la bonne logique métier.
	 * Enfin, suivant le résultat de cet appel il appelle vue correspondante.
	 *
	 *
	 * exemple :
	 * <code>
	 * <?php
	 * $lo = "entities";
	 * Controler::controler(array("entities","entities_advanced","entities_edition",
	 * "entities_import", "entities_index", "filebrowser", "tasks","xml"),$lo);
	 * ?>
	 * </code>
	 *
	 * @param array $logics Les logiques métiers acceptées par le point d'entrée
	 * @param string $lo La logique métier appelée. Par défaut cette valeur est vide
	 * @param array $request La requête à traiter, si elle n'est passée ni en GET ni en POST (dans un script par ex.) : utilisé pour l'import massif de XML
	 * 
	 */
	public function Controler($logics, $lo = '', $request = array())
	{
		global $home, $context;
		
		// si la requete provient d'un script qui appelle le controleur
		if (!empty($request)) {
			$therequest =& $request;
		// GET ou POST
		} else {
			if ($_POST) {
				$therequest = &$_POST;
			} else {
				$therequest = &$_GET;
			}
		}

		// backup original context for multiple import
		if(isset($therequest['next_entity']) && 'yes' === (string)$therequest['next_entity'])
			$backupContext = $context;
		
		$do = $therequest['do'];
		
		if ($do == 'back') {
			require_once 'view.php';
			View::back(2); //revient 2 rang en arrière dans l'historique.
			return;
		}
		require_once 'func.php';
		extract_post($therequest); // nettoyage des valeurs issues de formulaire

		if ($do) {
		
			if (isset($therequest['lo'])) {
				$lo = $therequest['lo'];
				unset($therequest['lo']);
			}
			if ($lo != 'texts' && !in_array($lo, $logics)) {
				trigger_error("ERROR: unknown logic", E_USER_ERROR);
			}
			$context['lo'] = $lo;

			// get the various common parameters
			if(!function_exists('validfield')) require 'validfunc.php';
			foreach (array('class', 'classtype', 'type', 'textgroups') as $var) {
				if (isset($therequest[$var])) {
					if (!validfield($therequest[$var], $var))
						die("ERROR: a valid $var name is required");
					$context[$var] = $therequest[$var];
				}
			}

			// ids. Warning: don't remove this, the security in the following rely on these ids are real int.
			foreach (array('id', 'idgroup', 'idclass', 'idparent', 'idtype') as $var) {
				if(isset($therequest[$var]))
					$context[$var] = (int)$therequest[$var];
				else
					$context[$var] = 0;
			}

      			// dir
			if (isset($therequest['dir']) && ($therequest['dir'] == 'up' || 
					$therequest['dir'] == 'down' || 
					is_numeric($therequest['dir']))) 
			{
				$context['dir'] = $therequest['dir'];
			}
			
			// valid the request
			if (!preg_match("/^[a-zA-Z]+$/", $do)) 
				die("ERROR: invalid action");
			$do = $do. 'Action';

			if(!function_exists('getLogic')) require 'logic.php';
			// que fait-on suivant l'action demandée
			switch($do) {
				case 'listAction' :
					recordurl(); //enregistre l'url dans la pile
				#case 'viewAction' :
					#recordurl();
				default:
					$logic = &getLogic($lo);
					// create the logic for the table
					if (!method_exists($logic, $do)) {
						if ($do == 'listAction') {
							$ret = '_ok';
						} else {
							die('ERROR: invalid action');
						}
					} else {
						// call the logic action
						$ret = $logic->$do($context, $error);
					}
			}
			if (!$ret) {
				die('ERROR: invalid return from the logic.');
			}
			
			//Appel de la vue nécessaire
			require_once 'view.php';
			$view = &View::getView();
			switch($ret) {
				case '_next' : 
					// si le controleur est appelé par un script
					// nettoyage
					$GLOBALS['context'] = $backupContext;
					return 'ok';
				case '_back' :
					$view->back();
					break;
      				case '_error' :
					// hum... needs to remove the slashes... don't really like that, because some value may still 
					// come from  database or lodel. Doing this way is not a security issue but may forbide
					// user to use \' in there text
					#require_once 'func.php';
					mystripslashes($context);
					$logic->viewAction($context, $error); // in case anything is needed to be put in the context
					$context['error'] = $error;
					//print_r($error);
				case '_ok' :
					if ($do == 'listAction') {
						$view->renderCached($context, $lo);
					} else {
						$view->render($context, "edit_$lo");
					}
					break;
				default:
				if (strpos($ret, '_location:') === 0) {
					header(substr($ret, 1));
					exit;
				}
				$view->render($context, $ret);
				break;
			}
		} else {
			global $db, $lodeluser;
			// appel d'un docannexe
			if($context['file']) {
				$critere = $lodeluser['rights'] > LEVEL_VISITOR ? '' : " AND {$GLOBALS['tableprefix']}entities.status>0 AND {$GLOBALS['tableprefix']}types.status>0";
				
				$row = $db->getRow("SELECT {$GLOBALS['tableprefix']}tablefields.name, {$GLOBALS['tableprefix']}tablefields.class FROM {$GLOBALS['tableprefix']}tablefields, {$GLOBALS['tableprefix']}entities LEFT JOIN {$GLOBALS['tableprefix']}types on ({$GLOBALS['tableprefix']}entities.idtype = {$GLOBALS['tableprefix']}types.id) WHERE {$GLOBALS['tableprefix']}entities.id='{$context['id']}' AND {$GLOBALS['tableprefix']}tablefields.class = {$GLOBALS['tableprefix']}types.class AND {$GLOBALS['tableprefix']}tablefields.type = 'file'{$critere}");
				
				if($row) {
					$datepubli = $db->getRow("SELECT name FROM {$GLOBALS['tableprefix']}tablefields WHERE class = '{$row['class']}' AND name = 'datepubli'");
					if(!$datepubli) {
						$file = $db->getRow("SELECT {$row['name']} FROM {$GLOBALS['tableprefix']}{$row['class']} WHERE identity = '{$context['id']}'");
						if($file[$row['name']]) {
							download($file[$row['name']]);
							exit();
						}
					} else {
						$datepubli = $db->getRow("SELECT datepubli FROM {$GLOBALS['tableprefix']}{$row['class']} WHERE identity = '{$context['id']}'");
						$datepubli = $datepubli['datepubli'];
			
						if(!function_exists('today'))
							require 'textfunc.php';
						if(!$datepubli || $datepubli <= today() || $lodeluser['rights'] >= LEVEL_RESTRICTEDUSER) {
							$file = $db->getRow("SELECT {$row['name']} FROM {$GLOBALS['tableprefix']}{$row['class']} WHERE identity = '{$context['id']}'");
							if($file[$row['name']]) {
								download($file[$row['name']]);
								exit();
							}
						}
					}
				}
			}
			if ($context['login']) {
				require_once 'loginfunc.php';
				do {
					if (!check_auth_restricted($context['login'], $context['passwd'], $GLOBALS['site'])) {
						$context['error_login'] = $err = 1;
						break;
					}
			
					//vérifie que le compte n'est pas en suspend. Si c'est le cas, on amène l'utilisateur à modifier son mdp, sinon on l'identifie
					if(!check_expiration()) {
						$context['error_expiration'] = $err = 1;
						unset($context['lodeluser'], $lodeluser);
						break;
					} else {
						// ouvre une session
						$err = open_session($context['login']);
						if ($err) {
							$context[$err] = $err = 1;
							break;
						}
					}
					$context['passwd'] = $passwd = 0;
				} while (0);
				if($err) // une erreur : besoin de l'afficher, donc pas d'utilisation du cache
					$context['nocache'] = true;
			} 
			// ID ou IDENTIFIER
			if ($context['id'] || $context['identifier']) {
				do { // exception block
					require_once 'func.php';
					if ($context['id']) {
						$class = $db->getOne(lq("SELECT class FROM #_TP_objects WHERE id='{$context['id']}'"));
						if ($db->errorno() && $lodeluser['rights'] > LEVEL_VISITOR) {
							dberror();
						}
						if (!$class) { 
							header("HTTP/1.0 404 Not Found");
							header("Status: 404 Not Found");
							header("Connection: Close");
							if(file_exists($home."../../missing.html")) {
								include $home."../../missing.html";
							} else {
								header('Location: not-found.html');
							}
							exit; 
						}
					} elseif ($context['identifier']) {
						$class = 'entities';
					} else {
						die("?? strange");
					}
					switch($class) {
					case 'entities':
						$this->_printEntities($context['id'], $context['identifier'], $context);
						break;
					case 'entrytypes':
					case 'persontypes':
						$result = $db->execute(lq("SELECT * FROM #_TP_{$class} WHERE id='{$context['id']}' AND status>0")) or dberror();
						$context['type'] = $result->fields;
						require_once 'view.php';
						$view = &View::getView();
						$view->renderCached($context, $result->fields['tplindex']);
						exit;
					case 'persons':
					case 'entries':
						$this->_printIndex($context['id'], $class, $context);
						break;
					} // switch class
				} while(0);
			//PAGE
			} elseif ($context['page']) { // call a special page (and template)
				if (strlen($context['page']) > 64 || preg_match("/[^a-zA-Z0-9_\/-]/", $context['page'])) {
					die('invalid page');
				}
				require_once 'view.php';
				$view = &View::getView();
				$view->renderCached($context, $context['page']);
				exit;
			} else {
				//tente de récupérer le path - parse la query string pour trouver l'entité
				$query = preg_replace("/[&?](format|clearcache)=\w+/", '', $_SERVER['QUERY_STRING']);
				
				if($query && !preg_match("/[^a-zA-Z0-9_\/-]/", $query)) {
					// maybe a path to the document
					$path = preg_split("#/#", $query, -1, PREG_SPLIT_NO_EMPTY);
					$entity = end($path);
					$id = (int)$entity;
					if ($id) {
						$this->_printEntities($id, '', $context);
					}
				} else {
					// rien à faire.
				}
			}
			require_once 'view.php';
			$view = &View::getView();
			$view->renderCached($context, 'index');
		}
  	} // constructor }}}

	/**
	* Affichage d'une entité
	*
	* Affiche une entité grâce à son id, son identifiant. Appelle la vue associée
	*
	* @param integer $id identifiant de l'entité
	* @param string $identifier l'identifiant littéral de l'entité
	* @param array &$context le contexte par référence
	*/
	private function _printEntities($id, $identifier, &$context)
	{
		global $lodeluser, $home, $db;
		$context['classtype'] = 'entities';
		$critere = $lodeluser['visitor'] ? 'AND #_TP_entities.status>-64' : 'AND #_TP_entities.status>0 AND #_TP_types.status>0';
	
		// cherche le document, et le template
		do {
			if ($identifier) {
				$identifier = addslashes(stripslashes(substr($identifier, 0, 255)));
				$where = "#_TP_entities.identifier='". $identifier. "' ". $critere;
			} else {
				$where = "#_TP_entities.id='". $id. "' ". $critere;
			}
			$row = $db->getRow(lq("SELECT #_TP_entities.*,tpl,type,class FROM #_entitiestypesjoin_ WHERE ". $where));
			if ($row === false) {
				dberror();
			}
			if (!$row) { 
				header("HTTP/1.0 404 Not Found");
				header("Status: 404 Not Found");
				header("Connection: Close");
				if(file_exists($home."../../missing.html")) {
					include $home."../../missing.html";
				} else {
					header('Location: not-found.html');
				}
				exit; 
			}
			$base = $row['tpl']; // le template à utiliser pour l'affichage
			if (!$base) { 
				$id = $row['idparent'];
				$relocation = TRUE;
			}
		} while (!$base && !$identifier && $id); 
	
		if ($relocation) { 
			header('location: '. makeurlwithid('index', $row['id']));
			exit;
		}
		$context = array_merge($context, $row);
		$row = $db->getRow(lq("SELECT * FROM #_TP_". $row['class']. " WHERE identity='". $row['id']. "'"));
		if ($row === false) {
			dberror();
		}
		if (!$row) {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			header("Connection: Close");
			if(file_exists($home."../../missing.html")) {
				include $home."../../missing.html";
			} else {
				header('Location: not-found.html');
			}
			exit; 
		}
		if (!(@include_once('CACHE/filterfunc.php'))) {
			require_once 'filterfunc.php';
		}
		//Merge $row et applique les filtres définis dans le ME
		merge_and_filter_fields($context, $context['class'], $row);
		getgenericfields($context); // met les champs génériques de l'entité dans le contexte
		require_once 'view.php';
		$view=&View::getView();
		$view->renderCached($context, $base);
		exit;
	}

	/**
	* Affichage d'un objet de type index
	*
	* @param integer $id identifiant numérique de l'index
	* @param string $classtype type de la classe
	* @param array &$context le context par référence
	*/
	private function _printIndex($id, $classtype, &$context)
	{
		global $lodeluser, $home, $db;
		$context['classtype'] = $classtype;
		switch($classtype) {
		case 'persons':
			$typetable = '#_TP_persontypes';
			$table     = '#_TP_persons';
			$longid    = 'idperson';
			break;
		case 'entries':
			$typetable = '#_TP_entrytypes';
			$table     = '#_TP_entries';
			$longid    = 'identry';
			break;
		default:
			die('ERROR: internal error in printIndex');
		}
	
		// get the index
		$critere = $lodeluser['visitor'] ? 'AND status>-64' : 'AND status>0';
		$row = $db->getRow(lq("SELECT * FROM ". $table. " WHERE id='". $id. "' ". $critere));
		if ($row === false) {
			dberror();
		}
		if (!$row) {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			header("Connection: Close");
			if(file_exists($home."../../missing.html")) {
				include $home."../../missing.html";
			} else {
				header('Location: not-found.html');
			}
			exit;
		}
		$context = array_merge($context, $row);
		// get the type
		$row = $db->getRow(lq("SELECT * FROM ". $typetable. " WHERE id='". $row['idtype']. "'". $critere));
		if ($row === false) {
			dberror();
		}
		if (!$row) {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			header("Connection: Close");
			if(file_exists($home."../../missing.html")) {
				include $home."../../missing.html";
			} else {
				header('Location: not-found.html');
			}
			exit;
		}
		$base            = $row['tpl'];
		$context['type'] = $row;
	
		// get the associated table
		$row = $db->getRow(lq("SELECT * FROM #_TP_".$row['class']." WHERE ".$longid."='".$id."'"));
		if ($row === false) {
			dberror();
		}
		if (!$row) {
			die("ERROR: internal error");
		}
		if (!(@include_once("CACHE/filterfunc.php"))) {
			require_once "filterfunc.php";
		}
		merge_and_filter_fields($context, $row['class'], $row);
		require_once 'view.php';
		$view = &View::getView();
		$view->renderCached($context, $base);
		exit;
	}
} // }}}
?>