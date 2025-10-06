<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../helpers/session_helper.php';
require_once '../models/PaymentProcessor.php';
require_once '../models/OfficerPerformanceAnalyzer.php';
require_once '../models/OfficerTargetManager.php';
// Start session
requireLogin();
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

$processor = new PaymentProcessor();
$analyzer = new OfficerPerformanceAnalyzer();
$target_manager = new OfficerTargetManager();

$current_date = date('Y-m-d');
$current_month = date('n');
$current_year = date('Y');
$month_name = date('F');

// Get officer's performance data
$officer_info = $analyzer->getOfficerInfo($staff['user_id'], false);
$officer_targets = $target_manager->getOfficerTargets($staff['user_id'], $current_month, $current_year);
$performance_summary = $target_manager->getOfficerPerformanceSummary($staff['user_id'], $current_month, $current_year);
$daily_performance = $analyzer->getOfficerDailyPerformance($staff['user_id'], $current_month, $current_year, false);
$efficiency_metrics = $analyzer->getOfficerEfficiencyMetrics($staff['user_id'], $current_month, $current_year, false);
$rating = $analyzer->getOfficerRating($staff['user_id'], $current_month, $current_year, false);
$trends = $analyzer->getOfficerTrends($staff['user_id'], $current_month, $current_year, false);

