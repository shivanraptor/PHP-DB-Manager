<?php
/*
	dbManager for MSSQL v1.2
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
	v1.1
	- support PHP 5.3+ via sqlsrv
	v1.1.1
	- Bugfix of sqlsrv
	v1.1.2.
	- Improve Error Reporting
	v1.2
	- Support PDO with ODBC
	
	== Program History ==================
	ported from original MySQL dbManager
	
	Feel free to use, but kindly leave this statement here.
	
	Technical Support : findme@raptor.hk ( please specify "dbManager for MSSQL" in title )
*/

class dbManager {
	public $error = NULL;
	public $link;
	public $debugMode = TRUE;
	public $htmlError = TRUE;
	
	// For Transaction
	private $transaction_id;
    private $childTrans = array();
	
	// Settings
	private $driver = null;
	private $os = null;
	
	const DRIVER_MSSQL 	= 1;
	const DRIVER_SQLSRV = 2;
	const DRIVER_PDO	= 3;
	
	const OS_WINDOWS = 11;
	const OS_LINUX = 12;
	
	public function __construct($host, $user, $pass, $dbname = '', $port = 1433, $persistent = FALSE, $driver = self::DRIVER_MSSQL) {
		if($this->get_php_version() < 50300 && $driver == self::DRIVER_SQLSRV) {
			die('MSSQLSRV PHP Extension is not supported before PHP 5.3.');
		} elseif(!function_exists('mssql_connect') && $driver == self::DRIVER_MSSQL) {
			die('MSSQL PHP Extension is not installed.');
		} elseif(!function_exists('sqlsrv_connect') && $driver == self::DRIVER_SQLSRV) {
			die('MSSQLSRV PHP Extension is not installed.');
		} elseif(!defined('PDO::ATTR_DRIVER_NAME') && $driver == self::DRIVER_PDO) {
			die('PDO PHP Extension is not installed.');
		}
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->os = self::OS_WINDOWS;
		} else {
			$this->os = self::OS_LINUX;
		}
		$this->driver = $driver;
		$this->error = $this->connect($host, $user, $pass, $dbname, $port, $persistent);
		if($this->error !== null) {
			$this->halt($this->error);
		} else {
			// Required for Heterogeneous queries on older servers (e.g. SQL Server 2005)
			$this->query('SET ANSI_NULLS ON');
			$this->query('SET ANSI_WARNINGS ON');
		}
	}
	/*public function dbManager($host, $user, $pass, $dbname = '', $port = 1433, $persistent = TRUE, $driver = self::DRIVER_MSSQL) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->os = self::OS_WINDOWS;
		} else {
			$this->os = self::OS_LINUX;
		}
		$this->driver = $driver;
		$this->error = $this->connect($host, $user, $pass, $dbname, $port, $persistent);
		if($this->error !== null) {
			$this->halt($this->error);
		} else {
			// Required for Heterogeneous queries on older servers (e.g. SQL Server 2005)
			$this->query('SET ANSI_NULLS ON');
			$this->query('SET ANSI_WARNINGS ON');
		}
	}*/
	
	// return only error
	public function connect($host, $user, $pass, $dbname, $port, $persistent = TRUE) {
		$host_str = $host . ','. $port;
		if($this->os === self::OS_LINUX) {
			$host_str = $host . ':' . $port;
		}
		$extra_error = null;
		switch($this->driver) {
			case self::DRIVER_MSSQL:
				if($persistent) {
					$this->link = mssql_pconnect($host_str, $user, $pass);
				} else {
					$this->link = mssql_connect($host_str, $user, $pass);
				}
				if(!mssql_select_db($dbname, $this->link)) {
					$extra_error = 'Unable to select database';
					$this->link = false;
				}
				break;
			case self::DRIVER_SQLSRV:
				$connect_options = array(
					'UID' => $user,
					'PWD' => $pass,
					'Database' => $dbname,
                    'ReturnDatesAsStrings' => true,
                    'ConnectionPooling' => $persistent, 		// Connection Pool to replace Persistent Connection
                    'Encrypt' => false,							// Encryption is OFF by default
                    'TrustServerCertificate' => false, 			// Set to TRUE if self-signed certificate is used
				);
				$this->link = sqlsrv_connect($host_str, $connect_options);
				break;
			case self::DRIVER_PDO:
				try {
					if($this->os == self::OS_WINDOWS) {
						$this->link = new PDO("mssql:host=$host:$port;dbname=$dbname;charset=UTF-8", $user, $pass, array(
							PDO::ATTR_PERSISTENT => $persistent
						));
					} elseif($this->os == self::OS_LINUX) {
						$this->link = new PDO("dblib:host=$host:$port;dbname=$dbname", $user, $pass);
						// Persistent Connection is not available for DBLIB for Linux
						$this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					}
				} catch(PDOException $e) {
					$extra_error = $e->getMessage() . ' (Error Code: ' . $e->getCode() . ')';
					$this->link = null;
				}
				break;
		}
		
		if($this->link === null) { 
			$this->error = 'Error connecting to MSSQL: ';
			switch($this->driver) {
				case self::DRIVER_MSSQL:
					$this->error .= '{MSSQL Driver} ';
					if($extra_error != null) {
						$this->error .= $extra_error;
					} else {
						$this->error .= mssql_get_last_message();
					}
					break;
				case self::DRIVER_SQLSRV:
					$this->error .= '{SQLSRV Driver} ';
					$this->error .= var_export(sqlsrv_errors(), true);
					break;
				case self::DRIVER_PDO:
					$this->error .= '{PDO Driver} ';
					$this->error .= $extra_error;
					break;
			}
			$this->halt($this->error);
		}
	}
	
	public function query($sql, $report_error = NULL, &$error_msg = '') {
		if($this->error !== NULL) {
			echo $this->error . PHP_EOL;
			return FALSE;
		}
		if($this->link === null) {
			die('Query Error: Please make server connection first');
		}
		$resultset = null;
		switch($this->driver) {
			case self::DRIVER_MSSQL:
				$resultset = mssql_query($sql, $this->link);
				break;
			case self::DRIVER_SQLSRV:
				if(stripos($sql, 'UPDATE') !== FALSE || stripos($sql, 'INSERT') !== FALSE) {
					$resultset = sqlsrv_query($this->link, $sql, null, array());
				} else {
					$resultset = sqlsrv_query($this->link, $sql, null, array('Scrollable' => SQLSRV_CURSOR_KEYSET));
				}
				break;
			case self::DRIVER_PDO:
				 // WARN: Use Prepared Statement without Binding
				if(stripos($sql, 'UPDATE') !== FALSE || stripos($sql, 'INSERT') !== FALSE) {
					$stmt = $this->link->prepare($sql);
				} else {
					$stmt = $this->link->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
				}
				try {
					$resultset = $stmt->execute(); // PDO only returns boolean
					if($resultset === true) {
						$resultset = $stmt; // Copy PDOStatement into $resultset for further handling
					}
				} catch(PDOException $e) {
					die('PDO Execution Error: ' . $e->getMessage());
				}
				break;
		}
		if($resultset === null) {
			$err_msg = '';
			if($this->htmlError) {
				$err_msg = '<p>MSSQL error: </p>';
				if(is_array($this->error)) {
					$err_msg .= '<ul>' . PHP_EOL;
					foreach($this->error as $err) {
						$err_msg .= '<li>';
						if(is_array($err)) {
							$err_msg .= '<ul>';
							foreach($err as $e) {
								$err_msg .= '<li>' . $e . '</li>';
							}
							$err_msg .= '</ul>';
						}
						$err_msg .= '</li>' . PHP_EOL;
					}
					$err_msg .= '</ul>' . PHP_EOL;
				} else {
					$err_msg .= '<p>Error: ' . $this->error . '</p>' . PHP_EOL;
				}
				$err_msg .= '<p>Query Executed: ' . $sql . '</p>' . PHP_EOL;
			} else {
				$err_msg = 'MSSQL error: ' . $this->error . PHP_EOL;
				$err_msg .= 'Query: ' . $sql . PHP_EOL;
			}
			// When error occurred, Halt the process
			$this->halt($err_msg);
		} else {
			return $resultset;
		}
	}
	
	public function result($rs, $type = 'assoc') {
		if($this->error !== NULL) {
			switch($this->driver) {
				case self::DRIVER_MSSQL:
					echo 'MSSQL connection error! ' . mssql_get_last_message();
					break;
				case self::DRIVER_SQLSRV:
					echo 'MSSQL connection error! ' . PHP_EOL;
					print_r(sqlsrv_errors());
					break;
				case self::DRIVER_PDO:
					break;
			}
			return FALSE;
		}
		if($rs === null || $rs === false) {
			return FALSE;
		}
		switch($type) {
			case 'assoc':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_fetch_assoc($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->fetch(PDO::FETCH_ASSOC);
						break;
				}
				break;
			case 'array':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_fetch_array($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_fetch_array($rs);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->fetchAll();
						break;
				}
				break;
			case 'row':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_fetch_row($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_fetch_array($rs, SQLSRV_FETCH_NUMERIC);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->fetchAll();
						break;
				}
				break;
			case 'field':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_fetch_field($rs);
						break;
					case self::DRIVER_SQLSRV:
						$metadata = sqlsrv_field_metadata($rs);
						$out_value = $metadata['Name'];
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->fetchAll();
						break;
				}
				break;
			case 'num_rows_affected':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_rows_affected($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_rows_affected($rs);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->rowCount();
						break;
				}
				break;
			case 'num_fields':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_num_fields($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_num_fields($rs);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->columnCount();
						break;
				}
				break;
			case 'num_rows':
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_num_rows($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_num_rows($rs);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->rowCount();  // PDO may not have accurate rowCount() for certain drivers
						break;
				}
				break;
			default:
				switch($this->driver) {
					case self::DRIVER_MSSQL:
						$out_value = mssql_fetch_assoc($rs);
						break;
					case self::DRIVER_SQLSRV:
						$out_value = sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC);
						break;
					case self::DRIVER_PDO:
						$out_value = $rs->fetchAll();
						break;
				}
				break;
		}
		if($out_value === NULL){
			switch($this->driver) {
				case self::DRIVER_MSSQL:
					mssql_close();
					break;
				case self::DRIVER_SQLSRV:
					//sqlsrv_close($this->link);
					break;
				case self::DRIVER_PDO:
					$rs->closeCursor();
					break;
			}
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
	
	public function insert_id() {
		$sql = "SELECT SCOPE_IDENTITY() AS id_col"; // WARNING: Watch out for Parallelism Trap before SQL Server 2008 R1
		$rs = $this->query($sql);
		$row = $this->result($rs);
		return $row['id_col'];
	}
	
	public function close() {
		switch($this->driver) {
			case self::DRIVER_MSSQL:
				return mssql_close($this->link);
				break;
			case self::DRIVER_SQLSRV:
				return sqlsrv_close($this->link);
				break;
			case self::DRIVER_PDO:
				$this->link = null; // Well, PDO uses Connection = Null to close it. Bad Pattern.
				break;
		}
	}
	
	public function version() {
		switch($this->driver) {
			case self::DRIVER_MSSQL:
				return 'Not Implemented';
			case self::DRIVER_SQLSRV:
				$info = sqlsrv_server_info($this->link);
				return 'Server: ' . $info['SQLServerName'] . ' (Version: ' . $info['SQLServerVersion'] . ')';
			case self::DRIVER_PDO:
				break;
		}
		return 'Not Implemented';
	}
	
	public function halt($message = '') {
		if($message == '') {
			die('MSSQL query error');
		} else {
			die($message);
		}
	}
	
	public function free($rs) {
		switch($this->driver) {
			case self::DRIVER_MSSQL:
				return @mssql_free_result($rs);
				break;
			case self::DRIVER_SQLSRV:
				break;
			case self::DRIVER_PDO:
				break;
		}
	}
	
	private function get_php_version() {
		if (!defined('PHP_VERSION_ID')) {
			$version = explode('.', PHP_VERSION);
			define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
		}
		return PHP_VERSION_ID;
	}
	
	
	// Support Transaction (PDO only)
	public function begin_transaction() {
		if($this->driver !== self::DRIVER_PDO) {
			return false;
		}
        $alphanum = "AaBbCc0Dd1EeF2fG3gH4hI5iJ6jK7kLlM8mN9nOoPpQqRrSsTtUuVvWwXxYyZz";
        $this->transaction_id = 'T' . substr(str_shuffle($alphanum), 0, 7);

        array_unshift($this->childTrans, $this->transaction_id);

        $stmt = $this->link->prepare("BEGIN TRAN [$this->transaction_id];");
        return $stmt->execute();
    }
    
    public function rollback() {
    	if($this->driver !== self::DRIVER_PDO) {
			return false;
		}
        while(count($this->childTrans) > 0) {
            $tmp = array_shift($this->childTrans);
            $stmt = $this->link->prepare("ROLLBACK TRAN [$tmp];");
            $stmt->execute();
        }
        return $stmt;
    }
    
    public function commit() {
    	if($this->driver !== self::DRIVER_PDO) {
			return false;
		}
        while(count($this->childTrans) > 0) {
            $tmp = array_shift($this->childTrans);
            $stmt = $this->link->prepare("COMMIT TRAN [$tmp];");
            $stmt->execute();
        }
        return $stmt;
    }
    
    // Static functions below
    public static function SelfTest() {
    	// MSSQL PHP Extension Probe
    	echo '===========================' . PHP_EOL;
    	echo 'MSSQL PHP Extension Probing' . PHP_EOL;
    	echo '===========================' . PHP_EOL;
    	
    	echo 'MSSQL PHP Extension is ';
    	if(!function_exists('mssql_connect')) {
			echo 'NOT installed. ' . PHP_EOL;
		} else {
			echo 'installed. ' . PHP_EOL;
		}
		
		// SQLSRV PHP Extension Probe
		echo 'MSSQLSRV PHP Extension is ';
		if(!function_exists('sqlsrv_connect')) {
			echo 'NOT installed. ' . PHP_EOL;
		} else {
			echo 'installed. ' . PHP_EOL;
		}
		
		// PDO PHP Extension Probe
		echo 'PDO PHP Extension is ';
		if(!defined('PDO::ATTR_DRIVER_NAME')) {
			echo 'NOT installed. ' . PHP_EOL;
		} else {
			echo 'installed. Drivers are as below: ' . PHP_EOL;
			$drivers = PDO::getAvailableDrivers();
			print_r($drivers);
			echo PHP_EOL;
			
			$driver_available = false;
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				if(in_array('mssql', $drivers)) {
					echo 'MSSQL PDO Driver is available.' . PHP_EOL;
					$driver_available = true;
				}
			} else {
				if(in_array('dblib', $drivers)) { 
					echo 'DBLIB PDO Driver is available.' . PHP_EOL;
					$driver_available = true;
				}
			}
			if(!$driver_available) {
				echo 'No PDO Driver is suitable to use.' . PHP_EOL;
			}
		}
    }
}
?>