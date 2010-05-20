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
            trigger_error("Le fichier n'est pas un fichier chargé", E_USER_ERROR);
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
    $err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
    if(!class_exists('ServOO', false))
        include 'servoofunc.php';
    $client = new ServOO;
    
    if ($client->error_message) {
        $context['noservoo'] = true;
    } elseif ($file1) {
            do {
                // verifie que la variable file1 n'a pas ete hackee
                $t = time();
                @chmod($source, 0666 & octdec(C::get('filemask', 'cfg'))); 
    
                // get the extension...it's indicative only !
                $ext = strrchr($sourceoriginale, '.');
                if($ext)
                    $ext = substr($ext, 1);
                else
                    $ext = '';
    
                $options = array('block' => true,	'inline' => true);
                $outformat = isset($context['sortiexhtml']) ? 'W2L-XHTML' : 'W2L-XHTMLLodel';
                $xhtml = $client->convertToXHTML($source, $ext, $outformat, $tmpdir, '',
                                                        $options, array('allowextensions' => 'xhtml|jpg|png|gif'),
                                                        'imagesnaming', // callback
                                                        SITEROOT. 'docannexe/tmp'. rand()); // base name for the images

                if ($xhtml === FALSE) {
                    if (strpos($client->error_message, 'Not well-formed XML') !== false) {
                        $arr = explode("/\n/", $client->error_message);
                        $l = -3;
                        foreach ($arr as $t) {
                            echo $l++," ",$t,"\n";
                        }
                        return;
                    } else {
                        $erreur = "<br />1er ServOO : ".$client->error_message;
                        $i=2;
                        while(TRUE === $client->status && FALSE === $xhtml) {
                            $client = new ServOO($i);
                            if(empty($client->error_message)) {
                                $xhtml = $client->convertToXHTML($source, $ext, $outformat, $tmpdir, '',
                                                        $options, array('allowextensions' => 'xhtml|jpg|png|gif'),
                                                        'imagesnaming', // callback
                                                        SITEROOT. 'docannexe/tmp'. rand()); // base name for the images
                                if(FALSE === $xhtml)
                                    $erreur .= "<br /> ".$i."ème ServOO : ".$client->error_message;
                            }
                            $i++;
                        }
                        if(FALSE === $xhtml) {
                            $context['error'] = "Erreur renvoyée par le ServOO: ". $erreur;
                            break;
                        }
                    }
                }
            if (isset($context['sortieoo']) || isset($context['sortiexhtml'])) {
                die(htmlentities($xhtml));
            }
    
            $err = lodelprocessing($xhtml);
    
            if ($err) {
                $context['error'] = 'error in the lodelprocessing function';
                break;
            }
            
            if (isset($context['sortiexmloo']) || isset($context['sortie'])) {
                die($xhtml);
            }
    
            require_once 'balises.php';
            $fileconverted = $source. '.converted';
            if (!writefile($fileconverted, $xhtml)) {
                $context['error'] = 'unable to write converted file';
                break;
            }
    
            $row                    = array();
            $row['fichier']         = $fileconverted;
            $row['source']          = $source;
            $row['sourceoriginale'] = magic_stripslashes($sourceoriginale);
            // build the import
            $row['importversion']   ="oochargement ".C::get('version', 'cfg').";";
            if ($context['identity']) {
                $row['identity']      = $context['identity'];
            } else {
                $row['idparent']      = $context['idparent'];
                $row['idtype']        = $context['idtype'];
            }
            
            function_exists('maketask') || include 'taskfunc.php';
            $idtask = maketask("Import $file1", 3, $row);
    	    error_reporting($err);
            header("Location: checkimport.php?reload=".$context['reload']."&idtask=". $idtask);
            return;
        } while (0); // exceptions
    }
    error_reporting($err);
    $context['url'] = 'oochargement.php';
    View::getView()->render('oochargement', !(bool)$file1);

}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>