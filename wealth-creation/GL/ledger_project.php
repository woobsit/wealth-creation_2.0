<?php
// Database configuration
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


function validate_table_name($name) {
    return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $name);
}

function fmt_money($v) {
    return number_format((float)$v, 2);
}

// Ledger for a single account_id
$acct_id = isset($_GET['acct_id']) ? (int)($_GET['acct_id']) : 0;

if (!$acct_id) { echo "Missing acct_id"; exit; }

// get account metadata
$acct = $db->prepare('SELECT * FROM accounts WHERE acct_id = :id LIMIT 1');
$acct->execute([':id'=>$acct_id]);
$acct = $acct->fetch();
if (!$acct) { echo "Account not found"; exit; }

// We'll derive ledger entries by scanning account_general_transaction_new
// and also any additional journal legs (jrn2..jrn7). For simplicity we collect rows where debit_account or credit_account matches.
$perPage = 100;
$page = max(1, isset($_GET['page']) ? (int)($_GET['page']) : 1);
$offset = ($page-1)*$perPage;

$sql = "SELECT id, date_of_payment, transaction_desc, debit_account, credit_account, debit_amount_jrn1, credit_amount_jrn1, receipt_no FROM account_general_transaction_new
        WHERE (debit_account = :acct OR credit_account = :acct)
        ORDER BY date_of_payment ASC LIMIT :lim OFFSET :off";
$stmt = $db->prepare($sql);
$stmt->bindValue(':acct', $acct_id, PDO::PARAM_INT);
$stmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$entries = $stmt->fetchAll();

// compute running balance - need account type to know normal balance. We'll assume Asset/Expense = Debit normal; Liability/Equity/Income = Credit normal
//$normalDebit = in_array(isset($acct['acct_type']) ? $acct['acct_type'] : 'Asset', ['Asset','Expense']);
$acctType = isset($acct['acct_type']) ? $acct['acct_type'] : 'Asset';
$normalDebit = in_array($acctType, ['Asset','Expense']);
$balance = 0.0;
$rowsOut = [];
foreach ($entries as $e) {
    $debit = ((int)$e['debit_account'] === $acct_id) ? (float)$e['debit_amount_jrn1'] : 0.0;
    $credit = ((int)$e['credit_account'] === $acct_id) ? (float)$e['credit_amount_jrn1'] : 0.0;
    $balance += ($debit - $credit);
    $rowsOut[] = array_merge($e, ['debit'=>$debit, 'credit'=>$credit, 'balance'=>$balance]);
}

// Render
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><title>Ledger</title></head>
<body class="bg-gray-100 p-6">
<div class="max-w-6xl mx-auto bg-white p-4 rounded shadow">
  <h1 class="text-xl font-bold">Ledger: <?=htmlspecialchars(isset($acct['acct_alias']) ? $acct['acct_alias'] : $acct['acct_code'])?></h1>
  <p class="text-sm text-gray-600">Type: <?=htmlspecialchars($acct['acct_type'])?></p>
  <div class="overflow-x-auto mt-4">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50"><tr><th class="px-2 py-2">Date</th><th class="px-2 py-2">Desc</th><th class="px-2 py-2">Debit</th><th class="px-2 py-2">Credit</th><th class="px-2 py-2">Running Balance</th></tr></thead>
      <tbody>
      <?php foreach ($rowsOut as $r): ?>
        <tr class="border-b hover:bg-gray-50">
          <td class="px-2 py-2"><?=htmlspecialchars($r['date_of_payment'])?></td>
          <td class="px-2 py-2"><?=htmlspecialchars($r['transaction_desc'])?></td>
          <td class="px-2 py-2 text-right"><?=fmt_money($r['debit'])?></td>
          <td class="px-2 py-2 text-right"><?=fmt_money($r['credit'])?></td>
          <td class="px-2 py-2 text-right"><?=fmt_money($r['balance'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>