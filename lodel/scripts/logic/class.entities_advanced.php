<?php
/**	
 * Logique des entités - avancée
 *
 * PHP version 5
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
 * Classe de logique des entités (gestion avancée)
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
class Entities_AdvancedLogic extends Logic
{

	/**
	 * Tableau des équivalents génériques
	 *
	 * @var array
	 */
	public $g_name;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct("entities");
	}


	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context, &$error)
	{
		if ($error) {
			return;
		}
		recordurl();

		$id = @$context['id'];
		if (!$id) {
			trigger_error("ERROR: give the id ", E_USER_ERROR);
		}
		if (!rightonentity('advanced', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}

		$vo  = $this->_getMainTableDAO()->getById($id);
		if (!$vo) {
			trigger_error("ERROR: can't find object $id in the table ". $this->maintable, E_USER_ERROR);
		}
		$this->_populateContext($vo, $context);

		$votype  = DAO::getDAO('types')->getById($vo->idtype);
		$this->_populateContext($votype, @$context['type']);

		// look for the source
		$context['sourcefile'] = file_exists(SITEROOT."lodel/sources/entite-".$id.".source");

		// look for a multi-doc source ?
		$context['multidocsourcefile'] = file_exists(SITEROOT. "lodel/sources/entite-multidoc". $vo->idparent. ".source");

		return '_ok';
	}

	/**
	 * Changer le status d'une entité
	 *
	 * Modifie le status d'une entité en utilisant la valeur passée dans le context :
	 * $context['status'].
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeStatusAction(&$context, &$error)
	{
		global $db;
		$status = @$context['status'];
		$this->_isAuthorizedStatus($status);
		$dao = $this->_getMainTableDAO();
		$id = @$context['id'];
		$vo  = $dao->find("id='".$id. "' AND status*$status>0 AND status<16", 'status,id');
		if (!$vo) {
			trigger_error("ERROR: interface error in Entities_AdvancedLogic::changeStatusAction ", E_USER_ERROR);
		}
		$vo->status = $status;
		$dao->save($vo);
	
		// check if the entities have an history field defined
		$this->_processSpecialFields('history', $context, $status);
	
		update();
		return '_back';
	}

	/**
	 * Préparation du déplacement  d'une entité.
	 *
	 * Cette méthode est appelée avant l'action move. Elle prépare le déplacement en vérifiant
	 * certaines conditions à celui-ci.
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function prepareMoveAction(&$context, &$error)
	{
		if (!rightonentity('move', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}
		if(function_exists('loop_move_right')) return 'move';
		/**
		 * Boucle permettant de savoir si on a le droit de déplacer l'entité IDDOCUMENT (identifiée
		 * par son type IDTYPE) dans l'entité courante.
		 * On teste si le type de l'entité courante peut contenir le type de l'entité ID.
		 * On doit aussi tester si l'entité courante n'est pas un descendant de IDDOCUMENT
		 *
		 * @param array $context le context passé par référence
		 * @param string $funcname le nom de la fonction
		 */
		function loop_move_right(&$context,$funcname)
		{
			static $cache,$idtypes;
			global $db;
			$context['iddocument'] = @$context['iddocument'];
			$context['idtype'] = @$context['idtype'];
			$context['id'] = @$context['id'];
			//test1 : si le type de l'entité courante peut contenir ce type d'entité
			if (!isset($cache[$context['idtype']])) {
				//mise en cache du type du document
				$idtype = $idtypes[$context['iddocument']];
				if (!$idtype) { // get the type, we don't have it!
					$dao = DAO::getDAO("entities");
					$vo = $dao->getById($context['iddocument'],"idtype");
					$idtype = $idtypes[$context['iddocument']]=$vo->idtype;
				}
				// récupère la condition sur les deux types testé.
				$condition = $db->getOne(lq("SELECT cond FROM #_TP_entitytypes_entitytypes WHERE identitytype='". $idtype. "' AND identitytype2='". $context['idtype']. "'"));
				$cache[$context['idtype']] = (bool)$condition;
				if ($db->errorno()) {
					trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
			//test2 : si l'entité courante est une descendante de l'entité IDDOCUMENT
			function_exists('isChild') || include 'entitiesfunc.php';
			$boolchild = isChild($context['iddocument'], $context['id']);
			if (!empty($cache[$context['idtype']]) && $boolchild) { //si c'est ok
				if (function_exists("code_do_$funcname")) {
					call_user_func("code_do_$funcname", $context);
				}
			} else {
				if (function_exists("code_alter_$funcname")) {
					call_user_func("code_alter_$funcname", $context);
				}
			}
		}
		return 'move';
	}

	/**
	 * Déplacer une entité
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function moveAction(&$context, &$error)
	{
		global $db;
		if (!rightonentity('move', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}

		$id = @$context['id']; // which entities
		$idparent = @$context['idparent']; // where to move it

		function_exists('checkTypesCompatibility') || include 'entitiesfunc.php';
		if (!checkTypesCompatibility($id, $idparent)) {
			trigger_error("ERROR: Can move the entities $id into $idparent. Check the editorial model.", E_USER_ERROR);
		}

		// yes we have the right, move the entities
		$dao = $this->_getMainTableDAO();
		$dao->instantiateObject($vo);
		$vo->id       = $id;
		$vo->rank     = 0; // recalculate
		$vo->idparent = $idparent;
		$dao->save($vo);

		if ($db->affected_Rows()>0) { // effective change
			// get the new parent hierarchy
			$result=$db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='$idparent' AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$parents = array();
			$values = '';
			$dmax   = 0;
			while (!$result->EOF) {
				$id1 = $result->fields['id1'];
				$degree = $result->fields['degree'];
				$parents[$degree] = $id1;
				if ($degree>$dmax) {
					$dmax = $degree;
				}
				$values.= "('". $id1. "','". $id. "','P','". ($degree+1). "'),";
				$result->MoveNext();
			}
			$parents[0] = $idparent;
			// search for the children
			$result=$db->execute(lq("SELECT id2,degree FROM #_TP_relations WHERE id1='$id' AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			$delete = '';
			while (!$result->EOF) {
				$id2 = $result->fields['id2'];
				$degree = $result->fields['degree'];
		
				$delete.= " (id2='".$id2."' AND degree>".$degree.") OR "; // remove all the parent above $id.
				for ($d=0; $d<=$dmax; $d++) { // for each degree
					$values.= "('".$parents[$d]."','".$id2."','P','".($degree+$d+1)."'),"; // add all the parent
				}
				$result->MoveNext();
			}
	
			$delete.= " id2='".$id."' ";
			$values.= "('".$idparent."','".$id."','P',1)";
	
			// delete the relation to the parent 
			if ($delete) {
				$db->execute(lq("DELETE FROM #_TP_relations WHERE (".$delete.") AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if ($values) {
				$db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}

		update();
		return '_back';
	}

	/**
	 * Récuperer le fichier source correspondant à une entité
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function downloadAction(&$context, &$error)
	{
		$id = @$context['id'];
		$context['type'] = @$context['type'];
		$multidoc = false;
		switch($context['type']) {
		case 'xml':
			trigger_error('a implementer', E_USER_ERROR);
			$filename = "r2r-$id.xml";
			$originalname = $filename;
			$dir = '../txt';
			break;
		case 'multidocsource' :
			$multidoc = true;
		case 'source' :
			$filename = $multidoc ? "entite-multidoc-$id.source" : "entite-$id.source";
			$dir = "../sources";
			// get the official name 
			$vo  = $this->_getMainTableDAO()->getById($id,"creationmethod,creationinfo");
			if ($vo->creationmethod!=($multidoc ? "servoo;multidoc" : "servoo")) {
				trigger_error("ERROR: error creationmethod is not compatible with download", E_USER_ERROR);
			}
			$originalname = $vo->creationinfo ? basename($vo->creationinfo) : basename($filename);
			break;
		default:
			trigger_error("ERROR: unknow type of download in downloadAction", E_USER_ERROR);
		}
		
		if (!file_exists($dir."/".$filename)) {
			trigger_error("ERROR: the filename $filename does not exists", E_USER_ERROR);
		}
		download ($dir. '/'. $filename, $originalname);
		exit;
	}

	// begin{publicfields} automatic generation  //
	/**
	 * @access private
	 */
	protected function _publicfields() {
		return array();
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //


} // class 
?>