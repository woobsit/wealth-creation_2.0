<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Closing Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-8">
                <?php if ($result): ?>
                    <div class="inline-block bg-green-100 border border-green-400 rounded-full p-4 mb-4">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-green-800 mb-2">Period Closed Successfully</h1>
                    <p class="text-green-700 mb-4">Period: <strong><?= htmlspecialchars($_POST['period'] ?? 'Unknown') ?></strong></p>
                <?php else: ?>
                    <div class="inline-block bg-red-100 border border-red-400 rounded-full p-4 mb-4">
                        <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-red-800 mb-2">Closing Failed</h1>
                    <p class="text-red-700">An error occurred while processing the closing.</p>
                <?php endif; ?>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Summary</h2>
                <ul class="text-gray-700 space-y-2">
                    <li>✓ Revenue accounts closed</li>
                    <li>✓ Expense accounts closed</li>
                    <li>✓ Net income transferred to Retained Earnings</li>
                    <li>✓ Opening balances created for next period</li>
                    <li>✓ All entries logged to journal</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <a href="?action=closing" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 font-medium">
                    Back to Closing
                </a>
                <a href="?action=gl" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 font-medium">
                    View General Ledger
                </a>
            </div>
        </div>
    </div>
</body>
</html>
