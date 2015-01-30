<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des options du OTX
 */

/**
 * Classe de logique de la configuration de OTX
 */

class OTXConfLogic extends UserOptionGroupsLogic {

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct();
	}


	/**
		* list Action
		*/
	public function listAction(&$context,&$error)
	{ 
		$this->_getGroup($context);
		return parent::listAction($context,$error);
	}

	/**
		* view Action
		*/
	public function viewAction(&$context,&$error)
	{ 
		$this->_getGroup($context);
		return parent::viewAction($context,$error);
	}

	/**
		* add/edit Action
		*/
	public function editAction(&$context,&$error, $clean=false)
	{ 
		$this->_getGroup($context);
		$ret=parent::editAction($context,$error);

		if ($ret=="_error") return $ret;

		$client = new OTXClient();
		$error = array();
		$user = C::get('id', 'lodeluser').';'.C::get('name', 'lodeluser').';'.C::get('rights', 'lodeluser');
		$site = C::get('site', 'cfg');
		$i = 0;
		$request = array('mode' => 'hello', 'request' => '', 'attachment' => '', 'schema' => '');
		do
		{
			$options = $client->selectServer($i);
			if(!$options) break;
			$options['lodel_user'] = $user;
			$options['lodel_site'] = $site;
			$client->instantiate($options);
			if($client->error)
				$error[] = $client->status;
			else
			{
				$client->request($request);
				if(!$client->error)
					break;
				$error[] = $client->status;
			}
			++$i;
		} while (1);

		if(count($error) === $i)
		{
			$error['OTX']=join('<br/>', $error);
			return "_error";
		}

		return $ret=="_ok" ? "edit_options" : $ret;
	}


	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/
	function _getGroup(&$context)
	{
		$vo=DAO::getDAO("optiongroups")->find("name='OTX'");
		$context['id']=$vo->id;

#    if (!$context['id']) {
#      // little hack... should be in the model anyway
#      $db->execute(lq("INSERT INTO #_TP_optiongroups (name,title,logic,status,exportpolicy) VALUES ('OTX','OTX','OTXconf',1,1)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
#      $context['id']=$db->Insert_ID();
#
#      $db->execute(lq("INSERT INTO #_TP_options (name,title,type,userrights,idgroup,status,rank) VALUES ('url','url','url',40,".$context['id'].",32,1),('username','username','tinytext',40,".$context['id'].",32,2),(3,1,'passwd','password','passwd',40,".$context['id'].",32,3)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
#    }
	}

	// begin{publicfields} automatic generation  //
	function _publicfields() {
		return array("name"=>array("text","+"),
									"title"=>array("text","+"),
									"idgroup"=>array("int","+"),
									"type"=>array("select",""),
									"userrights"=>array("select","+"),
									"defaultvalue"=>array("text",""),
									"comment"=>array("longtext",""));
						}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

		function _uniqueFields() {  return array(array("name","idgroup"),);  }
	// end{uniquefields} automatic generation  //
} // class 