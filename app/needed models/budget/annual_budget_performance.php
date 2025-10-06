<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../models/BudgetManager.php';
require_once '../helpers/session_helper.php';

// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

$manager = new BudgetManager();

class AnnualBudgetPerformanceAnalyzer {
    private $db;
    private $budget_manager;
    
    public function __construct() {
        $this->db = new Database();
        $this->budget_manager = new BudgetManager();
    }
    
    /**
     * Get annual budget performance summary
     */
    public function getAnnualPerformanceSummary($year) {
        $monthly_summary = [];
        $annual_totals = [
            'total_budget' => 0,
            'total_achieved' => 0,
            'total_variance' => 0,
            'overall_percentage' => 0
        ];
        
        for ($month = 1; $month <= 12; $month++) {
            $month_data = $this->getMonthlyPerformance($month, $year);
            $monthly_summary[] = $month_data;
            
            $annual_totals['total_budget'] += $month_data['monthly_budget'];
            $annual_totals['total_achieved'] += $month_data['achieved'];
        }
        
        $annual_totals['total_variance'] = $annual_totals['total_achieved'] - $annual_totals['total_budget'];
        $annual_totals['overall_percentage'] = $annual_totals['total_budget'] > 0 ? 
            ($annual_totals['total_achieved'] / $annual_totals['total_budget']) * 100 : 0;
        
        return [
            'monthly_summary' => $monthly_summary,
            'annual_totals' => $annual_totals
        ];
    }

