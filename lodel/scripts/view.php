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

   function back()

   {
     global $db,$idsession;
     $url=preg_replace("/[\?&]recalcul\w+=[^&]*/","",$_SERVER['REQUEST_URI']);

     $offset=-1-$back;

     usemaindb();

#     echo $url;
#     $result=$db->execute(lq("SELECT id,url FROM #_MTP_urlstack WHERE url!='' AND url!=".$db->qstr($url)." AND idsession='$idsession' ORDER BY id DESC"));
#     while(!$result->EOF) {
#       print_r($result->fields);
#       $result->MoveNext();
#     }

##     $result=$db->selectLimit(lq("SELECT id,url FROM #_MTP_urlstack WHERE url!='' AND url!=".$db->qstr($url)." AND idsession='$idsession' ORDER BY id DESC",1,$offset)) or dberror();

     $result=$db->selectLimit(lq("SELECT id,url FROM #_MTP_urlstack WHERE url!='' AND idsession='$idsession' ORDER BY id DESC",1,$offset)) or dberror();

     list ($id,$newurl)=$result->fetchRow();

     if ($id) {
       $db->execute(lq("DELETE FROM #_TP_urlstack WHERE id>='$id' AND idsession='$idsession'")) or dberror();
       $newurl="http://".$_SERVER['SERVER_NAME'].$newurl;
     } else {
       $newurl="index.php";
     }

     if (!headers_sent()) {
       header("location: ".$newurl);
       exit;
     } else {
       echo "<h2>Warnings seem to appear on this page. You may go on anyway by following <a href=\"$go\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
       exit;
     }
     //usecurrentdb();
   }

   /**
    * render
    */

   function render(&$context,$tpl,$cache=false)

   {
     global $home;

     if (!$cache) { // calculate the page and that's it !
       require_once ($home."calcul-page.php");
       calcul_page($context,$tpl);
       return;
     }
     // si le fichier de mise-a-jour est plus recent
     if (!isset($this->_iscachevalid)) $this->isCacheValid();

     if (!$this->_iscachevalid) {
       require_once ($home."calcul-page.php");
       $this->_calculateCacheAndOutput($context,$tpl);

       // the cache is valid... do we have a php file ?
     } elseif ($this->_extcachedfile=="php") {
       $ret=include($this->_cachedfile.".php");

       // c'est etrange ici, un require ne marche pas. Ca provoque des plantages lourds !

       if ($ret=="refresh") { // does php say we must refresh ?
	 require_once ($home."calcul-page.php");
	 $this->_calculateCacheAndOutput($context,$tpl);
       }
     } else { // no, we have a proper html, let read it.
       // sinon affiche le cache.
       readfile($this->_cachedfile.".html");
     }
   }


   function isCacheValid()
   {
     if ($GLOBALS['right']['visitor']) {
       $this->_iscachevalid=true;
       return true;
     }

     $maj=myfilemtime("CACHE/maj");


     // Calculate the name of the cached file

     $this->_cachedfile = substr(rawurlencode(preg_replace("/#[^#]*$/","",
							       $_SERVER['REQUEST_URI'])), 0, 255);


     $cachedir = substr(md5($this->_cachedfile), 0, 1);
     if ($GLOBALS['context']['charset']!="utf-8") $cachedir="il1.".$cachedir;


     if (!file_exists("CACHE/".$cachedir)) {
       mkdir("CACHE/".$cachedir, 0777 & octdec($GLOBALS['filemask']));
     }
     $this->_cachedfile = "CACHE/".$cachedir."/".$this->_cachedfile;
     $this->_extcachedfile=file_exists($this->_cachedfile.".php") ? "php" : "html";


     // The variable $cachedfile must exist and be visible in the global scope
     // The compiled file need it to know if it must produce cacheable output or direct output.
     // An object should be created in order to avoid the global scope pollution.
     $GLOBALS['cachedfile']=$this->_cachedfile;


     if ($maj>=myfilemtime($this->_cachedfile.".".$this->_extcachedfile)) {
       $this->_iscachevalid=true;
       return true;
     }

     $this->_iscachevalid=false;
     return false;
   }

   /**
    * render cached
    */
   function renderCached()

   {
     return $this->view($true);
   }




   //! private from this point

   var $_cachedfile;
   var $_extcachedfile;
   var $_iscachevalid;


   function _calculateCacheAndOutput ($context,$tpl) 

   {
     global $home;

     ob_start();
     $this->_extcachedfile=calcul_page($context,$tpl);
     $content=ob_get_contents();
     ob_end_clean();

     $this->_extcachedfile= substr($content,0,5)=='<'.'?php' ? "php" : "html";
  
     if ($this->_extcachedfile=="html") {
       echo $content; // send right now the html. Do other thing later. 
       flush(); // That may save few milliseconde !
       @unlink($this->_cachedfile.".php"); // remove if the php file exists because it has the precedence above.
     }

     // write the file in the cache
     $f = fopen($this->_cachedfile.".".$this->_extcachedfile, "w");
     fputs($f,$content);
     fclose($f);
     if ($this->_extcachedfile=="php") { 
       $dontcheckrefresh=1;
       include($this->_cachedfile.".php"); 
     }
   }
}

function &getView()

{
  static $view;

  if (!$view) $view=new View;
  return $view;
}

/**
 * Calling the right makeSelect
 */

function makeSelect(&$context,$varname,$lo,$edittype)

{
  $logic=getLogic($lo);
  $logic->makeSelect($context,$varname);
}


/**
 * render the <option> html tags for normal and multiple select
 */

function renderOptions($arr,$selected)

{
  $multipleselect=is_array($selected);

  foreach ($arr as $k=>$v) {
    if ($multipleselect) {
      $s=in_array($k,$selected) ? "selected" : "";
    } else {
      $s=$k==$selected ? "selected" : "";
    }
    $k=htmlentities($k);
    echo '<option value="'.htmlentities($k).'" '.$s.'>'.$v."</option>\n";
  }
}

?>
