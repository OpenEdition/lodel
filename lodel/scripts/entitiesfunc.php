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


   /**
    * Check that the type of $id can be in the type of $idparant.
    * if $id=0 (creation of entites), use $idtype .
    */

   function checkTypesCompatibility($id,$idparent,$idtype=0)
   {
     global $db;
     //
     // check whether we have the right or not to put an entitie $id in the $idparent
     //
     if ($id>0) {
       $table="#_TP_entitytypes_entitytypes INNER JOIN #_TP_entities as son ON identitytype=son.idtype";
       $criteria="son.id='".$id."'";
     } elseif ($idtype>0) {
       $table="#_TP_entitytypes_entitytypes";
       $criteria="identitytype='".$idtype."'";
     } else {
       die("ERROR: id=0 and idtype=0 in EntitiesLogic::_checkTypesCompatibility");
     }
     
     if ($idparent>0) { // there is a parent
       $query="SELECT condition FROM ".$table." INNER JOIN #_TP_entities as parent ON identitytype2=parent.idtype  WHERE parent.id='".$idparent."' AND ".$criteria;
     } else { // no parent, the base.
       $query="SELECT condition FROM ".$table." WHERE identitytype2=0 AND ".$criteria;
     }
       
     #echo $query;
     $condition=$db->getOne(lq($query));
     if ($db->errorno()) dberror();
     return $condition;
   }

?>