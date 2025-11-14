
<?php
// Daily Income Analysis Header Component
function renderDailyIncomeHeader($userName, $department) {
?>
<header class="bg-white shadow-sm border-b">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <i class="fas fa-chart-bar text-2xl text-blue-600"></i>
                    <span class="text-xl font-bold text-gray-900">Income ERP</span>
                </div>
                <div class="ml-8">
                    <h1 class="text-lg font-semibold text-gray-900">Daily Income Line Analysis</h1>
                    <p class="text-sm text-gray-500">Comprehensive daily performance by income line</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($userName) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($department) ?></div>
                </div>
                <div class="relative">
                    <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            <?= strtoupper($userName[0]) ?>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                        <a href="income_performance_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-line mr-2"></i> Performance Analysis
                        </a>
                        <a href="income_ledger.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-book mr-2"></i> Income Ledger
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<?php
}
?>
