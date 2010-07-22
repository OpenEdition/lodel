<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML_DTD
 *
 * XML DTD package
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
 * @version   CVS: $Id: DTD.php,v 1.9 2009/01/18 23:47:55 ifeghali Exp $
 * @link      http://pear.php.net/package/XML_DTD
 */

/**
 * XML_DTD_Parser
 * 
 * Usage:
 * 
 * <code>
 * // Create a new XML_DTD parser object
 * $dtd_parser = new XML_DTD_Parser;
 * // Do the parse and return a XML_DTD_Tree object
 * // containing the DTD tree representatio.
 * $dtd_tree = $dtd_parser->parse($dtd_file);
 * </code>
 * 
 * @category  XML
 * @package   XML_DTD
 * @author    Tomas V.V.Cox <cox@idecnet.com> 
 * @author    Igor Feghali <ifeghali@php.net>
 * @copyright 2003-2008 Tomas V.V.Cox <cox@idecnet.com> 
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: 0.5.2
 * @link      http://pear.php.net/package/XML_DTD
 * @todo      Entities: PUBLIC | SYSTEM | NDATA
 * @todo      Tokenized types for ATTLIST
 */
class XML_DTD_Parser
{
    /**
     * @var array
     */
    var $dtd = array();

    /**
     * @var boolean
     */
    var $folding = false;

    /**
     * XML_DTD_Parser::_parseENTITIES()
     * 
     * Do entities preprocessing
     * 
     * @param string $str string to parse
     *
     * @return string 
     * @access private 
     */
    function _parseENTITIES($str)
    {
        // Find all ENTITY tags
        if (preg_match_all('|<!ENTITY\s+([^>]+)\s*>|s', $str, $m)) {
            $ids   = array();
            $repls = array();
            foreach ($m[1] as $entity) {
                // Internal entities
                if (
                    preg_match('/^%?\s+([a-zA-Z0-9.\-_]+)\s+(["\'])(.*)\2\s*$/s',
                        $entity, $n)
                ) {
                    // entity name
                    $id = '/%' . $n[1] . ';/';
                    // replacement text
                    $repl    = $n[3];
                    $ids[]   = $id;
                    $repls[] = $repl;

                } else {
                    // XXX PUBLIC | SYSTEM | NDATA
                    trigger_error("Entity <!ENTITY $entity> not supported");
                }
            }
            // replace replacements in entities
            $defined_ids = $defined_repls = array();
            for ($i = 0; $i < count($ids); $i++) {
                if ($i <> 0) {
                    $repls[$i] = preg_replace($defined_ids, 
                        $defined_repls, $repls[$i]);
                    // XXX Search for not previously defined entities
                }
                $defined_ids[]   = $ids[$i];
                $defined_repls[] = $repls[$i];
            }
            // replace replacements in the whole DTD
            array_flip($ids);
            array_flip($repls);
            $str = preg_replace($ids, $repls, $str);
            // Check if there are still unparsed entities
            if (preg_match_all('/(%[^#][a-zA-Z0-9.]+;)/', $str, $o)) {
                foreach ($o[1] as $notparsed) {
                    trigger_error("Entity ID: "
                        . "'$notparsed' not recognized, skipping");
                    $str = preg_replace("/$notparsed/", '', $str);
                }
            }
        }
        return $str;
    }

    /**
     * XML_DTD_Parser::parse()
     * 
     * @param string  $cont    it could be either a filename or a string
     * @param boolean $is_file if the first param is supposed to be a string
     *                         or a filename
     *
     * @return XML_DTD_Tree    a XML_DTD_Tree object
     * @access public
     */
    function parse($cont, $is_file = true)
    {
        if ($is_file) {
            $cont = file_get_contents($cont);
        }

        if ($this->folding) {
            $cont = strtoupper($cont);
        }

        // Remove DTD comments
        $cont = preg_replace('|<!--.*-->|Us', '', $cont);
        $cont = $this->_parseENTITIES($cont);
        if (preg_match_all('|<!([^>]+)>|s', $cont, $m)) {
            foreach ($m[1] as $tag) {
                $fields = array();
                $in     = 0;
                $buff   = '';
                $tag    = preg_replace('|\s+|s', ' ', $tag);
                // Manual split the parts of the elements
                // take care of netsted lists (a|(c|d)|b)
                for ($i = 0; $i < strlen($tag); $i++) {
                    if ($tag{$i} == ' ' && !$in && $buff) {
                        $fields[] = $buff;
                        $buff     = '';
                        continue;
                    }
                    if ($tag{$i} == '(') {
                        $in++;
                    } elseif ($tag{$i} == ')') {
                        $in--;
                    }
                    $buff .= $tag{$i};
                }
                if ($buff) {
                    $fields[] = $buff;
                }
                // Call the element handler
                $elem = $fields[0];
                array_shift($fields);
                switch ($elem) {
                case 'ELEMENT':
                    $this->_ELEMENT($fields);
                    break;
                case 'ATTLIST':
                    $this->_ATTLIST($fields);
                    break;
                case 'ENTITY':
                    break;
                default:
                    trigger_error("$elem not implemented yet", E_USER_WARNING);
                    break;
                }
            }
        }
        return new XML_DTD_Tree($this->dtd);
    }

