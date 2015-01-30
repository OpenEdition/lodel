<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des personnes
 */


/**
 * Classe de logique des personnes
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