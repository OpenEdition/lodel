<?php
/**	
 * Logique des entités - avancée
 *
 * PHP version 4
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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
	var $g_name;

	/**
	 * Constructeur
	 */
	function Entities_AdvancedLogic()
	{
		$this->Logic("entities");
	}


	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function viewAction(&$context, &$error)
	{
		if ($error) {
			return;
		}
		recordurl();

		$id = $context['id'];
		if (!$id) {
			die("ERROR: give the id ");
		}
		if (!rightonentity('advanced', $context)) {
			die("ERROR: you don't have the right to perform this operation");
		}

		$dao = $this->_getMainTableDAO();
		$vo  = $dao->getById($id);
		if (!$vo) {
			die("ERROR: can't find object $id in the table ". $this->maintable);
		}
		$this->_populateContext($vo, $context);

		$daotype = &getDAO('types');
		$votype  = $daotype->getById($vo->idtype);
		$this->_populateContext($votype, $context['type']);

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
	function changeStatusAction(&$context, &$error)
	{
		global $db;
		$status = intval($context['status']);
		$dao = $this->_getMainTableDAO();
		$vo  = $dao->find("id='". $context['id']. "' AND status*$status>0 AND status<16", 'status,id');
		if (!$vo) {
			die("ERROR: interface error in Entities_AdvancedLogic::changeStatusAction ");
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
	function prepareMoveAction(&$context, &$error)
	{
		if (!rightonentity('move', $context)) {
			die("ERROR: you don't have the right to perform this operation");
		}
		function loop_move_right(&$context,$funcname)
		{
			static $cache,$idtypes;
			global $db,$home;
			if (!isset($cache[$context['idtype']])) {
				$idtype = $idtypes[$context['iddocument']];
				if (!$idtype) { // get the type, we don't have it!
					
					$dao = &getDAO("entities");
					$vo = $dao->getById($context['iddocument'],"idtype");
					$idtype = $idtypes[$context['iddocument']]=$vo->idtype;
				}
				$condition = $db->getOne(lq("SELECT condition FROM #_TP_entitytypes_entitytypes WHERE identitytype='". $idtype. "' AND identitytype2='". $context['idtype']. "'"));
				$cache[$context['idtype']] = (boolean)$condition;
				if ($db->errorno()) {
					dberror();
				}
			} //

			if ($cache[$context['idtype']]) {
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
	function moveAction(&$context, &$error)
	{
		global $db,$home;
		if (!rightonentity('move', $context)) {
			die("ERROR: you don't have the right to perform this operation");
		}

		$id = $context['id']; // which entities
		$idparent = intval($context['idparent']); // where to move it

		require_once 'entitiesfunc.php';
		if (!checkTypesCompatibility($id, $idparent)) {
			die("ERROR: Can move the entities $id into $idparent. Check the editorial model.");
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
			$result=$db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='$idparent' AND nature='P'")) or dberror();
	
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
			$result=$db->execute(lq("SELECT id2,degree FROM #_TP_relations WHERE id1='$id' AND nature='P'")) or dberror();

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
				$db->execute(lq("DELETE FROM #_TP_relations WHERE (".$delete.") AND nature='P'")) or dberror();
			}
			if ($values) {
				$db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or dberror();
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
	function downloadAction(&$context, &$error)
	{
		$id = $context['id'];
		switch($context['type']) {
		case 'xml':
			die('a implementer');
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
			$dao = $this->_getMainTableDAO();
			$vo  = $dao->getById($id,"creationmethod,creationinfo");
			if ($vo->creationmethod!=($multidoc ? "servoo;multidoc" : "servoo")) {
				die("ERROR: error creationmethod is not compatible with download");
			}
			$originalname = $vo->creationinfo ? basename($vo->creationinfo) : basename($filename);
			break;
		default:
			die ("ERROR: unknow type of download in downloadAction");
		}
		
		if (!file_exists($dir."/".$filename)) {
			die ("ERROR: the filename $filename does not exists");
		}
		require_once 'func.php';
		download ($dir. '/'. $filename, $originalname);
		exit;
	}

	// begin{publicfields} automatic generation  //
	/**
	 * @access private
	 */
	function _publicfields() {
		return array();
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //


} // class 

?>
