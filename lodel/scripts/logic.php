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


class Logic {

  /**
   * Table and class name of the central table
   */
  var $maintable;

  //var $jointable;




  /** Constructor
   */
   function Logic($maintable) {
     $this->$maintable=$maintable;
   }


   /**
    * view Action
    */

   function editAction($context)

   {
     $dao=getMainTableDAO();
     $vo=$dao->createObject();
   }


   /**
    * add/edit Action
    */

   function editAction($context)

   {
     $dao=getMainTableDAO();
     $dao->instantiateObject($vo);

     $this->_populateObject($vo,$context);
     if (!$this->_validateObject($vo)) return;
     $this->save($vo); // a revoir
   }

   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction($id,$dir)

   {
     $this->_changeRank($id,$dir,"status>0");
   }

   /**
    * Delete
    * Default implementation
    */

   function deleteAction($id)

   {     
   }


   /*---------------------------------------------------------------*/
   //! Private from this point
   /**
    * @private
    */

   function &getMainTableDAO() {
     require_once($GLOBALS['home']."dao.php");
     return getDOA($this->maintable);
   }


   /**
    * Change the rank of an Object
    */


   function _changeRank($id,$dir,$criteria)

   {
     global $db;

     if (is_numeric($dir)) {
       $dir=$dir>0 ? 1 : -1;
     } else {
       $dir=$dir=="up" ? -1 : 1;
     }

     $desc=$dir>0 ? "" : "DESC";

     $dao=getMainTableDAO();
     $vos=$dao->findMany($criteria,"rank $desc","id,rank");

     $count=count($vos);
     $newrank=$dir>0 ? 1 : $count;

     $i=0; 
     for($i=0; $i<$count; $i++) {
       if ($vos[$i]->id==$id) {
	 // exchange with the next if it exists
	 if (!($vos[$i+1])) break;
	 $vos[$i+1]->rank=$newrank;
	 $dao->save($vos[$i+1]);
	 $newrank+=$dir;
       }
       if ($vos[$i]->rank!=$newrank) { // rebuild the rank if necessary
	 $vos[$i]->rank=$newrank;
	 $dao->save($vos[$i]);
       }
       $newrank+=$dir;
     }
   }
}


/**
 * Logic factory
 *
 */

function &getLogic($table) {
  static $factory; // cache

  if ($factory[$table]) return $factory[$table]; // cache

  require_once($GLOBALS['home']."logic/class.$table.php");
  $logicclass="$tableDAO";
  return $factory[$table]=new $logicclass;
}

?>
