<?php
/**
 * Chargement d'un document OpenOffice (via ServOO)
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source/lodel/edition
 */

define('backoffice', true);
require 'siteconfig.php';

try
{
function lodelprocessing(&$xhtml)
{
    $xhtml = str_replace(array("&#39;", "&apos;"), array("'", "'"), $xhtml);
    return false;
}

function imagesnaming($filename, $index, $uservars)
{
    return $uservars. "_". $index. strrchr($filename, '.');
}

function cleanList($text)
{
    $arr = preg_split("/(<\/?(?:ul|ol)\b[^>]*>)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $count = count($arr);
    $arr[0] = addList($arr[0]);
    $inlist = 0;
    $start = 0;
    for($i = 1; $i < $count; $i+= 2) {
        if ($arr[$i][1] == "/") { // closing
            $inlist--;
            if ($inlist == 0) {
                $arr[$i].= "</r2r:puces>"; 
            } // end of a list
        } else { // opening
            if ($inlist == 0) {
                $arr[$i] = "<r2r:puces>". $arr[$i];
            } // beginning of a list
            $inlist++;
        }
        if ($inlist > 0) { // in a list
            $arr[$i+1] = preg_replace("/<\/?r2r:[^>]+>/", " ", $arr[$i+1]);
        } else { // out of any list
            $arr[$i+1] = addList($arr[$i+1]);
        }
    }
    $text = join("", $arr);
    return preg_replace("/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
                    "", $text);
}

function addList($text)
{ // especially for RTF file where there are some puces but no li
    return preg_replace(array(
                "/<r2r:(puces?)>(.*?)<\/r2r:\\1>/", // put li
                "/<\/r2r:(puces?)>((?:<\/?(p|br)(?:\s[^>]*)?\/?>|\s)*)<r2r:\\1(?:\s[^>]*)?>/s", // process couple 
                "/(<r2r:puces?>)/",  // add ul
                "/(<\/r2r:puces?>)/" // add /ul
                ),
                array("<r2r:\\1><li>\\2</li></r2r:\\1>",
                "",
                "\\1<ul>",
                "</ul>\\1"
                ), $text);
}

	include 'auth.php';
	authenticate(LEVEL_REDACTOR);
	// require 'func.php';
	include 'utf8.php'; // conversion des caracteres
	defined('INC_FUNC') || include 'func.php';
	
	$context =& C::getC();
	
	foreach(array('idtask', 'lodeltags', 'reload', 'iddocument') as $var)
	{
		if(isset($context[$var]))
		$context[$var] = (int)$context[$var];
		else $context[$var] = 0;
	}
	
	if (!$context['idtask'] && !$context['identity'] && !$context['idtype']) {
		header("location: index.php?id=". $context['idparent']);
		return;
	}
	$context['id'] = $context['identity'];
	$task = $context['idtask'];
	$fileorigin = C::get('fileorigin');
	$localfile = C::get('localfile');
    
	if ($fileorigin == 'upload' && isset($_FILES['file1']) && $_FILES['file1']['tmp_name'] && $_FILES['file1']['tmp_name'] != 'none') {
		$file1 = $_FILES['file1']['tmp_name'];
		if (!is_uploaded_file($file1)) {
			trigger_error(utf8_encode("Le fichier n'est pas un fichier charg�"), E_USER_ERROR);
		}
		$sourceoriginale = $_FILES['file1']['name'];
		$tmpdir = tmpdir(); // use here and later.
		$source = $tmpdir. "/". basename($file1). '-source';
		move_uploaded_file($file1, $source); // move first because some provider does not allow operation in the upload dir
	} elseif ($fileorigin == 'serverfile' && $localfile) {
		$sourceoriginale = basename($localfile);
		$file1           = SITEROOT. 'upload/'. $sourceoriginale;
		$tmpdir          = tmpdir(); // use here and later.
		$source          = $tmpdir. "/". basename($file1). '-source';
		copy($file1, $source);
	} else {
		$file1           = '';
		$sourceoriginale = '';
		$source          = '';
	}


	if($source)
	{
		set_time_limit(0);
		$context['error'] = '';
		$sources = $context['urls'] = array();
		$context['multiple'] = false;
		$ext = substr($sourceoriginale, -4);
		if($ext === '.zip')
		{ // multiple
			$unzipcmd = C::get('unzipcmd', 'cfg');
			if ($unzipcmd && $unzipcmd != "pclzip") {
				$line = `$unzipcmd -o -d $tmpdir $source`;
				$line = explode("\n", $line);
				if(count($line) > 1 && !empty($line[1]))
				{
					unset($line[0]);
					foreach($line as $file)
					{
						$file = trim(substr($file, strpos($file, ':') + 1));
						$sources[basename($file)] = $file;
					}
				}
				else
				{
					$context['error'] .= 'No files were extracted from the archive';
				}
			} else {
				function LodelOtxPostExtractCallBack($p_event, &$p_header)
				{
					// ----- look for valid extraction
					if ($p_header['status'] == 'ok') {
						rename($p_header['filename'], $p_header['filename'].'-source');
						return 1;
					}
					return 0;
				}
				
				class_exists('PclZip', false) || include "pclzip/pclzip.lib.php";
				$archive = new PclZip($source);
				$arr = $archive->extract(PCLZIP_OPT_PATH, $tmpdir, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_CB_POST_EXTRACT, 'LodelOtxPostExtractCallBack');
				
				if($arr)
				{
					foreach($arr as $file)
					{
						$sources[$file['stored_filename']] = $file['filename'].'-source';
					}
				}
			}
			
			$context['multiple'] = true;
		}
		elseif($ext === '.xml')
		{
			$parser = new TEIParser($context['idtype']);
			$contents = array();
			
			$contents['contents'] = $parser->parse(file_get_contents($source), '');
			$contents['parserreport'] = $parser->getLogs();
			$contents['otxreport'] = '';
			$row = array();
			$fileconverted = $source. '.converted';
			if (!writefile($fileconverted, base64_encode(serialize($contents)))) 
			{
				$context['error'] .= 'unable to write converted file<br/>';
				$context['url'] = 'oochargement.php?'.$_SERVER['QUERY_STRING'];
				View::getView()->render('oochargement', !(bool)$file1);
				exit;
			}
			unset($contents);
			$row['multidoc']	= false; 
			$row['fichier']         = $fileconverted;
			$row['odt']		= null;
			$row['source']          = $source;
			$row['sourceoriginale'] = magic_stripslashes($sourceoriginale);
			// build the import
			$row['importversion']   = "oochargement ".C::get('version', 'cfg').";";
			$row['identity']      = $context['identity'];
			$row['idparent']      = $context['idparent'];
			$row['idtype']        = $context['idtype'];
			
			function_exists('maketask') || include 'taskfunc.php';
			if(!$context['multiple'])
			{
				header("Location: checkimport.php?reload=".$context['reload']."&idtask=". maketask("Import $file1", 3, $row));
				return;
			}
			else
			{
				$context['urls'][$sourceoriginale] = "checkimport.php?reload=".$context['reload']."&idtask=". maketask("Import $file1", 3, $row);
			}
		}
		else
		{
			$sources = array($sourceoriginale => $source);
		}
		
		$user = C::get('id', 'lodeluser').';'.C::get('name', 'lodeluser').';'.C::get('rights', 'lodeluser');
		$site = C::get('site', 'cfg');
		defined('INC_CONNECT') || include 'connect.php';
		global $db;
		$url = $db->GetOne(lq('SELECT url FROM #_MTP_sites WHERE name='.$db->quote($site)));
		
		$client = new OTXClient();
		$error = array();
		$i = 0;
		do
		{
			$options = $client->selectServer($i++);
			if(!$options) break;
			$options['lodel_user'] = $user;
			$options['lodel_site'] = $site;

			$client->instantiate($options);
			if($client->error)
			{
				$error[] = $client->status;
			}
		} while (1);
		
		// get the XML schema of the editorial model
		$datas['title'] = 'ME';
		$datas['description'] = 'ME OTX';
		$datas['author'] = 'Lodel';
		$datas['modelversion'] = 1;
		$schema = Logic::getLogic('data')->generateXML($datas);
		
		foreach($sources as $sourceoriginale => $source)
		{
			$request = array('schema'=>$schema);
			$request['attachment'] = file_get_contents($source);
			//$request['schema'] = file_get_contents('model_lodel.otx.xml'); // todo
			$request['mode'] = 'lodel';
			$request['request'] = <<<RDF
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    <dc:source>{$sourceoriginale}</dc:source>
    <prism:publicationName>{$site}</prism:publicationName>
    <dc:identifier>{$url}</dc:identifier>
</rdf:RDF>
RDF;
			
			$client->request($request);
	
			if(!$client->error)
			{
				if(C::get('sortieoo'))
				{
					header("Content-Type: application/xml; charset=UTF-8");
					echo $client->lodelxml;
					die();
				}
				
				if(empty($context['idtype']))
				{ // reload
					$context['idtype'] = $GLOBALS['db']->GetOne(lq('SELECT idtype FROM #_TP_entities WHERE id='.(int)$context['identity']));
				}
				
				$odtconverted = $source.'-odt.converted';
				if (!writefile($odtconverted, $client->odt)) 
				{
					$context['error'] .= 'unable to write .odt converted file<br/>';
					$context['url'] = 'oochargement.php?'.$_SERVER['QUERY_STRING'];
					View::getView()->render('oochargement', !(bool)$file1);
					exit;
				}
				
				$parser = new TEIParser($context['idtype']);
				$contents = array();
				
				$contents['contents'] = $parser->parse($client->lodelxml, $odtconverted);
				$contents['parserreport'] = $parser->getLogs();
				$contents['otxreport'] = $client->report;

				if(C::get('sortie'))
				{
					$f = create_function('&$var', '$var = htmlentities($var, ENT_COMPAT, "UTF-8");');
					array_walk_recursive($contents, $f);
					echo '<pre>'. print_r($contents, 1) . '</pre>';
					die();
				}

				$row = array();
				if(!$context['multiple']) unset($client);
				$fileconverted = $source. '.converted';
				if (!writefile($fileconverted, base64_encode(serialize($contents)))) 
				{
					$context['error'] .= 'unable to write converted file<br/>';
					$context['url'] = 'oochargement.php?'.$_SERVER['QUERY_STRING'];
					View::getView()->render('oochargement', !(bool)$file1);
					exit;
				}
				unset($contents);
				$row['multidoc']	= false; // TODO : import multidoc
				$row['fichier']         = $fileconverted;
				$row['odt']		= $odtconverted;
				$row['source']          = $source;
				$row['sourceoriginale'] = magic_stripslashes($sourceoriginale);
				// build the import
				$row['importversion']   = "oochargement ".C::get('version', 'cfg').";";
				$row['identity']      = $context['identity'];
				$row['idparent']      = $context['idparent'];
				$row['idtype']        = $context['idtype'];
				
				function_exists('maketask') || include 'taskfunc.php';
				if(!$context['multiple'])
				{
					header("Location: checkimport.php?reload=".$context['reload']."&idtask=". maketask("Import $file1", 3, $row));
					return;
				}
				else
				{
					$context['urls'][$sourceoriginale] = "checkimport.php?reload=".$context['reload']."&idtask=". maketask("Import $file1", 3, $row);
				}
			}
			else
			{
				$context['error'] .= $sourceoriginale.':<br/>'.htmlentities($client->status)."<br/>";
			}
		}
	}

	$context['url'] = 'oochargement.php?'.$_SERVER['QUERY_STRING'];
	View::getView()->render('oochargement', !(bool)$file1);
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>