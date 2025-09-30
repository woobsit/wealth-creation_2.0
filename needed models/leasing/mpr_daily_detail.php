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

$account_id = isset($_GET['account']) ? intval($_GET['account']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$account_id || !$date) {
    die("Invalid request. Please provide a valid account and date.");
}

$db = new Database();

// Get account details
$db->query("SELECT acct_desc, acct_table_name FROM accounts WHERE acct_id = :account_id");
$db->bind(':account_id', $account_id);
$account = $db->single();

if (!$account) {
    die("Account not found.");
}

$account_name = $account['acct_desc'];
$table_name = $account['acct_table_name'];

// Fetch transactions for the given date
$db->query("
    SELECT * FROM {$table_name} 
    WHERE acct_id = :account_id 
      AND date = :date 
      AND (approval_status = 'Approved' OR approval_status = '')
");
$db->bind(':account_id', $account_id);
$db->bind(':date', $date);
$transactions = $db->resultSet();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Details - <?php echo htmlspecialchars($account_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 py-6 px-4">
    <div class="max-w-5xl mx-auto bg-white rounded shadow p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">
            <?php echo htmlspecialchars($account_name); ?> - <?php echo date('F j, Y', strtotime($date)); ?>
        </h2>
        <p class="text-gray-600 mb-6">Transaction details for this income line on the selected day</p>

        <?php if (count($transactions) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-2">Payer</th>
                            <th class="px-4 py-2">Amount</th>
                            <th class="px-4 py-2">Payment Mode</th>
                            <th class="px-4 py-2">Purpose</th>
                            <th class="px-4 py-2">Officer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2"><?php echo isset($tx['payer']) ? $tx['payer'] : ''; ?></td>
                                <td class="px-4 py-2 text-green-600 font-semibold"><?php echo number_format($tx['credit_amount'], 2); ?></td>
                                <td class="px-4 py-2"><?php echo isset($tx['payment_mode']) ? $tx['payment_mode'] : ''; ?></td>
                                <td class="px-4 py-2"><?php echo isset($tx['purpose']) ? $tx['purpose'] : ''; ?></td>
                                <td class="px-4 py-2"><?php echo isset($tx['entry_by']) ? $tx['entry_by'] : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-700">No transactions recorded for this date.</p>
        <?php endif; ?>
    </div>
</body>
</html>
