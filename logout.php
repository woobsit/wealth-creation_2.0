<?php
require 'config/config.php';
require 'models/User.php';
require 'helpers/session_helper.php';

// Check if user is already logged in
requireLogin();

$user = new User($databaseObj);
$user->logout();
redirect("index.php");