<?php
/**
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 */

/**
 * Classe gérant l'insertion de la TEI dans Lodel en collaboration avec HTMLPurifier
 * Vérifie que le document est valide XML
 *
 * @package lodel
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
 * @since Fichier ajouté depuis la version 0.9
 */
if(defined('backoffice'))
{
	class HTMLPurifier_Filter_LodelTEI extends HTMLPurifier_Filter
	{
		public $name = 'LodelTEI';
		
		public function preFilter($html, $config, $context) 
		{
			if(empty($html) || is_numeric($html)) return $html;
			$pre_regex = '#<\?xml\b[^\?]+\?>\s*(<!DOCTYPE[^>]+>\s*)?(<TEI([^>]*>.+?)</TEI>)$#si';
			$ret = preg_replace_callback($pre_regex, array($this, 'preFilterCallback'), $html);
			if(empty($ret) && preg_last_error() === PREG_BACKTRACK_LIMIT_ERROR)
                        {
                                $prev = ini_get('pcre.backtrack_limit');
                                ini_set('pcre.backtrack_limit', 1000000000);
                                $ret = preg_replace_callback($pre_regex, array($this, 'preFilterCallback'), $html);
                                ini_set('pcre.backtrack_limit', $prev);
                                if(empty($ret) && preg_last_error() !== PREG_NO_ERROR)
                                {
                                        trigger_error('ERROR: can not recover TEI, PCRE error: '.preg_last_error(), E_USER_WARNING);
                                }
                        }
			return $ret;
		}
		
		protected function preFilterCallback($matches)
		{
			libxml_clear_errors();
			libxml_use_internal_errors(true);

			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->resolveExternals = true;
			$doc->validateOnParse = true;
			$doc->loadXML($matches[0]);
            
			if($errors = libxml_get_errors()){
				$doc = new DOMDocument('1.0', 'UTF-8');
				$doc->resolveExternals = true;
				$doc->validateOnParse = false;

				$doc->loadXML($matches[0]);

				if( isset($doc->documentElement) ){
    				$xsd = $doc->documentElement->getAttributeNS($doc->lookupNamespaceURI('xsi'), 'schemaLocation');
    
        			if(! @$doc->schemaValidate($xsd) )
        				$errors = libxml_get_errors();
				}

			}
			
			libxml_use_internal_errors(false);
			libxml_clear_errors();
			if(!empty($errors)) {
				foreach($errors as $error){
					if($error->level === LIBXML_ERR_FATAL){
						trigger_error('ERROR: Invalid TEI regarding to the DTD, escaped : '.print_r($errors,1).'<br/>'.htmlentities($matches[0], ENT_COMPAT, 'UTF-8'), E_USER_WARNING);
					}
				} 
			}
			return '<span class="lodel-TEI">' . htmlspecialchars($matches[3], ENT_QUOTES, 'UTF-8') . '</span>';
		}

		public function postFilter($html, $config, $context) 
		{
			if(empty($html) || is_numeric($html)) return $html;
			$post_regex = '#<span class="lodel-TEI">(.+?)</span>#si';
			$ret = preg_replace_callback($post_regex, array($this, 'postFilterCallback'), $html);
			if(empty($ret) && preg_last_error() === PREG_BACKTRACK_LIMIT_ERROR)
			{
				$prev = ini_get('pcre.backtrack_limit');
				ini_set('pcre.backtrack_limit', 1000000000);
				$ret = preg_replace_callback($post_regex, array($this, 'postFilterCallback'), $html);
				ini_set('pcre.backtrack_limit', $prev);
				if(empty($ret) && preg_last_error() !== PREG_NO_ERROR)
				{
					trigger_error('ERROR: can not recover TEI, PCRE error: '.preg_last_error(), E_USER_WARNING);
				}
			}

			return $ret;
		}
		
		protected function postFilterCallback($matches) 
		{
			return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n
				<!DOCTYPE TEI SYSTEM \"http://www.tei-c.org/release/xml/tei/custom/schema/dtd/tei_all.dtd\">\n
				<TEI".html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8').'</TEI>';
		}
	}
	$filters[] = new HTMLPurifier_Filter_LodelTEI();
}

class HTMLPurifier_Filter_MathML extends HTMLPurifier_Filter {
	public $name = 'MathML';
	
	public function preFilter($html, $config, $context) {
		if(empty($html) || is_numeric($html)) return $html;
		$pre_regex = '#(<math[^>]*MathML[^>]*>.+?</math>)#si';
		$ret = preg_replace_callback($pre_regex, array($this, 'preFilterCallback'), $html);
		if(empty($ret) && preg_last_error() === PREG_BACKTRACK_LIMIT_ERROR) {
			trigger_error('ERROR: can not recover MathML, PCRE error: '.preg_last_error(), E_USER_WARNING);
		}
		return $ret;
	}
	protected function preFilterCallback($matches) {
		return '<span class="mathml">' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
	}
	public function postFilter($html, $config, $context) {
		if(empty($html) || is_numeric($html)) return $html;
		$post_regex = '#<span class="mathml">(.+?)</span>#si';
		$ret = preg_replace_callback($post_regex, array($this, 'postFilterCallback'), $html);
		if(empty($ret) && preg_last_error() === PREG_BACKTRACK_LIMIT_ERROR) {
				trigger_error('ERROR: can not recover TEI, PCRE error: '.preg_last_error(), E_USER_WARNING);
		}
		return $ret;
	}
	protected function postFilterCallback($matches) {
		return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
	}
}

$filters[] = new HTMLPurifier_Filter_MathML();
