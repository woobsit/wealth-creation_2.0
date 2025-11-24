<?php
include('includes/db.php');
include('includes/helpers.php');
// General Ledger: list all journal-like rows from account_general_transaction_new
// Supports search (q), date_from, date_to, sort (column), order (asc|desc), page

$perPage = 50;

// page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = ($page < 1) ? 1 : $page;

$offset = ($page - 1) * $perPage;

// search
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// date filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to   = isset($_GET['date_to']) ? $_GET['date_to']   : '';

// sorting
$allowedSort = array('date_of_payment', 'amount_paid', 'receipt_no');
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'date_of_payment';

// order
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' 
            ? 'ASC' 
            : 'DESC';

$where = ["1=1"];
$params = [];
if ($q !== '') {
    $where[] = "(transaction_desc LIKE :q OR receipt_no LIKE :q OR shop_no LIKE :q OR remitting_customer LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($date_from) { $where[] = "date_of_payment >= :df"; $params[':df'] = $date_from; }
if ($date_to) { $where[] = "date_of_payment <= :dt"; $params[':dt'] = $date_to; }
$where_sql = implode(' AND ', $where);

// count
$stmt = $db->prepare("SELECT COUNT(*) FROM account_general_transaction_new WHERE $where_sql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$sql = "SELECT * FROM account_general_transaction_new WHERE $where_sql ORDER BY $sort $order LIMIT :lim OFFSET :off";
$stmt = $db->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
stmt_bind_int:
$stmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Fetch account names for mapping (to display account alias or code)
$acctIds = [];
foreach ($rows as $r) {
    if (!empty($r['debit_account'])) $acctIds[] = (int)$r['debit_account'];
    if (!empty($r['credit_account'])) $acctIds[] = (int)$r['credit_account'];
}
$acctMap = [];
if (!empty($acctIds)) {
    $in = implode(',', array_unique($acctIds));
    $qac = $db->query("SELECT acct_id, acct_code, acct_alias FROM accounts WHERE acct_id IN ($in)");
    foreach ($qac->fetchAll() as $a) $acctMap[$a['acct_id']] = $a;
}

// Render (Tailwind minimal)
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<title>General Ledger</title>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-7xl mx-auto">
  <div class="bg-white p-4 rounded shadow">
    <h1 class="text-xl font-bold mb-4">General Ledger</h1>
    <form method="get" class="mb-4 flex gap-2">
      <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="search description, receipt, shop no" class="border px-2 py-1 rounded w-1/3" />
      <input type="date" name="date_from" value="<?=htmlspecialchars($date_from)?>" class="border px-2 py-1 rounded" />
      <input type="date" name="date_to" value="<?=htmlspecialchars($date_to)?>" class="border px-2 py-1 rounded" />
      <select name="sort" class="border px-2 py-1 rounded">
        <option value="date_of_payment" <?= $sort== 'date_of_payment' ? 'selected' : '' ?>>Date</option>
        <option value="amount_paid" <?= $sort== 'amount_paid' ? 'selected' : '' ?>>Amount</option>
        <option value="receipt_no" <?= $sort== 'receipt_no' ? 'selected' : '' ?>>Receipt</option>
      </select>
      <select name="order" class="border px-2 py-1 rounded">
        <option value="desc" <?= $order=='DESC' ? 'selected' : '' ?>>Desc</option>
        <option value="asc" <?= $order=='ASC' ? 'selected' : '' ?>>Asc</option>
      </select>
      <button class="bg-blue-600 text-white px-3 py-1 rounded">Search</button>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
          <tr>
            <th class="px-2 py-2">Date</th>
            <th class="px-2 py-2">Description</th>
            <th class="px-2 py-2">Debit (acct)</th>
            <th class="px-2 py-2">Credit (acct)</th>
            <th class="px-2 py-2">Amount</th>
            <th class="px-2 py-2">Receipt</th>
            <th class="px-2 py-2">Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="border-b hover:bg-gray-50">
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['date_of_payment']); ?></td>

              <td class="px-2 py-2"><?php echo htmlspecialchars($r['transaction_desc']); ?></td>

              <td class="px-2 py-2">
                  <?php
                      $a = isset($acctMap[$r['debit_account']]) ? $acctMap[$r['debit_account']] : null;
                      if ($a) {
                          $alias = isset($a['acct_alias']) ? $a['acct_alias'] : (isset($a['acct_code']) ? $a['acct_code'] : '');
                          echo htmlspecialchars($alias);
                      } else {
                          echo htmlspecialchars($r['debit_account']);
                      }
                  ?>
              </td>

              <td class="px-2 py-2">
                  <?php
                      $a = isset($acctMap[$r['credit_account']]) ? $acctMap[$r['credit_account']] : null;
                      if ($a) {
                          $alias = isset($a['acct_alias']) ? $a['acct_alias'] : (isset($a['acct_code']) ? $a['acct_code'] : '');
                          echo htmlspecialchars($alias);
                      } else {
                          echo htmlspecialchars($r['credit_account']);
                      }
                  ?>
              </td>

              <td class="px-2 py-2 text-right"><?php echo fmt_money($r['amount_paid']); ?></td>

              <td class="px-2 py-2"><?php echo htmlspecialchars($r['receipt_no']); ?></td>

              <td class="px-2 py-2"><?php echo htmlspecialchars($r['approval_status']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex justify-between items-center">
      <div>Showing <?= count($rows) ?> of <?= $total ?> results</div>
      <div class="space-x-2">
        <?php for ($p=1;$p<=ceil($total/$perPage);$p++): ?>
          <a class="px-2 py-1 border rounded <?= $p==$page ? 'bg-gray-200' : '' ?>" href="?page=<?=$p?>&<?=htmlspecialchars(http_build_query(array_merge($_GET,['page'=>$p])))?>"><?=$p?></a>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>