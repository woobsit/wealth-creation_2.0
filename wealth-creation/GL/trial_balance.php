<?php
require_once "includes/db.php";
require_once "includes/helpers.php";

// Pagination
$perPage = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Filters
$q = isset($_GET['q']) ? trim($_GET['q']) : ""; // search account alias/code
$class_type = isset($_GET['acct_class_type']) ? $_GET['acct_class_type'] : "";

$where = [];
$params = [];

// Search
if ($q !== "") {
    $where[] = "(a.acct_alias LIKE :q OR a.acct_code LIKE :q)";
    $params[':q'] = "%$q%";
}

if ($class_type !== "") {
    $where[] = "a.acct_class_type = :class_type";
    $params[':class_type'] = $class_type;
}

$whereSql = $where ? " AND " . implode(" AND ", $where) : "";


// COUNT accounts
$countSql = "
SELECT COUNT(*)
FROM accounts a
LEFT JOIN account_general_transaction_new ag
    ON ag.debit_account = a.acct_id
    OR ag.credit_account = a.acct_id
WHERE ag.approval_status='Approved'
$whereSql
";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();


// MAIN SQL
$sql = "
SELECT  
    a.acct_id,
    a.acct_code,
    a.acct_alias,
    a.acct_class_type,
    SUM(CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_debit,
    SUM(CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_credit
FROM accounts a
LEFT JOIN account_general_transaction_new ag
    ON ag.debit_account = a.acct_id
    OR ag.credit_account = a.acct_id
WHERE ag.approval_status='Approved'
$whereSql
GROUP BY a.acct_id, a.acct_code, a.acct_alias, a.acct_class_type
ORDER BY a.acct_code
LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$totalDebit = 0;
$totalCredit = 0;

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<style>
    @media print {
        .no-print { display: none; }
    }
</style>
</head>

<body class="bg-gray-100">

<div class="w-full mx-auto p-6">

    <div class="bg-white shadow p-6 rounded-xl mb-6">
        <h1 class="text-3xl font-bold text-indigo-700">Trial Balance</h1>
        <p class="text-gray-600">IFRS Compliant Trial Balance</p>
    </div>

    <!-- Filters -->
    <form class="bg-white shadow p-4 rounded-xl grid grid-cols-1 md:grid-cols-4 gap-4 mb-4" method="GET">

        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
            placeholder="Search account code / alias"
            class="border rounded-lg px-3 py-2"/>

        <select name="acct_class_type" class="border rounded-lg px-3 py-2">
            <option value="">Filter by Class Type</option>
            <option value="Asset" <?= $class_type=="Asset"?"selected":"" ?>>Asset</option>
            <option value="Liability" <?= $class_type=="Liability"?"selected":"" ?>>Liability</option>
            <option value="Equity" <?= $class_type=="Equity"?"selected":"" ?>>Equity</option>
            <option value="Income" <?= $class_type=="Income"?"selected":"" ?>>Income</option>
            <option value="Expense" <?= $class_type=="Expense"?"selected":"" ?>>Expense</option>
        </select>

        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
            Filter
        </button>
    </form>

    <!-- Buttons -->
    <div class="flex gap-4 mb-4">
        <button onclick="exportExcel()" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">
            Export Excel
        </button>

        <button onclick="window.print()" 
                class="bg-gray-700 hover:bg-black text-white px-4 py-2 rounded-lg shadow">
            Print
        </button>

        <a href="trial_balance_pdf.php?<?= http_build_query($_GET) ?>"
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow">
           PDF
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-auto">
        <table id="tbTable" class="min-w-full text-sm">
            <thead class="bg-gray-50 font-medium">
                <tr>
                    <th class="p-2 text-left">Code</th>
                    <th class="p-2 text-left">Account</th>
                    <th class="p-2">Type</th>
                    <th class="p-2 text-right text-indigo-700">Debit</th>
                    <th class="p-2 text-right text-pink-700">Credit</th>
                    <th class="p-2 text-right">Net Balance</th>
                    <th class="p-2">Ledger</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php 
                    $debit  = floatval($r['total_debit']);
                    $credit = floatval($r['total_credit']);
                    $net = $debit - $credit;

                    $totalDebit  += $debit;
                    $totalCredit += $credit;
                ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2"><?= $r['acct_code'] ?></td>

                    <td class="p-2 font-medium">
                        <?= htmlspecialchars($r['acct_alias']) ?>
                    </td>

                    <td class="p-2 text-center">
                        <?= htmlspecialchars($r['acct_class_type']) ?>
                    </td>

                    <td class="p-2 text-right text-indigo-700">
                        <?= $debit ? fmt_money($debit) : "" ?>
                    </td>

                    <td class="p-2 text-right text-pink-700">
                        <?= $credit ? fmt_money($credit) : "" ?>
                    </td>

                    <td class="p-2 text-right font-semibold">
                        <?= fmt_money($net) ?>
                    </td>

                    <td class="p-2 text-center no-print">
                        <a class="text-blue-600 underline"
                           href="ledger_account.php?acct_id=<?= $r['acct_id'] ?>">
                           Open Ledger
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <!-- Totals -->
            <tfoot>
                <tr class="bg-gray-100 font-bold">
                    <td colspan="3" class="p-2 text-right">Total:</td>
                    <td class="p-2 text-right text-indigo-700"><?= fmt_money($totalDebit) ?></td>
                    <td class="p-2 text-right text-pink-700"><?= fmt_money($totalCredit) ?></td>
                    <td class="p-2 text-right"><?= fmt_money($totalDebit - $totalCredit) ?></td>
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
    let table = document.getElementById("tbTable");
    let wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, ".xlsx");
}
function exportExcel() {
    const table = document.getElementById("tbTable");

    const clonedTable = table.cloneNode(true);
    const actionCells = clonedTable.querySelectorAll('td:last-child, th:last-child');
    actionCells.forEach(cell => cell.remove());

    const wb = XLSX.utils.table_to_book(clonedTable, {sheet: "Trial Balance"});

    const ws = wb.Sheets["Trial Balance"];

    const colWidths = [
        {wch: 12},
        {wch: 15},
        {wch: 25},
        {wch: 35},
        {wch: 15},
        {wch: 15}
    ];
    ws['!cols'] = colWidths;

    const filename = "trial_balance_" + new Date().toISOString().slice(0,10) + ".xlsx";
    XLSX.writeFile(wb, filename);
}
</script>

</body>
</html>
