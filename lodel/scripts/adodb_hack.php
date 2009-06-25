<?php
/**
 * Fichier d'ajout de support mysql(i) pour ADOdb
 *
 * PHP version 5
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
 * @version CVS:$Id:
 * @package lodel
 */

$err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
include "adodb/adodb.inc.php";

if('mysql' === DBDRIVER)
{
    class_exists('ADODB_mysql', false) || include 'adodb/drivers/adodb-mysql.inc.php';

    class lodel_mysql extends ADODB_mysql 
    {
        public $rsPrefix = 'lodel_rs_';
    
        public function __construct()
        {
            parent::__construct();
        }
    
        public function getFieldName(&$result, $i)
        {
            if(!is_object($result))
                return false;
    
            return isset($result->_fieldobjects[$i]) ? $result->_fieldobjects[$i]->name : false;
        }
    
        public function getFieldTable(&$result, $i)
        {
            if(!is_object($result))
                return false;
    
            return isset($result->_fieldobjects[$i]) ? $result->_fieldobjects[$i]->table : false;
        }
        
        public function getNbRows(&$result)
        {
            if(!is_object($result))
                return false;
    
	    if(isset($result->_fieldobjects))
            	return count($result->_fieldobjects);
	    elseif(isset($result->_numOfRows))
		return $result->_numOfRows;
        }
    
	public function getFieldNum(&$result)
	{
		if(!is_object($result))
                	return false;

		return count($result->fields);
	}

        public function fetchField(&$result, $i=null)
        {
            if(!is_object($result))
                return false;
    
            if(isset($i))
            {
                return isset($result->_fieldobjects[$i]) ? $result->_fieldobjects[$i] : false;
            }
            return $result->_fieldobjects;
        }
    }
    
    class lodel_rs_mysql extends ADORecordSet_mysql 
    {
        public function __construct($queryID,$mode=false)
        {
            parent::__construct($queryID,$mode);
        }
    }
}
elseif('mysqli' === DBDRIVER)
{
    class_exists('ADODB_mysqli', false) || include 'adodb/drivers/adodb-mysqli.inc.php';

    class lodel_mysqli extends ADODB_mysqli 
    {
        public $rsPrefix = 'lodel_rs_';
    
        private $_transaction;
    
	public $_fields;

        public function __construct()
        {
            parent::__construct();
        }
    
        public function getFieldName(&$result, $i)
        {
            if(!is_object($result))
                return false;

            if(!isset($result->_fieldobjects[$i]))
                return false;
    
            return $result->_fieldobjects[$i]->name;
        }
    
        public function getFieldTable(&$result, $i)
        {
            if(!is_object($result))
                return false;

            if(!isset($result->_fieldobjects[$i]))
                return false;
    
            return $result->_fieldobjects[$i]->table;
        }
        
	public function getFieldNum(&$result)
	{
		if(!is_object($result))
                	return false;

		return count($result->fields);
	}

        public function getNbRows(&$result)
        {
            if(!is_object($result))
                return false;

	    if(isset($result->_fieldobjects))
            	return count($result->_fieldobjects);
	    elseif(isset($result->_numOfRows))
		return $result->_numOfRows;
        }
    
        public function fetchField(&$result, $i=null)
        {
            if(!is_object($result))
                return false;

            if(isset($i))
            {
                return isset($result->_fieldobjects[$i]) ? $result->_fieldobjects[$i] : false;
            }
            return $result->_fieldobjects;
        }
    }
    
    class lodel_rs_mysqli extends ADORecordSet_mysqli 
    {
        public function __construct($queryID,$mode=false)
        {
            parent::__construct($queryID,$mode);
        }
    }
}
else trigger_error('Invalid DB driver, allowed mysql or mysqli', E_USER_ERROR);

$GLOBALS['ADODB_NEWCONNECTION'] = 'lodelADODB_factory';

function &lodelADODB_factory($driver)
{
    if ($driver !== 'mysql' && $driver !== 'mysqli') return false;
    
    $driver = 'lodel_'.$driver;
    $obj = new $driver();
    return $obj;
}
error_reporting($err);
?>