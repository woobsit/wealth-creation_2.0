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

// Trial Balance: aggregate all debit and credit movements from account_general_transaction_new (including jrn2..jrn7)

$sql_debits = [];
$sql_credits = [];
// We'll sum the first leg columns; if you use jrn2..jrn7, extend these columns appropriately. For simplicity handle jrn1..jrn7
$debitCols = ['debit_account','debit_account_jrn2','debit_account_jrn3','debit_account_jrn4','debit_account_jrn5','debit_account_jrn6','debit_account_jrn7'];
$debitAmtCols = ['debit_amount_jrn1','debit_amount_jrn2','debit_amount_jrn3','debit_amount_jrn4','debit_amount_jrn5','debit_amount_jrn6','debit_amount_jrn7'];
$creditCols = ['credit_account','credit_account_jrn2','credit_account_jrn3','credit_account_jrn4','credit_account_jrn5','credit_account_jrn6','credit_account_jrn7'];
$creditAmtCols = ['credit_amount_jrn1','credit_amount_jrn2','credit_amount_jrn3','credit_amount_jrn4','credit_amount_jrn5','credit_amount_jrn6','credit_amount_jrn7'];

// Build UNION ALL selects for debits
$unionParts = [];
for ($i=0;$i<count($debitCols);$i++) {
    $acct_col = $debitCols[$i];
    $amt_col = $debitAmtCols[$i];
    $unionParts[] = "SELECT $acct_col AS acct_id, SUM(COALESCE($amt_col,0)) AS amt FROM account_general_transaction_new WHERE approval_status='Approved' GROUP BY $acct_col";
}
for ($i=0;$i<count($creditCols);$i++) {
    $acct_col = $creditCols[$i];
    $amt_col = $creditAmtCols[$i];
    $unionParts[] = "SELECT $acct_col AS acct_id, -SUM(COALESCE($amt_col,0)) AS amt FROM account_general_transaction_new WHERE approval_status='Approved' GROUP BY $acct_col";
}

$tempSql = implode(' UNION ALL ', $unionParts);
$sql = "SELECT acct_id, SUM(amt) AS net_amount FROM ( $tempSql ) AS t GROUP BY acct_id HAVING acct_id IS NOT NULL";
$stmt = $db->query($sql);
$data = $stmt->fetchAll();

// Map to accounts for display
$acctIds = array_filter(array_map(fn($r)=>(int)$r['acct_id'], $data));
$acctMap = [];
if (!empty($acctIds)) {
    $in = implode(',', array_unique($acctIds));
    $q = $db->query("SELECT acct_id, acct_code, acct_alias, acct_type FROM accounts WHERE acct_id IN ($in)");
    foreach ($q->fetchAll() as $a) $acctMap[$a['acct_id']] = $a;
}

// Render
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><title>Trial Balance</title></head>
<body class="bg-gray-100 p-6">
<div class="max-w-6xl mx-auto bg-white p-4 rounded shadow">
  <h1 class="text-xl font-bold mb-4">Trial Balance</h1>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50"><tr><th class="px-2 py-2 text-left">Account</th><th class="px-2 py-2 text-right">Debit</th><th class="px-2 py-2 text-right">Credit</th></tr></thead>
      <tbody>
      <?php $totalDebit=0; $totalCredit=0; foreach ($data as $row): $aid=(int)$row['acct_id']; $amt=(float)$row['net_amount']; $acct=$acctMap[$aid] ?? null; $name=$acct?($acct['acct_alias'] ?? $acct['acct_code']):$aid; if ($amt>=0) { $totalDebit += $amt; $d=$amt; $c=0; } else { $totalCredit += -$amt; $d=0; $c=-$amt; } ?>
        <tr class="border-b hover:bg-gray-50">
          <td class="px-2 py-2"><?=htmlspecialchars($name)?></td>
          <td class="px-2 py-2 text-right"><?=fmt_money($d)?></td>
          <td class="px-2 py-2 text-right"><?=fmt_money($c)?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot class="bg-gray-50 font-semibold"><tr><td class="px-2 py-2">Totals</td><td class="px-2 py-2 text-right"><?=fmt_money($totalDebit)?></td><td class="px-2 py-2 text-right"><?=fmt_money($totalCredit)?></td></tr></tfoot>
    </table>
  </div>
</div>
</body>
</html>
