<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

class SimpleXML_extended extends SimpleXMLElement{

    public function getAttribute($name){
        foreach($this->attributes() as $key=>$val){
            if($key == $name){
                return (string)$val;
            }
        }
    }
   
    public function getAttributeNames(){
        $cnt = 0;
        $arrTemp = array();
        foreach($this->attributes() as $a => $b) {
            $arrTemp[$cnt] = (string)$a;
            $cnt++;
        }// end foreach
        return (array)$arrTemp;
    }// end function getAttributeNames
   
    public function getChildrenCount(){
        $cnt = 0;
        foreach($this->children() as $node){
            $cnt++;
        }// end foreach
        return (int)$cnt;
    }// end function getChildrenCount
   
    public function getAttributeCount(){
        $cnt = 0;
        foreach($this->attributes() as $key=>$val){
            $cnt++;
        }// end foreach
        return (int)$cnt;
    }// end function getAttributeCount
   
    public function getAttributesArray($names){
        $len = count($names);
        $arrTemp = array();
        for($i = 0; $i < $len; $i++){
            $arrTemp[$names[$i]] = $this->getAttribute((string)$names[$i]);
        }// end for
        return (array)$arrTemp;
    }// end function getAttributesArray
}