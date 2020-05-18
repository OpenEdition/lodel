<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Vérification d'un import
 */

define('backoffice', true);
define('backoffice-edition', true);
require 'siteconfig.php';

try
{
	include 'auth.php';
    C::set('env', 'edition');
	authenticate(LEVEL_REDACTOR);

	$idtask            = (int)C::get('idtask');
	$taskLogic         = Logic::getLogic('tasks');
	$task              = $taskLogic->getTask($idtask);
	$context['reload'] = (bool)C::get('reload');
	$statistics        = array();

	if (!$task)
		View::getView()->back();
	$taskLogic->populateContext($task, $context);
    $tmp_importdir = C::get('tmp_importdir', 'cfg');
    if (!empty($tmp_importdir)) {
        if (isset($task['fichier']['use_importdir']) && $task['fichier']['use_importdir']) {
            $task['fichier']['contents'] = unserialize(file_get_contents($tmp_importdir.$task['fichier']['contents']));
        }
    }
     $context = array_merge($context, $task['fichier']);
 
 if(!empty($task['identity']))
		$context['identity'] = $task['identity'];
	if(!empty($task['idparent']))
		$context['idparent'] = $task['idparent'];

	foreach (array('entries', 'persons', 'entities', 'errors') as $content) {
		if(!empty($context['contents'][$content]))
			$context[$content] = $context['contents'][$content];
		unset($context['contents'][$content]);
	}

	if(!empty($context['otxreport']['meta-soffice']))
		$statistics['docstats'] = $context['otxreport']['meta-soffice'];

	if(!empty($context['otxreport']['warning']))
		$context['otxwarnings'] = $context['otxreport']['warning'];

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

								if(isset($attrs['class']) && $attrs['class'] !== 'footnotecall' && $attrs['class'] !== 'endnotecall' && $attrs['class'] !== 'FootnoteSymbol' && $attrs['class'] !== 'inline')
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

						if(isset($attrs['class']) && $attrs['class'] !== 'footnotecall' && $attrs['class'] !== 'endnotecall' && $attrs['class'] !== 'FootnoteSymbol' && $attrs['class'] !== 'EndnoteSymbol' && $attrs['class'] !== 'inline')
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
