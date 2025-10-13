<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/Transaction.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/OfficerPerformanceAnalyzer.php';
require __DIR__.'/../app/models/OfficerTargetManager.php';
require __DIR__.'/../app/models/PaymentProcessor.php';
require __DIR__.'/../app/models/Remittance.php';
// Check if user is already logged in
requireLogin();
$user_id = $_SESSION['user_id'];
$transaction =  new Transaction($databaseObj);
//shop renewal
$renewalSummary = $transaction->getShopRenewalStatusSummary($databaseObj);
//corrected Records
$correctedSummary = $transaction->getCorrectedRecordStatusSummary($databaseObj);
//expected adjustment
$expectedSummary = $transaction->getExpectedAdjustmentSummary($databaseObj);

$current_date = date('Y-m-d');
$current_month = date('n');
$current_year = date('Y');
$month_name = date('F');

// Account officers
if ($_SESSION['department'] == "Accounts") {
    $remittancemanager = new Remittance($databaseObj);
    $stats = $transaction->getTransactionStats();
    $pendingTransactions = [];
    $pendingTransactions = $transaction->getPendingTransactionsForAccountApproval();
    $accountOfficerTotalTillbalance = $transaction->totalTillAccounts($user_id);
    $remittances = $remittancemanager->getRemittancesByDate($current_date);
    $summary = $remittancemanager->getRemittanceSummary($current_date);
    $officers = $remittancemanager->getWealthCreationOfficers();
    //$declinedTransactions = $transaction->countDeclinedPostsByOfficer($userId, $_SESSION['department']);
}
// Account officers
if ($_SESSION['department'] == 'Audit/Inspections') {
    $stats = $transaction->getTransactionStats();
    $pendingTransactions = [];
    $pendingTransactions = $transaction->getPendingTransactionsForAuditVerification();
    //$declinedTransactions = $transaction->countDeclinedPostsByOfficer($userId, $_SESSION['department']);
}
//leasing officers
if ($_SESSION['department'] == "Wealth Creation") {
    $processor = new PaymentProcessor($databaseObj);
    $analyzer = new OfficerPerformanceAnalyzer($databaseObj);
    $target_manager = new OfficerTargetManager($databaseObj);
    // Get officer's performance data
    $officer_info = $analyzer->getOfficerInfo($user_id, false);
    $officer_targets = $target_manager->getOfficerTargets($user_id, $current_month, $current_year);
    $performance_summary = $target_manager->getOfficerPerformanceSummary($user_id, $current_month, $current_year);
    $daily_performance = $analyzer->getOfficerDailyPerformance($user_id, $current_month, $current_year, false);
    $efficiency_metrics = $analyzer->getOfficerEfficiencyMetrics($user_id, $current_month, $current_year, false);
    $rating = $analyzer->getOfficerRating($user_id, $current_month, $current_year, false);
    $trends = $analyzer->getOfficerTrends($user_id, $current_month, $current_year, false);
    // Get remittance balance
    $remittance_data = $processor->getRemittanceBalance($user_id, $current_date);
    // Calculate today's progress
    $today_collections = isset($daily_performance[date('j')]) ? $daily_performance[date('j')] : 0;
    $daily_target = 0;
    if (!empty($officer_targets)) {
        $daily_target = array_sum(array_column($officer_targets, 'daily_target'));
    }
    $today_progress = $daily_target > 0 ? ($today_collections / $daily_target) * 100 : 0;
    // Calculate monthly progress
    $monthly_collections = array_sum($daily_performance);
    $monthly_target = isset($performance_summary['total_target']) ? $performance_summary['total_target'] : 0;
    $monthly_progress = $monthly_target > 0 ? ($monthly_collections / $monthly_target) * 100 : 0;
    // Calculate annual targets
    $annual_target = 0;
    foreach ($officer_targets as $target) {
        $annual_target += $target['monthly_target'] * 12; // Approximate annual target
    }

    //Sunday position for chart
    $sundays = $analyzer->getSundayPositions($current_month, $current_year);
    // Calculate achievement levels and awards
    $achievement_level = 'Bronze';
    $achievement_icon = 'fa-medal';
    $achievement_color = 'orange';
    $achievement_message = 'Keep working hard!';

    if ($performance_summary) {
        $avg_achievement = $performance_summary['avg_achievement_percentage'];
        if ($avg_achievement >= 150) {
            $achievement_level = 'Platinum King';
            $achievement_icon = 'fa-crown';
            $achievement_color = 'yellow';
            $achievement_message = 'Outstanding performance! You are the King of the field!';
        } elseif ($avg_achievement >= 120) {
            $achievement_level = 'Gold Medal';
            $achievement_icon = 'fa-trophy';
            $achievement_color = 'yellow';
            $achievement_message = 'Excellent work! Gold medal performance!';
        } elseif ($avg_achievement >= 100) {
            $achievement_level = 'Silver Medal';
            $achievement_icon = 'fa-medal';
            $achievement_color = 'gray';
            $achievement_message = 'Great job! You\'ve met your targets!';
        } elseif ($avg_achievement >= 80) {
            $achievement_level = 'Bronze Medal';
            $achievement_icon = 'fa-medal';
            $achievement_color = 'orange';
            $achievement_message = 'Good effort! Almost there!';
        } else {
            $achievement_level = 'Needs Improvement';
            $achievement_icon = 'fa-exclamation-triangle';
            $achievement_color = 'red';
            $achievement_message = 'Warning: Performance below expectations. Let\'s improve!';
        }
    }
}
// Handle form submissions
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['btn_post'])) {
        print_r($_POST);
        exit;

        // Validate amounts match
        if ($_POST['amount_paid'] !== $_POST['confirm_amount_paid']) {
            $error = 'Amount and confirmation amount do not match!';
        } else {
            $date_parts = explode('/', $_POST['date_of_payment']);
            $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            
            $remittance_data = [
                'officer_id' => $_POST['officer'],
                'date' => $formatted_date,
                'amount_paid' => preg_replace('/[,]/', '', $_POST['amount_paid']),
                'no_of_receipts' => $_POST['no_of_receipts'],
                'category' => $_POST['category'],
                'posting_officer_id' => $staff['user_id'],
                'posting_officer_name' => $staff['full_name']
            ];
            
            $result = $manager->processRemittance($remittance_data);
            
            if ($result['success']) {
                $message = $result['message'];
                // Redirect to prevent resubmission
                header('Location: account_remittance.php?success=1');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome -<?php echo $_SESSION['first_name']; ?> | WEALTH CREATION ERP </title> 
    <meta http-equiv="Content-Type" name="description" content="Wealth Creation ERP Management System; text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Woobs Resources Ltd">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Custom styles from your original request, adapted for the static context */
        .dropdown-menu {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .dropdown-menu a {
            padding: 0.5rem 1rem;
            color: #374151;
            text-decoration: none;
            display: block;
        }
        .dropdown-menu a:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        /* Removed .navbar-nav .dropdown:hover .dropdown-menu as we rely purely on JS toggle */
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .btn-modern {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .table-modern {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .table-modern thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table-modern thead th {
            color: white !important; /* Force white text on header */
            border-bottom: none !important;
        }
        .table-modern tbody tr:hover {
            background-color: #f8fafc;
        }
        /* Custom DataTables adjustments to fit theme */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #2563eb !important;
            color: white !important;
            border: 1px solid #2563eb;
            border-radius: 0.25rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 0.25rem;
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div id="body" class="min-h-screen"> 
    
    <?php include('include/header.php'); ?>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="mb-8 border-b pb-4">
            <h1 class="text-3xl font-extrabold text-gray-900">
                <i class="fas fa-sun text-yellow-500 mr-2"></i> Dashboard Overview. 
            </h1> 
            
            <p class="mt-1 text-lg text-gray-500">
                Welcome! <?php echo $_SESSION['first_name'] ." ". $_SESSION['last_name']; ?> Your Dashboard is particular to your department's activities.
            </p>
        </div>
<?php if ($_SESSION['department'] == "Accounts"): ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-10">
            
            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-primary-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-primary-100 p-3 rounded-full">
                            <i class="fas fa-coins text-2xl text-primary-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Today's Total Collections
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['today']['total']) ? formatCurrency($stats['today']['total']) : 0; ?></p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-primary-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-primary-700 hover:text-primary-800 transition-colors">
                        View full report <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-success-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-success-100 p-3 rounded-full">
                            <i class="fas fa-calendar-day text-2xl text-success-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    This week
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['week']['total']) ? formatCurrency($stats['week']['total']) : 0; ?></p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-success-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-success-700 hover:text-success-800 transition-colors">
                        view report <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-warning-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-warning-100 p-3 rounded-full">
                            <i class="fas fa-exclamation-circle text-2xl text-warning-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Pending Transactions
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <?php echo count($pendingTransactions); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-warning-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-warning-700 hover:text-warning-800 transition-colors">
                        Review pending transaction <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-danger-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-danger-100 p-3 rounded-full">
                            <i class="fas fa-home text-2xl text-danger-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Officer Till Balance
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <?php echo isset($accountOfficerTotalTillbalance) ? formatCurrency($accountOfficerTotalTillbalance) : 0; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-danger-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-danger-700 hover:text-danger-800 transition-colors">
                     Till Balance <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Account Cash Remittance -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Cash Remittance Form -->
            <?php if ($_SESSION['department'] === 'Accounts'): ?>
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-red-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Cash Remittance</h3>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="posting_officer_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <input type="hidden" name="posting_officer_name" value="<?php echo $_SESSION['full_name']; ?>">

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Payment</label>
                            <input type="text" name="date_of_payment" 
                                   value="<?php echo date('d/m/Y'); ?>" 
                                   readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50">
                        </div>

                        <!-- Officer -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Officer</label>
                            <select name="officer" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select...</option>
                                <?php foreach ($officers as $officer): ?>
                                    <option value="<?php echo $officer['user_id']; ?>">
                                        <?php echo $officer['full_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Remitted</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₦</span>
                                <input type="password" name="amount_paid" id="amount_paid" required
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Confirm Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Amount Remitted</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₦</span>
                                <input type="text" name="confirm_amount_paid" id="confirm_amount_paid" required
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <span id="message" class="text-sm mt-1"></span>
                        </div>

                        <!-- Number of Receipts -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No of Receipts</label>
                            <input type="number" name="no_of_receipts" required min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select...</option>
                                <option value="Rent">Rent Collection</option>
                                <option value="Service Charge">Service Charge Collection</option>
                                <option value="Other Collection">Other Collection</option>
                            </select>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-3">
                            <button type="submit" name="btn_post"
                                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Post Remittance
                            </button>
                            <button type="reset"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-6">
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
                
                
            </div>
            </div>
            <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-6">
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
                </div>
            </div>
            <!-- Shop Renewal Summary -->
            
            <?php endif; ?>

            <!-- Remittance Summary -->
            <!-- <div class="<?php //echo $_SESSION['department'] === 'Accounts' ? 'lg:col-span-2' : 'lg:col-span-3'; ?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <?php foreach ($summary as $category => $data): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <?php echo $category; ?> Remittance
                        </h3>
                        <div class="space-y-2">
                            <?php 
                            $total_remitted = 0;
                            $total_posted = 0;
                            foreach ($data['remitted'] as $index => $officer): 
                                $total_remitted += $officer['amount_remitted'];
                                $posted_amount = isset($data['posted'][$index]['amount_posted']) ? $data['posted'][$index]['amount_posted'] : 0;
                                $total_posted += $posted_amount;
                            ?>
                            <div class="flex justify-between items-center text-sm">
                                <span class="font-medium"><?php echo $officer['officer_name']; ?></span>
                                <div class="text-right">
                                    <div class="text-green-600">₦<?php echo number_format($officer['amount_remitted']); ?></div>
                                    <div class="text-blue-600">₦<?php echo number_format($posted_amount); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="border-t pt-2 flex justify-between items-center font-bold">
                                <span>Total</span>
                                <div class="text-right">
                                    <div class="text-green-600">₦<?php echo number_format($total_remitted); ?></div>
                                    <div class="text-blue-600">₦<?php echo number_format($total_posted); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div> -->
            
        </div>

        <?php if($_SESSION['department'] === 'Accounts'): ?>
            
        <?php endif; ?>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="block sm:inline">Collection posted successfully!</span>
            </div>
        </div>
        <?php endif; ?>
<?php endif; ?>

        <!-- Performance Overview Cards -->
        <?php if ($_SESSION['department'] == "Wealth Creation"): ?>
            <!-- Achievement Banner -->
            <div class="bg-gradient-to-r from-<?php echo $achievement_color; ?>-400 to-<?php echo $achievement_color; ?>-600 rounded-xl shadow-lg p-8 mb-8 text-white">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-6 lg:mb-0">
                        <div class="flex items-center mb-4">
                            <div class="p-4 bg-white bg-opacity-20 rounded-full mr-4">
                                <i class="fas <?php echo $achievement_icon; ?> text-4xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold mb-2"><?php echo $achievement_level; ?></h1>
                                <p class="text-lg opacity-90"><?php echo $achievement_message; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <span class="text-sm"><?php echo $month_name . ' ' . $current_year; ?> Performance</span>
                            </div>
                            <?php if ($performance_summary): ?>
                            <div class="flex items-center">
                                <i class="fas fa-chart-line mr-2"></i>
                                <span class="text-sm"><?php echo number_format($performance_summary['avg_achievement_percentage'], 1); ?>% Target Achievement</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-5xl font-bold mb-2">₦<?php echo number_format($monthly_collections); ?></div>
                        <div class="text-lg opacity-90">This Month's Collections</div>
                        <?php if ($monthly_target > 0): ?>
                        <div class="mt-2 flex items-center justify-end">
                            <div class="w-32 bg-white bg-opacity-30 rounded-full h-3 mr-3">
                                <div class="bg-white h-3 rounded-full" style="width: <?php echo min(100, $monthly_progress); ?>%"></div>
                            </div>
                            <span class="text-sm">
                                <?php echo number_format($monthly_progress, 1); ?>% of target
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Annual Target -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Annual Target</p>
                            <p class="text-2xl font-bold text-gray-900">
                                ₦<?php echo number_format($annual_target); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                Projected for <?php echo $current_year; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Monthly Target -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-bullseye text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Monthly Target</p>
                            <p class="text-2xl font-bold text-gray-900">
                                ₦<?php echo number_format($monthly_target); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo $month_name; ?> target
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Daily Target -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-calendar-day text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Daily Target</p>
                            <p class="text-2xl font-bold text-gray-900">
                                ₦<?php echo number_format($daily_target); ?>
                            </p>
                            <p class="text-xs text-gray-500">Working days average</p>
                        </div>
                    </div>
                </div>

                <!-- Today's Progress -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center">
                        <?php
                            $progressColor = ($today_progress >= 100)
                                ? 'bg-green-100 text-green-600'
                                : (($today_progress >= 80)
                                    ? 'bg-yellow-100 text-yellow-600'
                                    : 'bg-red-100 text-red-600');
                            $textColor = ($today_progress >= 100)
                                ? 'text-green-600'
                                : (($today_progress >= 80)
                                    ? 'text-yellow-600'
                                    : 'text-red-600');
                        ?>
                        <div class="p-3 rounded-full <?php echo $progressColor; ?>">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Today's Progress</p>
                            <p class="text-2xl font-bold text-gray-900">
                                ₦<?php echo number_format($today_collections); ?>
                            </p>
                            <p class="text-xs <?php echo $textColor; ?>">
                                <?php echo number_format($today_progress, 1); ?>% of daily target
                            </p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Performance Indicators -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Performance Rating -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Rating</h3>
                    <div class="text-center">
                        <div class="w-24 h-24 mx-auto mb-4 rounded-full flex items-center justify-center text-white text-3xl font-bold <?php echo str_replace('text-', 'bg-', str_replace('100', '600', $rating['rating_class'])); ?>">
                            <?php echo substr($rating['rating'], 0, 1); ?>
                        </div>
                        <div class="text-xl font-bold text-gray-900"><?php echo $rating['rating']; ?></div>
                        <div class="text-sm text-gray-500"><?php echo number_format($rating['performance_ratio'], 1); ?>% of department average</div>
                        
                        <!-- Performance Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="<?php echo str_replace('100', '500', str_replace('text-', 'bg-', $rating['rating_class'])); ?> h-3 rounded-full transition-all duration-500" 
                                    style="width: <?php echo min(100, $rating['performance_ratio']); ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">Performance Index</div>
                        </div>
                    </div>
                </div>

                

                <!-- Remittance Status -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Today's Remittance</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Amount Remitted</span>
                            <span class="text-lg font-bold text-green-600">₦<?php echo number_format($remittance_data['amount_remitted']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Amount Posted</span>
                            <span class="text-lg font-bold text-blue-600">₦<?php echo number_format($remittance_data['amount_posted']); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-sm font-medium text-gray-700">Unposted Balance</span>
                            <span class="text-lg font-bold <?php echo $remittance_data['unposted'] > 0 ? 'text-orange-600' : 'text-gray-400'; ?>">
                                ₦<?php echo number_format($remittance_data['unposted']); ?>
                            </span>
                        </div>
                        
                        <?php if ($remittance_data['unposted'] <= 0): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                <span class="text-sm text-red-800">No unposted remittances available</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payments posting access -->
                <div class="bg-white rounded-lg shadow-md p-6">
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

            </div>
        <?php endif; ?>


        <!-- <div class="bg-white shadow-xl rounded-xl p-6 lg:p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-clock mr-2 text-primary-600"></i> Recent System Activity
            </h2>
            <p class="text-sm text-gray-500 mb-6">A list of the most recent actions taken across the system. (DataTables Enabled)</p>
            
            <div class="overflow-x-auto">
                <table id="activityTable" class="min-w-full divide-y divide-gray-200 table-modern">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Time
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Action
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Details
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10:30 AM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Accountant A</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Posted Transaction
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Rent for Shop B-205 (₦750,000)</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">09:15 AM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CEO</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved Lease
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">New Lease for Coldroom C-02</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yesterday, 4:45 PM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Leasing Officer B</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Customer Update
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Updated contact info for Mr. Uche</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 days ago</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Head of Audit</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Generated Report
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Trial Balance for Q3 2025</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 days ago</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Accountant C</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Posted Transaction
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Service Charge for Container 1</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 days ago</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Leasing Officer A</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Customer Registered
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">New customer: Mrs. Nkechi</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 text-right">
                <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 btn-modern">
                    View All Activity Log
                    <i class="fas fa-chevron-right ml-2 text-xs"></i>
                </a>
            </div>
        </div> -->
    </main>

    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> WEALTH CREATION ERP. All rights reserved. Developed by Woobs Resources Ltd.
            </div>
        </div>
    </footer>

<script>
    // Amount confirmation validation in cash remittance.
    document.getElementById('amount_paid').addEventListener('input', validateAmounts);
    document.getElementById('confirm_amount_paid').addEventListener('input', validateAmounts);

    function validateAmounts() {
        const amount = document.getElementById('amount_paid').value;
        const confirmAmount = document.getElementById('confirm_amount_paid').value;
        const message = document.getElementById('message');

        if (amount && confirmAmount) {
            if (amount === confirmAmount) {
                message.textContent = 'Confirmed!';
                message.className = 'text-sm mt-1 text-green-600';
            } else {
                message.textContent = 'Amount mismatch!';
                message.className = 'text-sm mt-1 text-red-600';
            }
        } else {
            message.textContent = '';
        }
    }

    // **JAVASCRIPT FOR DROPDOWN FUNCTIONALITY**
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            // Close all other dropdowns (to prevent multiple open menus)
            document.querySelectorAll('.relative > div.dropdown-menu').forEach(otherDropdown => {
                if (otherDropdown.id !== id) {
                    otherDropdown.classList.add('hidden');
                }
            });

            // Toggle the clicked one
            dropdown.classList.toggle('hidden');
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        // Check if the click is outside of any element that triggers or is a dropdown
        if (!event.target.closest('.relative button') && !event.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.relative > div.dropdown-menu').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });

    // **JAVASCRIPT FOR DATATABLES**
    $(document).ready(function() {
        $('#activityTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "pageLength": 5, // Show 5 entries by default
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "Filter records:",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No matching records found"
            },
            // Add Bootstrap styling classes (often needed for DataTables styling integration)
            "dom": 'lfrtip' 
        });
        
        // This is a common fix to make DataTables pagination buttons and search/length dropdowns visible 
        // when using a theme like Tailwind.
        $('.dataTables_wrapper').addClass('mt-4');
        $('.dataTables_length').addClass('mb-2');
        $('.dataTables_filter').addClass('mb-2');
    });
</script>

    </div>
</body>
</html>