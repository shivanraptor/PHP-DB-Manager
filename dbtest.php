<?php
header('Content-Type: text/plain');
require_once('lib/DbManagerInterface.php');
require_once('lib/db.manager.mysqli.php');

use PhpDbManager\DbManager;

$db = new DbManager([
	'host' => 'mysql-cityu-scam.cneqkm6e0o2b.ap-east-1.rds.amazonaws.com',
	'username' => 'admin',
	'password' => 'cityuscam123',
	'database' => 'scam',
]);

$result = $db->execute("SELECT * FROM subscribers WHERE status = 1");
$subscribers = $db->fetchAll($result, 'assoc');
print_r($subscribers);
?>