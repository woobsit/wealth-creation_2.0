<?php
ob_start();
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../helpers/session_helper.php';
require_once '../models/OfficerPerformanceAnalyzer.php';
require_once '../models/OfficerTargetManager.php';
// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

$analyzer = new OfficerPerformanceAnalyzer();
$target_manager = new OfficerTargetManager();

// Get current date info
$current_date = date('Y-m-d');
$selected_month = isset($_GET['smonth']) ? $_GET['smonth'] : date('n');
$selected_year = isset($_GET['syear']) ? $_GET['syear'] : date('Y');
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$per_page = 15;
$selected_officer = isset($_GET['sstaff']) ? $_GET['sstaff'] : null;

// Parse officer ID and type
$is_other_staff = false;
$officer_id = null;
if ($selected_officer) {
    if (strpos($selected_officer, '-so') !== false) {
        $officer_id = str_replace('-so', '', $selected_officer);
        $is_other_staff = true;
    } else {
        $officer_id = $selected_officer;
    }
}

$selected_month_name = date('F', mktime(0, 0, 0, $selected_month, 1));

// Get data
$wc_officers = $analyzer->getWealthCreationOfficers();
$other_officers = $analyzer->getOtherOfficers();
$officer_comparison = $analyzer->getOfficerComparison($selected_month, $selected_year);
$top_performers = $analyzer->getTopPerformers($selected_month, $selected_year, 5);
$sundays = $analyzer->getSundayPositions($selected_month, $selected_year);

// Filter for Wealth Creation department only
$officer_comparison = array_filter($officer_comparison, function($officer) {
    return $officer['department'] === 'Wealth Creation';
});

// Get officer targets and performance data
$officers_with_targets = [];
foreach ($officer_comparison as $officer) {
    $targets = $target_manager->getOfficerTargets($officer['user_id'], $selected_month, $selected_year);
    $performance_summary = $target_manager->getOfficerPerformanceSummary($officer['user_id'], $selected_month, $selected_year);
    $detailed_performance = $target_manager->getOfficerDetailedPerformance($officer['user_id'], $selected_month, $selected_year);
    
    // Calculate totals
    $total_target = array_sum(array_column($targets, 'monthly_target'));
    $total_achieved = $officer['total_collections'];
    $achievement_percentage = $total_target > 0 ? ($total_achieved / $total_target) * 100 : 0;
    $deficit = $total_target - $total_achieved;
    
    // Calculate performance score
    $performance_score = 0;
    if ($total_target > 0) {
        if ($total_achieved >= $total_target * 1.5) $performance_score = 100;
        elseif ($total_achieved >= $total_target * 1.2) $performance_score = 90;
        elseif ($total_achieved >= $total_target) $performance_score = 80;
        elseif ($total_achieved >= $total_target * 0.8) $performance_score = 70;
        elseif ($total_achieved >= $total_target * 0.6) $performance_score = 60;
        elseif ($total_achieved >= $total_target * 0.4) $performance_score = 50;
        elseif ($total_achieved >= $total_target * 0.2) $performance_score = 40;
        else $performance_score = 30;
    }
    
    $officers_with_targets[] = [
        'officer' => $officer,
        'targets' => $targets,
        'performance_summary' => $performance_summary,
        'detailed_performance' => $detailed_performance,
        'total_target' => $total_target,
        'total_achieved' => $total_achieved,
        'achievement_percentage' => $achievement_percentage,
        'deficit' => $deficit,
        'performance_score' => $performance_score,
        'assigned_lines' => count($targets)
    ];
}

// Sort by performance score descending
usort($officers_with_targets, function($a, $b) {
    if ($a['performance_score'] == $b['performance_score']) {
        return 0;
    }
    return ($a['performance_score'] < $b['performance_score']) ? 1 : -1;
});

// Pagination
$total_officers = count($officers_with_targets);
$total_pages = ceil($total_officers / $per_page);
$offset = ($page - 1) * $per_page;
$paginated_officers = array_slice($officers_with_targets, $offset, $per_page);

// Get specific officer data if selected
$officer_info = null;
$officer_performance = [];
$officer_daily_data = [];
$officer_rating = null;
$officer_trends = [];
$officer_insights = [];
$officer_targets_detail = [];

