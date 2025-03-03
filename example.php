<?php
// Use Composer to autoload DB Manager
require_once('vendor/autoload.php');

use PhpDbManager\DbManager;

// Require the Configuration file
require_once('conf/config.db.inc.php');

try {
	// Initialize database connection with options
	$db = new DbManager([
		'host' => DB_HOST,
		'username' => DB_USERNAME,
		'password' => DB_PASSWORD,
		'database' => DB_SCHEMA,
		'charset' => 'utf8mb4',
		'persistent' => false,
		'autocommit' => true,
		'retry_attempts' => 3,
		'retry_delay' => 100
	]);
} catch (Exception $e) {
	error_log('Database connection error: ' . $e->getMessage());
	die('Unable to connect to database. Please try again later.');
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Demo Page</title>
</head>
<body>
<?php
try {
	// Example 1: Using prepared statements with parameters
	$result = $db->execute(
		"SELECT * FROM some_table WHERE status = ?",
		['s' => 'active']
	);

	// Loop through results
	while ($row = $db->fetch($result)) {
		// Get row value
		$val = $row['column_name'];
		echo htmlspecialchars($val) . '<br>';
	}

	// Example 2: Fetch all rows at once
	$result = $db->execute("SELECT * FROM some_table");
	$allRows = $db->fetchAll($result);
	foreach ($allRows as $row) {
		echo htmlspecialchars($row['column_name']) . '<br>';
	}

	// Example 3: Insert with prepared statement
	$db->execute(
		"INSERT INTO some_table (column_name, status) VALUES (?, ?)",
		['s' => 'New Value', 's' => 'active']
	);
	$newId = $db->lastInsertId();

	// Example 4: Transaction example
	$db->beginTransaction();
	try {
		$db->execute(
			"UPDATE some_table SET status = ? WHERE id = ?",
			['s' => 'inactive', 'i' => 1]
		);
		$db->execute(
			"INSERT INTO log_table (action, table_id) VALUES (?, ?)",
			['s' => 'status_change', 'i' => 1]
		);
		$db->commit();
	} catch (Exception $e) {
		$db->rollback();
		throw $e;
	}

	// Example 5: Get connection info
	$info = $db->getConnectionInfo();
	echo "Connected to {$info['server']} as {$info['user']}<br>";
	echo "MySQL version: {$info['version']}<br>";
	echo "Charset: {$info['charset']}<br>";
	echo "Total queries executed: " . $db->getQueryCount() . "<br>";

} catch (Exception $e) {
	error_log('Database error: ' . $e->getMessage());
	echo 'An error occurred while processing your request.';
}
?>
</body>
</html>