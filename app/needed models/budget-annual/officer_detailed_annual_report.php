<?php
require_once 'OfficerRealTimeTargetManager.php';
require_once 'OfficerPerformanceAnalyzer.php';
require_once 'config.php';

// Start session
session_start();

// Mock session data for demonstration
$staff = [
    'user_id' => 1,
    'full_name' => 'John Doe',
    'department' => 'Accounts'
];

class DetailedAnnualReportAnalyzer {
    private $db;
    private $target_manager;
    private $performance_analyzer;
    
    public function __construct() {
        $this->db = new Database();
        $this->target_manager = new OfficerRealTimeTargetManager();
        $this->performance_analyzer = new OfficerPerformanceAnalyzer();
    }
    
    /**
     * Get comprehensive annual report for a specific officer
     */
    public function getOfficerAnnualReport($officer_id, $year) {
        $officer_info = $this->performance_analyzer->getOfficerInfo($officer_id, false);
        
        $report = [
            'officer_info' => $officer_info,
            'annual_summary' => $this->getAnnualSummary($officer_id, $year),
            'monthly_performance' => $this->getMonthlyPerformanceData($officer_id, $year),
            'quarterly_analysis' => $this->getQuarterlyAnalysis($officer_id, $year),
            'income_line_analysis' => $this->getIncomeLineAnnualAnalysis($officer_id, $year),
            'performance_trends' => $this->getPerformanceTrends($officer_id, $year),
            'consistency_metrics' => $this->getConsistencyMetrics($officer_id, $year),
            'comparative_analysis' => $this->getComparativeAnalysis($officer_id, $year),
            'recommendations' => $this->generateRecommendations($officer_id, $year)
        ];
        
        return $report;
    }
    
    /**
     * Get annual summary for officer
     */
    private function getAnnualSummary($officer_id, $year) {
        $this->db->query("
            SELECT 
                SUM(opt.monthly_target) as total_annual_target,
                COUNT(DISTINCT opt.target_month) as months_with_targets,
                COUNT(opt.id) as total_assigned_lines,
                AVG(opt.daily_target) as avg_daily_target
            FROM officer_monthly_targets opt
            WHERE opt.officer_id = :officer_id
            AND opt.target_year = :year
            AND opt.status = 'Active'
        ");
        
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':year', $year);
        $targets = $this->db->single();
        
        // Get actual achievements
        $this->db->query("
            SELECT 
                SUM(t.amount_paid) as total_annual_achieved,
                COUNT(DISTINCT t.date_of_payment) as total_working_days,
                COUNT(t.id) as total_transactions,
                AVG(t.amount_paid) as avg_transaction_amount,
                MAX(t.amount_paid) as highest_transaction,
                MIN(t.amount_paid) as lowest_transaction
            FROM account_general_transaction_new t
            WHERE t.remitting_id = :officer_id
            AND YEAR(t.date_of_payment) = :year
            AND (t.approval_status = 'Approved' OR t.approval_status = '')
        ");
        
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':year', $year);
        $achievements = $this->db->single();
        
        $total_target = $targets['total_annual_target'] ?? 0;
        $total_achieved = $achievements['total_annual_achieved'] ?? 0;
        $achievement_percentage = $total_target > 0 ? ($total_achieved / $total_target) * 100 : 0;
        
        return [
            'total_target' => $total_target,
            'total_achieved' => $total_achieved,
            'achievement_percentage' => $achievement_percentage,
            'variance' => $total_achieved - $total_target,
            'months_with_targets' => $targets['months_with_targets'] ?? 0,
            'total_assigned_lines' => $targets['total_assigned_lines'] ?? 0,
            'total_working_days' => $achievements['total_working_days'] ?? 0,
            'total_transactions' => $achievements['total_transactions'] ?? 0,
            'avg_transaction_amount' => $achievements['avg_transaction_amount'] ?? 0,
            'highest_transaction' => $achievements['highest_transaction'] ?? 0,
            'lowest_transaction' => $achievements['lowest_transaction'] ?? 0,
            'daily_average' => $achievements['total_working_days'] > 0 ? $total_achieved / $achievements['total_working_days'] : 0,
            'grade' => $this->calculateGrade($achievement_percentage),
            'score' => $this->calculateScore($achievement_percentage)
        ];
    }
    
