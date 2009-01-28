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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

if('mysql' == DBDRIVER)
{
	if(!class_exists('ADODB_mysql', false))
		require 'adodb/drivers/adodb-mysql.inc.php';
	class lodel_mysql extends ADODB_mysql 
	{
		public $rsPrefix = 'lodel_rs_';
	
		public function __construct()
		{
			parent::__construct();
		}
	
		public function getFieldName($i)
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_field_name($this->_queryID, $i);
		}
	
		public function getFieldTable($i)
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_field_table($this->_queryID, $i);
		}
		
		public function getFieldNum()
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_num_fields($this->_queryID);
		}

		public function fetchField($i=null)
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_fetch_field($this->_queryID, $i);
		}
	}
	
	class lodel_rs_mysql extends ADORecordSet_mysql 
	{
		public function __construct($queryID,$mode=false)
		{
			parent::__construct($queryID,$mode);
		}
	
		public function getFieldName($i)
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_field_name($this->_queryID, $i);
		}
	
		public function getFieldTable($i)
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_field_table($this->_queryID, $i);
		}
		
		public function getFieldNum()
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_num_fields($this->_queryID);
		}

		public function fetchField($i=null)
		{
			if(!is_resource($this->_queryID))
				return false;

			return mysql_fetch_field($this->_queryID, $i);
		}
	}
}
elseif('mysqli' == DBDRIVER)
{
	if(!class_exists('ADODB_mysqli', false))
		require 'adodb/drivers/adodb-mysqli.inc.php';
	class lodel_mysqli extends ADODB_mysqli 
	{
		public $rsPrefix = 'lodel_rs_';
	
		private $_transaction;

		public function __construct()
		{
			parent::__construct();
			$this->_transaction = false;
		}
	
		public function getFieldName($i)
		{
			if(!is_object($this->_queryID))
				return false;
			$fields = $this->_queryID->fetch_fields();
			if(!isset($fields[$i]))
				return false;

			return $fields[$i]->name;
		}
	
		public function getFieldTable($i)
		{
			if(!is_object($this->_queryID))
				return false;
			$fields = $this->_queryID->fetch_fields();
			if(!isset($fields[$i]))
				return false;

			return $fields[$i]->table;
		}
		
		public function getFieldNum()
		{
			if(!is_object($this->_queryID))
				return false;
			$fields = $this->_queryID->fetch_fields();

			return count($fields);
		}

		public function fetchField($i=null)
		{
			if(!is_object($this->_queryID))
				return false;

			$fields = $this->_queryID->fetch_fields();
			if(!is_null($i))
			{
				return isset($fields[$i]) ? $fields[$i] : false;
			}
			return $fields;
		}
	}
	
	class lodel_rs_mysqli extends ADORecordSet_mysqli 
	{
		public function __construct($queryID,$mode=false)
		{
			parent::__construct($queryID,$mode);
		}
	
		public function getFieldName($i)
		{
			if(!is_object($this->_queryID))
				return false;
			$fields = $this->_queryID->fetch_fields();
			if(!isset($fields[$i]))
				return false;

			return $fields[$i]->name;
		}
	
		public function getFieldTable($i)
		{
			if(!is_object($this->_queryID))
				return false;
			$fields = $this->_queryID->fetch_fields();
			if(!isset($fields[$i]))
				return false;

			return $fields[$i]->table;
		}
		
		public function getFieldNum()
		{
			if(!is_object($this->_queryID))
				return false;
			$fields = $this->_queryID->fetch_fields();

			return count($fields);
		}

		public function fetchField($i=null)
		{
			if(!is_object($this->_queryID))
				return false;

			$fields = $this->_queryID->fetch_fields();
			if(!is_null($i))
			{
				return isset($fields[$i]) ? $fields[$i] : false;
			}
			return $fields;
		}
	}
}
else
{
	trigger_error('Invalid DB driver, allowed mysql or mysqli', E_USER_ERROR);
}

$GLOBALS['ADODB_NEWCONNECTION'] = 'lodelADODB_factory';

function &lodelADODB_factory($driver)
{
	if ($driver !== 'mysql' && $driver !== 'mysqli') return false;
	
	$driver = 'lodel_'.$driver;
	$obj = new $driver();
	return $obj;
}

?>