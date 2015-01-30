<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilisé si la version de PHP est inférieure à 5, pour assurer la compatibilité PHP4/PHP5
 * Ce fichier doit être inclus ssi la version de PHP est inférieure à 5
 */

/**
	 * Définition de la fonction clone en PHP4, pour pouvoir utiliser la copie d'objets en PHP5
	 * 
	 *
	 * Gather information from tablefield to know what to do with the various styles.
	 * class is the context and criteria is the where to select the tablefields
	 *
	 * @param string $object le nom de l'objet à copier
	 */
function clone($object) {
	return $object;
}

?>
