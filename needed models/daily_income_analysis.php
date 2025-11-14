
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'helpers/session_helper.php';
require_once 'includes/daily_income_config.php';
require_once 'includes/daily_income_header.php';
require_once 'includes/daily_income_period_selector.php';
require_once 'includes/daily_income_summary.php';
require_once 'includes/daily_income_table.php';
require_once 'includes/daily_income_styles.php';
require_once 'includes/daily_income_scripts.php';

// Check if user is logged in and has proper role
requireLogin();
requireAnyRole(['admin', 'manager', 'accounts', 'financial_controller']);

// Use session data directly
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];
$userRole = $_SESSION['user_role'];

// Initialize database
$database = new Database();

// Get filter parameters
$selected_month = isset($_GET['month']) ? sanitize($_GET['month']) : date('F');
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : date('Y');

// Get daily income data
$data = getDailyIncomeData($selected_month, $selected_year, $database);
$daily_analysis = $data['daily_analysis'];
$daily_totals = $data['daily_totals'];
$grand_total = $data['grand_total'];
$days_in_month = $data['days_in_month'];
$sundays = $data['sundays'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Income Analysis - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <?php renderStyles(); ?>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php renderDailyIncomeHeader($userName, $department); ?>

    <!-- Main Content -->
    <main class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <?php renderPeriodSelector($selected_month, $selected_year); ?>
        <?php renderSummaryStats($selected_month, $selected_year, $daily_analysis, $grand_total, $days_in_month); ?>
        <?php renderAnalysisTable($daily_analysis, $daily_totals, $grand_total, $days_in_month, $sundays); ?>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    
    <?php renderScripts($selected_month, $selected_year); ?>
</body>
</html>
