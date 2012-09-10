<?php
/*
	dbManager for Mysqli v1.1
	== Usage ============================
	1. Backward compatible version:
	   $db = new dbManager(db_host, db_user, db_pass, db_schm);
	2. Support of charaset, disable debug message
	   $db = new dbManager(db_host, db_user, db_pass, db_schm, FALSE, 'utf8');
	3. To check connection error:
	   if($db->error !== NULL) {
		   // error exists
	   }
	4. Escape String
	   $db->escape_string($str);
	5. Use MySQLi PHP functions directly
	   Obtain MySQLi object by:
	   $db->mysqli
	   
	Parameters of constructor:
	host 		: Host of MySQL server , e.g. localhost or 192.168.1.123 ( make sure TCP/IP connection of MySQL server is enabled )
	user 		: Username
	pass		: Password
	_debugMode	: Debug mode ( set TRUE to enable , set FALSE to disable )
	charSet		: Character set of connection ( defaults to UTF-8 )
	autoCommit	: Transaction Auto Commit mode ( set TRUE to enable , set FALSE to disable )
	port		: Server Port of MySQL server ( defaults to 3306 , standard port of MySQL server )
	persistent	: Persistent Connection mode ( set TRUE to enable , set FALSE to disable )
	
	
	== Version History ==================
	v1.0
	- initial release
	v1.1
	- add custom server port support
	- add persistent connection support
	
	== Program History ==================
	original dbManager for MySQL by Raptor Kwok
	original dbManager for MySQLi by Hoyu
	
	Feel free to use, but kindly leave this statement here.
	
	Technical Support : findme@raptor.hk ( please specify "dbManager for MySQLi" in title )
*/

class dbManager {
	public $error = NULL;
	public $debugMode = TRUE;
	public $mysqli;
	
	public function __construct($host, $user, $pass, $dbname = '', $_debugMode = TRUE, $charSet = 'utf8', $autoCommit = TRUE, $port = 3306, $persistent = FALSE) {
		$this->debugMode = $_debugMode;
		$this->error = $this->connect($host, $user, $pass, $dbname, $persistent ,$charSet, $autoCommit, $port);
	}
	public function dbManager($host, $user, $pass, $dbname = '', $_debugMode = TRUE, $charSet = 'utf8', $autoCommit = TRUE, $port = 3306, $persistent = FALSE) {
		$this->debugMode = $_debugMode;
		$this->connect($host, $user, $pass, $dbname, $persistent, $charSet, $autoCommit, $port);
	}
	
	public function connect($host, $user, $pass, $dbname, $persistent, $charSet, $autoCommit, $port) {
		if($persistent === true) {
			$host = 'p:' . $host;
		}
		$this->mysqli = new mysqli($host, $user, $pass, $dbname, $port);
		if ($this->mysqli->connect_error !== NULL) {
			if($this->debugMode){
				die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
			}else{
				return $mysqli->connect_error;
			}
		}
		$this->mysqli->autocommit($autoCommit);
		if(!$this->mysqli->set_charset($charSet)){
			$this->mysqli->set_charset('utf8');
		}
		return NULL;

	}
	public function select_db($dbname) {
		return $this->mysqli->select_db($dbname);
	}
	public function query($sql,$report_error = NULL,&$error_msg = '') {
		if($this->error !== NULL){
			if($this->debugMode){
				echo 'MySQL connection error!';
			}
			return FALSE;
		}
		$resultset = $this->mysqli->query($sql);
		if($resultset === FALSE) {
			if($report_error === NULL){
				$report_error = $this->debugMode;
			}
			if($report_error === TRUE){
				$err_msg = 'MySQL error: ' . $this->mysqli->error . "\n";
				$err_msg .= 'Query: ' . $sql;
				die($err_msg);
			}elseif($error_msg != ''){
				$error_msg = $this->mysqli->error;
				return FALSE;
			}else{
				return FALSE;
			}
			
		}
		return $resultset;
	}
	public function result($rs, $type = 'assoc') {
		if($this->error !== NULL){
			if($this->debugMode){
				echo 'MySQL connection error!';
			}
			return FALSE;
		}
		switch($type) {
			case 'assoc':
				$out_value = $rs->fetch_assoc();
				break;
			case 'array':
				$out_value = $rs->fetch_array();
				break;
			case 'row':
				$out_value = $rs->fetch_row();
				break;
			case 'field':
				$out_value = $rs->fetch_field();
				break;
			case 'num_rows_affected':
				$out_value = $this->mysqli->affected_rows;
				break;
			case 'num_fields':
				$out_value = $rs->field_count;
				break;
			case 'num_rows':
				$out_value = $rs->num_rows;
				break;
			default:
				$out_value = $rs->fetch_assoc();
				break;
		}
		if($out_value === NULL){
			$rs->close();
		}
		return $out_value;
	}
	public function escape_string($str){
		return $this->mysqli->real_escape_string($str);
	}
	public function insert_id(){
		return $this->mysqli->insert_id;
	}
	public function close() {
		return $this->mysqli->close();
	}
	public function version() {
		return $this->mysqli->server_info;
	}
	public function halt($message = '') {
		if($message == '')
			die('MySQL query error');
		else
			die($message);
	}
	public function free($rs) {
		return @$rs->close();
	}
	public function start_transaction(){
		$this->autocommit(FALSE);
	}
	public function autocommit($bool){
		$this->mysqli->autocommit($bool);
	}
	public function commit(){
		$this->mysqli->commit();
		$this->autocommit(TRUE);
	}
	public function rollback(){
		$this->mysqli->rollback();
	}
	public function stmt_prepare($psql){
		$stmt = $this->mysqli->stmt_init();
		if(!$stmt->prepare($psql)){
			return NULL;
		}
		return $stmt;
	}
}

?>