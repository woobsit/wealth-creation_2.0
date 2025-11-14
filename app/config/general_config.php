<?php
// Fix the internal require using __DIR__
require __DIR__ . '/Database.php';

session_set_cookie_params(86400);
session_start();
ini_set('session.gc-maxlifetime', 60 * 60 * 24);
// ini_set('session.cookie_lifetime', 60 * 60 * 24);
// 
// 
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