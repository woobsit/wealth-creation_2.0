<?php
// Fix the internal require using __DIR__
require __DIR__ . '/Database.php'; 
session_set_cookie_params(86400);
session_start();
ini_set('session.gc-maxlifetime', 60 * 60 * 24);
// ini_set('session.cookie_lifetime', 60 * 60 * 24);
// 
// 

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wealth_creation');

// Application configuration
define('APP_NAME', 'WEALTH CREATION ERP');
define('APP_URL', 'http://localhost:8080/woobs_erp/wealth-creation-2.0');
define('APP_VERSION', '2.0.0');

define('ROOT_PATH', dirname(__DIR__)); 

// Session configuration
//define('SESSION_NAME', 'income_erp_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Date and time configuration
date_default_timezone_set('Africa/Lagos');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Secret key for token generation
define('SECRET_KEY', '');

$databaseObj = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
?>
