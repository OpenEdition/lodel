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

require_once($GLOBALS['home']."xmldbfunc.php");


function mkeditlodeltext($name,$textgroup,$lang=-1)

{
  list($id,$text,$status)=getlodeltext($name,$textgroup,$lang);
  if (!$id) { # create it ?? 
    return; # to be decided
  }

  // determin the number of rows to use for the textarea
  $ncols=100;
  $nrows=intval(strlen($text)/$ncols);
  if ($nrows<1) $nrows=1;
  if ($nrows>10) $nrows=10; // limit for very long text, it's not usefull anyway

  echo '<div class="editlodeltext"><label for="texte" style="float: left; width: 10em;">@'.strtoupper($name).'</label>
<textarea name="texts['.$id.']" cols="'.$ncols.'" rows="'.$nrows.'" " onchange=" obj=document.getElementById(\'selectstatus'.$id.'\'); obj.selectedIndex=\'2\'; lodeltextchangecolor(obj,\'2\'); " >'.htmlspecialchars($text).'</textarea>
 <select style="background-color: '.lodeltextcolor($status).';" onchange="lodeltextchangecolor(this,this.options[this.selectedIndex].value);" id="selectstatus'.$id.'" name="status['.$id.']">';


  foreach (array(-1,1,2) as $s) {
    echo '<option style="background-color: '.lodeltextcolor($s).';" value="'.$s.'" ';
    if ($s==$status) echo "selected ";
    echo '>&nbsp;&nbsp;</option>
';
  }

  echo '</select></div>
';



##### reserve ce bout de code
      //
      // Translated texte
      //
#       $translatedtext='<'.'?php $result=mysql_query("SELECT texte,lang FROM $GLOBALS[tp]texts WHERE name=\''.$name.'\' AND textgroup=\''.$textgroup.'\' AND lang IN ('.$this->translationlanglist.')") or die(mysql_error());
# $divs=""; 
# while (list($text,$lang)=mysql_fetch_row($result)) { 
#    echo \'<a href="">[\'.$lang.\']</a> \'; 
#    $divs.=\'<div id="lodeltexttranslation_$lang">\'.$text.\'</div>\';
# }
# echo $divs; 
# ?'.'>';
}

function mkeditlodeltextJS()

{
?>
<script type="text/javascript"><!-- 
function lodeltextchangecolor(obj,value) {  
 switch(value) {
<?php
 foreach (array(-1,1,2) as $status) {
     echo 'case "'.$status.'": obj.style.backgroundColor="'.lodeltextcolor($status).'"; break;';
   }
?>
      }
 } 
--></script>
<?php
}




class XMLDB_Translations extends XMLDB {

  var $textgroups;
  var $lang;
  var $currentlang;

  function XMLDB_Translations($textgroups,$lang="") 
  {
    $this->textgroups=$textgroups;
    $this->lang=$lang;

    $this->XMLDB("lodeltranslations",$GLOBALS[tp]);
    $this->addTable("translations","textes");
    $this->addElement("translations","lang","title","textgroups","translators","modificationdate","creationdate");
    $this->addWhere("translations","lang='$lang'");
    $this->addElement("textes",array("texte","text"));
    $this->addAttr("textes","name","textgroup","status");
    if ($lang!="all") $this->addWhere("textes","lang='$lang'");
    require_once($GLOBALS['home']."textgroupfunc.php");
    $this->addWhere("textes",textgroupswhere($textgroups));
    $this->addJoin("translations","lang","textes","lang");
  }

  function insertRow($table,$record) 

  {
    #echo "table:$table\n<br>";
    #print_r($record);

    // protect record
    clean_request_variable($record);

    switch($table) {
      //
      // table translations
      //
    case "translations":
      // check the lang is ok
      if ($this->lang!="all" && $this->lang!="" && $this->lang!=$record['lang']) return;
      $this->currentlang=$record['lang'];
      // look for the translation
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]translations WHERE lang='".$record['lang']."' AND textgroups='".$this->textgroups."'") or die(mysql_error());
      list($id)=mysql_fetch_row($result);

      $record['textgroups']=$this->textgroups;
      setrecord($table,$id,$record);
      return $record['lang'];
      break;
      //
      // table textes
      //
    case "textes":
      // check the lang is ok
      if (!$record['lang'] || $this->currentlang!=$record['lang']) return;
      // check the textgroup is ok
      if (!in_array($record['textgroup'],$GLOBALS['textgroups'][$this->textgroups])) die("ERROR: Invalid textgroup");

      // look for text
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]texts WHERE name='".$record['name']."' AND textgroup='".$record['textgroup']."' AND lang='".$record['lang']."'") or die(mysql_error());
      list($id)=mysql_fetch_row($result);
#      echo $id," ";
#      print_r($record);
      setrecord($table,$id,$record);
      return;
      break;
    }
  }
}

?>