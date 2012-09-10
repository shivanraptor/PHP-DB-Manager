<?php
/*
	dbManager for MSSQL v1.0
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
	   
	Parameters of constructor:
	host 		: Host of MSSQL server , e.g. SQLEXPRESS\\INSTANCE123 or 192.168.1.123 
	user 		: Username
	pass		: Password
	_debugMode	: Debug mode ( set TRUE to enable , set FALSE to disable )
	port		: Server Port of MSSQL server ( defaults to 1433 , standard port of MSSQL server )
	persistent	: Persistent Connection mode ( set TRUE to enable , set FALSE to disable )
	
	
	== Version History ==================
	v1.0
	- initial release
	
	== Program History ==================
	ported from original MySQL dbManager
	
	Feel free to use, but kindly leave this statement here.
	
	Technical Support : findme@raptor.hk ( please specify "dbManager for MSSQL" in title )
*/

class dbManager {
	public $error = NULL;
	public $link;
	public $debugMode = TRUE;
	
	public function __construct($host, $user, $pass, $dbname = '', $port = 1433, $persistent = FALSE, $os = 'OS_WINDOW') {
		if(!function_exists('mssql_connect')) {
			die('MSSQL PHP Extension is not installed.');
		}
		$this->error = $this->connect($host, $user, $pass, $dbname, $port, $persistent);
		if($this->error !== null) {
			$this->halt($this->error);
		}
	}
	public function dbManager($host, $user, $pass, $dbname = '', $port = 1433, $persistent = FALSE, $os = 'OS_WINDOW') {
		$this->connect($host, $user, $pass, $dbname, $port, $persistent);
	}
	
	// return only error
	public function connect($host, $user, $pass, $dbname, $port, $persistent = FALSE, $os = 'OS_WINDOW') {
		$host_str = $host . ','. $port;
		if($os === 'OS_LINUX') {
			$host_str = $host . ':' . $port;
		}
		if($persistent) {
			$this->link = mssql_pconnect($host_str, $user, $pass);
		} else {
			$this->link = mssql_connect($host_str, $user, $pass);
		}
		if($this->link === FALSE) {
			$this->error = 'Error connecting to MSSQL: ' . mssql_get_last_message();
		}
		if(!$this->select_db($dbname)) {
			$this->error = 'Unable to select database';
		}
		return $this->error;
	}
	public function select_db($dbname) {
		return mssql_select_db($dbname, $this->link);
	}
	public function query($sql, $report_error = NULL, &$error_msg = '') {
		if($this->error !== NULL){
			if($this->debugMode) {
				echo 'MSSQL error' . PHP_EOL;
			}
			return FALSE;
		}
		$resultset = mssql_query($sql, $this->link);
		if($resultset === FALSE) {
			if($report_error === NULL) {
				$report_error = $this->debugMode;
			}
			if($report_error === TRUE) {
				$err_msg = 'MSSQL error: ' . $this->error . PHP_EOL;
				$err_msg .= 'Query: ' . $sql . PHP_EOL;
				die($err_msg);
			} elseif($error_msg != '') {
				$error_msg = $this->error;
				return FALSE;
			} else {
				return FALSE;
			}
		}
		return $resultset;
	}
	public function result($rs, $type = 'assoc') {
		if($this->error !== NULL){
			if($this->debugMode){
				echo 'MSSQL connection error! ' . mssql_get_last_message();
			}
			return FALSE;
		}
		switch($type) {
			case 'assoc':
				$out_value = mssql_fetch_assoc($rs);
				break;
			case 'array':
				$out_value = mssql_fetch_array($rs);
				break;
			case 'row':
				$out_value = mssql_fetch_row($rs);
				break;
			case 'field':
				$out_value = mssql_fetch_field($rs);
				break;
			case 'num_rows_affected':
				$out_value = mssql_rows_affected($rs);
				break;
			case 'num_fields':
				$out_value = mssql_num_fields($rs);
				break;
			case 'num_rows':
				$out_value = mssql_num_rows($rs);
				break;
			default:
				$out_value = mssql_fetch_assoc($rs);
				break;
		}
		if($out_value === NULL){
			mssql_close();
		}
		return $out_value;
	}
	public function escape_string($data){
		// ref: http://stackoverflow.com/questions/574805/how-to-escape-strings-in-mssql-using-php
		if ( !isset($data) or empty($data) ) {
			return '';
		}
        if ( is_numeric($data) ) {
        	return $data;
        }
        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ( $non_displayables as $regex ) {
            $data = preg_replace( $regex, '', $data );
        }
        $data = str_replace("'", "''", $data );
        return $data;
	}
	public function insert_id(){
		$sql = "SELECT SCOPE_IDENTITY()";
		$rs = $this->query($sql, $this->link);
		$row = $this->result($rs, 'array');
		return $row[0];
	}
	public function close() {
		return mssql_close($this->link);
	}
	public function version() {
		return 'Not Implemented'; //$this->mysqli->server_info;
	}
	public function halt($message = '') {
		if($message == '')
			die('MSSQL query error');
		else
			die($message);
	}
	public function free($rs) {
		return @mssql_free_result($rs);
	}
}

?>