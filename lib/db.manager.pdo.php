<?php
class dbManagerPDO {
	private $pdo;
	
	const DRIVER_MYSQL = 'mysql:host=';
	const DRIVER_MSSQL_SQLSRV = 'sqlsrv:server=';
	const DRIVER_MSSQL = 'mssql:host=';
	
	const DB_MSSQL = 'MSSQL';
	const DB_MYSQL = 'MYSQL';
	
	public function __construct($host, $user, $pass, $dbname = '', $_debugMode = TRUE, $charSet = 'utf8', $autoCommit = TRUE, $port = 3306, $persistent = FALSE, $db_engine = self::DB_MYSQL) {
		if($this->requirement_check() === TRUE) {
			$dsn = '';
			try {
				switch($db_engine) {
					case self::DB_MYSQL:
						$dsn = self::DRIVER_MYSQL . $host . ';dbname=' . $dbname;
						$this->pdo = new PDO($dsn, $user, $pass);
						break;
					case self::DB_MSSQL:
						if($this->get_php_version() < 50300) {
							$dsn = self::DRIVER_MSSQL . $host . ';Database=' . $dbname;
							$this->pdo = new PDO($dsn, $user, $pass);
						} else {
							$dsn = self::DRIVER_MSSQL_SQLSRV . $host . ';Database=' . $dbname;
							$this->pdo = new PDO($dsn, $user, $pass);
							$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
							$this->pdo->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1);
						}
						break;
				}
			} catch (PDOException $e) {
				$this->halt($e->getMessage());
			}
		}
	}

	public function query($sql, $report_error = NULL, &$error_msg = '') {
		$pdo_stmt = $this->pdo->query($sql);
		return $pdo_stmt;
	}
	
	public function result($rs, $type = 'assoc') {
		switch($type) {
			case 'assoc':
				$out_value = $rs->fetch(PDO::FETCH_ASSOC);
				break;
			/*case 'array':
				$out_value = $rs->fetch_array();
				break;
			case 'row':
				$out_value = $rs->fetch_row();
				break;*/
			// TODO: Support PDO::FETCH_COLUMN , PDO::FETCH_CLASS , PDO::FETCH_INTO, PDO::FETCH_BOTH , PDO::FETCH_BOUND, PDO::FETCH_LAZY, PDO::FETCH_NUM, PDO::FETCH_OBJ
			case 'object':
				$out_value = $rs->fetch(PDO::FETCH_OBJ);
				break;
			/*case 'field':
				$out_value = $rs->fetch_field();
				break;*/
			case 'num_rows_affected':
				$out_value = (int)$rs->rowCount();
				break;
			/*case 'num_fields':
				$out_value = (int)$rs->field_count;
				break;*/
			case 'num_rows':
				$out_value = count($rs->fetchAll());
				break;
			default:
				$out_value = $rs->fetch(PDO::FETCH_ASSOC);
				break;
		}
		return $out_value;
	}
	// ================
	// Helper Functions
	// ================
	private function get_php_version() {
		if (!defined('PHP_VERSION_ID')) {
			$version = explode('.', PHP_VERSION);
			define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
		}
		return PHP_VERSION_ID;
	}
	private function requirement_check() {
		if (!defined('PDO::ATTR_DRIVER_NAME')) {
			$this->halt('PDO Support is unavailable');
		}
		return true;
	}
	private function halt($message = '') {
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