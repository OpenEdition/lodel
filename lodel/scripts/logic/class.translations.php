<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des traductions
 */

$GLOBALS['translations_textgroups']=array(
	"interface"=>array("common","edition","admin","lodeladmin","install","lodelloader"),
	"site"=>array("site"),
);


/**
 * Classe de logique des traductions
 */
class TranslationsLogic extends Logic {

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct("translations");
	}

	/**
	 * lookfor Action
	 * recherche dans tous les templates du site les variables LS de traductions
	 */
	public function lookforAction(&$context, $error)
	{
		$this->_setTextGroups($context);
		if('site' === (string)$context['textgroups']) {
			$tplDirs = SITEROOT.'tpl/';
		} else { // interface
			$tplDirs = array('./tpl/', '../tpl/', '../share/macros/', '../lodel/tpl/', '../lodel/src/lodel/edition/tpl/', '../lodel/src/lodel/admin/tpl/');
		}
		$lodelparser = LodelParser::getParser();
		$vars = array();
		if(is_array($tplDirs)) {
			foreach($tplDirs as $tplDir) {
				$cache = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tplDir));
				foreach($cache as $file) {
					if($cache->isDot() || $cache->isDir() || substr($file, -5) !== '.html') continue;
					if(preg_match_all("/\[@([A-Z][A-Z_0-9]*(?:\.[A-Z][A-Z_0-9]*)*)\]/", file_get_contents($file), $matches)>0) {
						$matches = array_unique($matches[1]);
						foreach($matches as $var) {
							if(!isset($vars[$var])) {
								$lodelparser->parse_variable_extra('@', $var);
								$vars[$var] = true;
							}
						}
					}
				}
			}
		} else {
			$cache = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tplDirs));
			foreach($cache as $file) {
				if($cache->isDot() || $cache->isDir() || substr($file, -5) !== '.html') continue;
				if(preg_match_all("/\[@([A-Z][A-Z_0-9]*(?:\.[A-Z][A-Z_0-9]*)*)\]/", file_get_contents($file), $matches)>0) {
					$matches = array_unique($matches[1]);
					foreach($matches as $var) {
						if(!isset($vars[$var])) {
							$lodelparser->parse_variable_extra('@', $var);
							$vars[$var] = true;
						}
					}
				}
			}
		}
		clearcache();
		return '_back';
	}

	/**
		* list Action
		*/

	public function listAction(&$context,&$errro) 
	{
		include_once 'translationfunc.php';

		$this->_setTextGroups($context);
		if(!function_exists('loop_textgroups')) {
			function loop_textgroups(&$context,$funcname)
			{
				if(empty($context['textgroups']) || empty($GLOBALS['translations_textgroups'][$context['textgroups']])) return;

				// permettre aux adminlodel d'accéder à tous les textgroup de traduction 
				if ($context['textgroups']=='interface' && C::get('adminlodel', 'lodeluser')) {
					global $db;
					$textgroups = $db->GetCol(lq("select distinct textgroup from #_MTP_texts;"));
					$GLOBALS['translations_textgroups'][$context['textgroups']] = array_unique(array_merge($GLOBALS['translations_textgroups'][$context['textgroups']], $textgroups));
				}

				foreach($GLOBALS['translations_textgroups'][$context['textgroups']] as $textgroup) {
					$localcontext=$context;
					$localcontext['textgroup']=$textgroup;
					call_user_func("code_do_".$funcname,$localcontext);
				}
			}
		}
		if(!function_exists('loop_alltexts')) {
			function loop_alltexts(&$context,$funcname)
			{
				global $db,$distincttexts,$alltexts_cache;

				if(empty($context['textgroup'])) return;
				if(defined('backoffice-lodeladmin'))
				{
					$sql = "SELECT t.* FROM #_TP_texts t JOIN #_TP_translations tr ON (t.lang=tr.lang) WHERE t.status>=-1 AND tr.status>=-1 AND t.textgroup='".$context['textgroup']."' ORDER BY tr.rank, t.name";
				}
				else
				{
					$sql = "SELECT t.* FROM #_TP_texts t JOIN #_TP_translations tr ON (t.lang=tr.lang AND t.textgroup=tr.textgroups) WHERE t.status>=-1 AND tr.status>=-1 AND t.textgroup='".$context['textgroup']."' ORDER BY tr.rank, t.name";
				}

				$result=$db->execute(lq($sql)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

				$distincttexts=array();
				while(!$result->EOF) {
					$lang=strtolower($result->fields['lang']);
					$name=$result->fields['name'];	
					if ($name && $lang) {
						$alltexts_cache[$lang][$name]=$result->fields;
						if ($lang==C::get('sitelang')) {
							$distincttexts[$name]=$result->fields['contents'];
						} elseif (!isset($distincttexts[$name])) {
							$distincttexts[$name]=true;
						}
					} // valid name
					$result->MoveNext();
				}
				foreach($distincttexts as $name=>$contents) {
					$localcontext=$context;
					$localcontext['name']=$name;
					$localcontext['contents']=$contents;
					call_user_func("code_do_".$funcname,$localcontext);
				}
			}
		}
		if(!function_exists('loop_lang_and_text')) {
			function loop_lang_and_text(&$context,$funcname)
			{
				global $alltexts_cache, $distincttexts, $db;
				$logic = null;
				if(empty($context['textgroups']) || empty($context['name'])) return;

				foreach(array_keys($alltexts_cache) as $lang) {
					$row = isset($alltexts_cache[$lang][$context['name']]) ? $alltexts_cache[$lang][$context['name']] : null;
					$localcontext = $row ? array_merge($context, $row) : $context;
					$localcontext['id'] = (int) (isset($localcontext['id']) ? $localcontext['id'] : 0);
					if(0 === $localcontext['id']) { // entry doesn't exist in this lang
						if(!isset($logic)) $logic = Logic::getLogic('texts');
						$logic->createTexts($localcontext['name'], $localcontext['textgroups']);
						
						$result=$db->execute(lq("SELECT status,contents,name,id,lang FROM #_TP_texts WHERE status>=-1 AND textgroup='".$localcontext['textgroup']."' AND name='{$localcontext['name']}' ORDER BY lang")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			
						$distincttexts[$localcontext['name']]=array();
						while(!$result->EOF) {
							$l=strtolower($result->fields['lang']);
							if ($l) {
								$alltexts_cache[$l][$localcontext['name']]=$result->fields;
								if ($l==C::get('sitelang')) {
									$distincttexts[$localcontext['name']]=$result->fields['contents'];
								} elseif (!isset($distincttexts[$localcontext['name']])) {
									$distincttexts[$localcontext['name']]=true;
								}
							} // valid name
							$result->MoveNext();
						}
					}
					$row= isset($alltexts_cache[$lang][$context['name']]) ? $alltexts_cache[$lang][$context['name']] : null;
					$localcontext = $row ? array_merge($context, $row) : $context;
					call_user_func("code_do_".$funcname, $localcontext);
				}
			}
		}
		return "_ok";
	}

	/**
		* add/edit Action
		*/
	public function editAction(&$context,&$error,$clean=false)
	{
		$this->_setTextGroups($context);
		if (empty($context['id'])) $context['modificationdate'] = date("Y-m-d");

		$ret = parent::editAction($context,$error);
		clearcache();
		return $ret;
	}

	/**
		* export Action
		*/
	public function exportAction(&$context,&$error)
	{
		function_exists('validfield') || include "validfunc.php";

		$lang = isset($context['lang_trad']) ? $context['lang_trad'] : 'all';
		
		if ($lang != "all" && !validfield($lang, "lang"))
			trigger_error("ERROR: invalid lang", E_USER_ERROR);
		
		// lock the database
		//lock_write("translations","textes");

		$tmpfile=tempnam(tmpdir(),"lodeltranslation");

		class_exists('XMLDB_Translations', false) || include "translationfunc.php";

		$this->_setTextGroups($context);
		$xmldb=new XMLDB_Translations($context['textgroups'], $lang);

		#$ret=$xmldb->saveToString();
		#trigger_error($ret, E_USER_ERROR);

		$xmldb->saveToFile($tmpfile);

		$filename="translation-$lang-".date("dmy").".xml";

		download($tmpfile, $filename);
		@unlink ($tmpfile);
		exit();

		return "back";
	}


	public function importAction(&$context,&$error)
	{
		$this->_setTextGroups($context);
		$lang="";

		function_exists('extract_import') || include "importfunc.php";
		$file = extract_import("translation",$context,"xml");

		if ($file) {
			class_exists('XMLDB_Translations', false) || include("translationfunc.php");
			$xmldb=new XMLDB_Translations($context['textgroups']);
			
			$xmldb->readFromFile($file);
			clearcache();

			return "_back";
		}

		if(!function_exists('loop_files'))
		{
		function loop_files(&$context,$funcname)
		{
			global $fileregexp,$importdirs;
		
			foreach ($importdirs as $dir) {
				if ( $dh= @opendir($dir)) {
					while (($file=readdir($dh))!==FALSE) {
						if (!preg_match("/^$fileregexp$/i",$file)) continue;
						$localcontext=$context;
						$localcontext['filename']=$file;
						$localcontext['fullfilename']="$dir/$file";
						call_user_func("code_do_$funcname",$localcontext);	   
					}
					closedir ($dh);
				}
			}
		}
		}
		if(!function_exists('loop_translation'))
		{
			function loop_translation(&$context,$funcname)
			{
				if(empty($context['fullfilename'])) return;

				$arr=preg_split("/<\/?row>/", file_get_contents($context['fullfilename']));

				$langs=array();
				for($i=1; $i<count($arr); $i+=2) {
					$localcontext=$context;
					foreach (array("lang","title","creationdate","modificationdate") as $tag) {
						if (preg_match("/<$tag>(.*)<\/$tag>/",$arr[$i],$result)) 
							$localcontext[$tag]=trim(strip_tags($result[1]));
					}
					if (empty($localcontext['lang'])) continue;
					call_user_func("code_do_$funcname",$localcontext);
				}
			}
		}
		return "import_translations";
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

	/**
		* Set the textgroups
		*/

	protected function _setTextGroups(&$context) 
	{
		$context['textgroups'] = C::get('site', 'cfg') ? "site" : "interface";
	}

	/**
	* Sauve des données dans des tables liées éventuellement
	*
	* Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a été créé
	* @param array $context le contexte
	*/
	protected function _saveRelatedTables($vo,&$context) 
	{
		global $db;
		//
		// create all the texts if needed
		// 
		// can't use insert select... so it not really funny to do
		//

		
		if (!$vo->lang) { // get the lang if we don't have it
			$dao=$this->_getMainTableDAO();
			$vo=$dao->getById($vo->id);
		}
		if ($vo->lang==C::get('lang', 'lodeluser')) {
			// get any lang... this should not happen anyway
			$dao=$this->_getMainTableDAO();
			$vo2=$dao->find("status>0","lang");
			$fromlang=$vo2->lang;
		} else {
			// normal case... should be different !
			$fromlang=C::get('lang', 'lodeluser');
		}
		$textscriteria=textgroupswhere( C::get('site', 'cfg') ? "site" : "interface" );

		// get all the text name, group, text in current lang for which the translation does not exists in the new lang
		$result=$db->execute(lq("SELECT t1.name,t1.textgroup,t1.contents FROM #_TP_texts as t1 LEFT OUTER JOIN #_TP_texts as t2 ON t1.name=t2.name AND t1.textgroup=t2.textgroup AND t2.lang='".$vo->lang."' WHERE t1.status>-64 AND t1.lang='".$fromlang."' AND t2.id IS NULL AND t1.".$textscriteria." GROUP BY t1.name,t1.textgroup")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		do { // use multiple insert but not to much... to minimize the size of the query
			$inserts=array(); $count=0;
			while (!$result->EOF && $count<20) {
				$row=$result->fields;
				#$langs=explode(",",$row['langs']); // get the lang
							#if (in_array($lang,$langs)) continue; // the text already exists in the correct lang
							#echo $row['name']," ";
				
				$inserts[]="('".$row['name']."','".$row['textgroup']."','".mysqli_escape_string($row['contents'])."','-1','".$context['lang']."')";
				$count++;
				$result->MoveNext();
			}
			if ($inserts) 
				$db->execute(lq("INSERT INTO #_TP_texts (name,textgroup,contents,status,lang) VALUES ".join(",",$inserts))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		} while (!$result->EOF);
	}

	/**
	 * Suppression dans les tables liées
	 *
	 * @param integer $id identifiant numérique de l'objet supprimé
	 */
	protected function _deleteRelatedTables($id) 
	{
		//il faut supprimer les texts associés à la traduction
		global $db;

		if (!$this->vo) {
			trigger_error("ERROR: internal error in Translations::deleteAction", E_USER_ERROR);
		}
		
		$db->execute(lq('DELETE FROM #_TP_texts WHERE lang="'.$this->vo->lang.'" AND textgroup="'.$this->vo->textgroups.'"')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		unset($this->vo);

		return '_back';
	}

	/**
	* Appelé avant l'action delete
	*
	* Cette méthode est appelée avant l'action delete pour effectuer des vérifications
	* préliminaires à une suppression.
	*
	* @param object $dao la DAO utilisée
	* @param array &$context le contexte passé par référénce
	*/
	protected function _prepareDelete($dao, &$context)
	{
		if(empty($context['id']))
			trigger_error("ERROR: missing id in Translations::deleteAction", E_USER_ERROR);
		$id = $context['id'];
		// gather information for the following
		$this->vo=$dao->getById($id);
		if (!$this->vo) trigger_error("ERROR: internal error in Translations::deleteAction", E_USER_ERROR);
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('lang' => array('text', '+'),
									'title' => array('text', ''),
									'textgroups' => array('text', ''),
									'translators' => array('text', ''),
									'creationdate' => array('date', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('lang', 'textgroups'), );
	}
	// end{uniquefields} automatic generation  //
} // class 


/*-----------------------------------*/
/* function pipe                     */
if(!function_exists('textgroupswhere'))
{
	function textgroupswhere($textgroups)
	{
		if (!$textgroups) trigger_error("ERROR: which textgroups ?", E_USER_ERROR);
		if (!empty($GLOBALS['translations_textgroups'][$textgroups])) {
			return "textgroup IN ('".join("','",$GLOBALS['translations_textgroups'][$textgroups])."')";
		} else {
			trigger_error("ERROR: unkown textgroup", E_USER_ERROR);
		}
	}
}