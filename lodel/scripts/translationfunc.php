<?php
/**
 * Fichier utilitaire de gestion des traductions i18n
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @version CVS:$Id:
 * @package lodel
 * @since Fichier ajouté depuis la version 0.8
 */
class_exists('XMLDB', false) || include 'xmldbfunc.php';

/**
 * Affichage des champs textes pour la traduction
 *
 * @param string $name le nom du champ
 * @param string $textgroup le nom du groupe
 * @param string $lang (par défaut est à -1)
 */
function mkeditlodeltext($name, $textgroup, $lang = -1)
{
	list () = getlodeltext($name, $textgroup, $id, $text, $status, $lang);
	if (!$id)	{ // create it ?? 
		return; // to be decided
	}
	// determin the number of rows to use for the textarea
	$ncols = 50;
	$nrows = (int)(strlen($text) / $ncols);
	if ($nrows < 1)
		$nrows = 1;
	if ($nrows > 10)
		$nrows = 10; // limit for very long text, it's not usefull anyway
	echo '<dt><label for="contents'.$id.'">@'.strtoupper($name).'</label></dt>
	<dd><textarea name="contents['.$id.']" id="contents'.$id.'" cols="'.$ncols.'" rows="'.$nrows.'"  onchange=" tachanged('.$id.');" >'.htmlspecialchars($text).'</textarea>
	 <select class="select'.lodeltextcolor($status).'" onchange="selectchanged(this);" id="selectstatus'.$id.'" name="status['.$id.']">';

	foreach (array (-1, 1, 2) as $s) {
		echo '<option class="select'.lodeltextcolor($s).'" value="'.$s.'" ';
		if ($s == $status)
			echo "selected=\"selected\" ";
		echo '>&nbsp;&nbsp;</option>';
	}
	echo '</select></dd>';
	##### reserve ce bout de code - ne pas supprimer
	//
	// Translated texte
	//
	#       $translatedtext='<'.'?php $result=mysql_query("SELECT texte,lang FROM $GLOBALS[tp]texts WHERE name=\''.$name.'\' AND textgroup=\''.$textgroup.'\' AND lang IN ('.$this->translationlanglist.')") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	# $divs=""; 
	# while (list($text,$lang)=mysql_fetch_row($result)) { 
	#    echo \'<a href="">[\'.$lang.\']</a> \'; 
	#    $divs.=\'<div id="lodeltexttranslation_$lang">\'.$text.\'</div>\';
	# }
	# echo $divs; 
	# ?'.'>';
}

/**
 * Créé la fonction Javascript qui permet de changer la couleur lors de la saisie d'une
 * traduction. Cela réflète le status de la traduction.
 */
function mkeditlodeltextJS()
{
?>
<script type="text/javascript"><!-- 
function lodeltextchangecolor(obj,value) {
	switch(value) {
<?php

	foreach (array (-1, 1, 2) as $status)	{
		echo 'case "'.$status.'": obj.style.backgroundColor="'.lodeltextcolor($status).'"; break;';
	}
?>
      }
 } 

  function tachanged(id) {
obj=document.getElementById('selectstatus'+id);
obj.selectedIndex='2';
lodeltextchangecolor(obj,'2');
  }

  function selectchanged(obj) {
    lodeltextchangecolor(obj,obj.options[obj.selectedIndex].value);
  }
--></script>
<?php

}

/**
 * Classe de gestion des traductions.
 *
 * Fille de la classe XMLDB
 */

class XMLDB_Translations extends XMLDB
{

	var $textgroups;
	var $lang;
	var $currentlang;

	/**
	 * Constructeur
	 *
	 * Construit le fichier XML des traductions pour une langue
	 *
	 * @param array $textgroups les groupes de texte des traductions 
	 * @param string $lang la langue de la traduction
	 */
	function XMLDB_Translations($textgroups, $lang = "")
	{
		$this->textgroups = $textgroups;
		$this->lang = $lang;

		$this->XMLDB("lodeltranslations", $GLOBALS['tp']);
		$this->addTable("translations", "texts");
		$this->addElement("translations", "lang", "title", "textgroups", "translators", "modificationdate", "creationdate");
		$this->addWhere("translations", "lang='$lang'");
		$this->addElement("texts", "contents");
		$this->addAttr("texts", "name", "textgroup", "status");
		if ($lang != "all")
			$this->addWhere("texts", "lang='$lang'");
		$this->addWhere("texts", textgroupswhere($textgroups));
		$this->addJoin("translations", "lang", "texts", "lang");
	}

	/**
	 * Insertion d'une ligne dans le XML
	 *
	 * @param string $table nom de la table concernée : translations ou texts
	 * @param array $record les données à insérer
	 *
	 */
	function insertRow($table, $record)
	{
		global $db;
		switch ($table) {
			//
			// table translations
			//
		case "translations" :
			// check the lang is ok
			if (!empty($record['lang']) && $this->lang != "all" && $this->lang != "" && $this->lang != $record['lang'])
			{
				return;
			}
			$this->currentlang = $record['lang'];
			// look for the translation
			$dao = DAO::getDAO("translations");
			$vo = $dao->find("lang='".$record['lang']."' AND textgroups='".$this->textgroups."'");
			if(!$vo) $vo = DAO::getDAO('translations')->createObject();
			$vo->textgroups = $this->textgroups;
			foreach ($record as $k => $v)
			{
				$vo->$k = $v;
			}
			$dao->save($vo);
			#print_R($vo);
			return $record['lang'];
			break;
				//
				// table texts
				//
		case "texts" :
			// check the lang is ok
			if (empty($record['lang']) || $this->currentlang != $record['lang'])
				return;
			// check the textgroup is ok
			if (!in_array(strtolower($record['textgroup']), $GLOBALS['translations_textgroups'][$this->textgroups])) {
				print_r($this);
				trigger_error("ERROR: Invalid textgroup : ".$this->textgroups, E_USER_ERROR);
			}
			// look for the translation
			$dao = DAO::getDAO("texts");
			$vo = $dao->find("name='".$record['name']."' AND textgroup='".$record['textgroup']."' AND lang='".$record['lang']."'");
			if(!$vo) $vo = DAO::getDAO('texts')->createObject();
			foreach ($record as $k => $v)
			{
				$vo->$k = $v;
			}
			$dao->save($vo);
			return;
			break;
		}
	}
}