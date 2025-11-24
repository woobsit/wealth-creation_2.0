<?php
include('includes/db.php');

$perPage = isset($_GET['perPage']) ? intval($_GET['perPage']) : 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

$allowedSort = ['acct_code','date_of_payment','debit','credit'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort)
        ? $_GET['sort']
        : 'date_of_payment';

$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc')
            ? 'ASC'
            : 'DESC';

$sql = "
SELECT
    a.acct_id,
    a.acct_code,
    a.acct_alias,
    a.acct_desc,
    ag.id AS txn_id,
    ag.date_of_payment,
    ag.transaction_desc,
    ag.receipt_no,
    CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END AS debit,
    CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END AS credit
FROM accounts a
LEFT JOIN account_general_transaction_new ag
    ON ag.debit_account = a.acct_id
    OR ag.credit_account = a.acct_id
WHERE ag.approval_status = 'Approved'
ORDER BY {$sort} {$order}
LIMIT :offset, :perPage
";

$stmt = $db->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countSql = "
SELECT COUNT(*) AS total
FROM account_general_transaction_new
WHERE approval_status='Approved'
";
$totalRows = $db->query($countSql)->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$pageDebitTotal = 0;
$pageCreditTotal = 0;
foreach ($rows as $r) {
    $pageDebitTotal += floatval($r['debit']);
    $pageCreditTotal += floatval($r['credit']);
}