if ($officer_id) {
    $officer_info = $analyzer->getOfficerInfo($officer_id, $is_other_staff);
    $officer_performance = $analyzer->getOfficerPerformance($officer_id, $selected_month, $selected_year, $is_other_staff);
    $officer_daily_data = $analyzer->getOfficerDailyPerformance($officer_id, $selected_month, $selected_year, $is_other_staff);
    $officer_rating = $analyzer->getOfficerRating($officer_id, $selected_month, $selected_year, $is_other_staff);
    $officer_trends = $analyzer->getOfficerTrends($officer_id, $selected_month, $selected_year, $is_other_staff);
    $officer_insights = $analyzer->getPerformanceInsights($officer_id, $selected_month, $selected_year, $is_other_staff);
    $officer_targets_detail = $target_manager->getOfficerTargets($officer_id, $selected_month, $selected_year);
}

// Calculate overall statistics
$total_all_targets = array_sum(array_column($officers_with_targets, 'total_target'));
$total_all_achieved = array_sum(array_column($officers_with_targets, 'total_achieved'));
$overall_achievement = $total_all_targets > 0 ? ($total_all_achieved / $total_all_targets) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Officer Performance Analysis</title>
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
                    <h1 class="text-xl font-bold text-gray-900"><?php echo APP_NAME; ?> - Officer Performance</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="mpr_income_lines.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-chart-line mr-1"></i>
                        General Summary
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
                        <h2 class="text-2xl font-bold text-gray-900">Officer Performance Analysis</h2>
                        <p class="text-gray-600">
                            <?php echo $selected_month_name . ' ' . $selected_year; ?> Target vs Achievement Analysis
                        </p>
                    </div>
                    
                    <!-- Filter Form -->
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Officer</label>
                            <select name="sstaff" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Officers</option>
                                <optgroup label="Wealth Creation Officers">
                                    <?php foreach ($wc_officers as $officer): ?>
                                        <option value="<?php echo $officer['user_id']; ?>" 
                                                <?php echo $selected_officer == $officer['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo $officer['full_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Other Officers">
                                    <?php foreach ($other_officers as $officer): ?>
                                        <option value="<?php echo $officer['id']; ?>-so" 
                                                <?php echo $selected_officer == $officer['id'] . '-so' ? 'selected' : ''; ?>>
                                            <?php echo $officer['full_name'] . ' - ' . $officer['department']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                            <select name="smonth" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <select name="syear" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <?php for ($y = date('Y') - 3; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-search mr-2"></i>Analyze
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Overall Performance Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-bullseye text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Targets</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_all_targets); ?></p>
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
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_all_achieved); ?></p>
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

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Officers</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_officers; ?></p>
                        <p class="text-xs text-gray-500">with targets</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($officer_info): ?>
        <!-- Individual Officer Analysis -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                            <?php echo strtoupper(substr($officer_info['full_name'], 0, 2)); ?>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $officer_info['full_name']; ?></h3>
                            <p class="text-gray-600"><?php echo $officer_info['department']; ?></p>
                            <?php if ($officer_info['phone_no']): ?>
                                <p class="text-sm text-gray-500"><?php echo $officer_info['phone_no']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $officer_rating['rating_class']; ?>">
                            <?php echo $officer_rating['rating']; ?>
                        </span>
                        <p class="text-sm text-gray-500 mt-1">
                            Performance: <?php echo number_format($officer_rating['performance_ratio'], 1); ?>%
                        </p>
                    </div>
                </div>

                <!-- Officer Performance Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">₦<?php echo number_format($officer_rating['officer_total']); ?></div>
                        <div class="text-sm text-gray-500">Total Collections</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo count($officer_targets_detail); ?></div>
                        <div class="text-sm text-gray-500">Assigned Lines</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo count(array_filter($officer_daily_data)); ?></div>
                        <div class="text-sm text-gray-500">Active Days</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">₦<?php echo number_format(count(array_filter($officer_daily_data)) > 0 ? $officer_rating['officer_total'] / count(array_filter($officer_daily_data)) : 0); ?></div>
                        <div class="text-sm text-gray-500">Daily Average</div>
                    </div>
                </div>

                <!-- Officer Income Line Performance -->
                <?php if (!empty($officer_targets_detail)): ?>
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Income Line Performance vs Targets</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achieved</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement %</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance Bar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($officer_targets_detail as $target): ?>
                                <?php
                                // Find actual performance for this income line
                                $achieved_amount = 0;
                                foreach ($officer_performance as $perf) {
                                    if ($perf['acct_id'] === $target['acct_id']) {
                                        $achieved_amount = $perf['total_amount'];
                                        break;
                                    }
                                }
                                $line_achievement = $target['monthly_target'] > 0 ? ($achieved_amount / $target['monthly_target']) * 100 : 0;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $target['acct_desc']; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        ₦<?php echo number_format($target['monthly_target']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                        ₦<?php echo number_format($achieved_amount); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                        <span class="<?php echo $line_achievement >= 100 ? 'text-green-600' : ($line_achievement >= 80 ? 'text-yellow-600' : 'text-red-600'); ?> font-bold">
                                            <?php echo number_format($line_achievement, 1); ?>%
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center">
                                            <div class="w-20 bg-gray-200 rounded-full h-3">
                                                <div class="<?php echo $line_achievement >= 100 ? 'bg-green-500' : ($line_achievement >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-3 rounded-full transition-all duration-300" 
                                                     style="width: <?php echo min(100, $line_achievement); ?>%"></div>
                                            </div>
                                            <span class="ml-2 text-xs text-gray-500">
                                                <?php echo number_format($line_achievement, 0); ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Performance Insights -->
                <?php if (!empty($officer_insights)): ?>
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($officer_insights as $insight): ?>
                        <div class="border-l-4 <?php echo $insight['type'] === 'success' ? 'border-green-500 bg-green-50' : 'border-yellow-500 bg-yellow-50'; ?> p-4 rounded-r-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <?php if ($insight['type'] === 'success'): ?>
                                        <i class="fas fa-check-circle text-green-600"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo $insight['title']; ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo $insight['message']; ?></p>
                                    <p class="text-xs text-gray-500 mt-2 italic"><?php echo $insight['recommendation']; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Charts Section for Individual Officer -->
                <?php if ($officer_id && !empty($officer_trends)): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Performance Trend Chart -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">6-Month Performance Trend</h4>
                        <div class="relative h-48">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <!-- Income Line Distribution -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Income Line Distribution</h4>
                        <div class="relative h-48">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Performers Section -->
        <!-- <div class="mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Top Performers - <?php echo $selected_month_name . ' ' . $selected_year; ?></h3>
                    <a href="officer_reward_system.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-trophy mr-1"></i>
                        Reward System
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach (array_slice($top_performers, 0, 6) as $index => $performer): ?>
                    <div class="bg-gradient-to-r <?php echo $index === 0 ? 'from-yellow-400 to-yellow-600' : ($index === 1 ? 'from-gray-400 to-gray-600' : ($index === 2 ? 'from-orange-400 to-orange-600' : 'from-blue-400 to-blue-600')); ?> rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold">#<?php echo $index + 1; ?></div>
                                <div class="text-sm opacity-90">Rank</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold">₦<?php echo number_format($performer['total_collections']); ?></div>
                                <div class="text-xs opacity-90"><?php echo $performer['total_transactions']; ?> transactions</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="font-medium"><?php echo $performer['full_name']; ?></div>
                            <div class="text-xs opacity-90"><?php echo $performer['department']; ?></div>
                        </div>
                        <div class="mt-2 flex justify-between text-xs">
                            <span><?php echo $performer['active_days']; ?> active days</span>
                            <span>₦<?php echo number_format($performer['avg_per_day']); ?>/day</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div> -->

        <!-- Officer Performance Comparison Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Officer Performance vs Targets Analysis</h3>
                    <div class="text-sm text-gray-500">
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_officers); ?> of <?php echo $total_officers; ?> officers
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Target</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Deficit</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement %</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Lines</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance Score</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Performance Bar</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($paginated_officers as $index => $officer_data): ?>
                        <?php $global_rank = $offset + $index + 1; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($global_rank <= 3): ?>
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold
                                            <?php echo $global_rank === 1 ? 'bg-yellow-500' : ($global_rank === 2 ? 'bg-gray-500' : 'bg-orange-500'); ?>">
                                            <?php echo $global_rank; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-blue-100 text-blue-800 text-sm font-bold">
                                            <?php echo $global_rank; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        <?php echo strtoupper(substr($officer_data['officer']['full_name'], 0, 2)); ?>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $officer_data['officer']['full_name']; ?></div>
                                        <div class="text-xs text-gray-500"><?php echo $officer_data['officer']['department']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($officer_data['total_target']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($officer_data['total_achieved']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                <span class="<?php echo $officer_data['deficit'] > 0 ? 'text-red-600' : 'text-green-600'; ?> font-medium">
                                    ₦<?php echo number_format(abs($officer_data['deficit'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                <span class="text-lg font-bold <?php echo $officer_data['achievement_percentage'] >= 100 ? 'text-green-600' : ($officer_data['achievement_percentage'] >= 80 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($officer_data['achievement_percentage'], 1); ?>%
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                    <?php echo $officer_data['assigned_lines']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="text-lg font-bold <?php echo $officer_data['performance_score'] >= 80 ? 'text-green-600' : ($officer_data['performance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo number_format($officer_data['performance_score'], 1); ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-4">
                                        <div class="<?php echo $officer_data['achievement_percentage'] >= 100 ? 'bg-green-500' : ($officer_data['achievement_percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-4 rounded-full transition-all duration-500" 
                                             style="width: <?php echo min(100, $officer_data['achievement_percentage']); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="?sstaff=<?php echo $officer_data['officer']['user_id'] . ($officer_data['officer']['department'] !== 'Wealth Creation' ? '-so' : ''); ?>&smonth=<?php echo $selected_month; ?>&syear=<?php echo $selected_year; ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="officer_performance_report.php?officer_id=<?php echo $officer_data['officer']['user_id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                                       class="text-green-600 hover:text-green-800" title="Detailed Report">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="officer_target_management.php?officer_id=<?php echo $officer_data['officer']['user_id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                                       class="text-purple-600 hover:text-purple-800" title="Manage Targets">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="2" class="px-4 py-3 text-left text-sm font-bold text-gray-900">TOTALS</th>
                            <th class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                ₦<?php echo number_format($total_all_targets); ?>
                            </th>
                            <th class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                ₦<?php echo number_format($total_all_achieved); ?>
                            </th>
                            <th class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                ₦<?php echo number_format($total_all_targets - $total_all_achieved); ?>
                            </th>
                            <th class="px-4 py-3 text-center text-sm font-bold text-gray-900">
                                <?php echo number_format($overall_achievement, 1); ?>%
                            </th>
                            <th colspan="4" class="px-4 py-3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_officers); ?> of <?php echo $total_officers; ?> officers
                    </div>
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                <i class="fas fa-chevron-left mr-1"></i>Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-3 py-2 text-sm font-medium <?php echo $i === (int)$page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300'; ?> border rounded-md hover:bg-gray-50">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Next<i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Decision Making Tools -->
        <!-- <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Management Decision Tools</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="officer_reward_system.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-trophy mr-2"></i>
                    Reward System
                </a>
                
                <a href="officer_target_management.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-bullseye mr-2"></i>
                    Target Management
                </a>
                
                <a href="officer_ranking_report.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-medal mr-2"></i>
                    Officer Ranking
                </a>
                
                <a href="target_vs_achievement_report.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Target vs Achievement
                </a>
            </div>
        </div> -->
    </div>

    <script>
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // In a real application, this would redirect to logout.php
                alert('Logout functionality would be implemented here');
                // window.location.href = 'logout.php';
            }
        }

        <?php if ($officer_id && !empty($officer_trends)): ?>
        // Performance Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php echo json_encode($officer_trends); ?>;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(item => item.month_name),
                datasets: [{
                    label: 'Monthly Collections (₦)',
                    data: trendData.map(item => item.total),
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
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
                                return 'Amount: ₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Income Line Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        const performanceData = <?php echo json_encode($officer_performance); ?>;
        
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: performanceData.map(item => item.income_line),
                datasets: [{
                    data: performanceData.map(item => item.total_amount),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 69, 19, 0.8)',
                        'rgba(75, 85, 99, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
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
                                return context.label + ': ₦' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

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

        // Add hover effects to cards
        document.querySelectorAll('.hover\\:shadow-lg').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'all 0.3s ease';
            });
            
            element.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>