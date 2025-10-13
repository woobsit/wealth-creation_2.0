<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/PaymentProcessor.php';
require __DIR__.'/../app/models/TransactionManager.php';
requireLogin();
date_default_timezone_set('Africa/Lagos');

$db = new Database();
$paymentProcessor = new PaymentProcessor($db);
$transactionManager = new TransactionManager($db);

$current_date = date('Y-m-d');
$errors = [];
$success_message = '';

$user_id = $_SESSION['user_id'];
$db->query("SELECT * FROM roles WHERE user_id = :user_id");
$db->bind(':user_id', $user_id);
$role = $db->single();

if ($role['acct_post_record'] != "Yes") {
    die('You do not have permissions to post records! Contact your HOD for authorization.');
}

$posting_officer_id = $role['user_id'];
$posting_officer_name = $role['full_name'];
$posting_officer_dept = $role['department'];

$db->query("SELECT * FROM accounts WHERE income_line = 'Yes' AND active = 'Yes' ORDER BY acct_desc ASC");
$income_lines = $db->resultSet();

$db->query("SELECT * FROM accounts WHERE active = 'Yes' ORDER BY acct_desc ASC");
$all_accounts = $db->resultSet();

$db->query("SELECT * FROM staffs WHERE active = 'Yes' ORDER BY full_name ASC");
$staff_list = $db->resultSet();

$current_remittance_balance = 0;
$selected_income_line = isset($_GET['income_line']) ? $_GET['income_line'] : '';

