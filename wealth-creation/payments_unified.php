<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/Transaction.php';
require __DIR__.'/../app/models/TransactionManager.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/OfficerPerformanceAnalyzer.php';
require __DIR__.'/../app/models/OfficerTargetManager.php';
require __DIR__.'/../app/models/PaymentProcessor.php';
require __DIR__.'/../app/models/Remittance.php';
require __DIR__.'/../app/models/FileCacheWealthCreation.php';
require __DIR__.'/../app/models/FileCacheAccount.php';

// Check if user is already logged in
requireLogin();
$user_id = $_SESSION['user_id'];
date_default_timezone_set('Africa/Lagos');
$db = $databaseObj;
$paymentProcessor = new PaymentProcessor($databaseObj);
$transactionManager = new Transaction($databaseObj);
$user = new User($databaseObj);
$staff = $user->getUserStaffDetail($user_id);

$current_date = date('Y-m-d');
$errors = [];
$success_message = '';

// $role = $user->getUserAdminRole($user_id);
// if ($role['acct_post_record'] != "Yes") {
//     die('You do not have permissions to post records! Contact your HOD for authorization.');
// }

$posting_officer_id = $_SESSION['user_id'];
$posting_officer_name = $_SESSION['first_name']." ".$_SESSION['last_name'];
$posting_officer_dept = $_SESSION['department'];

$db->query("SELECT * FROM accounts WHERE income_line = 'Yes' AND active = 'Yes' ORDER BY acct_desc ASC");
$income_lines = $db->resultSet();

$db->query("SELECT * FROM accounts WHERE active = 'Yes' ORDER BY acct_desc ASC");
$all_accounts = $db->resultSet();

// Get staff lists for dropdowns
$wc_staff = $paymentProcessor->getStaffList('Wealth Creation');
$other_staff = $paymentProcessor->getOtherStaffList();

$current_remittance_balance = [];
$selected_income_line = isset($_GET['income_line']) ? $_GET['income_line'] : '';

// $credit_legs = $processor->getIncomeLineAccounts();

if ($posting_officer_dept == "Wealth Creation") {
    $current_remittance_balance = $transactionManager->getRemittanceBalance(
        $posting_officer_id,
        $current_date
    );
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_post_transaction'])) {
    try {
        $posting_data = [
            'date_of_payment'       => isset($_POST['date_of_payment']) ? $_POST['date_of_payment'] : '',
            'receipt_no'            => isset($_POST['receipt_no']) ? $_POST['receipt_no'] : '',
            'amount_paid'           => isset($_POST['amount_paid']) ? $_POST['amount_paid'] : 0,
            'remitting_staff'       => isset($_POST['remitting_staff']) ? $_POST['remitting_staff'] : '',
            'transaction_desc'      => isset($_POST['transaction_descr']) ? $_POST['transaction_descr'] : '',
            'debit_account'         => isset($_POST['debit_account']) ? $_POST['debit_account'] : '',
            'credit_account'        => isset($_POST['credit_account']) ? $_POST['credit_account'] : $_POST['credit_account_wc'],
            'income_line'           => isset($_POST['income_line']) ? $_POST['income_line'] : '',
            'income_line_type'      => isset($_POST['income_line_type']) ? $_POST['income_line_type'] : '',
            'posting_officer_id'    => $posting_officer_id,
            'posting_officer_name'  => $posting_officer_name,
            'posting_officer_dept'  => $posting_officer_dept,
            'remit_id'              => isset($_POST['remit_id']) ? $_POST['remit_id'] : '',
            'amt_remitted'          => isset($_POST['amt_remitted']) ? $_POST['amt_remitted'] : 0,
            'current_date'          => $current_date,
            'ticket_category'       => isset($_POST['ticket_category']) ? $_POST['ticket_category'] : '',
            'no_of_tickets'         => isset($_POST['no_of_tickets']) ? $_POST['no_of_tickets'] : '',
            'category'              => isset($_POST['category']) ? $_POST['category'] : '',
            'plate_no'              => isset($_POST['plate_no']) ? $_POST['plate_no'] : '',
            'no_of_days'            => isset($_POST['no_of_days']) ? $_POST['no_of_days'] : '',
            'quantity'              => isset($_POST['quantity']) ? $_POST['quantity'] : '',
            'no_of_nights'          => isset($_POST['no_of_nights']) ? $_POST['no_of_nights'] : '',
            'type'                  => isset($_POST['type']) ? $_POST['type'] : '',
            'board_name'            => isset($_POST['board_name']) ? $_POST['board_name'] : ''
        ];
        // print_r($posting_data);
        // exit;
        $validation = $paymentProcessor->validatePosting($posting_data);

        if (!$validation['valid']) {
            $errors = $validation['errors'];
        } else {
            $db->beginTransaction();

            $result = $paymentProcessor->processIncomeLine($posting_data);

            if ($result['success']) {
                $db->endTransaction();
                $success_message = 'Payment successfully posted for approval!';
                header("refresh:2; url=payments_unified.php?income_line=" . urlencode($posting_data['income_line_type']));
            } else {
                $db->cancelTransaction();
                $errors[] = $result['message'];
            }
        }
    } catch (Exception $e) {
        if ($db) {
            $db->cancelTransaction();
        }
        $errors[] = 'System error: ' . $e->getMessage();
    }
}

