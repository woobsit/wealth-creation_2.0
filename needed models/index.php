<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Remittance.php';
require_once 'models/BudgetManager.php'; 
require_once 'models/BudgetRealTimeManager.php';
require_once 'models/UnpostedTransaction.php'; 
require_once 'models/PaymentProcessor.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

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
$processor = new PaymentProcessor();
$budget_manager = new BudgetManager();
$manager = new BudgetRealTimeManager();

$current_date = date('Y-m-d');

// Get current year data
$current_year = date('Y');
$current_month = date('n');

// Get transaction statistics
$stats = $transaction->getTransactionStats();
// Get today's remittances
$todayRemittances = $remittance->getRemittancesByDate(date('Y-m-d'));
// Get income line accounts
$incomeLines = $account->getIncomeLineAccounts();
// Get staff department
$staff = $user->getUserStaffDetail($userId);
//Get Current User check if admin role
$currentUserAdminRole = $user->getUserAdminRole($userId);
// Get remittance balance for Wealth Creation staff
$remittance_data = [];
if ($staff['department'] === 'Wealth Creation') {
    $remittance_data = $processor->getRemittanceBalance($staff['user_id'], $current_date);
}


$pendingTransactions = [];

$selected_month = isset($_GET['month']) ? $_GET['month'] : null;

// Get performance data
$performance_data = $manager->getBudgetPerformanceRealTime($current_year, $selected_month);
$budget_lines = $manager->getBudgetLines($current_year);
// Calculate summary statistics
$total_budget = array_sum(
    array_map(function($line) {
        return $line['status'] === 'Active' ? $line['annual_budget'] : 0;
    }, $budget_lines)
);
//$total_budget = array_sum(array_column($budget_lines, 'annual_budget'));
$total_actual = 0;
foreach ($performance_data as $perf) {
    $total_actual += $perf['actual_amount'];
}
$budget_variance = $total_actual - $total_budget;
$achievement_percent = $total_budget > 0 ? (($total_actual - $total_budget) / $total_budget) * 100 : 0;


if ($staff['department'] === 'Wealth Creation') {
    // Get remittances for this officer
    $myTotalTillbalance = $otherTransactions->totalTillWealthCreation($userId);
    $myRemittances = $remittance->getRemittancesByOfficer($userId);
    $myUnpostedTransactions = $otherTransactions->getUnpostedTransactionsByRemitId($userId);
    $countUnposted = $otherTransactions->countUnpostedTransactionsByOfficer($userId);
    $amountUnpostedToday = $otherTransactions->totalUnpostedAmountTodayByOfficer($userId);
    $declinedTransactions = $otherTransactions->countDeclinedPostsByOfficer($userId, $staff['department']);
    $pending = $otherTransactions->countPendingPostsByOfficer($userId, $staff['department']);
    $wrong = $otherTransactions->countWrongEntriesFlaggedByIT($userId);
    //$sumofPendingPost = $otherTransactions->totalofPendingPost($userId);
    $totalRemitted = $otherTransactions->getRemittanceSummaryForToday($userId);
} elseif ($staff['department'] === 'Accounts') {
    // Get pending transactions for account approval
    $accountOfficerTotalTillbalance = $otherTransactions->totalTillAccounts($userId);
    $pendingTransactions = $transaction->getPendingTransactionsForAccountApproval();
    $declinedTransactions = $otherTransactions->countDeclinedPostsByOfficer($userId, $staff['department']);
    $pending = $otherTransactions->countPendingPostsByOfficer($userId, $staff['department']);
    $wrong = $otherTransactions->countWrongEntriesFlaggedByIT($userId);
} elseif ($staff['department'] === 'Audit/Inspections') {
    // Get pending transactions for audit verification
    $pendingTransactions = $transaction->getPendingTransactionsForAuditVerification();
}