if ($posting_officer_dept == "Wealth Creation") {
    $current_remittance_balance = $transactionManager->calculateUnpostedBalance(
        $posting_officer_id,
        $current_date,
        'Other Collection'
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
            'credit_account'        => isset($_POST['credit_account']) ? $_POST['credit_account'] : '',
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

        $validation = $paymentProcessor->validatePosting($posting_data);

        if (!$validation['valid']) {
            $errors = $validation['errors'];
        } else {
            $db->beginTransaction();

            $result = $paymentProcessor->processIncomeLine($posting_data);

            if ($result['success']) {
                $db->endTransaction();
                $success_message = 'Payment successfully posted for approval!';
                header("refresh:2; url=modern_payments_unified.php?income_line=" . urlencode($posting_data['income_line_type']));
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
    <title>Modern Payment Posting - All Income Lines</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($posting_officer_dept == "Wealth Creation"): ?>
            <div class="sticky top-0 z-50 p-4 <?php echo $current_remittance_balance > 0 ? 'bg-red-600 border-b-4 border-red-800' : 'bg-green-600 border-b-4 border-green-800'; ?> text-white text-lg font-bold text-center">
                <span>TILL BALANCE: &#8358;<?php echo number_format($current_remittance_balance, 2); ?></span>
                <?php if ($current_remittance_balance == 0): ?>
                    <span class="ml-5">✓ BALANCED</span>
                <?php else: ?>
                    <span class="ml-5">⚠ UNBALANCED - POST TO CLEAR</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="mt-5">
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800">Modern Payment Posting - All Income Lines</h2>
                <p class="mt-2 text-gray-600">Officer: <strong><?php echo htmlspecialchars($posting_officer_name); ?></strong> |
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

            <div class="bg-white p-6 my-5 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Select Income Line</h3>
                <div id="income-line-cards" class="flex flex-wrap gap-3">
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="car_park" onclick="selectIncomeLine('car_park')">
                        <h4 class="text-lg font-bold mb-1">Car Park</h4>
                        <p class="text-sm">Parking Tickets</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="loading" onclick="selectIncomeLine('loading')">
                        <h4 class="text-lg font-bold mb-1">Loading & Offloading</h4>
                        <p class="text-sm">Cargo Services</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="daily_trade" onclick="selectIncomeLine('daily_trade')">
                        <h4 class="text-lg font-bold mb-1">Daily Trade</h4>
                        <p class="text-sm">Daily Permits</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="hawkers" onclick="selectIncomeLine('hawkers')">
                        <h4 class="text-lg font-bold mb-1">Hawkers</h4>
                        <p class="text-sm">Hawker Permits</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="wheelbarrow" onclick="selectIncomeLine('wheelbarrow')">
                        <h4 class="text-lg font-bold mb-1">Wheelbarrow</h4>
                        <p class="text-sm">Wheelbarrow Permits</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="abattoir" onclick="selectIncomeLine('abattoir')">
                        <h4 class="text-lg font-bold mb-1">Abattoir</h4>
                        <p class="text-sm">Slaughter Charges</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="overnight_parking" onclick="selectIncomeLine('overnight_parking')">
                        <h4 class="text-lg font-bold mb-1">Overnight Parking</h4>
                        <p class="text-sm">Long-term Parking</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="scroll_board" onclick="selectIncomeLine('scroll_board')">
                        <h4 class="text-lg font-bold mb-1">Scroll Board</h4>
                        <p class="text-sm">Advertising</p>
                    </div>
                    <div class="income-line-card inline-block w-48 p-5 bg-gradient-to-br from-blue-500 to-purple-600 text-white text-center rounded-lg cursor-pointer transition-transform duration-200 hover:-translate-y-1 hover:shadow-xl" data-income-line="general" onclick="selectIncomeLine('general')">
                        <h4 class="text-lg font-bold mb-1">General/Other</h4>
                        <p class="text-sm">Miscellaneous</p>
                    </div>
                </div>
            </div>

                <form method="POST" action="" id="payment_form">
                    <input type="hidden" name="income_line_type" id="income_line_type" value="">
                    <input type="hidden" name="posting_officer_dept" value="<?php echo $posting_officer_dept; ?>">
                    <?php if ($posting_officer_dept == "Wealth Creation"): ?>
                        <input type="hidden" name="remit_id" value="">
                        <input type="hidden" name="amt_remitted" value="<?php echo $current_remittance_balance; ?>">
                    <?php endif; ?>

                    <!-- Common Fields Section -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="common_fields">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Transaction Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Date of Payment <span class="text-red-600">*</span></label>
                                <input type="date" name="date_of_payment" value="<?php echo $current_date; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Receipt No <span class="text-red-600">*</span></label>
                                <input type="text" name="receipt_no" placeholder="7-digit receipt number" pattern="^\d{7}$" maxlength="7" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Remitting Staff <span class="text-red-600">*</span></label>
                            <select name="remitting_staff" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">-- Select Staff --</option>
                                <?php foreach ($staff_list as $staff): ?>
                                    <option value="<?php echo $staff['user_id']; ?>-wc">
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Debit Account <span class="text-red-600">*</span></label>
                                <select name="debit_account" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="">-- Select Debit Account --</option>
                                    <?php foreach ($all_accounts as $account): ?>
                                        <option value="<?php echo $account['acct_id']; ?>">
                                            <?php echo htmlspecialchars($account['acct_desc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Credit Account (Income Line) <span class="text-red-600">*</span></label>
                                <select name="credit_account" id="credit_account" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="">-- Select Income Line --</option>
                                    <?php foreach ($income_lines as $account): ?>
                                        <option value="<?php echo $account['acct_id']; ?>"
                                                data-desc="<?php echo htmlspecialchars($account['acct_desc']); ?>">
                                            <?php echo htmlspecialchars($account['acct_desc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="income_line" id="income_line">
                    </div>

                    <!-- Car Park Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_car_park">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Car Park Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Category <span class="text-red-600">*</span></label>
                            <select name="category" id="cp_category" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select category</option>
                                <option value="Car Park 1 (Alpha 1)">Car Park 1 (Alpha 1)</option>
                                <option value="Car Park 2 (Alpha 2)">Car Park 2 (Alpha 2)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Ticket Category <span class="text-red-600">*</span></label>
                                <select name="ticket_category" id="cp_ticket" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                    <option value="500">&#8358;500</option>
                                    <option value="700">&#8358;700</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">No of Tickets <span class="text-red-600">*</span></label>
                                <input type="number" name="no_of_tickets" id="cp_tickets" min="1" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="cp_amount" step="0.01" readonly class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="cp_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Car Park Collection</textarea>
                        </div>
                    </div>

                    <!-- Loading Form -->
                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md" id="form_loading">
                        <h3 class="text-xl font-semibold text-gray-800 border-b-2 border-blue-500 pb-2 mb-5">Loading & Offloading Details</h3>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Category <span class="text-red-600">*</span></label>
                            <select name="category" id="ld_category" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                                <option value="">Select category</option>
                                <option value="Goods (Offloading) - N7000" data-amount="7000">Goods (Offloading) - N7000</option>
                                <option value="Goods (Offloading) - N15000" data-amount="15000">Goods (Offloading) - N15000</option>
                                <option value="Goods (Offloading) - N20000" data-amount="20000">Goods (Offloading) - N20000</option>
                                <option value="Goods (Offloading) - N30000" data-amount="30000">Goods (Offloading) - N30000</option>
                                <option value="Goods (Loading) - N20000" data-amount="20000">Goods (Loading) - N20000</option>
                                <option value="40 feet container - (Offloading) N30000" data-amount="30000">40 feet container - (Offloading) N30000</option>
                                <option value="40 feet container - (Apple Offloading - Sunday) - N60000" data-amount="60000">40 feet container - (Apple Offloading - Sunday) - N60000</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">No of Days <span class="text-red-600">*</span></label>
                                <input type="number" name="no_of_days" id="ld_days" min="1" value="1" onchange="calculateAmount()" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 font-bold text-gray-800">Plate No <span class="text-red-600">*</span></label>
                                <input type="text" name="plate_no" id="ld_plate" maxlength="8" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Amount <span class="text-red-600">*</span></label>
                            <input type="number" name="amount_paid" id="ld_amount" step="0.01" readonly class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 font-bold text-gray-800">Transaction Description</label>
                            <textarea name="transaction_descr" id="ld_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm">Loading & Offloading Charges</textarea>
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
                            <textarea name="transaction_descr" id="gen_desc" rows="3" placeholder="Describe the transaction in detail" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:outline-none text-sm"></textarea>
                        </div>
                    </div>

                    <div class="form-section hidden bg-white p-6 my-5 rounded-lg shadow-md text-center" id="submit_section">
                        <button type="submit" name="btn_post_transaction" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-10 text-base rounded transition-colors duration-200">
                            POST TRANSACTION
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentIncomeLine = '';

        function selectIncomeLine(incomeLine) {
            currentIncomeLine = incomeLine;
            document.getElementById('income_line_type').value = incomeLine;

            document.querySelectorAll('.income-line-card').forEach(card => {
                card.classList.remove('bg-gradient-to-br', 'from-pink-400', 'to-red-500', 'border-4', 'border-white');
                card.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-purple-600');
            });

            const selectedCard = document.querySelector(`[data-income-line="${incomeLine}"]`);
            selectedCard.classList.remove('from-blue-500', 'to-purple-600');
            selectedCard.classList.add('from-pink-400', 'to-red-500', 'border-4', 'border-white');

            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.add('hidden');
            });

            document.getElementById('common_fields').classList.remove('hidden');
            document.getElementById(`form_${incomeLine}`).classList.remove('hidden');
            document.getElementById('submit_section').classList.remove('hidden');
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
                const remittanceBalance = <?php echo $current_remittance_balance; ?>;

                if (amountPaid > remittanceBalance) {
                    alert('WARNING: Amount (₦' + amountPaid.toFixed(2) + ') exceeds remittance balance (₦' +
                          remittanceBalance.toFixed(2) + ')');
                    this.value = remittanceBalance.toFixed(2);
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>