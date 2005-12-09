<?php
/**
 * Fichier de la classe GenericDAO
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
 * @version CVS:$Id:
 */

/**
 * Classe GenericDAO
 *
 * <p>Cette classe permet de gérer les éléments génériques de l'interface</p>
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @since Classe ajoutée depuis la version 0.8
 * @see genericlogic.php
 */

class genericDAO extends DAO
{
	/**
	 * Constructeur
	 *
	 * @param string $table la table SQL de la DAO
	 * @param string $idfield Indique le nom du champ identifiant
	 */
	function genericDAO($table, $idfield)
	{
		$this->DAO($table, false, $idfield); // create the class
	}

	/**
	 * Instantie un nouvel objet virtuel (VO)
	 *
	 * Instantiate a new object
	 *
	 * @param object &$vo l'objet virtuel qui sera instancié
	 */
	function instantiateObject(&$vo)
	{
		static $def;
		$classname = $this->table."VO";
		if (!$def[$classname]) {
			eval ("class ". $classname. " { var $". $this->idfield. "; } ");
			$def[$classname] = true;
		}
		$vo = new $classname; // the same name as the table. We don't use factory...
	}
	/**
	 * Retourne les droits correspondant à un access particulier
	 *
	 * @access private
	 * @param string $access l'accès pour lequel on veut les droits
	 * @return Retourne une chaîne vide.
	 */
	function _rightscriteria($access)
	{
		return '';
	}
}
?>