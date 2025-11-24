<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger - Accounting System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body class="bg-gray-50">

<div class="container mx-auto px-4 py-6">

    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">📘 General Ledger</h1>
        <p class="text-gray-600">All approved debit and credit entries from the accounting system.</p>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Account Dropdown -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Choose a ledger to view</label>
                    <select name="acct_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">-- All Accounts --</option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['acct_id'] ?>" <?= $selectedAccount == $acc['acct_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['acct_code']) ?> - <?= htmlspecialchars($acc['acct_alias'] ?: $acc['acct_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- From Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="from_date" value="<?= $fromDate ? htmlspecialchars($fromDate) : '' ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <!-- To Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="to_date" value="<?= $toDate ? htmlspecialchars($toDate) : '' ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <!-- Search Box -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input id="glSearch" type="text" placeholder="Search..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm">
                    Show Range
                </button>
                <a href="?action=gl" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 font-medium text-sm">
                    Reset
                </a>
                <button type="button" onclick="exportExcel()" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium text-sm">
                    Export Excel
                </button>
                <button type="button" onclick="window.print()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium text-sm">
                    Print
                </button>
            </div>
        </form>

        <!-- Current Filter Display -->
        <?php if ($fromDate || $toDate || $selectedAccount): ?>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                <strong>Currently showing entries from <?= $fromDate ? htmlspecialchars($fromDate) : 'earliest' ?> to <?= $toDate ? htmlspecialchars($toDate) : 'latest' ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Results Section -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <p class="text-gray-700">
                <strong><?= number_format($totalRows) ?></strong> transactions found
            </p>
        </div>

        <!-- Ledger Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="gtlTable">
                <thead class="bg-gray-100 border-b border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Date</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Account Code</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Account</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Description</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Receipt</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700">Debit</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700">Credit</th>
                    </tr>
                </thead>
                <tbody id="glTable">
                    <?php 
                    $pageDebit = 0;
                    $pageCredit = 0;
                    foreach ($rows as $row): 
                        $pageDebit += floatval($row['debit']);
                        $pageCredit += floatval($row['credit']);
                    ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-900 text-sm"><?= htmlspecialchars($row['date_of_payment']) ?></td>
                            <td class="px-6 py-3 font-medium text-gray-900 text-sm"><?= htmlspecialchars($row['acct_code']) ?></td>
                            <td class="px-6 py-3 text-gray-900 text-sm"><?= htmlspecialchars($row['acct_alias'] ?: $row['acct_name']) ?></td>
                            <td class="px-6 py-3 text-gray-700 text-sm"><?= htmlspecialchars($row['transaction_desc'] ?: '') ?></td>
                            <td class="px-6 py-3 text-gray-700 text-sm"><?= htmlspecialchars($row['receipt_no'] ?: '') ?></td>
                            <td class="px-6 py-3 text-right text-sm">
                                <?php if ($row['debit'] > 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                        <?= number_format($row['debit'], 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-right text-sm">
                                <?php if ($row['credit'] > 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                        <?= number_format($row['credit'], 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-400">
                    <tr>
                        <td colspan="5" class="px-6 py-3 text-right">Page Totals:</td>
                        <td class="px-6 py-3 text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-green-200 text-green-900">
                                <?= number_format($pageDebit, 2) ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-red-200 text-red-900">
                                <?= number_format($pageCredit, 2) ?>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<script>
// Front-end search functionality
document.getElementById("glSearch").addEventListener("keyup", function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#glTable tr");

    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
    });
});

// Excel Export
function exportExcel() {
    const table = document.getElementById("gtlTable");
    const clonedTable = table.cloneNode(true);
    const wb = XLSX.utils.table_to_book(clonedTable, {sheet: "General Ledger"});
    const ws = wb.Sheets["General Ledger"];
    const colWidths = [{wch: 12}, {wch: 12}, {wch: 20}, {wch: 30}, {wch: 12}, {wch: 15}, {wch: 15}];
    ws['!cols'] = colWidths;
    const filename = "general_ledger_" + new Date().toISOString().slice(0,10) + ".xlsx";
    XLSX.writeFile(wb, filename);
}
</script>

</body>
</html>
