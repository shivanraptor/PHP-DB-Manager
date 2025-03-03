<?php
declare(strict_types=1);

namespace PhpDbManager;

use PDO;
use PDOStatement;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

/**
 * Modern PHP Database Manager for PDO
 * 
 * @package PhpDbManager
 * @version 2.0.0
 */
class DbManagerPDO implements DbManagerInterface
{
	private const DRIVER_MYSQL = 'mysql:host=%s;dbname=%s;charset=%s';
	private const DRIVER_MSSQL_SQLSRV = 'sqlsrv:server=%s;Database=%s';
	private const DRIVER_MSSQL = 'mssql:host=%s;Database=%s';
	
	private const DB_MSSQL = 'MSSQL';
	private const DB_MYSQL = 'MYSQL';
	
	private const DEFAULT_PORT = 3306;
	private const DEFAULT_CHARSET = 'utf8mb4';
	
	private ?PDO $connection = null;
	private string $connectedServer;
	private string $connectedUser;
	private int $queryCount = 0;
	private array $options;

	/**
	 * @param array $options Connection options
	 * @throws RuntimeException If connection fails
	 */
	public function __construct(array $options)
	{
		$this->options = array_merge([
			'host' => 'localhost',
			'port' => self::DEFAULT_PORT,
			'charset' => self::DEFAULT_CHARSET,
			'persistent' => false,
			'autocommit' => true,
			'timeout' => 30,
			'retry_attempts' => 3,
			'retry_delay' => 100, // milliseconds
			'engine' => self::DB_MYSQL,
			'options' => [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES => false,
			]
		], $options);

		$this->validateOptions();
		$this->connect();
	}

	/**
	 * Validates connection options
	 * @throws InvalidArgumentException
	 */
	private function validateOptions(): void
	{
		$required = ['host', 'username', 'password', 'database'];
		foreach ($required as $field) {
			if (empty($this->options[$field])) {
				throw new InvalidArgumentException("Missing required option: {$field}");
			}
		}
	}

	/**
	 * Establishes database connection with retry mechanism
	 * @throws RuntimeException
	 */
	private function connect(): void 
	{
		$attempts = 0;
		$lastError = null;

		do {
			try {
				$dsn = $this->buildDsn();
				
				$this->connection = new PDO(
					$dsn,
					$this->options['username'],
					$this->options['password'],
					$this->options['options']
				);
				
				// Store connection info
				$this->connectedServer = "{$this->options['host']}:{$this->options['port']}";
				$this->connectedUser = $this->options['username'];
				
				return;
			} catch (PDOException $e) {
				$lastError = $e;
				$attempts++;
				if ($attempts < $this->options['retry_attempts']) {
					usleep($this->options['retry_delay'] * 1000);
				}
			}
		} while ($attempts < $this->options['retry_attempts']);

		throw new RuntimeException(
			"Failed to connect after {$attempts} attempts. Last error: " . $lastError->getMessage()
		);
	}

	/**
	 * Builds the DSN string based on the database engine
	 */
	private function buildDsn(): string
	{
		$host = $this->options['host'];
		$dbname = $this->options['database'];
		$charset = $this->options['charset'];

		return match($this->options['engine']) {
			self::DB_MSSQL => sprintf(
				$this->getPhpVersion() < 50300 ? self::DRIVER_MSSQL : self::DRIVER_MSSQL_SQLSRV,
				$host,
				$dbname
			),
			default => sprintf(
				self::DRIVER_MYSQL,
				$host,
				$dbname,
				$charset
			)
		};
	}

	/**
	 * Executes a prepared statement
	 * @param string $sql SQL query with placeholders
	 * @param array $params Array of parameters
	 * @return PDOStatement|bool
	 * @throws RuntimeException
	 */
	public function execute(string $sql, array $params = []): PDOStatement|bool
	{
		try {
			$stmt = $this->prepare($sql);
			
			if (!empty($params)) {
				$stmt->execute($params);
			} else {
				$stmt->execute();
			}
			
			$this->queryCount++;
			return $stmt;
		} catch (PDOException $e) {
			throw new RuntimeException("Execute failed: " . $e->getMessage());
		}
	}

	/**
	 * Fetches a single row
	 * @param PDOStatement $result
	 * @param string $type Fetch type (assoc|array|object)
	 * @return array|object|null
	 */
	public function fetch(PDOStatement $result, string $type = 'assoc'): array|object|null
	{
		return match($type) {
			'assoc' => $result->fetch(PDO::FETCH_ASSOC),
			'array' => $result->fetch(PDO::FETCH_NUM),
			'object' => $result->fetch(PDO::FETCH_OBJ),
			default => $result->fetch(PDO::FETCH_ASSOC)
		};
	}

	/**
	 * Fetches all rows
	 * @param PDOStatement $result
	 * @param string $type Fetch type
	 * @return array
	 */
	public function fetchAll(PDOStatement $result, string $type = 'assoc'): array
	{
		return match($type) {
			'assoc' => $result->fetchAll(PDO::FETCH_ASSOC),
			'array' => $result->fetchAll(PDO::FETCH_NUM),
			'object' => $result->fetchAll(PDO::FETCH_OBJ),
			default => $result->fetchAll(PDO::FETCH_ASSOC)
		};
	}

	/**
	 * Begins a transaction
	 */
	public function beginTransaction(): bool
	{
		return $this->connection->beginTransaction();
	}

	/**
	 * Commits a transaction
	 */
	public function commit(): bool
	{
		return $this->connection->commit();
	}

	/**
	 * Rolls back a transaction
	 */
	public function rollback(): bool
	{
		return $this->connection->rollBack();
	}

	/**
	 * Gets the ID of the last inserted row
	 */
	public function lastInsertId(): int
	{
		return (int)$this->connection->lastInsertId();
	}

	/**
	 * Gets the number of affected rows
	 */
	public function affectedRows(): int
	{
		return (int)$this->connection->rowCount();
	}

	/**
	 * Gets the number of executed queries
	 */
	public function getQueryCount(): int
	{
		return $this->queryCount;
	}

	/**
	 * Gets connection info
	 */
	public function getConnectionInfo(): array
	{
		return [
			'server' => $this->connectedServer,
			'user' => $this->connectedUser,
			'version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
			'charset' => $this->options['charset']
		];
	}

	/**
	 * Closes the connection
	 */
	public function close(): void
	{
		$this->connection = null;
	}

	/**
	 * Prepares an SQL statement
	 * @throws RuntimeException
	 */
	private function prepare(string $sql): PDOStatement
	{
		try {
			return $this->connection->prepare($sql);
		} catch (PDOException $e) {
			throw new RuntimeException("Prepare failed: " . $e->getMessage());
		}
	}

	/**
	 * Gets PHP version ID
	 */
	private function getPhpVersion(): int
	{
		if (!defined('PHP_VERSION_ID')) {
			$version = explode('.', PHP_VERSION);
			define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
		}
		return PHP_VERSION_ID;
	}

	/**
	 * Destructor ensures connection is closed
	 */
	public function __destruct()
	{
		$this->close();
	}
}
?>