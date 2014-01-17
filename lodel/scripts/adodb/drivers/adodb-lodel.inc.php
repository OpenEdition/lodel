<?php
if (!defined('ADODB_DIR')) die('pas bon');

$file = ADODB_DIR."/drivers/adodb-mysql.inc.php";
include_once($file);

if (! defined("_ADODB_LODEL_LAYER")) {
	define("_ADODB_LODEL_LAYER", 1 );

	class ADODB_lodel extends ADODB_mysqli {
                var $_connectionID = false;    /// The returned link identifier whenever a successful database connection is made.
                var $_connectionIDR = false;    /// The returned link identifier whenever a successful database connection is made (read only).
                
            function _connect(Array $databases, $argDatabasename, $persist)/*($argHostname = NULL, $argUsername = NULL, $argPassword = NULL, $argDatabasename = NULL, $persist=false)*/
            {
                    if(!extension_loaded("mysqli")) {
                           return null;
                    }

                    $this->_connectionID = @mysqli_init();
                    $this->_connectionIDR = @mysqli_init();

                    if (is_null($this->_connectionID || $this->_connectionIDR)) {
                        // mysqli_init only fails if insufficient memory
                        if ($this->debug) 
                                       ADOConnection::outp("mysqli_init() failed : "  . $this->ErrorMsg());
                        return false;
                    }
                    /*
                    I suggest a simple fix which would enable adodb and mysqli driver to
                    read connection options from the standard mysql configuration file
                    /etc/my.cnf - "Bastien Duclaux" <bduclaux#yahoo.com>
                    */
                    foreach($this->optionFlags as $arr) {	
                        mysqli_options($this->_connectionID,$arr[0],$arr[1]);
                        mysqli_options($this->_connectionIDR,$arr[0],$arr[1]);
                    }

                    if ($persist && PHP_VERSION > 5.2 && strncmp($databases['write'][0]['dbhost'],'p:',2) != 0) $databases['write'][0]['dbhost'] = 'p:'.$databases['write'][0]['dbhost'];
                    if ($persist && PHP_VERSION > 5.2 && strncmp($databases['read'][0]['dbhost'],'p:',2) != 0) $databases['read'][0]['dbhost'] = 'p:'.$databases['read'][0]['dbhost'];


                    $ok = mysqli_real_connect($this->_connectionID,
                                       $databases['write'][0]['dbhost'],
                                       $databases['write'][0]['dbusername'],
                                       $databases['write'][0]['dbpasswd'],
                                       $argDatabasename,
                                           $this->port,
                                           $this->socket,
                                           $this->clientFlags);
                    $okr = mysqli_real_connect($this->_connectionID,
                                       $databases['read'][0]['dbhost'],
                                       $databases['read'][0]['dbusername'],
                                       $databases['read'][0]['dbpasswd'],
                                       $argDatabasename,
                                           $this->port,
                                           $this->socket,
                                           $this->clientFlags);


                    if ($ok && $okr) 
                    {
                        if ($argDatabasename)
                        {  
                            return $this->SelectDB($argDatabasename);
                        }

                        return true;

                    } else {
                        if ($this->debug) 
                                ADOConnection::outp("Could't connect : "  . $this->ErrorMsg());
                        $this->_connectionID = null;
                        $this->_connectionIDR = null;

                        return false;
                    }
                }
	
                // returns true or false
                // How to force a persistent connection
                function _pconnect(Array $databases, $argDatabasename)
                {
                    return $this->_connect($databases, $argDatabasename, true);
                }

                // When is this used? Close old connection first?
                // In _connect(), check $this->forceNewConnect? 
                function _nconnect($databases, $argDatabasename)
                {
                    $this->forceNewConnect = true;
                    return $this->_connect($databases, $argDatabasename);
                }

                function SelectDB($dbName) 
                {
                //	    $this->_connectionID = $this->mysqli_resolve_link($this->_connectionID);
                    $this->database = $dbName;
                        $this->databaseName = $dbName; # obsolete, retained for compat with older adodb versions

                    if ($this->_connectionID && $this->_connectionIDR) 
                    {
                        $result = true;

                        $result = @mysqli_select_db($this->_connectionID, $dbName);
                        $result = @mysqli_select_db($this->_connectionIDR, $dbName);
                        if (!$result)
                        {
                            ADOConnection::outp("Select of database " . $dbName . " failed. " . $this->ErrorMsg());
                        }
                        return $result;		
                    }

                    return false;	
                }

                function _query($sql,$inputarr=false) {
                    error_log("SQL: ".var_export($sql,true));
                    $success = false;
                    if($this->_connect($databases)){
                        if(ADODB_lodel::is_write_query($sql))
                        {
                            $success = parent::_query($sql,$this->_connectionID);
                        }else{
                            $success = parent::_query($sql,$this->_connectionIDR);
                        }
                    }

                    return $success;
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

    }

}