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
 * base class view
 */

class View {




  /** Constructor
   */
   function View() {
   }


   /**
    * back
    */

   function back();

   {
     global $db,$idsession;
     $url=preg_replace("/[\?&]recalcul\w+=[^&]*/","",$_SERVER['REQUEST_URI']);

     $offset=-1-$back;

     usemaindb();

     $result=$db->selectLimit(lq("SELECT id,url FROM #_MTP_urlstack WHERE url!='' AND url!=".$db->qstr($url)." AND idsession='$idsession' ORDER BY id DESC",1,$offset)) or die($db->errormsg());

     list ($id,$newurl)=$result->fetchRow();

     if ($id) {
       $db->execute(lq("DELETE FROM #_TP_urlstack WHERE id>='$id' AND idsession='$idsession'")) or die($db->errormsg());
       header("Location: http://".$_SERVER['SERVER_NAME'].$newurl.$arg);exit;
     } else {
       header("Location: index.php");exit;
     }
     usecurrentdb();
   }


   /**
    * print
    */

   function print(&$context,$tpl,$cache=false)

   {
     global $home;

     if (!$cache) { // that's it !
       require_once ($home."calcul-page.php");
       calcul_page($context,$tpl);
       return;
     }
XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
     // si le fichier de mise-a-jour est plus recent
     if (!isset($this->_iscachevalid)) $this->isCacheValid();

     if (!$this->_iscachevalid) {
       require_once ($home."calcul-page.php");
       $this->_calculateCacheAndOutput($context,$tpl,$cachedfile);
     } elseif ($extension=="php") {
       // execute le cache.
#  echo "cache:$cachedfile";
       $ret=include($cachedfile.".php");
  // c'est etrange ici, un require ne marche pas. Ca provoque des plantages lourds !
#  echo "required: ",join(",",get_required_files()),"<br>";
#  echo "return:$ret fichier$cachedfile.php<br/>";
  if ($ret=="refresh") {
    #echo "refresh";
    require_once ($home."calcul-page.php");
#    echo "planter?<br/>\n";
    calculate_cache_and_output($context,$tpl,$cachedfile);
  }
} else {
  // sinon affiche le cache.
  readfile($cachedfile.".html");
}


   }


   function isCacheValid()
   {
     if ($GLOBALS['rightvisitor']) {
       $this->_iscachevalid=true;
       return true;
     }

     $maj=myfilemtime("CACHE/maj");


     // Calculate the name of the cached file

     $GLOBALS['cachedfile'] = substr(rawurlencode(preg_replace("/#[^#]*$/","",
							       $_SERVER['REQUEST_URI'])), 0, 255);

     // The variable $cachedfile must exist and be visible in the global scope
     // The compiled file need it to know if it must produce cacheable output or direct output.
     // An object should be created in order to avoid the global scope pollution.

     $cachedir = substr(md5($GLOBALS['cachedfile']), 0, 1);
     if ($GLOBALS['context']['charset']!="utf-8") $cachedir="il1.".$cachedir;


     if (!file_exists("CACHE/".$cachedir)) {
       mkdir("CACHE/".$cachedir, 0777 & octdec($GLOBALS['filemask']));
     }
     $GLOBALS['cachedfile'] = "CACHE/".$cachedir."/".$cachedfile;
     $extension=file_exists($cachedfile.".php") ? "php" : "html";

     if ($maj>=myfilemtime($cachedfile.".".$extension)) {
       $this->_iscachevalid=true;
       return true;
     }

     $this->_iscachevalid=false;
     return false;
   }

   /**
    * print cached
    */
   function printCached()

   {
     return $this->view($true);
   }




   //! private from this point

   function _calculateCacheAndOutput ($context,$tpl,$cachedfile) {
     global $home;

     ob_start();
     $extension=calcul_page($context,$tpl);
     $content=ob_get_contents();
     ob_end_clean();

     $extension= substr($content,0,5)=='<'.'?php' ? "php" : "html";
  
     if ($extension=="html") {
       echo $content; // send right now the html. Do other thing later. 
       flush(); // That may save few milliseconde !
       @unlink($cachedfile.".php"); // remove if the php file exists because it has the precedence above.
     }

     // write the file in the cache
     $f = fopen($cachedfile.".".$extension, "w");
     fputs($f,$content);
     fclose($f);
     if ($extension=="php") { 
       $dontcheckrefresh=1;
       include($cachedfile.".php"); 
     }
   }
}

function &getView()

{
  static $view;

  if (!$view) $view=new View;
  return $view;
}

?>
