<?php
include('includes/db.php');
include('includes/helpers.php');

// --- PAGINATION ---
$perPage = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- FILTERS ---
$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : "";
$date_to   = isset($_GET['date_to'])   ? $_GET['date_to']   : "";

// --- QUERY BUILDER ---
$where = [];
$params = [];

if ($q !== "") {
    $where[] = "(agt.transaction_desc LIKE :q OR agt.receipt_no LIKE :q)";
    $params[':q'] = "%$q%";
}

if ($date_from !== "") {
    $where[] = "agt.date_of_payment >= :df";
    $params[':df'] = $date_from;
}

if ($date_to !== "") {
    $where[] = "agt.date_of_payment <= :dt";
    $params[':dt'] = $date_to;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Total count
$stmt = $db->prepare("SELECT COUNT(*) FROM account_general_transaction_new agt $whereSql");
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();

// Fetch paginated rows
$sql = "SELECT agt.*, a1.acct_alias AS debit_alias, a2.acct_alias AS credit_alias
        FROM account_general_transaction_new agt
        LEFT JOIN accounts a1 ON a1.acct_id = agt.debit_account
        LEFT JOIN accounts a2 ON a2.acct_id = agt.credit_account
        $whereSql
        ORDER BY agt.date_of_payment ASC
        LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page totals
$pageDebit  = array_sum(array_column($rows, "debit_amount"));
$pageCredit = array_sum(array_column($rows, "credit_amount"));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-7xl mx-auto p-6">

    <div class="bg-white p-6 shadow rounded-xl mb-6">
        <h1 class="text-2xl font-bold text-indigo-700 mb-4">General Ledger</h1>

        <!-- SEARCH / FILTER BAR -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                placeholder="Search description or receipt"
                class="border rounded-lg px-3 py-2">

            <input type="date" name="date_from" value="<?= $date_from ?>"
                class="border rounded-lg px-3 py-2"/>

            <input type="date" name="date_to" value="<?= $date_to ?>"
                class="border rounded-lg px-3 py-2"/>

            <button class="bg-indigo-600 text-white rounded-lg px-3 py-2 hover:bg-indigo-700">
                Filter
            </button>
        </form>
    </div>

    <!-- EXPORT BUTTONS -->
    <div class="flex gap-3 mb-4">
        <button onclick="exportExcel()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow">
            Export Excel
        </button>

        <a href="gl_pdf.php?<?= http_build_query($_GET) ?>"
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow">
           Export PDF
        </a>
    </div>

    <!-- LEDGER TABLE -->
    <div class="bg-white shadow rounded-xl overflow-auto">
        <table id="glTable" class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-2 text-left">Date</th>
                    <th class="p-2 text-left">Description</th>
                    <th class="p-2 text-left">Debit</th>
                    <th class="p-2 text-left">Credit</th>
                    <th class="p-2 text-right">Amount</th>
                    <th class="p-2 text-left">Link</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2"><?= htmlspecialchars($r['date_of_payment']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($r['transaction_desc']) ?></td>
                    <td class="p-2 text-indigo-700"><?= htmlspecialchars($r['debit_alias']) ?></td>
                    <td class="p-2 text-pink-700"><?= htmlspecialchars($r['credit_alias']) ?></td>
                    <td class="p-2 text-right font-semibold"><?= fmt_money($r['amount_paid']) ?></td>
                    <td class="p-2">
                        <a class="text-blue-600 underline"
                           href="view_transaction.php?id=<?= $r['remit_id'] ?>">
                           View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>

            <!-- PAGE TOTALS -->
            <tfoot>
                <tr class="bg-gray-100 font-bold">
                    <td colspan="2" class="p-2 text-right">Page Total:</td>
                    <td class="p-2 text-indigo-700"><?= fmt_money($pageDebit) ?></td>
                    <td class="p-2 text-pink-700"><?= fmt_money($pageCredit) ?></td>
                    <td class="p-2 text-right"><?= fmt_money($pageDebit - $pageCredit) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="flex justify-center mt-6">
        <?php
        $pages = ceil($totalRows / $perPage);
        for ($i=1; $i<=$pages; $i++):
        ?>
            <a href="?page=<?=$i?>"
               class="px-3 py-1 border rounded mx-1 <?= $i==$page ? 'bg-indigo-600 text-white' : 'bg-white' ?>">
               <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script>
// Excel Export
function exportExcel() {
    var table = document.getElementById("glTable");
    var wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, "general_ledger.xlsx");
}
</script>

</body>
</html>
