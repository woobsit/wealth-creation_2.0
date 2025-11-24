<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../models/PaymentProcessor.php';
require_once '../helpers/session_helper.php';
require_once '../models/OfficerPerformanceAnalyzer.php';

// Check if user is logged in
requireLogin();
// print_r($_SESSION);
// exit;
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

class MPRDashboard {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get current month summary statistics
     */
    public function getCurrentMonthSummary() {
        $current_month = date('n');
        $current_year = date('Y');
        
        // Total revenue this month
        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as total_revenue
            FROM account_general_transaction_new 
            WHERE MONTH(date_of_payment) = :month 
            AND YEAR(date_of_payment) = :year
        ");
        $this->db->bind(':month', $current_month);
        $this->db->bind(':year', $current_year);
        $revenue = $this->db->single();
        
        // Total transactions this month
        $this->db->query("
            SELECT COUNT(*) as total_transactions
            FROM account_general_transaction_new 
            WHERE MONTH(date_of_payment) = :month 
            AND YEAR(date_of_payment) = :year
        ");
        $this->db->bind(':month', $current_month);
        $this->db->bind(':year', $current_year);
        $transactions = $this->db->single();
        
        // Active income lines
        $this->db->query("
            SELECT COUNT(DISTINCT income_line) as active_lines
            FROM account_general_transaction_new 
            WHERE MONTH(date_of_payment) = :month 
            AND YEAR(date_of_payment) = :year
            AND amount_paid > 0
        ");
        $this->db->bind(':month', $current_month);
        $this->db->bind(':year', $current_year);
        $active_lines = $this->db->single();
        
        // Growth rate (compared to last month)
        $prev_month = $current_month == 1 ? 12 : $current_month - 1;
        $prev_year = $current_month == 1 ? $current_year - 1 : $current_year;
        
        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as prev_revenue
            FROM account_general_transaction_new 
            WHERE MONTH(date_of_payment) = :prev_month 
            AND YEAR(date_of_payment) = :prev_year
        ");
        $this->db->bind(':prev_month', $prev_month);
        $this->db->bind(':prev_year', $prev_year);
        $prev_revenue = $this->db->single();
        
        $growth_rate = 0;
        if ($prev_revenue['prev_revenue'] > 0) {
            $growth_rate = (($revenue['total_revenue'] - $prev_revenue['prev_revenue']) / $prev_revenue['prev_revenue']) * 100;
        }
        
        return [
            'total_revenue' => $revenue['total_revenue'],
            'total_transactions' => $transactions['total_transactions'],
            'active_lines' => $active_lines['active_lines'],
            'growth_rate' => $growth_rate,
            'prev_revenue' => $prev_revenue['prev_revenue']
        ];
    }
    
    /**
     * Get quick alerts for management attention
     */
    public function getManagementAlerts() {
        $alerts = [];
        
        // Check for pending approvals
        $this->db->query("
            SELECT COUNT(*) as pending_count
            FROM account_general_transaction_new 
            WHERE approval_status = 'Pending'
        ");
        $pending = $this->db->single();
        
        if ($pending['pending_count'] > 100) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Pending Approvals',
                'message' => $pending['pending_count'] . ' transactions awaiting approval',
                'action' => 'Review approval workflow'
            ];
        }
        
        // Check for declining performance
        $current_month = date('n');
        $current_year = date('Y');
        $prev_month = $current_month == 1 ? 12 : $current_month - 1;
        $prev_year = $current_month == 1 ? $current_year - 1 : $current_year;
        
        $this->db->query("
            SELECT 
                (SELECT COALESCE(SUM(amount_paid), 0) FROM account_general_transaction_new WHERE MONTH(date_of_payment) = :current_month AND YEAR(date_of_payment) = :current_year) as current_total,
                (SELECT COALESCE(SUM(amount_paid), 0) FROM account_general_transaction_new WHERE MONTH(date_of_payment) = :prev_month AND YEAR(date_of_payment) = :prev_year) as prev_total
        ");
        $this->db->bind(':current_month', $current_month);
        $this->db->bind(':current_year', $current_year);
        $this->db->bind(':prev_month', $prev_month);
        $this->db->bind(':prev_year', $prev_year);
        $comparison = $this->db->single();
        
        if ($comparison['prev_total'] > 0) {
            $decline_rate = (($comparison['current_total'] - $comparison['prev_total']) / $comparison['prev_total']) * 100;
            if ($decline_rate < -15) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Revenue Decline Alert',
                    'message' => 'Revenue down ' . number_format(abs($decline_rate), 1) . '% from last month',
                    'action' => 'Investigate operational issues'
                ];
            }
        }
        
        return $alerts;
    }
}

