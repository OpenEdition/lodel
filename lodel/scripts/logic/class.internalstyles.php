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
 *  Logic Internalstyle
 */

class InternalstylesLogic extends Logic {

  /** Constructor
   */
   function InternalstylesLogic() {
     $this->Logic("internalstyles");
   }


   function makeSelect(&$context,$var)

   {
     switch($var) {
     case "surrounding" :
       $arr=array(
		  "-*"=>getlodeltextcontents("previous_style","admin"),
		  "*-"=>getlodeltextcontents("next_styles","admin"),
		  );
       
       $dao=&getDAO("tablefields");
       $vos=$dao->findMany("style!=''","style","style");
       foreach($vos as $vo) {
	 if (strpos($vo->style,".")!==false || strpos($vo->style,":")!==false) continue;
	 $style=preg_replace("/[;,].*/","",$vo->style); // remove the synonyms
	 $arr[$style]=$style;
       }
       renderOptions($arr,$context['surrounding']);
       break;
     }
   }


   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */




   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("style"=>array("style","+"),
                  "surrounding"=>array("select","+"),
                  "conversion"=>array("text",""),
                  "greedy"=>array("boolean",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("style"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */





?>
