<?php

class LodelSql
{
	public $debug = false;
	public $database;
	public $memcachecompress = false;
	public $memcachehost;
	public $memcacheport;
	public $memcache;
	public $connectionObject;
	public $_connectionID;
	public $databaseType;
	public $fetchMode;
	public $hasInsertID;
	public $insertid;
	public $nameQuote;
	public $_errorMsg;

	public function __construct($dbDriver) {
		$this->connectionObject = ADONewConnection($dbDriver);

		$this->memcachecompress = &$this->connectionObject->memcachecompress;
		$this->memcachehost = &$this->connectionObject->memcachehost;
		$this->memcacheport = &$this->connectionObject->memcacheport;
		$this->memcache = &$this->connectionObject->memcache;
		$this->_connectionID = &$this->connectionObject->_connectionID;
		$this->fetchMode = &$this->connectionObject->fetchMode;
		$this->hasInsertID = &$this->connectionObject->hasInsertID;
		$this->insertid = &$this->connectionObject->insertid;
		$this->nameQuote = &$this->connectionObject->nameQuote;
		$this->_errorMsg = &$this->connectionObject->_errorMsg;
		$this->database = &$this->connectionObject->database;
		$this->debug = &$this->connectionObject->debug;
	}

	public function Affected_Rows() {
		return $this->connectionObject->Affected_Rows();
	}

	public function CacheExecute($secs2cache, $sql=false, $inputarr=false){
		return $this->connectionObject->CacheExecute($secs2cache, $sql, $inputarr);
	}

	public function CacheFlush($sql=false, $inputarr=false) {
		return $this->connectionObject->cacheflush($sql, $inputarr);
	}

	public function cachegetone($secs2cache, $sql=false, $inputarr=false) {
	}

	public function connect($argHostname="", $argUsername="", $argPassword="", $argDatabaseName="", $forceNew=false) {
		return $this->connectionObject->connect($argHostname, $argUsername, $argPassword, $argDatabaseName);
	}

	function Concat(){
		return $this->connectionObject->Concat();
	}

	public function database($database=null) {
		$this->connectionObject->database = $database;
	}

	public function errormsg() {
		return $this->connectionObject->ErrorMsg();
	}

	public function errorno() {
		return $this->connectionObject->errorno();
	}

	public function execute($sql, $inputarr=false) {
		return $this->connectionObject->execute($sql, $inputarr);
	}

	public function GetActiveRecordsClass(
			$class, $table, $whereOrderBy=false, $bindarr=false,
			$primkeyArr=false, $extra=array(), $relations=array()){
		return $this->connectionObject->GetActiveRecordsClass(
			$class, $table, $whereOrderBy, $bindarr,
			$primkeyArr, $extra, $relations);
	}

	public function GetActiveRecords($table, $where=false, $bindarr=false, $primkeyArr=false){
		return $this->connectionObject->GetActiveRecords($table, $where, $bindarr, $primkeyArr);
	}

	public function GetAll($sql, $inputarr=false){
		return $this->connectionObject->GetAll($sql, $inputarr);
	}

	public function getArray($sql, $inputarr=false) {
		return $this->connectionObject->GetArray($sql, $inputarr);
	}

	public function getcol($sql, $inputarr=false, $trim=false) {
		return $this->connectionObject->GetCol($sql, $inputarr, $trim);
	}

	public function getone($sql, $inputarr=false) {
		return $this->connectionObject->GetOne($sql, $inputarr);
	}

	public function getrow($sql, $inputarr=false) {
		return $this->connectionObject->GetRow($sql,$inputarr);
	}

	public function Insert_ID($table='', $column='') {
		return $this->connectionObject->Insert_ID($table, $column);
	}

	public function MetaColumns($table, $normalize=true) {
		return $this->connectionObject->MetaColumns($table, $normalize);
	}

	public function MetaDatabases() {
		return $this->connectionObject->MetaDatabases();
	}

	public function MetaTables($ttype=false, $showSchema=false, $mask=false) {
		return $this->connectionObject->MetaTables($ttype, $showSchema, $mask);
	}

	public function MetaPrimaryKeys($table, $owner=false) {
		return $this->connectionObject->MetaPrimaryKeys($table, $owner);
	}

	public function MetaType($t,$len=-1, $fieldobj=false){
		return $this->connectionObject->MetaType($t, $len, $fieldobj);
	}

	public function outp_throw($msg, $src='WARN', $sql=''){
		return $this->connectionObject->outp_throw($msg, $src, $sql);
	}

	public function Prepare($sql){
			return $this->connectionObject->Prepare($sql);
	}

	public function Param($name, $type='C'){
		return $this->connectionObject->Param($name, $type);
	}

	public function Parameter(&$stmt, &$var, $name, $isOutput=false, $maxLen=4000, $type=false){
		return $this->connectionObject->Parameter($stmt, $var, $name, $isOutput, $maxLen, $type);
	}

	public function qstr($s, $magic_quotes=false) {
		return $this->connectionObject->qstr($s, $magic_quotes);
	}

	public function Query($sql, $inputarr=false){
		return $this->connectionObject->Query($sql, $inputarr);
	}

	public function quote($s) {
		return $this->connectionObject->quote($s);
	}

	public function Replace($table, $fieldArray, $keyCol, $autoQuote=false, $has_autoinc=false){
		return $this->connectionObject->Replace($table, $fieldArray, $keyCol, $autoQuote, $has_autoinc);
	}

	public function SelectDB($dbName) {
		return $this->connectionObject->SelectDB($dbName);
	}

	public function selectlimit($sql, $nrows=-1, $offset=-1, $inputarr=false, $secs2cache=0) {
		return $this->connectionObject->SelectLimit($sql, $nrows, $offset, $inputarr, $secs2cache);
	}

	public function serverinfo() {
		return $this->connectionObject->ServerInfo();
	}

	public function SetCharSet($charset){
		return $this->connectionObject->SetCharSet($charset);
	}

	public function GetAssoc() {
		return $this->connectionObject->GetAssoc();
	}

	public function setfetchmode($mode) {
		return $this->connectionObject->SetFetchMode($mode);
	}
}
