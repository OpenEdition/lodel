<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des fonctionnalités XML
 */

/**
 * Classe de logique des fonctionnalités XML
 */
class XMLLogic extends Logic {

	/** 
	* Constructor
	*/
	public function __construct() 
	{
		parent::__construct("translations");
	}
	
	/**
	* Generate the XML for an entity.
	* Must have context['id'] given
	*/
	public function generateXMLAction (&$context, &$error) 
	{
		if (empty($context['id']))
			trigger_error('ERROR : no id given. Id attribute is required to generate XML file', E_USER_ERROR);

		global $db;
		$id = (int) $context['id'];
		//check if the given id is OK
		$row = $db->getOne(lq("SELECT 1 FROM #_TP_objects WHERE id='$id' AND class='entities'"));
		if (!$row) {//if the object is not an entity do not generate XML
			header ("Location: not-found.html"); return;
		}
		$row = $db->getRow(lq("SELECT e.id,t.type,t.class FROM #_TP_entities as e, #_TP_types as t WHERE e.id='$id' AND e.idtype=t.id"));
		if (!$row) trigger_error("Error. Type and Class unknown for this entity", E_USER_ERROR);
		
		$context['class'] = $class = $row['class'];
		$context['type'] = $row['type'];
		$context['identity'] = $id;

		function_exists('calculateXML') || include 'xmlfunc.php';
		$context['contents'] =  $contents = calculateXML ($context);

		if (isset($context['view']))	{
			return "_ok";
		} else  {// "download"
			download ("", "$class-$id.xml", $contents);
            exit();
		}
	}
	/**
	* Generate the XSD Schema for a class
	* Must have context['class'] given
	*/
	public function generateXSDAction (&$context, &$error) {
		
		if (empty($context['class']))
			trigger_error('ERROR: no class given. Class attribute is required to generate XSD Schema', E_USER_ERROR);
		function_exists('calculateXML') || include 'xmlfunc.php';
		//verif if the given class is OK
		global $db;
		$class = addslashes($context['class']);
		$row = $db->getOne (lq ("SELECT id FROM #_TP_classes WHERE class='$class'"));
		if (!$row) {
			header ("Location: not-found.html"); return;
		}
		$originalname = C::get('site', 'cfg'). '-'.$context['class']. '-schema-xml.xsd';
		$ret = calculateXMLSchema ($context);
		download ("", $originalname, $ret);
        exit();
	}
}