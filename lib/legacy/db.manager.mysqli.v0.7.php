<?php

/*
  dbManager for Mysqli
  Usage:
  backward compatible version:
  $db = new dbManager(db_host, db_user, db_pass, db_schm);
  Support of charaset, disable debug message
  $db = new dbManager(db_host, db_user, db_pass, db_schm, FALSE, 'utf8');
  To check connection error:
  no error iff($db->error === NULL)

  $db->escape_string($str); //to escape string
  $db->mysqli to directly use the mysqli function


  History:
  v0.7 by hoyu
  - minor error handling correction
  v0.6 by hoyu
  - minor modification
  v0.5 by hoyu
  - added error handling
  v0.4 by hoyu
  - added prepared statement (a little bit only)
  v0.3 by hoyu
  - added transaction:	$db->start_transaction();$db->commit();$db->rollback();
  v0.2 by hoyu
  - trial

  original dbManager for Mysql by raptor
 */

class dbManager {

	public $error = NULL;
	public $debugMode = TRUE;
	public $mysqli;

	public function __construct($host, $user, $pass, $dbname = '', $_debugMode = TRUE, $charSet = 'utf8',
			$autoCommit = TRUE) {
		// default with non-persistent link
		$this->debugMode = $_debugMode;
		$this->error = $this->connect($host, $user, $pass, $dbname, 0, $charSet, $autoCommit);
	}

	public function dbManager($host, $user, $pass, $dbname = '', $_debugMode = TRUE, $charSet = 'utf8', $autoCommit = TRUE) {
		// default with non-persistent link
		$this->debugMode = $_debugMode;
		$this->connect($host, $user, $pass, $dbname, 0, $charSet, $autoCommit);
	}

	public function connect($host, $user, $pass, $dbname, $persistent, $charSet, $autoCommit) {
		//actually, no persistent connection here
		$this->mysqli = new mysqli($host, $user, $pass, $dbname);
		if ($this->mysqli->connect_error !== NULL) {
			if ($this->debugMode) {
				die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
			} else {
				return $mysqli->connect_error;
			}
		}
		$this->mysqli->autocommit($autoCommit);
		if (!$this->mysqli->set_charset($charSet)) {
			$this->mysqli->set_charset('utf8');
		}
		return NULL;
	}

	public function select_db($dbname) {
		return $this->mysqli->select_db($dbname);
	}

	public function query($sql, $report_error = NULL, &$error_msg = '') {
		if ($this->error !== NULL) {
			if ($this->debugMode) {
				echo 'MySQL connection error!';
			}
			return FALSE;
		}
		$resultset = $this->mysqli->query($sql);
		if ($resultset === FALSE) {
			if ($report_error === NULL) {
				$report_error = $this->debugMode;
			}
			if ($report_error === TRUE) {
				$err_msg = 'MySQL error: ' . $this->mysqli->error . "\n";
				$err_msg .= 'Query: ' . $sql;
				SystemLogger::error($err_msg);
				echo $err_msg;
				//die($err_msg);
			} elseif ($error_msg != '') {
				$error_msg = $this->mysqli->error;
				SystemLogger::error($err_msg);
				return FALSE;
			} else {
				return FALSE;
			}
		}
		return $resultset;
	}

	public function result($rs, $type = 'assoc') {
		if ($this->error !== NULL) {
			if ($this->debugMode) {
				echo 'MySQL connection error!';
			}
			return FALSE;
		}
		switch ($type) {
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
		if ($out_value === NULL) {
			$rs->close();
		}
		return $out_value;
	}

	public function escape_string($str) {
		return $this->mysqli->real_escape_string($str);
	}

	public function insert_id() {
		return $this->mysqli->insert_id;
	}

	public function close() {
		return $this->mysqli->close();
	}

	public function version() {
		return $this->mysqli->server_info;
	}

	public function halt($message = '') {
		if ($message == '')
			die('MySQL query error');
		else
			die($message);
	}

	public function free($rs) {
		return @$rs->close();
	}

	public function start_transaction() {
		$this->autocommit(FALSE);
	}

	public function autocommit($bool) {
		$this->mysqli->autocommit($bool);
	}

	public function commit() {
		$this->mysqli->commit();
		$this->autocommit(TRUE);
	}

	public function rollback() {
		$this->mysqli->rollback();
	}

	public function stmt_prepare($psql) {
		$stmt = $this->mysqli->stmt_init();
		if (!$stmt->prepare($psql)) {
			return NULL;
		}
		return $stmt;
	}

}

?>
