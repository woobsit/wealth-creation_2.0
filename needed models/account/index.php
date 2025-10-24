<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../helpers/session_helper.php';

// Check if user is logged in
if(isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}
