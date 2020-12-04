PHP-DB-Manager
==============
[![CodeFactor](https://www.codefactor.io/repository/github/shivanraptor/php-db-manager/badge)](https://www.codefactor.io/repository/github/shivanraptor/php-db-manager)
[![LoC](https://tokei.rs/b1/github/shivanraptor/php-db-manager?category=code)](https://tokei.rs/b1/github/shivanraptor/php-db-manager?category=code)

PHP DB Manager aims to provide easy-to-use wrapper for MySQL database.
Features:
- UTF-8 Connection
- Support Transaction
- Support MySQLi PHP driver
- Support custom port
- Query Count information
- Connection information

Coming Soon Features:
- PDO support

Requirements:
- PHP v5.3+ (Compatible with PHP7)
- MySQL v4.1+
- PHP MySQLi module enabled

QUICK START
-----------

Step 1:
Install Composer by issuing the following command in your project root:

    curl -s http://getcomposer.org/installer | php


Step 2:
create a composer.json configuration file in the same folder, with the following contents:

    {
      "require": {
        "shivanraptor/php-db-manager": ">=1.0"
      }
    }
    
Step 3:
Execute the following command to install the library:

    php composer.phar install


Step 4:
Create a `config.db.inc.php` in `conf/` folder to configure the connection parameters to MySQL database

    <?php 
    $db_settings = array(
    	'DB_ENCODING' 		=> 'utf8',
    	'DB_HOST' 			=> 'localhost',
    	'DB_SCHEMA' 		=> 'your_schema',
    	'DB_USERNAME' 		=> 'root',
    	'DB_PASSWORD' 		=> 'your_password',
    	'DB_PREFIX' 		=> 'test_',
    );
    while (list($key, $value) = each($db_settings)) {
    	define($key, $value);
    }
    ?>

Parameters explained:

    DB_USERNAME : user name of MySQL database account
    DB_PASSWORD : password of MySQL database account
    DB_HOST 	: host name / IP of MySQL database ( in most cases, it is "localhost" )
    DB_SCHEMA 	: the desired schema of MySQL database
    DB_ENCODING : encoding of MySQL database connection
    DB_PREFIX 	: table prefix of MySQL database tables ( see Example 1 below )


Step 5:
Include the DB Manager to your codes and follow the sample codes to write your logic:

    // Use Composer to autoload DB Manager
    require_once('vendor/autoload.php');
    // Require the Configuration file
    require_once('conf/config.db.inc.php');
    

Parameters of Constructor
-------------------------

    host 		: Host of MySQL server , e.g. localhost or 192.168.1.123 ( make sure TCP/IP connection of MySQL server is enabled )
    user 		: Username
    pass		: Password
    _debugMode		: Debug mode ( set TRUE to enable , set FALSE to disable )
    charSet		: Character set of connection ( defaults to UTF-8 )
    autoCommit		: Transaction Auto Commit mode ( set TRUE to enable , set FALSE to disable )
    port		: Server Port of MySQL server ( defaults to 3306 , standard port of MySQL server )
    persistent		: Persistent Connection mode ( set TRUE to enable , set FALSE to disable )


Sample Codes
============

Example 1: Simple SELECT action
-------------------------------

    // Use Composer to autoload DB Manager
    require_once('vendor/autoload.php');
    // Require the Configuration file
    require_once('conf/config.db.inc.php');

    // Initialization
    $db = new dbManager(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA);

    // Query & Get the result
    $sql = "SELECT * FROM " . DB_PREFIX . "example";
    $rs = $db->query($sql);
    while($row = $db->result($rs)) {
    	// do your action here, for example...
    	echo $row['action_id'];
    }

    // Query & Get the result
    $sql = "SELECT * FROM " . DB_PREFIX . "example";
    $rows = $db->rs($sql);
    foreach($rows as $row) {
    	// do your action here, for example...
    	echo $row['action_id'];
    }

Example 2: Simple INSERT action
-------------------------------

    // Use Composer to autoload DB Manager
    require_once('vendor/autoload.php');
    // Require the Configuration file
    require_once('conf/config.db.inc.php');

    // Initialization
    $db = new dbManager(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA);

    // DBManager - Query & Get the result
    $sql = "INSERT INTO tbl_example VALUES ($value1, '$value2')";
    $db->query($sql);

    $row_id = $db->insert_id();


Other Functions
===============

1. Backward compatible version:

   $db = new dbManager(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA);
   
   
2. Support of charaset, disable debug message

   $db = new dbManager(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA, FALSE, 'utf8');
   
   
3. To check connection error:

   if($db->error !== NULL) {
	   // error exists
   }
   
   
4. Escape String

   $db->escape_string($str);


5. Use MySQLi PHP functions directly, e.g. `mysqli::rollback()`

   $db->mysqli->rollback();
   
   
6. Prepared Statement

   $sql = "SELECT field_name1, field_name2 FROM table_name WHERE id = ?"; 	// cannot use "SELECT *"
   $params = array('i' => 1); 							// i = integer , d = double , s = string , b = blob
   $result = $db->query_prepare($sql, $params);
   if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
	   $row = $db->result($result);
	   echo $row['field_name1'];
   } else {
	   foreach($result as $row) {
		   echo $row['field_name1'] . ' ' .$row['field_name2'];
	   }
   }


Version History
===============
v1.0
- initial release

Technical Support 
=================
findme@raptor.hk ( please specify email subject: "dbManager for MySQLi" )
or
ask in Stack Overflow using tag : `php-db-manager`

COPYRIGHT
=================
Copyright (c) 2009 Raptor K


SUPPORT US
=================
You can donate via [PayPal](https://paypal.me/YourAppApp).

    BTC: 1D1fxiG6B7GL4Cr14MpR7N7uJBemXo7nKK
    ETH: 0x740Ed7bBE8d287D0dC0477D6118962fcF600c4cc
