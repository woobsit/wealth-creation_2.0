<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../models/Transaction.php';
require_once '../models/Remittance.php'; 
require_once '../models/Account.php';
require_once '../helpers/session_helper.php';

// Check if user is logged in
requireLogin();

$userId = getLoggedInUserId();
// Initialize objects
$db = new Database();
$user = new User();
$transaction = new Transaction();
$remittance = new Remittance();
$account = new Account();
// Get transaction statistics
$stats = $transaction->getTransactionStats();

// Get current user information
$currentUser = $user->getUserById($userId);
$currentUserStaffInfo = $user->getUserStaffDetail($userId);
$currentUserAdminRole = $user->getUserAdminRole($userId);
//Array ( [id] => 61 [user_level] => 0 [full_name] => Aanu ELEMIDE [password] => 5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8 [email] => aanu.elemide@thearenamarket.com [has_roles] => [status] => active ) 
$myTotalremittances = $remittance->getTotalRemittancesForOfficer($userId);
//$myCashRemittance = $remittance->getTotalCashRemitanceForOfficer($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">Income ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">Transaction Management</h1>
                        <p class="text-sm text-gray-500">View, Approve & Manage All Transactions</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($currentUserStaffInfo['department']) ?></div>
                    </div>
                    <div class="relative">
                        <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                <?= strtoupper($currentUser['full_name']) ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <?php if(hasDepartment('Wealth Creation')): ?>
                            <a href="post_collection.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-receipt mr-2"></i> Post Collections
                            </a>
                            <?php endif; ?>
                            <?php if(hasDepartment('Accounts')): ?>
                            <a href="remittance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-money-bill-wave mr-2"></i> Remittances
                            </a>
                            <?php endif; ?>
                            <?php if(hasDepartment('Audit/Inspections')): ?>
                            <a href="verify_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-clipboard-check mr-2"></i> Audit Dashboard
                            </a>
                            <?php endif; ?>
                            <div class="border-t my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</body>

</html>
