<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <?php if ($result): ?>
                <div class="inline-block bg-green-100 border border-green-400 rounded-full p-4 mb-4">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-green-800 mb-2">Transaction Approved</h1>
                <p class="text-green-700 mb-6">The transaction has been successfully moved to the next approval level.</p>
            <?php else: ?>
                <div class="inline-block bg-red-100 border border-red-400 rounded-full p-4 mb-4">
                    <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-red-800 mb-2">Transaction Rejected</h1>
                <p class="text-red-700 mb-6">The transaction has been rejected and returned for review.</p>
            <?php endif; ?>

            <div class="flex gap-4 justify-center">
                <a href="?action=posting-demo" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                    Back to Posting
                </a>
                <a href="?action=gl" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-medium">
                    View General Ledger
                </a>
            </div>
        </div>
    </div>
</body>
</html>
