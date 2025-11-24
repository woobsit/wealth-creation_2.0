<?php
require_once "includes/db.php";
require_once "includes/helpers.php";

// INPUT ACCOUNT
$acct_id = isset($_GET['acct_id']) ? intval($_GET['acct_id']) : 0;
if ($acct_id <= 0) {
    die("Account not specified.");
}

// FETCH ACCOUNT DETAILS
$stmt = $db->prepare("SELECT * FROM accounts WHERE acct_id = ?");
$stmt->execute([$acct_id]);
$acct = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$acct) {
    die("Account not found.");
}

// PAGINATION
$perPage = 60;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// FILTERS
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : "";
$date_to   = isset($_GET['date_to']) ? $_GET['date_to']  : "";
$q = isset($_GET['q']) ? trim($_GET['q']) : "";

$where = [];
$params = [':acct_id' => $acct_id];

// Search description / receipt
if ($q !== "") {
    $where[] = "(ag.transaction_desc LIKE :q OR ag.receipt_no LIKE :q)";
    $params[':q'] = "%$q%";
}

if ($date_from !== "") {
    $where[] = "ag.date_of_payment >= :df";
    $params[':df'] = $date_from;
}

if ($date_to !== "") {
    $where[] = "ag.date_of_payment <= :dt";
    $params[':dt'] = $date_to;
}

$whereSql = $where ? " AND " . implode(" AND ", $where) : "";

// COUNT ROWS
$countSql = "
    SELECT COUNT(*) 
    FROM account_general_transaction_new ag
    WHERE ag.approval_status='Approved'
    AND (ag.debit_account = :acct_id OR ag.credit_account = :acct_id)
    $whereSql
";

$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();

// FETCH ROWS
$sql = "
SELECT
    ag.remit_id,
    ag.date_of_payment,
    ag.transaction_desc,
    ag.receipt_no,
    CASE WHEN ag.debit_account = :acct_id THEN ag.amount_paid ELSE 0 END AS debit,
    CASE WHEN ag.credit_account = :acct_id THEN ag.amount_paid ELSE 0 END AS credit
FROM account_general_transaction_new ag
WHERE ag.approval_status='Approved'
AND (ag.debit_account = :acct_id OR ag.credit_account = :acct_id)
$whereSql
ORDER BY ag.date_of_payment ASC, ag.remit_id ASC
LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Running balance
$running = 0;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>

<body class="bg-gray-100">

