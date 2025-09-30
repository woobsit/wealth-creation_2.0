
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
requireAnyRole(['admin', 'manager', 'accounts', 'financial_controller']);

// Use session data directly instead of database queries
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];
$userRole = $_SESSION['user_role'];

// Initialize database
$database = new Database();

// Get filter parameters
$period = isset($_GET['period']) ? sanitize($_GET['period']) : 'week';
$income_line = isset($_GET['income_line']) ? sanitize($_GET['income_line']) : '';
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';

// Set date ranges based on period
switch($period) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-d');
        break;
    case 'quarter':
        $start_date = date('Y-m-01', strtotime('-3 months'));
        $end_date = date('Y-m-d');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-m-d');
        break;
}

// Get all unique income lines
$database->query("SELECT DISTINCT income_line FROM account_general_transaction_new WHERE income_line IS NOT NULL AND income_line != '' ORDER BY income_line ASC");
$incomeLines = $database->resultSet();

// Build query for income performance
$query = "SELECT 
    income_line,
    DATE(date_of_payment) as payment_date,
    COUNT(*) as transaction_count,
    SUM(amount_paid) as total_amount,
    AVG(amount_paid) as avg_amount,
    MAX(amount_paid) as max_amount,
    MIN(amount_paid) as min_amount
FROM account_general_transaction_new 
WHERE date_of_payment BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if (!empty($income_line)) {
    $query .= " AND income_line = :income_line";
    $params[':income_line'] = $income_line;
}

$query .= " GROUP BY income_line, DATE(date_of_payment) ORDER BY payment_date DESC, total_amount DESC";

$database->query($query);
foreach($params as $param => $value) {
    $database->bind($param, $value);
}
$performanceData = $database->resultSet();

// Get summary statistics
$summaryQuery = "SELECT 
    income_line,
    COUNT(*) as total_transactions,
    SUM(amount_paid) as total_revenue,
    AVG(amount_paid) as avg_amount,
    MAX(amount_paid) as max_amount,
    MIN(amount_paid) as min_amount
FROM account_general_transaction_new 
WHERE date_of_payment BETWEEN :start_date AND :end_date";

if (!empty($income_line)) {
    $summaryQuery .= " AND income_line = :income_line";
}

$summaryQuery .= " GROUP BY income_line ORDER BY total_revenue DESC";

$database->query($summaryQuery);
foreach($params as $param => $value) {
    $database->bind($param, $value);
}
$summaryStats = $database->resultSet();

// Get top performing income lines
$topPerformersQuery = "SELECT 
    income_line,
    SUM(amount_paid) as total_revenue,
    COUNT(*) as transaction_count
FROM account_general_transaction_new 
WHERE date_of_payment BETWEEN :start_date AND :end_date
GROUP BY income_line 
ORDER BY total_revenue DESC 
LIMIT 10";

$database->query($topPerformersQuery);
$database->bind(':start_date', $start_date);
$database->bind(':end_date', $end_date);
$topPerformers = $database->resultSet();

// Get daily trends for chart
$trendsQuery = "SELECT 
    DATE(date_of_payment) as trend_date,
    SUM(amount_paid) as daily_revenue,
    COUNT(*) as daily_transactions
FROM account_general_transaction_new 
WHERE date_of_payment BETWEEN :start_date AND :end_date";

if (!empty($income_line)) {
    $trendsQuery .= " AND income_line = :income_line";
}

$trendsQuery .= " GROUP BY DATE(date_of_payment) ORDER BY trend_date ASC";

$database->query($trendsQuery);
foreach($params as $param => $value) {
    $database->bind($param, $value);
}
$trendsData = $database->resultSet();

