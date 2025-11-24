<?php
// IFRS Financial Module - 4 Pages in Tailwind UI
// Pages:
// 1. Chart of Accounts Summary
// 2. Ledger View (per account)
// 3. Trial Balance
// 4. IFRS Financial Statements Generator

// ========== PAGE 1: CHART OF ACCOUNTS SUMMARY ==========
?>

<!-- PAGE 1: CHART OF ACCOUNTS SUMMARY -->
<div class="p-6 bg-white rounded-xl shadow-md">
    <h1 class="text-2xl font-bold mb-4">Chart of Accounts Summary</h1>

    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-2">Code</th>
                <th class="p-2">Alias</th>
                <th class="p-2">Class</th>
                <th class="p-2">Debit</th>
                <th class="p-2">Credit</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $acc): ?>
                <tr class="border-b">
                    <td class="p-2"><?= $acc['acct_code'] ?></td>
                    <td class="p-2"><?= $acc['acct_alias'] ?></td>
                    <td class="p-2"><?= $acc['acct_class_type'] ?></td>
                    <td class="p-2 text-green-700 font-semibold"><?= number_format($acc['total_debit']) ?></td>
                    <td class="p-2 text-red-700 font-semibold"><?= number_format($acc['total_credit']) ?></td>
                    <td class="p-2">
                        <a href="ledger.php?acct_id=<?= $acc['acct_id'] ?>" class="text-blue-600 hover:underline">View Ledger</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
// ========== PAGE 2: LEDGER VIEW ==========
?>

<!-- PAGE 2: LEDGER VIEW -->
<div class="p-6 bg-white rounded-xl shadow-md mt-10">
    <h1 class="text-2xl font-bold mb-4">Ledger for <?= $acctName ?></h1>

    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-2">Date</th>
                <th class="p-2">Description</th>
                <th class="p-2">Receipt</th>
                <th class="p-2">Debit</th>
                <th class="p-2">Credit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ledgerRows as $row): ?>
                <tr class="border-b">
                    <td class="p-2"><?= $row['date_of_payment'] ?></td>
                    <td class="p-2"><?= $row['transaction_descr'] ?></td>
                    <td class="p-2"><?= $row['receipt_no'] ?></td>
                    <td class="p-2 text-green-700"><?= number_format($row['debit']) ?></td>
                    <td class="p-2 text-red-700"><?= number_format($row['credit']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
// ========== PAGE 3: TRIAL BALANCE ==========
?>

<!-- PAGE 3: TRIAL BALANCE PREPARATION -->
<div class="p-6 bg-white rounded-xl shadow-md mt-10">
    <h1 class="text-2xl font-bold mb-4">Trial Balance</h1>

    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-2">Account</th>
                <th class="p-2">Debit</th>
                <th class="p-2">Credit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trialBalance as $t): ?>
                <tr class="border-b">
                    <td class="p-2">(<?= $t['acct_code'] ?>) - <?= $t['acct_alias'] ?></td>
                    <td class="p-2 text-green-700"><?= number_format($t['debit']) ?></td>
                    <td class="p-2 text-red-700"><?= number_format($t['credit']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
// ========== PAGE 4: IFRS FINANCIAL STATEMENTS GENERATOR ==========
?>

<!-- PAGE 4: IFRS STATEMENTS -->
<div class="p-6 bg-white rounded-xl shadow-md mt-10">
    <h1 class="text-2xl font-bold mb-4">IFRS Financial Statements Generator</h1>

    <form method="GET" class="space-y-4 bg-gray-50 p-4 rounded-lg">
        <label class="block">
            <span>Choose Period:</span>
            <input type="month" name="period" class="border rounded p-2 w-full" required />
        </label>
        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Generate</button>
    </form>

    <?php if (isset($ifrs)): ?>
        <div class="mt-6">
            <h2 class="text-xl font-bold mb-2">Statement of Financial Position</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-white shadow rounded">
                    <h3 class="font-semibold">Assets</h3>
                    <?= $ifrs['assets_html'] ?>
                </div>
                <div class="p-4 bg-white shadow rounded">
                    <h3 class="font-semibold">Liabilities & Equity</h3>
                    <?= $ifrs['liabilities_html'] ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
