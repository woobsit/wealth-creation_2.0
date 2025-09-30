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

// Check access permissions
$can_view = $manager->checkAccess($staff['level'], 'can_view_budget');

if (!$can_view) {
    header('Location: unauthorized.php?error=access_denied');
    exit;
}

$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : null;

// Get performance data
$performance_data = $manager->getBudgetPerformanceo($selected_year, $selected_month);
$budget_lines = $manager->getBudgetLines($selected_year);

// print_r($performance_data);
// echo"<br>";
// print_r($budget_lines);
// exit;
//Array ( [0] => Array ( [id] => 1 [acct_id] => 10400 [acct_desc] => Abattoir [budget_year] => 2025 [january_budget] => 2319850.00 [february_budget] => 2141400.00 [march_budget] => 2319850.00 [april_budget] => 2319850.00 [may_budget] => 2409075.00 [june_budget] => 2230625.00 [july_budget] => 2409075.00 [august_budget] => 2319850.00 [september_budget] => 2319850.00 [october_budget] => 2409075.00 [november_budget] => 2230625.00 [december_budget] => 2319850.00 [annual_budget] => 27748975.00 [status] => Active [created_by] => 207 [created_at] => 2025-08-26 23:48:14 [updated_by] => [updated_at] => 2025-08-27 00:11:59 [created_by_name] => Opeyemi AKINLUYI [updated_by_name] => ) [1] => Array ( [id] => 2 [acct_id] => 12050 [acct_desc] => Advert [budget_year] => 2025 [january_budget] => 1720000.00 [february_budget] => 1800000.00 [march_budget] => 3950000.00 [april_budget] => 2000000.00 [may_budget] => 1400000.00 [june_budget] => 3700000.00 [july_budget] => 1350000.00 [august_budget] => 1450000.00 [september_budget] => 950000.00 [october_budget] => 6070000.00 [november_budget] => 1300000.00 [december_budget] => 3950000.00 [annual_budget] => 29640000.00 [status] => Active [created_by] => 207 [created_at] => 2025-08-26 23:48:14 [updated_by] => [updated_at] => 2025-08-27 00:12:47 [created_by_name] => Opeyemi AKINLUYI [updated_by_name] => ) 
// Calculate summary statistics
$total_budget = array_sum(array_column($budget_lines, 'annual_budget'));
$total_actual = 0;
$above_budget_count = 0;
$on_budget_count = 0;
$below_budget_count = 0;

foreach ($performance_data as $perf) {
    $total_actual += $perf['actual_amount'];
    switch ($perf['performance_status']) {
        case 'Above Budget':
            $above_budget_count++;
            break;
        case 'On Budget':
            $on_budget_count++;
            break;
        case 'Below Budget':
            $below_budget_count++;
            break;
    }
}

$overall_variance = $total_budget > 0 ? (($total_actual - $total_budget) / $total_budget) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Budget Performance Analysis</title>
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
                    <h1 class="text-xl font-bold text-gray-900"><?php echo APP_NAME; ?> - Budget Performance Summary</h1>
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
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-chart-pie text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Budget</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_budget); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Actual Collections</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_actual); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $overall_variance >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Overall Variance</p>
                        <p class="text-2xl font-bold <?php echo $overall_variance >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $overall_variance >= 0 ? '+' : ''; ?><?php echo number_format($overall_variance, 1); ?>%
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-tasks text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Performance Status</p>
                        <div class="text-sm text-gray-900">
                            <div class="text-green-600"><?php echo $above_budget_count; ?> Above</div>
                            <div class="text-blue-600"><?php echo $on_budget_count; ?> On Target</div>
                            <div class="text-red-600"><?php echo $below_budget_count; ?> Below</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Analysis Chart -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Budget vs Actual Performance</h3>
            <div class="relative h-64">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Detailed Performance Table -->
        <!-- <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Detailed Performance Analysis</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Budgeted</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Variance %</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($performance_data as $perf): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $perf['acct_desc']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                               As at <?php echo date('M Y', mktime(0, 0, 0, $perf['performance_month'], 1, $perf['performance_year'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($perf['budgeted_amount']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                ₦<?php echo number_format($perf['actual_amount']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right 
                                <?php echo $perf['variance_amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $perf['variance_amount'] >= 0 ? '+' : ''; ?>₦<?php echo number_format($perf['variance_amount']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium
                                <?php echo $perf['variance_percentage'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $perf['variance_percentage'] >= 0 ? '+' : ''; ?><?php echo number_format($perf['variance_percentage'], 1); ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $perf['performance_status'] === 'Above Budget' ? 'bg-green-100 text-green-800' : 
                                              ($perf['performance_status'] === 'On Budget' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo $perf['performance_status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div> -->
    </div>

    <script>
        // Performance Chart
        const perfCtx = document.getElementById('performanceChart').getContext('2d');
        const perfData = <?php echo json_encode($performance_data); ?>;
        
        new Chart(perfCtx, {
            type: 'bar',
            data: {
                labels: perfData.map(item => item.acct_desc.length > 15 ? 
                    item.acct_desc.substring(0, 15) + '...' : item.acct_desc),
                datasets: [{
                    label: 'Budgeted (₦)',
                    data: perfData.map(item => item.budgeted_amount),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }, {
                    label: 'Actual (₦)',
                    data: perfData.map(item => item.actual_amount),
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