<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Chargement d'un document OpenOffice (via OTX)
 */

define('backoffice', true);
define('backoffice-edition', true);
require 'siteconfig.php';

function printJavascript($msg)
{
	echo str_repeat(" ", 256); // for IE
	echo '<script type="text/javascript">'.$msg.'</script>';
	flush();
}

function printErrors($errors, $exit = true, $isFrame = true)
{
	if(!is_array($errors))
		$errors = (array) $errors;

	if($isFrame)
		echo '<script type="text/javascript">';

	foreach($errors as $error){
		$error = addcslashes(str_replace("\n", "", $error), '"');
		echo $isFrame ? 'window.parent.o.error("<p class=\"error\">Error: ' . $error . '</p>");' : "<p class=\"error\">Error: $error </p>";
	}

	if($isFrame)
		echo '</script>';
	flush();

	if($exit) die;
}

try
{
	include 'auth.php';

	authenticate(LEVEL_REDACTOR);
	// require 'func.php';
	include 'utf8.php'; // conversion des caracteres
	defined('INC_FUNC') || include 'func.php';

	$context =& C::getC();
	header('content-type: text/html; charset=utf8');
	foreach(array('lodeltags', 'reload', 'iddocument') as $var)
	{
		if(isset($context[$var])) $context[$var] = (int)$context[$var];
		else $context[$var] = 0;
	}

	if (!$context['identity'] && !$context['idtype']) {
		header("location: index.php?id=". $context['idparent']);
		return;
	}
	$context['id'] = $context['identity'];
	$fileorigin = C::get('fileorigin');
	$localfile = C::get('localfile');
	$isFrame = ! (C::get('sortietei') || C::get('sortie')) && C::get('adminlodel', 'lodeluser');
	$task = Logic::getLogic('tasks');

	$file_cache_lifetime = C::get('timeout', 'cfg') ? C::get('timeout', 'cfg') : 3600;

	if($fileorigin == 'upload' && !empty($_FILES['file1'])) {
		$file = $_FILES['file1'];
		if($file['error'] > 0)
		{
			switch($file['error'])
			{
				case UPLOAD_ERR_NO_FILE: printErrors('Missing file', true, $isFrame); break;
				case UPLOAD_ERR_INI_SIZE: printErrors('Filesize is more than limit configuration : '.ini_get('upload_max_filesize'), true, $isFrame); break;
				case UPLOAD_ERR_FORM_SIZE: printErrors('Filesize is more than form limit configuration : 25Mo', true, $isFrame); break;
				case UPLOAD_ERR_PARTIAL: printErrors('Error while transfering, try again', true, $isFrame); break;
				case UPLOAD_ERR_NO_TMP_DIR: printErrors('No temporary directory found', true, $isFrame); break;
				case UPLOAD_ERR_CANT_WRITE: printErrors("Can't write file", true, $isFrame); break;
				case UPLOAD_ERR_EXTENSION: printErrors('A PHP extension stopped file upload', true, $isFrame); break;
				default: printErrors('An error occured, try again', true, $isFrame); break;
			}
		}

		if(empty($file['tmp_name']) || $file['tmp_name'] == 'none' || !is_uploaded_file($file['tmp_name']))
			printErrors("Le fichier n'est pas un fichier chargé", true, $isFrame);

		$file1 = $file['tmp_name'];
		$sourceoriginale = $file['name'];
		$tmpdir = tmpdir(uniqid('import_', true)); // use here and later.
		$source = $tmpdir. DIRECTORY_SEPARATOR . basename($file1). '-source';

		// move first because some provider does not allow operation in the upload dir
		if(!move_uploaded_file($file['tmp_name'], $source))
			printErrors("Impossible de déplacer le fichier chargé", true, $isFrame);
	}
	elseif($fileorigin == 'serverfile' && $localfile)
	{
		$sourceoriginale = basename($localfile);
		$file1           = SITEROOT. 'upload/'. $sourceoriginale;
		$tmpdir          = tmpdir(uniqid('import_', true)); // use here and later.
		$source          = $tmpdir. DIRECTORY_SEPARATOR . basename($file1). '-source';
		if(!copy($file1, $source))
			printErrors("Impossible de déplacer le fichier", true, $isFrame);
	}
	else
	{
		$file1           = '';
		$sourceoriginale = '';
		$source          = '';
	}

	if(empty($source))
	{
		$context['url'] = 'oochargement.php?'.$_SERVER['QUERY_STRING'];
		View::getView()->render('oochargement', !(bool)$file1);
		die;
	}

	if($isFrame) printJavascript("window.parent.o.changeStep(1);");

	$auth_imp = C::get('authorized_import', 'cfg') ? C::get('authorized_import', 'cfg') : array('doc', 'rtf', 'sxw', 'odt');
	set_time_limit(0);
	$sources = $context['urls'] = array();
	$ext = strtolower(pathinfo($sourceoriginale, PATHINFO_EXTENSION));
        $tmp_importdir = C::get('tmp_importdir', 'cfg');
	$userid = C::get('id', 'lodeluser');
	if ($context['idtype'] === 2) {
		$mask = $tmp_importdir."*-".$userid."-".$context['id'];
   		array_map( "unlink", glob( $mask ) );
	}
	if($ext === 'zip')
	{ // multiple

		if(empty($context['multiple']))
		{
			if($isFrame) printJavascript('window.parent.o.changeStep(2);');

			extract_files_from_zip($source, $tmpdir);

			$dir = opendir($tmpdir);
			while ($file = readdir($dir)) {
				if(is_file( $tmpdir . DIRECTORY_SEPARATOR . $file ) && preg_match( "/\.xml$/",$file) ){
					$contents = array();
					$teiContents = file_get_contents($tmpdir . DIRECTORY_SEPARATOR . $file);
					if(empty($context['idtype']))
					{ // reload
						$context['idtype'] = $GLOBALS['db']->GetOne(lq('SELECT idtype FROM #_TP_entities WHERE id='.(int)$context['identity']));
					}
					try
					{
						$parser = new TEIParser($context['idtype']);
                        if (!empty($tmp_importdir)) {
                            if (!file_exists($tmp_importdir)) {
                                mkdir($tmp_importdir, 0700, true);
                            }
                            $tmp_importfile = $tmp_importdir.'/'.basename($file1).'-'.$userid.'-'.$context['id'];
                            file_put_contents($tmp_importfile, serialize($parser->parse($teiContents, '', $tmpdir, $sourceoriginale)));
                            $contents['contents'] = '/'.basename($file1).'-'.$userid.'-'.$context['id'];
                            $contents['use_importdir'] =  true;
                        } else{
						    $contents['contents'] = $parser->parse($teiContents, '', $tmpdir, $sourceoriginale);
                        }
					}
					catch(Exception $e)
					{
						printErrors($e->getMessage(), true, $isFrame);
					}

					$contents['parserreport'] = $parser->getLogs();

					if(C::get('sortie') && C::get('adminlodel', 'lodeluser'))
					{
					    array_walk_recursive($contents, function(&$var) { $var = htmlentities($var, ENT_COMPAT, "UTF-8");});
						echo '<pre>'. print_r($contents, 1) . '</pre>';
						die();
					}

					$row = array();
					$row['fichier']         = $contents;
					$row['tei']             = $teiContents;
					$row['sourceoriginale'] = $sourceoriginale;
					$row['source']          = file_get_contents($source);
					// build the import
					$row['importversion']   = "oochargement ".C::get('version', 'cfg').";";
					$row['identity']        = $context['identity'];
					$row['idparent']        = $context['idparent'];
					$row['idtype']          = $context['idtype'];
					$row['reload']          = $context['reload'];
					$row['tmpdirs']         = array($tmpdir, realpath(SITEROOT.'docannexe/image/'.basename($tmpdir))); // TEIParser.php l429

					delete_files($source);
					unset($contents);

					$idtask = $task->createAction("Import $file1", 3, $row);
					printJavascript('window.parent.o.changeStep(3, "'.$idtask.'");');

					die;
				}
			}
		}
		else
		{
			$oldtmpdir = $tmpdir;
			$tmpdir = array();

			$extracted_files = extract_files_from_zip($source, $oldtmpdir);
			if($extracted_files)
			{
				foreach($extracted_files as $file)
				{
					if(in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $auth_imp))
					{
						$tmp = tmpdir(uniqid('import_', true));
						rename($oldtmpdir . DIRECTORY_SEPARATOR . $file, $tmp . DIRECTORY_SEPARATOR . $file);
						$sources[$file] = $tmp . DIRECTORY_SEPARATOR . $file;
						$tmpdir[] = $tmp;
					}
				}
			}else{
				printErrors('No files were extracted from the archive '.$source, true, $isFrame);
			}

			if(!function_exists('removefilesfromimport'))
			{
				function removefilesfromimport($rep)
				{
					if(!file_exists($rep)) return;
					$fd = @opendir($rep) or trigger_error("Impossible d'ouvrir $rep", E_USER_ERROR);
					while (($file = readdir($fd)) !== false) {
						if('.' === $file{0}) continue;
						$file = $rep. "/". $file;
						if (is_dir($file)) { //si c'est un répertoire on execute la fonction récursivement
							removefilesfromimport($file);
							// puis on supprime le répertoire
							@rmdir($file);
						} else {@unlink($file);}
					}
					closedir($fd);
					@rmdir($rep);
				}
			}
			removefilesfromimport($oldtmpdir);
			$context['multiple'] = count($sources);
		}
	}
	elseif($ext === 'xml')
	{
		if($isFrame) printJavascript('window.parent.o.changeStep(2);');

		$contents = array();
		$teiContents = file_get_contents($source);
		if(empty($context['idtype']))
		{ // reload
			$context['idtype'] = $GLOBALS['db']->GetOne(lq('SELECT idtype FROM #_TP_entities WHERE id='.(int)$context['identity']));
		}
		try
		{
			$parser = new TEIParser($context['idtype']);
            if (!empty($tmp_importdir)) {
                            if (!file_exists($tmp_importdir)) {
                                mkdir($tmp_importdir, 0700, true);
                            }
                            $tmp_importfile = $tmp_importdir.'/'.basename($file1).'-'.$userid.'-'.$context['id'];
                            file_put_contents($tmp_importfile, serialize($parser->parse($teiContents, '', $tmpdir, $sourceoriginale)));
                            $contents['contents'] = '/'.basename($file1).'-'.$userid.'-'.$context['id'];
                            $contents['use_importdir'] =  true;
             } else{
				    $contents['contents'] = $parser->parse($teiContents, '', $tmpdir, $sourceoriginale);
            }
        }
		catch(Exception $e)
		{
			printErrors($parser->getLogs(), false, $isFrame);
//			printErrors($e->getMessage(), true, $isFrame);
		}

		$contents['parserreport'] = $parser->getLogs();

		if(C::get('sortie') && C::get('adminlodel', 'lodeluser'))
		{
		    array_walk_recursive($contents, function(&$var) {$var = htmlentities($var, ENT_COMPAT, "UTF-8");});
			echo '<pre>'. print_r($contents, 1) . '</pre>';
			die();
		}

		$row = array();
		$row['fichier']         = $contents;
		$row['tei']             = $teiContents;
		$row['sourceoriginale'] = $sourceoriginale;
		// build the import
		$row['importversion']   = "oochargement ".C::get('version', 'cfg').";";
		$row['identity']        = $context['identity'];
		$row['idparent']        = $context['idparent'];
		$row['idtype']          = $context['idtype'];
		$row['reload']          = $context['reload'];
		$row['tmpdirs']         = array($tmpdir, realpath(SITEROOT.'docannexe/image/'.basename($tmpdir))); // TEIParser.php l429

		delete_files($source);
		unset($contents);

		$idtask = $task->createAction("Import $file1", 3, $row);
		printJavascript('window.parent.o.changeStep(3, "'.$idtask.'");');

		die;
	}
	elseif(!in_array($ext, $auth_imp))
	{
		printErrors('Invalid file type for document <em>'.$sourceoriginale.'</em>, authorized are '.implode(', ', $auth_imp), true, $isFrame);
	}
	elseif(!empty($context['multiple']))
	{
		printErrors('You can not import single file while using massive import mode', true, $isFrame);
	}
	else
	{
		$sources = array($sourceoriginale => $source);
		$tmpdir = array($tmpdir);
	}

	$user = C::get('id', 'lodeluser').';'.C::get('name', 'lodeluser').';'.C::get('rights', 'lodeluser');
	$site = C::get('site', 'cfg');
	defined('INC_CONNECT') || include 'connect.php';
	global $db;
	$url = $db->GetOne(lq('SELECT url FROM #_MTP_sites WHERE name='.$db->quote($site)));

	$client = new OTXClient();
	$errors = array();
	$i = 0;
	do
	{
		$options = $client->selectServer($i++);
		if(!$options) break;
		$options['lodel_user'] = $user;
		$options['lodel_site'] = $site;

		$client->instantiate($options);
		if($client->error)
			$errors[] = 'Connection failed for documentt <em>'.$sourceoriginale.'</em>: '.$client->status;
		else break;
	} while (1);

	if($client->error)
	{
		$context['error'] = join('<br/>', $errors);
		$context['url'] = 'oochargement.php?'.$_SERVER['QUERY_STRING'];
		View::getView()->render('oochargement', false);
		die;
	}

	// get the XML schema of the editorial model
	C::set('do', 'import');
	$datas['title'] = 'ME';
	$datas['description'] = 'ME OTX';
	$datas['author'] = 'Lodel';
	$datas['modelversion'] = 1;
	$schema = Logic::getLogic('data')->generateXML($datas);
	$i = 0;
	$mode = C::get('mode') ? C::get('mode') : 'strict';
	$nb = count($sources);
	if(empty($context['idtype']))
	{ // reload
		$context['idtype'] = $GLOBALS['db']->GetOne(lq('SELECT idtype FROM #_TP_entities WHERE id='.(int)$context['identity']));
	}

	$parser = new TEIParser($context['idtype']);

	foreach($sources as $sourceoriginale => $source)
	{
		++$i;
		$nomoriginal = $sourceoriginale;
		$sourceoriginale = preg_replace('/[^a-zA-Z\-_\.0-9]/', '-', $sourceoriginale, -1, $count);

		if(!empty($context['multiple']))
		{
			printJavascript('window.parent.o.changeStep(1, "'.addcslashes(sprintf(getlodeltextcontents('OTX_PROCESSING_CURRENT_FILE', 'edition'), $i, $nb, $sourceoriginale), '"').'");');
		}

		$request = array('schema' => $schema);
		$request['attachment'] = $source;
		$request['mode'] = 'lodel:'.$mode;
		$request['site'] = $site;
		$request['sourceoriginale'] = $sourceoriginale;

		$client->request($request);

		if(!$client->error)
		{
			if($isFrame)
			{
				printJavascript('window.parent.o.changeStep(2, "'.$sourceoriginale.'");');
			}

			if(empty($context['multiple']) && C::get('sortietei') && C::get('adminlodel', 'lodeluser'))
			{
				header("Content-Type: application/xml; charset=UTF-8");
				echo $client->xml;
				die();
			}

			$odtconverted = $source.'-odt.converted';
			if(!writefile($odtconverted, $client->odt))
			{
				printErrors('unable to write .odt converted file for document <em>'.$sourceoriginale.'</em>', empty($context['multiple']), $isFrame);
			}

			$tei = $source. '.tei';
			if($client->xml == NULL)
			{
				printErrors('Generated XML is empty !', empty($context['multiple']), $isFrame);
			}

			if(!writefile($tei, $client->xml))
			{
				printErrors('unable to write tei file for document <em>'.$sourceoriginale.'</em>', empty($context['multiple']), $isFrame);
			}

			$contents = array();

			try
			{
                if (!empty($tmp_importdir)) {
                            if (!file_exists($tmp_importdir)) {
                                mkdir($tmp_importdir, 0700, true);
                            }
                            $tmp_importfile = $tmp_importdir.'/'.basename($file1).'-'.$userid.'-'.$context['id'];
                            file_put_contents($tmp_importfile, serialize($parser->parse($client->xml, $odtconverted, $tmpdir[$i - 1], $sourceoriginale)));
                            $contents['contents'] = '/'.basename($file1).'-'.$userid.'-'.$context['id'];
                            $contents['use_importdir'] =  true;
                        } else{
						    $contents['contents'] = $parser->parse($client->xml, $odtconverted, $tmpdir[$i - 1], $sourceoriginale);
                        }
			}
			catch(Exception $e)
			{
				printErrors($parser->getLogs(), false, $isFrame);
				printErrors($e->getMessage(), empty($context['multiple']), $isFrame);
				if(!empty($context['multiple'])) continue;
			}

			$contents['parserreport'] = $parser->getLogs();
			$contents['otxreport'] = json_decode($client->report, true);
			if(false === $contents['contents'])
			{
				printErrors($contents['parserreport'], empty($context['multiple']), $isFrame);
				if(!empty($context['multiple'])) continue;
			}

			if(empty($context['multiple'])) unset($client);

			if(empty($context['multiple']) && C::get('sortie') && C::get('adminlodel', 'lodeluser'))
			{
			    array_walk_recursive($contents, function(&$var) {$var = htmlentities($var, ENT_COMPAT, "UTF-8");});
				echo '<pre>'. print_r($contents, 1) . '</pre>';
				die();
			}

			$row = array();
			$row['fichier']         = $contents;
			$row['odt']             = file_get_contents($odtconverted);
			$row['tei']             = file_get_contents($tei);
			$row['source']          = file_get_contents($source);
			$row['sourceoriginale'] = $sourceoriginale;
			// build the import
			$row['importversion']   = "oochargement ".C::get('version', 'cfg').";";
			$row['identity']        = $context['identity'];
			$row['idparent']        = $context['idparent'];
			$row['idtype']          = $context['idtype'];
			$row['reload']          = $context['reload'];
			$row['tmpdirs']         = array($tmpdir[$i - 1], realpath(SITEROOT.'docannexe/image/'.basename($tmpdir[$i - 1]))); // TEIParser.php l429

			delete_files($source, $tei, $odtconverted);
			unset($contents);

			$idtask = $task->createAction("Import $file1", 3, $row);
			if(empty($context['multiple']))
			{
				printJavascript('window.parent.o.changeStep(3, "'.$idtask.'");');
			}
			else
			{
				$html = '<div class="otxfile"><input type="button" class="styled styled_green right" value="'.getlodeltextcontents('continue', 'edition').'" onclick="window.open(\'checkimport.php?idtask='.$idtask.'\');"/><p class="filename">'.$sourceoriginale.'</p><p class="doctitle">'.strip_tags($parser->getDocTitle(), '<em><sup><sub><span><strong><a>').'</p></div>';

				printJavascript('window.parent.o.changeStep(3, "'.addcslashes($html, '"').'");');
			}
		}
		else
		{
			switch($client->error)
			{
				case E_USER_ERROR:
					$type = 'INTERNAL ERROR: ';
					break;

				case E_ERROR:
					$type = 'FATAL ERROR: ';
					break;

				default:
					$type = 'UNKNOWN ERROR: ';
					break;
			}
			printErrors($type."Conversion failed for document <em>".$sourceoriginale.'</em> :<br/>'.htmlentities($client->status, ENT_COMPAT, 'UTF-8'), empty($context['multiple']), $isFrame);
		}
	}

	exit;
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
