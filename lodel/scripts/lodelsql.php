<?php

class LodelSql
{
	private $connectionObject;

	public $database;
	public $debug;
	public $memcachecompress;
	public $memcachehost;
	public $memcacheport;
	public $memcache;

// TODO: search for these constants and change to use setFetchMode instead
// $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

	/**
	 * Create a connection object to a database
	 * @param string $dbDriver name of the sql engine to connect to
	 */
	public function __construct($dbDriver) {
		$this->connectionObject = ADONewConnection($dbDriver);

		$this->database = &$this->connectionObject->database;
		$this->debug = &$this->connectionObject->debug;
		$this->memcachecompress = &$this->connectionObject->memcachecompress;
		$this->memcachehost = &$this->connectionObject->memcachehost;
		$this->memcacheport = &$this->connectionObject->memcacheport;
		$this->memcache = &$this->connectionObject->memcache;
	}

	/*
	Server functions
	*/
	/**
	 * Connect to the given database
	 * @param string $host uri of the SQL server
	 * @param string $user name of the SQL user
	 * @param string $password password for the SQL user
	 * @param string $database name of the database to connect to
	 * @return boolean True on success, False on failure
	 */
	public function connect($host, $user, $password, $database) {
		return $this->connectionObject->connect($host, $user, $password, $database);
	}

	/**
	 * Select a database, using the current connection
	 * @param string $database name of the database to connect to
	 * @return boolean True on success, False on failure
	 * TODO: not all database engine support that ! We should create different connections for different databases
	 */
	public function selectDB($database) {
		return $this->connectionObject->SelectDB($database);
	}

	/**
	 * Returns an array of containing two elements 'description' and 'version'.
	 * @return int error number
	 */
	// used only once src/lodel/edition/tpl/dashboard_information.html:129
	public function serverInfo() {
		return $this->connectionObject->ServerInfo();
	}

	/**
	 *  Setting the client encoding of the connection
	 * @param string $charset name of the encoding
	 * @return boolean True on success, False on failure
	 */
	// not used, but could be usefull using SQL : SET NAMES '$charset';
	// TODO: we also should provide a getCharSet()
	public function setCharSet($charset) {
		return $this->connectionObject->SetCharSet($charset);
	}

	/**
	 * Set the default fetch mode
	 * @param string $mode name of the encoding
	 * @return boolean True on success, False on failure
	 */
	// used once lodel/scripts/connect.php:108, et avec des globals lodel/scripts/dao.php:361 …
	// TODO: one day put that in LodelSqlStatement object
	public function setFetchMode($mode) {
		return $this->connectionObject->SetFetchMode($mode);
	}

	/**
	 * return the last error message associated with the last operation on the database handle
	 * @return string error message
	 */
	// TODO: one day put that in LodelSqlStatement object
	public function errorMsg() {
		return $this->connectionObject->ErrorMsg();
	}

	/**
	 * return the last error number associated with the last operation on the database handle
	 * @return int error number
	 */
	// TODO: one day put that in LodelSqlStatement object
	public function errorNo() {
		return $this->connectionObject->errorno();
	}

	/**
	 * Returns the number of rows affected by the last SQL statement
	 * @return int number of rows affected by the last SQL statement
	 */
	// TODO: must be RENAMED to fit other method naming, but keep it for compatibility
// lodel/scripts/dao.php:525:
// lodel/scripts/dao.php:574:
// lodel/scripts/logic/class.entities_advanced.php:254:
	public function Affected_Rows() {
		return $this->connectionObject->Affected_Rows();
	}

	/**
	 * Returns the ID of the last inserted row
	 * @return int ID of the last inserted row
	 */
	// TODO: must be RENAMED to fit other method naming, but keep it for compatibility
// lodel/scripts/class.siteManage.php:335:
// lodel/scripts/connect.php:198:
// lodel/scripts/dao.php:294:
// lodel/scripts/logic/class.entities_edition.php:807:
// lodel/scripts/logic/class.entities_edition.php:843:
// lodel/scripts/logic/class.entries.php:832:
// lodel/scripts/logic/class.tasks.php:106:
	public function Insert_ID($table='', $column='') {
		return $this->connectionObject->Insert_ID($table, $column);
	}

