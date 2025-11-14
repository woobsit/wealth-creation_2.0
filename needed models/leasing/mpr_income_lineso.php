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
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

$analyzer = new OfficerPerformanceAnalyzer();

// Get current date info
$current_date = date('Y-m-d');
$selected_month = isset($_GET['smonth']) ? $_GET['smonth'] : date('n');
$selected_year = isset($_GET['syear']) ? $_GET['syear'] : date('Y');
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$per_page = 15;
$search_term = isset($_GET['search']) ? $_GET['search']  : '';
$selected_month_name = date('F', mktime(0, 0, 0, $selected_month, 1));

// Get unique income lines (no duplicates)
$db->query("
    SELECT DISTINCT a.acct_id, a.acct_desc as income_line, a.acct_table_name 
    FROM accounts a 
    WHERE a.active = 'Yes' 
    AND a.acct_desc IS NOT NULL 
    AND a.income_line = 'Yes'
    " . ($search_term ? "AND a.acct_desc LIKE :search_term" : "") . "
    ORDER BY a.acct_desc ASC
");

if ($search_term) {
    $db->bind(':search_term', '%' . $search_term . '%');
}

$income_lines = $db->resultSet();

// Pagination for income lines
$total_lines = count($income_lines);
$total_pages = ceil($total_lines / $per_page);
$offset = ($page - 1) * $per_page;

$sundays = $analyzer->getSundayPositions($selected_month, $selected_year);

// Calculate totals for each day across ALL income lines (not just paginated ones)
$daily_totals = array_fill(1, 31, 0);
$grand_total = 0;

// Calculate totals for all lines (not just paginated ones) to get accurate grand totals
foreach ($income_lines as $line) {
    $daily_collections = $analyzer->getDailyCollectionsForIncomeLine($line['acct_id'], $selected_month, $selected_year);
    $line_total = array_sum($daily_collections);
    $grand_total += $line_total;
    
    // Add to daily totals
    for ($day = 1; $day <= 31; $day++) {
        $daily_totals[$day] += $daily_collections[$day];
    }
}

// Get paginated lines with their data
$paginated_lines = array_slice($income_lines, $offset, $per_page);
foreach ($paginated_lines as &$line) {
    $daily_collections = $analyzer->getDailyCollectionsForIncomeLine($line['acct_id'], $selected_month, $selected_year);
    $line['daily_collections'] = $daily_collections;
    $line['line_total'] = array_sum($daily_collections);
}

// Get performance statistics
$active_lines_count = count(array_filter($income_lines, function($line) use ($analyzer, $selected_month, $selected_year) {
    $daily_collections = $analyzer->getDailyCollectionsForIncomeLine($line['acct_id'], $selected_month, $selected_year);
    return array_sum($daily_collections) > 0;
}));

$best_performing_line = null;
$best_amount = 0;
foreach ($income_lines as $line) {
    $daily_collections = $analyzer->getDailyCollectionsForIncomeLine($line['acct_id'], $selected_month, $selected_year);
    $total = array_sum($daily_collections);
    if ($total > $best_amount) {
        $best_amount = $total;
        $best_performing_line = $line;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Income Lines Performance</title>
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
                    <h1 class="text-xl font-bold text-gray-900"><?php echo APP_NAME; ?> - Income Lines MPR</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="mpr_income_lines_officers.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-users mr-1"></i>
                        Officer Analysis
                    </a>
                    <span class="text-sm text-gray-700">Welcome, <?php echo $staff['full_name']; ?></span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                        <?php echo $staff['department']; ?>
                    </span>
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
                        <h2 class="text-2xl font-bold text-gray-900">Monthly Performance Report</h2>
                        <p class="text-gray-600"><?php echo $selected_month_name . ' ' . $selected_year; ?> Collection Summary</p>
                    </div>
                    
                    <!-- Period Selection and Search Form -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4">
                            <!-- Search Input -->
                            <div class="flex-1">
                                <input type="text" name="search" placeholder="Search income lines..." 
                                       value="<?php echo htmlspecialchars($search_term); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <select name="smonth" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Month</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            
                            <select name="syear" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Year</option>
                                <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-search mr-2"></i>
                                Search & Filter
                            </button>
                            
                            <?php if ($search_term): ?>
                            <a href="?smonth=<?php echo $selected_month; ?>&syear=<?php echo $selected_year; ?>" 
                               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <i class="fas fa-times mr-2"></i>
                                Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Search Results Info -->
                <?php if ($search_term): ?>
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-search mr-2"></i>
                        Search results for "<strong><?php echo htmlspecialchars($search_term); ?></strong>" - 
                        Found <?php echo $total_lines; ?> income line<?php echo $total_lines !== 1 ? 's' : ''; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Collections</p>
                        <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($grand_total); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-list text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Income Lines</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $active_lines_count; ?></p>
                        <p class="text-xs text-gray-500">of <?php echo $total_lines; ?> total</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Best Performing Line</p>
                        <p class="text-lg font-bold text-gray-900">
                            <?php 
                            echo $best_performing_line ? substr($best_performing_line['income_line'], 0, 15) . '...' : 'N/A';
                            ?>
                        </p>
                        <p class="text-sm text-gray-500">₦<?php echo $best_performing_line ? number_format($best_amount) : '0'; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-calendar-day text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Daily Average</p>
                        <p class="text-2xl font-bold text-gray-900">
                            ₦<?php echo number_format($grand_total / date('t', mktime(0, 0, 0, $selected_month, 1, $selected_year))); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Income Lines Performance Overview</h3>
            <div class="relative h-48">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Daily Collection Summary Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Daily Collection Summary</h3>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_lines); ?> of <?php echo $total_lines; ?> income lines
                            <?php if ($search_term): ?>
                                (filtered by "<?php echo htmlspecialchars($search_term); ?>")
                            <?php endif; ?>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="exportToExcel()" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                <i class="fas fa-file-excel mr-1"></i>
                                Excel
                            </button>
                            <button onclick="printReport()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                <i class="fas fa-print mr-1"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table id="mprTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">
                                Income Line
                            </th>
                            <?php for ($day = 1; $day <= 31; $day++): ?>
                                <?php 
                                $is_sunday = in_array($day, $sundays);
                                $header_class = $is_sunday ? 'bg-red-100 text-red-800' : 'text-gray-500';
                                ?>
                                <th class="px-2 py-3 text-right text-xs font-medium <?php echo $header_class; ?> uppercase tracking-wider min-w-16">
                                    <?php if ($is_sunday): ?>
                                        <div class="text-red-600 font-bold">Sun</div>
                                    <?php endif; ?>
                                    <div><?php echo sprintf('%02d', $day); ?></div>
                                </th>
                            <?php endfor; ?>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 sticky right-0">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($paginated_lines)): ?>
                        <tr>
                            <td colspan="33" class="px-6 py-8 text-center text-gray-500">
                                <?php if ($search_term): ?>
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium">No income lines found</p>
                                        <p class="text-sm">No income lines match your search criteria "<?php echo htmlspecialchars($search_term); ?>"</p>
                                        <a href="?smonth=<?php echo $selected_month; ?>&syear=<?php echo $selected_year; ?>" 
                                           class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                            Clear Search
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium">No income lines available</p>
                                        <p class="text-sm">No active income lines found for the selected period</p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($paginated_lines as $line): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                        <a href="mpr_income_line.php?acct_id=<?php echo $line['acct_id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                                           class="text-blue-600 hover:text-blue-800 hover:underline">
                                            <?php echo ucwords(strtolower($line['income_line'])); ?>
                                        </a>
                                    </div>
                                </td>
                                <?php for ($day = 1; $day <= 31; $day++): ?>
                                    <?php 
                                    $amount = $line['daily_collections'][$day];
                                    $is_sunday = in_array($day, $sundays);
                                    $cell_class = $is_sunday ? 'bg-red-50 text-red-800' : 'text-gray-900';
                                    ?>
                                    <td class="px-2 py-4 whitespace-nowrap text-sm <?php echo $cell_class; ?> text-right">
                                        <?php if ($amount > 0): ?>
                                            <a href="ledger.php?acct_id=<?php echo $line['acct_id']; ?>&d1=<?php echo sprintf('%02d/%02d/%04d', $day, $selected_month, $selected_year); ?>&d2=<?php echo sprintf('%02d/%02d/%04d', $day, $selected_month, $selected_year); ?>" 
                                               class="hover:underline text-blue-600 hover:text-blue-800">
                                                <?php echo number_format($amount); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">0</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right bg-gray-50 sticky right-0">
                                    ₦<?php echo number_format($line['line_total']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-900 sticky left-0 bg-gray-100">
                                TOTAL (All <?php echo $total_lines; ?> Lines)
                            </th>
                            <?php for ($day = 1; $day <= 31; $day++): ?>
                                <?php 
                                $is_sunday = in_array($day, $sundays);
                                $cell_class = $is_sunday ? 'bg-red-200 text-red-900' : 'text-gray-900';
                                ?>
                                <th class="px-2 py-3 text-right text-sm font-bold <?php echo $cell_class; ?>">
                                    <?php echo number_format($daily_totals[$day]); ?>
                                </th>
                            <?php endfor; ?>
                            <th class="px-6 py-3 text-right text-sm font-bold text-gray-900 bg-gray-200 sticky right-0">
                                ₦<?php echo number_format($grand_total); ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_lines); ?> of <?php echo $total_lines; ?> results
                        <?php if ($search_term): ?>
                            for "<?php echo htmlspecialchars($search_term); ?>"
                        <?php endif; ?>
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

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="mpr_income_lines_officers.php?smonth=<?php echo $selected_month; ?>&syear=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-users mr-2"></i>
                    Officer Analysis
                </a>
                
                <a href="mpr_trends.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>
                    Trend Analysis
                </a>
                
                <a href="mpr_forecasting.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-crystal-ball mr-2"></i>
                    Forecasting
                </a>
                
                <a href="mpr_recommendations.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fas fa-lightbulb mr-2"></i>
                    Recommendations
                </a>
            </div>
        </div>
    </div>

    <script>
        // Performance Overview Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        
        // Prepare data for chart (top 10 income lines for better visibility)
        const chartData = <?php 
        $chart_lines = array_slice($income_lines, 0, 10);
        $chart_data = [];
        foreach ($chart_lines as $line) {
            $daily_collections = $analyzer->getDailyCollectionsForIncomeLine($line['acct_id'], $selected_month, $selected_year);
            $chart_data[] = [
                'income_line' => $line['income_line'],
                'total_amount' => array_sum($daily_collections)
            ];
        }
        echo json_encode($chart_data);
        ?>;
        
        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: chartData.map(item => item.income_line.length > 20 ? 
                    item.income_line.substring(0, 20) + '...' : item.income_line),
                datasets: [{
                    label: 'Total Collections (₦)',
                    data: chartData.map(item => item.total_amount),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
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
                                return 'Amount: ₦' + context.parsed.x.toLocaleString();
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Export functions
        function exportToExcel() {
            // Simple CSV export
            let csv = 'Income Line,';
            for (let day = 1; day <= 31; day++) {
                csv += String(day).padStart(2, '0') + ',';
            }
            csv += 'Total\n';
            
            // Add data rows
            const table = document.getElementById('mprTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) { // Skip empty state row
                    cells.forEach((cell, index) => {
                        if (index === 0) {
                            // Income line name - extract text content
                            const linkElement = cell.querySelector('a');
                            const text = linkElement ? linkElement.textContent.trim() : cell.textContent.trim();
                            csv += '"' + text + '",';
                        } else {
                            // Amount values - clean and format
                            const text = cell.textContent.trim();
                            const cleanValue = text.replace(/[₦,]/g, '').replace(/\s+/g, '');
                            csv += (cleanValue === '0' || cleanValue === '' ? '0' : cleanValue) + ',';
                        }
                    });
                    csv += '\n';
                }
            });
            
            // Add totals row
            const totalRow = table.querySelector('tfoot tr');
            if (totalRow) {
                const totalCells = totalRow.querySelectorAll('th');
                totalCells.forEach((cell, index) => {
                    if (index === 0) {
                        csv += '"TOTAL",';
                    } else {
                        const text = cell.textContent.trim();
                        const cleanValue = text.replace(/[₦,]/g, '').replace(/\s+/g, '');
                        csv += (cleanValue === '0' || cleanValue === '' ? '0' : cleanValue) + ',';
                    }
                });
                csv += '\n';
            }
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `income_lines_${<?php echo $selected_month; ?>}_${<?php echo $selected_year; ?>}<?php echo $search_term ? '_filtered' : ''; ?>.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function printReport() {
            window.print();
        }

        // Auto-submit search form on Enter key
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Highlight search terms in results
        <?php if ($search_term): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const searchTerm = '<?php echo addslashes($search_term); ?>';
            const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            
            document.querySelectorAll('td a').forEach(function(link) {
                if (link.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                    link.innerHTML = link.textContent.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>