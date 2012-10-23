<?php
/**
 * ISBN validation and conversion functionality
 *
 * PHP version 5
 *
 * Copyright (c) Demian Katz 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/system_classes Wiki
 */
 
/**
 * ISBN Class
 *
 * This class provides ISBN validation and conversion functionality.
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/system_classes Wiki
 */
class ISBN
{
    private $_raw;
    private $_valid = null;
 
    /**
     * Constructor
     *
     * @param string $raw Raw ISBN string to convert/validate.
     *
     * @access public
     */
    public function __construct($raw)
    {
        // Strip out irrelevant characters:
        $this->_raw = self::normalizeISBN($raw);
    }
 
    /**
     * Get the ISBN in ISBN-10 format:
     *
     * @return mixed ISBN, or false if invalid/incompatible.
     * @access public
     */
    public function get10()
    {
        // Is it valid?
        if ($this->isValid()) {
            // Is it already an ISBN-10?  If so, return as-is.
            if (strlen($this->_raw) == 10) {
                return $this->_raw;
            } else if (strlen($this->_raw) == 13
                && substr($this->_raw, 0, 3) == '978'
            ) {
                // Is it a Bookland EAN?  If so, we can convert to ISBN-10.
                $start = substr($this->_raw, 3, 9);
                return $start . self::getISBN10CheckDigit($start);
            }
        }
        
        // If we made it this far, conversion was not possible:
        return false;
    }
 
    /**
     * Get the ISBN in ISBN-13 format:
     *
     * @return mixed ISBN, or false if invalid/incompatible.
     * @access public
     */
    public function get13()
    {
        // Is it valid?
        if ($this->isValid()) {
            // Is it already an ISBN-13?  If so, return as-is.
            if (strlen($this->_raw) == 13) {
                return $this->_raw;
            } else if (strlen($this->_raw) == 10) {
                // Is it an ISBN-10?  If so, convert to Bookland EAN:
                $start = '978' . substr($this->_raw, 0, 9);
                return $start . self::getISBN13CheckDigit($start);
            }
        }
        
        // If we made it this far, conversion was not possible:
        return false;
    }
 
    /**
     * Is the current ISBN valid in some format?  (May be 10 or 13 digit).
     *
     * @return boolean 
     * @access public
     */
    public function isValid()
    {
        // If we haven't already checked validity, do so now and store the result:
        if (is_null($this->_valid)) {
            if (self::isValidISBN10($this->_raw)
                || self::isValidISBN13($this->_raw)
            ) {
                $this->_valid = true;
            } else {
                $this->_valid = false;
            }
        }
        return $this->_valid;
    }
 
    /**
     * Strip extraneous characters and whitespace from an ISBN.
     *
     * @param string $raw ISBN to clean up.
     *
     * @return string     Normalized ISBN.
     * @access public
     */
    public static function normalizeISBN($raw)
    {
        return preg_replace('/[^0-9X]/', '', strtoupper($raw));
    }
 
    /**
     * Given the first 9 digits of an ISBN-10, generate the check digit.
     *
     * @param string $isbn The first 9 digits of an ISBN-10.
     *
     * @return string      The check digit.
     * @access public
     */
    public static function getISBN10CheckDigit($isbn)
    {
        $sum = 0;
        for ($x = 0; $x < strlen($isbn); $x++) {
            $sum += intval(substr($isbn, $x, 1)) * (1 + $x);
        }
        $checkdigit = $sum % 11;
        return $checkdigit == 10 ? 'X' : $checkdigit;
    }
 
    /**
     * Is the provided ISBN-10 valid?
     *
     * @param string $isbn The ISBN-10 to test.
     *
     * @return boolean 
     * @access public
     */
    public static function isValidISBN10($isbn)
    {
        $isbn = self::normalizeISBN($isbn);
        if (strlen($isbn) != 10) {
            return false;
        }
        return (substr($isbn, 9) == self::getISBN10CheckDigit(substr($isbn, 0, 9)));
    }
 
    /**
     * Given the first 12 digits of an ISBN-13, generate the check digit.
     *
     * @param string $isbn The first 12 digits of an ISBN-13.
     *
     * @return string      The check digit.
     * @access public
     */
    public static function getISBN13CheckDigit($isbn)
    {
        $sum = 0;
        $weight = 1;
        for ($x = 0; $x < strlen($isbn); $x++) {
            $sum += intval(substr($isbn, $x, 1)) * $weight;
            $weight = $weight == 1 ? 3 : 1;
        }
        $retval = 10 - ($sum % 10);
        return $retval == 10 ? 0 : $retval;
    }
 
    /**
     * Is the provided ISBN-13 valid?
     *
     * @param string $isbn The ISBN-13 to test.
     *
     * @return boolean 
     * @access public
     */
    public static function isValidISBN13($isbn)
    {
        $isbn = self::normalizeISBN($isbn);
        if (strlen($isbn) != 13) {
            return false;
        }
        return
            (substr($isbn, 12) == self::getISBN13CheckDigit(substr($isbn, 0, 12)));
    }
}