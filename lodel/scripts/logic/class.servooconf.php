<?php
/**	
 * Logique des options du servOO
 *
 * PHP versions 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
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
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

/**
 * Classe de logique de la configuration de ServOO
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */

class ServOOConfLogic extends UserOptionGroupsLogic {

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
			$error['servoo']=join('<br/>', $error);
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
		$vo=DAO::getDAO("optiongroups")->find("name='servoo'");
		$context['id']=$vo->id;

#    if (!$context['id']) {
#      // little hack... should be in the model anyway
#      $db->execute(lq("INSERT INTO #_TP_optiongroups (name,title,logic,status,exportpolicy) VALUES ('servoo','Servoo','servooconf',1,1)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
?>