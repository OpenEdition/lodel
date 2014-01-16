<?php
if (!defined('ADODB_DIR')) die('pas bon');

$file = ADODB_DIR."/drivers/adodb-mysql.inc.php";
include_once($file);

if (! defined("_ADODB_LODEL_LAYER")) {
	define("_ADODB_LODEL_LAYER", 1 );

	class ADODB_lodel extends ADODB_mysql {
// 		function ADODB_lodel() 
// 		{			
// 			if (defined('ADODB_EXTENSION')) $this->rsPrefix .= 'ext_';
// 		}

		function _query($sql,$inputarr=false) {
			error_log("SQL: ".var_export($sql,true));
// 			error_log("A: ".var_export($this->_connectionID,true));
// 			if ($method == 'write') {
// 				$this->connect();
// 			} else {
// 				
// 			}
			return parent::_query($sql,$inputarr);
		}
                
                /**
                * Determine the likelihood that this query could alter anything
                * @param string query
                * @return bool
                */
               static function is_write_query( $q ) {
                       // Quick and dirty: only SELECT statements are considered read-only.
                       $q = ltrim($q, "\r\n\t (");
                       return !preg_match('/^(?:SELECT|SHOW|DESCRIBE|EXPLAIN)\s/i', $q);
               }

	}

}