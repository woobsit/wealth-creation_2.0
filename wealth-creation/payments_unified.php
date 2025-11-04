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

// Check if user is already logged in
requireLogin();
$user_id = $_SESSION['user_id'];
$db = $databaseObj;
$paymentProcessor = new PaymentProcessor($databaseObj);
$transactionManager = new Transaction($databaseObj);
$remittancemanager = new Remittance($databaseObj);

$officers = $remittancemanager->getWealthCreationOfficers();

$user = new User($databaseObj);
$staff = $user->getUserStaffDetail($user_id);

$current_date = date('Y-m-d');
$errors = [];
//$success_message = '';

// $role = $user->getUserAdminRole($user_id);
// if ($role['acct_post_record'] != "Yes") {
//     die('You do not have permissions to post records! Contact your HOD for authorization.');
// }

$posting_officer_name = $_SESSION['first_name']." ".$_SESSION['last_name'];
$posting_officer_dept = $_SESSION['department'];

$db->query("SELECT * FROM accounts WHERE income_line = 'Yes' AND active = 'Yes' ORDER BY acct_desc ASC");
$income_lines = $db->resultSet();

// $db->query("SELECT * FROM accounts WHERE active = 'Yes' AND ( acct_desc = 'Account Till' OR acct_desc = 'Wealth Creation Funds Account' ) ORDER BY acct_desc ASC");
// $all_accounts = $db->resultSet();

$db->query("SELECT shop_no, customer_name FROM customers WHERE (facility_type = 'Coldroom' OR facility_type = 'Container' OR facility_type = 'Kclamp') AND shop_no != '' ORDER BY shop_no ASC");
$kclamp = $db->resultSet();

$db->query("SELECT shop_no, customer_name FROM customers ORDER BY shop_no ASC");
$all_customers = $db->resultSet();

$db->query("SELECT sticker_no FROM car_sticker WHERE status = '' ORDER BY sticker_no ASC");
$all_stickers = $db->resultSet();


// Get staff lists for dropdowns
$wc_staff = $paymentProcessor->getStaffList('Wealth Creation');
$other_staff = $paymentProcessor->getOtherStaffList();

$current_remittance_balance = [];
$selected_income_line = isset($_GET['income_line']) ? $_GET['income_line'] : '';

//$credit_legs = $paymentProcessor->getIncomeLineAccounts();

