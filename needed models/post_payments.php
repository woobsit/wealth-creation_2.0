<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/PaymentProcessor.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);

$processor = new PaymentProcessor();
$current_date = date('Y-m-d');
$income_line = isset($_GET['income_line']) ? $_GET['income_line'] : '';

// Get remittance balance for Wealth Creation staff
$remittance_data = [];
if ($staff['department'] === 'Wealth Creation') {
    $remittance_data = $processor->getRemittanceBalance($staff['user_id'], $current_date);
}
$credit_legs = $processor->getIncomeLineAccounts();
// print_r($credit_legs);
// exit;
// Handle form submission
$message = '';
$error = '';
//Handle Car Pack posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_post_car_park'])) {
    // Validate receipt number
    $receipt_check = $processor->checkReceiptExists($_POST['receipt_no']);
    if ($receipt_check) {
        $error = "Transaction failed! Receipt No: {$_POST['receipt_no']} has already been used by {$receipt_check['posting_officer_name']} on {$receipt_check['date_of_payment']}!";
    } else {
        // Process payment data
        $date_parts = explode('/', $_POST['date_of_payment']);
        $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        
        // Get staff info
        list($remitting_id, $remitting_check) = explode('-', $_POST['remitting_staff']);
        $staff_info = $processor->getStaffInfo($remitting_id, $remitting_check);
        
        // Get account info
        $debit_account_info = $processor->getAccountInfo('till', true);
        $credit_account_info = $processor->getAccountInfo('carpark', true);
        
        $payment_data = [
            'date_of_payment' => $formatted_date,
            'ticket_category' => $_POST['ticket_category'],
            'transaction_desc' => $_POST['category'] . ' - ' . $staff_info['full_name'],
            'receipt_no' => $_POST['receipt_no'],
            'amount_paid' => preg_replace('/[,]/', '', $_POST['amount_paid']),
            'remitting_id' => $remitting_id,
            'remitting_staff' => $staff_info['full_name'],
            'posting_officer_id' => $staff['user_id'],
            'posting_officer_name' => $staff['full_name'],
            'leasing_post_status' => $staff['department'] === 'Accounts' ? '' : 'Pending',
            'approval_status' => $staff['department'] === 'Accounts' ? 'Pending' : '',
            'verification_status' => $staff['department'] === 'Accounts' ? 'Pending' : '',
            'debit_account' => $debit_account_info['acct_id'],
            'credit_account' => $credit_account_info['acct_id'],
            'db_debit_table' => $debit_account_info['acct_table_name'],
            'db_credit_table' => $credit_account_info['acct_table_name'],
            'no_of_tickets' => $_POST['no_of_tickets'],
            'remit_id' => isset($_POST['remit_id']) ? $_POST['remit_id'] : '',
            'income_line' => $income_line
        ];
        
        $result = $processor->processCarParkPayment($payment_data);
        
        if ($result['success']) {
            $message = $result['message'];
            // Redirect to prevent resubmission
            header('Location: index.php?income_line=' . $income_line . '&success=1');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Get staff lists for dropdowns
$wc_staff = $processor->getStaffList('Wealth Creation');
$other_staff = $processor->getOtherStaffList();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wealth Creation - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dept-badge {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        /* .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        } */
        .stat-card-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card-4 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#059669',
                        accent: '#dc2626'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
     <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">WC ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">
                         <?php 
                            if ($staff['department'] === 'Accounts') { echo 'Account Dept. Dashboard'. '<p class="text-sm text-gray-500"> View, Post, Approve & Manage lines of Income</p>';}
                            if ($staff['department'] === 'Wealth Creation') { echo 'Wealth Creation Dashboard'. '<p class="text-sm text-gray-500"> View, Post, & Manage lines of Income</p>'; }
                            if ($staff['department'] === 'Audit/Inspections') { echo 'Audit/Inspections Dashboard'. '<p class="text-sm text-gray-500"> View, Approve, & Manage lines of Income</p>'; } 
                         ?>
                        </h1>
                        
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">Welcome, <?= htmlspecialchars($staff['full_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($staff['department']) ?></div>
                    </div>
                    <div class="relative">
                        <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                <?= strtoupper($currentUser['full_name'][0]) ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <?php if(hasDepartment('Wealth Creation')): ?>
                            <a href="post_collection.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-receipt mr-2"></i> Post Collections
                            </a>
                            <?php endif; ?>
                            <?php if(hasDepartment('Accounts')): ?>
                            <a href="remittance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-money-bill-wave mr-2"></i> Remittances
                            </a>
                            <?php endif; ?>
                            <?php if(hasDepartment('Audit/Inspections')): ?>
                            <a href="verify_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-clipboard-check mr-2"></i> Audit Dashboard
                            </a>
                            <?php endif; ?>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"> <!-- max-w-6xl mx-auto p-6 bg-gray-50 -->
        <div class="flex items-center justify-between bg-white p-4 rounded shadow mb-6">
            <div class="text-lg font-semibold text-gray-700">
                <p><?php include ('countdown_script.php'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="text-sm text-gray-500">
        <?php 
        $current_time = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $wc_begin_time = $today . ' 00:00:00';
        $wc_end_time   = $today . ' 20:30:00';

        if ($current_time >= $wc_begin_time && $current_time >= $wc_end_time) {
            echo '<a href="log_unposted_trans_others.php" class="inline-block bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-blue-700 transition">Log Unposted Collection</a>';
        }
        ?>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-wrap -mx-4">
            <!-- Sidebar - Income Lines -->
            <div class="w-full lg:w-1/4 px-4 mb-6 lg:mb-0">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <!-- Sidebar - Income Lines -->
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Lines of Income</h2>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Select a Line</h3>
                    <div class="flex flex-col space-y-3">
                        <?php
                        $income_lines = [
                            'general' => 'General',
                            'car_park' => 'Car Park Tickets',
                            'car_loading' => 'Car Loading Tickets',
                            'hawkers' => 'Hawkers Tickets',
                            'wheelbarrow' => 'WheelBarrow Tickets',
                            'daily_trade' => 'Daily Trade Tickets',
                            'abattoir' => 'Abattoir',
                            'toilet_collection' => 'Toilet Collection',
                            'loading' => 'Loading & Offloading',
                            'overnight_parking' => 'Overnight Parking',
                            'scroll_board' => 'Scroll Board',
                            'other_pos' => 'Other POS Tickets',
                            'car_sticker' => 'Car Sticker',
                            'daily_trade_arrears' => 'Daily Trade Arrears'
                        ];

                        foreach ($income_lines as $key => $label) {
                            $active = $income_line === $key ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-gray-700 hover:bg-gray-50';
                            echo "
                            <a href='?income_line={$key}' type='button'  class='block px-3 py-2 rounded-md text-sm font-medium border flex items-center border border-indigo-200 rounded-lg px-4 py-2 hover:shadow-sm transition w-full text-left {$active}'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='w-5 h-5 text-indigo-600 mr-3' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 6h16M4 12h16M4 18h16' />
                            </svg>
                            <span class='text-sm font-medium text-gray-800'>{$label}</span>
                            </a>";
                        }

                        ?>
                    </div>

                </div>
            </div>

            <!-- Main Content -->
            <div class="w-full lg:w-3/4 px-4">
                <!-- Status Cards -->
                <?php if ($staff['department'] === 'Wealth Creation'): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="stats-card rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500"> Amount Remitted </p>
                                <p class="text-2xl font-bold text-gray-900"><?= formatCurrency($remittance_data['amount_remitted']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-card rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                                </div>
                            </div>
                            <a href="">
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Amount Posted</p>
                                <p class="text-2xl font-bold text-gray-900"><?= formatCurrency($remittance_data['amount_posted']) ?> </p>
                            </div>
                            </a>
                        </div>
                    </div>

                    <div class="stats-card rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-calendar-alt text-orange-600 text-xl"></i>
                                </div>
                            </div>
                            <a href="view_transactions.php">
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Unposted Balance</p>
                                <p class="text-2xl font-bold text-red-900"> <?= formatCurrency($remittance_data['unposted']) ?> </p>
                            </div>
                            </a>
                        </div>
                    </div>
                </div
                <?php endif; ?>
               

                <!-- Payment Form -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            <?php echo ucwords(str_replace('_', ' ', $income_line)); ?> Payment
                        </h2>
                        <?php if (isset($_GET['success'])): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                            <span class="block sm:inline">Payment successfully posted for approval!</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- General Form -->
                    <?php if ($income_line === 'general'): ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="posting_officer_id" value="<?php echo $staff['user_id']; ?>">
                        <input type="hidden" name="posting_officer_name" value="<?php echo $staff['full_name']; ?>">
                        <input type="hidden" name="income_line" value="<?php echo $income_line; ?>">
                        <input type="hidden" name="posting_officer_dept" value="<?php echo $currentUserStaffInfo['department']; ?>">
                        
                        <!-- Date This will always be constant on all forms -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Payment</label>
                                <input type="text" name="date_of_payment" 
                                       value="<?php echo date('d/m/Y'); ?>" 
                                       readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <?php if ($staff['department'] === 'Wealth Creation' && $remittance_data['unposted'] > 0): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Remittance</label>
                                <select name="remit_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select...</option>
                                    <option value="<?php echo $remittance_data['remit_id']; ?>">
                                        <?php echo $remittance_data['date'] . ': Remittance - ₦' . number_format($remittance_data['unposted']); ?>
                                    </option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Payment Information, Description Number of Tickets and Receipt Number -->
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Description:</label>
                                <input type="text" name="transaction_descr" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?php isset($_POST['transaction_descr']) ? $_POST['transaction_descr'] : '';  ?>" pattern=".{10,}" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Receipt No</label>
                                <input type="text" name="receipt_no" required maxlength="7" pattern="^\d{7}$"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount and Staff -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Amount Remitted (₦)</label>
                                <input type="text" name="amount_paid" id="amount_paid" readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Remitter's Name</label>
                                <select name="remitting_staff" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select...</option>
                                    <?php foreach ($wc_staff as $staff_member): ?>
                                        <option value="<?php echo $staff_member['user_id']; ?>-wc">
                                            <?php echo $staff_member['full_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php foreach ($other_staff as $staff_member): ?>
                                        <option value="<?php echo $staff_member['id']; ?>-so">
                                            <?php echo $staff_member['full_name'] . ' - ' . $staff_member['department']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php if ($staff['department'] === 'Wealth Creation' || $staff['level'] === 'ce' ) : ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <input type="hidden" name="debit_account" value="<?php echo "till"; ?>" maxlength="50">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Income Line</label>
                                <select name="credit_account" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select an Income line...</option>
                                    <?php foreach ($credit_legs as $credit_leg): ?>
                                        <option value="<?php echo $credit_leg['acct_id']; ?>">
                                            <?php echo ucwords(strtolower($credit_leg['acct_desc'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>


                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <button type="reset" 
                                    class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Clear
                            </button>
                            
                            <?php if ($staff['department'] === 'Wealth Creation' && $remittance_data['unposted'] <= 0): ?>
                                <p class="text-red-600 font-medium">You do not have any unposted remittances for today.</p>
                            <?php else: ?>
                                <button type="submit" name="btn_post_car_park"
                                        class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Post Car Park Payment
                                </button>
                            <?php endif; ?>
                        </div>
                    </form> 
                    <?php endif; ?>

                    <!-- Car Park Form -->
                    <?php if ($income_line === 'car_park'): ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="posting_officer_id" value="<?php echo $staff['user_id']; ?>">
                        <input type="hidden" name="posting_officer_name" value="<?php echo $staff['full_name']; ?>">
                        <input type="hidden" name="income_line" value="<?php echo $income_line; ?>">
                        <input type="hidden" name="posting_officer_dept" value="<?php echo $currentUserStaffInfo['department']; ?>">

                        <!-- Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Payment</label>
                                <input type="text" name="date_of_payment" 
                                       value="<?php echo date('d/m/Y'); ?>" 
                                       readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <?php if ($staff['department'] === 'Wealth Creation' && $remittance_data['unposted'] > 0): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Remittance</label>
                                <select name="remit_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select...</option>
                                    <option value="<?php echo $remittance_data['remit_id']; ?>">
                                        <?php echo $remittance_data['date'] . ': Remittance - ₦' . number_format($remittance_data['unposted']); ?>
                                    </option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Category and Ticket Category -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select name="category" id="category" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a category</option>
                                    <option value="Car Park 1 (Alpha 1)">Car Park 1 (Alpha 1)</option>
                                    <option value="Car Park 2 (Alpha 2)">Car Park 2 (Alpha 2)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Category (₦)</label>
                                <select name="ticket_category" id="ticket_category" required onchange="calculateAmount()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="500">500</option>
                                    <option value="700">700</option>
                                </select>
                            </div>
                        </div>

                        <!-- Number of Tickets and Receipt Number -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">No of Tickets</label>
                                <input type="number" name="no_of_tickets" id="no_of_tickets" required min="1" 
                                       onchange="calculateAmount()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Receipt No</label>
                                <input type="text" name="receipt_no" required maxlength="7" pattern="^\d{7}$"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount and Staff -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Amount Remitted (₦)</label>
                                <input type="text" name="amount_paid" id="amount_paid" readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Remitter's Name</label>
                                <select name="remitting_staff" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select...</option>
                                    <?php foreach ($wc_staff as $staff_member): ?>
                                        <option value="<?php echo $staff_member['user_id']; ?>-wc">
                                            <?php echo $staff_member['full_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php foreach ($other_staff as $staff_member): ?>
                                        <option value="<?php echo $staff_member['id']; ?>-so">
                                            <?php echo $staff_member['full_name'] . ' - ' . $staff_member['department']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <button type="reset" 
                                    class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Clear
                            </button>
                            
                            <?php if ($staff['department'] === 'Wealth Creation' && $remittance_data['unposted'] <= 0): ?>
                                <p class="text-red-600 font-medium">You do not have any unposted remittances for today.</p>
                            <?php else: ?>
                                <button type="submit" name="btn_post_car_park"
                                        class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Post Car Park Payment
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>





                    <!-- Other income line forms would go here -->
                    <?php if ($income_line !== 'car_park'): ?>
                    <div class="text-center py-12">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            <?php echo ucwords(str_replace('_', ' ', $income_line)); ?> Form
                        </h3>
                        <p class="text-gray-500">This form is under development. Please select Car Park for now.</p>
                    </div>
                    <?php endif; ?>



                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateAmount() {
            const ticketCategory = parseFloat(document.getElementById('ticket_category').value) || 0;
            const noOfTickets = parseFloat(document.getElementById('no_of_tickets').value) || 0;
            const totalAmount = ticketCategory * noOfTickets;
            document.getElementById('amount_paid').value = totalAmount.toLocaleString();
        }
    </script>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[1, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7] }
                ]
            });
        });

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

        // Refresh page
        function refreshPage() {
            window.location.reload();
        }

        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(alert => {
                if (alert.textContent.includes('successfully') || alert.textContent.includes('Error')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>