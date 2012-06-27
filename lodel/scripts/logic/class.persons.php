<?php
/**	
 * Logique des personnes
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
 * @author Sophie Malafosse
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
 * Classe de logique des personnes
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
 * @since Classe ajoutée depuis la version 0.8
 * @see logic.php
 */
class PersonsLogic extends EntriesLogic
{

	/** Constructor
	 */
	public function __construct() 
	{
		parent::__construct('persons');
		$this->daoname = 'persontypes';
		$this->idtype = 'idperson';
	}

		
	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Ajout d'un nouvel objet persons
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function editAction (&$context, &$error, $clean=false) 
	{
		if (empty($context['idtype'])) trigger_error("ERROR: internal error in PersonsLogic::editAction", E_USER_ERROR);

		$id = (int) (isset($context['id']) ? $context['id'] : null);
		$idtype = (int) $context['idtype'];
		$status = isset($context['status']) ? $context['status'] : null;
#echo "status=$status"; print_r ($context);
		// get the class 
		$daotype=DAO::getDAO ("persontypes");
		$votype=$daotype->getById ($idtype, "class");
		if(!$votype)
			trigger_error('ERROR: invalid idtype', E_USER_ERROR);

		$class=$context['class']=$votype->class;
		#print_r($context);
		if ($clean!='CLEAN') {
			if (!$this->validateFields ($context, $error)) {
			// error.
			// if the entity is imported and will be checked
			// that's fine, let's continue, if not return an error
				if ($status>-64) return "_error";
			}
		}
		foreach (array ("firstname", "familyname") as $g) {
			$equiv[$g]=$this->getGenericEquivalent($class,$g);
			if (!$equiv[$g]) trigger_error ("ERROR: The generic field $g is required. Please edit your editorial model.",E_USER_ERROR);
			$$g=trim($context['data'][$equiv[$g]]);
		}

		if (empty($familyname) && empty($firstname)) { $error[$equiv['familyname']]="+"; return "_error"; }
		// get the dao for working with the object
		$dao=$this->_getMainTableDAO();
		
		$vo = false;

		if (!$id && ($familyname || $firstname)) {
			// search if the person exists
			// ajout de slashes pour le SELECT dans la base 
			$tmpfirstname = $firstname;
			$tmpfamilyname = $familyname;
			myaddslashes($tmpfirstname);
			myaddslashes($tmpfamilyname);
			$vo=$dao->find ("g_familyname='". $tmpfamilyname. "' AND g_firstname='". $tmpfirstname. "'  AND idtype='".$idtype."' AND status>-64","id,status");
			//$vo=$dao->find ("g_familyname='". $familyname. "' AND g_firstname='". $firstname. "'  AND idtype='".$idtype."' AND status>-64","id,status");
			if($vo && abs($vo->status) == 32) $context['protected'] = 1; //if protected
			$new=false;
		}

		if (!$vo) {
			if ($id) { //edit
				$new=false;
				$dao->instantiateObject($vo);
				$vo->id=$id;
			} else { //create
				$new=true;
				$vo=$dao->createObject();
				$vo->status=$status ? $status : -1;
			}
		}
		// populate the persons table
		if (isset($dao->rights['protect']) && $dao->rights['protect']) $vo->protect=isset($context ['protected']) ? 1 : 0;
		if ($idtype) $vo->idtype=$idtype;
		$vo->g_firstname=$firstname;
		$vo->g_familyname=$familyname;
		$vo->sortkey=makeSortKey ($familyname." ".$firstname);
		$id=$context['id']=$dao->save($vo);
		// change the group recursively
		//if ($context['usergrouprec'] && $lodeluser['admin']) change_usergroup_rec($id,$usergroup);

		// save the class table
		$gdao=DAO::getGenericDAO ($class,"idperson");
		$gdao->instantiateObject ($gvo);
		$context['data']['id']=$context['id'];
		$this->_populateObject ($gvo,$context['data']);
		$gvo->idperson=$id;
		$this->_moveFiles ($id, $this->files_to_move, $gvo);
		$gdao->save ($gvo,$new);  // save the related table
		// save the entities_class table
		if (!empty($context['identity'])) {
			$dao=DAO::getDAO ("relations");
			$vo=$dao->find ("id1='".(int) $context['identity']. "' AND id2='". $id. "' AND nature='G' AND degree='".(int)$context['degree']. "'", "idrelation");
			$new_relation = false;
			if (!$vo) {
				$dao->instantiateObject ($vo);
				$vo->id1=(int)$context['identity'];
				$vo->id2=$id;
				$vo->degree=(int)$context['degree'];
				$vo->nature='G';
				$idrelation=$context['idrelation'] = $dao->save($vo);
				$new_relation = true;
			} else {
				$idrelation=$context['idrelation'] = $vo->idrelation;
			}

			$gdao=DAO::getGenericDAO("entities_".$class,"idrelation");
			$gdao->instantiateObject($gvo);
			$this->_populateObject($gvo,$context['data']);
			$gvo->idrelation=$idrelation;
			$gdao->save($gvo,$new_relation);  // save the related table
		}

		update();
		return "_back";
	}
}// class 