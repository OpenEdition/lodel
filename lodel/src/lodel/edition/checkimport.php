<?php
/**
 * Vérification d'un import
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source/lodel/edition
 */

define('backoffice', true);
define('backoffice-edition', true);
require 'siteconfig.php';

try
{
	include 'auth.php';
    C::set('env', 'edition');
	authenticate(LEVEL_REDACTOR);

	include 'taskfunc.php';
	include 'xmlimport.php';
	include 'class.checkImportHandler.php';
	$idtask            = (int)C::get('idtask');
	$task              = gettask($idtask);
	$context['reload'] = (bool)C::get('reload');
	$statistics        = array();
	gettypeandclassfromtask($task, $context);

	$context = array_merge($context, unserialize(base64_decode(file_get_contents($task['fichier']))));
	$context['idtype'] = $task['idtype'];
	if(!empty($context['contents']['entries']))
		$context['entries'] = $context['contents']['entries'];
	if(!empty($context['contents']['persons']))
		$context['persons'] = $context['contents']['persons'];
	if(!empty($context['contents']['entities']))
		$context['entities'] = $context['contents']['entities'];
	if(!empty($context['contents']['error']))
		$context['error'] = $context['contents']['error'];

	unset($context['contents']['persons'], $context['contents']['entries'], $context['contents']['entities'], $context['contents']['errors']);

	if(!empty($context['otxreport']['meta-soffice']))
	{
		$statistics['docstats'] = $context['otxreport']['meta-soffice'];
	}

	if(!empty($context['otxreport']['warning']))
	{
		$context['otxwarnings'] = $context['otxreport']['warning'];
	}

	unset($context['otxreport']);

	$table = $contents = $context['titles'] = array();

	$parser = new TEIParser($context['idtype']);
	$i = 0;

	$reader = new XMLReader();
	foreach($context['contents'] as $k => $block)
	{
		$contents[$k] = array();
		$mltext = is_array($block);
		if($mltext)
		{
			foreach($block as $lang => $b)
			{
				$reader->XML('<lodelblock>'.$b.'</lodelblock>', 'UTF-8', LIBXML_COMPACT | LIBXML_NOCDATA);
				$reader->read() && $reader->read(); // jump to first element
				do
				{
					$table[$k][$lang][] = array('text' => $reader->readOuterXML());
				}
				while($reader->next() && $reader->localName !== 'lodelblock');
				$reader->close();

				$contents[$k][$lang] = array();

				foreach($table[$k] as $lang => $content)
				{
					foreach($content as $key => $container)
					{
						$reader->XML('<lodelblock>'.$container['text'].'</lodelblock>', 'UTF-8', LIBXML_COMPACT | LIBXML_NOCDATA);
						$contents[$k][$lang][$key]['text'] = '';
						while($reader->read())
						{
							if( ( !$reader->isEmptyElement || in_array($reader->localName, array("img","br")) ) && ( XMLReader::ELEMENT === $reader->nodeType) )
							{
								if('lodelblock' === $reader->localName) continue;

								$attrs = array();
								if($reader->hasAttributes)
								{
									$reader->moveToFirstAttribute();
									do
									{
										$attrs[$reader->localName] = $reader->value;
									} while($reader->moveToNextAttribute());

									$reader->moveToElement();
								}

								if(isset($attrs['class']) && $attrs['class'] !== 'footnotecall' && $attrs['class'] !== 'endnotecall' && $attrs['class'] !== 'FootnoteSymbol')
								{
									if(!$parser->getStyle($attrs['class']))
									{
										$attrs['id'] = 'unknown_'.$attrs['class'];
										$contents[$k][$lang][$key]['error'] = true;
									}

									if('span' !== $reader->localName)
										$contents[$k][$lang][$key]['class'] = $attrs['class'];
									else
									{
										$contents[$k][$lang][$key]['style'][] = '<span class="mestyles">'.$attrs['class'].'</span>';
										$attrs['title'] = 'LOCALCLASS:'.$attrs['class'].';';
										$attrs['class'] .= ' mestyles';
									}
								}

								if(isset($attrs['style']))
								{
									$contents[$k][$lang][$key]['style'][] = '<span class="localstyles">'.$attrs['style'].'</span>';
									if(isset($attrs['class'])) $attrs['class'] .= ' localstyles';
									else $attrs['class'] = 'localstyles';
									if(isset($attrs['title'])) $attrs['title'] .= 'LOCALSTYLES:'.htmlspecialchars($attrs['style']).';';
									else $attrs['title'] = 'LOCALSTYLES:'.htmlspecialchars($attrs['style']).';';
								}

								if(isset($attrs['lang']))
								{
									$contents[$k][$lang][$key]['style'][] = 'lang:'.$attrs['lang'];
									if(isset($attrs['title'])) $attrs['title'] .= 'LANG:'.$attrs['lang'].';';
									else $attrs['title'] = 'LANG:'.$attrs['lang'].';';
									$statistics['lang'][] = $attrs['lang'];
								}

								if('img' === $reader->localName)
								{
									if(0 === strpos($attrs['src'], '../../docannexe/image/'))
									{
										list($w, $h, $t) = @getimagesize($attrs['src']);
										$statistics['images'][$attrs['src']] = $contents[$k][$lang][$key]['style'][] = array('imagewidth' => $w, 'imageheight' => $h, 'imagemime' => image_type_to_mime_type($t), 'imagesize' => filesize($attrs['src']));
									}
									else
									{
										list($w, $h, $t) = @getimagesize($attrs['src']);
										$statistics['images'][$attrs['src']] = $contents[$k][$lang][$key]['style'][] = array('imagewidth' => $w, 'imageheight' => $h, 'imagemime' => image_type_to_mime_type($t), 'imagesize' => 0);
									}
								}
								elseif(in_array($reader->localName, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6')))
								{
									$context['titles'][] = $reader->readOuterXML();
								}

								$contents[$k][$lang][$key]['text'] .= '<'.$reader->localName;
								if(!empty($attrs))
								{
									foreach($attrs as $name => $value)
										$contents[$k][$lang][$key]['text'] .= ' '.$name.'="'.htmlspecialchars($value).'"';
								}
								$contents[$k][$lang][$key]['text'] .= '>';
							}
							elseif(XMLReader::END_ELEMENT === $reader->nodeType)
							{
								if('lodelblock' === $reader->localName) continue;

								$contents[$k][$lang][$key]['text'] .= '</'.$reader->localName.'>';
							}
							elseif(XMLReader::TEXT === $reader->nodeType || XMLReader::WHITESPACE === $reader->nodeType || XMLReader::SIGNIFICANT_WHITESPACE === $reader->nodeType)
							{
								$contents[$k][$lang][$key]['text'] .= htmlentities($reader->value, ENT_COMPAT, 'UTF-8');
							}
						}
						$reader->close();
					}
				}
			}
		}
		else
		{
			$reader->XML('<lodelblock>'.$block.'</lodelblock>', 'UTF-8', LIBXML_COMPACT | LIBXML_NOCDATA);
			$reader->read() && $reader->read(); // jump to first element
			$i=0;

			do
			{
			    if( $reader->nodeType !== XMLReader::SIGNIFICANT_WHITESPACE )
    				$table[$k][] = array('text' => $reader->readOuterXML());
			}
			while($reader->next() && $reader->localName !== 'lodelblock');
			$reader->close();

			$contents[$k] = array();

			foreach($table[$k] as $key => $container)
			{
				$reader->XML('<lodelblock>'.$container['text'].'</lodelblock>', 'UTF-8', LIBXML_COMPACT | LIBXML_NOCDATA);
				$contents[$k][$key]['text'] = '';
				$contents[$k][$key]['localstyles'] = array();

				while($reader->read())
				{
					if( (!$reader->isEmptyElement || in_array($reader->localName, array("img","br")) ) && ( XMLReader::ELEMENT === $reader->nodeType) )
					{
						if('lodelblock' === $reader->localName) continue;

						$attrs = array();
						if($reader->hasAttributes)
						{
							$reader->moveToFirstAttribute();
							do
							{
								$attrs[$reader->localName] = $reader->value;
							} while($reader->moveToNextAttribute());

							$reader->moveToElement();
						}

						if(isset($attrs['class']) && $attrs['class'] !== 'footnotecall' && $attrs['class'] !== 'endnotecall' && $attrs['class'] !== 'FootnoteSymbol' && $attrs['class'] !== 'EndnoteSymbol')
						{
							if(!$parser->getStyle($attrs['class']))
							{
								$attrs['id'] = 'unknown_'.$attrs['class'];
								$contents[$k][$key]['error'] = true;
							}

							if('span' !== $reader->localName)
								$contents[$k][$key]['class'] = $attrs['class'];
							else
							{
								$contents[$k][$key]['style'][] = '<span class="mestyles">'.$attrs['class'].'</span>';
								$attrs['title'] = 'LOCALCLASS:'.$attrs['class'].';';
								$attrs['class'] .= ' mestyles';
							}
						}

						if(isset($attrs['style']))
						{
							if(!in_array($attrs['style'], $contents[$k][$key]['localstyles'])){
								$contents[$k][$key]['style'][] 	     = '<span class="localstyles">'.$attrs['style'].'</span>';
								$contents[$k][$key]['localstyles'][] = $attrs['style'];
							}
							if(isset($attrs['class'])) $attrs['class'] .= ' localstyles';
							else $attrs['class'] = 'localstyles';
							if(isset($attrs['title'])) $attrs['title'] .= 'LOCALSTYLES:'.htmlspecialchars($attrs['style']).';';
							else $attrs['title'] = 'LOCALSTYLES:'.htmlspecialchars($attrs['style']);
						}

						if(isset($attrs['lang']))
						{
							$contents[$k][$key]['style'][] = 'lang:'.$attrs['lang'];
							if(isset($attrs['title'])) $attrs['title'] .= 'LANG:'.$attrs['lang'].';';
							else $attrs['title'] = 'LANG:'.$attrs['lang'].';';
							$statistics['lang'][] = $attrs['lang'];
						}

						if('img' === $reader->localName)
						{
							if(0 === strpos($attrs['src'], '../../docannexe/image/'))
							{
								list($w, $h, $t) = @getimagesize($attrs['src']);
								$statistics['images'][$attrs['src']] = $contents[$k][$key]['style'][] = array('imagename' => @basename($attrs['src']), 'imagewidth' => $w, 'imageheight' => $h, 'imagemime' => image_type_to_mime_type($t), 'imagesize' => @filesize($attrs['src']));
							}
							else
							{
								list($w, $h, $t) = @getimagesize($attrs['src']);
								$statistics['images'][$attrs['src']] = $contents[$k][$key]['style'][] = array('imagename' => @basename($attrs['src']), 'imagewidth' => $w, 'imageheight' => $h, 'imagemime' => image_type_to_mime_type($t), 'imagesize' => 0);
							}
						}
						elseif(in_array($reader->localName, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6')))
						{
							$context['titles'][] = $reader->readOuterXML();
						}

						$contents[$k][$key]['text'] .= '<'.$reader->localName;
						if(!empty($attrs))
						{
							foreach($attrs as $name => $value)
								$contents[$k][$key]['text'] .= ' '.$name.'="'.htmlspecialchars($value).'"';
						}
						$contents[$k][$key]['text'] .= '>';
					}
					elseif(XMLReader::END_ELEMENT === $reader->nodeType)
					{
						if('lodelblock' === $reader->localName) continue;

						$contents[$k][$key]['text'] .= '</'.$reader->localName.'>';
					}
					elseif(XMLReader::TEXT === $reader->nodeType || XMLReader::WHITESPACE === $reader->nodeType || XMLReader::SIGNIFICANT_WHITESPACE === $reader->nodeType)
					{
						$contents[$k][$key]['text'] .=  htmlentities($reader->value, ENT_COMPAT, 'UTF-8');
					}
				}
				$reader->close();
			}
		}
	}

	unset($table);

	$context['contents'] = $contents;
	$context['statistics'] = $statistics;
	unset($contents, $statistics);

	View::getView()->render('checkimport', false, false);
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}