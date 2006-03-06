<?php
/**
 * Fichier de la classe Controler
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

require_once "auth.php" ;

// {{{ class
/**
 * Classe gérant la partie contrôleur du modèle MVC utilisé par Lodel 0.8
 * 
 * 
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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
	 * 
	 */
	function Controler($logics, $lo = "") 
	{
		global $home, $context;
		if ($_POST) {
			$therequest = &$_POST;
		} else {
			$therequest = &$_GET;
		}
		#print_r($therequest);
		$do=$therequest['do'];
		if ($do=="back") {
			require_once "view.php";
			View::back(2); //revient 2 rang en arrière dans l'historique.
			return;
		}

		require_once "func.php";
		extract_post($therequest);

		if ($do) {
			if ($therequest['lo']) $lo = $therequest['lo'];
			if ($lo!="texts" && !in_array($lo,$logics)) trigger_error("ERROR: unknown logic", E_USER_ERROR);
			$context['lo'] = $lo;

			// get the various common parameters
			foreach (array("class", "classtype", "type", "textgroups") as $var) {
				if ($therequest[$var]) {
					require_once "validfunc.php";
					if (!validfield($therequest[$var],$var))
						die("ERROR: a valid $var name is required");
					$context[$var] = $therequest[$var];
				}
			}
			// ids. Warning: don't remove this, the security in the following rely on these ids are real int.
			foreach (array("id", "idgroup", "idclass", "idparent") as $var) {
				$context[$var] = intval($therequest[$var]);
			}
      // dir
			if ($therequest['dir'] && ($therequest['dir'] == "up" || 
					$therequest['dir'] == "down" || 
					is_numeric($therequest['dir']))) 
				$context['dir'] = $therequest['dir'];

			// valid the request
			if (!preg_match("/^[a-zA-Z]+$/", $do)) 
				die("ERROR: invalid action");
			$do = $do. "Action";
			
			require_once "logic.php";
		
			switch($do) {
			case 'listAction' :
				recordurl();
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
			if (!$ret) die("ERROR: invalid return from the logic.");

			// create the view
			require_once "view.php";
			$view = &View::getView();
			switch($ret) {
			case '_back' :
				$view->back();
				break;
      case '_error' :
				// hum... needs to remove the slashes... don't really like that, because some value may still 
				// come from  database or lodel. Doing this way is not a security issue but may forbide
				// user to use \' in there text
				require_once "func.php";
				mystripslashes($context);
				$logic->viewAction($context, $error); // in case anything is needed to be put in the context
				$context['error'] = $error;
				print_r($error);
			case '_ok' :
				if ($do == "listAction") {
					$view->renderCached($context, $lo);
				} else {
					$view->render($context, "edit_". $lo);
				}
				break;
			default:
				if (strpos($ret,"_location:")===0) {
					header(substr($ret,1));
					exit();
				}
				$view->render($context, $ret);
			}
		} else {
			recordurl();
			require_once "view.php";
			$view = &View::getView();
			$view->renderCached($context,"index");
		}
  } // constructor }}}

} // }}}
?>