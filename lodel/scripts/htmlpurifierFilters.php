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
// need to be logged in and in backoffice
if(defined('backoffice'))
{
	class HTMLPurifier_Filter_LodelTEI extends HTMLPurifier_Filter
	{
		public $name = 'LodelTEI';
		
		public function preFilter($html, $config, $context) 
		{
			$pre_regex = '#(<\?xml\b[^\?]+\?>\s*)?(<TEI([^>]+>.+?)</TEI>)#si';
			return preg_replace_callback($pre_regex, array($this, 'preFilterCallback'), $html);
		}
		
		protected function preFilterCallback($matches)
		{
			libxml_use_internal_errors(true);

			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->resolveExternals = true;
			$doc->validateOnParse = true;
			$xml = $matches[1].'<!DOCTYPE TEI SYSTEM "'.C::get('sharedir', 'cfg').'/tei_all.dtd">'.$matches[2];
			$doc->loadXML($xml);

			$errors = libxml_get_errors();

			if(!empty($errors)) { trigger_error('ERROR: Invalid TEI regarding to the DTD, escaped', E_USER_WARNING); return ''; }

			return '<span class="lodel-TEI">'.htmlentities($matches[3], ENT_QUOTES, 'UTF-8').'</span>';
		}

		public function postFilter($html, $config, $context) 
		{
			$post_regex = '#<span class="lodel-TEI">(.+?)</span>#si';
			return preg_replace_callback($post_regex, array($this, 'postFilterCallback'), $html);
		}
		
		protected function postFilterCallback($matches) 
		{
			return '<TEI'.html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8').'</TEI>';
		}
	}

	$filters[] = new HTMLPurifier_Filter_LodelTEI();
}
?>