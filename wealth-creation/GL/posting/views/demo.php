<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Posting Workflow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Transaction Posting Workflow</h1>
            <p class="text-gray-600 mb-6">Three-level approval system before transactions hit the ledger</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Level 1: Accounting Staff Review -->
                <div class="border-2 border-blue-300 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">1</div>
                        <h3 class="text-xl font-bold text-gray-800 ml-3">Accounting Staff Review</h3>
                    </div>
                    <ul class="text-gray-700 space-y-2 text-sm mb-4">
                        <li>✓ Verify transaction details</li>
                        <li>✓ Check account codes</li>
                        <li>✓ Validate amounts</li>
                        <li>✓ Approve or reject</li>
                    </ul>
                    <p class="text-xs text-blue-600 font-medium">Status: Pending → Staff-Approved</p>
                </div>

                <!-- Level 2: FC Head Approval -->
                <div class="border-2 border-amber-300 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-amber-500 text-white rounded-full flex items-center justify-center font-bold">2</div>
                        <h3 class="text-xl font-bold text-gray-800 ml-3">FC Head Approval</h3>
                    </div>
                    <ul class="text-gray-700 space-y-2 text-sm mb-4">
                        <li>✓ Review staff notes</li>
                        <li>✓ Check budget limits</li>
                        <li>✓ Authorize posting</li>
                        <li>✓ Approve or reject</li>
                    </ul>
                    <p class="text-xs text-amber-600 font-medium">Status: Staff-Approved → FC-Approved</p>
                </div>

                <!-- Level 3: Audit Approval -->
                <div class="border-2 border-green-300 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">3</div>
                        <h3 class="text-xl font-bold text-gray-800 ml-3">Audit Approval</h3>
                    </div>
                    <ul class="text-gray-700 space-y-2 text-sm mb-4">
                        <li>✓ Final compliance check</li>
                        <li>✓ Verify all approvals</li>
                        <li>✓ Audit trail review</li>
                        <li>✓ Post to ledger</li>
                    </ul>
                    <p class="text-xs text-green-600 font-medium">Status: FC-Approved → Approved</p>
                </div>
            </div>

            <!-- Workflow Information -->
            <div class="bg-gradient-to-r from-blue-50 to-green-50 border-l-4 border-blue-500 rounded p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-3">How the Workflow Works</h3>
                <ol class="text-gray-700 space-y-2 text-sm list-decimal list-inside">
                    <li>Transactions are created with status <strong>"Pending"</strong></li>
                    <li><strong>Accounting Staff</strong> reviews and approves → <strong>"Staff-Approved"</strong></li>
                    <li><strong>FC Head</strong> reviews and approves → <strong>"FC-Approved"</strong></li>
                    <li><strong>Audit Department</strong> conducts final review → <strong>"Approved"</strong></li>
                    <li>Once fully approved, transaction is <strong>posted to the General Ledger</strong></li>
                    <li>All steps are <strong>logged in the audit trail</strong> for compliance</li>
                </ol>
            </div>

            <!-- Access Points -->
            <div class="bg-gray-50 border border-gray-200 rounded p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Role-Based Access</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white border border-gray-200 rounded p-4">
                        <h4 class="font-bold text-blue-600 mb-2">👤 Accounting Staff</h4>
                        <p class="text-sm text-gray-700">Review pending transactions and provide initial approval or rejection with notes.</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded p-4">
                        <h4 class="font-bold text-amber-600 mb-2">💼 FC Head</h4>
                        <p class="text-sm text-gray-700">Review staff-approved transactions and authorize posting to ledger or request changes.</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded p-4">
                        <h4 class="font-bold text-green-600 mb-2">🔍 Audit Department</h4>
                        <p class="text-sm text-gray-700">Conduct final compliance check and post approved transactions to the General Ledger.</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="mt-8 flex gap-4">
                <a href="?action=posting&role=staff" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                    View as Staff
                </a>
                <a href="?action=posting&role=fc_head" class="px-6 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-medium">
                    View as FC Head
                </a>
                <a href="?action=posting&role=audit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    View as Audit
                </a>
            </div>
        </div>
    </div>
</body>
</html>
