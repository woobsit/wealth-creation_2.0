<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../models/Transaction.php';
require_once '../models/Remittance.php'; 
require_once '../models/UnpostedTransaction.php'; 
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
$otherTransactions = new UnpostedTransaction();
// Get current user information
$currentUser = $user->getUserById($userId);
// Get Current User all staff information
$currentUserStaffInfo = $user->getUserStaffDetail($userId);
// Get the logged-in officer's remittance records
$remittances = $remittance->getRemittancesByOfficer($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wealth Creation - Income ERP System</title>
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
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dept-badge {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        /* .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        } */
        .stat-card-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card-4 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
                        <span class="text-xl font-bold text-gray-900">WC ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">
                         <?php 
                            if (hasDepartment('Accounts')) { echo 'Account Dept. Dashboard'. '<p class="text-sm text-gray-500"> View, Post, Approve & Manage lines of Income</p>';}
                            if (hasDepartment('Wealth Creation')) { echo 'Wealth Creation Dashboard'. '<p class="text-sm text-gray-500"> View, Post, & Manage lines of Income</p>'; }
                            if (hasDepartment('Audit/Inspections')) { echo 'Audit/Inspections Dashboard'. '<p class="text-sm text-gray-500"> View, Approve, & Manage lines of Income</p>'; } 
                         ?>
                        </h1>
                        
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
                                <?= strtoupper($currentUser['full_name'][0]) ?>
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



<!-- <div class="container mt-4">
    <h3 class="mb-4">My Transactions (Wealth Creation)</h3>
    <?php if (!empty($remittances)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Remittance Ref</th>
                    <th>Income Line</th>
                    <th>Date</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach ($remittances as $rem): ?>
                    <?php 
                        $transactions = $transaction->getTransactionsByRemitId($rem['id']);
                        foreach ($transactions as $txn): 
                    ?>
                        <tr>
                            <td><?= $counter++; ?></td>
                            <td><?= htmlspecialchars($rem['remittance_ref']); ?></td>
                            <td><?= htmlspecialchars($txn['income_line']); ?></td>
                            <td><?= htmlspecialchars(date('d M, Y', strtotime($txn['date_of_payment']))); ?></td>
                            <td><?= number_format($txn['amount_paid'], 2); ?></td>
                            <td>
                                <?php if ($txn['is_approved']): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">You have not made any remittances yet.</div>
    <?php endif; ?>
</div> -->

<div class="container mt-4">
    <h3 class="mb-4">My Transactions (Wealth Creation)</h3>
    <?php if (!empty($remittances)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Remittance Ref</th>
                    <th>Income Line</th>
                    <th>Date</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                foreach ($remittances as $rem): 
                    $transactions = $otherTransactions->getUnpostedTransactionsByOfficer($rem['id']);
                    if (empty($transactions)) {
                        echo '<tr class="table-warning">';
                        echo '<td>' . $counter++ . '</td>';
                        echo '<td>' . htmlspecialchars($rem['remittance_ref']) . '</td>';
                        echo '<td colspan="3" class="text-muted">No transactions recorded</td>';
                        echo '<td><span class="badge bg-danger">Missing</span></td>';
                        echo '</tr>';
                    } else {
                        foreach ($transactions as $txn): 
                ?>
                        <tr>
                            <td><?= $counter++; ?></td>
                            <td><?= htmlspecialchars($rem['remittance_ref']); ?></td>
                            <td><?= htmlspecialchars($txn['income_line']); ?></td>
                            <td><?= htmlspecialchars(date('d M, Y', strtotime($txn['date_of_payment']))); ?></td>
                            <td><?= number_format($txn['amount_paid'], 2); ?></td>
                            <td>
                                <?php if ($txn['is_approved']): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php 
                        endforeach; 
                    }
                endforeach; 
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">You have not made any remittances yet.</div>
    <?php endif; ?>
</div>

<?php // include_once '../includes/footer.php'; ?>
