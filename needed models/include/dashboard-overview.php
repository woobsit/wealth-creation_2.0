<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-3">
    <div class="stats-card rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500"> Today's Collection </p>
                <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['today']['total']) ? formatCurrency($stats['today']['total']) : 0; ?></p>
                <p class="text-sm text-gray-500"><?php echo isset($stats['today']['count']) ? $stats['today']['count'] : 0; ?> Approved Transactions </p>
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
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500"> This Week </p>
                <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['week']['total']) ? formatCurrency($stats['week']['total']) : 0; ?></p>
                <p class="text-sm text-gray-500"><?php echo isset($stats['week']['count']) ? $stats['week']['count'] : 0 ?> Approved Transactions </p>
            </div>
        </div>
    </div>

    <div class="stats-card rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-week text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">This Month</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['month']['total']) ? formatCurrency($stats['month']['total']) : 0; ?></p>
                <p class="text-sm text-gray-500"><?php echo isset($stats['month']['count']) ? $stats['month']['count'] : 0; ?> Approved Transactions </p>
            </div>
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
                <p class="text-sm font-medium text-gray-500"> Pending Approvals </p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($pendingTransactions); ?></p>
                <p class="text-sm text-gray-500"> Waiting for your action </p>
            </div>
        </div>
    </div>
</div>