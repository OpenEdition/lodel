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
 *  Logic Site
 */

class SitesLogic extends Logic {

  /** Constructor
   */
   function SitesLogic() {
     $this->Logic("sites");
   }



   /**
    * make the select for this logic
    */

   function makeSelect(&$context,$var)

   {
     switch($var) {
     }
   }
 
   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

/*
  function _prepareEdit($dao,&$context) 

  {
  }



   function _populateContextRelatedTables(&$vo,&$context)

   {
     if ($vo->siterights<=LEVEL_EDITOR) {
       $dao=&getDAO("sites_sitegroups");
       $list=$dao->findMany("idsite='".$vo->id."'","","idgroup");
       $context['sitegroups']=array();
       foreach($list as $relationobj) {
	 $context['sitegroups'][]=$relationobj->idgroup;
       }
     }
   }

   function _saveRelatedTables($vo,$context) 

   {
     global $db;
     if ($vo->siterights<=LEVEL_EDITOR) {
       if (!$context['sitegroups']) $context['sitegroups']=array(1);

       // change the sitegroups     
       // first delete the group
       $this->_deleteRelatedTables($vo->id);
       // now add the sitegroups
       foreach ($context['sitegroups'] as $sitegroup) {
	 $sitegroup=intval($sitegroup);
	 $db->execute(lq("INSERT INTO #_TP_sites_sitegroups (idgroup, idsite) VALUES  ('$sitegroup','$id')")) or dberror();
       }
     }
   }

   function _deleteRelatedTables($id) {
     global $db;
     if ($GLOBALS['site']) { // only in the site table
       $db->execute(lq("DELETE FROM #_TP_sites_sitegroups WHERE idsite='$id'")) or dberror();
     }
   }


   function validateFields(&$context,&$error) {
     global $db,$lodelsite;

     if (!Logic::validateFields($context,$error)) return false;

     // check the site has the right equal or higher to the new site
     if ($lodelsite['rights']<$context['siterights']) die("ERROR: You don't have the right to create a site with rights higher than yours");

     // Check the site is not duplicated in the main table...
     if (!usemaindb()) return true; // use the main db, return if it is the same as the current one.

     $ret=$db->getOne("SELECT 1 FROM ".lq("#_TP_".$this->maintable)." WHERE status>-64 AND id!='".$context['id']."' AND sitename='".$context['sitename']."'");
     if ($db->errorno()) die($this->errormsg());
     usecurrentdb();

     if ($ret) {
       $error['sitename']="1"; // report the error on the first field
       return false;
     } else {
       return true;
     }
   }
*/


   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("title"=>array("text","+"),
                  "subtitle"=>array("text","+"),
                  "path"=>array("text","+"),
                  "url"=>array("text","+"),
                  "langdef"=>array("select",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

   // end{uniquefields} automatic generation  //

} // class 


/*-----------------------------------*/
/* loops                             */

?>
