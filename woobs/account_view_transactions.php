<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/helpers/session_helper.php';

// Check if user is already logged in
requireLogin();
$userId = $_SESSION['user_id'];
$db = $databaseObj;
$user = new User($databaseObj);
$staff = $user->getUserStaffDetail($userId);
