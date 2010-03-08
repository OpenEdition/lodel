<?php
/**
 * V�rification d'un import
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
	include 'auth.php';
	authenticate(LEVEL_REDACTOR);
	
	include 'taskfunc.php';
	include 'xmlimport.php';
	include 'class.checkImportHandler.php';
	$idtask = (int)C::get('idtask');
	$task              = gettask($idtask);
	$context['reload'] = (bool)C::get('reload');
	gettypeandclassfromtask($task, $context);

	$context = array_merge($context, unserialize(base64_decode(file_get_contents($task['fichier']))));
	$context['idtype'] = $task['idtype'];
	if(!empty($context['contents']['entries']))
		$context['entries'] = $context['contents']['entries'];
	if(!empty($context['contents']['persons']))
		$context['persons'] = $context['contents']['persons'];
	if(!empty($context['contents']['entities']))
		$context['relations'] = $context['contents']['entities'];
	
	unset($context['contents']['persons'], $context['contents']['entries'], $context['contents']['entities']);
	
	$node = null;
	if(!empty($context['otxreport']))
	{
		$reader = new XMLReader(); // parse OTX logs
    	$reader->XML($context['otxreport'], 'UTF-8', LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOCDATA);
    	$tree = array();
    	$isMetas = 0;
    	$nbItem = 0;
    	while($reader->read())
    	{
    		if('RDF' === $reader->localName || 'item' === $reader->localName || 'meta' === $reader->localName ||
    		('document-meta' !== $reader->localName && !$isMetas)) continue;
    		
    		++$isMetas;
    		
    		if(XMLReader::ELEMENT === $reader->nodeType)
    		{
    			if('document-meta' === $reader->localName)
    			{
    				$context['otx_report'][++$nbItem] = array();
    				continue;
    			}
    			$tree[] = $reader->localName;
    			$node =& $context['otx_report'][$nbItem];
    			foreach($tree as $t)
    			{
    				isset($node[$t]) || $node[$t] = array();
    				$node =& $node[$t];
    			}
    			
    			if($reader->isEmptyElement)
    			{
    				array_pop($tree);
    				$node = array();
    				if($reader->hasAttributes)
    				{
    					$reader->moveToFirstAttribute();
    					do
    					{
    						$node[$reader->localName] = $reader->value;
    					} while($reader->moveToNextAttribute());
    				}
    			}
    			else $node = '';
    		}
    		elseif(XMLReader::END_ELEMENT === $reader->nodeType)
    		{
    			if('RDF' === $reader->localName || 'item' === $reader->localName || 'meta' === $reader->localName ||
    			('document-meta' !== $reader->localName && !$isMetas)) continue;
    			array_pop($tree);
    			if('document-meta' === $reader->localName) --$isMetas;
    		}
    		elseif(XMLReader::TEXT === $reader->nodeType)
    		{
    			$node .= $reader->value;
    		}
    	}
    	$reader->close();
	}	
	$context['multidoc'] = $task['multidoc'] ? true : false;
	View::getView()->render('checkimport');
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
