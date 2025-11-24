
        <style>
            @media print {
                .no-print { display: none; }
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

        <!-- <div class="bg-white rounded-lg shadow-md p-6"> -->
            <!-- HEADER -->
            <div class="mb-3">
                <h1 class="text-3xl font-bold text-gray-700">📘 General Ledger</h1>
                <p class="text-gray-500 mt-1">All approved debit and credit entries from the accounting system.</p>
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
                            <input type="date" name="from_date" value="<?= $fromDate ? htmlspecialchars($fromDate) : '' ?>" class="p-2 border rounded-md shadow-sm text-sm focus:ring focus:ring-blue-200"/>
                            <span class="text-gray-500">–</span>
                            <input type="date" name="to_date" value="<?= $toDate ? htmlspecialchars($toDate) : '' ?>" class="p-2 border rounded-md shadow-sm text-sm focus:ring focus:ring-blue-200"/>

                            <button  type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                Show Range
                            </button>
                        </div>
                     </form>
                    <form method="GET" action="">
                        <!-- ACCOUNT SELECT -->
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <select name="acct_id" 
                                class="p-2 border rounded-md shadow-sm text-sm w-48 focus:ring focus:ring-blue-200">
                                <option value="">Choose a ledger</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['acct_id'] ?>" <?= $selectedAccount == $acc['acct_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acc['acct_code']) ?> - <?= htmlspecialchars($acc['acct_desc'] ?: $acc['acct_alias']) ?>
                            </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                View Ledger
                            </button>
                        </div>
                    </form>

                    <!-- EXPORT BUTTONS -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <a href="?action=gl" class="bg-gray-500 text-white rounded-md hover:bg-gray-700 px-3 py-1.5 text-sm">
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

                </div>


                
                
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="gtlTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-black-200 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-black-200 uppercase tracking-wider">Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-black-200 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-black-200 uppercase tracking-wider">Receipt</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-black-200 uppercase tracking-wider">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-black-200 uppercase tracking-wider">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="glTable">
                        <?php 
                        $totalDebit = 0;
                        $totalCredit = 0;
                        foreach ($rows as $row): 
                            $totalDebit += floatval($row['debit']);
                            $totalCredit += floatval($row['credit']);
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($row['date_of_payment']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <a href="?action=ledger&acct_id=<?= $row['acct_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                    <?= htmlspecialchars($row['acct_code']) ?> - <?= htmlspecialchars($row['acct_desc'] ?: $row['acct_alias']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= htmlspecialchars($row['transaction_desc']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($row['receipt_no']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                <?= $row['debit'] > 0 ? number_format($row['debit'], 2) : '' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                                <?= $row['credit'] > 0 ? number_format($row['credit'], 2) : '' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-bold">
                            <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900">Page Totals:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                <?= number_format($totalDebit, 2) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                <?= number_format($totalCredit, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if ($totalRows > $perPage): ?>
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Showing page <?= $page ?> of <?= ceil($totalRows / $perPage) ?>
                    (<?= $totalRows ?> total transactions)
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?action=gl&page=<?= $page - 1 ?>" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Previous</a>
                    <?php endif; ?>
                    <?php if ($page * $perPage < $totalRows): ?>
                    <a href="?action=gl&page=<?= $page + 1 ?>" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        <!-- FRONT-END SEARCH -->
        <script>
            document.getElementById("glSearch").addEventListener("keyup", function() {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll("#glTable tr");

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
                const table = document.getElementById("gtlTable");

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
