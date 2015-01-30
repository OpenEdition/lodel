<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des styles internes
 */
class InternalstylesLogic extends Logic {

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct("internalstyles");
	}

	public function editAction(&$context, &$error, $clean = false)
	{
		if(empty($context['style']))
		{
			$error['style'] = '+';
			return '_error';
		}

		if(DAO::getDAO('tablefields')->find('name='.$GLOBALS['db']->quote($context['style'])))
		{
			$error['style'] = '1';
			return '_error';
		}

		return parent::editAction($context, $error);
	}

	public function makeSelect(&$context,$var)
	{
		switch($var) {
			case "surrounding" :
			$arr=array(
			"-*"=>getlodeltextcontents("previous_style","admin"),
			"*-"=>getlodeltextcontents("next_styles","admin"),
			);
			
			$dao=DAO::getDAO("tablefields");
			$vos=$dao->findMany("style!=''","style","style");
			foreach($vos as $vo) {
				if (strpos($vo->style,".")!==false || strpos($vo->style,":")!==false) continue;
				$style=preg_replace("/[;,].*/","",$vo->style); // remove the synonyms
				$arr[$style]=$style;
			}
			renderOptions($arr,isset($context['surrounding']) ? $context['surrounding'] : '');
			break;
		}
	}


	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/




	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('style' => array('style', '+'),
									'conversion' => array('text', ''),
									'surrounding' => array('select', '+'),
									'greedy' => array('boolean', ''),
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
} // class 