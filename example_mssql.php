<?php
require_once('lib/config.inc.php');
require_once('lib/db.manager.mssql.php');
$db = new dbManager(db_host, db_user, db_pass, db_schm);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>MSSQL Demo Page</title>
</head>
<body>
<?php
$sql = "SELECT * FROM some_table ORDER BY id";
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