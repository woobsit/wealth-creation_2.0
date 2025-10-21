<?php
require_once 'config/config.php';
requireLogin();

// Check if user has access to reports (IT, Accounts, Audit departments)
$allowedDepartments = ['IT', 'Accounts', 'Audit'];
if (!in_array($_SESSION['department'], $allowedDepartments) && $_SESSION['level'] < 4) {
    redirect('index.php');
}

$requisition = new Requisition();
$reports = new Reports();

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';


// Get report data
$monthlyStats = $reports->getMonthlyStats($month, $year, $department);
$yearlyStats = $reports->getYearlyStats($year, $department);
$retirementStats = $reports->getRetirementStats($month, $year, $department);
$departmentBreakdown = $reports->getDepartmentBreakdown($month, $year);

$departments = ['Finance', 'Human Resources', 'IT', 'Operations', 'Marketing', 'Legal', 'Procurement', 'Administration', 'Accounts', 'Audit'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Reports & Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8 pt-24">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
                                <p class="text-gray-600 mt-1">Comprehensive analysis of requisition expenses and retirements</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="exportReport('pdf')" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                                </button>
                                <button onclick="exportReport('excel')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="px-6 py-4 bg-gray-50">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                <select name="month" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                                <select name="year" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select name="department" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept; ?>" <?php echo $department === $dept ? 'selected' : ''; ?>><?php echo $dept; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-filter mr-2"></i>Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Monthly Expenses</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo formatCurrency(isset($monthlyStats['total_amount']) ? $monthlyStats['total_amount'] : 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-chart-line text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Yearly Expenses</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo formatCurrency(isset($yearlyStats['total_amount']) ? $yearlyStats['total_amount']  : 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-undo text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Retired</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo formatCurrency(isset($retirementStats['total_retired']) ? $retirementStats['total_retired'] : 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i class="fas fa-arrow-left text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Amount Returned</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo formatCurrency(isset($retirementStats['total_returned']) ? $retirementStats['total_returned'] : 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Department Breakdown Chart -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Department Breakdown</h2>
                        </div>
                        <div class="p-6">
                            <canvas id="departmentChart" width="400" height="300"></canvas>
                        </div>
                    </div>

                    <!-- Monthly Trend Chart -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Monthly Trend (<?php echo $year; ?>)</h2>
                        </div>
                        <div class="p-6">
                            <canvas id="trendChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Detailed Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Monthly Breakdown -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Monthly Breakdown</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($departmentBreakdown as $dept): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($dept['department']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $dept['count']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatCurrency($dept['total_amount']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Retirement Summary -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Retirement Summary</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Total Requisitions with Retirement</span>
                                    <span class="text-lg font-bold text-gray-900"><?php echo isset($retirementStats['count_with_retirement']) ? $retirementStats['count_with_retirement'] : 0; ?></span>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Total Amount Retired</span>
                                    <span class="text-lg font-bold text-green-600"><?php echo formatCurrency(isset($retirementStats['total_retired']) ? $retirementStats['total_retired'] : 0); ?></span>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Total Amount Returned</span>
                                    <span class="text-lg font-bold text-blue-600"><?php echo formatCurrency(isset($retirementStats['total_returned']) ? $retirementStats['total_returned'] : 0); ?></span>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-purple-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Net Retirement</span>
                                    <span class="text-lg font-bold text-purple-600">
                                        <?php
                                            echo formatCurrency(
                                                (isset($retirementStats['total_retired']) ? $retirementStats['total_retired'] : 0)
                                                - (isset($retirementStats['total_returned']) ? $retirementStats['total_returned'] : 0)
                                            );
                                        ?>

                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Department Breakdown Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentData = <?php echo json_encode($departmentBreakdown); ?>;
        
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: departmentData.map(d => d.department),
                datasets: [{
                    data: departmentData.map(d => d.total_amount),
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                        '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const monthlyTrend = <?php echo json_encode($reports->getMonthlyTrend($year, $department)); ?>;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyTrend,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function exportReport(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            window.open('export-report.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>