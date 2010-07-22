<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML_DTD_XmlParser
 *
 * Extracts the skeleton of a XML file by chaining its elements.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008-2009 Igor Feghali
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The name of the author may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  XML
 * @package   XML_DTD
 * @author    Igor Feghali <ifeghali@php.net>
 * @copyright 2008-2009 Igor Feghali
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   CVS: $Id: XmlParser.php,v 1.3 2009/04/28 02:01:29 ifeghali Exp $
 * @link      http://pear.php.net/package/XML_DTD
 */

/**
 * @uses XML_Parser
 */
require_once 'XML/Parser.php';

/**
 * XML_DTD_XmlParser
 * 
 * Usage:
 * 
 * <code>
 * $nodes =& XML_DTD_XmlParser::factory('file.xml');
 * if (PEAR::isError($nodes)) {
 *     die($nodes->getMessage());
 * }
 * </code>
 * 
 * @category  XML
 * @package   XML_DTD
 * @author    Igor Feghali <ifeghali@php.net>
 * @copyright 2008 Igor Feghali
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @link      http://pear.php.net/package/XML_DTD
 */
class XML_DTD_XmlParser extends XML_Parser
{
    var $children = array();
    var $ptr      = null;
    var $folding  = true;

    /**
     * XML_DTD_XmlParser::XmlParser()
     *
     * Constructor, not to be used. Use factory() instead.
     *
     * @return void
     * @access public
     */
    function XML_DTD_XmlParser()
    {
        parent::XML_Parser();
        $this->ptr =& $this;
    }

    /**
     * XML_DTD_XmlParser::factory()
     *
     * Parses a XML file and returns its "skeleton".
     *  
     * @param string $xml Path to XML file
     *
     * @return object Instance of XML_DTD_XmlElement containing root
     * @access public
     */
    function factory($xml)
    {
        $p =& new XML_DTD_XmlParser();

        $result = $p->setInputFile($xml);
        if (PEAR::isError($result)) {
            return $result;
        }

        $result = $p->parse();
        if (PEAR::isError($result)) {
            return $result;
        }

        /**
         * Preventing the circular reference memory leak.
         * See: http://bugs.php.net/bug.php?id=33595
         */
        unset($p->_handlerObj, $p->ptr);

        if (count($p->children)) {
            return $p->children[0];
        } else {
            return PEAR::raiseError('empty XML?');
        }
    }

    /**
     * handle start element
     *
     * @param resource $xp   xml parser resource
     * @param string   $name name of the element
     * @param array    $attr attributes
     *
     * @return void
     * @access private
     */
    function startHandler($xp, $name, $attr)
    {
        $e         =& new XML_DTD_XmlElement();
        $e->name   = $name;
        $e->attr   = $attr;
        $e->lineno = @xml_get_current_line_number($xp);
        $e->colno  = @xml_get_current_column_number($xp);
        $e->parent =& $this->ptr;

        $this->ptr->children[] =& $e;
        $this->ptr             =& $e;
    }

    /**
     * handle end element
     *
     * @param resource $xp   xml parser resource
     * @param string   $name name of the element
     *
     * @return void
     * @access private
     */
    function endHandler($xp, $name)
    {
        $tmp       =& $this->ptr;
        $this->ptr =& $this->ptr->parent;

        /**
         * We do not need the reference to the parent object anymore. Clean it 
         * up to prevent the circular reference memory leak.
         * See: http://bugs.php.net/bug.php?id=33595
         */
        unset($tmp->parent);
    }

    /**
     * handle character data
     *
     * @param resource $xp    xml parser resource
     * @param string   $cdata character data
     *
     * @return void
     * @access private
     */
    function cdataHandler($xp, $cdata)
    {
        if (strlen(trim($cdata))) {
            $this->ptr->content = '#PCDATA';
        }
    }

}

/**
 * XML_DTD_XmlElement
 *
 * Simplistic class to hold XML elements and its associated data
 * 
 * @category  XML
 * @package   XML_DTD
 * @author    Igor Feghali <ifeghali@php.net>
 * @copyright 2008 Igor Feghali
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @link      http://pear.php.net/package/XML_DTD
 */
class XML_DTD_XmlElement
{
    var $name     = '';
    var $content  = 'EMPTY';
    var $attr     = array();
    var $children = array();
    var $parent   = null;
    var $lineno   = 0;
    var $colno    = 0;

    /**
     * XML_DTD_XmlElement::coord()
     *
     * Returns the line/column numbers of the start of a XML element.
     *  
     * @return string Position of element in xml file (line:column).
     * @access public
     */
    function coord()
    {
        return sprintf('%d:%d', $this->lineno, $this->colno);
    }

    /**
     * XML_DTD_XmlElement::getChildrenNames()
     *
     * Returns the names of the children of current XML element.
     *  
     * @return array Array containing child names
     * @access public
     */
    function getChildrenNames()
    {
        $return = array();
        foreach ($this->children as $child) {
            $return[] = $child->name;
        }
        return $return;
    }
}

?>
