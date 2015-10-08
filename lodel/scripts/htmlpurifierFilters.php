<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
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
