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

require("siteconfig.php");
$tables=array("entrytypes","persontypes","entries",
	      "tablefieldgroups","tablefields","indextablefields",
	      "translations","usergroups","users",
	      "types","classes",
	      "options","optiongroups","useroptiongroups",
	      "internalstyles","characterstyles");

$level=LEVEL_VISITOR;
require($home."controler.php");


/*

  // special processing for each tables


 case 'entries':
   if (!$therequest['type'] || !isvalidtype($therequest['type'])) die("ERROR: a valid type is required");
   $result=mysql_query ("SELECT * FROM $GLOBALS[tp]entrytypes WHERE type='$type'") or dberror();
   $context=array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
   $context['idtype']=$context['type_id']; // import
   $context['sorting']=$GLOBALS['tp']."entries.".$context['type_sort'];
   break;
  //
  //

 case 'translations':
   die("site translations. En cours de developpement. Voir interface translation sur la page admin");
   $context['textgroups']="site";
   require($home."textgroupfunc.php");
   $context['textgroupswhere']=textgroupswhere($context['textgroups']);
   break;
}

*/



function loop_classtypes ($context,$funcname)

{
  global $db;

  foreach(array("entities","entries","persons") as $classtype) {
    $localcontext=$context;
    $localcontext['classtype']=$classtype;
    $localcontext['title']=getlodeltextcontents("classtype_".$classtype,"admin");
    call_user_func("code_do_$funcname",$localcontext);
  }
}




?>