	/**
	 * Quotes the string $s, escaping the database specific quote character as appropriate
	 * @param string $s string to be quoted
	 * @return string quoted string
	 */
	public function quote($s) {
		return $this->connectionObject->quote($s);
	}

	// used only once scripts/loginfunc.php:88
	// must be DELETED and use quote() instead
	public function qstr($s, $magic_quotes=false) {
		return $this->connectionObject->qstr($s, $magic_quotes);
	}

	/*
	SQL functions
	should return a LodelSqlStatement
	*/

	/**
	 * prepare an SQL query to be executed with execute()
	 * @param string $sql SQL query to be prepared
	 * @return object statement object
	 */
	// TODO: not used in Lodel, but we should
	public function prepare($sql) {
			return $this->connectionObject->Prepare($sql);
	}

	/**
	 * Executes a prepared SQL query
	 * @param object $sql statement object prepared by prepare()
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return object RecordSet
	 */
	// TODO: in lodel execute is used using a string $sql query !!!
	// TODO: MUST return a LodelSqlStatement
	public function execute($sql, $inputarr=false) {
		$res = $this->connectionObject->execute($sql, $inputarr);
		if($res!== false)
			return new LodelSqlStatement($res);
		return false;
	}

	/**
	 * Executes an SQL query
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return object LodelSqlStatement
	 */
	// TODO: not used in Lodel, since execute does the same job
	public function query($sql, $inputarr=false) {
		$res = $this->connectionObject->Query($sql, $inputarr);
		if($res!== false)
			return new LodelSqlStatement($res);
		return false;
	}

	/**
	 * Executes an SQL query, simulating  LIMIT and OFFSET statement
	 * @param string $sql SQL query to be executed
	 * @param int $nrows limit
	 * @param int $offset offset
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return object RecordSet
	 */
	// TODO: used only once scripts/view.php:224, to DELETE
	public function selectlimit($sql, $nrows=-1, $offset=-1, $inputarr=false) {
		$res = $this->connectionObject->SelectLimit($sql, $nrows, $offset, $inputarr);
		if($res!== false)
			return new LodelSqlStatement($res);
		return false;
	}

	/*
	SQL functions
	return an array
	*/

	/**
	 * Executes an SQL query and returns an array of result set
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return mixed[] associated array of the result set
	 */
	public function getAll($sql, $inputarr=false) {
		return $this->connectionObject->GetAll($sql, $inputarr);
	}

	/**
	 * Executes an SQL query and returns an array of result set
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return mixed[] associated array of the result set
	 */
	public function getArray($sql, $inputarr=false) {
		return $this->connectionObject->GetArray($sql, $inputarr);
	}

	/**
	 * Executes an SQL query and returns an array of result set
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return mixed[] associated array of the result set
	 */
	// not used in lodel
	public function getAssoc() {
		return $this->connectionObject->GetAssoc();
	}

	/**
	 * Executes an SQL query and returns the first value of the first result
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return mixed first value of the first result
	 */
	public function getOne($sql, $inputarr=false) {
		return $this->connectionObject->GetOne($sql, $inputarr);
	}

	/**
	 * Executes an SQL query and returns the first value of the result set
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return mixed[] first value of the result set
	 */
	public function getCol($sql, $inputarr=false, $trim=false) {
		return $this->connectionObject->GetCol($sql, $inputarr, $trim);
	}

	/**
	 * Executes an SQL query and returns the first row of the result set
	 * @param string $sql SQL query to be executed
	 * @param mixed[] $inputarr array of insert values, placeholders or named parameters
	 * @return mixed[] first row of the result set
	 */
	public function getRow($sql, $inputarr=false) {
		return $this->connectionObject->GetRow($sql,$inputarr);
	}

	/*
	Schema
	*/

