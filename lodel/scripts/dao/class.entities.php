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

 //
 // File generate automatically the 2004-12-26.
 //
 // begin{definitions} automatic generation  //


/**
  * VO of table entities
  */

class entitiesVO {
   var $id;
   var $idparent;
   var $idtype;
   var $identifier;
   var $entitytitle;
   var $usergroup;
   var $iduser;
   var $rank;
   var $status;
   var $upd;
   var $creationdate;
   var $modificationdate;
   var $creationmethod;
   var $creationinfo;

}

/**
  * DAO of table entities
  */

class entitiesDAO extends DAO {

   function entitiesDAO() {
       $this->DAO("entities",true);
       $this->rights=array('write'=>LEVEL_REDACTOR,'protect'=>LEVEL_REDACTOR);
   }





















































 // end{definitions} automatic generation  //

   function _rightscriteria($access) {
     if (!isset($this->cache_rightscriteria[$access])) {
       DAO::_rightscriteria ($access);
       if (!$GLOBALS['lodeluser']['admin']) {
	 $this->cache_rightscriteria[$access].=" AND usergroup IN (".$GLOBALS['lodeluser']['groups'].")";
       }
     }
     return $this->cache_rightscriteria[$access];
   }
}

?>