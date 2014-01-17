<?php
if (!defined('ADODB_DIR')) die('pas bon');

$file = ADODB_DIR."/drivers/adodb-mysqli.inc.php";
include_once($file);

if (! defined("_ADODB_LODEL_LAYER")) {
	define("_ADODB_LODEL_LAYER", 1 );

	class ADODB_lodel extends ADODB_mysqli {
        var $_connectionID = false;    /// The returned link identifier whenever a successful database connection is made.
        var $_connectionIDR = false;    /// The returned link identifier whenever a successful database connection is made (read only).
        var $databases = false;

        function Connect($argHostname = "", $argUsername = "", $argPassword = "", $argDatabaseName = "", $forceNew = false) 
        {
            $this->databases = ADODB_lodel::createDatabaseArray($argHostname, $argUsername, $argPassword, $argDatabaseName);
            foreach (array('read', 'write') as $mode) {
                foreach (array('host', 'user', 'password', 'database') as $var)
                    $$var = $this->databases[$mode][0][$var];
//                     error_log("$host, $user, $password, $database,".var_export("",true));
                $ret = parent::connect($host, $user, $password, $database);
                $this->databases[$mode][0]['_connectionID'] = $this->_connectionID;
                error_log("CONNECTID: ".var_export($this->_connectionID, true));
//                     error_log("A: ".var_export($ret,true));
            }
            return $ret;
        }

        function SelectDB($dbName) 
        {
error_log("SelectDB : ".var_export($this->_connectionID,true));
        //      $this->_connectionID = $this->mysqli_resolve_link($this->_connectionID);
            $this->database = $dbName;
            $this->databaseName = $dbName; # obsolete, retained for compat with older adodb versions

            if ($this->getHostConnectId('read')) {
                $result = true;

                $result = @mysqli_select_db($this->getHostConnectId('write'), $dbName);
                $result = @mysqli_select_db($this->getHostConnectId('read'), $dbName);
                if (!$result)
                {
                    ADOConnection::outp("Select of database " . $dbName . " failed. " . $this->ErrorMsg());
                }
                return $result;
            }

            return false;
        }

        function _query($sql,$inputarr=false) {
//                     error_log("SQL: ".var_export($sql,true));
            $success = false;
            if (ADODB_lodel::is_write_query($sql)) {
                $connectId = $this->getHostConnectId('write');
                error_log("WRITE");
            } else {
                $connectId = $this->getHostConnectId('read');
                error_log("READ");
            }

            $this->_connectionID = $connectId;
            $success = mysqli_query($sql, $connectId);
            return $success;
        }

        function getHostConnectId($mode) {
            $connectId = false;
            switch ($mode) {
                case 'write':
                    if (isset($this->databases['write'][0]['_connectionID']))
                        $connectId = $this->databases['write'][0]['_connectionID'];
                    break;
                default:
                    if (isset($this->databases['read'][0]['_connectionID']))
                        $connectId = $this->databases['read'][0]['_connectionID'];
            }
            if (!$connectId)
                $connectId = $this->_connectionID;

            return $connectId;
        }

        /**
        * Determine the likelihood that this query could alter anything
        * @param string query
        * @return bool
        */
        static function is_write_query( $q ) 
        {
            // Quick and dirty: only SELECT statements are considered read-only.
            $q = ltrim($q, "\r\n\t (");
            return !preg_match('/^(?:SELECT|SHOW|DESCRIBE|EXPLAIN)\s/i', $q);
        }

        static function createDatabaseArray($hosts, $user = NULL, $password = NULL, $database = NULL) {
            if (!is_array($hosts)) {
                $hosts = array(array('host'=>$hosts, 'user'=>$user, 'password'=>$password, 'database'=>$database, 'mode'=>'rw'));
            } elseif (!is_array($hosts[0])) {
                $hosts = array($hosts);
            }

            $databases = array('read' => array(), 'write' => array());
            foreach ($hosts as $host) {
                $hostmodes = array('read', 'write');
                if (isset($host['mode'])) {
                    switch ($host['mode']) {
                        case 'r':
                            unset($hostmodes['write']);
                            break;
                        case 'w';
                            unset($hostmodes['read']);
                            break;
                    }
                }
                foreach ($hostmodes as $mode)
                    $databases[$mode][] = $host;
            }

//                     error_log("A: ".var_export($databases,true));
            return $databases;
        }

    }

}