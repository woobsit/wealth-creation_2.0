<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../helpers/session_helper.php';
require_once '../models/OfficerPerformanceAnalyzer.php';
require_once '../models/OfficerRealTimeTargetManager.php';

// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

$analyzer = new OfficerPerformanceAnalyzer();

class AnnualPerformanceAnalyzer {
    private $db;
    private $target_manager;
    private $performance_analyzer;
    
    public function __construct() {
        $this->db = new Database();
        $this->target_manager = new OfficerRealTimeTargetManager();
        $this->performance_analyzer = new OfficerPerformanceAnalyzer();
    }
    
    /**
     * Get annual performance summary for all officers
     */
    public function getAnnualPerformanceSummary($year) {
        $officers = [];
        
        // Get all officers with targets in the year
        $this->db->query("
            SELECT DISTINCT officer_id, officer_name, department
            FROM officer_monthly_targets 
            WHERE target_year = :year 
            AND status = 'Active'
            ORDER BY officer_name ASC
        ");
        $this->db->bind(':year', $year);
        $officer_list = $this->db->resultSet();
        
        foreach ($officer_list as $officer) {
            $annual_data = $this->calculateAnnualPerformance($officer['officer_id'], $year);
            $quarterly_data = $this->calculateQuarterlyPerformance($officer['officer_id'], $year);
            $half_yearly_data = $this->calculateHalfYearlyPerformance($officer['officer_id'], $year);
            
            $officers[] = [
                'officer_info' => $officer,
                'annual' => $annual_data,
                'quarterly' => $quarterly_data,
                'half_yearly' => $half_yearly_data,
                'monthly_breakdown' => $this->getMonthlyBreakdown($officer['officer_id'], $year)
            ];
        }
        
        // Sort by annual performance score
        usort($officers, function($a, $b) {
            if ($b['annual']['overall_score'] == $a['annual']['overall_score']) {
                return 0;
            }
            return ($b['annual']['overall_score'] > $a['annual']['overall_score']) ? 1 : -1;
        });

        
        return $officers;
    }
    
    /**
     * Calculate annual performance for an officer
     */
    private function calculateAnnualPerformance($officer_id, $year) {
        $this->db->query("
            SELECT 
                SUM(opt.monthly_target) as total_annual_target,
                SUM(COALESCE(actual_data.achieved_amount, 0)) as total_annual_achieved,
                COUNT(opt.id) as total_assigned_lines,
                AVG(COALESCE(actual_data.working_days, 0)) as avg_working_days,
                SUM(COALESCE(actual_data.total_transactions, 0)) as total_transactions
            FROM officer_monthly_targets opt
            LEFT JOIN (
                SELECT 
                    t.remitting_id,
                    t.credit_account,
                    MONTH(t.date_of_payment) as month,
                    SUM(t.amount_paid) as achieved_amount,
                    COUNT(DISTINCT t.date_of_payment) as working_days,
                    COUNT(t.id) as total_transactions
                FROM account_general_transaction_new t
                WHERE YEAR(t.date_of_payment) = :year
                AND (t.approval_status = 'Approved' OR t.approval_status = '')
                GROUP BY t.remitting_id, t.credit_account, MONTH(t.date_of_payment)
            ) actual_data ON opt.officer_id = actual_data.remitting_id 
                AND opt.acct_id = actual_data.credit_account
                AND opt.target_month = actual_data.month
            WHERE opt.officer_id = :officer_id
            AND opt.target_year = :year
            AND opt.status = 'Active'
        ");
        
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':year', $year);
        
        $result = $this->db->single();
        
        $achievement_percentage = $result['total_annual_target'] > 0 ? 
            ($result['total_annual_achieved'] / $result['total_annual_target']) * 100 : 0;
        
        $overall_score = $this->calculatePerformanceScore($achievement_percentage);
        $grade = $this->calculatePerformanceGrade($achievement_percentage);
        
        return array(
            'total_target' => isset($result['total_annual_target']) ? $result['total_annual_target'] : 0,
            'total_achieved' => isset($result['total_annual_achieved']) ? $result['total_annual_achieved'] : 0,
            'achievement_percentage' => $achievement_percentage,
            'overall_score' => $overall_score,
            'grade' => $grade,
            'total_assigned_lines' => isset($result['total_assigned_lines']) ? $result['total_assigned_lines'] : 0,
            'avg_working_days' => isset($result['avg_working_days']) ? $result['avg_working_days'] : 0,
            'total_transactions' => isset($result['total_transactions']) ? $result['total_transactions'] : 0,
            'variance' => (isset($result['total_annual_achieved']) ? $result['total_annual_achieved'] : 0) 
                        - (isset($result['total_annual_target']) ? $result['total_annual_target'] : 0)
        );

    }
    
    /**
     * Calculate quarterly performance for an officer
     */
    private function calculateQuarterlyPerformance($officer_id, $year) {
        $quarters = [
            'Q1' => [1, 2, 3],
            'Q2' => [4, 5, 6],
            'Q3' => [7, 8, 9],
            'Q4' => [10, 11, 12]
        ];
        
        $quarterly_data = [];
        
        foreach ($quarters as $quarter => $months) {
            $month_list = implode(',', $months);
            
            $this->db->query("
                SELECT 
                    SUM(opt.monthly_target) as quarter_target,
                    SUM(COALESCE(actual_data.achieved_amount, 0)) as quarter_achieved,
                    COUNT(opt.id) as assigned_lines,
                    AVG(COALESCE(actual_data.working_days, 0)) as avg_working_days
                FROM officer_monthly_targets opt
                LEFT JOIN (
                    SELECT 
                        t.remitting_id,
                        t.credit_account,
                        MONTH(t.date_of_payment) as month,
                        SUM(t.amount_paid) as achieved_amount,
                        COUNT(DISTINCT t.date_of_payment) as working_days
                    FROM account_general_transaction_new t
                    WHERE YEAR(t.date_of_payment) = :year
                    AND MONTH(t.date_of_payment) IN ($month_list)
                    AND (t.approval_status = 'Approved' OR t.approval_status = '')
                    GROUP BY t.remitting_id, t.credit_account, MONTH(t.date_of_payment)
                ) actual_data ON opt.officer_id = actual_data.remitting_id 
                    AND opt.acct_id = actual_data.credit_account
                    AND opt.target_month = actual_data.month
                WHERE opt.officer_id = :officer_id
                AND opt.target_year = :year
                AND opt.target_month IN ($month_list)
                AND opt.status = 'Active'
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':year', $year);
            
            $result = $this->db->single();
            
            $achievement_percentage = $result['quarter_target'] > 0 ? 
                ($result['quarter_achieved'] / $result['quarter_target']) * 100 : 0;
            
            $quarterly_data[$quarter] = array(
                'target' => isset($result['quarter_target']) ? $result['quarter_target'] : 0,
                'achieved' => isset($result['quarter_achieved']) ? $result['quarter_achieved'] : 0,
                'achievement_percentage' => $achievement_percentage,
                'score' => $this->calculatePerformanceScore($achievement_percentage),
                'grade' => $this->calculatePerformanceGrade($achievement_percentage),
                'assigned_lines' => isset($result['assigned_lines']) ? $result['assigned_lines'] : 0,
                'avg_working_days' => isset($result['avg_working_days']) ? $result['avg_working_days'] : 0
            );

        }
        
        return $quarterly_data;
    }
    
    /**
     * Calculate half-yearly performance for an officer
     */
    private function calculateHalfYearlyPerformance($officer_id, $year) {
        $halves = [
            'H1' => [1, 2, 3, 4, 5, 6],
            'H2' => [7, 8, 9, 10, 11, 12]
        ];
        
        $half_yearly_data = [];
        
        foreach ($halves as $half => $months) {
            $month_list = implode(',', $months);
            
            $this->db->query("
                SELECT 
                    SUM(opt.monthly_target) as half_target,
                    SUM(COALESCE(actual_data.achieved_amount, 0)) as half_achieved,
                    COUNT(opt.id) as assigned_lines,
                    AVG(COALESCE(actual_data.working_days, 0)) as avg_working_days
                FROM officer_monthly_targets opt
                LEFT JOIN (
                    SELECT 
                        t.remitting_id,
                        t.credit_account,
                        MONTH(t.date_of_payment) as month,
                        SUM(t.amount_paid) as achieved_amount,
                        COUNT(DISTINCT t.date_of_payment) as working_days
                    FROM account_general_transaction_new t
                    WHERE YEAR(t.date_of_payment) = :year
                    AND MONTH(t.date_of_payment) IN ($month_list)
                    AND (t.approval_status = 'Approved' OR t.approval_status = '')
                    GROUP BY t.remitting_id, t.credit_account, MONTH(t.date_of_payment)
                ) actual_data ON opt.officer_id = actual_data.remitting_id 
                    AND opt.acct_id = actual_data.credit_account
                    AND opt.target_month = actual_data.month
                WHERE opt.officer_id = :officer_id
                AND opt.target_year = :year
                AND opt.target_month IN ($month_list)
                AND opt.status = 'Active'
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':year', $year);
            
            $result = $this->db->single();
            
            $achievement_percentage = $result['half_target'] > 0 ? 
                ($result['half_achieved'] / $result['half_target']) * 100 : 0;
            
            $half_yearly_data[$half] = array(
                'target' => isset($result['half_target']) ? $result['half_target'] : 0,
                'achieved' => isset($result['half_achieved']) ? $result['half_achieved'] : 0,
                'achievement_percentage' => $achievement_percentage,
                'score' => $this->calculatePerformanceScore($achievement_percentage),
                'grade' => $this->calculatePerformanceGrade($achievement_percentage),
                'assigned_lines' => isset($result['assigned_lines']) ? $result['assigned_lines'] : 0,
                'avg_working_days' => isset($result['avg_working_days']) ? $result['avg_working_days'] : 0
            );

        }
        
        return $half_yearly_data;
    }
    
    /**
     * Get monthly breakdown for an officer
     */
    private function getMonthlyBreakdown($officer_id, $year) {
        $monthly_data = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $this->db->query("
                SELECT 
                    SUM(opt.monthly_target) as month_target,
                    SUM(COALESCE(actual_data.achieved_amount, 0)) as month_achieved,
                    COUNT(opt.id) as assigned_lines
                FROM officer_monthly_targets opt
                LEFT JOIN (
                    SELECT 
                        t.remitting_id,
                        t.credit_account,
                        SUM(t.amount_paid) as achieved_amount
                    FROM account_general_transaction_new t
                    WHERE MONTH(t.date_of_payment) = :month
                    AND YEAR(t.date_of_payment) = :year
                    AND (t.approval_status = 'Approved' OR t.approval_status = '')
                    GROUP BY t.remitting_id, t.credit_account
                ) actual_data ON opt.officer_id = actual_data.remitting_id 
                    AND opt.acct_id = actual_data.credit_account
                WHERE opt.officer_id = :officer_id
                AND opt.target_month = :month
                AND opt.target_year = :year
                AND opt.status = 'Active'
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            
            $result = $this->db->single();
            
            $achievement_percentage = $result['month_target'] > 0 ? 
                ($result['month_achieved'] / $result['month_target']) * 100 : 0;
            
            $monthly_data[date('M', mktime(0, 0, 0, $month, 1))] = array(
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'target' => isset($result['month_target']) ? $result['month_target'] : 0,
                'achieved' => isset($result['month_achieved']) ? $result['month_achieved'] : 0,
                'achievement_percentage' => $achievement_percentage,
                'score' => $this->calculatePerformanceScore($achievement_percentage),
                'grade' => $this->calculatePerformanceGrade($achievement_percentage),
                'assigned_lines' => isset($result['assigned_lines']) ? $result['assigned_lines'] : 0
            );

        }
        
        return $monthly_data;
    }
    
    /**
     * Calculate performance score based on achievement percentage
     */
    private function calculatePerformanceScore($achievement_percentage) {
        if ($achievement_percentage >= 150) return 100;
        if ($achievement_percentage >= 120) return 90;
        if ($achievement_percentage >= 100) return 80;
        if ($achievement_percentage >= 80) return 70;
        if ($achievement_percentage >= 60) return 60;
        if ($achievement_percentage >= 40) return 50;
        if ($achievement_percentage >= 20) return 40;
        return 30;
    }
    
    /**
     * Calculate performance grade based on achievement percentage
     */
    private function calculatePerformanceGrade($achievement_percentage) {
        if ($achievement_percentage >= 150) return 'A+';
        if ($achievement_percentage >= 120) return 'A';
        if ($achievement_percentage >= 100) return 'B+';
        if ($achievement_percentage >= 80) return 'B';
        if ($achievement_percentage >= 60) return 'C+';
        if ($achievement_percentage >= 40) return 'C';
        if ($achievement_percentage >= 20) return 'D';
        return 'F';
    }
    
    /**
     * Get performance trends over multiple years
     */
    public function getPerformanceTrends($officer_id, $years) {
        $trends = [];
        
        foreach ($years as $year) {
            $annual_data = $this->calculateAnnualPerformance($officer_id, $year);
            $trends[] = [
                'year' => $year,
                'data' => $annual_data
            ];
        }
        
        return $trends;
    }
    
    /**
     * Get department comparison for the year
     */
    public function getDepartmentAnnualComparison($year) {
        $this->db->query("
            SELECT 
                opt.department,
                COUNT(DISTINCT opt.officer_id) as officer_count,
                SUM(opt.monthly_target) as total_department_target,
                SUM(COALESCE(actual_data.achieved_amount, 0)) as total_department_achieved,
                AVG(CASE 
                    WHEN opt.monthly_target > 0 THEN 
                        (COALESCE(actual_data.achieved_amount, 0) / opt.monthly_target) * 100
                    ELSE 0
                END) as avg_achievement_percentage
            FROM officer_monthly_targets opt
            LEFT JOIN (
                SELECT 
                    t.remitting_id,
                    t.credit_account,
                    MONTH(t.date_of_payment) as month,
                    SUM(t.amount_paid) as achieved_amount
                FROM account_general_transaction_new t
                WHERE YEAR(t.date_of_payment) = :year
                AND (t.approval_status = 'Approved' OR t.approval_status = '')
                GROUP BY t.remitting_id, t.credit_account, MONTH(t.date_of_payment)
            ) actual_data ON opt.officer_id = actual_data.remitting_id 
                AND opt.acct_id = actual_data.credit_account
                AND opt.target_month = actual_data.month
            WHERE opt.target_year = :year
            AND opt.status = 'Active'
            GROUP BY opt.department
            ORDER BY avg_achievement_percentage DESC
        ");
        
        $this->db->bind(':year', $year);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get top performers for the year
     */
    public function getTopPerformersAnnual($year, $limit = 10) {
        $officers = $this->getAnnualPerformanceSummary($year);
        return array_slice($officers, 0, $limit);
    }
    
    /**
     * Get performance distribution statistics
     */
    public function getPerformanceDistribution($year) {
        $officers = $this->getAnnualPerformanceSummary($year);
        
        $distribution = [
            'A+' => 0, 'A' => 0, 'B+' => 0, 'B' => 0,
            'C+' => 0, 'C' => 0, 'D' => 0, 'F' => 0
        ];
        
        foreach ($officers as $officer) {
            $grade = $officer['annual']['grade'];
            $distribution[$grade]++;
        }
        
        return $distribution;
    }
}

$analyzer = new AnnualPerformanceAnalyzer();

// Get parameters
$selected_year   = isset($_GET['year']) ? $_GET['year'] : date('Y');
$view_type       = isset($_GET['view']) ? $_GET['view'] : 'annual';
$officer_filter  = isset($_GET['officer_id']) ? $_GET['officer_id'] : null;


// Get data
$annual_performance = $analyzer->getAnnualPerformanceSummary($selected_year);
$department_comparison = $analyzer->getDepartmentAnnualComparison($selected_year);
$top_performers = $analyzer->getTopPerformersAnnual($selected_year, 5);
$performance_distribution = $analyzer->getPerformanceDistribution($selected_year);

// Filter by officer if specified
if ($officer_filter) {
    $annual_performance = array_filter($annual_performance, function($officer) use ($officer_filter) {
        return $officer['officer_info']['officer_id'] == $officer_filter;
    });
}

// Calculate overall statistics
$total_officers = count($annual_performance);
$total_annual_target = array_sum(array_column(array_column($annual_performance, 'annual'), 'total_target'));
$total_annual_achieved = array_sum(array_column(array_column($annual_performance, 'annual'), 'total_achieved'));
$overall_achievement = $total_annual_target > 0 ? ($total_annual_achieved / $total_annual_target) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Annual Officer Performance Analysis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="mpr_income_lines_officers.php" class="text-blue-600 hover:text-blue-800 mr-4">← Back to Monthly Analysis</a>
                    <h1 class="text-xl font-bold text-gray-900">Annual Performance Analysis</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700"><?php echo $selected_year; ?> Performance Review</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0">
                        <h2 class="text-2xl font-bold text-gray-900">Annual Officer Performance Dashboard</h2>
                        <p class="text-gray-600">Comprehensive yearly performance analysis with quarterly and half-yearly breakdowns</p>
                    </div>
                    
                    <!-- Filter Controls -->
                    <form method="GET" class="flex flex-col sm:flex-row gap-4">
                        <select name="year" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <?php for ($y = date('Y') - 3; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        
                        <select name="view" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="annual" <?php echo $view_type === 'annual' ? 'selected' : ''; ?>>Annual View</option>
                            <option value="quarterly" <?php echo $view_type === 'quarterly' ? 'selected' : ''; ?>>Quarterly View</option>
                            <option value="half_yearly" <?php echo $view_type === 'half_yearly' ? 'selected' : ''; ?>>Half-Yearly View</option>
                        </select>
                        
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Load Analysis
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Officers</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_officers; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-bullseye text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Annual Target</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_annual_target); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Annual Achieved</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_annual_achieved); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $overall_achievement >= 100 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Overall Achievement</p>
                        <p class="text-2xl font-bold <?php echo $overall_achievement >= 100 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($overall_achievement, 1); ?>%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Performance Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Grade Distribution</h3>
                <div class="relative h-64">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- Department Comparison Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Performance Comparison</h3>
                <div class="relative h-64">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Performance Analysis Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <?php echo ucfirst(str_replace('_', '-', $view_type)); ?> Performance Analysis - <?php echo $selected_year; ?>
                    </h3>
                    <div class="flex space-x-2">
                        <button onclick="exportToExcel()" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                            <i class="fas fa-file-excel mr-1"></i>Excel
                        </button>
                        <button onclick="printReport()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            <i class="fas fa-print mr-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table id="performanceTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            
                            <?php if ($view_type === 'annual'): ?>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Target</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Achieved</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement %</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Grade</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <?php elseif ($view_type === 'quarterly'): ?>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Q1</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Q2</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Q3</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Q4</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Score</th>
                            <?php else: ?>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">H1 (Jan-Jun)</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">H2 (Jul-Dec)</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Best Half</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Consistency</th>
                            <?php endif; ?>
                            
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance Bar</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($annual_performance as $index => $officer_data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold
                                    <?php echo $index === 0 ? 'bg-yellow-500' : ($index === 1 ? 'bg-gray-500' : ($index === 2 ? 'bg-orange-500' : 'bg-blue-500')); ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        <?php echo strtoupper(substr($officer_data['officer_info']['officer_name'], 0, 2)); ?>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $officer_data['officer_info']['officer_name']; ?></div>
                                        <div class="text-xs text-gray-500"><?php echo $officer_data['annual']['total_assigned_lines']; ?> lines assigned</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $officer_data['officer_info']['department'] === 'Wealth Creation' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo $officer_data['officer_info']['department']; ?>
                                </span>
                            </td>
                            
                            <?php if ($view_type === 'annual'): ?>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    ₦<?php echo number_format($officer_data['annual']['total_target']); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    ₦<?php echo number_format($officer_data['annual']['total_achieved']); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="text-lg font-bold <?php echo $officer_data['annual']['achievement_percentage'] >= 100 ? 'text-green-600' : ($officer_data['annual']['achievement_percentage'] >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo number_format($officer_data['annual']['achievement_percentage'], 1); ?>%
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                        <?php 
                                        $grade_class = 'bg-gray-100 text-gray-800';
                                        switch($officer_data['annual']['grade']) {
                                            case 'A+':
                                            case 'A':
                                                $grade_class = 'bg-green-100 text-green-800';
                                                break;
                                            case 'B+':
                                            case 'B':
                                                $grade_class = 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'C+':
                                            case 'C':
                                                $grade_class = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'D':
                                                $grade_class = 'bg-orange-100 text-orange-800';
                                                break;
                                            case 'F':
                                                $grade_class = 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        echo $grade_class;
                                        ?>">
                                        <?php echo $officer_data['annual']['grade']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                    <span class="<?php echo $officer_data['annual']['variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                        <?php echo $officer_data['annual']['variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($officer_data['annual']['variance']); ?>
                                    </span>
                                </td>
                            <?php elseif ($view_type === 'quarterly'): ?>
                                <?php foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarter): ?>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-bold <?php echo $officer_data['quarterly'][$quarter]['achievement_percentage'] >= 100 ? 'text-green-600' : ($officer_data['quarterly'][$quarter]['achievement_percentage'] >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo number_format($officer_data['quarterly'][$quarter]['achievement_percentage'], 1); ?>%
                                    </div>
                                    <div class="text-xs text-gray-500"><?php echo $officer_data['quarterly'][$quarter]['grade']; ?></div>
                                </td>
                                <?php endforeach; ?>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="text-lg font-bold text-gray-900">
                                        <?php echo number_format(array_sum(array_column($officer_data['quarterly'], 'score')) / 4, 1); ?>
                                    </div>
                                </td>
                            <?php else: ?>
                                <?php foreach (['H1', 'H2'] as $half): ?>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-bold <?php echo $officer_data['half_yearly'][$half]['achievement_percentage'] >= 100 ? 'text-green-600' : ($officer_data['half_yearly'][$half]['achievement_percentage'] >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo number_format($officer_data['half_yearly'][$half]['achievement_percentage'], 1); ?>%
                                    </div>
                                    <div class="text-xs text-gray-500"><?php echo $officer_data['half_yearly'][$half]['grade']; ?></div>
                                    <div class="text-xs text-gray-400">₦<?php echo number_format($officer_data['half_yearly'][$half]['achieved']); ?></div>
                                </td>
                                <?php endforeach; ?>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-bold text-purple-600">
                                        <?php 
                                        $best_half = $officer_data['half_yearly']['H1']['achievement_percentage'] > $officer_data['half_yearly']['H2']['achievement_percentage'] ? 'H1' : 'H2';
                                        echo $best_half;
                                        ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-bold text-gray-900">
                                        <?php 
                                        $consistency = 100 - abs($officer_data['half_yearly']['H1']['achievement_percentage'] - $officer_data['half_yearly']['H2']['achievement_percentage']);
                                        echo number_format(max(0, $consistency), 1); ?>%
                                    </div>
                                </td>
                            <?php endif; ?>
                            
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-4">
                                        <div class="<?php echo $officer_data['annual']['achievement_percentage'] >= 100 ? 'bg-green-500' : ($officer_data['annual']['achievement_percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-4 rounded-full transition-all duration-500" 
                                             style="width: <?php echo min(100, $officer_data['annual']['achievement_percentage']); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="officer_detailed_annual_report.php?officer_id=<?php echo $officer_data['officer_info']['officer_id']; ?>&year=<?php echo $selected_year; ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Detailed Report">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="officer_performance_trends.php?officer_id=<?php echo $officer_data['officer_info']['officer_id']; ?>&year=<?php echo $selected_year; ?>" 
                                       class="text-green-600 hover:text-green-800" title="Trend Analysis">
                                        <i class="fas fa-trending-up"></i>
                                    </a>
                                    <a href="mpr_income_lines_officers.php?sstaff=<?php echo $officer_data['officer_info']['officer_id']; ?>&smonth=<?php echo date('n'); ?>&syear=<?php echo $selected_year; ?>" 
                                       class="text-purple-600 hover:text-purple-800" title="Monthly View">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights & Recommendations</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Top Performers (Annual)</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <?php foreach (array_slice($annual_performance, 0, 3) as $performer): ?>
                            <li>• <?php echo $performer['officer_info']['officer_name']; ?> - <?php echo number_format($performer['annual']['achievement_percentage'], 1); ?>%</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Improvement Needed</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <?php 
                        $low_performers = array_filter($annual_performance, function($officer) {
                            return $officer['annual']['achievement_percentage'] < 80;
                        });
                        foreach (array_slice($low_performers, 0, 3) as $performer): 
                        ?>
                            <li>• <?php echo $performer['officer_info']['officer_name']; ?> - <?php echo number_format($performer['annual']['achievement_percentage'], 1); ?>%</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Strategic Actions</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Review annual target setting methodology</li>
                        <li>• Implement performance improvement programs</li>
                        <li>• Recognize and reward consistent performers</li>
                        <li>• Analyze seasonal performance patterns</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Navigation</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="?year=<?php echo $selected_year; ?>&view=annual" 
                   class="flex items-center justify-center px-4 py-3 <?php echo $view_type === 'annual' ? 'bg-blue-600' : 'bg-gray-600'; ?> text-white rounded-lg hover:opacity-90 transition-colors">
                    <i class="fas fa-calendar mr-2"></i>Annual View
                </a>
                
                <a href="?year=<?php echo $selected_year; ?>&view=quarterly" 
                   class="flex items-center justify-center px-4 py-3 <?php echo $view_type === 'quarterly' ? 'bg-blue-600' : 'bg-gray-600'; ?> text-white rounded-lg hover:opacity-90 transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i>Quarterly View
                </a>
                
                <a href="?year=<?php echo $selected_year; ?>&view=half_yearly" 
                   class="flex items-center justify-center px-4 py-3 <?php echo $view_type === 'half_yearly' ? 'bg-blue-600' : 'bg-gray-600'; ?> text-white rounded-lg hover:opacity-90 transition-colors">
                    <i class="fas fa-chart-pie mr-2"></i>Half-Yearly View
                </a>
                
                <a href="officer_performance_comparison.php?year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-balance-scale mr-2"></i>Comparison Tool
                </a>
            </div>
        </div>
    </div>

    <script>
        // Performance Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        const distributionData = <?php echo json_encode($performance_distribution); ?>;
        
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(distributionData),
                datasets: [{
                    data: Object.values(distributionData),
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',  // A+
                        'rgba(34, 197, 94, 0.8)',   // A
                        'rgba(59, 130, 246, 0.8)',  // B+
                        'rgba(99, 102, 241, 0.8)',  // B
                        'rgba(245, 158, 11, 0.8)',  // C+
                        'rgba(251, 191, 36, 0.8)',  // C
                        'rgba(249, 115, 22, 0.8)',  // D
                        'rgba(239, 68, 68, 0.8)'    // F
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' officers';
                            }
                        }
                    }
                }
            }
        });

        // Department Comparison Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentData = <?php echo json_encode($department_comparison); ?>;
        
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: departmentData.map(item => item.department),
                datasets: [{
                    label: 'Target (₦)',
                    data: departmentData.map(item => item.total_department_target),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }, {
                    label: 'Achieved (₦)',
                    data: departmentData.map(item => item.total_department_achieved),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
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
                                return context.dataset.label + ': ₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Export functions
        function exportToExcel() {
            let csv = 'Officer,Department,';
            
            <?php if ($view_type === 'annual'): ?>
                csv += 'Annual Target,Annual Achieved,Achievement %,Grade,Variance\n';
            <?php elseif ($view_type === 'quarterly'): ?>
                csv += 'Q1 %,Q2 %,Q3 %,Q4 %,Average Score\n';
            <?php else: ?>
                csv += 'H1 %,H2 %,Best Half,Consistency %\n';
            <?php endif; ?>
            
            const table = document.getElementById('performanceTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowData = [];
                
                // Officer name and department
                rowData.push('"' + cells[1].textContent.trim().split('\n')[0] + '"');
                rowData.push('"' + cells[2].textContent.trim() + '"');
                
                // Performance data based on view type
                for (let i = 3; i < cells.length - 2; i++) {
                    rowData.push(cells[i].textContent.trim().replace(/[₦,%]/g, ''));
                }
                
                csv += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `officer_annual_performance_${<?php echo $selected_year; ?>}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function printReport() {
            window.print();
        }

        // Add smooth animations to performance bars
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('[style*="width:"]');
            bars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>