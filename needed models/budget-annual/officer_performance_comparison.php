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

class PerformanceComparisonAnalyzer {
    private $db;
    private $target_manager;
    private $performance_analyzer;
    
    public function __construct() {
        $this->db = new Database();
        $this->target_manager = new OfficerRealTimeTargetManager();
        $this->performance_analyzer = new OfficerPerformanceAnalyzer();
    }
    
    /**
     * Get multi-year performance comparison
     */
    public function getMultiYearComparison($officer_ids, $years) {
        $comparison_data = [];
        
        foreach ($officer_ids as $officer_id) {
            $officer_info = $this->performance_analyzer->getOfficerInfo($officer_id, false);
            $yearly_performance = [];
            
            foreach ($years as $year) {
                $yearly_performance[$year] = $this->getYearlyPerformance($officer_id, $year);
            }
            
            $comparison_data[] = [
                'officer_info' => $officer_info,
                'yearly_performance' => $yearly_performance,
                'average_performance' => $this->calculateAveragePerformance($yearly_performance),
                'growth_trend' => $this->calculateGrowthTrend($yearly_performance)
            ];
        }
        
        return $comparison_data;
    }
    
    /**
     * Get yearly performance for an officer
     */
    private function getYearlyPerformance($officer_id, $year) {
        // Get annual targets
        $this->db->query("
            SELECT SUM(monthly_target) as annual_target
            FROM officer_monthly_targets 
            WHERE officer_id = :officer_id
            AND target_year = :year
            AND status = 'Active'
        ");
        
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':year', $year);
        $target_result = $this->db->single();
        
        // Get annual achievements
        $this->db->query("
            SELECT 
                SUM(amount_paid) as annual_achieved,
                COUNT(DISTINCT date_of_payment) as working_days,
                COUNT(id) as total_transactions
            FROM account_general_transaction_new 
            WHERE remitting_id = :officer_id
            AND YEAR(date_of_payment) = :year
            AND (approval_status = 'Approved' OR approval_status = '')
        ");
        
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':year', $year);
        $achievement_result = $this->db->single();
        
        $target = $target_result['annual_target'] ?? 0;
        $achieved = $achievement_result['annual_achieved'] ?? 0;
        $achievement_percentage = $target > 0 ? ($achieved / $target) * 100 : 0;
        
        return [
            'year' => $year,
            'target' => $target,
            'achieved' => $achieved,
            'achievement_percentage' => $achievement_percentage,
            'working_days' => $achievement_result['working_days'] ?? 0,
            'total_transactions' => $achievement_result['total_transactions'] ?? 0,
            'grade' => $this->calculateGrade($achievement_percentage),
            'score' => $this->calculateScore($achievement_percentage)
        ];
    }
    
    /**
     * Calculate average performance across years
     */
    private function calculateAveragePerformance($yearly_performance) {
        $achievements = array_column($yearly_performance, 'achievement_percentage');
        $scores = array_column($yearly_performance, 'score');
        
        return [
            'avg_achievement_percentage' => array_sum($achievements) / count($achievements),
            'avg_score' => array_sum($scores) / count($scores),
            'best_year' => $this->findBestYear($yearly_performance),
            'worst_year' => $this->findWorstYear($yearly_performance)
        ];
    }
    
    /**
     * Calculate growth trend
     */
    private function calculateGrowthTrend($yearly_performance) {
        $years = array_keys($yearly_performance);
        sort($years);
        
        if (count($years) < 2) {
            return ['trend' => 'insufficient_data', 'rate' => 0];
        }
        
        $first_year = $yearly_performance[$years[0]];
        $last_year = $yearly_performance[$years[count($years) - 1]];
        
        $growth_rate = $first_year['achieved'] > 0 ? 
            (($last_year['achieved'] - $first_year['achieved']) / $first_year['achieved']) * 100 : 0;
        
        return [
            'rate' => $growth_rate,
            'trend' => $growth_rate > 10 ? 'strong_growth' : ($growth_rate > 0 ? 'growth' : ($growth_rate < -10 ? 'strong_decline' : 'decline')),
            'first_year_performance' => $first_year,
            'last_year_performance' => $last_year
        ];
    }
    
    /**
     * Find best performing year
     */
    private function findBestYear($yearly_performance) {
        $best_year = null;
        $best_score = 0;
        
        foreach ($yearly_performance as $year => $performance) {
            if ($performance['score'] > $best_score) {
                $best_score = $performance['score'];
                $best_year = $year;
            }
        }
        
        return $best_year;
    }
    
