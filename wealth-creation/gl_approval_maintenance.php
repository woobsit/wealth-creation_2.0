<?php
// gl_approval_maintenance.php
// Optimized maintenance page to detect and approve missing ledger legs
// NOTE: configure DB credentials below

// ==================== CONFIG ====================
$dsn = "mysql:host=localhost;dbname=wealth_creation;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper: validate table name (letters, numbers, underscore only)
function is_valid_table_name($name) {
    return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $name);
}

// ==================== AJAX HANDLER ====================
if (isset($_POST['action']) && $_POST['action'] === 'approve_missing') {
    $txn_id = (int) $_POST['txn_id'];

    $stmt = $db->prepare("SELECT debit_account, credit_account FROM account_general_transaction_new WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $txn_id]);
    $txn = $stmt->fetch();

    if (!$txn) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
        exit;
    }

    $acct_stmt = $db->prepare("SELECT acct_id, acct_table_name FROM accounts WHERE acct_id IN (:debit, :credit)");
    $acct_stmt->execute([':debit' => $txn['debit_account'], ':credit' => $txn['credit_account']]);
    $accts = $acct_stmt->fetchAll();

    $debit_table = $credit_table = null;
    foreach ($accts as $acct) {
        if ((int)$acct['acct_id'] === (int)$txn['debit_account']) {
            $debit_table = $acct['acct_table_name'];
        } elseif ((int)$acct['acct_id'] === (int)$txn['credit_account']) {
            $credit_table = $acct['acct_table_name'];
        }
    }

    if (!$debit_table || !$credit_table) {
        echo json_encode(['success' => false, 'message' => 'Debit or Credit table not found.']);
        exit;
    }

    // Validate table names
    if (!is_valid_table_name($debit_table) || !is_valid_table_name($credit_table)) {
        echo json_encode(['success' => false, 'message' => 'Invalid table name detected.']);
        exit;
    }

    $db->beginTransaction();
    try {
        $db->exec("UPDATE {$debit_table} SET approval_status = 'Approved' WHERE id = {$txn_id}");
        $db->exec("UPDATE {$credit_table} SET approval_status = 'Approved' WHERE id = {$txn_id}");
        $db->commit();

        echo json_encode(['success' => true, 'message' => "Transaction {$txn_id} ledger legs approved successfully."]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== OPTIMIZED FETCH ====================
$yesterday = date('Y-m-d', strtotime('-1 day'));

$query = "
SELECT t.id, t.debit_account, t.credit_account, t.approval_time,
       a1.acct_table_name AS debit_table, a2.acct_table_name AS credit_table
FROM account_general_transaction_new t
LEFT JOIN accounts a1 ON a1.acct_id = t.debit_account
LEFT JOIN accounts a2 ON a2.acct_id = t.credit_account
WHERE DATE(t.approval_time) = :yesterday
  AND t.approval_status = 'Approved'
";
$stmt = $db->prepare($query);
$stmt->execute([':yesterday' => $yesterday]);
$transactions = $stmt->fetchAll();

if (!$transactions) {
    $missing = [];
} else {
    // Collect all table => [txn_ids]
    $table_txn_map = [];
    foreach ($transactions as $txn) {
        if ($txn['debit_table'] && is_valid_table_name($txn['debit_table'])) {
            $table_txn_map[$txn['debit_table']][] = (int)$txn['id'];
        }
        if ($txn['credit_table'] && is_valid_table_name($txn['credit_table'])) {
            $table_txn_map[$txn['credit_table']][] = (int)$txn['id'];
        }
    }

    // Check missing approvals in bulk per table
    $not_approved = []; // txn_id => true
    foreach ($table_txn_map as $table => $txn_ids) {
        // Skip if no valid ids
        if (empty($txn_ids)) continue;

        // Build comma-separated list safely (integers only)
        $in_list = implode(',', array_map('intval', $txn_ids));

        // Query all rows in table that are NOT approved
        try {
            $q = "SELECT transaction_id FROM {$table} WHERE transaction_id IN ($in_list) AND (approval_status != 'Approved' OR approval_status IS NULL)";
            $rows = $db->query($q)->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $tid) {
                $not_approved[(int)$tid] = true;
            }
        } catch (Exception $e) {
            // Table might not exist or other error: log and continue
            // error_log("GL maintenance: table check failed for {$table} - " . $e->getMessage());
            continue;
        }
    }

    // Filter original transactions to those missing at least one leg
    $missing = [];
    foreach ($transactions as $t) {
        if (isset($not_approved[(int)$t['id']])) {
            $missing[] = $t;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GL Maintenance - Missing Ledger Approvals</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center py-10">

<div class="bg-white shadow-lg rounded-xl w-11/12 max-w-6xl p-6">
    <h1 class="text-2xl font-bold text-gray-700 mb-6">⚙️ GL Maintenance - Missing Ledger Approvals</h1>

    <?php if (empty($missing)): ?>
        <p class="text-green-600 text-lg font-medium">✅ All ledger transactions are in sync. No missing approvals found for <?= htmlspecialchars($yesterday) ?>.</p>
    <?php else: ?>
        <div class="mb-4">
            <p class="text-sm text-gray-600">Found <?= count($missing) ?> transaction(s) that are approved in the main table but have missing ledger leg approval(s) for <?= htmlspecialchars($yesterday) ?>.</p>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left">Txn ID</th>
                <th class="px-3 py-2 text-left">Debit Table</th>
                <th class="px-3 py-2 text-left">Credit Table</th>
                <th class="px-3 py-2 text-left">Approval Time</th>
                <th class="px-3 py-2 text-center">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($missing as $txn): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-semibold text-gray-700"><?= htmlspecialchars($txn['id']) ?></td>
                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($txn['debit_table']) ?></td>
                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($txn['credit_table']) ?></td>
                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($txn['approval_time']) ?></td>
                    <td class="px-3 py-2 text-center">
                        <button 
                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                            onclick="approveMissing(<?= (int)$txn['id'] ?>)">
                            Approve Missing Legs
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function approveMissing(txn_id) {
    Swal.fire({
        title: 'Approve Missing Legs?',
        text: 'This will mark debit and credit legs as Approved (both ledger tables).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'approve_missing',
                    txn_id: txn_id
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('✅ Success', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('❌ Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('❌ Error', err.message, 'error'));
        }
    });
}
</script>

</body>
</html>