//corrected Records
$correctedSummary = $otherTransactions->getCorrectedRecordStatusSummary($db);
//Shop Renewal Summary
$renewalSummary = $otherTransactions->getShopRenewalStatusSummary($db);
$expectedSummary = $otherTransactions->getExpectedAdjustmentSummary($db);
//$myCashRemittance = $remittance->getTotalCashRemitanceForOfficer($userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wealth Creation - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
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
    <?php include('include/header-nav.php'); ?>

    <!-- Main Content -->
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

        <h3 class="text-start"><b>Hello <?php echo $staff['full_name']; ?> 👋🏼</b> </h3>
        <p>Welcome to your dashboard! Your dashboard is peculiar to your <?php echo $staff['department']; ?> Department.  </p>
        <p><?php include ('countdown_script.php'); ?></p>

        <!-- Stats Overview -->
        <?php 
            if ($staff['department'] === 'Wealth Creation') {
                include('include/dashboard-leasing.php');
            } else {
                include ('include/dashboard-overview.php');
            }

            if ($staff['department'] === 'Accounts') {
                echo
                '<div class="bg-white text-sm text-gray-700 rounded-md shadow px-4 py-2 flex items-center justify-between space-x-4">
                    <span class="font-semibold text-green-600">'.formatCurrency($accountOfficerTotalTillbalance). ' Till Balance </span> |
                    <span class="text-red-500">'. $declinedTransactions .' Declined</span> |
                    <span class="text-yellow-600">'. $pending .' Pending</span> |
                    <span class="text-gray-500">'. $wrong .' Wrong entries</span> |
                </div>';
                
            }
        ?>
        <!-- Department-Specific Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Analysis Section -->
            <?php if($staff['level'] ==='ce' || $staff['level'] ==='IT' || $staff['level'] ==='fc'): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mt-4">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">MPR & Revenue Report Analysis</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-1 gap-3">
                    
                    <a href="leasing/mpr_income_lines.php" target="_blank" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors group">
                        <div class="p-2 bg-blue-600 rounded-lg mr-3 group-hover:bg-blue-700 transition-colors">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Monthly Collection Report</div>
                            <div class="text-xs text-gray-500">Lists income lines in daily collections</div>
                        </div>
                    </a>
                    
                    <a href="leasing/mpr_income_lines_officers.php" target="_blank" class="flex items-center p-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors group">
                        <div class="p-2 bg-orange-600 rounded-lg mr-3 group-hover:bg-orange-700 transition-colors">
                            <i class="fas fa-search-plus text-white"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900"> Officer's Summary Report </div>
                            <div class="text-xs text-gray-500">View Officer by Officer Collection </div>
                        </div>
                    </a>

                    
                    <a href="ledger/ledger.php" target="_blank" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors group">
                        <div class="p-2 bg-green-600 rounded-lg mr-3 group-hover:bg-green-700 transition-colors">
                            <i class="fas fa-balance-scale text-white"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900"> General Ledger </div>
                            <div class="text-xs text-gray-500">View Revenue Report </div>
                        </div>
                    </a>
                </div>
                
                <div class="space-y-3">
                    <!-- <a href="leasing/mpr_income_lines.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Monthly Collection Report</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a> -->

                    <!-- <a href="leasing/mpr_income_lines_officers.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-search-plus text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Officer's Summary Report</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a> -->

                    <!-- <a href="leasing/mpr_recommendations.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-lightbulb text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Strategic Recommendations</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a> -->  
                </div>
            </div>
            <?php endif; ?>

            <?php if($staff['department'] ==='Wealth Creation'): ?>
            <!-- Wealth Creation Department -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-coins text-yellow-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Collection Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="post_payments.php" class="card-hover block bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-receipt text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Post Payments</span>
                        </div>
                    </a>
                    <a href="leasing/mpr_income_lines.php" class="card-hover block bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-file-alt text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Monthly Collection Report</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($staff['department'] === 'Accounts'): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mt-4">
                <div class="flex items-center mb-4">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-coins text-yellow-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Collection Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="account/account_remittance.php" class="card-hover block bg-gradient-to-r from-orange-50 to-red-50 p-4 rounded-lg border border-orange-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-money-bill-wave text-orange-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Account Remittance</span>
                        </div>
                    </a>
                    <a href="post_payments.php" class="card-hover block bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-receipt text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Post Payments</span>
                        </div>
                    </a> 
                </div>
            </div>
            <?php endif; ?>

            <!-- Officer Management for Wealth Creation -->
            <!-- <div class="bg-white rounded-xl shadow-lg p-6"> -->
            <?php if($staff['level'] ==='IT' || $staff['level'] ==='fc'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 rounded-xl xl:grid-cols-3 gap-4 mt-4 shadow-lg p-6">
                <!-- Corrected Records Summary -->
                <div class="bg-yellow-100 card-hover border border-orange-100 transition-all duration-200 rounded-md shadow p-3 text-xs">
                    <h2 class="text-base font-semibold text-yellow-800 mb-1">Corrected Records</h2>
                    <div class="space-y-0.5 text-gray-800">
                    <p><strong class="text-red-600">Total:</strong> <?= $correctedSummary['total'] ?> record(s)</p>
                    <p><strong class="text-green-600">HOD Approved:</strong> <?= $correctedSummary['awaiting_audit'] ?> awaiting Audit verification</p>
                    <p><strong class="text-blue-600">Approved/Declined:</strong> <?= $correctedSummary['fully_approved_or_declined'] ?> record(s)</p>
                    <p><strong class="text-red-500">Declined by Audit:</strong> <?= $correctedSummary['declined_by_audit'] ?></p>
                    <a href="mod/leasing/corrected_records.php" class="inline-block mt-1 text-blue-700 underline">View All</a>
                    </div>
                </div>

                <!-- Shop Renewal Summary -->
                <div class="bg-green-100 card-hover transition-all duration-200 rounded-md shadow p-3 text-xs">
                    <h2 class="text-base font-semibold text-green-800 mb-1">Shop Renewal</h2>
                    <div class="space-y-0.5 text-gray-800">
                        <p><strong class="text-red-600">Total:</strong> <?= $renewalSummary['total'] ?> record(s)</p>
                        <p><strong class="text-yellow-600">Awaiting IT Approval:</strong> <?= $renewalSummary['awaiting_it'] ?></p>
                        <p><strong class="text-red-600">Declined by IT:</strong> <?= $renewalSummary['declined_by_it'] ?></p>
                        <p><strong class="text-yellow-600">Awaiting HOD Approval:</strong> <?= $renewalSummary['awaiting_hod'] ?></p>
                        <p><strong class="text-red-600">Declined by HOD:</strong> <?= $renewalSummary['declined_by_hod'] ?></p>
                        <p><strong class="text-yellow-600">Awaiting Audit Verification:</strong> <?= $renewalSummary['awaiting_audit'] ?></p>
                        <p><strong class="text-red-600">Declined by Audit:</strong> <?= $renewalSummary['declined_by_audit'] ?></p>
                        <a href="mod/leasing/renewed_records.php" class="inline-block mt-1 text-blue-700 underline">View All</a>
                    </div>
                </div>

                <!-- Expected Adjustment Summary -->
                <div class="bg-cyan-100 card-hover transition-all duration-200 rounded-md shadow p-3 text-xs shadow p-4">
                    <h2 class="text-base font-semibold text-cyan-800 mb-1">Expected Adjustment</h2>
                    <div class="text-sm text-gray-800 space-y-1">
                    <p><strong class="text-red-600">Total:</strong> <?= $expectedSummary['total'] ?> record(s)</p>
                    <p><strong class="text-yellow-600">Awaiting IT Approval:</strong> <?= $expectedSummary['awaiting_it'] ?></p>
                    <p><strong class="text-red-600">Declined by IT:</strong> <?= $expectedSummary['declined_by_it'] ?></p>
                    <a href="mod/leasing/update_expected_records.php" class="inline-block mt-2 text-blue-700 underline">View All</a>
                    </div>
                </div>

                <!-- <div class="flex items-center mb-4">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Officer Management</h2>
                </div> -->
                <!-- <div class="space-y-3">
                    <a href="officer_management.php" class="card-hover block bg-gradient-to-r from-purple-50 to-indigo-50 p-4 rounded-lg border border-purple-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-user-tie text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">View Officers & Assignments</span>
                        </div>
                    </a>
                    <a href="officers.php" class="card-hover block bg-gradient-to-r from-indigo-50 to-blue-50 p-4 rounded-lg border border-indigo-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-store text-indigo-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Individual Officer Shops</span>
                        </div>
                    </a>
                </div> -->
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-lg p-6 mt-4">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900"> WC Budget Administration </h3>
                </div>
                <?php
                    // Status
                    $status_text = $budget_variance >= 0 ? 'Above Budget' : 'Below Budget';
                    $status_color = $budget_variance >= 0 ? 'text-green-600' : 'text-red-600';
                    $bar_status_color = $achievement_percent > 0 ? 'bg-green-500' : 'bg-red-500';
                ?>
                <div class="space-y-4">
                    <!-- Totals -->
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-500">JAN-AUG 2025 Budget</p>
                            <p class="text-lg font-semibold text-gray-800">₦<?php echo number_format($total_budget); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Actual (JAN-AUG) 2025</p>
                            <p class="text-lg font-semibold text-red-600">₦<?php echo number_format($total_actual); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Variance</p>
                            <p class="text-lg font-semibold text-red-600">₦<?php echo number_format($budget_variance); ?></p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <!--<a href="budget/annual_budget_performance.php?year=<?php echo $current_year; ?>" target="_blank" -->
                        <!--class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors group">-->
                        <!--    <div class="p-2 bg-purple-600 rounded-lg mr-3 group-hover:bg-purple-700 transition-colors">-->
                        <!--        <i class="fas fa-chart-bar text-white"></i>-->
                        <!--    </div>-->
                        <!--    <div>-->
                        <!--        <div class="text-sm font-medium text-gray-900">Budget Performance Analysis</div>-->
                        <!--        <div class="text-xs text-gray-500">View detailed performance reports</div>-->
                        <!--    </div>-->
                        <!--</a>-->

                        <!--<a href="budget/target_vs_achievement_report.php?month=<?php echo $current_month; ?>&year=<?php echo $current_year; ?>" target="_blank"-->
                        <!--class="flex items-center p-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors group">-->
                        <!--    <div class="p-2 bg-orange-600 rounded-lg mr-3 group-hover:bg-orange-700 transition-colors">-->
                        <!--        <i class="fas fa-trophy text-white"></i>-->
                        <!--    </div>-->
                        <!--    <div>-->
                        <!--        <div class="text-sm font-medium text-gray-900">Officer's Target vs Achievement </div>-->
                        <!--        <div class="text-xs text-gray-500">Compare targets with actual performance</div>-->
                        <!--    </div>-->
                        <!--</a>-->

                        <?php if($staff['level'] ==='IT' || $staff['level'] ==='fc' || $staff['level'] ==='ce'): ?>
                        <a href="budget/budget_management.php" target="_blank"
                        class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors group">
                            <div class="p-2 bg-blue-600 rounded-lg mr-3 group-hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">Create Budget Line</div>
                                <div class="text-xs text-gray-500">Set up new income line budget</div>
                            </div>
                        </a>
                        
                        <a href="budget/officer_target_management.php" target="_blank"
                        class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors group">
                            <div class="p-2 bg-green-600 rounded-lg mr-3 group-hover:bg-green-700 transition-colors">
                                <i class="fas fa-bullseye text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">Manage Officer Targets</div>
                                <div class="text-xs text-gray-500">Set monthly collection targets</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                    <!-- Progress bar -->
                    
                     <div>
                        <p class="text-sm text-gray-600 mb-1">Performance Variance</p>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="<?php echo $bar_status_color; ?> h-3 rounded-full" 
                                 style="width: <?php echo min(100, $achievement_percent); ?>%;">
                            </div>
                        </div>
                        <p class="text-xs <?php echo $status_color; ?> mt-1">
                            <?php echo number_format($achievement_percent, 2); ?>% of budget achieved (<?php echo $status_text; ?>)
                        </p>
                    </div> 

                    <!-- Action button -->
                    <!-- <div class="pt-3">
                        <a href="budget/" target="_blank" 
                        class="inline-block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition">
                            Go to Budget Dashboard
                        </a>
                    </div> -->
                </div>
            </div>


        </div>
        
        


        <?php 
         if (hasDepartment('Accounts') || hasDepartment('Audit/Inspections') || hasDepartment('IT/E-Business')) {
            include('include/quick-link-account.php');
         }
         if (hasDepartment('Wealth Creation')) {
            include('include/quick-link-leasing.php');
         } 
        ?>



    
    </main>

 <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<?php include('include/footer-script.php');?>
</body>
</html>