// Get remittance balance
$remittance_data = $processor->getRemittanceBalance($staff['user_id'], $current_date);

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

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_post_collection'])) {
    // Temporary block
    echo "<script>alert('This section is not ready, go to payments');</script>";
    exit;
    // Validate receipt number
    $receipt_check = $processor->checkReceiptExists($_POST['receipt_no']);
    if ($receipt_check) {
        $error = "Receipt No: {$_POST['receipt_no']} has already been used!";
    } else {
        // Process payment data
        $date_parts = explode('/', $_POST['date_of_payment']);
        $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        
        // Get account info
        $debit_account_info = $processor->getAccountInfo('till', true);
        $credit_account_info = $processor->getAccountInfo($_POST['income_line'], true);
        
        $payment_data = [
            'date_of_payment' => $formatted_date,
            'ticket_category' => isset($_POST['ticket_category']) ? $_POST['ticket_category'] : '',
            'transaction_desc' => $_POST['income_line'] . ' Collection - ' . $staff['full_name'],
            'receipt_no' => $_POST['receipt_no'],
            'amount_paid' => preg_replace('/[,]/', '', $_POST['amount_paid']),
            'remitting_id' => $staff['user_id'],
            'remitting_staff' => $staff['full_name'],
            'posting_officer_id' => $staff['user_id'],
            'posting_officer_name' => $staff['full_name'],
            'leasing_post_status' => 'Pending',
            'approval_status' => '',
            'verification_status' => '',
            'debit_account' => $debit_account_info['acct_id'],
            'credit_account' => $credit_account_info['acct_id'],
            'db_debit_table' => $debit_account_info['acct_table_name'],
            'db_credit_table' => $credit_account_info['acct_table_name'],
            'no_of_tickets' => isset($_POST['no_of_tickets']) ? $_POST['no_of_tickets'] : 1,
            'remit_id' => isset($_POST['remit_id']) ? $_POST['remit_id'] : '',
            'income_line' => $_POST['income_line']
        ];
        
        $result = $processor->processCarParkPayment($payment_data);
        
        if ($result['success']) {
            $message = $result['message'];
            // Redirect to prevent resubmission
            header('Location: officer_dashboard.php?success=1');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

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

$sundays = $analyzer->getSundayPositions($current_month, $current_year);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - My Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#059669',
                        accent: '#dc2626',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex items-center mr-8">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">My Performance Dashboard</h1>
                    </div>
                    <a href="http://192.168.0.230/wealthcreation" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-dashboard mr-1"></i>
                        Dashboard
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-700">Welcome, <?php echo $staff['full_name']; ?></span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                            <?php echo $staff['department']; ?>
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500"><?php echo date('d M Y'); ?></span>
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($annual_target); ?></p>
                        <p class="text-xs text-gray-500">Projected for <?php echo $current_year; ?></p>
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
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($monthly_target); ?></p>
                        <p class="text-xs text-gray-500"><?php echo $month_name; ?> target</p>
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
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($daily_target); ?></p>
                        <p class="text-xs text-gray-500">Working days average</p>
                    </div>
                </div>
            </div>

            <!-- Today's Progress -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $today_progress >= 100 ? 'bg-green-100 text-green-600' : ($today_progress >= 80 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'); ?>">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Today's Progress</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($today_collections); ?></p>
                        <p class="text-xs <?php echo $today_progress >= 100 ? 'text-green-600' : ($today_progress >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
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

            <!-- Monthly Progress -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Progress</h3>
                <div class="space-y-4">
                    <?php if (!empty($officer_targets)): ?>
                        <?php foreach ($officer_targets as $target): ?>
                        <?php 
                        $target_achievement = 0;
                        foreach ($performance_summary ? [$performance_summary] : [] as $perf) {
                            if ($target['acct_id'] === $target['acct_id']) {
                                $target_achievement = isset($perf['avg_achievement_percentage']) ? $perf['avg_achievement_percentage'] : 0;
                                break;
                            }
                        }
                        ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo $target['acct_desc']; ?></div>
                                <div class="text-xs text-gray-500">₦<?php echo number_format($target['monthly_target']); ?> target</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold <?php echo $target_achievement >= 100 ? 'text-green-600' : ($target_achievement >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($target_achievement, 1); ?>%
                                </div>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="<?php echo $target_achievement >= 100 ? 'bg-green-500' : ($target_achievement >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-2 rounded-full" 
                                         style="width: <?php echo min(100, $target_achievement); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-gray-500">
                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                            <p>No targets set for this month</p>
                        </div>
                    <?php endif; ?>
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
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="block sm:inline">Collection posted successfully!</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Collection Posting Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-6">
                        <div class="p-3 bg-blue-100 rounded-lg mr-4">
                            <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Post New Collection</h3>
                            <p class="text-sm text-gray-600">Record your collections here</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="posting_officer_id" value="<?php echo $staff['user_id']; ?>">
                        <input type="hidden" name="posting_officer_name" value="<?php echo $staff['full_name']; ?>">

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Collection</label>
                            <input type="text" name="date_of_payment" 
                                   value="<?php echo date('d/m/Y'); ?>" 
                                   readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50">
                        </div>

                        <!-- Income Line -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Income Line</label>
                            <select name="income_line" id="income_line" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select income line...</option>
                                <option value="carpark">Car Park Collection</option>
                                <option value="loading">Loading & Offloading</option>
                                <option value="hawkers">Hawkers Collection</option>
                                <option value="wheelbarrow">WheelBarrow Collection</option>
                                <option value="daily_trade">Daily Trade Collection</option>
                                <option value="abattoir">Abattoir Collection</option>
                                <option value="overnight_parking">Overnight Parking</option>
                                <option value="scroll_board">Scroll Board</option>
                                <option value="other_pos">Other POS Collection</option>
                                <option value="car_sticker">Car Sticker</option>
                            </select>
                        </div>

                        <!-- Ticket Category (for applicable income lines) -->
                        <div id="ticket_category_section" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Category (₦)</label>
                            <select name="ticket_category" id="ticket_category" onchange="calculateAmount()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="500">500</option>
                                <option value="700">700</option>
                                <option value="1000">1000</option>
                            </select>
                        </div>

                        <!-- Number of Tickets -->
                        <div id="tickets_section" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">No of Tickets</label>
                            <input type="number" name="no_of_tickets" id="no_of_tickets" min="1" 
                                   onchange="calculateAmount()"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount Collected (₦)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₦</span>
                                <input type="text" name="amount_paid" id="amount_paid" required
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Receipt Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Receipt No</label>
                            <input type="text" name="receipt_no" required maxlength="7" pattern="^\d{7}$"
                                   placeholder="7-digit receipt number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Remittance Selection -->
                        <?php if ($remittance_data['unposted'] > 0): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Remittance</label>
                            <select name="remit_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select remittance...</option>
                                <option value="<?php echo $remittance_data['remit_id']; ?>">
                                    <?php echo $remittance_data['date'] . ': ₦' . number_format($remittance_data['unposted']); ?> available
                                </option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <?php if ($remittance_data['unposted'] <= 0): ?>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl mb-2"></i>
                                    <p class="text-red-800 font-medium">No unposted remittances available</p>
                                    <p class="text-red-600 text-sm">Please remit cash before posting collections</p>
                                </div>
                            <?php else: ?>
                                <button type="submit" name="btn_post_collection"
                                        class="w-full px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Post Collection
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Performance Charts and Analytics -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Daily Performance Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Performance This Month</h3>
                    <div class="relative h-48">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- 6-Month Trend -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">6-Month Performance Trend</h3>
                    <div class="relative h-48">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target Breakdown Table -->
        <?php if (!empty($officer_targets)): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">My Targets & Performance</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Target</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Daily Target</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achieved</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($officer_targets as $target): ?>
                        <?php 
                        // Get actual achievement for this target
                        $achieved_amount = 0;
                        $achievement_percentage = 0;
                        
                        $db->query("
                            SELECT COALESCE(SUM(amount_paid), 0) as achieved
                            FROM account_general_transaction_new 
                            WHERE remitting_id = :officer_id
                            AND credit_account = :acct_id
                            AND MONTH(date_of_payment) = :month 
                            AND YEAR(date_of_payment) = :year
                            AND (approval_status = 'Approved' OR approval_status = '')
                        ");
                        $db->bind(':officer_id', $staff['user_id']);
                        $db->bind(':acct_id', $target['acct_id']);
                        $db->bind(':month', $current_month);
                        $db->bind(':year', $current_year);
                        $result = $db->single();
                        $achieved_amount = $result['achieved'];
                        $achievement_percentage = $target['monthly_target'] > 0 ? ($achieved_amount / $target['monthly_target']) * 100 : 0;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $target['acct_desc']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($target['monthly_target']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($target['daily_target']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($achieved_amount); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="<?php echo $achievement_percentage >= 100 ? 'bg-green-500' : ($achievement_percentage >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-2 rounded-full transition-all duration-500" 
                                             style="width: <?php echo min(100, $achievement_percentage); ?>%"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-gray-500">
                                        <?php echo number_format($achievement_percentage, 1); ?>%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($achievement_percentage >= 120): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-crown mr-1"></i>Excellent
                                    </span>
                                <?php elseif ($achievement_percentage >= 100): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>Target Met
                                    </span>
                                <?php elseif ($achievement_percentage >= 80): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <i class="fas fa-arrow-up mr-1"></i>Good
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation mr-1"></i>Below Target
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Statistics</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $efficiency_metrics['working_days']; ?></div>
                    <div class="text-sm text-gray-500">Working Days</div>
                    <div class="text-xs text-gray-400"><?php echo number_format($efficiency_metrics['attendance_rate'], 1); ?>% attendance</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo number_format($efficiency_metrics['total_transactions']); ?></div>
                    <div class="text-sm text-gray-500">Total Transactions</div>
                    <div class="text-xs text-gray-400">₦<?php echo number_format($efficiency_metrics['avg_transaction_amount']); ?> avg</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">₦<?php echo number_format($efficiency_metrics['daily_average']); ?></div>
                    <div class="text-sm text-gray-500">Daily Average</div>
                    <div class="text-xs text-gray-400">This month</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">₦<?php echo number_format($efficiency_metrics['max_transaction']); ?></div>
                    <div class="text-sm text-gray-500">Highest Transaction</div>
                    <div class="text-xs text-gray-400">Personal best</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show/hide form sections based on income line
        document.getElementById('income_line').addEventListener('change', function() {
            const ticketSection = document.getElementById('ticket_category_section');
            const ticketsSection = document.getElementById('tickets_section');
            
            if (['carpark', 'loading', 'hawkers', 'wheelbarrow', 'daily_trade'].includes(this.value)) {
                ticketSection.classList.remove('hidden');
                ticketsSection.classList.remove('hidden');
            } else {
                ticketSection.classList.add('hidden');
                ticketsSection.classList.add('hidden');
            }
        });

        // Calculate amount based on tickets
        function calculateAmount() {
            const ticketCategory = parseFloat(document.getElementById('ticket_category').value) || 0;
            const noOfTickets = parseFloat(document.getElementById('no_of_tickets').value) || 0;
            const totalAmount = ticketCategory * noOfTickets;
            
            if (totalAmount > 0) {
                document.getElementById('amount_paid').value = totalAmount.toLocaleString();
            }
        }

        // Format amount input
        document.getElementById('amount_paid').addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString();
            }
        });

        // Daily Performance Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = <?php echo json_encode(array_values($daily_performance)); ?>;
        const dailyLabels = <?php echo json_encode(array_keys($daily_performance)); ?>;
        const sundayPositions = <?php echo json_encode($sundays); ?>;
        const dailyTargetLine = Array(dailyLabels.length).fill(<?php echo $daily_target; ?>);
        
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'Daily Collections (₦)',
                    data: dailyData,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: dailyLabels.map(day => 
                        sundayPositions.includes(parseInt(day)) ? 'rgba(239, 68, 68, 1)' : 'rgba(59, 130, 246, 1)'
                    ),
                    pointRadius: 5,
                    pointHoverRadius: 8
                }, {
                    label: 'Daily Target',
                    data: dailyTargetLine,
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const isSunday = sundayPositions.includes(parseInt(context.label));
                                return context.dataset.label + ': ₦' + context.parsed.y.toLocaleString() + 
                                       (isSunday ? ' (Sunday)' : '');
                            }
                        }
                    }
                }
            }
        });

        // 6-Month Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php echo json_encode($trends); ?>;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(item => item.month_name),
                datasets: [{
                    label: 'Monthly Collections (₦)',
                    data: trendData.map(item => item.total),
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Collections: ₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll('[style*="width:"]');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>