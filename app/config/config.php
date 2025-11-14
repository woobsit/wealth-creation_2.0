<?php
require __DIR__ . '/general_config.php';

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wealth_creation');

// Application configuration
define('APP_NAME', 'WEALTH CREATION ERP');
define('APP_URL', 'http://localhost:8080/wealth-creation_2.0');
define('APP_VERSION', '2.0.0');

$databaseObj = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
?>
