<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger View - IFRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Ledger: <?= htmlspecialchars($acctName ?: 'Account') ?></h1>
                <div class="flex space-x-2">
                    <a href="?action=ifrs&page=summary" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Chart of Accounts</a>
                    <a href="?action=ifrs&page=trial-balance" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Trial Balance</a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($ledgerRows as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['date_of_payment']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['transaction_desc'] ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($row['receipt_no'] ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600"><?= floatval($row['debit']) > 0 ? number_format($row['debit'], 2) : '-' ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600"><?= floatval($row['credit']) > 0 ? number_format($row['credit'], 2) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