$db->query("SELECT * FROM scroll_boards ORDER BY board_location ASC");
$scroll_boards = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Payment Posting - All Income Lines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
</head>
<body class="bg-gray-100 font-sans">
        <!-- Navigation -->
        <?php include('include/header.php'); ?>
        <!-- <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"> -->
        <?php if ($posting_officer_dept == "Wealth Creation"): ?>
            <div class="relative z-40 mt-4 flex justify-center">
                <div class="w-fit max-w-xl px-6 py-3 rounded-xl border 
                    <?php echo $current_remittance_balance['unposted'] > 0 
                        ? 'bg-red-50 border-red-300 text-red-800' 
                        : 'bg-emerald-50 border-emerald-300 text-emerald-800'; ?> 
                    shadow-sm font-semibold text-center tracking-wide transition-all duration-300">
                    
                    <div class="flex items-center justify-center space-x-2">
                        <?php if ($current_remittance_balance['unposted'] > 0): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>⚠ UNBALANCED — POST TO CLEAR</span>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>100% BALANCED</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-1 text-sm font-medium opacity-90">
                        TILL BALANCE: &#8358;<?php echo number_format($current_remittance_balance['unposted'], 2); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <div class="max-w-7xl mx-auto px-4 py-6">

        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-1">New Payment Posting - All Income Lines</h2>
            <p class="text-gray-600">Officer: <strong><?php echo htmlspecialchars($posting_officer_name); ?></strong> |
                Department: <strong><?php echo htmlspecialchars($posting_officer_dept); ?></strong></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-400 text-red-800 px-4 py-3 rounded-lg mb-4">
                <h4 class="font-bold mb-2">Error(s):</h4>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-4">
                <h4 class="font-bold mb-2">Success!</h4>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Split Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Side - Income Lines -->
            <aside class="lg:col-span-1 bg-white rounded-xl shadow-md p-5 border border-gray-100 items-start">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fa-solid fa-wallet text-blue-600 mr-2"></i> Income Lines
                </h3>
                <div id="income-line-cards" class="flex flex-col gap-3">
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="car_park" onclick="selectIncomeLine('car_park')">
                        <div>
                            <h5 class="text-sm font-bold">Car Park</h5>
                            <p class="text-xs opacity-90">Parking Tickets</p>
                        </div>
                        <i class="fa fa-car text-lg opacity-90"></i>
                    </div>

                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="loading" onclick="selectIncomeLine('loading')">
                        <div>
                            <h5 class="text-sm font-bold">Loading & Offloading</h5>
                            <p class="text-xs opacity-90">Offloading of Trucks</p>
                        </div>
                        <i class="fa fa-truck text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="daily_trade" onclick="selectIncomeLine('daily_trade')">
                        <div>
                            <h5 class="text-sm font-bold">Daily Trade</h5>
                            <p class="text-xs opacity-90">Everyday Trade</p>
                        </div>
                        <i class="fa fa-store text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="hawkers" onclick="selectIncomeLine('hawkers')">
                        <div>
                            <h5 class="text-sm font-bold">Hawkers</h5>
                            <p class="text-xs">Hawker Selling Permits</p>
                        </div>
                        <i class="fa fa-person-walking text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="wheelbarrow" onclick="selectIncomeLine('wheelbarrow')">
                        <div>
                            <h5 class="text-sm font-bold">Wheelbarrow</h5>
                            <p class="text-xs">Wheelbarrow pushers</p>
                        </div>
                        <i class="fa fa-wheelchair text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="abattoir" onclick="selectIncomeLine('abattoir')">
                        <div>
                            <h5 class="text-sm font-bold">Abattoir</h5>
                            <p class="text-xs">Slaughter Charges</p>
                        </div>
                        <i class="fa fa-drumstick-bite text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="overnight_parking" onclick="selectIncomeLine('overnight_parking')">
                        <div>
                            <h5 class="text-sm font-bold">Overnight Parking</h5>
                            <p class="text-xs">Long-term Parking</p>
                        </div>
                        <i class="fa fa-parking text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="scroll_board" onclick="selectIncomeLine('scroll_board')">
                        <div>
                            <h5 class="text-sm font-bold">Scroll Board</h5>
                            <p class="text-xs">Advertising</p>
                        </div>
                        <i class="fa fa-scroll text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg" data-income-line="general" onclick="selectIncomeLine('general')">
                        <div>
                            <h5 class="text-sm font-bold mb-1">General/Other</h5>
                            <p class="text-xs">Miscellaneous</p>
                        </div>
                        <i class="fa fa-sack-dollar text-lg opacity-90"></i>
                    </div>
                </div>
            </aside>

            <!-- Right Side - Forms -->
            <section class="lg:col-span-2 bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <form method="POST" action="" id="payment_form">
                    <input type="hidden" name="posting_officer_name" value="<?php echo  $posting_officer_name; ?>">
                    <input type="hidden" name="income_line_type" id="income_line_type" value="">
                    <input type="hidden" name="posting_officer_dept" value="<?php echo $posting_officer_dept; ?>">
                    <input type="hidden" name="posting_officer_id" value="<?php echo $posting_officer_id; ?>">
                    <?php if ($posting_officer_dept == "Wealth Creation"): ?>
                        <input type="hidden" name="remit_id" value="<?php echo $current_remittance_balance['remit_id']; ?>">
                        <input type="hidden" name="amt_remitted" value="<?php echo $current_remittance_balance['unposted']; ?>">
                    <?php endif; ?>

                    <!-- Common Fields Section -->
                    <div id="common_fields" class="common-inputs form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                    <h3 class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                        💳 Payment Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Date of Payment <span class="text-red-600">*</span>
                            </label>
                            <input type="date" name="date_of_payment" value="<?php echo $current_date; ?>" required class="common-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Receipt No <span class="text-red-600">*</span>
                            </label>
                            <input type="text" name="receipt_no" placeholder="7-digit receipt number" pattern="^\d{7}$" maxlength="7" required class="common-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>

                    <!-- Conditional: Remittance -->
                    <?php if ($posting_officer_dept == 'Wealth Creation' && $current_remittance_balance['unposted'] > 0): ?>
                        <div class="mt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Remittance</label>
                        <select 
                            name="remit_id" 
                            required
                            class="common-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        >
                            <option value="">Select...</option>
                            <option value="<?php echo $current_remittance_balance['remit_id']; ?>">
                            <?php echo $current_remittance_balance['date'] . ': Remittance - ₦' . number_format($current_remittance_balance['unposted']); ?>
                            </option>
                        </select>
                        </div>
                    <?php endif; ?>

                    <!-- Row 2: Remitting Staff -->
                    <div class="mt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Remitter's Name <span class="text-red-600">*</span>
                        </label>
                        <select name="remitting_staff" required class="common-inputs w-full px-3 py-2 border border-gray-300 bg-gray-50 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
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

                    <!-- Row 3: Debit & Credit Accounts -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <?php if ($_SESSION['department'] == "Accounts") : ?>
                        <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Debit Account <span class="text-red-600">*</span>
                        </label>
                        <select  name="debit_account"  required class="common-inputs w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">-- Select Debit Account --</option>
                            <option value="10103">Account Till</option> 
                            <option value="10150">Wealth Creation Funds Account</option>
                        </select>
                        </div>
                        

                        <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Credit Account (Income Line) <span class="text-red-600">*</span>
                        </label>
                        <select 
                            name="credit_account" 
                            id="credit_account" 
                            required 
                            class="common-inputs w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        >
                            <option value="">-- Select Income Line --</option>
                            <?php foreach ($income_lines as $account): ?>
                            <option 
                                value="<?php echo $account['acct_id']; ?>"
                                data-desc="<?php echo htmlspecialchars($account['acct_desc']); ?>"
                            >
                                <?php echo htmlspecialchars($account['acct_desc']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                        <?php endif; ?>

                        <?php if ($_SESSION['department'] == "Wealth Creation" || $staff["level"] == "ce") : ?>
                        <div>
                        <input type="hidden" class="common-inputs" name="debit_account" value="till" maxlength="50">
                        </div>
                        
                        <div>
                        <input type="hidden" class="common-inputs" name="credit_account_wc" id="credit_account_wc" value="" maxlength="50">
                        </div>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" class="common-inputs" name="income_line" id="income_line" value="">
                    </div>


                    <!-- Car Park Form -->
                    <div id="form_car_park" class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                    <h3 class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                        🚗 Car Park Details
                    </h3>

                    <!-- Category -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Category <span class="text-red-600">*</span>
                        </label>
                        <select 
                        name="category" 
                        id="cp_category" 
                        data-required="true"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <option value="">Select category</option>
                        <option value="Car Park 1 (Alpha 1)">Car Park 1 (Alpha 1)</option>
                        <option value="Car Park 2 (Alpha 2)">Car Park 2 (Alpha 2)</option>
                        </select>
                    </div>

                    <!-- Ticket Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Ticket Category <span class="text-red-600">*</span>
                        </label>
                        <select 
                            name="ticket_category" 
                            id="cp_ticket" 
                            onchange="calculateAmount()" 
                            data-required="true"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select ticket</option>
                            <option value="500">&#8358;500</option>
                            <option value="700">&#8358;700</option>
                        </select>
                        </div>

                        <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            No. of Tickets <span class="text-red-600">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="no_of_tickets" 
                            id="cp_tickets" 
                            min="1" 
                            onchange="calculateAmount()" 
                            data-required="true"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="mt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Amount <span class="text-red-600">*</span>
                        </label>
                        <input 
                        type="number" 
                        name="amount_paid" 
                        id="cp_amount" 
                        step="0.01" 
                        readonly 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <!-- Transaction Description -->
                    <div class="mt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Transaction Description
                        </label>
                        <textarea 
                        name="transaction_descr" 
                        id="cp_desc" 
                        rows="2" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >Car Park Collection</textarea>
                    </div>
                    </div>

                    <!-- Loading Form -->
                    <div id="form_loading" class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                    <h3 class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                        🚚 Loading & Offloading Details
                    </h3>

                    <!-- Category -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Category <span class="text-red-600">*</span>
                        </label>
                        <select 
                        name="category" 
                        id="ld_category" 
                        onchange="calculateAmount()" 
                        data-required="true"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <option value="">Select category</option>
                        <option value="Goods (Offloading) - N7000" data-amount="7000">Goods (Offloading) - ₦7,000</option>
                        <option value="Goods (Offloading) - N15000" data-amount="15000">Goods (Offloading) - ₦15,000</option>
                        <option value="Goods (Offloading) - N20000" data-amount="20000">Goods (Offloading) - ₦20,000</option>
                        <option value="Goods (Offloading) - N30000" data-amount="30000">Goods (Offloading) - ₦30,000</option>
                        <option value="Goods (Loading) - N20000" data-amount="20000">Goods (Loading) - ₦20,000</option>
                        <option value="40 feet container - (Offloading) N30000" data-amount="30000">40ft Container (Offloading) - ₦30,000</option>
                        <option value="40 feet container - (Apple Offloading - Sunday) - N60000" data-amount="60000">40ft Container (Apple Offloading - Sunday) - ₦60,000</option>
                        </select>
                    </div>

                    <!-- No. of Days and Plate No -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            No. of Days <span class="text-red-600">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="no_of_days" 
                            id="ld_days" 
                            min="1" 
                            value="1" 
                            onchange="calculateAmount()" 
                            data-required="true"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        </div>

                        <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Plate No <span class="text-red-600">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="plate_no" 
                            id="ld_plate" 
                            maxlength="8" 
                            data-required="true"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="mt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Amount <span class="text-red-600">*</span>
                        </label>
                        <input 
                        type="number" 
                        name="amount_paid" 
                        id="ld_amount" 
                        step="0.01" 
                        readonly 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <!-- Transaction Description -->
                    <div class="mt-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                        Transaction Description
                        </label>
                        <textarea 
                        name="transaction_descr" 
                        id="ld_desc" 
                        rows="2" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >Loading & Offloading Charges</textarea>
                    </div>
                    </div>

                    <!-- Daily Trade Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_daily_trade">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Daily Trade Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Ticket Category <span class="text-red-600">*</span></label>
                                <select name="ticket_category" id="dt_ticket" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="300">&#8358;300</option>
                                    <option value="500">&#8358;500</option>
                                    <option value="700">&#8358;700</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">No of Tickets <span class="text-red-600">*</span></label>
                                <input type="number" name="no_of_tickets" id="dt_tickets" min="1" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="dt_amount" step="0.01" readonly class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="dt_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Daily Trade Permit</textarea>
                        </div>
                    </div>

                    <!-- Hawkers Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_hawkers">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Hawkers Permit Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">No of Tickets <span class="text-red-600">*</span></label>
                            <input type="number" name="no_of_tickets" id="hw_tickets" min="1" value="1" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="hw_amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="hw_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Hawkers Permit</textarea>
                        </div>
                    </div>

                    <!-- Wheelbarrow Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_wheelbarrow">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Wheelbarrow Permit Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">No of Tickets <span class="text-red-600">*</span></label>
                            <input type="number" name="no_of_tickets" id="wb_tickets" min="1" value="1" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="wb_amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="wb_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Wheelbarrow Permit</textarea>
                        </div>
                    </div>

                    <!-- Abattoir Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_abattoir">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Abattoir Charges</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Category <span class="text-red-600">*</span></label>
                                <select name="category" id="ab_category" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="">Select category</option>
                                    <option value="Cows Killed">Cows Killed</option>
                                    <option value="Cows Takeaway">Cows Takeaway</option>
                                    <option value="Goats Killed">Goats Killed</option>
                                    <option value="Goats Takeaway">Goats Takeaway</option>
                                    <option value="Pots of Pomo">Pots of Pomo</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Quantity <span class="text-red-600">*</span></label>
                                <input type="number" name="quantity" id="ab_quantity" min="1" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="ab_amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="ab_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Abattoir Charges</textarea>
                        </div>
                    </div>

                    <!-- Overnight Parking Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_overnight_parking">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Overnight Parking Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Type <span class="text-red-600">*</span></label>
                            <select name="type" id="op_type" onchange="showOvernightCategory()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select type</option>
                                <option value="Vehicle">Vehicle</option>
                                <option value="Forklift Operator">Forklift Operator</option>
                                <option value="Artisan">Artisan</option>
                            </select>
                        </div>
                        <div class="mb-4 hidden" id="op_vehicle_div">
                            <label class="block mb-2 font-bold text-gray-800">Vehicle Category <span class="text-red-600">*</span></label>
                            <select name="category" id="op_vehicle_cat" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select category</option>
                                <option value="Overnight Parking - 40 feet - N5000" data-amount="5000">40 feet - N5000</option>
                                <option value="Overnight Parking - OK Trucks - N2000" data-amount="2000">OK Trucks - N2000</option>
                                <option value="Overnight Parking - LT Buses - N1500" data-amount="1500">LT Buses - N1500</option>
                                <option value="Overnight Parking - Sienna - N1000" data-amount="1000">Sienna - N1000</option>
                                <option value="Overnight Parking - Cars - N1000" data-amount="1000">Cars - N1000</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">No of Nights <span class="text-red-600">*</span></label>
                            <input type="number" name="no_of_nights" id="op_nights" min="1" value="1" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="op_amount" step="0.01" readonly class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="op_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Overnight Parking</textarea>
                        </div>
                    </div>

                    <!-- Scroll Board Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_scroll_board">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Scroll Board Rental</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Scroll Board Location <span class="text-red-600">*</span></label>
                            <select name="board_name" id="sb_board" onchange="loadScrollBoardInfo()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select location</option>
                                <?php foreach ($scroll_boards as $board): ?>
                                    <option value="<?php echo htmlspecialchars($board['board_name']); ?>"
                                            data-allocated="<?php echo htmlspecialchars(isset($board['allocated_to']) ? $board['allocated_to'] : ''); ?>"
                                            data-monthly="<?php echo isset($board['expected_rent_monthly']) ? $board['expected_rent_monthly'] : 0; ?>"
                                            data-yearly="<?php echo isset($board['expected_rent_yearly']) ? $board['expected_rent_yearly'] : 0; ?>"
                                        <?php echo htmlspecialchars($board['board_location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Allocated To</label>
                                <input type="text" id="sb_allocated" readonly class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Expected Monthly Rent</label>
                                <input type="text" id="sb_monthly" readonly class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="sb_amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="sb_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Scroll Board Rental</textarea>
                        </div>
                    </div>

                    <!-- General/Other Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_general">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">General/Other Income</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="gen_amount" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description <span class="text-red-600">*</span></label>
                            <textarea name="transaction_descr" id="gen_desc" rows="3" placeholder="Describe the transaction in detail" data-required="true" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"></textarea>
                        </div>
                    </div>

                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md text-center" id="submit_section">
                        <button type="submit" name="btn_post_transaction" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-10 text-base rounded transition-colors duration-200">
                            POST TRANSACTION
                        </button>
                    </div>
                </form>
            </section>

        </div>   
    </div>

    <script>
        let currentIncomeLine = '';

        // function selectIncomeLine(incomeLine) {
        //     currentIncomeLine = incomeLine;
        //     document.getElementById('income_line_type').value = incomeLine;
        //     document.getElementById('credit_account_wc').value = incomeLine;

        //     document.querySelectorAll('.income-line-card').forEach(card => {
        //         card.classList.remove('bg-gradient-to-br', 'from-pink-400', 'to-red-500', 'border-4', 'border-white');
        //         card.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-purple-600');
        //     });

        //     const selectedCard = document.querySelector(`[data-income-line="${incomeLine}"]`);
        //     selectedCard.classList.remove('from-blue-500', 'to-purple-600');
        //     selectedCard.classList.add('from-pink-400', 'to-red-500', 'border-4', 'border-white');

        //     document.querySelectorAll('.form-section').forEach(section => {
        //         section.classList.add('hidden');
        //     });

        //     document.getElementById('common_fields').classList.remove('hidden');
        //     document.getElementById(`form_${incomeLine}`).classList.remove('hidden');
        //     document.getElementById('submit_section').classList.remove('hidden');
        // }
        // function selectIncomeLine(incomeLine) {
        //     currentIncomeLine = incomeLine;
        //     document.getElementById('income_line_type').value = incomeLine;
        //     document.getElementById('credit_account_wc').value = incomeLine;

        //     // Reset all cards' appearance
        //     document.querySelectorAll('.income-line-card').forEach(card => {
        //         card.classList.remove('bg-gradient-to-br', 'from-pink-400', 'to-red-500', 'border-4', 'border-white');
        //         card.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-purple-600');
        //     });

        //     // Highlight selected card
        //     const selectedCard = document.querySelector(`[data-income-line="${incomeLine}"]`);
        //     selectedCard.classList.remove('from-blue-500', 'to-purple-600');
        //     selectedCard.classList.add('from-pink-400', 'to-red-500', 'border-4', 'border-white');

        //     // Hide all form sections and disable their inputs
        //     document.querySelectorAll('.form-section').forEach(section => {
        //         section.classList.add('hidden');
        //         section.querySelectorAll('input, select, textarea').forEach(el => {
        //             el.disabled = true;
        //         });
        //     });

        //     // Show selected form and enable its inputs
        //     const activeSection = document.getElementById(`form_${incomeLine}`);
        //     if (activeSection) {
        //         activeSection.classList.remove('hidden');
        //         activeSection.querySelectorAll('input, select, textarea').forEach(el => {
        //             el.disabled = false;
        //         });
        //     }

        //     // Always show the submit section
        //     document.getElementById('common_fields').classList.remove('hidden');
        //     document.getElementById('submit_section').classList.remove('hidden');
        // }
        function selectIncomeLine(incomeLine) {
    currentIncomeLine = incomeLine;
    document.getElementById('income_line_type').value = incomeLine;
    document.getElementById('credit_account_wc').value = incomeLine;
    document.getElementById('income_line').value = incomeLine;

    // Reset all cards' appearance
    document.querySelectorAll('.income-line-card').forEach(card => {
        card.classList.remove('bg-gradient-to-br', 'from-pink-400', 'to-red-500', 'border-4', 'border-white');
        card.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-purple-600');
    });

    // Highlight selected card
    const selectedCard = document.querySelector(`[data-income-line="${incomeLine}"]`);
        if (selectedCard) {
            selectedCard.classList.remove('from-blue-500', 'to-purple-600');
            selectedCard.classList.add('from-pink-400', 'to-red-500', 'border-4', 'border-white');
        }

        // Hide all form sections and disable their inputs
        document.querySelectorAll('.form-section').forEach(section => {
            section.classList.add('hidden');
            section.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = true;
                el.removeAttribute('required'); // remove required from hidden elements
            });
        });

        // Show selected form and enable its inputs
        const activeSection = document.getElementById(`form_${incomeLine}`);
        if (activeSection) {
            activeSection.classList.remove('hidden');
            activeSection.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = false;
                // restore required if marked as data-required="true"
                if (el.getAttribute('data-required') === 'true') {
                    el.setAttribute('required', 'required');
                }
            });
        }

        // Always show the common and submit sections
        const commonFields = document.getElementById('common_fields');
        const submitSection = document.getElementById('submit_section');
        if (commonFields) {
            commonFields.classList.remove('hidden');
            commonFields.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = false;
            });
        }
        if (submitSection) {
            submitSection.classList.remove('hidden');
        }
    }


        document.getElementById('credit_account').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const incomeLineDesc = selectedOption.getAttribute('data-desc');
            document.getElementById('income_line').value = incomeLineDesc || '';
        });

        function calculateAmount() {
            const incomeLine = currentIncomeLine;

            if (incomeLine === 'car_park') {
                const ticketPrice = parseFloat(document.getElementById('cp_ticket').value) || 0;
                const tickets = parseInt(document.getElementById('cp_tickets').value) || 0;
                document.getElementById('cp_amount').value = (ticketPrice * tickets).toFixed(2);
            }

            if (incomeLine === 'loading') {
                const category = document.getElementById('ld_category');
                const amount = parseFloat(category.options[category.selectedIndex]?.getAttribute('data-amount')) || 0;
                const days = parseInt(document.getElementById('ld_days').value) || 1;
                document.getElementById('ld_amount').value = (amount * days).toFixed(2);
            }

            if (incomeLine === 'daily_trade') {
                const ticketPrice = parseFloat(document.getElementById('dt_ticket').value) || 0;
                const tickets = parseInt(document.getElementById('dt_tickets').value) || 0;
                document.getElementById('dt_amount').value = (ticketPrice * tickets).toFixed(2);
            }

            if (incomeLine === 'overnight_parking') {
                const category = document.getElementById('op_vehicle_cat');
                const amount = parseFloat(category.options[category.selectedIndex]?.getAttribute('data-amount')) || 0;
                const nights = parseInt(document.getElementById('op_nights').value) || 1;
                document.getElementById('op_amount').value = (amount * nights).toFixed(2);
            }
        }

        function showOvernightCategory() {
            const type = document.getElementById('op_type').value;
            const vehicleDiv = document.getElementById('op_vehicle_div');
            if (type === 'Vehicle') {
                vehicleDiv.classList.remove('hidden');
            } else {
                vehicleDiv.classList.add('hidden');
            }
        }

        function loadScrollBoardInfo() {
            const select = document.getElementById('sb_board');
            const option = select.options[select.selectedIndex];

            document.getElementById('sb_allocated').value = option.getAttribute('data-allocated') || '';
            document.getElementById('sb_monthly').value = '₦' + parseFloat(option.getAttribute('data-monthly') || 0).toLocaleString();
        }

        <?php if ($selected_income_line): ?>
            selectIncomeLine('<?php echo $selected_income_line; ?>');
        <?php endif; ?>

        <?php if ($posting_officer_dept == "Wealth Creation"): ?>
        document.querySelectorAll('input[name="amount_paid"]').forEach(input => {
            input.addEventListener('change', function() {
                const amountPaid = parseFloat(this.value) || 0;
                const remittanceBalance = <?php echo $current_remittance_balance['unposted']; ?>;

                if (amountPaid > remittanceBalance) {
                    alert('WARNING: Amount (₦' + amountPaid.toFixed(2) + ') exceeds remittance balance (₦' +
                          remittanceBalance.toFixed(2) + ')');
                    this.value = remittanceBalance.toFixed(2);
                }
            });
        });
        <?php endif; ?>

        // **JAVASCRIPT FOR DROPDOWN FUNCTIONALITY**
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            if (dropdown) {
                
                // 1. Close all currently visible dropdowns
                // We now select ALL elements with the new 'dropdown-menu' class.
                document.querySelectorAll('.dropdown-menu').forEach(otherDropdown => {
                    if (otherDropdown.id !== id) {
                        otherDropdown.classList.add('hidden');
                    }
                });

                // 2. Toggle the clicked one's visibility
                dropdown.classList.toggle('hidden');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            // We update the selector here too, to check if the click is outside 
            // any button or any element with the 'dropdown-menu' class.
            if (!event.target.closest('.relative button') && !event.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });

    // **JAVASCRIPT FOR DATATABLES**
    $(document).ready(function() {
        $('#activityTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "pageLength": 5, // Show 5 entries by default
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "Filter records:",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No matching records found"
            },
            // Add Bootstrap styling classes (often needed for DataTables styling integration)
            "dom": 'lfrtip' 
        });
        
        // This is a common fix to make DataTables pagination buttons and search/length dropdowns visible 
        // when using a theme like Tailwind.
        $('.dataTables_wrapper').addClass('mt-4');
        $('.dataTables_length').addClass('mb-2');
        $('.dataTables_filter').addClass('mb-2');
    });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <footer class="bg-fuchsia-700 border-t border-fuchsia-800 mt-12 shadow-inner">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-fuchsia-200">
                &copy; <?php echo date('Y'); ?> WEALTH CREATION ERP. All rights reserved. Developed by Woobs Resources Ltd.
            </div>
        </div>
    </footer>
</body>
</html>
