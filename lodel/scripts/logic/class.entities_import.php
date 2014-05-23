<?php
/**	
 * Logique des entités - import
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
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
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

/**
 * Classe de logique des entités (gestion de l'import)
 * 
 * @package lodel/logic
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class Entities_ImportLogic extends Entities_EditionLogic 
{
	/**
	 * Tableau des équivalents génériques
	 *
	 * @var array
	 */
	public $g_name;
	
	private $_moved_images = array();
	
	protected $prefixregexp="Pr\.|Dr\.|Mr\.|Ms\.";
	
	protected $context; // save the current context
	protected $_localcontext;
	protected $task;

  	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Importation d'une entité
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function importAction (&$context, &$error, $delete = true) 
	{
		global $db;
		$this->context=&$context;
		$this->error =& $error;
		if(empty($context['idtask']))
			View::getView()->back();

		$idtask = $context['idtask'];
		$taskLogic = Logic::getLogic('tasks');
		$this->task = $task = $taskLogic->getTask($idtask);
		if (!$task)
			View::getView()->back();

		$taskLogic->populateContext($task, $context);
		$context['id'] = !empty($task['identity']) ? $task['identity'] : 0;
		// restore the entity
		$contents = $task['fichier'];
		if(!$contents) trigger_error("ERROR: internal error in Entities_ImportLogic::importAction", E_USER_ERROR);
		$context['idparent'] = $task['idparent'];
		$context['entries'] = !empty($contents['contents']['entries']) ? $contents['contents']['entries'] : array();
		$context['externalentries'] = !empty($contents['contents']['externalentries']) ? $contents['contents']['externalentries'] : array();
		$context['persons'] = !empty($contents['contents']['persons']) ? $contents['contents']['persons'] : array();
		$context['entities'] = !empty($contents['contents']['entities']) ? $contents['contents']['entities'] : array();
		unset($contents['contents']['entities'], $contents['contents']['persons'], $contents['contents']['entries']);
		$context['data'] = $contents['contents'];
		$context['creationmethod'] = "otx";
		$context['creationinfo'] = $task['sourceoriginale'];
		$source = isset($task['source']) ? $task['source'] : null;
		$odt = isset($task['odt']) ? $task['odt'] : null;
		$tei = $task['tei'];
		unset($task, $contents);
		$ret = $this->editAction($context, $error, 'FORCE');
		$this->id = $context['id'];
		$sourcefile=SITEROOT."lodel/sources/entite-".$this->id.".source";
		if($delete) @unlink ($sourcefile);
		if(isset($source))
		{
			file_put_contents($sourcefile, $source);
			@chmod ($sourcefile, 0666 & octdec(C::get('filemask', 'cfg')));
		}
		$sourcefileodt=SITEROOT."lodel/sources/entite-odt-".$this->id.".source";
		if($delete) @unlink ($sourcefileodt);
		if(isset($odt))
		{
            file_put_contents($sourcefileodt, $odt);
			@chmod ($sourcefileodt, 0666 & octdec(C::get('filemask', 'cfg')));
		}

		$this->_fixImagesPath($tei);

        $teifile = SITEROOT."lodel/sources/entite-tei-".$this->id.".xml";
		if($delete) @unlink ($teifile);
		file_put_contents($teifile, $tei);
		@chmod ($teifile, 0666 & octdec(C::get('filemask', 'cfg')));
// 		class_exists('XMLImportParser', false) || include "xmlimport.php";
// 		$parser=new XMLImportParser();
// 		$parser->init (@$context['class']);
// 		$parser->parse (file_get_contents (@$task['fichier']), $this);
// 		if (!$this->id) trigger_error("ERROR: internal error in Entities_ImportLogic::importAction", E_USER_ERROR);		
// 		if (isset($this->nbdocuments) && $this->nbdocuments>1) { // save the file
// 			$sourcefile=SITEROOT."lodel/sources/entite-multidoc-".@$task['idparent'].".source";
// 		} else {
// 			$sourcefile=SITEROOT."lodel/sources/entite-".$this->id.".source";
// 		}
// 		@unlink ($sourcefile);
// 		copy (@$task['source'], $sourcefile);
// 		@chmod ($sourcefile, 0666 & octdec(C::get('filemask', 'cfg')));

		// close the task
		$taskContext = array('id'=>$idtask);
		$taskLogic->deleteAction($taskContext, $error);

		if ($ret != '_error' && isset($context['finish'])) {
			return $ret;
		} elseif ($ret != '_error') {
			return "_location: index.php?do=view&id=".$this->id;
		} else { //ret=error
			return "_location: index.php?do=view&id=".$this->id."&check=oui";
		}
	}

	/**
	 * fix the path of images in the TEI
	 * @access private
	 * @param string the TEI file
	 */
	private function _fixImagesPath( &$tei ){
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = true;
		$dom->formatOutput       = false;

		$dom->loadXML($tei);

		$a = $dom->getElementsByTagName('graphic');
		foreach($a as $image){
			foreach($this->_moved_images as $original => $new){
				if( $image->hasAttribute('url') && preg_match("#{$original}$#", $image->getAttribute('url')) ){
					$image->setAttribute('url', $new);
					break;
				}
			}
		}

		$tei = $dom->saveXML();
	}
	/**
	 * method to move img link when the new id is known
	 * @access private
	 */
	protected function _moveImages (&$context)
	{ 
		$count = 1;
		$dir = '';
		$this->_moveImages_rec ($context, $dir, $count); 
	}

	protected function _moveImages_rec (&$context, &$dir, &$count) 
	{
		$imglist = array();

		foreach (array_keys ($context) as $k) {
			if (is_array ($context[$k])) {
				$this->_moveImages_rec ($context[$k], $dir, $count);
				continue;
			}
			$text=&$context[$k];
			preg_match_all ('/<img[^>]+src=\\\?"([^"]+\.([^"\.]+?))\\\?"([^>]*>)/i', $text, $results, PREG_SET_ORDER);

			foreach ($results as $result) {
				$imgfile=$result[1];
				$ext=$result[2];

				if (substr ($imgfile, 0, 5)=="http:") continue; // external image

				if (isset($imglist[$imgfile])) { // is it in the cache ?
					$text = str_replace ($result[0], "<img src=\"$imglist[$imgfile]\" />", $text);
				} else {
					// not in the cache let's move it
					if (!$dir) {
						$dir="docannexe/image/".$context['id'];
						$this->_checkdir ($dir);
					}
					$imglist[$imgfile]= $newimgfile = "{$dir}/img-{$count}.{$ext}";

					$imgfile_path = (file_exists($imgfile)) ? $imgfile : $base . DIRECTORY_SEPARATOR . $imgfile;

					$ok = @copy ($imgfile_path , SITEROOT.$newimgfile );
					@unlink ($imgfile_path);
					if ($ok) { // ok, the image has been correctly copied
						$text=str_replace ($result[0], '<img src="'.$newimgfile.'"'.$result[3], $text);
						@chmod (SITEROOT.$newimgfile, 0666  & octdec(C::get('filemask', 'cfg')));
						++$count;
					} else { // no, problem copying the image
						$text=str_replace ($result[0], "<span class=\"image_error\">[Image non convertie]</span>", $text);
					}
				}
				
				$this->_moved_images[basename($imgfile)] = $imglist[$imgfile];
			}
		}
	}
 
	protected function _checkdir ($dir) 
	{
		if (!is_dir (SITEROOT.$dir)) {
			mkdir (SITEROOT.$dir, 0777 & octdec(C::get('filemask', 'cfg')));
			@chmod(SITEROOT.$dir,0777 & octdec(C::get('filemask', 'cfg')));
		} else { // clear the directory the first time.
			$fd=@opendir(SITEROOT.$dir);
			if (!$fd) trigger_error("ERROR: cannot open the directory $dir", E_USER_ERROR);
			while ($file=readdir($fd)) {
				if ($file{0}=="." || !preg_match("/^(img-\d+(-small\d+)?|\w+-small\d+).(jpg|gif|png)$/i", $file)) continue;
				$file=SITEROOT.$dir."/".$file;
				if (is_file($file)) @unlink($file);
			}
			closedir($fd);
		}
	}



	public function openClass ($class, $obj=null, $multidoc=false) 
	{
		switch ($class[1]) { // classtype
			case 'entries':
				break;
			case 'persons':
				break;
			case 'entities':
				$this->_localcontext=array();
				$this->_currentcontext=&$this->_localcontext;
				break;
		}
	}

	public function closeClass ($class, $multidoc=false) 
	{
		global $db;
		switch ($class[1]) { // classtype
		case 'entries':
			break;
		case 'persons': // come back to the main context
			$this->_currentcontext=&$this->_localcontext;
			break;
		case 'entities':    // let's import now.
			$localcontext=array_merge ($this->context, $this->_localcontext);
			$localcontext['idparent']=@$this->task['idparent'];
			$localcontext['idtype']=@$this->task['idtype'];
			if ($multidoc) { // try to find the id
				$result=$db->execute (lq ("SELECT id FROM #_TP_entities WHERE idparent='".$localcontext['idparent']."' AND creationmethod='servoo;multidoc' ORDER BY id LIMIT ".(int)$this->nbdocuments.",1")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				if (!$result->EOF) $localcontext['id']=$result->fields['id'];
				$this->nbdocuments++;
			} else if (!empty($this->task['identity'])) $localcontext['id']=$this->task['identity'];
			$localcontext['creationmethod']=$multidoc ? "servoo;multidoc" : "servoo";
			$localcontext['creationinfo']=@$this->task['sourceoriginale'];

			if ($multidoc) $this->context['finish']="oui";
			if (empty($this->context['finish'])) $localcontext['status']=-64;

			$error=array ();
			$this->ret=parent::editAction ($localcontext, $this->error, 'FORCE');
			#echo "ret1=".$this->ret."<br />";
			#print_r($error);
			if (!isset($this->id)) $this->id=$localcontext['id']; // record the first one only
			// move the source file and the files
		}
	}

	public function processData($data) 
	{
		return $data;
	}

	public function processTableFields ($obj, $data) 
	{
		global $db;
		static $styles_string;
		if (!$styles_string) { // record all the internal into a string to use it in the following regexp
			$styles = array();
			if (is_array ($this->commonstyles)) {
				foreach ($this->commonstyles as $key => $val) {
					$class = strtolower (get_class ($val));
					if ($class == "internalstylesvo") //if internalstyle add it to the array $styles
						$styles[] = $key;
				}
				if (count ($styles) > 0)	$styles_string = implode ('|',$styles);
				unset($styles);
			}
		}
		if(!function_exists('myfunction')) {
			function myfunction ($arg0, $arg1, $arg2, $arg3, $styles,$style) {
				//si on trouve pas $arg(2) dans les styles on remplace le style par le style de l'objet
				if (strstr ($styles, $arg2)===false) {
					return '<p class="'.$style.'"';
				}
				else {
					return '<p class="'.$arg2.'"';
				}
			}
		}
		
		// replace all the paragraph containing classes added by Oo except paragraph with internal style
		$data = preg_replace ('/(<p\b[^>]+class=")([^"]*)(")/e', "myfunction('\\0', '\\1','\\2','\\3','". $styles_string."','".$obj->style."')", $data);
		if ($obj->type=="file" || $obj->type=="image") {
			// nothing...
		} elseif ($obj->type=="mltext" || $obj->type=="mllongtext") {
			$lang=!empty($obj->lang) ? $obj->lang : C::get('lang', 'lodeluser');
			//$this->_currentcontext['data'][$obj->name][$lang].=addslashes ($data);
			if(!isset($this->_currentcontext['data']))
				$this->_currentcontext['data'] = array();
			if(!isset($this->_currentcontext['data'][$obj->name]))
				$this->_currentcontext['data'][$obj->name] = array();
			if(!isset($this->_currentcontext['data'][$obj->name][$lang]))
				$this->_currentcontext['data'][$obj->name][$lang] = '';
			$this->_currentcontext['data'][$obj->name][$lang].=$data;
		} elseif ($obj->style[0]!=".") {
			if(!isset($this->_currentcontext['data']))
				$this->_currentcontext['data'] = array();
			if(!isset($this->_currentcontext['data'][$obj->name]))
				$this->_currentcontext['data'][$obj->name] = '';
			//$this->_currentcontext['data'][$obj->name].=addslashes ($data);
			$this->_currentcontext['data'][$obj->name].=$data;
		}
		return $data;
	}

	public function processEntryTypes ($obj, $data) 
	{
		$data = preg_split ("/<\/p>/", $data);
		foreach ($data as $data2) {
			$data2 = explode (",", strip_tags ($data2));
			foreach ($data2 as $entry) {
				//$this->_localcontext['entries'][$obj->id][]=array ("g_name"=>trim (addslashes ($entry)));
			
				// le 2 ème argument de trim liste les caractères correspondant aux espaces dans le fichier source (utilisé pour supprimer TOUS les espaces avant et après l'entrée)
				$this->_localcontext['entries'][$obj->id][]=array ("g_name"=>trim($entry,"\xC2\xA0\x00\x1F\x20"));
 			}
		}
	}

	public function processPersonTypes ($obj, $data) 
	{
		static $g_name_cache;
		if (!isset($g_name_cache[$obj->class])) {  // get the generic type     
			$vos=DAO::getDAO("tablefields")->findMany ("class='".$obj->class."' or class='entites_".$obj->class."' and g_name IN ('familyname','firstname','prefix')", "", "name,g_name");
			foreach ($vos as $vo) {
				$g_name_cache[$obj->class][$vo->g_name]=$vo->name;
			}
		}
		$g_name=$g_name_cache[$obj->class];
		// ok, we have the generic type
		// let's split the paragraph and the comma
		$data = preg_split ("/<\/p>/", $data);
		foreach ($data as $data2) { 
			$data2 = explode (",", strip_tags ($data2));
			foreach ($data2 as $person) {
				if (!trim ($person)) continue;
				$this->_localcontext['persons'][$obj->id][]=array(); // add a person
				$this->_currentcontext=&$this->_localcontext['persons'][$obj->id][count ($this->_localcontext['persons'][$obj->id])-1];
				if (preg_match("/^\s*(".$this->prefixregexp.")\s/",$person,$result)) {
					$this->_currentcontext[$g_name['prefix']]=$result[1];
					$person=str_replace($result[0],"",$person);
				}
				// ok, we have the prefix
				// try to guess
				// ok, on cherche maintenant a separer le name et le firstname
				$person = trim($person, "\xC2\xA0\x00\x1F\x20");
				$name=$person;
				while ($name && strtoupper($name)!=$name) { $name=substr(strstr($name," "),1);}
				if ($name) {
					$firstname=str_replace ($name, "", $person);
				} else { // sinon coupe apres le premiere espace
					if (preg_match ("/^(.*?)\s+([^\s]+)$/i",$person, $result)) {
						$firstname=$result[1]; $name=$result[2];
					} else {$name = $person;}
				}
				//$this->_currentcontext['data'][$g_name['firstname']]=addslashes(trim($firstname));
				//$this->_currentcontext['data'][$g_name['familyname']]=addslashes(trim($name));
				
				// le 2 ème argument de trim liste les caractères correspondant aux espaces dans le fichier source (utilisé pour supprimer TOUS les espaces avant et après l'entrée)
				if(isset($g_name['firstname']) && !empty($firstname))
					$this->_currentcontext['data'][$g_name['firstname']]=trim($firstname,"\xC2\xA0\x00\x1F\x20");
				if(isset($g_name['familyname']) && !empty($name))
					$this->_currentcontext['data'][$g_name['familyname']]=trim($name,"\xC2\xA0\x00\x1F\x20");
			} // for each person
		}
  	}

	public function processCharacterStyles ($obj, $data) 
	{
		return $obj->conversion.$data.closetags ($obj->conversion);
	}

	public function processInternalStyles ($obj, $data) 
	{
		if (strpos ($obj->conversion, '<li>') !== false) {
			$conversion = str_replace ('<li>', '', $obj->conversion);
			$data = preg_replace (array ("/(<p\b)/", "/(<\/p>)/"), array ("<li>\\1", "\\1</li>"), $data);
		}
		elseif (preg_match ("/<hr\s*\/?>/", $obj->conversion)) {
			switch (trim (strip_tags ($data))) {
				case '*' : return "<hr width=\"30%\" \ >";
				case '**' : return "<hr width=\"50%\" \ >";
				case '***' : return "<hr width=\"80%\" \ >";
				case '****' : return "<hr \ >";
				default: return "<hr width=\"10%\" \ >";
			}
		} 
		else  {
			$conversion = $obj->conversion;
		}
		return $conversion.$data.closetags ($conversion);
	}

	public function unknownParagraphStyle ($style, $data) 
	{
		// nothing to do ?
	}

	public function unknownCharacterStyle ($style, $data) 
	{
		// nothing... let's clean it.
		return preg_replace(array("/^<span\b[^>]*>/","/<\/span>$/"),"",$data);
	}

   // begin{publicfields} automatic generation  //
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //
   // end{uniquefields} automatic generation  //
} // class 
