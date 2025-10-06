<!-- Array ( [amount_posted] => 606500 [amount_remitted] => 669500 [unposted] => 63000 [remit_id] => 17537022955132 [date] => 2025-07-29 )  -->

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="stats-card rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500"> Till Balance </p>
                <p class="text-2xl font-bold text-gray-900"><?= formatCurrency($myTotalTillbalance); ?></p>
            </div>
        </div>
    </div>

    <div class="stats-card rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                </div>
            </div>
            <a href="">
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Unposted Transactions</p>
                <p class="text-2xl font-bold text-gray-900"><?= formatCurrency($remittance_data['unposted']) ?> </p>
                <p class="text-sm text-gray-500">For <?php echo date('Y-m-d'); ?></p>
            </div>
            </a>
        </div>
    </div>

    <div class="stats-card rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-week text-purple-600 text-xl"></i>
                </div>
            </div>
            <a href="view_transactions.php">
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Posted Transations</p>
                <p class="text-2xl font-bold text-gray-900"> <?= formatCurrency($remittance_data['amount_posted']) ?> </p>
            </div>
            </a>
        </div>
    </div>

    <div class="stats-card rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-orange-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500"> Today's Remittance </p>
                <p class="text-2xl font-bold text-gray-900"><?= isset($remittance_data['amount_remitted']) ? formatCurrency($remittance_data['amount_remitted']) : 0; ?></p>
            </div>
        </div>
    </div>
</div>