if ($posting_officer_dept == "Wealth Creation") {
    $current_remittance_balance = $transactionManager->getRemittanceBalance(
        $user_id,
        $current_date
    );
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_post_transaction'])) {
    try {
        $posting_data = [
             'shop_no'            => isset($_POST['shop_no']) ? $_POST['shop_no'] : '',
            'date_of_payment'       => isset($_POST['date_of_payment']) ? $_POST['date_of_payment'] : '',
            'receipt_no'            => isset($_POST['receipt_no']) ? $_POST['receipt_no'] : '',
            'amount_paid'           => isset($_POST['amount_paid']) ? trim($_POST['amount_paid']) : 0,
            'remitting_staff'       => isset($_POST['remitting_staff']) ? $_POST['remitting_staff'] : '',
            'transaction_desc'      => isset($_POST['transaction_descr']) ? $_POST['transaction_descr'] : '',
            'debit_account'         => isset($_POST['debit_account']) ? $_POST['debit_account'] : '',
            'credit_account'        => isset($_POST['credit_account']) ? $_POST['credit_account'] : $_POST['credit_account_wc'],
            'income_line'           => isset($_POST['income_line']) ? $_POST['income_line'] : '',
            'income_line_type'      => isset($_POST['income_line_type']) ? $_POST['income_line_type'] : '',
            'posting_officer_id'    => $user_id,
            'posting_officer_name'  => $posting_officer_name,
            'posting_officer_dept'  => $posting_officer_dept,
            'remit_id'              => isset($_POST['remit_id']) ? $_POST['remit_id'] : '',
            'amt_remitted'          => isset($_POST['amt_remitted']) ? trim($_POST['amt_remitted']) : 0,
            'current_date'          => $current_date,
            'ticket_category'       => isset($_POST['ticket_category']) ? $_POST['ticket_category'] : '',
            'no_of_tickets'         => isset($_POST['no_of_tickets']) ? trim($_POST['no_of_tickets']) : '',
            'category'              => isset($_POST['category']) ? $_POST['category'] : '',
            'plate_no'              => isset($_POST['plate_no']) ? trim($_POST['plate_no']) : '',
            'no_of_days'            => isset($_POST['no_of_days']) ? trim($_POST['no_of_days']) : '',
            'quantity'              => isset($_POST['quantity']) ? trim($_POST['quantity']) : '',
            'no_of_nights'          => isset($_POST['no_of_nights']) ? trim($_POST['no_of_nights']) : '',
            'type'                  => isset($_POST['type']) ? $_POST['type'] : '',
            'board_name'            => isset($_POST['board_name']) ? $_POST['board_name'] : '',
             'car_sticker'            => isset($_POST['car_sticker']) ? $_POST['car_sticker'] : ''
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
               
                header("refresh:2; url=payments_unified.php?income_line=" . urlencode($posting_data['income_line_type'])."&success=".true);
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
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>⚠ UNBALANCED — POST TO CLEAR</span>
                <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
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
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8"> 
    <!-- max-w-7xl mx-auto px-4 py-6 -->
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-1">New Payment Posting - All Income Lines</h2>
            <p class="text-gray-600">Officer: <strong><?php echo htmlspecialchars($posting_officer_name); ?></strong> |
                Department: <strong><?php echo htmlspecialchars($posting_officer_dept); ?></strong></p>
        </div>
        
        <div class="flex items-center justify-between bg-white p-4 rounded shadow mb-6">
            <div class="text-lg font-semibold text-gray-700">
                <p><?php include ('countdown_script.php'); ?></p>
            </div>
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

        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-4">
            <h4 class="font-bold mb-2">Success!</h4>
            <p><?php echo 'Payment successfully posted for approval!'; ?></p>
        </div>
        <?php endif; ?>

        <!-- Split Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Left Side - Income Lines -->
            <aside class="lg:col-span-1 bg-white rounded-xl shadow-md p-5 border border-gray-100 items-start">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa-solid fa-wallet text-blue-600 mr-2"></i> Income Lines
                </h3>
                <div id="income-line-cards" class="flex flex-col gap-3">
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="general" onclick="selectIncomeLine('general')">
                        <div>
                            <h5 class="text-sm font-bold">General/Other</h5>
                            <p class="text-xs opacity-90">Miscellaneous</p>
                        </div>
                        <i class="fa fa-sack-dollar text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="car_park" onclick="selectIncomeLine('car_park')">
                        <div>
                            <h5 class="text-sm font-bold">Car Park</h5>
                            <p class="text-xs opacity-90">Parking Tickets</p>
                        </div>
                        <i class="fa fa-car text-lg opacity-90"></i>
                    </div>

                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="car_loading" onclick="selectIncomeLine('car_loading')">
                        <div>
                            <h5 class="text-sm font-bold">Car Loading</h5>
                            <p class="text-xs opacity-90">Car Loading Tickets</p>
                        </div>
                        <i class="fa fa-car text-lg opacity-90"></i>
                    </div>

                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="car_sticker" onclick="selectIncomeLine('car_sticker')">
                        <div>
                            <h5 class="text-sm font-bold">Car Sticker</h5>
                            <p class="text-xs opacity-90">Stickers for Cars</p>
                        </div>
                        <i class="fa fa-car text-lg opacity-90"></i>
                    </div>

                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="toilet_collection" onclick="selectIncomeLine('toilet_collection')">
                        <div>
                            <h5 class="text-sm font-bold">Toilet Collection</h5>
                            <p class="text-xs opacity-90">Toilet Usage</p>
                        </div>
                        <i class="fa fa-car text-lg opacity-90"></i>
                    </div>

                     <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="other_pos" onclick="selectIncomeLine('other_pos')">
                        <div>
                            <h5 class="text-sm font-bold">Other POS</h5>
                            <p class="text-xs opacity-90">POS Tickets</p>
                        </div>
                        <i class="fa fa-car text-lg opacity-90"></i>
                    </div>


                    
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="loading" onclick="selectIncomeLine('loading')">
                        <div>
                            <h5 class="text-sm font-bold">Loading & Offloading</h5>
                            <p class="text-xs opacity-90">Offloading of Trucks</p>
                        </div>
                        <i class="fa fa-truck text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="daily_trade" onclick="selectIncomeLine('daily_trade')">
                        <div>
                            <h5 class="text-sm font-bold">Daily Trade</h5>
                            <p class="text-xs opacity-90">Everyday Trade</p>
                        </div>
                        <i class="fa fa-store text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="daily_trade_arrears" onclick="selectIncomeLine('daily_trade_arrears')">
                        <div>
                            <h5 class="text-sm font-bold">Daily Trade Arrears</h5>
                            <p class="text-xs opacity-90">Everyday Trade Arrears</p>
                        </div>
                        <i class="fa fa-store text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="hawkers" onclick="selectIncomeLine('hawkers')">
                        <div>
                            <h5 class="text-sm font-bold">Hawkers</h5>
                            <p class="text-xs">Hawker Selling Permits</p>
                        </div>
                        <i class="fa fa-person-walking text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="wheelbarrow" onclick="selectIncomeLine('wheelbarrow')">
                        <div>
                            <h5 class="text-sm font-bold">Wheelbarrow</h5>
                            <p class="text-xs">Wheelbarrow pushers</p>
                        </div>
                        <i class="fa fa-wheelchair text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="abattoir" onclick="selectIncomeLine('abattoir')">
                        <div>
                            <h5 class="text-sm font-bold">Abattoir</h5>
                            <p class="text-xs">Slaughter Charges</p>
                        </div>
                        <i class="fa fa-drumstick-bite text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="overnight_parking" onclick="selectIncomeLine('overnight_parking')">
                        <div>
                            <h5 class="text-sm font-bold">Overnight Parking</h5>
                            <p class="text-xs">Long-term Parking</p>
                        </div>
                        <i class="fa fa-parking text-lg opacity-90"></i>
                    </div>
                    <div class="income-line-card flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-4 py-3 rounded-lg cursor-pointer transition hover:scale-[1.02] hover:shadow-lg"
                        data-income-line="scroll_board" onclick="selectIncomeLine('scroll_board')">
                        <div>
                            <h5 class="text-sm font-bold">Scroll Board</h5>
                            <p class="text-xs">Advertising</p>
                        </div>
                        <i class="fa fa-scroll text-lg opacity-90"></i>
                    </div>

                </div>
            </aside>

            <!-- Right Side - Forms -->
            <section class="lg:col-span-2 bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <form method="POST" action="" id="payment_form">
                    <input type="hidden" name="posting_officer_name" value="<?php echo  $posting_officer_name; ?>">
                    <input type="hidden" name="income_line_type" id="income_line_type" value="">
                    <input type="hidden" name="posting_officer_dept" value="<?php echo $posting_officer_dept; ?>">
                    <input type="hidden" name="posting_officer_id" value="<?php echo $user_id; ?>">
                    <?php if ($posting_officer_dept == "Wealth Creation"): ?>
                    <input type="hidden" name="remit_id" value="<?php echo $current_remittance_balance['remit_id']; ?>">
                    <input type="hidden" name="amt_remitted"
                        value="<?php echo $current_remittance_balance['unposted']; ?>">
                    <?php endif; ?>
                     <input type="hidden" class="common-inputs" name="income_line" id="income_line" value="">

                    <div id="default_info_section"
                        class="form-section p-8 bg-white rounded-xl shadow-lg border-l-4 border-blue-500 animate-fade-in">
                        <h2 class="text-3xl font-extrabold text-gray-800 mb-4">Welcome to the Income Lines Posting
                            Portal 💰
                        </h2>
                        <hr class="mb-4">
                        <p class="text-gray-600 mb-6">
                            To begin posting revenue, please **select an Income Line Card** from the list on the left
                            (e.g., 'General/Other', 'Car Park', etc.).
                        </p>
                        <ul class="space-y-3 text-gray-700 list-disc list-inside">
                            <li>Each card represents a distinct revenue source and will load a specific form for data
                                entry.</li>
                            <li>The **General/Other** card is suitable for miscellaneous or unlisted income types.</li>
                            <li>Your posting activities will be tracked under your current department.</li>
                        </ul>
                        <p class="mt-6 text-sm text-blue-600 font-medium">
                            Select a card now to reveal the required posting fields and the Submit button.
                        </p>
                    </div>


                    <!-- Common Fields Section -->
                    <div id="common_fields"
                        class="common-inputs form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            💳 Payment Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
    <label class="block mb-2 text-sm font-medium text-gray-700">
        Date of Payment <span class="text-red-600">*</span>
    </label>
    <input
        type="date"
        name="date_of_payment"
        value="<?php echo isset($_POST['date_of_payment']) ? $_POST['date_of_payment'] : $current_date; ?>"
        required
        class="common-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
    >
</div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Receipt No <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="receipt_no" 
                               placeholder="7-digit receipt number"
                                    pattern="^\d{7}$" maxlength="7" required
                                    class="common-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>

                        <!-- Conditional: Remittance -->
                        <?php if ($posting_officer_dept == 'Wealth Creation' && $current_remittance_balance['unposted'] > 0): ?>
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Remittance</label>
                            <select name="remit_id" required
                                class="common-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
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
                            <select name="remitting_staff" required class="common-inputs w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">

    <option value=""
        <?php
        // Check if the form was submitted and the selected value is NOT set or is empty.
        // This handles both the default selection and the case where the user didn't select one.
        if (!isset($_POST['remitting_staff']) || $_POST['remitting_staff'] == '') {
            echo 'selected';
        }
        ?>
    >Select...</option>

    <?php foreach ($wc_staff as $staff_member): ?>
        <?php
        $option_value = $staff_member['user_id'] . '-wc';
        $selected = (isset($_POST['remitting_staff']) && $_POST['remitting_staff'] == $option_value) ? 'selected' : '';
        ?>
        <option value="<?php echo $option_value; ?>" <?php echo $selected; ?>>
            <?php echo $staff_member['full_name']; ?>
        </option>
    <?php endforeach; ?>

    <?php foreach ($other_staff as $staff_member): ?>
        <?php
        $option_value = $staff_member['id'] . '-so';
        $selected = (isset($_POST['remitting_staff']) && $_POST['remitting_staff'] == $option_value) ? 'selected' : '';
        ?>
        <option value="<?php echo $option_value; ?>" <?php echo $selected; ?>>
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
                                <select name="debit_account" required class="common-inputs w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
    
    <option value="">
        -- Select Debit Account --
    </option>
    
    <option value="10103">
        Account Till
    </option> 
    
    <option value="10150">
        Wealth Creation Funds Account
    </option>
    
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
        <?php
        // Define the option's value
        $option_value = $account['acct_id'];
        
        ?>
        <option 
            value="<?php echo $option_value; ?>"
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
                                <input type="hidden" class="common-inputs" name="debit_account" value="till"
                                    maxlength="50">
                            </div>

                            <div>
                                <input type="hidden" class="common-inputs" name="credit_account_wc"
                                    id="credit_account_wc" value="" maxlength="50">
                            </div>
                            <?php endif; ?>
                        </div>

                       
                    </div>

                    <!-- General/Other Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_general">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">
                            General/Other Income</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="gen_amount" step="1.00" min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description <span
                                    class="text-red-600">*</span></label>
                            <textarea name="transaction_descr" id="gen_desc" rows="3"
                                placeholder="Describe the transaction in detail" data-required="true"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"></textarea>
                        </div>
                    </div>

                    <!-- Car Loading Form -->
                    <div id="form_car_loading"
                        class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            🚗 Car Loading Details
                        </h3>

                        <!-- Ticket Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    No. of Tickets <span class="text-red-600">*</span>
                                </label>
                                <input type="number" name="no_of_tickets" id="cl_ticket" min="1"
                                    onchange="calculateAmount()" data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="amount_paid" id="cl_amount" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Transaction Description -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Transaction Description
                            </label>
                            <textarea name="transaction_descr" id="cp_desc" rows="2" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">Car Loading Collection</textarea>
                        </div>
                    </div>


                     <!-- Car Sticker -->
                    <div id="form_car_sticker"
                        class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            🚗 Car Stickers Details
                        </h3>

                         <!-- Sticker No -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Sticker No <span class="text-red-600">*</span>
                            </label>
                            <select name="" id="car_sticker"  data-required="false"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select sticker</option>
                                <?php foreach ($all_stickers as $sticker): ?>
                                    <option value="<?php echo $sticker['sticker_no']; ?>"
                                        data-desc="<?php echo $sticker['sticker_no']; ?>">
                                       <?php echo $sticker['sticker_no'];; ?>
                                    </option>
                                    <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Shop No and Plate No -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Shop No: <span class="text-red-600">*</span>
                                </label>
                                 <div>
                                    
                                <select name="shop_no" 
                                    data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select...</option>
                                     <?php foreach ($kclamp as $kcustomer): ?>
                                    <option value="<?php echo "{$kcustomer['shop_no']} - {$kcustomer['customer_name']}"; ?>"
                                        data-desc="<?php echo "{$kcustomer['shop_no']} - {$kcustomer['customer_name']}"; ?>">
                                       <?php echo "{$kcustomer['shop_no']} - {$kcustomer['customer_name']}"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                     <?php foreach ($all_customers as $customer): ?>
                                    <option value="<?php echo "{$customer['shop_no']} - {$customer['customer_name']}"; ?>"
                                        data-desc="<?php echo "{$customer['shop_no']} - {$customer['customer_name']}"; ?>">
                                       <?php echo "{$customer['shop_no']} - {$customer['customer_name']}"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Plate No <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="plate_no" id="ld_plate" maxlength="8" data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <input type="number" 
                            value="60000" name="amount_paid" id="" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                     <!-- Toilet Collection -->
                    <div id="form_toilet_collection"
                        class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            🚚 Toilet Collection Details
                        </h3>

                        <!-- Toilet -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Toilet <span class="text-red-600">*</span>
                            </label>
                            <select name="" data-required="false"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">A Block Toilet</option>
                                <option value="">Buka Toilet</option>
                                <option value="">Center Toilet</option>
                                <option value="">Exit Toilet</option>
                                <option value="">Pedestrian Toilet</option>
                            </select>
                        </div>


                        <!-- Amount -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="amount_paid" id="ld_amount" step="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Transaction Description -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Transaction Description
                            </label>
                            <textarea name="transaction_descr" id="ld_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50" readonly>Toilet Collection Charges</textarea>
                        </div>
                    </div>

                     <!-- Other POS Tickets Form -->
                    <div id="form_other_pos"
                        class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            🚗 Other POS Tickets Details
                        </h3>

                        <!-- Ticket Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    No. of Tickets <span class="text-red-600">*</span>
                                </label>
                                <input type="number" name="no_of_tickets" id="op_ticket" min="1"
                                    onchange="calculateAmount()" data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="amount_paid" id="op_amount" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Transaction Description -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Transaction Description
                            </label>
                            <textarea name="transaction_descr" id="cp_desc" rows="2" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-100">Other POS Ticket Collection</textarea>
                        </div>
                    </div>

                    <!-- Car Park Form -->
                    <div id="form_car_park"
                        class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            🚗 Car Park Details
                        </h3>

                        <!-- Category -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Category <span class="text-red-600">*</span>
                            </label>
                            <select name="category" id="cp_category" data-required="true"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                                <select name="ticket_category" id="cp_ticket" onchange="calculateAmount()"
                                    data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select ticket</option>
                                    <option value="500">&#8358;500</option>
                                    <option value="700">&#8358;700</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    No. of Tickets <span class="text-red-600">*</span>
                                </label>
                                <input type="number" name="no_of_tickets" id="cp_tickets" min="1"
                                    onchange="calculateAmount()" data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="amount_paid" id="cp_amount" step="1" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Transaction Description -->
                        <!-- <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Transaction Description
                            </label>
                            <textarea name="transaction_descr" id="cp_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">Car Park Collection</textarea>
                        </div> -->
                    </div>

                    <!-- Loading Form -->
                    <div id="form_loading"
                        class="form-section hidden bg-white p-8 my-6 rounded-xl shadow-lg border border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-gray-800 border-b pb-3 border-blue-500 mb-6 flex items-center gap-2">
                            🚚 Loading & Offloading Details
                        </h3>

                        <!-- Category -->
                       <div>
    <label class="block mb-2 text-sm font-medium text-gray-700">
        Category <span class="text-red-600">*</span>
    </label>
    <select name="category" id="ld_category" onchange="calculateAmount()" data-required="true"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        
        <option value="">Select category</option>
        
        <option value="Goods (Offloading) - N7000" data-amount="7000">Goods (Offloading) - N7000</option>
        <option data-amount="15000" value="Goods (Offloading) - N15000">Goods (Offloading) - N15000</option>
        <option data-amount="20000" value="Goods (Offloading) - N20000">Goods (Offloading) - N20000</option>
        <option data-amount="30000" value="Goods (Offloading) - N30000">Goods (Offloading) - N30000</option>
        <option data-amount="20000" value="Goods (Loading) - N20000">Goods (Loading) - N20000</option>
        
        <option data-amount="2500" value="Fruits (Offloading) - N2500">Fruits (Offloading) - N2500</option>
        <option data-amount="3500" value="Fruits (Offloading) - N3500">Fruits (Offloading) - N3500</option>
        <option data-amount="7000" value="Fruits (Offloading) - N7000">Fruits (Offloading) - N7000</option>
        <option data-amount="15000" value="Fruits (Offloading) - N15000">Fruits (Offloading) - N15000</option>
        
        <option data-amount="3500" value="Apple Bus (Loading) - N3500">Apple Bus (Loading) - N3500</option>
        <option data-amount="7000" value="Cargo Truck (Loading) - N7000">Cargo Truck (Loading) - N7000</option>
        <option data-amount="15000" value="Cargo Truck 1 (Offloading) - N15000">Cargo Truck 1 (Offloading) - N15000</option>
        <option data-amount="20000" value="Cargo Truck 2 (Offloading) - N20000">Cargo Truck 2 (Offloading) - N20000</option>
        <option data-amount="20000" value="OK Truck (Offloading) - N20000">OK Truck (Offloading) - N20000</option>
        
        <option data-amount="15000" value="20 feet container - (Loading) - N15000">20 feet container - (Loading) - N15000</option>
        <option data-amount="15000" value="20 feet container - (Offloading) - N15000">20 feet container - (Offloading) - N15000</option>
        
        <option data-amount="30000" value="40 feet container - (Offloading) N30000">40 feet container - (Offloading) N30000</option>
        <option data-amount="30000" value="40 feet container - (Abassa Offloading - Weekend) - N30000">40 feet container - (Abassa Offloading - Weekend) - N30000</option>
        <option data-amount="60000" value="40 feet container - (Shoe Offloading - Weekend) - N60000">40 feet container - (Shoe Offloading - Weekend) - N60000</option>
        <option data-amount="30000" value="40 feet container - (Apple Offloading) - N30000">40 feet container - (Apple Offloading) - N30000</option>
        <option data-amount="60000" value="40 feet container - (Apple Offloading - Sunday) - N60000">40 feet container - (Apple Offloading - Sunday) - N60000</option>
        <option data-amount="30000" value="40 feet container - (Ok, Curtain Offloading) - N30000">40 feet container - (Ok, Curtain Offloading) - N30000</option>
        
        <option data-amount="4000" value="LT Buses (Offloading) - N4000">LT Buses (Offloading) - N4000</option>
        <option data-amount="7000" value="LT Buses (Offloading - Sunday) - N7000">LT Buses (Offloading - Sunday) - N7000</option>
        <option data-amount="4000" value="LT Buses (Loading) - N4000">LT Buses (Loading) - N4000</option>
        
        <option data-amount="3000" value="Mini LT Buses (Loading) - N3000">Mini LT Buses (Loading) - N3000</option>
        <option data-amount="3000" value="Mini LT Buses (Offloading) - N3000">Mini LT Buses (Offloading) - N3000</option>
        
        <option data-amount="1000" value="LT Buses Army Staff (Loading) - N1000">LT Buses Army Staff (Loading) - N1000</option>
        <option data-amount="2000" value="LT Buses Army Staff (Loading) - N2000">LT Buses Army Staff (Loading) - N2000</option>
        
        <option data-amount="5000" value="Mini Van (Loading) - N5000">Mini Van (Loading) - N5000</option>
        <option data-amount="5000" value="Mini Van (Offloading) - N5000">Mini Van (Offloading) - N5000</option>
        <option data-amount="6000" value="OK Mini Van (Loading) - N6000">OK Mini Van (Loading) - N6000</option>
        <option data-amount="6000" value="OK Mini Van (Offloading) - N6000">OK Mini Van (Offloading) - N6000</option>
        
        <option data-amount="2000" value="Sienna Buses (Loading) - N2000">Sienna Buses (Loading) - N2000</option>
        <option data-amount="30000" value="Oil Tanker (Offloading) - N30000">Oil Tanker (Offloading) - N30000</option>
        
    </select>
</div>
                        <!-- No. of Days and Plate No -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    No. of Days <span class="text-red-600">*</span>
                                </label>
                                <input type="number" name="no_of_days" id="ld_days" min="1" value="1"
                                    onchange="calculateAmount()" data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Plate No <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="plate_no" id="ld_plate" maxlength="8" data-required="true"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mt-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Amount <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="amount_paid" id="loading_amount" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                    </div>

                    <!-- Daily Trade Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_daily_trade">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Daily Trade
                            Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Ticket Category <span
                                        class="text-red-600">*</span></label>
                                <select name="ticket_category" id="dt_ticket" onchange="calculateAmount()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="300">&#8358;300</option>
                                    <option value="500">&#8358;500</option>
                                    <option value="700">&#8358;700</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">No of Tickets <span
                                        class="text-red-600">*</span></label>
                                <input type="number" name="no_of_tickets" id="dt_tickets" min="1"
                                    onchange="calculateAmount()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="dt_amount" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="dt_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Daily Trade Permit</textarea>
                        </div>
                    </div>

                     <!-- Daily Trade Arrears Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_daily_trade_arrears">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Daily Trade Arrears
                            Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Ticket Category <span
                                        class="text-red-600">*</span></label>
                                <select name="daily_trade_a_ticket" id="daily_trade_a_ticket_category" onchange="calculateAmount()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="300">&#8358;300</option>
                                    <option value="500">&#8358;500</option>
                                    <option value="700">&#8358;700</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">No of Tickets <span
                                        class="text-red-600">*</span></label>
                                <input type="number" name="daily_trade_a_no_of_tickets" id="daily_trade_a_no_tickets" min="1"
                                    onchange="calculateAmount()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="daily_trade_a_amount_paid" id="daily_trade_a_amount" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="daily_trade_a_transaction_descr" id="dt_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Daily Trade Arrears Permit</textarea>
                        </div>
                    </div>

                    <!-- Hawkers Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_hawkers">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Hawkers
                            Permit Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">No of Tickets <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="no_of_tickets" id="hw_tickets" min="1" value="1"
                                onchange="calculateAmount()"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="hw_amount" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="hw_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Hawkers Permit</textarea>
                        </div>
                    </div>

                    <!-- Wheelbarrow Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_wheelbarrow">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Wheelbarrow
                            Permit Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">No of Tickets <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="no_of_tickets" id="wb_tickets" min="1" value="1"
                                onchange="calculateAmount()"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="wb_amount" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="wb_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Wheelbarrow Permit</textarea>
                        </div>
                    </div>

                    <!-- Abattoir Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_abattoir">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Abattoir
                            Charges</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Category <span
                                        class="text-red-600">*</span></label>
                                <select name="category" id="ab_category"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="">Select category</option>
                                    <option value="Cows Killed">Cows Killed</option>
                                    <option value="Cows Takeaway">Cows Takeaway</option>
                                    <option value="Goats Killed">Goats Killed</option>
                                    <option value="Goats Takeaway">Goats Takeaway</option>
                                    <option value="Pots of Pomo">Pots of Pomo</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Quantity <span
                                        class="text-red-600">*</span></label>
                                <input type="number" name="quantity" id="ab_quantity" min="1"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="ab_amount" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="ab_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Abattoir Charges</textarea>
                        </div>
                    </div>

                    <!-- Overnight Parking Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_overnight_parking">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Overnight
                            Parking Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Type <span
                                    class="text-red-600">*</span></label>
                            <select name="type" id="op_type" onchange="showOvernightCategory()"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select type</option>
                                <option value="Vehicle">Vehicle</option>
                                <option value="Forklift Operator">Forklift Operator</option>
                                <option value="Artisan">Artisan</option>
                            </select>
                        </div>
                        <div class="mb-4 hidden" id="op_vehicle_div">
                            <label class="block mb-2 font-bold text-gray-800">Vehicle Category <span
                                    class="text-red-600">*</span></label>
                            <select name="category" id="op_vehicle_cat" onchange="calculateAmount()"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select category</option>
                                <option value="Overnight Parking - 40 feet - N5000" data-amount="5000">40 feet - N5000
                                </option>
                                <option value="Overnight Parking - OK Trucks - N2000" data-amount="2000">OK Trucks -
                                    N2000</option>
                                <option value="Overnight Parking - LT Buses - N1500" data-amount="1500">LT Buses - N1500
                                </option>
                                <option value="Overnight Parking - Sienna - N1000" data-amount="1000">Sienna - N1000
                                </option>
                                <option value="Overnight Parking - Cars - N1000" data-amount="1000">Cars - N1000
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">No of Nights <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="no_of_nights" id="op_nights" min="1" value="1"
                                onchange="calculateAmount()"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="op_amount" step="0.01" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="op_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Overnight Parking</textarea>
                        </div>
                    </div>

                    <!-- Scroll Board Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_scroll_board">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Scroll
                            Board Rental</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Scroll Board Location <span
                                    class="text-red-600">*</span></label>
                            <select name="board_name" id="sb_board" onchange="loadScrollBoardInfo()"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select location</option>
                                <?php foreach ($scroll_boards as $board): ?>
                                <option value="<?php echo htmlspecialchars($board['board_name']); ?>"
                                    data-allocated="<?php echo htmlspecialchars(isset($board['allocated_to']) ? $board['allocated_to'] : ''); ?>"
                                    data-monthly="<?php echo isset($board['expected_rent_monthly']) ? $board['expected_rent_monthly'] : 0; ?>"
                                    data-yearly="<?php echo isset($board['expected_rent_yearly']) ? $board['expected_rent_yearly'] : 0; ?>"
                                    <?php echo htmlspecialchars($board['board_location']); ?> </option>
                                    <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Allocated To</label>
                                <input type="text" id="sb_allocated" readonly
                                    class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Expected Monthly Rent</label>
                                <input type="text" id="sb_monthly" readonly
                                    class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span
                                    class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="sb_amount" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="sb_desc" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Scroll Board Rental</textarea>
                        </div>
                    </div>

                   
                     <?php if ($staff['department'] === 'Wealth Creation' && $current_remittance_balance['unposted'] <= 0): ?>
                        <p class="text-red-600 font-medium">You do not have any unposted remittances for today.</p>
                    <?php else: ?>
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md text-center"
                        id="submit_section">
                        <button type="submit" name="btn_post_transaction"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-10 text-base rounded transition-colors duration-200">
                            POST TRANSACTION
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </section>

        </div>
    </div>

    <script>
    let currentIncomeLine = '';

    function selectIncomeLine(incomeLine) {
        currentIncomeLine = incomeLine;

        // Define aliases just like you'd do with PHP conditions
        var aliasMap = {
            'car_park': 'car_park',
            'daily_trade': 'daily_trade',
            'daily_trade_arrears': 'daily_trade_arrears',
            'hawkers_ticket': 'hawkers_ticket',
            'wheel_barrow_ticket': 'wheel_barrow_ticket',
            'other_pos':'other_pos',
            'car_loading': 'car_loading',
            'loading': 'loading',
            'Cleaning Fee': 'Cleaning Fee',
            'Fruit Offloading': 'offloading_fruit',
            'Ok Loading - Offloading': 'ok_loading_offloading',
            'Parking Store': 'Parking Store',
            'Pallet Loading': 'pallet',
            'Offloading Truck': 'offloading_truck',
            'offloading': 'offloading',
            'overnight_parking': 'overnight_parking',
            'toilet_collection': 'toilet_collection',
            'Abattoir': 'abattoir',
            'Wealth Creation Funds Account': 'wc_funds_ac',
            'KClamp (New Space)': 'kclamp',
            'Car Park Ticket': 'carpark',
            'Application Form': 'application_form',
            'car_sticker': 'car_sticker',
            'Taxi Operators (Renewal)': 'taxi_operators',
            'Toilet Collection': 'toilet_collection',
            'Key Replacement': 'key_replacement',
            'Other Loading - Offloading': 'goods_loading_offloading',
            'Food Seller Permit': 'food_seller_permit',
            'Retailers Monthly Due': 'retailers_due',
            'WheelBarrow Ticket': 'wheelbarrow',
            'Work Permit': 'work_permit',
            'Trade Permit': 'trade_permit'
            // add more mappings as needed
        };

        // Get alias from the map (default to incomeLine if not found)
        var alias = aliasMap[incomeLine] || incomeLine;
        var aliasField = document.getElementById('credit_account_wc');
        if (aliasField) {
            aliasField.value = alias;
        }

        document.getElementById('income_line_type').value = incomeLine; //e.g general
        //document.getElementById('credit_account_wc').value = incomeLine;
        document.getElementById('income_line').value = incomeLine;  //specific like 

        // Reset all cards' appearance
        document.querySelectorAll('.income-line-card').forEach(card => {
            card.classList.remove('bg-gradient-to-br', 'from-pink-400', 'to-red-500', 'border-4',
                'border-white');
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

        if (incomeLine === 'car_loading') {
            const ticketPrice = parseFloat(document.getElementById('cl_ticket').value) || 0;
            document.getElementById('cl_amount').value = (1000 * ticketPrice ).toFixed(2);
        }

         if (incomeLine === 'other_pos') {
            const ticketPrice = parseFloat(document.getElementById('op_ticket').value) || 0;
            document.getElementById('op_amount').value = (300 * ticketPrice ).toFixed(2);
        }

      if (incomeLine === 'loading') {
    // 1. Get the select element
    const category = document.getElementById('ld_category');
    // 2. Safely retrieve the numerical value from the 'data-amount' attribute
    const selectedOption = category.options[category.selectedIndex];

    const amount = parseFloat(selectedOption?.getAttribute('data-amount')) || 0; // If data-amount is not found, amount will be 0

    // 3. Get the number of days, defaulting to 1
    const days = parseInt(document.getElementById('ld_days').value) || 1;
                console.log(days);

    // 4. Calculate and set the amount
    document.getElementById('loading_amount').value = (amount * days).toFixed(2);
}

        if (incomeLine === 'daily_trade') {
            const ticketPrice = parseFloat(document.getElementById('dt_ticket').value) || 0;
            const tickets = parseInt(document.getElementById('dt_tickets').value) || 0;
            document.getElementById('dt_amount').value = (ticketPrice * tickets).toFixed(2);
        }

        if (incomeLine === 'daily_trade_arrears') {
            const ticketPrice = parseFloat(document.getElementById('daily_trade_a_ticket_category').value) || 0;
            const tickets = parseInt(document.getElementById('daily_trade_a_no_tickets').value) || 0;
            document.getElementById('daily_trade_a_amount').value = (ticketPrice * tickets).toFixed(2);
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
        document.getElementById('sb_monthly').value = '₦' + parseFloat(option.getAttribute('data-monthly') || 0)
            .toLocaleString();
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
                &copy; <?php echo date('Y'); ?> WEALTH CREATION ERP. All rights reserved. Developed by Woobs Resources
                Ltd.
            </div>
        </div>
    </footer>
</body>

</html>