	/**
	 * Returns a list of databases available on the server as an array
	 * @return string[] names of databases avalaible on the current connection
	 */
	// used only once lodel/scripts/class.siteManage.php:555
	public function metaDatabases() {
		return $this->connectionObject->MetaDatabases();
	}

	// used only once lodel/scripts/tablefields.php:77
	/**
	 * Returns an array of tables for the current database as an array
	 * @return string[] names of tables avalaible on the current database
	 */
	public function metaTables() {
		return $this->connectionObject->MetaTables('TABLES');
	}

	// used only once scripts/tablefields.php:79
	// TODO: should not return an object but an array('name'=>, 'type'=>, 'max_length'=>)
	/**
	 * Returns an array of ADOFieldObject's, one field object for every column
	 * @param string $table name of the table
	 * @return object[] names of collumns of the given table
	 */
	public function metaColumns($table) {
		return $this->connectionObject->MetaColumns($table, true);
	}

	// not used, to DELETE
	public function metaPrimaryKeys($table, $owner=false) {
		return $this->connectionObject->MetaPrimaryKeys($table, $owner);
	}

	// not used, to DELETE
	public function metaType($t,$len=-1, $fieldobj=false) {
		return $this->connectionObject->MetaType($t, $len, $fieldobj);
	}
 
	/*
	memcache functions, to DELETE
	*/
	// TODO: delete all use of these functions
	// It is not proprely used, there is no cacheExecute() calls
	public function cacheExecute($secs2cache, $sql=false, $inputarr=false) {
		return $this->connectionObject->CacheExecute($secs2cache, $sql, $inputarr);
	}

	//lodel/scripts/view.php:303:
	public function cacheFlush($sql=false, $inputarr=false) {
		return $this->connectionObject->cacheflush($sql, $inputarr);
	}

	// TODO: test it
	// scripts/loginfunc.php:409
	public function cacheGetOne($secs2cache, $sql=false, $inputarr=false) {
		return $this->connectionObject->cacheGetOne($secs2cache, $sql, $inputarr);
	}

}

//TODO !!  <- DONE =P
/**
 *  
 */
class LodelSqlStatement {
	
	/**
	 * True when no more row
	 * @var boolean 
	 */
	public $EOF = true;
	
	/**
	 * An array containing the results
	 * @var array 
	 */
	public $fields = array();

		//instance of a ADORecordSet
		private $rs;

		public function __construct($recordSet) {
			$this->rs = $recordSet;
			$this->EOF = &$this->rs->EOF;
			$this->fields = &$this->rs->fields;
		}

		/**
		 * Set the cursor to the next row of result
		 * @return boolean True on success, False on failure
		 */
		public function moveNext() {
				return $this->rs->MoveNext();
		}

		/**
		 * get the array of result, and move the cursor to the next row of result
		 * @return mixed[]
		 */
		public function fetchRow() {
				return $this->rs->FetchRow();
		}

		/**
		 * close the current statement
		 * @return boolean True on success, False on failure
		 */
		public function Close() {
				return $this->rs->Close();
		}

		/**
		 * Return the number of columns in the recordset. Some databases will set this to 0
		 * if no records are returned, others will return the number of columns in the query.
		 * @return int
		 */
		public function fieldCount(){
			return $this->rs->FieldCount();
		}

		/**
		* Get the FieldObject of a specific column.
		*
		* @param fieldoffset is the column position to access(0-based).
		*
		* @return FieldObject for that column, or false.
		*/
		function fetchField($fieldOffset = -1){ 
			return $this->rs->FetchField($fieldOffset);
		}

		/**
		 * 
		 * @return type
		 */
		public function RecordCount(){
			$result = ob_get_clean();
			
			return $this->rs->RecordCount();
		}

		/**
		 * 
		 * @return type
		 */
		public function rowCount(){
			return $this->rs->RowCount();
		}

		/**
		 * 
		 * @return type
		 */
		public function numRows(){
			return $this->rs->numRows();
		}
}
