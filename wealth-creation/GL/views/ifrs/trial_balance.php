<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance - IFRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Trial Balance</h1>
                <div class="flex space-x-2">
                    <a href="?action=ifrs&page=summary" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Chart of Accounts</a>
                    <a href="?action=ifrs&page=ledger" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Ledger View</a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        $totalDebit = 0;
                        $totalCredit = 0;
                        foreach ($trialBalance as $row): 
                            $totalDebit += floatval($row['total_debit']);
                            $totalCredit += floatval($row['total_credit']);
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['acct_code']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['acct_desc'] ?: '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600"><?= floatval($row['total_debit']) > 0 ? number_format($row['total_debit'], 2) : '-' ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600"><?= floatval($row['total_credit']) > 0 ? number_format($row['total_credit'], 2) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-right text-sm">TOTALS:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-700"><?= number_format($totalDebit, 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-700"><?= number_format($totalCredit, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
