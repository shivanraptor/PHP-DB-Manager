<?php 
$global_param = array(
	'debug_mode' 		=> TRUE,
	'db_encoding' 		=> 'utf8',
	'db_host' 			=> 'localhost',
	'db_schm' 			=> 'your_schema',
	'db_user' 			=> 'mysql_username',
	'db_pass' 			=> 'mysql_password',
	'db_prefix' 		=> 'prefix_',
);
while (list($key, $value) = each($global_param)) {
	define($key, $value);
}
?>