# PHP-DB-Manager

[![LoC](https://tokei.rs/b1/github/shivanraptor/php-db-manager?category=code)](https://tokei.rs/b1/github/shivanraptor/php-db-manager?category=code)

A modern PHP database wrapper for MySQL with prepared statements, connection pooling, and proper error handling.

## Features

- UTF-8/UTF8MB4 Connection Support
- Prepared Statements by Default
- Transaction Support
- Connection Pooling
- Automatic Retry Mechanism
- Proper Exception Handling
- Type Safety
- PSR-4 Autoloading
- Modern PHP 7.4+ Features

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- PHP MySQLi extension

## Installation

### Using Composer

```bash
composer require shivanraptor/php-db-manager
```

### Manual Installation

1. Download the latest release
2. Include the autoloader in your project:
```php
require_once('vendor/autoload.php');
```

## Quick Start

```php
use PhpDbManager\DbManager;

try {
    $db = new DbManager([
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'your_password',
        'database' => 'your_database',
        'charset' => 'utf8mb4',
        'port' => 3306,
        'persistent' => false,
        'autocommit' => true,
        'retry_attempts' => 3,
        'retry_delay' => 100 // milliseconds
    ]);

    // Execute a prepared statement
    $result = $db->execute(
        "SELECT * FROM users WHERE id = ? AND status = ?",
        ['i' => 1, 's' => 'active']
    );

    // Fetch a single row
    $user = $db->fetch($result);

    // Or fetch all rows
    $users = $db->fetchAll($result);

} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    // Handle error appropriately
}
```

## Connection Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| host | string | 'localhost' | Database host |
| username | string | - | Database username |
| password | string | - | Database password |
| database | string | - | Database name |
| charset | string | 'utf8mb4' | Connection charset |
| port | int | 3306 | Database port |
| persistent | bool | false | Use persistent connection |
| autocommit | bool | true | Enable autocommit |
| timeout | int | 30 | Connection timeout in seconds |
| retry_attempts | int | 3 | Number of connection retry attempts |
| retry_delay | int | 100 | Delay between retries in milliseconds |

## Usage Examples

### Basic Queries

```php
// Select query
$result = $db->execute("SELECT * FROM users WHERE id = ?", ['i' => 1]);
$user = $db->fetch($result);

// Insert query
$db->execute(
    "INSERT INTO users (name, email) VALUES (?, ?)",
    ['s' => 'John Doe', 's' => 'john@example.com']
);
$userId = $db->lastInsertId();

// Update query
$db->execute(
    "UPDATE users SET status = ? WHERE id = ?",
    ['s' => 'active', 'i' => 1]
);
$affectedRows = $db->affectedRows();
```

### Transactions

```php
try {
    $db->beginTransaction();
    
    $db->execute(
        "INSERT INTO orders (user_id, total) VALUES (?, ?)",
        ['i' => 1, 'd' => 99.99]
    );
    
    $db->execute(
        "UPDATE inventory SET stock = stock - 1 WHERE product_id = ?",
        ['i' => 123]
    );
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Fetching Results

```php
// Fetch as associative array
$result = $db->execute("SELECT * FROM users");
$users = $db->fetchAll($result, 'assoc');

// Fetch as object
$result = $db->execute("SELECT * FROM users");
$users = $db->fetchAll($result, 'object');

// Fetch as indexed array
$result = $db->execute("SELECT * FROM users");
$users = $db->fetchAll($result, 'array');
```

### Connection Info

```php
$info = $db->getConnectionInfo();
echo "Connected to {$info['server']} as {$info['user']}";
echo "MySQL version: {$info['version']}";
echo "Charset: {$info['charset']}";
```

## Breaking Changes from v1.x

1. **Constructor Changes**:
```php
// Old version
$db = new dbManager($host, $user, $pass, $dbname);

// New version
$db = new DbManager([
    'host' => $host,
    'username' => $user,
    'password' => $pass,
    'database' => $dbname
]);
```

2. **Namespace Required**:
```php
use PhpDbManager\DbManager;
```

3. **Error Handling**:
```php
// Old version
if ($db->error !== NULL) {
    // error exists
}

// New version
try {
    $db = new DbManager($options);
} catch (Exception $e) {
    // Handle error
}
```

4. **Query Execution**:
```php
// Old version
$result = $db->query_prepare($sql, $params);
$row = $db->result($result);

// New version
$result = $db->execute($sql, $params);
$row = $db->fetch($result);
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Run tests and code style checks
4. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.
