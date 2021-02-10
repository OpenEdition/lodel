<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des entités (gestion de l'import)
 *
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

		// accepter l'import depuis une tache ou depuis le context
		if(empty($context['idtask']) && empty($context['task']))
			View::getView()->back();

		$taskLogic = Logic::getLogic('tasks');
		if (!empty($context['idtask'])) {
			$idtask = $context['idtask'];
			$this->task = $task = $taskLogic->getTask($idtask);
		} else {
			$this->task = $task = $context['task'];
			unset($context['task']);
		}
        $tmp_importdir = C::get('tmp_importdir', 'cfg');
        if (!empty($tmp_importdir)) {
            $importfile = $tmp_importdir.$task['fichier']['contents'];
            if (isset($task['fichier']['use_importdir']) && $task['fichier']['use_importdir']) {
                $this->task['fichier']['contents'] = unserialize(file_get_contents($importfile));
                $task['fichier']['contents'] = $this->task['fichier']['contents'];
                //delete_files($importfile);
               unlink($importfile); 
            }
        }
		if (!$task)
			View::getView()->back();
		$taskLogic->populateContext($task, $context);

		$context['id'] = !empty($task['identity']) ? $task['identity'] : 0;
		$source = isset($task['source']) ? $task['source'] : null;
		$context['creationinfo'] = $task['sourceoriginale'];
		$context['idparent'] = $task['idparent'];
		$odt = isset($task['odt']) ? $task['odt'] : null;
		$tei = $task['tei'];
		$tmp_importdir = C::get('tmp_importdir', 'cfg');
	    $contents = $task['fichier'];

		unset($task);

		// restore the entity
		if(!$contents) trigger_error("ERROR: internal error in Entities_ImportLogic::importAction", E_USER_ERROR);
		$context['entries'] = !empty($contents['contents']['entries']) ? $contents['contents']['entries'] : array();
		$context['externalentries'] = !empty($contents['contents']['externalentries']) ? $contents['contents']['externalentries'] : array();
		$context['persons'] = !empty($contents['contents']['persons']) ? $contents['contents']['persons'] : array();
		$context['entities'] = !empty($contents['contents']['entities']) ? $contents['contents']['entities'] : array();
		$context['data'] = $contents['contents'];
		$context['creationmethod'] = "otx";
		unset($contents);

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

		// close the task
		if (isset($idtask)) {
			$taskContext = array('id'=>$idtask);
			$taskLogic->deleteAction($taskContext, $error);
		}

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

   // begin{publicfields} automatic generation  //
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //
   // end{uniquefields} automatic generation  //
} // class 
