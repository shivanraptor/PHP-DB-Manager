<?php
declare(strict_types=1);

namespace PhpDbManager;

use mysqli;
use mysqli_stmt;
use mysqli_result;
use RuntimeException;
use InvalidArgumentException;

/**
 * Modern PHP Database Manager for MySQLi
 * 
 * @package PhpDbManager
 * @version 2.0.0
 */
class DbManager implements DbManagerInterface
{
    private const DEFAULT_PORT = 3306;
    private const DEFAULT_CHARSET = 'utf8mb4';
    
    private ?mysqli $connection = null;
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
                $host = $this->options['persistent'] ? "p:{$this->options['host']}" : $this->options['host'];
                
                $this->connection = new mysqli(
                    $host,
                    $this->options['username'],
                    $this->options['password'],
                    $this->options['database'],
                    $this->options['port']
                );

                if ($this->connection->connect_error) {
                    throw new RuntimeException($this->connection->connect_error);
                }

                $this->connection->set_charset($this->options['charset']);
                $this->connection->autocommit($this->options['autocommit']);
                
                // Store connection info
                $this->connectedServer = "{$this->options['host']}:{$this->options['port']}";
                $this->connectedUser = $this->options['username'];
                
                return;
            } catch (RuntimeException $e) {
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
     * Executes a prepared statement
     * @param string $sql SQL query with placeholders
     * @param array $params Array of parameters ['type' => value]
     * @return mysqli_result|bool
     * @throws RuntimeException
     */
    public function execute(string $sql, array $params = []): mysqli_result|bool
    {
        $stmt = $this->prepare($sql);
        
        if (!empty($params)) {
            $types = '';
            $values = [];
            
            foreach ($params as $type => $value) {
                $types .= $type;
                $values[] = $value;
            }
            
            $stmt->bind_param($types, ...$values);
        }
        
        if (!$stmt->execute()) {
            throw new RuntimeException("Execute failed: " . $stmt->error);
        }
        
        $this->queryCount++;
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }

    /**
     * Fetches a single row
     * @param mysqli_result $result
     * @param string $type Fetch type (assoc|array|object)
     * @return array|object|null
     */
    public function fetch(mysqli_result $result, string $type = 'assoc'): array|object|null
    {
        return match($type) {
            'assoc' => $result->fetch_assoc(),
            'array' => $result->fetch_array(),
            'object' => $result->fetch_object(),
            default => $result->fetch_assoc()
        };
    }

    /**
     * Fetches all rows
     * @param mysqli_result $result
     * @param string $type Fetch type
     * @return array
     */
    public function fetchAll(mysqli_result $result, string $type = 'assoc'): array
    {
        $rows = [];
        while ($row = $this->fetch($result, $type)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Begins a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->begin_transaction();
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
        return $this->connection->rollback();
    }

    /**
     * Gets the ID of the last inserted row
     */
    public function lastInsertId(): int
    {
        return $this->connection->insert_id;
    }

    /**
     * Gets the number of affected rows
     */
    public function affectedRows(): int
    {
        return $this->connection->affected_rows;
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
            'version' => $this->connection->server_info,
            'charset' => $this->connection->charset
        ];
    }

    /**
     * Closes the connection
     */
    public function close(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Prepares an SQL statement
     * @throws RuntimeException
     */
    private function prepare(string $sql): mysqli_stmt
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Prepare failed: " . $this->connection->error);
        }
        return $stmt;
    }

    /**
     * Destructor ensures connection is closed
     */
    public function __destruct()
    {
        $this->close();
    }
}
