<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/models/User.php';
require __DIR__.'/app/helpers/session_helper.php';

// Check if user is already logged in
requireLogin();

$user = new User($databaseObj);
$user->logout();
redirect("index.php");