// Calculate overall totals
$totalRevenue = array_sum(array_column($summaryStats, 'total_revenue'));
$totalTransactions = array_sum(array_column($summaryStats, 'total_transactions'));
$avgDailyRevenue = count($trendsData) > 0 ? $totalRevenue / count($trendsData) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Performance Analysis - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">Income ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">Income Performance Analysis</h1>
                        <p class="text-sm text-gray-500">Revenue Analytics & Performance Insights</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="daily_income_analysis.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium">
                        <i class="fas fa-table mr-2"></i> Daily Analysis Table
                    </a>
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($userName) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($department) ?></div>
                    </div>
                    <div class="relative">
                        <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                <?= strtoupper($userName[0]) ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <a href="daily_income_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-table mr-2"></i> Daily Analysis
                            </a>
                            <a href="transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-exchange-alt mr-2"></i> Transactions
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

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filters -->
        <div class="mb-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Analysis Filters</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
                    <select name="period" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Last 3 Months</option>
                        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>This Year</option>
                        <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Income Line</label>
                    <select name="income_line" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Income Lines</option>
                        <?php foreach($incomeLines as $line): ?>
                            <option value="<?= htmlspecialchars($line['income_line']) ?>" <?= $income_line === $line['income_line'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($line['income_line']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        <i class="fas fa-search mr-2"></i> Analyze
                    </button>
                </div>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900"><?= formatCurrency($totalRevenue) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($totalTransactions) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Avg Daily Revenue</p>
                        <p class="text-2xl font-bold text-gray-900"><?= formatCurrency($avgDailyRevenue) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Income Lines</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($summaryStats) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Trend Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend</h3>
                <canvas id="revenueTrendChart" width="400" height="200"></canvas>
            </div>

            <!-- Top Performers Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Income Lines</h3>
                <canvas id="topPerformersChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Summary Statistics Table -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Income Line Performance Summary</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($summaryStats as $stat): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($stat['income_line']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="font-medium text-green-600">
                                        <?= formatCurrency($stat['total_revenue']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($stat['total_transactions']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= formatCurrency($stat['avg_amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= formatCurrency($stat['max_amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $performance = $totalRevenue > 0 ? ($stat['total_revenue'] / $totalRevenue) * 100 : 0;
                                    $performanceClass = $performance >= 10 ? 'text-green-600' : ($performance >= 5 ? 'text-yellow-600' : 'text-red-600');
                                    ?>
                                    <span class="<?= $performanceClass ?> font-medium">
                                        <?= number_format($performance, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detailed Performance Data -->
        <?php if (!empty($performanceData)): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Daily Performance Details</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($performanceData as $data): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= formatDate($data['payment_date']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($data['income_line']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($data['transaction_count']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                    <?= formatCurrency($data['total_amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= formatCurrency($data['avg_amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= formatCurrency($data['max_amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Scripts -->
    <script>
        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick) {
                dropdown.classList.add('hidden');
            }
        });

        // Revenue Trend Chart
        const trendData = <?= json_encode($trendsData) ?>;
        const trendLabels = trendData.map(item => new Date(item.trend_date).toLocaleDateString());
        const trendRevenue = trendData.map(item => parseFloat(item.daily_revenue));

        const trendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Daily Revenue',
                    data: trendRevenue,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
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
                                return 'Revenue: ₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Top Performers Chart
        const topData = <?= json_encode($topPerformers) ?>;
        const topLabels = topData.slice(0, 8).map(item => item.income_line);
        const topRevenue = topData.slice(0, 8).map(item => parseFloat(item.total_revenue));

        const topCtx = document.getElementById('topPerformersChart').getContext('2d');
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: 'Total Revenue',
                    data: topRevenue,
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(132, 204, 22, 0.8)',
                        'rgba(245, 101, 101, 0.8)'
                    ],
                    borderColor: [
                        'rgba(34, 197, 94, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(168, 85, 247, 1)',
                        'rgba(251, 146, 60, 1)',
                        'rgba(236, 72, 153, 1)',
                        'rgba(14, 165, 233, 1)',
                        'rgba(132, 204, 22, 1)',
                        'rgba(245, 101, 101, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Show/hide custom date inputs based on period selection
        document.querySelector('select[name="period"]').addEventListener('change', function() {
            const customInputs = document.querySelectorAll('input[name="start_date"], input[name="end_date"]');
            const isCustom = this.value === 'custom';
            
            customInputs.forEach(input => {
                input.style.display = isCustom ? 'block' : 'none';
                input.required = isCustom;
            });
        });
    </script>
</body>
</html>
