<?php 
$global_param = array(
	'debug_mode' 		=> TRUE,
	'db_encoding' 		=> 'utf8',
	// DB - CMS
	'db_host' 			=> 'localhost',
	'db_schm' 			=> 'db_name',
	'db_user' 			=> 'user_name',
	'db_pass' 			=> 'password',
	'db_prefix' 		=> 'tbl_',
	// version & paths
	'sys_version' 		=> '1.0',
	'domain_path' 		=> '/var/www/',
	'sys_url'			=> 'http://raptor.hk/',
	'abs_path'			=> '/var/www/',
	'php_path'			=> '/usr/bin/php',
);
while (list($key, $value) = each($global_param)) {
	define($key, $value);
}
?>