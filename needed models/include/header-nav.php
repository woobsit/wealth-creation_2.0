<header class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                    <span class="text-xl font-bold text-gray-900">WEALTH CREATION ERP</span>
                </div>
                <div class="ml-8">
                    <h1 class="text-lg font-semibold text-gray-900">
                        <?php 
                            if ($staff['department'] === 'Accounts') {
                                echo 'Account Dept. Dashboard'. '<p class="text-sm text-gray-500"> View, Post, Approve & Manage lines of Income</p>';
                            }
                            if ($staff['department'] === 'Wealth Creation') {
                                echo 'Wealth Creation Dashboard'. '<p class="text-sm text-gray-500"> View, Post, & Manage lines of Income</p>';
                            }
                            if ($staff['department'] === 'Audit/Inspections') {
                                echo 'Audit/Inspections Dashboard'. '<p class="text-sm text-gray-500"> View, Approve, & Manage lines of Income</p>';
                            }
                        ?>
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-4">

                <!-- Transaction Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown('transactionDropdown')" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                        <i class="fas fa-exchange-alt text-blue-600"></i>
                        <span class="font-semibold text-sm">Transaction</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="transactionDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <?php  if ($staff['department'] === 'Wealth Creation') : ?>
                        <a href="leasing/view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> View Transactions
                        </a>
                        <a href="post_payments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-receipt mr-2"></i> Post Payments
                        </a>
                        <a href="" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-clipboard-check mr-2"></i> My Remittance
                        </a>
                        <?php endif; ?>
                        <!-- Dropdown forr accounts -->
                        <?php  if ($staff['department'] === 'Accounts' || $staff['level'] === 'fc' || $staff['level'] === 'IT' || $staff['level'] === 'ce') : ?>
                        <a href="account/account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> View Transactions
                        </a>
                        <a href="account_remittance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-money-bill-wave mr-2"></i> Account Remittance
                        </a>
                        
                        <a href="post_payments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-receipt mr-2"></i> Post Payments
                        </a>
                        <a href="" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-clipboard-check mr-2"></i> My Remittance
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Financial Report Dropdown -->
                <div class="relative">
                    <?php if($staff['level'] ==='ce' || $staff['level'] ==='IT' || $staff['level'] ==='fc' || $staff['level'] ==='dgm'): ?>
                    <button onclick="toggleDropdown('reportDropdown')" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                        <i class="fas fa-file-invoice-dollar text-green-600"></i>
                        <span class="font-semibold text-sm">Financial Report</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="reportDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <a href="" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-bar mr-2"></i> General Ledger
                        </a>
                        <a href="income_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-pie mr-2"></i> Trial Balance
                        </a>
                        <a href="income_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-chart-pie mr-2"></i> Income Analysis
                        </a>
                        <a href="audit_log.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-history mr-2"></i> Audit Logs
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- User Profile Dropdown -->
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($staff['full_name']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($staff['department']) ?></div>
                </div>
                <div class="relative">
                    <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown('userDropdown')">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            <?= strtoupper($staff['full_name'][0]) ?>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                        <a href="index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
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