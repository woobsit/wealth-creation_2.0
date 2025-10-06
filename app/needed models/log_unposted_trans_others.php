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
$department = $user->getDepartmentByUserIdstring($userId);
$currentUserStaffInfo = $user->getUserStaffDetail($userId);
$incomeLines = $account->getIncomeLineAccounts();

$totalRemitted = $otherTransactions->getRemittanceSummaryForToday($userId);

$staffId     = $userId;
$fullName    = $currentUser['full_name'];
$currentDate = date('Y-m-d');

if(hasDepartment($department)) {
    die("Unauthorized Access");
}

// $controller = new LogUnpostedOtherController($conn, $staffId, $currentDate);
// $data = $controller->getSummaryData();

$pageTitle = "Log Unposted Other Collection - WC ERP";
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Flash Messages -->
    <?php if (!empty($success_msg)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <h3 class="text-start"><b>Hello <?php echo $currentUser['full_name']; ?> 👋🏼</b> </h3>
    <p>Welcome to your dashboard! Your dashboard is peculiar to your <?php echo $department; ?> Department. Please always logout of your account for security reasons. </p>
    <p><?php include ('countdown_script.php'); ?></p>


    <div class="max-w-6xl mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= $pageTitle ?></h2>
        <h4 class="text-base text-gray-700 mb-4">
            Remitted: <span class="text-red-600 font-semibold"> <?= formatCurrency($totalRemitted['remitted']); ?></span> |
            Posted: <span class="text-red-600 font-semibold"> <?= formatCurrency($totalRemitted['posted']); ?></span> |
            Unposted: <span class="text-red-600 font-semibold"> <?= formatCurrency($totalRemitted['unposted']) ?></span> |
            Logged: <span class="text-red-600 font-semibold"> <?= formatCurrency($totalRemitted['loggedAmount']); ?></span>
        </h4>

        <?php //include '../../controllers/error_messages_inc.php'; ?>

        <form method="post" action="" class="space-y-6 bg-white p-6 rounded-lg shadow" id="logForm" autocomplete="off">
            <input type="hidden" name="posting_officer_id" value="<?= $staffId ?>">
            <input type="hidden" name="posting_officer_name" value="<?= $fullName ?>">
            <input type="hidden" name="loggable" value="<?= $totalRemitted['unlogged'] ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Payment</label>
                <input type="text" name="date_of_payment" value="<?= date('d/m/Y') ?>" readonly class="w-full p-2 border border-gray-300 rounded bg-gray-100">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Income Line</label>
                <select name="income_line" class="w-full p-2 border border-gray-300 rounded" required>
                    <option value="">Select...</option>
                    <?php foreach ($incomeLines as $line): //foreach($incomeLines as $line) ?> 
                        <option value="<?= $line['acct_alias'] ?>"><?= $line['acct_desc'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Remittance</label>
                <select name="remit_id" class="w-full p-2 border border-gray-300 rounded" required>
                    <option value="">Select...</option>
                    <option value="<?= $totalRemitted['remit_id']; ?>">
                        <?= $totalRemitted['date']; ?>: Loggable Remittance - <?= formatCurrency($totalRemitted['unlogged']); ?>
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Description</label>
                <input type="text" name="transaction_desc" maxlength="100" class="w-full p-2 border border-gray-300 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Receipt No</label>
                <input type="text" name="receipt_no" maxlength="7" pattern="^\d{7}$" class="w-full p-2 border border-gray-300 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" class="w-full p-2 border border-gray-300 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <select name="reason" class="w-full p-2 border border-gray-300 rounded" required>
                    <option value="">Select...</option>
                    <option value="Missing Amount">Missing Amount</option>
                    <option value="Wrong Remittance Amount">Wrong Remittance Amount</option>
                    <option value="Posting Deadline">Posting Deadline</option>
                </select>
            </div>

            <?php if ($totalRemitted['unposted'] == $totalRemitted['loggedAmount']): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    You do not have any <strong>UNLOGGED</strong> remittances for today.
                </div>
            <?php else: ?>
                <div class="flex items-center space-x-4">
                    <button type="submit" name="btn_post" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">Log Payment</button>
                    <button type="reset" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">Clear</button>
                </div>
            <?php endif; ?>
        </form>
    </div>



    </main>

<script>
  setInterval(() => {
    fetch('get_posting_count.php')
      .then(res => res.text())
      .then(html => {
        document.getElementById('postcount').innerHTML = html;
      });
  }, 30000);
</script>