$totalSql = "
SELECT
    SUM(CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_debit,
    SUM(CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_credit
FROM accounts a
LEFT JOIN account_general_transaction_new ag
    ON ag.debit_account = a.acct_id
    OR ag.credit_account = a.acct_id
WHERE ag.approval_status = 'Approved'
";
$totalsResult = $db->query($totalSql)->fetch(PDO::FETCH_ASSOC);
$grandTotalDebit = floatval($totalsResult['total_debit']);
$grandTotalCredit = floatval($totalsResult['total_credit']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger - Accounting System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .table-striped tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

<div class="max-w-7xl mx-auto p-6">

    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">General Ledger</h1>
                <p class="text-gray-600">Comprehensive record of all approved accounting transactions</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Total Entries</div>
                <div class="text-3xl font-bold text-blue-600"><?= number_format($totalRows) ?></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 mb-6 no-print">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search Transactions</label>
                <input id="glSearch"
                       type="text"
                       placeholder="Search by account, description, amount, or receipt..."
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>
            <div class="flex items-end">
                <a href="trial_balance.php"
                   class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg shadow-md hover:bg-blue-700 transition font-semibold text-center">
                   View Trial Balance
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button onclick="exportExcel()" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg shadow-md transition font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export to Excel
            </button>

            <a href="gl_pdf.php?<?= http_build_query($_GET) ?>"
               class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg shadow-md transition font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Export to PDF
            </a>

            <button onclick="window.print()" class="flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg shadow-md transition font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full table-striped" id="gltTable">

                <thead class="bg-gradient-to-r from-gray-700 to-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Account Code</th>
                        <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Account Name</th>
                        <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Description</th>
                        <th class="px-6 py-4 text-right text-sm font-bold uppercase tracking-wider">Debit</th>
                        <th class="px-6 py-4 text-right text-sm font-bold uppercase tracking-wider">Credit</th>
                        <th class="px-6 py-4 text-center text-sm font-bold uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-4 text-center text-sm font-bold uppercase tracking-wider no-print">Action</th>
                    </tr>
                </thead>

                <tbody id="glTable" class="divide-y divide-gray-200">

                <?php foreach ($rows as $r): ?>
                    <tr class="hover:bg-blue-50 transition-colors duration-150">

                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap font-medium"><?= htmlspecialchars($r['date_of_payment']) ?></td>

                        <td class="px-6 py-4 font-semibold text-gray-900"><?= htmlspecialchars($r['acct_code']) ?></td>

                        <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($r['acct_alias'] ?: $r['acct_desc']) ?></td>

                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($r['transaction_desc']) ?></td>

                        <td class="px-6 py-4 text-right">
                            <?php if ($r['debit'] > 0): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-800">
                                    <?= number_format($r['debit'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <?php if ($r['credit'] > 0): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-rose-100 text-rose-800">
                                    <?= number_format($r['credit'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-center text-gray-600 text-sm">
                            <?= htmlspecialchars($r['receipt_no']) ?>
                        </td>

                        <td class="px-6 py-4 text-center no-print">
                            <a href="view_transaction.php?id=<?= $r['txn_id'] ?>"
                               class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition font-medium text-sm">
                               View
                            </a>
                        </td>

                    </tr>
                <?php endforeach; ?>

                </tbody>

                <tfoot class="bg-gradient-to-r from-gray-100 to-gray-200 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right font-bold text-gray-800 text-lg">Page Totals:</td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-emerald-200 text-emerald-900">
                                <?= number_format($pageDebitTotal, 2) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-rose-200 text-rose-900">
                                <?= number_format($pageCreditTotal, 2) ?>
                            </span>
                        </td>
                        <td colspan="2" class="px-6 py-4"></td>
                    </tr>
                    <tr class="bg-gradient-to-r from-gray-700 to-gray-800 text-white">
                        <td colspan="4" class="px-6 py-4 text-right font-bold text-lg">Grand Totals (All Pages):</td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-emerald-600 text-white">
                                <?= number_format($grandTotalDebit, 2) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-rose-600 text-white">
                                <?= number_format($grandTotalCredit, 2) ?>
                            </span>
                        </td>
                        <td colspan="2" class="px-6 py-4"></td>
                    </tr>
                </tfoot>

            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 mt-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">

            <div class="text-gray-700">
                <span class="font-semibold">Page <?= $page ?></span> of <span class="font-semibold"><?= $totalPages ?></span>
                <span class="text-gray-500 ml-2">(<?= number_format($totalRows) ?> total records)</span>
            </div>

            <div class="flex items-center gap-2">

                <?php if ($page > 1): ?>
                    <a href="?page=1&perPage=<?= $perPage ?>"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                       First
                    </a>
                    <a href="?page=<?= $page-1 ?>&perPage=<?= $perPage ?>"
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                       Previous
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?page=<?= $i ?>&perPage=<?= $perPage ?>"
                       class="px-4 py-2 <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> rounded-lg transition font-medium">
                       <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&perPage=<?= $perPage ?>"
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                       Next
                    </a>
                    <a href="?page=<?= $totalPages ?>&perPage=<?= $perPage ?>"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                       Last
                    </a>
                <?php endif; ?>

            </div>

            <div>
                <select onchange="window.location.href='?page=1&perPage=' + this.value"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25 per page</option>
                    <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50 per page</option>
                    <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100 per page</option>
                    <option value="200" <?= $perPage == 200 ? 'selected' : '' ?>>200 per page</option>
                </select>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById("glSearch");
    const tableRows = document.querySelectorAll("#glTable tr");

    searchInput.addEventListener("keyup", function() {
        const filter = this.value.toLowerCase();

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
});

function exportExcel() {
    const table = document.getElementById("gltTable");

    const clonedTable = table.cloneNode(true);
    const actionCells = clonedTable.querySelectorAll('td:last-child, th:last-child');
    actionCells.forEach(cell => cell.remove());

    const wb = XLSX.utils.table_to_book(clonedTable, {sheet: "General Ledger"});

    const ws = wb.Sheets["General Ledger"];

    const colWidths = [
        {wch: 12},
        {wch: 15},
        {wch: 25},
        {wch: 35},
        {wch: 15},
        {wch: 15},
        {wch: 15}
    ];
    ws['!cols'] = colWidths;

    const filename = "general_ledger_" + new Date().toISOString().slice(0,10) + ".xlsx";
    XLSX.writeFile(wb, filename);
}
</script>

</body>
</html>

