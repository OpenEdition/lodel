<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des tâches
 */

/**
 * Classe de logique des tâches
 *
 */
class TasksLogic extends Logic {

	/**
	* generic equivalent assoc array
	*/
	public $g_name;


	/** Constructor
	*/
	public function __construct() {
		parent::__construct("tasks");
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context,&$error)
	{
		trigger_error("TasksLogic::viewAction", E_USER_ERROR);
	}

	/**
	* Ajoute une tâche dans la table
	*
	* On profite de la création de la tâche, pour effacer les anciennes, une tâche prenant énormemment de place en base
	*
	* @param string $name le nom de la tâche
	* @param string $etape l'étape courante
	* @param array (ou string) $context le context courant pour l'étape: soit un tableau qui sera serialise soit une chaine deja serialise
	* @return integer l'identifiant inséré (si c'est le cas)
	*/
	public function createAction($name, $step, &$context)
	{
		$this->deleteOldTasks();
		global $db;
		if (is_array($context))
			$context = base64_encode(serialize($context));
		$db->execute(lq("INSERT INTO #_TP_tasks (name,step,user,context) VALUES ('$name','$step','".C::get('id', 'lodeluser')."','$context')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		return $db->insert_ID();
	}

	/**
	* Retrouve une tâche suivant son identifiant
	*
	* @param integer &$id l'identifiant de la tâche
	* @param array retourne les informations concernant la tâche
	*/
	public function getTask(& $id)
	{
		global $db;
		$id = (int)$id;
		$row = $db->getRow(lq("SELECT * FROM #_TP_tasks WHERE id='$id' AND status>0"));
		if ($row === false)
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if (!$row)
			return false;

		$context = @unserialize(base64_decode($row['context']));
		if (is_array($context))
			$row = array_merge($row, $context);
		return $row;
	}

	/**
	* Suivant que le document est importé ou réimporté, les informations dans la tâche sont
	* différentes. Cette fonction uniformise ces informations dans le context ($context).
	*
	* Depending the document is imported or re-imported, the information in task are different.
	* This function uniformize the information in the context
	*
	* @param array $task un tableau contenant les infos d'une tâche
	* @param array &$context le context dans lequel seront mis à jour les informations
	*/
	public function populateContext($task, & $context)
	{
		global $db;
		if (isset($task['identity']) && $task['identity']) {
			$row = $db->getRow(lq("SELECT class,idtype,idparent FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='".$task['identity']."'"));
			if ($db->errorno())
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$context['class'] = $row['class'];
			$context['idtype'] = $row['idtype'];
			$context['idparent'] = $row['idparent'];
			if (!$context['class'])
				trigger_error("ERROR: can't find entity ".$task['identity']." in populateContext", E_USER_ERROR);
		} else {
			if (!isset($task['idtype']) || !($idtype = $task['idtype']))
				trigger_error("ERROR: idtype must be given by task in populateContext", E_USER_ERROR);
			// get the type 
			$votype = DAO::getDAO("types")->getById($idtype, "class");
			$context['idtype'] = $task['idtype'];
			$context['class'] = $votype->class;
		}
	}

	protected function _prepareDelete($dao, &$context) {
		$task = $this->getTask($context['id']);

		if (!empty($task['tmpdirs'])) {
			foreach($task['tmpdirs'] as $dir) {
				rmtree($dir);
			}
		}
	}

	/**
	* Dertemine la date à laquelle une tâche est considérée comme obsolete
	*
	* @param int $age age en seconde des tâches à effacer
	*/
	private function getOldDate($age = false) {
		$age = $age!==false ? (int) $age : (60 * 60 * 4);
		return date("Y-m-d H:i:s", time() - $age);
	}

	/**
	* Éliminer les vieilles tâches en se limitant dans le temps
	* Fonction publique pour pouvoir scripter l'effacement
	*
	* @param int $age age en seconde des tâches à effacer
	* @param int $duration durée maximale de l'effacement des tâches
	*/
	public function deleteOldTasks($age=false, $duration=3) {
		$time = time();
		$limit = $time + $duration;
		$since = $this->getOldDate($age);

		$dao = $this->_getMainTableDAO();
		while ($time < $limit) {
			$vos = $dao->findMany("upd < '$since'", 'upd', 'id,upd', 1);
			if (empty($vos)) break;

			$taskContext = array('id'=>$vos[0]->id);
			$this->deleteAction($taskContext, $error);

			$time = time();
		}
	}

	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		trigger_error("TasksLogic::changeRankAction", E_USER_ERROR);
	}

	/**
		* add/edit Action
		*/

	public function editAction(&$context,&$error, $clean=false)
	{ trigger_error("TasksLogic::editAction", E_USER_ERROR); }

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/


	/**
	*  Indique si un objet est protégé en suppression
	*
	* Cette méthode indique si un objet, identifié par son identifiant numérique et
	* éventuellement son status, ne peut pas être supprimé. Dans le cas où un objet ne serait
	* pas supprimable un message est retourné indiquant la cause. Sinon la méthode renvoit le
	* booleen false.
	*
	* @param integer $id identifiant de l'objet
	* @param integer $status status de l'objet
	* @return false si l'objet n'est pas protégé en suppression, un message sinon
	*/
	public function isdeletelocked($id,$status=0)
	{
		$id = (int)$id;
		// basic check. Should be more advanced because of the potential conflict between adminlodel and other users
		// everyone can delete old tasks
		$tooOld = $this->getOldDate();
		$vo=$this->_getMainTableDAO()->find("id='".$id."' AND (user='".C::get('id', 'lodeluser')."' OR upd<'$tooOld')","id");
		return ($vo && $vo->id) ? false : true ;
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array();
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //
} // class 