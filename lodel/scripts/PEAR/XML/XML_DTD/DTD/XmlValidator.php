<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML_DTD_Validator
 *
 * XML DTD_Validator class
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2003-2008 Tomas Von Veschler Cox
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
 * @author    Tomas V.V.Cox <cox@idecnet.com>
 * @author    Igor Feghali <ifeghali@php.net>
 * @copyright 2003-2008 Tomas Von Veschler Cox
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   CVS: $Id: XmlValidator.php,v 1.8 2009/01/18 23:47:55 ifeghali Exp $
 * @link      http://pear.php.net/package/XML_DTD
 */

/**
 * @uses XML_DTD
 */
require_once 'XML/DTD.php';
/**
 * @uses XML_DTD_XmlParser
 */
require_once 'XML/DTD/XmlParser.php';

/**
 * XML_DTD_XmlValidator
 * 
 * Usage:
 * 
 * <code>
 * $validator = XML_DTD_XmlValidator;
 * // This will check if the xml is well formed
 * // and will validate it against its DTD
 * if (!$validator->isValid($dtd_file, $xml_file)) {
 *   die($validator->getMessage());
 * }
 * </code>
 * 
 * @category  XML
 * @package   XML_DTD
 * @author    Tomas V.V.Cox <cox@idecnet.com> 
 * @author    Igor Feghali <ifeghali@php.net>
 * @copyright 2003-2008 Tomas Von Veschler Cox
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: 0.5.2
 * @link      http://pear.php.net/package/XML_DTD
 * @todo Give better error messages :-)
 * @todo Implement error codes and better error reporting
 * @todo Add support for //XXX Missing .. (you may find them around the code)
 */
class XML_DTD_XmlValidator
{

    var $dtd     = array();
    var $_errors = false;

    /**
     * XML_DTD_XmlValidator::isValid()
     *
     * Checks an XML file against its DTD
     *  
     * @param string $dtd_file The DTD file name
     * @param string $xml_file The XML file
     *
     * @return bool True if the XML conforms the definition
     * @access public
     */
    function isValid($dtd_file, $xml_file)
    {
        $nodes =& XML_DTD_XmlParser::factory($xml_file);
        if (PEAR::isError($nodes)) {
            $this->_errors($nodes->getMessage());
            return false;
        }

        $dtd_parser =& new XML_DTD_Parser();
        $dtd_parser->folding = true;
        $this->dtd  = @$dtd_parser->parse($dtd_file);

        $this->_runTree($nodes);
        return ($this->_errors) ? false : true;
    }

    /**
     * XML_DTD_XmlValidator::_runTree()
     * 
     * Runs recursively over the XML_Tree tree of objects
     * validating each of its nodes
     * 
     * @param object &$node an XML_Tree_Node type object
     *
     * @return void
     * @access private
     */
    function _runTree(&$node)
    {
        $this->_validateNode($node);

        foreach ($node->children as $child) {
            $this->_runTree($child);
        }
    }

    /**
     * XML_DTD_XmlValidator::_validateNode()
     * 
     * Validate a XML_Tree_Node: allowed childs, allowed content
     * and allowed attributes
     * 
     * @param object $node     an XML_Tree_Node type object
     *
     * @return void
     * @access private
     */
    function _validateNode($node)
    {
        $name   = $node->name;
        $lineno = $node->coord();

        if (!$this->dtd->elementIsDeclared($name)) {
            $this->_errors("No declaration for tag <$name> in DTD", $lineno);
            // We don't run over the childs of undeclared elements
            // contrary of what xmllint does
            return;
        }

        //
        // Children validation
        //
        $dtd_children = $this->dtd->getChildren($name);
        do {
            // There are children when no children allowed
            if (count($node->children) && !count($dtd_children)) {
                $this->_errors("No children allowed under <$name>", $lineno);
                break;
            }

            // Search for children names not allowed
            $was_error = false;
            $i         = 0;
            foreach ($node->children as $child) {
                $child_name = $child->name;
                if (!in_array($child_name, $dtd_children)) {
                    $this->_errors("<$child_name> not allowed under <$name>", 
                        $child->coord());
                    $was_error = true;
                }
                $i++;
            }
           
            // Validate the order of the children
            if (!$was_error && count($dtd_children)) {
                $children_list = implode(',', $node->getChildrenNames());
                $regex         = $this->dtd->getPcreRegex($name);
                if (!preg_match('/^'.$regex.'$/', $children_list)) {
                    $dtd_regex = $this->dtd->getDTDRegex($name);
                    $this->_errors("In element <$name> the children list "
                        . "found:\n'$children_list', "
                        . "does not conform the DTD definition: "
                        . "'$dtd_regex'", $lineno);
                }
            }
        } while (false);

        //
        // Content Validation
        //
        $node_content = $node->content;
        $dtd_content  = $this->dtd->getContent($name);
        if ($node_content == '#PCDATA') {
            if ($dtd_content == null) {
                $this->_errors("No content allowed for tag <$name>", $lineno);
            } elseif ($dtd_content == 'EMPTY') {
                $this->_errors("No content allowed for tag <$name />, "
                    . "declared as 'EMPTY'", $lineno);
            }
        }
        // XXX Missing validate #PCDATA or ANY

        //
        // Attributes validation
        //
        $atts      = $this->dtd->getAttributes($name);
        $node_atts = $node->attr;
        foreach ($atts as $attname => $attvalue) {
            $opts    = $attvalue['opts'];
            $default = $attvalue['defaults'];
            if ($default == '#REQUIRED' && !isset($node_atts[$attname])) {
                $this->_errors("Missing required '$attname' attribute in <$name>", 
                    $lineno);
            }
            // FIXME: make case insensitive comparison
            if ($default == '#FIXED') {
                if (isset($node_atts[$attname]) 
                    && $node_atts[$attname] != $attvalue['fixed_value']
                ) {
                    $this->_errors("The value '{$node_atts[$attname]}' "
                        . "for attribute '$attname' "
                        . "in <$name> can only be "
                        . "'{$attvalue['fixed_value']}'", $lineno);
                }
            }
            if (isset($node_atts[$attname])) {
                $node_val = $node_atts[$attname];
                // Enumerated type validation
                if (is_array($opts)) {
                    // FIXME: strtoupper() should be applied only when folding=true
                    if (!in_array(strtoupper($node_val), $opts)) {
                        $this->_errors("'$node_val' value "
                            . "for attribute '$attname' under <$name> "
                            . "can only be: '". implode(', ', $opts) . "'", $lineno);
                    }
                }
                unset($node_atts[$attname]);
            }
        }
        // XXX Missing NMTOKEN, ID

        // If there are still attributes those are not declared in DTD
        if (count($node_atts) > 0) {
            $this->_errors("The attributes: '" 
                . implode(', ', array_keys($node_atts)) 
                . "' are not declared in DTD for tag <$name>", $lineno);
        }
    }

    /**
     * XML_DTD_XmlValidator::_errors()
     * 
     * Stores errors
     * 
     * @param string  $str    the error message to append
     * @param integer $lineno the line number where the tag is declared
     *
     * @return void
     * @access private
     */
    function _errors($str, $lineno = null)
    {
        if (is_null($lineno)) {
            $this->_errors .= "$str\n";
        } else {
            $this->_errors .= "line $lineno: $str\n";
        }
    }

    /**
     * XML_DTD_XmlValidator::getMessage()
     *
     * Gets all the errors the validator found in the
     * conformity of the xml document
     *  
     * @return string the error message 
     */
    function getMessage()
    {
        return $this->_errors;
    }

}
?>
