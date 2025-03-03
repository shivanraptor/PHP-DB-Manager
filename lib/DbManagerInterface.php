<?php
declare(strict_types=1);

namespace PhpDbManager;

use mysqli_result;

/**
 * Interface for database managers
 */
interface DbManagerInterface
{
    /**
     * Executes a prepared statement
     * @param string $sql SQL query with placeholders
     * @param array $params Array of parameters ['type' => value]
     * @return mysqli_result|bool
     */
    public function execute(string $sql, array $params = []): mysqli_result|bool;

    /**
     * Fetches a single row
     * @param mysqli_result $result
     * @param string $type Fetch type (assoc|array|object)
     * @return array|object|null
     */
    public function fetch(mysqli_result $result, string $type = 'assoc'): array|object|null;

    /**
     * Fetches all rows
     * @param mysqli_result $result
     * @param string $type Fetch type
     * @return array
     */
    public function fetchAll(mysqli_result $result, string $type = 'assoc'): array;

    /**
     * Begins a transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commits a transaction
     */
    public function commit(): bool;

    /**
     * Rolls back a transaction
     */
    public function rollback(): bool;

    /**
     * Gets the ID of the last inserted row
     */
    public function lastInsertId(): int;

    /**
     * Gets the number of affected rows
     */
    public function affectedRows(): int;

    /**
     * Gets the number of executed queries
     */
    public function getQueryCount(): int;

    /**
     * Gets connection info
     */
    public function getConnectionInfo(): array;

    /**
     * Closes the connection
     */
    public function close(): void;
} 