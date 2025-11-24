<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FC Head Approval - Transaction Posting</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">FC Head - Transaction Approval</h1>
                    <p class="text-gray-600">Level 2: Authorization and budget verification</p>
                </div>
                <div class="bg-amber-100 border border-amber-300 rounded-lg px-4 py-2">
                    <p class="text-sm text-amber-800"><strong><?= count($transactions) ?></strong> Staff-Approved</p>
                </div>
            </div>

            <?php if (empty($transactions)): ?>
            <div class="bg-green-50 border border-green-200 rounded p-6 text-center">
                <p class="text-green-800 font-medium">✓ No staff-approved transactions pending FC review</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                    <thead class="bg-amber-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Debit Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Credit Account</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Amount</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($transactions as $txn): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($txn['date_of_payment']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($txn['transaction_desc'] ?? '-') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($txn['debit_account'] ?? '-') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($txn['credit_account'] ?? '-') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900"><?= number_format($txn['amount_paid'], 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <div class="flex gap-2 justify-center">
                                    <form method="POST" action="?action=fc-approve" style="display:inline;">
                                        <input type="hidden" name="remit_id" value="<?= $txn['remit_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600">
                                            ✓ Approve
                                        </button>
                                    </form>
                                    <button onclick="openRejectModal(<?= $txn['remit_id'] ?>)" class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">
                                        ✗ Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="mt-6 bg-amber-50 border-l-4 border-amber-500 rounded p-4">
                <p class="text-sm text-amber-800">
                    <strong>Role:</strong> As FC Head, you authorize posting to the ledger after verifying budget limits, 
                    policy compliance, and the accuracy of staff-approved entries.
                </p>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Reject Transaction</h3>
            <form method="POST" action="?action=fc-approve">
                <input type="hidden" id="rejectRemitId" name="remit_id">
                <input type="hidden" name="action" value="reject">
                <textarea name="reason" placeholder="Reason for rejection..." required 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 mb-4"
                          rows="4"></textarea>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                        Reject
                    </button>
                    <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(remitId) {
            document.getElementById('rejectRemitId').value = remitId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</body>
</html>