    /**
     * Find worst performing year
     */
    private function findWorstYear($yearly_performance) {
        $worst_year = null;
        $worst_score = PHP_FLOAT_MAX;
        
        foreach ($yearly_performance as $year => $performance) {
            if ($performance['target'] > 0 && $performance['score'] < $worst_score) {
                $worst_score = $performance['score'];
                $worst_year = $year;
            }
        }
        
        return $worst_year;
    }
    
    /**
     * Get department ranking comparison
     */
    public function getDepartmentRankingComparison($year) {
        $this->db->query("
            SELECT 
                s.user_id,
                s.full_name,
                s.department,
                SUM(COALESCE(t.amount_paid, 0)) as annual_achieved,
                (SELECT SUM(monthly_target) FROM officer_monthly_targets 
                 WHERE officer_id = s.user_id AND target_year = :year) as annual_target
            FROM staffs s
            LEFT JOIN account_general_transaction_new t ON s.user_id = t.remitting_id
                AND YEAR(t.date_of_payment) = :year
                AND (t.approval_status = 'Approved' OR t.approval_status = '')
            WHERE s.department IN ('Wealth Creation', 'Leasing')
            GROUP BY s.user_id, s.full_name, s.department
            HAVING annual_target > 0
            ORDER BY (annual_achieved / annual_target) DESC
        ");
        
        $this->db->bind(':year', $year);
        
        $officers = $this->db->resultSet();
        
        foreach ($officers as &$officer) {
            $achievement_percentage = $officer['annual_target'] > 0 ? 
                ($officer['annual_achieved'] / $officer['annual_target']) * 100 : 0;
            
            $officer['achievement_percentage'] = $achievement_percentage;
            $officer['grade'] = $this->calculateGrade($achievement_percentage);
            $officer['score'] = $this->calculateScore($achievement_percentage);
        }
        
        return $officers;
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

$comparison_analyzer = new PerformanceComparisonAnalyzer();

// Get parameters
$selected_year = $_GET['year'] ?? date('Y');
$comparison_type = $_GET['type'] ?? 'department';
$selected_officers = $_GET['officers'] ?? [];

// Get available years for comparison
$available_years = range(date('Y') - 3, date('Y'));

// Get data based on comparison type
if ($comparison_type === 'multi_year' && !empty($selected_officers)) {
    $comparison_data = $comparison_analyzer->getMultiYearComparison($selected_officers, $available_years);
} else {
    $department_ranking = $comparison_analyzer->getDepartmentRankingComparison($selected_year);
}

// Get all officers for selection
$analyzer = new OfficerPerformanceAnalyzer();
$all_officers = $analyzer->getWealthCreationOfficers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Officer Performance Comparison</title>
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
                    <a href="officer_annual_performance.php" class="text-blue-600 hover:text-blue-800 mr-4">← Back to Annual Analysis</a>
                    <h1 class="text-xl font-bold text-gray-900">Performance Comparison Tool</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Comparative Analysis</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Comparison Controls -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Comparison Settings</h3>
            
            <form method="GET" class="space-y-6">
                <!-- Comparison Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comparison Type</label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="type" value="department" <?php echo $comparison_type === 'department' ? 'checked' : ''; ?> 
                                   class="mr-2" onchange="toggleOfficerSelection()">
                            <span class="text-sm text-gray-700">Department Ranking (Single Year)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="type" value="multi_year" <?php echo $comparison_type === 'multi_year' ? 'checked' : ''; ?> 
                                   class="mr-2" onchange="toggleOfficerSelection()">
                            <span class="text-sm text-gray-700">Multi-Year Comparison</span>
                        </label>
                    </div>
                </div>
                
                <!-- Year Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                    <select name="year" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Officer Selection (for multi-year comparison) -->
                <div id="officerSelection" class="<?php echo $comparison_type === 'multi_year' ? '' : 'hidden'; ?>">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Officers to Compare</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-4">
                        <?php foreach ($all_officers as $officer): ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="officers[]" value="<?php echo $officer['user_id']; ?>" 
                                   <?php echo in_array($officer['user_id'], $selected_officers) ? 'checked' : ''; ?>
                                   class="mr-2">
                            <span class="text-sm text-gray-700"><?php echo $officer['full_name']; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-chart-bar mr-2"></i>Generate Comparison
                </button>
            </form>
        </div>

        <?php if ($comparison_type === 'department'): ?>
        <!-- Department Ranking -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Department Performance Ranking - <?php echo $selected_year; ?></h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Target</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Annual Achieved</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement %</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance Bar</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($department_ranking as $index => $officer): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold
                                    <?php echo $index === 0 ? 'bg-yellow-500' : ($index === 1 ? 'bg-gray-500' : ($index === 2 ? 'bg-orange-500' : 'bg-blue-500')); ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        <?php echo strtoupper(substr($officer['full_name'], 0, 2)); ?>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $officer['full_name']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $officer['department'] === 'Wealth Creation' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo $officer['department']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($officer['annual_target']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($officer['annual_achieved']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-lg font-bold <?php echo $officer['achievement_percentage'] >= 100 ? 'text-green-600' : ($officer['achievement_percentage'] >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($officer['achievement_percentage'], 1); ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                    <?php 
                                    $grade_class = 'bg-gray-100 text-gray-800';
                                    switch($officer['grade']) {
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
                                    <?php echo $officer['grade']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-4">
                                        <div class="<?php echo $officer['achievement_percentage'] >= 100 ? 'bg-green-500' : ($officer['achievement_percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-4 rounded-full transition-all duration-500" 
                                             style="width: <?php echo min(100, $officer['achievement_percentage']); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="officer_detailed_annual_report.php?officer_id=<?php echo $officer['user_id']; ?>&year=<?php echo $selected_year; ?>" 
                                   class="text-blue-600 hover:text-blue-800" title="View Detailed Report">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($comparison_type === 'multi_year' && !empty($comparison_data)): ?>
        <!-- Multi-Year Comparison -->
        <div class="space-y-8">
            <?php foreach ($comparison_data as $officer_comparison): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white text-lg font-bold">
                            <?php echo strtoupper(substr($officer_comparison['officer_info']['full_name'], 0, 2)); ?>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-gray-900"><?php echo $officer_comparison['officer_info']['full_name']; ?></h3>
                            <p class="text-gray-600"><?php echo $officer_comparison['officer_info']['department']; ?></p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <div class="text-lg font-bold <?php echo $officer_comparison['growth_trend']['rate'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $officer_comparison['growth_trend']['rate'] >= 0 ? '+' : ''; ?><?php echo number_format($officer_comparison['growth_trend']['rate'], 1); ?>%
                        </div>
                        <div class="text-sm text-gray-500">Growth Rate</div>
                    </div>
                </div>
                
                <!-- Yearly Performance Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achieved</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement %</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Working Days</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance Bar</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($officer_comparison['yearly_performance'] as $year_data): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $year_data['year']; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    ₦<?php echo number_format($year_data['target']); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                    ₦<?php echo number_format($year_data['achieved']); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm font-bold <?php echo $year_data['achievement_percentage'] >= 100 ? 'text-green-600' : ($year_data['achievement_percentage'] >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo number_format($year_data['achievement_percentage'], 1); ?>%
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        $grade_class = 'bg-gray-100 text-gray-800';
                                        switch($year_data['grade']) {
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
                                        <?php echo $year_data['grade']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                    <?php echo $year_data['working_days']; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                    <?php echo number_format($year_data['total_transactions']); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-3">
                                            <div class="<?php echo $year_data['achievement_percentage'] >= 100 ? 'bg-green-500' : ($year_data['achievement_percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-3 rounded-full transition-all duration-500" 
                                                 style="width: <?php echo min(100, $year_data['achievement_percentage']); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Performance Insights -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Key Observations</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <?php if ($comparison_type === 'department'): ?>
                            <li>• Top performer: <?php echo $department_ranking[0]['full_name'] ?? 'N/A'; ?></li>
                            <li>• Average achievement: <?php echo number_format(array_sum(array_column($department_ranking, 'achievement_percentage')) / count($department_ranking), 1); ?>%</li>
                            <li>• Officers above target: <?php echo count(array_filter($department_ranking, function($o) { return $o['achievement_percentage'] >= 100; })); ?></li>
                        <?php else: ?>
                            <li>• Multi-year comparison shows performance trends</li>
                            <li>• Growth patterns indicate development areas</li>
                            <li>• Consistency metrics reveal reliability</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Recommendations</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Implement peer learning programs</li>
                        <li>• Establish performance benchmarks</li>
                        <li>• Create mentorship opportunities</li>
                        <li>• Regular performance reviews</li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Action Items</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Review target setting methodology</li>
                        <li>• Analyze top performer strategies</li>
                        <li>• Support underperforming officers</li>
                        <li>• Plan capacity building programs</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleOfficerSelection() {
            const multiYearRadio = document.querySelector('input[name="type"][value="multi_year"]');
            const officerSelection = document.getElementById('officerSelection');
            
            if (multiYearRadio.checked) {
                officerSelection.classList.remove('hidden');
            } else {
                officerSelection.classList.add('hidden');
            }
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