$dashboard = new MPRDashboard();
$summary = $dashboard->getCurrentMonthSummary();
$alerts = $dashboard->getManagementAlerts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - MPR Analysis Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <!-- <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-2xl text-blue-600 mr-3"></i>
                        <h1 class="text-xl font-bold text-gray-900"><?php //echo APP_NAME; ?> - MPR Analysis Dashboard</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php //echo $staff['full_name']; ?></span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                        <?php //echo $staff['department']; ?>
                    </span>
                </div>
            </div>
        </div>
    </nav> -->
    <header class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                    <span class="text-xl font-bold text-gray-900">WC ERP</span>
                </div>
                <div class="ml-8">
                    <h1 class="text-xl font-bold text-gray-900">
                        <?php 
                            if ($staff['department'] === 'Accounts') {
                                echo 'Account DEPT: MPR Analysis Dashboard'. '<p class="text-sm text-gray-500"> View, Post, Approve & Manage lines of Income</p>';
                            }
                            if ($staff['department'] === 'Wealth Creation') {
                                echo 'WC DEPT: MPR Analysis Dashboard'. '<p class="text-sm text-gray-500"> View, Post, & Manage lines of Income</p>';
                            }
                            if ($staff['department'] === 'Audit/Inspections') {
                                echo 'AUDIT : MPR Analysis Dashboard'. '<p class="text-sm text-gray-500"> View, Approve, & Manage lines of Income</p>';
                            }
                        ?>
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-4">

                <!-- Transaction Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown('transactionDropdown')" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                        <i class="fas fa-exchange-alt text-blue-600"></i>
                        <span class="font-semibold text-sm">Transaction</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="transactionDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <?php  if ($staff['department'] === 'Wealth Creation') : ?>
                        
                        <a href="" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> View Transactions
                        </a>
                        
                        <?php endif; ?>
                        <!-- Dropdown forr accounts -->
                        <?php  if ($staff['department'] === 'Accounts') : ?>
                        
                        <a href="../accounts/account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> View Transactions
                        </a>
                        
                        <?php endif; ?>

                         <?php  if ($staff['department'] === 'Audit/Inspections') : ?>
                        
                        <a href="account/account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> View Transactions
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Financial Report Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown('reportDropdown')" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                        <i class="fas fa-file-invoice-dollar text-green-600"></i>
                        <span class="font-semibold text-sm">Financial Report</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="reportDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <a href="ledger.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-bar mr-2"></i> General Ledger
                        </a>
                        <!-- <a href="income_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-pie mr-2"></i> Trial Balance
                        </a>
                        <a href="income_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-pie mr-2"></i> Income Analysis
                        </a>
                        <a href="audit_log.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-history mr-2"></i> Audit Logs
                        </a> -->
                    </div>
                </div>

                <!-- User Profile Dropdown -->
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($staff['full_name']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($staff['department']) ?></div>
                </div>
                <div class="relative">
                    <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown('userDropdown')">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            <?= strtoupper($staff['full_name'][0]) ?>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <a href="index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">Management Performance Evaluation Dashboard</h2>
                        <p class="text-blue-100">Real-time insights and decision-making tools for revenue optimization</p>
                    </div>
                    
                    <!-- officer's MPR button -->
                    <div class="relative">
                        <a href="mpr_income_lines_officers.php" type="button" class="flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Officer's Performance Summary
                        </a>  
                    </div>

                    <div class="text-right">
                        <p class="text-sm text-blue-100">Current Month</p>
                        <p class="text-2xl font-bold"><?php echo date('F Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($summary['total_revenue']); ?></p>
                        <p class="text-xs <?php echo $summary['growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $summary['growth_rate'] >= 0 ? '+' : ''; ?><?php echo number_format($summary['growth_rate'], 1); ?>% from last month
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-receipt text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Transactions</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($summary['total_transactions']); ?></p>
                        <p class="text-xs text-gray-500">This month</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-stream text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Income Lines</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $summary['active_lines']; ?></p>
                        <p class="text-xs text-gray-500">Generating revenue</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Avg Daily Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($summary['total_revenue'] / date('j')); ?></p>
                        <p class="text-xs text-gray-500">Current month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Alerts -->
        <?php if (!empty($alerts)): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                Management Alerts
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($alerts as $alert): ?>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 
                    <?php echo $alert['type'] === 'danger' ? 'border-red-500' : 'border-yellow-500'; ?>">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-<?php echo $alert['type'] === 'danger' ? 'exclamation-circle text-red-500' : 'exclamation-triangle text-yellow-500'; ?> text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900"><?php echo $alert['title']; ?></h4>
                            <p class="text-sm text-gray-600 mt-1"><?php echo $alert['message']; ?></p>
                            <p class="text-xs text-gray-500 mt-2 italic"><?php echo $alert['action']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Dashboard Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
            <!-- Revenue Analysis Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Revenue Analysis</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="ledger/mpr_income_lines.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Monthly Collection Report</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>

                    <a href="ledger/index.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-balance-scale text-orange-600 mr-3"></i>
                            <span class="font-medium text-gray-900">MPR Dashboard</span>
                        </div>
                        <!-- <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span> -->
                    </a>
                    
                    <a href="mpr_detailed_analysis.php?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-search-plus text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Detailed Analysis</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="mpr_trends.php?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-trending-up text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Trend Analysis</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a> 
                </div>
            </div>
            <!-- Income Line Performance Section -->
            <!-- <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <i class="fas fa-stream text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Income Line Performance</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="income_line_analysis.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-chart-pie text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Performance by Income Line</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="top_performers.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-trophy text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Top Performing Lines</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="underperformers.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Underperforming Lines</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="seasonal_analysis.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-week text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Seasonal Patterns</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                </div>
            </div> -->

            <!-- Forecasting & Planning Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                        <i class="fas fa-crystal-ball text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Forecasting & Planning</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="mpr_forecasting.php?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-chart-area text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Revenue Forecasting</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="mpr_recommendations.php?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-lightbulb text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Strategic Recommendations</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="budget_planning.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-calculator text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Budget Planning</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="target_setting.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-bullseye text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Target Setting</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                </div>
            </div>

            <!-- Operational Insights Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-orange-100 rounded-lg mr-3">
                        <i class="fas fa-cogs text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Operational Insights</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="staff_performance.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-users text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Staff Performance</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="collection_efficiency.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-tachometer-alt text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Collection Efficiency</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="remittance_analysis.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-hand-holding-usd text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Remittance Analysis</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="approval_workflow.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-orange-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Approval Workflow</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                </div>
            </div>

            <!-- Financial Controls Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-red-100 rounded-lg mr-3">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Financial Controls</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="ledger.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-book text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">General Ledger</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    
                    <a href="variance_analysis.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Variance Analysis</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="audit_reports.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-clipboard-check text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Audit Reports</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="compliance_dashboard.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-gavel text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Compliance Dashboard</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                </div>
            </div>

            <!-- Executive Reports Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="p-2 bg-indigo-100 rounded-lg mr-3">
                        <i class="fas fa-file-alt text-indigo-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Executive Reports</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="executive_summary.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Executive Summary</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="board_presentation.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-presentation text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Board Presentation</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="kpi_dashboard.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-tachometer-alt text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">KPI Dashboard</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                    
                    <a href="monthly_report_generator.php" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-file-pdf text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Report Generator</span>
                        </div>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Coming Soon</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions Bar -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                Quick Actions
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="view_transactions.php" 
                   class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-list text-blue-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">View Transactions</span>
                </a>
                
                <a href="remittance_dashboard.php" 
                   class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <i class="fas fa-hand-holding-usd text-green-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Remittances</span>
                </a>
                
                <a href="approval_queue.php" 
                   class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                    <i class="fas fa-clock text-yellow-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Approval Queue</span>
                </a>
                
                <a href="daily_summary.php" 
                   class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <i class="fas fa-calendar-day text-purple-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Daily Summary</span>
                </a>
                
                <a href="export_data.php" 
                   class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-download text-indigo-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Export Data</span>
                </a>
                
                <a href="system_settings.php" 
                   class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-cog text-gray-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Settings</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add click tracking for analytics
            const buttons = document.querySelectorAll('a[href]');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    const text = this.textContent.trim();
                    
                    // Check if it's a "Coming Soon" link
                    if (this.querySelector('.bg-yellow-100')) {
                        e.preventDefault();
                        alert('This feature is coming soon! We are working on implementing ' + text + ' for you.');
                    }
                });
            });
        });
    </script>
    <?php include('../include/footer-script.php'); ?>
</body>
</html>