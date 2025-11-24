<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/Journals.php';

// optional as-of filter
$asOf = isset($_GET['as_of']) && $_GET['as_of'] ? $_GET['as_of'] : null;

$journal = new Journal($db);
$tbMap = $journal->trialBalance($asOf);

// fetch account metadata for the returned accounts
$acctIds = array();
foreach ($tbMap as $aid => $v) $acctIds[] = (int)$aid;
$acctMap = array();
if (!empty($acctIds)) {
    $in = implode(',', $acctIds);
    $rows = $db->query("SELECT acct_id, acct_code, acct_name, acct_type FROM accounts WHERE acct_id IN ($in)")->fetchAll();
    foreach ($rows as $r) $acctMap[$r['acct_id']] = $r;
}

$totalDebit = 0; $totalCredit = 0;
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><title>Trial Balance</title></head>
<body class="bg-gray-100 p-6">
  <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Trial Balance <?php if ($asOf) echo 'as of ' . htmlspecialchars($asOf); ?></h1>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="px-2 py-2 text-left">Account</th><th class="px-2 py-2 text-right">Debit</th><th class="px-2 py-2 text-right">Credit</th></tr></thead>
        <tbody>
        <?php foreach ($tbMap as $aid => $vals): 
            $acct = isset($acctMap[$aid]) ? $acctMap[$aid] : array('acct_code'=>$aid,'acct_name'=>'Unknown');
            $d = floatval($vals['debit']); $c = floatval($vals['credit']);
            $totalDebit += $d; $totalCredit += $c;
        ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-2 py-2"><?php echo htmlspecialchars($acct['acct_code'] . ' - ' . $acct['acct_name']); ?></td>
            <td class="px-2 py-2 text-right"><?php echo fmt_money($d); ?></td>
            <td class="px-2 py-2 text-right"><?php echo fmt_money($c); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-gray-50 font-semibold"><tr><td class="px-2 py-2">Totals</td><td class="px-2 py-2 text-right"><?php echo fmt_money($totalDebit); ?></td><td class="px-2 py-2 text-right"><?php echo fmt_money($totalCredit); ?></td></tr></tfoot>
      </table>
    </div>
  </div>
</body></html>
