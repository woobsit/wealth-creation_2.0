<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Remittance.php'; 
require_once 'models/UnpostedTransaction.php'; 
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

$userId = getLoggedInUserId();
// Initialize objects
$db = new Database();
header('Content-Type: application/json');

$department = isset($_GET['department']) ? $_GET['department'] : 'Wealth Creation';

$data = [];

if ($department === 'Wealth Creation') {
    $db->query("SELECT user_id AS id, full_name FROM staffs WHERE department = 'Wealth Creation' ORDER BY full_name ASC");
    $data = $db->resultSet();
    foreach ($data as &$item) {
        $item['value'] = $item['id'] . '-wc';
    }
} else {
    $db->query("SELECT id, full_name, department FROM staffs_others ORDER BY full_name ASC");
    $data = $db->resultSet();
    foreach ($data as &$item) {
        $item['value'] = $item['id'] . '-so';
        $item['full_name'] = $item['full_name'] . ' - ' . $item['department'];
    }
}

echo json_encode($data);
exit;
