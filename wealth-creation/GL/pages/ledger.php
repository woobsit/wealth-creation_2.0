<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$acct_id = isset($_GET['acct_id']) ? (int)$_GET['acct_id'] : 0;
if (!$acct_id) {
    // show simple selector
    $acctList = $db->query("SELECT acct_id, acct_code, acct_desc FROM accounts ORDER BY acct_code")->fetchAll();
    ?>
    <!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><title>Ledger</title></head>
    <body class="bg-gray-100 p-6"><div class="max-w-4xl mx-auto bg-white p-6 rounded shadow"><h1 class="text-xl font-bold mb-4">Choose Account</h1>
    <ul class="space-y-2">
    <?php foreach ($acctList as $a): ?>
      <li><a class="text-blue-600" href="?acct_id=<?php echo $a['acct_id']; ?>"><?php echo htmlspecialchars($a['acct_code'] . ' - ' . $a['acct_desc']); ?></a></li>
    <?php endforeach; ?>
    </ul></div></body></html>
    <?php
    exit;
}

$acct = $db->prepare("SELECT * FROM accounts WHERE acct_id = :id LIMIT 1");
$acct->execute(array(':id'=>$acct_id));
$acct = $acct->fetch();
if (!$acct) { echo "Account not found"; exit; }

// fetch journal lines for this account (posted entries), order by date
$sql = "SELECT je.entry_date, je.reference_no, je.description, jl.debit, jl.credit
        FROM journal_lines jl
        JOIN journal_entries je ON je.id = jl.journal_entry_id
        WHERE je.status = 'Posted' AND jl.acct_id = :acct
        ORDER BY je.entry_date ASC, jl.id ASC";
$stmt = $db->prepare($sql);
$stmt->execute(array(':acct'=>$acct_id));
$lines = $stmt->fetchAll();

$balance = 0.0;
$rows = array();
$normalDebit = ($acct['acct_class_type'] == 'Debit');
foreach ($lines as $ln) {
    $debit = floatval($ln['debit']);
    $credit = floatval($ln['credit']);
    $balance += ($debit - $credit);
    $rows[] = array('date'=>$ln['entry_date'],'ref'=>$ln['reference_no'],'desc'=>$ln['description'],'debit'=>$debit,'credit'=>$credit,'balance'=>$balance);
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><title>Ledger</title></head>
<body class="bg-gray-100 p-6">
  <div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-1">Ledger: <?php echo htmlspecialchars($acct['acct_code'] . ' - ' . $acct['acct_desc']); ?></h1>
    <p class="text-sm text-gray-600 mb-4">Normal balance: <?php echo htmlspecialchars($acct['acct_class_type']); ?></p>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="px-2 py-2">Date</th><th class="px-2 py-2">Ref</th><th class="px-2 py-2">Description</th><th class="px-2 py-2 text-right">Debit</th><th class="px-2 py-2 text-right">Credit</th><th class="px-2 py-2 text-right">Balance</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['date']); ?></td>
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['ref']); ?></td>
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['desc']); ?></td>
              <td class="px-2 py-2 text-right"><?php echo fmt_money($r['debit']); ?></td>
              <td class="px-2 py-2 text-right"><?php echo fmt_money($r['credit']); ?></td>
              <td class="px-2 py-2 text-right"><?php echo fmt_money($r['balance']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body></html>
