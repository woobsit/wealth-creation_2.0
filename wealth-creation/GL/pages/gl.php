<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// pagination & filters (PHP 5.6 style)
$perPage = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$allowedSort = array('entry_date','amount','reference_no');
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'entry_date';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

// count and fetch posted journal entries (master), join totals
$where = array("je.status = 'Posted'");
$params = array();
if ($q !== '') { $where[] = "(je.description LIKE :q OR je.reference_no LIKE :q)"; $params[':q'] = "%$q%"; }
if ($date_from) { $where[] = "je.entry_date >= :df"; $params[':df'] = $date_from; }
if ($date_to) { $where[] = "je.entry_date <= :dt"; $params[':dt'] = $date_to; }
$where_sql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM journal_entries je WHERE $where_sql";
$stmt = $db->prepare($countSql); $stmt->execute($params); $total = (int)$stmt->fetchColumn();

$sql = "SELECT je.id, je.entry_date, je.description, je.reference_no,
        (SELECT SUM(jl.debit) FROM journal_lines jl WHERE jl.journal_entry_id = je.id) AS total_debit,
        (SELECT SUM(jl.credit) FROM journal_lines jl WHERE jl.journal_entry_id = je.id) AS total_credit
        FROM journal_entries je
        WHERE $where_sql
        ORDER BY $sort $order
        LIMIT :lim OFFSET :off";
$stmt = $db->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><title>General Ledger</title></head>
<body class="bg-gray-100 p-6">
  <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">General Ledger</h1>

    <form method="get" class="mb-4 flex gap-2">
      <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="search description or reference" class="border px-2 py-1 rounded w-1/3" />
      <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="border px-2 py-1 rounded" />
      <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="border px-2 py-1 rounded" />
      <select name="order" class="border px-2 py-1 rounded">
        <option value="desc" <?php if ($order=='DESC') echo 'selected'; ?>>Desc</option>
        <option value="asc" <?php if ($order=='ASC') echo 'selected'; ?>>Asc</option>
      </select>
      <button class="bg-blue-600 text-white px-3 py-1 rounded">Filter</button>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
          <tr>
            <th class="px-2 py-2">Date</th>
            <th class="px-2 py-2">Description</th>
            <th class="px-2 py-2">Reference</th>
            <th class="px-2 py-2 text-right">Debit</th>
            <th class="px-2 py-2 text-right">Credit</th>
            <th class="px-2 py-2">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['entry_date']); ?></td>
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['description']); ?></td>
              <td class="px-2 py-2"><?php echo htmlspecialchars($r['reference_no']); ?></td>
              <td class="px-2 py-2 text-right"><?php echo fmt_money($r['total_debit']); ?></td>
              <td class="px-2 py-2 text-right"><?php echo fmt_money($r['total_credit']); ?></td>
              <td class="px-2 py-2"><a class="text-blue-600" href="journal_view.php?id=<?php echo $r['id']; ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex justify-between">
      <div>Showing <?php echo count($rows); ?> of <?php echo $total; ?></div>
      <div class="space-x-2">
        <?php $pages = ceil($total / $perPage); for ($p=1;$p<=$pages;$p++): ?>
          <a class="px-2 py-1 border rounded <?php if ($p==$page) echo 'bg-gray-200'; ?>" href="?page=<?php echo $p; ?>&<?php echo htmlspecialchars(http_build_query(array_merge($_GET,array('page'=>$p)))); ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</body></html>