<div class="w-full mx-auto p-6">
    <!-- Header -->
    <div class="bg-white shadow p-6 rounded-xl mb-6">
        <h1 class="text-2xl font-bold text-indigo-700 mb-3">
            Ledger - <?= htmlspecialchars($acct['acct_alias'] ?: $acct['acct_desc']) ?>
        </h1>
        <p class="text-gray-600">General Ledger Code: <?= $acct['gl_code'] ?> | Account Code: <?= $acct['acct_code'] ?></p>
    </div>

    <!-- Filters -->
    <form class="bg-white shadow p-4 rounded-xl grid grid-cols-1 md:grid-cols-4 gap-4 mb-4" method="GET">
        <input type="hidden" name="acct_id" value="<?= $acct_id ?>">

        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
               placeholder="Search description / receipt"
               class="border rounded-lg px-3 py-2"/>

        <input type="date" name="date_from" value="<?= $date_from ?>"
               class="border rounded-lg px-3 py-2"/>

        <input type="date" name="date_to" value="<?= $date_to ?>"
               class="border rounded-lg px-3 py-2"/>

        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
            Filter
        </button>
    </form>

    <!-- Buttons -->
    <div class="flex gap-4 mb-4">
        <button onclick="exportExcel()" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">
            Excel
        </button>

        <button onclick="window.print()" 
                class="bg-gray-700 hover:bg-black text-white px-4 py-2 rounded-lg shadow">
            Print
        </button>

        <a href="ledger_pdf.php?<?= http_build_query($_GET) ?>"
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow">
           PDF
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-auto">
        <table id="ledgerTable" class="min-w-full text-sm">
            <thead class="bg-gray-50 font-medium">
                <tr>
                    <th class="p-2 text-left">Date</th>
                    <th class="p-2 text-left">Description</th>
                    <th class="p-2">Receipt</th>
                    <th class="p-2 text-right text-indigo-700">Debit</th>
                    <th class="p-2 text-right text-pink-700">Credit</th>
                    <th class="p-2 text-right">Balance</th>
                    <th class="p-2">Link</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php 
                    $running += ($r['debit'] - $r['credit']);
                ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2"><?= $r['date_of_payment'] ?></td>
                    <td class="p-2"><?= htmlspecialchars($r['transaction_desc']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($r['receipt_no']) ?></td>

                    <td class="p-2 text-right text-indigo-700">
                        <?= $r['debit'] ? fmt_money($r['debit']) : "" ?>
                    </td>

                    <td class="p-2 text-right text-pink-700">
                        <?= $r['credit'] ? fmt_money($r['credit']) : "" ?>
                    </td>

                    <td class="p-2 text-right font-semibold">
                        <?= fmt_money($running) ?>
                    </td>

                    <td class="p-2">
                        <a href="view_transaction.php?id=<?= $r['remit_id'] ?>"
                           class="text-blue-600 underline">
                           View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <!-- Page Totals -->
            <tfoot>
                <?php
                $pageDebit  = array_sum(array_column($rows, "debit"));
                $pageCredit = array_sum(array_column($rows, "credit"));
                ?>
                <tr class="bg-gray-100 font-bold">
                    <td colspan="3" class="p-2 text-right">Page Total:</td>
                    <td class="p-2 text-right text-indigo-700"><?= fmt_money($pageDebit) ?></td>
                    <td class="p-2 text-right text-pink-700"><?= fmt_money($pageCredit) ?></td>
                    <td class="p-2 text-right"><?= fmt_money($pageDebit - $pageCredit) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php 
        $totalPages = ceil($totalRows / $perPage);
        // Safety checks
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        if ($page < 1) {
            $page = 1;
        }
    ?>

    <div class="flex justify-between items-center mt-6 no-print">

        <!-- PAGE INFO -->
        <p class="text-gray-600">
            Page <?= $page ?> of <?= $totalPages ?>  
            (<?= number_format($totalRows) ?> records)
        </p>

        <!-- PAGINATION BUTTONS -->
        <div class="flex space-x-2">

            <!-- FIRST -->
            <?php if ($page > 1): ?>
                <a href="?acct_id=<?= $acct_id ?>&page=1&perPage=<?= $perPage ?>"
                class="px-3 py-2 bg-gray-300 rounded hover:bg-gray-400">First</a>
            <?php endif; ?>

            <!-- PREVIOUS -->
            <?php if ($page > 1): ?>
                <a href="?acct_id=<?= $acct_id ?>&page=<?= $page - 1 ?>&perPage=<?= $perPage ?>"
                class="px-3 py-2 bg-gray-300 rounded hover:bg-gray-400">Prev</a>
            <?php endif; ?>

            <!-- NEXT -->
            <?php if ($page < $totalPages): ?>
                <a href="?acct_id=<?= $acct_id ?>&page=<?= $page + 1 ?>&perPage=<?= $perPage ?>"
                class="px-3 py-2 bg-gray-300 rounded hover:bg-gray-400">Next</a>
            <?php endif; ?>

            <!-- LAST -->
            <?php if ($page < $totalPages): ?>
                <a href="?acct_id=<?= $acct_id ?>&page=<?= $totalPages ?>&perPage=<?= $perPage ?>"
                class="px-3 py-2 bg-gray-300 rounded hover:bg-gray-400">Last</a>
            <?php endif; ?>

        </div>
    </div>


    
    
</div>

<script>
// Excel Export
function exportExcel() {
    let table = document.getElementById("ledgerTable");
    let wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, "ledger_account_<?= $acct_id ?>.xlsx");
}
</script>

</body>
</html>
