<?php
// Use Composer to autoload DB Manager
require_once('vendor/autoload.php');
// Require the Configuration file
require_once('conf/config.db.inc.php');
$db = new dbManager(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Demo Page</title>
</head>
<body>
<?php
$sql = "SELECT * FROM some_table WHERE 1";
$rs = $db->query($sql);

// loop to get values
while($row = $db->result($rs)) {
	// get row value
	$val = $row['column_name'];
}

// or ... get 1 row only
// $row = $db->result($rs);

// or ... fetch total row count
// $num = $db->result($rs, 'num_rows');

?>
</body>
</html>