<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */


/**
 * Classe de logique des styles de caractères - Fille de la classe Logic
 */
class CharacterstylesLogic extends Logic
{

	/**
	 *Constructeur de classe
	 */
	public function __construct() 
	{
		parent::__construct('characterstyles');
	}

	/**#@+
	 * @access private
	 */

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array(	'style' => array('type', '+'),
				'conversion' => array('text', ''),
				'otx' => array('xpath', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('style'), array('otx'));
	}
	// end{uniquefields} automatic generation  //
	/**#@-*/

} // class 