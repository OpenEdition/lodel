<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

die("desuet");

/**
 *  Logic Relations
 */

  //require_once("genericlogic.php");

class RelationsLogic extends Logic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function RelationsLogic() {
     $this->Logic("relations");
   }


   /**
    * add/edit Action
    */

   function editAction(&$context,&$error)

   {
     die("RelationsLogic::editAction. non implementé");
   }

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   /**
    * Used in deleteAction to do extra operation before the object is saved.
    * Usually it gather information used after in _deleteRelatedTables
    */
   function _prepareDelete($dao,&$context) 

  {
    $this->vos=$dao->getByIds($context['id']);

    if ($context['idrelation']) {
      $this->idrelation=$context['idrelation'];
    } else {
      $dao=&getDAO("relations");
      $this->vos=$dao->getByIds($context['id']);
      $this->idrelation=array();
      foreach ($vos as $vo) {
	$this->idrelation[]=$vo->id;
      }
    }
  }

ZXXXXXXXXXXXXXXXXXXXx
   /**
    * Used in deleteAction to do extra operation after the object has been deleted
    */
   function _deleteRelatedTables($id) 

  {
    global $db;
    $result=$db->execute(lq("SELECT DISTINCT class FROM #_TP_persontypes INNER JOIN #_TP_persons ON idtype=#_TP_persontypes.id WHERE #_TP_persons.id='".$id."'")) or dberror();
		 
    while (!$result->EOF) {
      $class=$result->fields['class'];

      $gdao=&getGenericDAO($class,"idperson");
      $gdao->deleteObject($id);

      if ($this->idrelation) {
	$gdao=&getGenericDAO("entities_".$class,"idrelation");
	$gdao->deleteObject($this->idrelation);
      }

      $result->MoveNext();
    }
    if ($this->idrelation) {
      $gdao=&getDAO("relations","idrelation");
      $gdao->delete("id2 IN ('".join("','",$this->idrelation)."')");
    }

    // delete 
  }



   function _publicfields() {
     if (!isset($this->_publicfields)) die("ERROR: publicfield has not be created");
     return $this->_publicfields;
   }

} // class 

/*------------------------------------*/
/* special function                   */



/*-----------------------------------*/
/* loops                             */



?>
