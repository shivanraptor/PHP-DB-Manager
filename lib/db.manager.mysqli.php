<?php
/*
	dbManager for Mysqli v1.6
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
	6. Prepared Statement
	   $sql = "SELECT field_name1, field_name2 FROM table_name WHERE id = ?"; 	// cannot use "SELECT *"
	   $params = array('i' => 1); 												// i = integer , d = double , s = string , b = blob
	   $result = $db->query_prepare($sql, $params);
	   if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
		   $row = $db->result($result);
		   echo $row['field_name1'];
	   } else {
		   foreach($result as $row) {
		   	   echo $row['field_name1'] . ' ' .$row['field_name2'];
		   }
	   }

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
	v1.2
	- add fetch_object() support
	v1.3
	- add function query_prepare($sql, $params);
	v1.4
	- add query counter ( public variable : $query_count )
	- add current login user & server info ( see function : get_connection_info() )
	v1.5
	- add shortcut function combining $db->query & $db->result
	- centralize error message processing
	- error message respecting Content-Type
	v1.6 ( Coming Soon )
	- add PDO support

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
	public $query_count = 0;
	private $connected_server;
	private $connected_user;
	private $library_name = 'PHP DB Manager';

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
				$this->halt('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
			}else{
				return $mysqli->connect_error;
			}
		}
		// added in v1.4 : connected server & user info
		$this->connected_server = $host . ':' . $port;
		$this->connected_user = $user;
		$this->mysqli->autocommit($autoCommit);
		if(!$this->mysqli->set_charset($charSet)){
			$this->mysqli->set_charset('utf8');
		}
		return NULL;
	}
	public function select_db($dbname) {
		return $this->mysqli->select_db($dbname);
	}
	public function query_prepare($sql, $params, $report_error = NULL, &$error_msg = '') {
		if($this->error !== NULL){
			if($this->debugMode){
				$this->halt('MySQL connection error!');
			}
			return FALSE;
		}
		$stmt = $this->mysqli->prepare($sql);
		if($stmt !== FALSE) {
			$values = array();
			$types = '';
			foreach($params as $k => $v) {
				$values[] = $v;
				$types .= $k;
			}
			call_user_func_array(array($stmt, 'bind_param'), array_merge(array($types), $values));
			$stmt->execute();

			if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
				return $stmt->get_result();
			} else {
				$fields = array();
				$results = array();
				$stmt->store_result();
				$meta = $stmt->result_metadata();
				while ($field = $meta->fetch_field()) {
					$var = $field->name;
					$$var = null;
					$fields[$var] = &$$var;
				}
				call_user_func_array(array($stmt,'bind_result'),$fields);
				$i = 0;
		        while ($stmt->fetch()) {
		            $results[$i] = array();
		            foreach($fields as $k => $v)
		                $results[$i][$k] = $v;
		            $i++;
		        }

		        // close statement
		        $stmt->close();
		        return $results;
			}
		} else {
			if($report_error === NULL) {
				$report_error = $this->debugMode;
			}

			if($report_error === TRUE) {
				$err_msg  = 'MySQL error: ' . $this->mysqli->error . "\n";
				$err_msg .= 'Query: ' . $sql;
				$this->halt($err_msg);
			} elseif($error_msg != '') {
				$error_msg = $this->mysqli->error;
				return FALSE;
			} else {
				return FALSE;
			}
		}
	}

	public function query($sql, $report_error = NULL, &$error_msg = '') {
		if($this->error !== NULL){
			if($this->debugMode){
				$this->halt('MySQL connection error!');
			}
			return FALSE;
		}
		$resultset = $this->mysqli->query($sql);
		$this->query_count++;
		if($resultset === FALSE) {
			if($report_error === NULL){
				$report_error = $this->debugMode;
			}
			if($report_error === TRUE){
				$err_msg = 'MySQL error: ' . $this->mysqli->error . "\n";
				$err_msg .= 'Query: ' . $sql;
				$this->halt($err_msg);
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
				$this->halt('MySQL connection error!');
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
			case 'object':
				$out_value = $rs->fetch_object();
				break;
			case 'field':
				$out_value = $rs->fetch_field();
				break;
			case 'num_rows_affected':
				$out_value = (int)$this->mysqli->affected_rows;
				break;
			case 'num_fields':
				$out_value = (int)$rs->field_count;
				break;
			case 'num_rows':
				$out_value = (int)$rs->num_rows;
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
	// introduced in v1.5
	public function rs($sql, $extended_info = false) {
		if($extended_info === TRUE) {
			$start_time = microtime(true);
			$result = array();
			$rs = $this->query($sql);
			$result_array = array();
			while($row = $this->result($rs)) {
				$result_array[] = $row;
			}
			$result['rs'] = $result_array;
			$result['num_rows'] = $this->result($rs, 'num_rows');
			$end_time = microtime(true);
			$result['exec_time'] = ($end_time - $start_time);
			return $result;
		} else {
			$rs = $this->query($sql);
			$result = array();
			while($row = $this->result($rs)) {
				$result[] = $row;
			}
			return $result;
		}
	}

	public function escape_string($str){
		return $this->mysqli->real_escape_string($str);
	}
	public function insert_id(){
		return (int)$this->mysqli->insert_id;
	}
	public function close() {
		return $this->mysqli->close();
	}
	public function version() {
		return $this->mysqli->server_info;
	}
	public function halt($message = '') {
		$content_type = $this->get_content_type();
		if($message == '') {
			$message = '[' . $this->library_name . '] Error: MySQL Query Error';
		} else {
			if($content_type === 'text/html') {
				$message = str_replace("\n", '<br />', $message);
				$message = str_replace(PHP_EOL, '<br />', $message);
			}
			$message = '[' . $this->library_name . '] ' . $message;
		}
		if($content_type === 'text/html') {
			die('<p style="font-weight: bold; color: #F00; font-family: Arial; font-size: 11px;">' . $message . '</p>');
		} else {
			die($message);
		}
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
	public function get_connection_info() {
		return array('server' => $this->connected_server , 'user' => $this->connected_user);
	}
	// =======================
	// Helper Functions
	// =======================
	private function get_content_type() {
		$headers = headers_list();
		foreach($headers as $index => $value) {
			list($key, $value) = explode(': ', $value);
			unset($headers[$index]);
			$headers[$key] = $value;
		}
		if(isset($headers['Content-Type'])) {
			return $headers['Content-Type'];
		} else {
			return 'text/html';
		}
	}
}

?>