    /**
     * Get monthly performance data for the year
     */
    private function getMonthlyPerformanceData($officer_id, $year) {
        $monthly_data = [];
        
        for ($month = 1; $month <= 12; $month++) {
            // Get targets for the month
            $this->db->query("
                SELECT SUM(monthly_target) as month_target
                FROM officer_monthly_targets 
                WHERE officer_id = :officer_id
                AND target_month = :month
                AND target_year = :year
                AND status = 'Active'
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            $target_result = $this->db->single();
            
            // Get achievements for the month
            $this->db->query("
                SELECT 
                    SUM(amount_paid) as month_achieved,
                    COUNT(DISTINCT date_of_payment) as working_days,
                    COUNT(id) as transactions
                FROM account_general_transaction_new 
                WHERE remitting_id = :officer_id
                AND MONTH(date_of_payment) = :month
                AND YEAR(date_of_payment) = :year
                AND (approval_status = 'Approved' OR approval_status = '')
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            $achievement_result = $this->db->single();
            
            $target = $target_result['month_target'] ?? 0;
            $achieved = $achievement_result['month_achieved'] ?? 0;
            $achievement_percentage = $target > 0 ? ($achieved / $target) * 100 : 0;
            
            $monthly_data[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $achievement_percentage,
                'working_days' => $achievement_result['working_days'] ?? 0,
                'transactions' => $achievement_result['transactions'] ?? 0,
                'grade' => $this->calculateGrade($achievement_percentage),
                'score' => $this->calculateScore($achievement_percentage)
            ];
        }
        
        return $monthly_data;
    }
    
    /**
     * Get quarterly analysis
     */
    private function getQuarterlyAnalysis($officer_id, $year) {
        $quarters = [
            'Q1' => ['months' => [1, 2, 3], 'name' => 'Q1 (Jan-Mar)'],
            'Q2' => ['months' => [4, 5, 6], 'name' => 'Q2 (Apr-Jun)'],
            'Q3' => ['months' => [7, 8, 9], 'name' => 'Q3 (Jul-Sep)'],
            'Q4' => ['months' => [10, 11, 12], 'name' => 'Q4 (Oct-Dec)']
        ];
        
        $quarterly_data = [];
        
        foreach ($quarters as $quarter => $info) {
            $month_list = implode(',', $info['months']);
            
            // Get quarterly targets
            $this->db->query("
                SELECT SUM(monthly_target) as quarter_target
                FROM officer_monthly_targets 
                WHERE officer_id = :officer_id
                AND target_month IN ($month_list)
                AND target_year = :year
                AND status = 'Active'
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':year', $year);
            $target_result = $this->db->single();
            
            // Get quarterly achievements
            $this->db->query("
                SELECT 
                    SUM(amount_paid) as quarter_achieved,
                    COUNT(DISTINCT date_of_payment) as working_days,
                    COUNT(id) as transactions
                FROM account_general_transaction_new 
                WHERE remitting_id = :officer_id
                AND MONTH(date_of_payment) IN ($month_list)
                AND YEAR(date_of_payment) = :year
                AND (approval_status = 'Approved' OR approval_status = '')
            ");
            
            $this->db->bind(':officer_id', $officer_id);
            $this->db->bind(':year', $year);
            $achievement_result = $this->db->single();
            
            $target = $target_result['quarter_target'] ?? 0;
            $achieved = $achievement_result['quarter_achieved'] ?? 0;
            $achievement_percentage = $target > 0 ? ($achieved / $target) * 100 : 0;
            
            $quarterly_data[$quarter] = [
                'name' => $info['name'],
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $achievement_percentage,
                'working_days' => $achievement_result['working_days'] ?? 0,
                'transactions' => $achievement_result['transactions'] ?? 0,
                'grade' => $this->calculateGrade($achievement_percentage),
                'score' => $this->calculateScore($achievement_percentage),
                'daily_average' => $achievement_result['working_days'] > 0 ? $achieved / $achievement_result['working_days'] : 0
            ];
        }
        
        return $quarterly_data;
    }
    
    /**
     * Get income line annual analysis
     */
    private function getIncomeLineAnnualAnalysis($officer_id, $year) {
        $this->db->query("
            SELECT 
                opt.acct_id,
                opt.acct_desc,
                SUM(opt.monthly_target) as annual_target,
                SUM(COALESCE(actual_data.achieved_amount, 0)) as annual_achieved,
                COUNT(opt.id) as months_assigned,
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
            GROUP BY opt.acct_id, opt.acct_desc
            ORDER BY annual_achieved DESC
        ");
        
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':year', $year);
        
        $income_lines = $this->db->resultSet();
        
        foreach ($income_lines as &$line) {
            $achievement_percentage = $line['annual_target'] > 0 ? 
                ($line['annual_achieved'] / $line['annual_target']) * 100 : 0;
            
            $line['achievement_percentage'] = $achievement_percentage;
            $line['grade'] = $this->calculateGrade($achievement_percentage);
            $line['score'] = $this->calculateScore($achievement_percentage);
            $line['variance'] = $line['annual_achieved'] - $line['annual_target'];
        }
        
        return $income_lines;
    }
    
    /**
     * Get performance trends
     */
    private function getPerformanceTrends($officer_id, $year) {
        $monthly_data = $this->getMonthlyPerformanceData($officer_id, $year);
        
        $trends = [
            'improvement_trend' => $this->calculateImprovementTrend($monthly_data),
            'consistency_score' => $this->calculateConsistencyScore($monthly_data),
            'peak_month' => $this->findPeakMonth($monthly_data),
            'lowest_month' => $this->findLowestMonth($monthly_data),
            'growth_rate' => $this->calculateGrowthRate($monthly_data)
        ];
        
        return $trends;
    }
    
    /**
     * Calculate improvement trend
     */
    private function calculateImprovementTrend($monthly_data) {
        $first_half = array_slice($monthly_data, 0, 6);
        $second_half = array_slice($monthly_data, 6, 6);
        
        $first_half_avg = array_sum(array_column($first_half, 'achievement_percentage')) / 6;
        $second_half_avg = array_sum(array_column($second_half, 'achievement_percentage')) / 6;
        
        $trend_percentage = $first_half_avg > 0 ? (($second_half_avg - $first_half_avg) / $first_half_avg) * 100 : 0;
        
        return [
            'percentage' => $trend_percentage,
            'direction' => $trend_percentage > 5 ? 'improving' : ($trend_percentage < -5 ? 'declining' : 'stable'),
            'first_half_avg' => $first_half_avg,
            'second_half_avg' => $second_half_avg
        ];
    }
    
    /**
     * Calculate consistency score
     */
    private function getConsistencyMetrics($officer_id, $year) {
        $monthly_data = $this->getMonthlyPerformanceData($officer_id, $year);
        $achievements = array_column($monthly_data, 'achievement_percentage');
        
        $mean = array_sum($achievements) / count($achievements);
        $variance = 0;
        
        foreach ($achievements as $achievement) {
            $variance += pow($achievement - $mean, 2);
        }
        $variance /= count($achievements);
        
        $std_deviation = sqrt($variance);
        $coefficient_of_variation = $mean > 0 ? ($std_deviation / $mean) * 100 : 0;
        
        // Consistency score (lower CV = higher consistency)
        $consistency_score = max(0, 100 - $coefficient_of_variation);
        
        return [
            'consistency_score' => $consistency_score,
            'standard_deviation' => $std_deviation,
            'coefficient_of_variation' => $coefficient_of_variation,
            'mean_achievement' => $mean,
            'months_above_target' => count(array_filter($achievements, function($a) { return $a >= 100; })),
            'months_below_target' => count(array_filter($achievements, function($a) { return $a < 100; }))
        ];
    }
    
    /**
     * Find peak performance month
     */
    private function findPeakMonth($monthly_data) {
        $peak = null;
        $max_achievement = 0;
        
        foreach ($monthly_data as $month) {
            if ($month['achievement_percentage'] > $max_achievement) {
                $max_achievement = $month['achievement_percentage'];
                $peak = $month;
            }
        }
        
        return $peak;
    }
    
    /**
     * Find lowest performance month
     */
    private function findLowestMonth($monthly_data) {
        $lowest = null;
        $min_achievement = PHP_FLOAT_MAX;
        
        foreach ($monthly_data as $month) {
            if ($month['target'] > 0 && $month['achievement_percentage'] < $min_achievement) {
                $min_achievement = $month['achievement_percentage'];
                $lowest = $month;
            }
        }
        
        return $lowest;
    }
    
    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($monthly_data) {
        $first_quarter = array_slice($monthly_data, 0, 3);
        $last_quarter = array_slice($monthly_data, 9, 3);
        
        $first_quarter_avg = array_sum(array_column($first_quarter, 'achieved')) / 3;
        $last_quarter_avg = array_sum(array_column($last_quarter, 'achieved')) / 3;
        
        return $first_quarter_avg > 0 ? (($last_quarter_avg - $first_quarter_avg) / $first_quarter_avg) * 100 : 0;
    }
    
    /**
     * Get comparative analysis with peers
     */
    private function getComparativeAnalysis($officer_id, $year) {
        // Get officer's annual performance
        $officer_summary = $this->getAnnualSummary($officer_id, $year);
        
        // Get department average
        $this->db->query("
            SELECT 
                AVG(annual_totals.total_achieved) as dept_avg_achieved,
                AVG(annual_totals.total_target) as dept_avg_target,
                COUNT(*) as peer_count
            FROM (
                SELECT 
                    s.user_id,
                    SUM(COALESCE(t.amount_paid, 0)) as total_achieved,
                    (SELECT SUM(monthly_target) FROM officer_monthly_targets 
                     WHERE officer_id = s.user_id AND target_year = :year) as total_target
                FROM staffs s
                LEFT JOIN account_general_transaction_new t ON s.user_id = t.remitting_id
                    AND YEAR(t.date_of_payment) = :year
                    AND (t.approval_status = 'Approved' OR t.approval_status = '')
                WHERE s.department = 'Wealth Creation'
                GROUP BY s.user_id
            ) as annual_totals
            WHERE annual_totals.total_target > 0
        ");
        
        $this->db->bind(':year', $year);
        $dept_avg = $this->db->single();
        
        return [
            'officer_achievement' => $officer_summary['total_achieved'],
            'department_avg_achievement' => $dept_avg['dept_avg_achieved'] ?? 0,
            'officer_target' => $officer_summary['total_target'],
            'department_avg_target' => $dept_avg['dept_avg_target'] ?? 0,
            'peer_count' => $dept_avg['peer_count'] ?? 0,
            'performance_vs_peers' => $dept_avg['dept_avg_achieved'] > 0 ? 
                ($officer_summary['total_achieved'] / $dept_avg['dept_avg_achieved']) * 100 : 0
        ];
    }
    
    /**
     * Generate recommendations based on performance
     */
    private function generateRecommendations($officer_id, $year) {
        $annual_summary = $this->getAnnualSummary($officer_id, $year);
        $trends = $this->getPerformanceTrends($officer_id, $year);
        $consistency = $this->getConsistencyMetrics($officer_id, $year);
        
        $recommendations = [];
        
        // Performance-based recommendations
        if ($annual_summary['achievement_percentage'] >= 120) {
            $recommendations[] = [
                'type' => 'recognition',
                'title' => 'Exceptional Performance Recognition',
                'description' => 'Officer has exceeded annual targets by ' . number_format($annual_summary['achievement_percentage'] - 100, 1) . '%',
                'action' => 'Consider for promotion, increased responsibilities, or performance bonus'
            ];
        } elseif ($annual_summary['achievement_percentage'] < 80) {
            $recommendations[] = [
                'type' => 'improvement',
                'title' => 'Performance Improvement Plan',
                'description' => 'Officer is ' . number_format(100 - $annual_summary['achievement_percentage'], 1) . '% below annual targets',
                'action' => 'Implement targeted training, mentoring, and regular performance reviews'
            ];
        }
        
        // Consistency-based recommendations
        if ($consistency['consistency_score'] < 60) {
            $recommendations[] = [
                'type' => 'consistency',
                'title' => 'Consistency Improvement',
                'description' => 'Performance shows high variability (CV: ' . number_format($consistency['coefficient_of_variation'], 1) . '%)',
                'action' => 'Focus on establishing consistent daily routines and performance standards'
            ];
        }
        
        // Trend-based recommendations
        if ($trends['improvement_trend']['direction'] === 'declining') {
            $recommendations[] = [
                'type' => 'trend',
                'title' => 'Declining Performance Trend',
                'description' => 'Performance has declined by ' . number_format(abs($trends['improvement_trend']['percentage']), 1) . '% over the year',
                'action' => 'Investigate causes and implement corrective measures immediately'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate performance grade
     */
    private function calculateGrade($achievement_percentage) {
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
     * Calculate performance score
     */
    private function calculateScore($achievement_percentage) {
        if ($achievement_percentage >= 150) return 100;
        if ($achievement_percentage >= 120) return 90;
        if ($achievement_percentage >= 100) return 80;
        if ($achievement_percentage >= 80) return 70;
        if ($achievement_percentage >= 60) return 60;
        if ($achievement_percentage >= 40) return 50;
        if ($achievement_percentage >= 20) return 40;
        return 30;
    }
}

$detailed_analyzer = new DetailedAnnualReportAnalyzer();

$officer_id = $_GET['officer_id'] ?? null;
$year = $_GET['year'] ?? date('Y');

if (!$officer_id) {
    header('Location: officer_annual_performance.php');
    exit;
}

$report = $detailed_analyzer->getOfficerAnnualReport($officer_id, $year);

if (!$report['officer_info']) {
    header('Location: officer_annual_performance.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $report['officer_info']['full_name']; ?> Annual Report</title>
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
                    <a href="officer_annual_performance.php?year=<?php echo $year; ?>" class="text-blue-600 hover:text-blue-800 mr-4">← Back to Annual Analysis</a>
                    <h1 class="text-xl font-bold text-gray-900">Detailed Annual Report</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700"><?php echo $year; ?> Performance Report</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Officer Profile Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        <?php echo strtoupper(substr($report['officer_info']['full_name'], 0, 2)); ?>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $report['officer_info']['full_name']; ?></h2>
                        <p class="text-gray-600"><?php echo $report['officer_info']['department']; ?></p>
                        <?php if ($report['officer_info']['phone_no']): ?>
                            <p class="text-sm text-gray-500"><?php echo $report['officer_info']['phone_no']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="text-3xl font-bold <?php echo $report['annual_summary']['achievement_percentage'] >= 100 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo number_format($report['annual_summary']['achievement_percentage'], 1); ?>%
                    </div>
                    <div class="text-sm text-gray-500">Annual Achievement</div>
                    <span class="px-4 py-2 rounded-full text-lg font-medium 
                        <?php 
                        $grade_class = 'bg-gray-100 text-gray-800';
                        switch($report['annual_summary']['grade']) {
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
                        <?php echo $report['annual_summary']['grade']; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Annual Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-bullseye text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Annual Target</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($report['annual_summary']['total_target']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Achieved</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($report['annual_summary']['total_achieved']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-calendar-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Working Days</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $report['annual_summary']['total_working_days']; ?></p>
                        <p class="text-xs text-gray-500">₦<?php echo number_format($report['annual_summary']['daily_average']); ?>/day</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-receipt text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Transactions</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($report['annual_summary']['total_transactions']); ?></p>
                        <p class="text-xs text-gray-500">₦<?php echo number_format($report['annual_summary']['avg_transaction_amount']); ?> avg</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Monthly Performance Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Performance Trend</h3>
                <div class="relative h-64">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Quarterly Comparison Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quarterly Performance Comparison</h3>
                <div class="relative h-64">
                    <canvas id="quarterlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Income Line Performance -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Annual Performance by Income Line</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Target</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Achieved</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement %</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Months Assigned</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($report['income_line_analysis'] as $line): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $line['acct_desc']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($line['annual_target']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($line['annual_achieved']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="<?php echo $line['achievement_percentage'] >= 100 ? 'bg-green-500' : ($line['achievement_percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-2 rounded-full" 
                                             style="width: <?php echo min(100, $line['achievement_percentage']); ?>%"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-gray-500">
                                        <?php echo number_format($line['achievement_percentage'], 1); ?>%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php 
                                    $grade_class = 'bg-gray-100 text-gray-800';
                                    switch($line['grade']) {
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
                                    <?php echo $line['grade']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span class="<?php echo $line['variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                    <?php echo $line['variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($line['variance']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php echo $line['months_assigned']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php echo number_format($line['total_transactions']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performance Insights and Recommendations -->
        <?php if (!empty($report['recommendations'])): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights & Recommendations</h3>
            <div class="space-y-4">
                <?php foreach ($report['recommendations'] as $recommendation): ?>
                <div class="border-l-4 
                    <?php echo $recommendation['type'] === 'recognition' ? 'border-green-500 bg-green-50' : 
                              ($recommendation['type'] === 'improvement' ? 'border-red-500 bg-red-50' : 'border-yellow-500 bg-yellow-50'); ?> 
                    p-4 rounded-r-lg">
                    <h4 class="font-medium text-gray-900"><?php echo $recommendation['title']; ?></h4>
                    <p class="text-sm text-gray-600 mt-1"><?php echo $recommendation['description']; ?></p>
                    <p class="text-sm text-blue-600 mt-2 italic"><?php echo $recommendation['action']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Consistency and Trend Analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Consistency Metrics -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Consistency Analysis</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Consistency Score:</span>
                        <span class="text-lg font-bold text-gray-900"><?php echo number_format($report['consistency_metrics']['consistency_score'], 1); ?>%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Months Above Target:</span>
                        <span class="text-lg font-bold text-green-600"><?php echo $report['consistency_metrics']['months_above_target']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Months Below Target:</span>
                        <span class="text-lg font-bold text-red-600"><?php echo $report['consistency_metrics']['months_below_target']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Performance Variability:</span>
                        <span class="text-lg font-bold text-gray-900"><?php echo number_format($report['consistency_metrics']['coefficient_of_variation'], 1); ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Comparative Analysis -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Peer Comparison</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">vs Department Average:</span>
                        <span class="text-lg font-bold <?php echo $report['comparative_analysis']['performance_vs_peers'] >= 100 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($report['comparative_analysis']['performance_vs_peers'], 1); ?>%
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Department Peers:</span>
                        <span class="text-lg font-bold text-gray-900"><?php echo $report['comparative_analysis']['peer_count']; ?> officers</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Dept. Avg Achievement:</span>
                        <span class="text-lg font-bold text-gray-900">₦<?php echo number_format($report['comparative_analysis']['department_avg_achievement']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Performance Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($report['monthly_performance']); ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month_name),
                datasets: [{
                    label: 'Achievement %',
                    data: monthlyData.map(item => item.achievement_percentage),
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Target Line (100%)',
                    data: Array(12).fill(100),
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
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
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });

        // Quarterly Comparison Chart
        const quarterlyCtx = document.getElementById('quarterlyChart').getContext('2d');
        const quarterlyData = <?php echo json_encode($report['quarterly_analysis']); ?>;
        
        new Chart(quarterlyCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(quarterlyData).map(q => quarterlyData[q].name),
                datasets: [{
                    label: 'Target (₦)',
                    data: Object.values(quarterlyData).map(q => q.target),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }, {
                    label: 'Achieved (₦)',
                    data: Object.values(quarterlyData).map(q => q.achieved),
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
    </script>
</body>
</html>