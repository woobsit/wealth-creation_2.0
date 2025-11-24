<style>
@media print {
    .no-print { display: none; }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>


<!-- Header -->
    <div class="mb-3">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">📊 Trial Balance</h1>
        <p class="text-gray-500 mt-1">Summary of all account balances for the selected period.</p>
    </div>
    <!-- SEARCH -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4 no-print bg-white p-4 rounded-lg border shadow-sm">
        <!-- SEARCH -->
        <input id="glSearch" 
            type="text"
            placeholder="Search account, description, amount, receipt..." 
            class="w-full md:w-1/3 p-2 border rounded-md shadow-sm focus:ring focus:ring-blue-200 text-sm">

        <!-- DATE RANGE -->
        <form method="GET" action="">
            <div class="flex items-center gap-2 w-full md:w-auto">
                <input type="hidden" name="action" value="trial-balance">
                <input type="date" name="from_date" value="<?= $fromDate ? htmlspecialchars($fromDate) : '' ?>" class="p-2 border rounded-md shadow-sm text-sm focus:ring focus:ring-blue-200"/>
                <span class="text-gray-500">–</span>
                <input type="date" name="to_date" value="<?= $toDate ? htmlspecialchars($toDate) : '' ?>" class="p-2 border rounded-md shadow-sm text-sm focus:ring focus:ring-blue-200"/>

                <button  type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                    Show Range
                </button>
            </div>
        </form>
        
        
        <!-- EXPORT BUTTONS -->
        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="?action=trial-balance" class="bg-gray-500 text-white rounded-md hover:bg-gray-700 px-3 py-1.5 text-sm">
                Reset
            </a>
            <button onclick="exportExcel()" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-md text-sm">
                Excel
            </button>

            <a href="gl_pdf.php?<?= http_build_query($_GET) ?>"
                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md text-sm">
                PDF
            </a>

            <button onclick="window.print()" 
                class="flex items-center gap-1.5 bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded-md text-sm no-print">

                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 
                        4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 
                        2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                    </path>
                </svg>

                Print
            </button>
        </div>
          <!-- Current Filter Display -->
        <?php if ($fromDate || $toDate): ?>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                <strong>Currently showing period from <?= $fromDate ? htmlspecialchars($fromDate) : 'earliest' ?> to <?= $toDate ? htmlspecialchars($toDate) : 'latest' ?></strong>
            </div>
        <?php endif; ?>               
    </div>
    <!-- <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"></h1> -->
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tblTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tbTable">
                    <?php 
                    $totalDebit = 0;
                    $totalCredit = 0;
                    foreach ($rows as $row): 
                        $totalDebit += floatval($row['total_debit']);
                        $totalCredit += floatval($row['total_credit']);
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($row['acct_code']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= htmlspecialchars($row['acct_desc'] ?: $row['acct_alias']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= htmlspecialchars($row['acct_type']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= htmlspecialchars($row['acct_class'] ?: '-') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                            <?= floatval($row['total_debit']) > 0 ? number_format($row['total_debit'], 2) : '-' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                            <?= floatval($row['total_credit']) > 0 ? number_format($row['total_credit'], 2) : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-100 font-bold border-t-2">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-sm text-gray-900 text-right">TOTALS:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-700">
                            <?= number_format($totalDebit, 2) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-700">
                            <?= number_format($totalCredit, 2) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded text-green-700">
            ✓ Trial Balance is in balance (Debit = Credit = <?= number_format($totalDebit, 2) ?>)
        </div>
        <?php else: ?>
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded text-red-700">
            ✗ Trial Balance is NOT in balance (Difference: <?= number_format(abs($totalDebit - $totalCredit), 2) ?>)
        </div>
        <?php endif; ?>
    <!-- </div> -->
        <script>
            document.getElementById("glSearch").addEventListener("keyup", function() {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll("#tbTable tr");

                rows.forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(filter)
                        ? ""
                        : "none";
                });
            });
        </script>
        <script>
            // Excel Export
            function exportExcel() {
                const table = document.getElementById("tblTable");

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
                    {wch: 15},
                    {wch: 15}
                ];
                ws['!cols'] = colWidths;

                const filename = "trial_balance_" + new Date().toISOString().slice(0,10) + ".xlsx";
                XLSX.writeFile(wb, filename);
            }
        </script>