    /**
     * Get monthly performance for a specific month
     */
    private function getMonthlyPerformance($month, $year) {
        // Budget field for this month
        $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
        
        // Get monthly budget for active lines
        $this->db->query("
            SELECT id, acct_id, {$month_field} as monthly_budget
            FROM budget_lines
            WHERE budget_year = :year
            AND status = 'Active'
        ");
        $this->db->bind(':year', $year);
        $budget_lines = $this->db->resultSet();

        $monthly_budget = 0;
        $achieved = 0;

        foreach ($budget_lines as $line) {
            $monthly_budget += (float)$line['monthly_budget'];

            // Get achieved for this account_id
            $this->db->query("
                SELECT COALESCE(SUM(amount_paid), 0) as achieved
                FROM account_general_transaction_new
                WHERE MONTH(date_of_payment) = :month
                AND YEAR(date_of_payment) = :year
                AND credit_account = :acct_id
                AND (approval_status = 'Approved' OR approval_status = '')
            ");
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            $this->db->bind(':acct_id', $line['acct_id']);
            $achieved_result = $this->db->single();

            $achieved += isset($achieved_result['achieved']) ? $achieved_result['achieved'] : 0;
        }

        $variance = $achieved - $monthly_budget;
        $percentage = $monthly_budget > 0 ? ($achieved / $monthly_budget) * 100 : 0;

        return [
            'month' => $month,
            'month_name' => strtoupper(date('F', mktime(0, 0, 0, $month, 1))),
            'monthly_budget' => $monthly_budget,
            'achieved' => $achieved,
            'variance' => $variance,
            'percentage' => $percentage
        ];
    }

    // public function getAnnualPerformanceSummary($year) {
    //     $monthly_summary = [];
    //     $annual_totals = [
    //         'total_budget' => 0,
    //         'total_achieved' => 0,
    //         'total_variance' => 0,
    //         'overall_percentage' => 0
    //     ];
        
    //     for ($month = 1; $month <= 12; $month++) {
    //         $month_data = $this->getMonthlyPerformance($month, $year);
    //         $monthly_summary[] = $month_data;
            
    //         $annual_totals['total_budget'] += $month_data['monthly_budget'];
    //         $annual_totals['total_achieved'] += $month_data['achieved'];
    //     }
        
    //     $annual_totals['total_variance'] = $annual_totals['total_achieved'] - $annual_totals['total_budget'];
    //     $annual_totals['overall_percentage'] = $annual_totals['total_budget'] > 0 ? 
    //         ($annual_totals['total_achieved'] / $annual_totals['total_budget']) * 100 : 0;
        
    //     return [
    //         'monthly_summary' => $monthly_summary,
    //         'annual_totals' => $annual_totals
    //     ];
    // }
    
    /**
     * Get monthly performance for a specific month
     */
    // private function getMonthlyPerformance($month, $year) {
    //     // Get monthly budget from budget_lines table
    //     $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
        
    //     $this->db->query("
    //         SELECT SUM({$month_field}) as monthly_budget
    //         FROM budget_lines 
    //         WHERE budget_year = :year 
    //         AND status = 'Active'
    //     ");
    //     $this->db->bind(':year', $year);
    //     $budget_result = $this->db->single();
        
    //     // Get actual collections for the month from transactions
    //     $this->db->query("
    //         SELECT COALESCE(SUM(amount_paid), 0) as achieved
    //         FROM account_general_transaction_new 
    //         WHERE MONTH(date_of_payment) = :month 
    //         AND YEAR(date_of_payment) = :year
    //         AND (approval_status = 'Approved' OR approval_status = '')
    //     ");
    //     $this->db->bind(':month', $month);
    //     $this->db->bind(':year', $year);
    //     $achieved_result = $this->db->single();
        
    //     $monthly_budget = isset($budget_result['monthly_budget']) ? $budget_result['monthly_budget'] : 0;
    //     $achieved = isset($achieved_result['achieved']) ? $achieved_result['achieved'] : 0;
    //     $variance = $achieved - $monthly_budget;
    //     $percentage = $monthly_budget > 0 ? ($achieved / $monthly_budget) * 100 : 0;
        
    //     return [
    //         'month' => $month,
    //         'month_name' => strtoupper(date('F', mktime(0, 0, 0, $month, 1))),
    //         'monthly_budget' => $monthly_budget,
    //         'achieved' => $achieved,
    //         'variance' => $variance,
    //         'percentage' => $percentage
    //     ];
    // }
    
    /**
     * Get income line breakdown for a specific month
     */
    public function getMonthlyIncomeLineBreakdown($month, $year) {
        $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
        
        $this->db->query("
            SELECT 
                bl.acct_id,
                bl.acct_desc,
                bl.{$month_field} as monthly_budget,
                COALESCE(SUM(t.amount_paid), 0) as achieved
            FROM budget_lines bl
            LEFT JOIN account_general_transaction_new t ON bl.acct_id = t.credit_account
                AND MONTH(t.date_of_payment) = :month 
                AND YEAR(t.date_of_payment) = :year
                AND (t.approval_status = 'Approved' OR t.approval_status = '')
            WHERE bl.budget_year = :year 
            AND bl.status = 'Active'
            GROUP BY bl.acct_id, bl.acct_desc, bl.{$month_field}
            ORDER BY bl.acct_desc ASC
        ");
        
        $this->db->bind(':month', $month);
        $this->db->bind(':year', $year);
        
        $income_lines = $this->db->resultSet();
        
        foreach ($income_lines as &$line) {
            $line['variance'] = $line['achieved'] - $line['monthly_budget'];
            $line['percentage'] = $line['monthly_budget'] > 0 ? 
                ($line['achieved'] / $line['monthly_budget']) * 100 : 0;
        }
        
        return $income_lines;
    }
    
    /**
     * Get quarterly performance summary
     */
    public function getQuarterlyPerformance($year) {
        $quarters = [
            'Q1' => ['months' => [1, 2, 3], 'name' => 'Q1 (Jan-Mar)'],
            'Q2' => ['months' => [4, 5, 6], 'name' => 'Q2 (Apr-Jun)'],
            'Q3' => ['months' => [7, 8, 9], 'name' => 'Q3 (Jul-Sep)'],
            'Q4' => ['months' => [10, 11, 12], 'name' => 'Q4 (Oct-Dec)']
        ];
        
        $quarterly_data = [];
        
        foreach ($quarters as $quarter => $info) {
            $quarter_budget = 0;
            $quarter_achieved = 0;
            
            foreach ($info['months'] as $month) {
                $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
                
                // Get budget
                $this->db->query("
                    SELECT SUM({$month_field}) as monthly_budget
                    FROM budget_lines 
                    WHERE budget_year = :year 
                    AND status = 'Active'
                ");
                $this->db->bind(':year', $year);
                $budget_result = $this->db->single();
                
                // Get achieved
                $this->db->query("
                    SELECT COALESCE(SUM(amount_paid), 0) as achieved
                    FROM account_general_transaction_new 
                    WHERE MONTH(date_of_payment) = :month 
                    AND YEAR(date_of_payment) = :year
                    AND (approval_status = 'Approved')
                ");
                $this->db->bind(':month', $month);
                $this->db->bind(':year', $year);
                $achieved_result = $this->db->single();
                
                $quarter_budget += isset($budget_result['monthly_budget']) ? $budget_result['monthly_budget'] : 0;
                $quarter_achieved += isset($achieved_result['achieved']) ? $achieved_result['achieved'] : 0;
            }
            
            $quarterly_data[$quarter] = [
                'name' => $info['name'],
                'budget' => $quarter_budget,
                'achieved' => $quarter_achieved,
                'variance' => $quarter_achieved - $quarter_budget,
                'percentage' => $quarter_budget > 0 ? ($quarter_achieved / $quarter_budget) * 100 : 0
            ];
        }
        
        return $quarterly_data;
    }
    
    /**
     * Get performance trends and insights
     */
    public function getPerformanceInsights($year) {
        $annual_data = $this->getAnnualPerformanceSummary($year);
        $monthly_data = $annual_data['monthly_summary'];
        
        $insights = [];
        
        // Find best and worst performing months
        $best_month = null;
        $worst_month = null;
        $best_percentage = 0;
        $worst_percentage = INF;
        //$worst_percentage = PHP_FLOAT_MAX; 5.6 xamp doesn't take it
        
        foreach ($monthly_data as $month) {
            if ($month['monthly_budget'] > 0) {
                if ($month['percentage'] > $best_percentage) {
                    $best_percentage = $month['percentage'];
                    $best_month = $month;
                }
                if ($month['percentage'] < $worst_percentage) {
                    $worst_percentage = $month['percentage'];
                    $worst_month = $month;
                }
            }
        }
        
        // Calculate trend
        $first_half = array_slice($monthly_data, 0, 6);
        $second_half = array_slice($monthly_data, 6, 6);
        
        $first_half_avg = array_sum(array_column($first_half, 'percentage')) / 6;
        $second_half_avg = array_sum(array_column($second_half, 'percentage')) / 6;
        
        $trend_direction = $second_half_avg > $first_half_avg ? 'improving' : 
                          ($second_half_avg < $first_half_avg ? 'declining' : 'stable');
        
        // Count months above/below target
        $months_above_target = count(array_filter($monthly_data, function($month) {
            return $month['percentage'] >= 100;
        }));
        
        $months_below_target = count(array_filter($monthly_data, function($month) {
            return $month['percentage'] < 100 && $month['monthly_budget'] > 0;
        }));
        
        return [
            'best_month' => $best_month,
            'worst_month' => $worst_month,
            'trend_direction' => $trend_direction,
            'first_half_avg' => $first_half_avg,
            'second_half_avg' => $second_half_avg,
            'months_above_target' => $months_above_target,
            'months_below_target' => $months_below_target
        ];
    }
}
// class AnnualBudgetPerformanceAnalyzer {
//     private $db;
//     private $budget_manager;
    
//     public function __construct() {
//         $this->db = new Database();
//         $this->budget_manager = new BudgetManager();
//     }
    
//     /**
//      * Get annual budget performance summary
//      */
//     public function getAnnualPerformanceSummary($year) {
//         $monthly_summary = [];
//         $annual_totals = [
//             'total_budget' => 0,
//             'total_achieved' => 0,
//             'total_variance' => 0,
//             'overall_percentage' => 0
//         ];
        
//         for ($month = 1; $month <= 12; $month++) {
//             $month_data = $this->getMonthlyPerformance($month, $year);
//             $monthly_summary[] = $month_data;
            
//             $annual_totals['total_budget'] += $month_data['monthly_budget'];
//             $annual_totals['total_achieved'] += $month_data['achieved'];
//         }
        
//         $annual_totals['total_variance'] = $annual_totals['total_achieved'] - $annual_totals['total_budget'];
//         $annual_totals['overall_percentage'] = $annual_totals['total_budget'] > 0 ? 
//             ($annual_totals['total_achieved'] / $annual_totals['total_budget']) * 100 : 0;
        
//         return [
//             'monthly_summary' => $monthly_summary,
//             'annual_totals' => $annual_totals
//         ];
//     }

//     /**
//      * Get monthly performance for a specific month
//      */
//     private function getMonthlyPerformance($month, $year) {
//         $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
        
//         $this->db->query("
//             SELECT 
//                 SUM(bl.{$month_field}) as monthly_budget,
//                 COALESCE(SUM(t.amount_paid), 0) as achieved
//             FROM budget_lines bl
//             LEFT JOIN account_general_transaction_new t 
//                 ON bl.acct_id = t.credit_account
//                 AND MONTH(t.date_of_payment) = :month
//                 AND YEAR(t.date_of_payment) = :year
//                 AND (t.approval_status = 'Approved' OR t.approval_status = '')
//             WHERE bl.budget_year = :year
//             AND bl.status = 'Active'
//         ");
//         $this->db->bind(':month', $month);
//         $this->db->bind(':year', $year);
//         $result = $this->db->single();

//         $monthly_budget = isset($result['monthly_budget']) ? (float)$result['monthly_budget'] : 0;
//         $achieved = isset($result['achieved']) ? (float)$result['achieved'] : 0;
//         $variance = $achieved - $monthly_budget;
//         $percentage = $monthly_budget > 0 ? ($achieved / $monthly_budget) * 100 : 0;

//         return [
//             'month' => $month,
//             'month_name' => strtoupper(date('F', mktime(0, 0, 0, $month, 1))),
//             'monthly_budget' => $monthly_budget,
//             'achieved' => $achieved,
//             'variance' => $variance,
//             'percentage' => $percentage
//         ];
//     }

//     /**
//      * Get income line breakdown for a specific month
//      */
//     public function getMonthlyIncomeLineBreakdown($month, $year) {
//         $month_field = strtolower(date('F', mktime(0, 0, 0, $month, 1))) . '_budget';
        
//         $this->db->query("
//             SELECT 
//                 bl.acct_id,
//                 bl.acct_desc,
//                 bl.{$month_field} as monthly_budget,
//                 COALESCE(SUM(t.amount_paid), 0) as achieved
//             FROM budget_lines bl
//             LEFT JOIN account_general_transaction_new t 
//                 ON bl.acct_id = t.credit_account
//                 AND MONTH(t.date_of_payment) = :month 
//                 AND YEAR(t.date_of_payment) = :year
//                 AND (t.approval_status = 'Approved' OR t.approval_status = '')
//             WHERE bl.budget_year = :year 
//             AND bl.status = 'Active'
//             GROUP BY bl.acct_id, bl.acct_desc, bl.{$month_field}
//             ORDER BY bl.acct_desc ASC
//         ");
        
//         $this->db->bind(':month', $month);
//         $this->db->bind(':year', $year);
//         $income_lines = $this->db->resultSet();
        
//         foreach ($income_lines as &$line) {
//             $line['variance'] = $line['achieved'] - $line['monthly_budget'];
//             $line['percentage'] = $line['monthly_budget'] > 0 ? 
//                 ($line['achieved'] / $line['monthly_budget']) * 100 : 0;
//         }
        
//         return $income_lines;
//     }
    
//     /**
//      * Get quarterly performance summary
//      */
//     public function getQuarterlyPerformance($year) {
//         $quarters = [
//             'Q1' => ['months' => [1, 2, 3], 'name' => 'Q1 (Jan-Mar)'],
//             'Q2' => ['months' => [4, 5, 6], 'name' => 'Q2 (Apr-Jun)'],
//             'Q3' => ['months' => [7, 8, 9], 'name' => 'Q3 (Jul-Sep)'],
//             'Q4' => ['months' => [10, 11, 12], 'name' => 'Q4 (Oct-Dec)']
//         ];
        
//         $quarterly_data = [];
        
//         foreach ($quarters as $quarter => $info) {
//             $budget_fields = [];
//             foreach ($info['months'] as $m) {
//                 $budget_fields[] = "bl." . strtolower(date('F', mktime(0, 0, 0, $m, 1))) . "_budget";
//             }
//             $budget_sum = implode(" + ", $budget_fields);

//             $this->db->query("
//                 SELECT 
//                     SUM({$budget_sum}) as quarter_budget,
//                     COALESCE(SUM(t.amount_paid), 0) as achieved
//                 FROM budget_lines bl
//                 LEFT JOIN account_general_transaction_new t 
//                     ON bl.acct_id = t.credit_account
//                     AND MONTH(t.date_of_payment) IN (" . implode(',', $info['months']) . ")
//                     AND YEAR(t.date_of_payment) = :year
//                     AND (t.approval_status = 'Approved' OR t.approval_status = '')
//                 WHERE bl.budget_year = :year
//                 AND bl.status = 'Active'
//             ");
//             $this->db->bind(':year', $year);
//             $res = $this->db->single();

//             $quarter_budget = isset($res['quarter_budget']) ? (float)$res['quarter_budget'] : 0;
//             $quarter_achieved = isset($res['achieved']) ? (float)$res['achieved'] : 0;

//             $quarterly_data[$quarter] = [
//                 'name' => $info['name'],
//                 'budget' => $quarter_budget,
//                 'achieved' => $quarter_achieved,
//                 'variance' => $quarter_achieved - $quarter_budget,
//                 'percentage' => $quarter_budget > 0 ? ($quarter_achieved / $quarter_budget) * 100 : 0
//             ];
//         }
        
//         return $quarterly_data;
//     }
    
//     /**
//      * Get performance trends and insights
//      */
//     public function getPerformanceInsights($year) {
//         $annual_data = $this->getAnnualPerformanceSummary($year);
//         $monthly_data = $annual_data['monthly_summary'];
        
//         $best_month = null;
//         $worst_month = null;
//         $best_percentage = 0;
//         $worst_percentage = INF; // PHP 5.6 safe
        
//         foreach ($monthly_data as $month) {
//             if ($month['monthly_budget'] > 0) {
//                 if ($month['percentage'] > $best_percentage) {
//                     $best_percentage = $month['percentage'];
//                     $best_month = $month;
//                 }
//                 if ($month['percentage'] < $worst_percentage) {
//                     $worst_percentage = $month['percentage'];
//                     $worst_month = $month;
//                 }
//             }
//         }
        
//         $first_half = array_slice($monthly_data, 0, 6);
//         $second_half = array_slice($monthly_data, 6, 6);
        
//         $first_half_avg = array_sum(array_column($first_half, 'percentage')) / 6;
//         $second_half_avg = array_sum(array_column($second_half, 'percentage')) / 6;
        
//         $trend_direction = $second_half_avg > $first_half_avg ? 'improving' : 
//                           ($second_half_avg < $first_half_avg ? 'declining' : 'stable');
        
//         $months_above_target = count(array_filter($monthly_data, function($month) {
//             return $month['percentage'] >= 100;
//         }));
        
//         $months_below_target = count(array_filter($monthly_data, function($month) {
//             return $month['percentage'] < 100 && $month['monthly_budget'] > 0;
//         }));
        
//         return [
//             'best_month' => $best_month,
//             'worst_month' => $worst_month,
//             'trend_direction' => $trend_direction,
//             'first_half_avg' => $first_half_avg,
//             'second_half_avg' => $second_half_avg,
//             'months_above_target' => $months_above_target,
//             'months_below_target' => $months_below_target
//         ];
//     }
// }

$analyzer = new AnnualBudgetPerformanceAnalyzer();

// Check access permissions
//$can_view = $analyzer->budget_manager->checkAccess($staff['user_id'], 'can_view_budget');
// Check access permissions
$can_view = $manager->checkAccess($staff['level'], 'can_view_budget');

if (!$can_view) {
    header('Location: index.php?error=access_denied');
    exit;
}

$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : null;

// Get data
$annual_data = $analyzer->getAnnualPerformanceSummary($selected_year);
$quarterly_data = $analyzer->getQuarterlyPerformance($selected_year);
$insights = $analyzer->getPerformanceInsights($selected_year);

// Get monthly breakdown for selected month if provided
$monthly_breakdown = null;
if ($selected_month) {
    $monthly_breakdown = $analyzer->getMonthlyIncomeLineBreakdown($selected_month, $selected_year);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Annual Budget Performance Summary</title>
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
                    <h1 class="text-xl font-bold text-gray-900"><?php echo APP_NAME; ?> - Annual Budget Performance Summary</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-users mr-1"></i>
                        General Officers Performance
                    </a>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                            <?php echo strtoupper(substr($staff['full_name'], 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-700"><?php echo $staff['full_name']; ?></span>
                    </div>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                        <?php echo $staff['department']; ?>
                    </span>
                    <button onclick="logout()" class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Logout
                    </button>
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
                        <h2 class="text-2xl font-bold text-gray-900">Annual Budget Performance Summary</h2>
                        <p class="text-gray-600">Monthly budget vs achieved performance for <?php echo $selected_year; ?> (Variable Income Lines)</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Year Selection -->
                        <form method="GET" class="flex gap-2">
                            <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                            <select name="year" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <?php for ($y = date('Y') - 3; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Load Year
                            </button>
                        </form>
                        
                        <!-- Export Options -->
                        <div class="flex gap-2">
                            <button onclick="exportToExcel()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                <i class="fas fa-file-excel mr-2"></i>Export Excel
                            </button>
                            <button onclick="printReport()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Annual Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-chart-pie text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Annual Budget</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($annual_data['annual_totals']['total_budget']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Achieved</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($annual_data['annual_totals']['total_achieved']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $annual_data['annual_totals']['total_variance'] >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Variance</p>
                        <p class="text-2xl font-bold <?php echo $annual_data['annual_totals']['total_variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $annual_data['annual_totals']['total_variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($annual_data['annual_totals']['total_variance']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $annual_data['annual_totals']['overall_percentage'] >= 100 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Overall Achievement</p>
                        <p class="text-2xl font-bold <?php echo $annual_data['annual_totals']['overall_percentage'] >= 100 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($annual_data['annual_totals']['overall_percentage'], 2); ?>%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $insights['months_above_target']; ?></div>
                    <div class="text-sm text-gray-500">Months Above Target</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $insights['months_below_target']; ?></div>
                    <div class="text-sm text-gray-500">Months Below Target</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">
                        <?php echo $insights['best_month'] ? $insights['best_month']['month_name'] : 'N/A'; ?>
                    </div>
                    <div class="text-sm text-gray-500">Best Month</div>
                    <?php if ($insights['best_month']): ?>
                        <div class="text-xs text-gray-400"><?php echo number_format($insights['best_month']['percentage'], 1); ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        <?php echo ucfirst($insights['trend_direction']); ?>
                    </div>
                    <div class="text-sm text-gray-500">Annual Trend</div>
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

        <!-- Monthly Performance Summary Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    SUMMARY MONTHLY PERFORMANCE FROM JANUARY TO DECEMBER, <?php echo $selected_year; ?> (VARIABLE INCOME LINES)
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table id="performanceTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Budget</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achieved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Achieved</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($annual_data['monthly_summary'] as $month_data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $month_data['month_name']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($month_data['monthly_budget'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($month_data['achieved'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span class="<?php echo $month_data['variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                    <?php echo $month_data['variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($month_data['variance'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-lg font-bold <?php echo $month_data['percentage'] >= 100 ? 'text-green-600' : ($month_data['percentage'] >= 90 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($month_data['percentage'], 2); ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-20 bg-gray-200 rounded-full h-3">
                                        <div class="<?php echo $month_data['percentage'] >= 100 ? 'bg-green-500' : ($month_data['percentage'] >= 90 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-3 rounded-full transition-all duration-500" 
                                             style="width: <?php echo min(100, $month_data['percentage']); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="?year=<?php echo $selected_year; ?>&month=<?php echo $month_data['month']; ?>" 
                                   class="text-blue-600 hover:text-blue-800" title="View Monthly Breakdown">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-900">TOTAL</th>
                            <th class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                ₦<?php echo number_format($annual_data['annual_totals']['total_budget'], 2); ?>
                            </th>
                            <th class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                ₦<?php echo number_format($annual_data['annual_totals']['total_achieved'], 2); ?>
                            </th>
                            <th class="px-6 py-3 text-right text-sm font-bold <?php echo $annual_data['annual_totals']['total_variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $annual_data['annual_totals']['total_variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($annual_data['annual_totals']['total_variance'], 2); ?>
                            </th>
                            <th class="px-6 py-3 text-center text-sm font-bold <?php echo $annual_data['annual_totals']['overall_percentage'] >= 100 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo number_format($annual_data['annual_totals']['overall_percentage'], 2); ?>%
                            </th>
                            <th colspan="2" class="px-6 py-3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Monthly Income Line Breakdown -->
        <?php if ($monthly_breakdown): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <?php echo strtoupper(date('F', mktime(0, 0, 0, $selected_month, 1))); ?> <?php echo $selected_year; ?> - Income Line Breakdown
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Budget</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achieved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Achieved</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($monthly_breakdown as $line): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $line['acct_desc']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($line['monthly_budget'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($line['achieved'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span class="<?php echo $line['variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                    <?php echo $line['variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($line['variance'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold <?php echo $line['percentage'] >= 100 ? 'text-green-600' : ($line['percentage'] >= 90 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($line['percentage'], 2); ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="<?php echo $line['percentage'] >= 100 ? 'bg-green-500' : ($line['percentage'] >= 90 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-2 rounded-full" 
                                             style="width: <?php echo min(100, $line['percentage']); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quarterly Summary -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quarterly Performance Summary</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quarter</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quarterly Budget</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achieved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Achieved</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($quarterly_data as $quarter => $data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $data['name']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($data['budget'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($data['achieved'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span class="<?php echo $data['variance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                    <?php echo $data['variance'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($data['variance'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-lg font-bold <?php echo $data['percentage'] >= 100 ? 'text-green-600' : ($data['percentage'] >= 90 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($data['percentage'], 2); ?>%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $data['percentage'] >= 100 ? 'bg-green-100 text-green-800' : 
                                              ($data['percentage'] >= 90 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo $data['percentage'] >= 100 ? 'Excellent' : ($data['percentage'] >= 90 ? 'Good' : 'Needs Improvement'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Monthly Performance Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($annual_data['monthly_summary']); ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month_name),
                datasets: [{
                    label: 'Budget (₦)',
                    data: monthlyData.map(item => item.monthly_budget),
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: false
                }, {
                    label: 'Achieved (₦)',
                    data: monthlyData.map(item => item.achieved),
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: false
                }, {
                    label: 'Target Line (100%)',
                    data: monthlyData.map(item => item.monthly_budget),
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 1,
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

        // Quarterly Chart
        const quarterlyCtx = document.getElementById('quarterlyChart').getContext('2d');
        const quarterlyData = <?php echo json_encode($quarterly_data); ?>;
        
        new Chart(quarterlyCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(quarterlyData).map(q => quarterlyData[q].name),
                datasets: [{
                    label: 'Budget (₦)',
                    data: Object.values(quarterlyData).map(q => q.budget),
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

        // Export to Excel function
        function exportToExcel() {
            let csv = 'Monthly,Monthly Budget,Achieved,Variance,% Achieved\n';
            
            const table = document.getElementById('performanceTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const month = cells[0].textContent.trim();
                const budget = cells[1].textContent.trim().replace(/[₦,]/g, '');
                const achieved = cells[2].textContent.trim().replace(/[₦,]/g, '');
                const variance = cells[3].textContent.trim().replace(/[₦,]/g, '');
                const percentage = cells[4].textContent.trim().replace('%', '');
                
                csv += `"${month}","${budget}","${achieved}","${variance}","${percentage}"\n`;
            });
            
            // Add totals
            const totalRow = table.querySelector('tfoot tr');
            const totalCells = totalRow.querySelectorAll('th');
            const totalBudget = totalCells[1].textContent.trim().replace(/[₦,]/g, '');
            const totalAchieved = totalCells[2].textContent.trim().replace(/[₦,]/g, '');
            const totalVariance = totalCells[3].textContent.trim().replace(/[₦,]/g, '');
            const totalPercentage = totalCells[4].textContent.trim().replace('%', '');
            
            csv += `"TOTAL","${totalBudget}","${totalAchieved}","${totalVariance}","${totalPercentage}"\n`;
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `annual_budget_performance_${<?php echo $selected_year; ?>}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Print function
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