    /**
     * XML_DTD_Parser::_ELEMENT()
     * 
     * Handles the ELEMENT parsing
     * 
     * @param array $data $data[0] the element, 
     *                    $data[1] the string with allowed childs
     *
     * @return void
     * @access private
     * @todo   PHPCS - rename to _element for CamelCase rule
     */
    function _ELEMENT($data)
    {
        // $data[0] the element
        // $data[1] the string with allowed childs
        $elem_name = $data[0];
        $ch        = str_replace(' ', '', $data[1]);
        // Content
        if ($ch{0} != '(') {
            $content  = $ch;
            $children = array();
        } else {
            // Enumerated list of childs
            $content = null;
            do {
                $children = preg_split('/([^#a-zA-Z0-9_.-]+)/', 
                    $ch, -1, PREG_SPLIT_NO_EMPTY);
                if (in_array('#PCDATA', $children)) {
                    $content = '#PCDATA';
                    if (count($children) == 1) {
                        $children = array();
                        break;
                    }
                }
                $this->dtd['elements'][$elem_name]['child_validation_dtd_regex'] =
                    $ch;
                // Convert the DTD regex language into PCRE regex format
                $reg = str_replace(',', ',?', $ch);
                $reg = preg_replace('/([#a-zA-Z0-9_.-]+)/', '(,?\\0)', $reg);

                $this->dtd['elements'][$elem_name]['child_validation_pcre_regex'] =
                    $reg;
            } while (false);
        }
        // Tree of rules childs
        $this->dtd['elements'][$elem_name]['children'] = $children;
        // Either null, #PCDATA, EMPTY or ANY
        $this->dtd['elements'][$elem_name]['content'] = $content;
    }

    /**
     * XML_DTD_Parser::_ATTLIST()
     * 
     * Handles the ATTLIST parsing
     * 
     * @param array $data $data[0] the element name, 
     *                    $data[1] string with the attributes
     *
     * @return void
     * @access private 
     * @todo   PHPCS - rename to _attList for CamelCase rule
     */
    function _ATTLIST($data)
    {
        $elem = $data[0];
        array_shift($data);
        for ($i=0; $i < count($data); $i = $i + 3) {
            $a    = array();
            $att  = $data[$i];
            $opts = $data[$i+1];
            if ($opts{0} == '(' 
                && $opts{strlen($opts)-1} == ')'
            ) {
                $a['opts'] = 
                    preg_split('/\||,/',
                        preg_replace('|\s+|',
                            '', substr($opts, 1, -1)));
            } else {
                $a['opts'] = $opts; // XXX ID is missing yet
            }
            $def = $data[$i+2];
            if ($def{0} == '"' && $def{strlen($def)-1} == '"') {
                $def = substr($def, 1, -1);
            } elseif ($def == '#FIXED') {
                $a['fixed_value'] = substr($data[$i+3], 1, -1); //strip "s
                $i++;
            }
            $a['defaults'] = $def;

            $this->dtd['elements'][$elem]['attributes'][$att] = $a;
        }
    }
}

/**
 * XML_DTD_Tree
 * 
 * DTD tree format:
 * 
 * <code>
 * [elements] => array(
 *      <tag name> => array(
 *          [children] => array(
 *              0 => <child name>                    //allowed children array
 *          ),
 *          [child_validation_pcre_regex] => string, // The regex for validating
 *                                                   // the list of childs
 *          [child_validation_dtd_regex] => string,  // The DTD element declaration
 *          [content] => string                      // null, #PCDATA, EMPTY or ANY
 *          [attributes] => array(
 *              <att name> => array(
 *                  [opts] => (array|string),        // enumerated or CDATA
 *                  [defaults] => (#IMPLIED|#REQUIRED|#FIXED|value),
 *                  [fixed_value] => string          // only when defaults is #FIXED
 *              )
 *          )
 *      )
 * )
 * </code>
 * 
 * @category  XML
 * @package   XML_DTD
 * @author    Tomas V.V.Cox <cox@idecnet.com> 
 * @copyright 2003-2008 Tomas V.V.Cox <cox@idecnet.com> 
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: 0.5.2
 * @link      http://pear.php.net/package/XML_DTD
 */
class XML_DTD_Tree
{
    /**
     * XML_DTD_Tree::XML_DTD_Tree()
     *
     * The DTD tree array coming from XML_DTD_Parse->parse()
     *  
     * @param array $tree DTD tree
     *
     * @access public
     */
    function XML_DTD_Tree($tree)
    {
        $this->dtd = $tree;
    }

    /**
     * XML_DTD_Tree::getChildren()
     * 
     * @param string $elem element
     *
     * @return array
     * @access public
     */
    function getChildren($elem)
    {
        return $this->dtd['elements'][$elem]['children'];
    }

    /**
     * XML_DTD_Tree::getContent()
     * 
     * @param string $elem element
     *
     * @return string
     * @access public
     */
    function getContent($elem)
    {
        return $this->dtd['elements'][$elem]['content'];
    }

    /**
     * XML_DTD_Tree::getPcreRegex()
     * 
     * Return the perl regular expresion used for validating
     * the children of a node
     * 
     * @param string $elem element
     *
     * @return string
     * @access public
     */
    function getPcreRegex($elem)
    {
        return $this->dtd['elements'][$elem]['child_validation_pcre_regex'];
    }

    /**
     * XML_DTD_Tree::getDTDRegex()
     * 
     * Return the DTD element definition for $elem
     * 
     * @param string $elem element
     *
     * @return string
     * @access public
     */
    function getDTDRegex($elem)
    {
        return $this->dtd['elements'][$elem]['child_validation_dtd_regex'];
    }

    /**
     * XML_DTD_Tree::getAttributes()
     * 
     * @param string $elem element
     *
     * @return array
     * @access public
     */
    function getAttributes($elem)
    {
        if (!isset($this->dtd['elements'][$elem]['attributes'])) {
            return array();
        }
        return $this->dtd['elements'][$elem]['attributes'];
    }

    /**
     * XML_DTD_Tree::elementIsDeclared()
     * 
     * @param string $elem element
     *
     * @return bool
     * @access public
     */
    function elementIsDeclared($elem)
    {
        return isset($this->dtd['elements'][$elem]);
    }
}
?>
