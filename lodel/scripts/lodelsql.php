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

	public function connect($argHostname="", $argUsername="", $argPassword="", $argDatabaseName="", $forceNew=false) {
		return $this->connectionObject->connect($argHostname, $argUsername, $argPassword, $argDatabaseName);
	}

	public function selectDB($dbName) {
		return $this->connectionObject->SelectDB($dbName);
	}

	public function errormsg() {
		return $this->connectionObject->ErrorMsg();
	}

	public function errorno() {
		return $this->connectionObject->errorno();
	}

	// used only oncescripts/view.php:224
	public function selectlimit($sql, $nrows=-1, $offset=-1, $inputarr=false, $secs2cache=0) {
		return $this->connectionObject->SelectLimit($sql, $nrows, $offset, $inputarr, $secs2cache);
	}

	// used only once src/lodel/edition/tpl/dashboard_information.html:129
	public function serverInfo() {
		return $this->connectionObject->ServerInfo();
	}

	// not used, but could be usefull
	public function setCharSet($charset){
		return $this->connectionObject->SetCharSet($charset);
	}

	// not used, but could be usefull
	public function setFetchMode($mode) {
		return $this->connectionObject->SetFetchMode($mode);
	}

	/*
	SQL functions
	*/

	public function prepare($sql){
			return $this->connectionObject->Prepare($sql);
	}

	public function execute($sql, $inputarr=false) {
		return $this->connectionObject->execute($sql, $inputarr);
	}

	public function query($sql, $inputarr=false){
		return $this->connectionObject->Query($sql, $inputarr);
	}

	public function getAll($sql, $inputarr=false){
		return $this->connectionObject->GetAll($sql, $inputarr);
	}

	public function getOne($sql, $inputarr=false) {
		return $this->connectionObject->GetOne($sql, $inputarr);
	}

	public function getArray($sql, $inputarr=false) {
		return $this->connectionObject->GetArray($sql, $inputarr);
	}

	public function getAssoc() {
		return $this->connectionObject->GetAssoc();
	}

	public function getCol($sql, $inputarr=false, $trim=false) {
		return $this->connectionObject->GetCol($sql, $inputarr, $trim);
	}

	public function getRow($sql, $inputarr=false) {
		return $this->connectionObject->GetRow($sql,$inputarr);
	}

	public function Affected_Rows() {
		return $this->connectionObject->Affected_Rows();
	}

	public function Insert_ID($table='', $column='') {
		return $this->connectionObject->Insert_ID($table, $column);
	}

	function concat(){
		return $this->connectionObject->Concat();
	}

	public function quote($s) {
		return $this->connectionObject->quote($s);
	}

	// used only once scripts/loginfunc.php:88
	public function qstr($s, $magic_quotes=false) {
		return $this->connectionObject->qstr($s, $magic_quotes);
	}

	/*
	Schema
	*/

	// used only once scripts/tablefields.php:79
	public function metaColumns($table, $normalize=true) {
		return $this->connectionObject->MetaColumns($table, $normalize);
	}

	public function metaDatabases() {
		return $this->connectionObject->MetaDatabases();
	}

	public function metaTables($ttype=false, $showSchema=false, $mask=false) {
		return $this->connectionObject->MetaTables($ttype, $showSchema, $mask);
	}

	public function metaPrimaryKeys($table, $owner=false) {
		return $this->connectionObject->MetaPrimaryKeys($table, $owner);
	}

	public function metaType($t,$len=-1, $fieldobj=false){
		return $this->connectionObject->MetaType($t, $len, $fieldobj);
	}

	/*
	memcache functions
	*/

	public function cacheExecute($secs2cache, $sql=false, $inputarr=false){
		return $this->connectionObject->CacheExecute($secs2cache, $sql, $inputarr);
	}

	public function cacheFlush($sql=false, $inputarr=false) {
		return $this->connectionObject->cacheflush($sql, $inputarr);
	}

	// TODO: test it
	// scripts/lodelsql.php:162
	// scripts/loginfunc.php:409
	public function cacheGetOne($secs2cache, $sql=false, $inputarr=false) {
	}

}
