<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Closing & Opening</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Period Closing & Opening</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Closing Information -->
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <h2 class="text-xl font-bold text-blue-900 mb-4">About Closing</h2>
                    <ul class="text-blue-800 space-y-2 text-sm">
                        <li>✓ Closes revenue and expense accounts</li>
                        <li>✓ Transfers net income to retained earnings</li>
                        <li>✓ Creates opening balances for new period</li>
                        <li>✓ Maintains audit trail</li>
                    </ul>
                </div>

                <!-- Closing Form -->
                <div class="bg-amber-50 border border-amber-200 rounded p-4">
                    <h2 class="text-xl font-bold text-amber-900 mb-4">Execute Closing</h2>
                    <form method="POST" action="?action=run-closing" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Fiscal Period</label>
                            <select name="period" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-amber-500">
                                <option value="">-- Select a period --</option>
                                <option value="FY2025-Q1">FY2025 - Q1 (Jan-Mar)</option>
                                <option value="FY2025-Q2">FY2025 - Q2 (Apr-Jun)</option>
                                <option value="FY2025-Q3">FY2025 - Q3 (Jul-Sep)</option>
                                <option value="FY2025-Q4">FY2025 - Q4 (Oct-Dec)</option>
                            </select>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded p-3">
                            <p class="text-red-800 text-sm font-medium">⚠️ Warning: This action cannot be undone. Make sure to backup your data first.</p>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 font-medium">
                            Execute Period Closing
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Closings -->
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Period Closing Notes</h2>
                <div class="bg-gray-50 border border-gray-200 rounded p-4">
                    <p class="text-gray-700 text-sm mb-3">The closing process will:</p>
                    <ul class="text-gray-700 text-sm space-y-2 list-disc list-inside">
                        <li>Close all Revenue accounts and transfer to Income Summary</li>
                        <li>Close all Expense accounts and transfer to Income Summary</li>
                        <li>Transfer net income to Retained Earnings</li>
                        <li>Create opening balances for the next period</li>
                        <li>Record all entries in the journal for audit trail</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
