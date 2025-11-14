<header class="bg-fuchsia-700 shadow-xl border-b border-fuchsia-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2">
                    <i class="fas fa-chart-line text-2xl text-fuchsia-300"></i>
                    <span class="text-xl font-bold text-white">Wealth Creation</span>
                    </a>
                </div>

            </div>

            <div class="flex items-center gap-4">

            <?php          
            /*****************************************************
                 Chief Executive Exclusive Navigation begins here    
                 *****************************************************/
                 
                 if ($_SESSION['level'] == "ce") { ?>
                     
                     <div class="relative">
                         <button onclick="toggleDropdown('transactionDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-exchange-alt text-fuchsia-300"></i>
                             <span class="font-semibold text-sm">Transaction</span>
                             <i class="fas fa-chevron-down text-xs"></i>
                         </button>

                         <div id="transactionDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-list mr-2"></i>View Transactions
                             </a>
                             <a href="mod/leasing/trans_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-chart-line mr-2"></i>Print Analysis
                             </a>
                             <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-home mr-2"></i>Kclamp Rent
                             </a>
                             <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-tools mr-2"></i>Kclamp Service Charge
                             </a>
                             <a href="mod/account/payments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-credit-card mr-2"></i>Payments
                             </a>
                             <a href="mod/account/journal_entry.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-book mr-2"></i>Journal Entry
                             </a>
                         </div>
                     </div>

                     <div class="relative">
                         <button onclick="toggleDropdown('reportDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-file-invoice-dollar text-fuchsia-300"></i>
                             <span class="font-semibold text-sm">Finance</span>
                             <i class="fas fa-chevron-down text-xs"></i>
                         </button>
                         <div id="reportDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/account/ledgers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-book-open mr-2"></i>General Ledgers
                             </a>
                             <a href="mod/account/trial_balance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-balance-scale mr-2"></i>Trial Balance
                             </a>
                             <a href="mod/account/profit_loss.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-chart-line mr-2"></i>Income Statement
                             </a>
                             <a href="mod/account/balance_sheet.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-file-invoice-dollar mr-2"></i>Financial Position
                             </a>
                         </div>
                     </div>

                     <div class="relative">
                         <button onclick="toggleDropdown('accountDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-chart-pie text-fuchsia-300"></i>Chart
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="accountDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/account/acct_chart.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-chart-pie mr-2"></i>Account Chart
                             </a>
                         </div>
                     </div>

                     <div class="relative">
                         <button onclick="toggleDropdown('customerDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-users text-fuchsia-300"></i>Customers
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="customerDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/leasing/manage_customer.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-user-cog mr-2"></i>Manage Customers
                             </a>
                         </div>
                     </div>

                 <?php } ?>

                 <?php 
                 /*****************************************************
                 Account Department Exclusive Navigation begins here 
                 *****************************************************/
                 
                 if ($_SESSION['department'] == "Accounts") { ?>
                     <div class="relative">
                         <button onclick="toggleDropdown('accounttransactionDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-exchange-alt text-fuchsia-300"></i>
                             <span class="font-semibold text-sm">Transaction</span>
                             <i class="fas fa-chevron-down text-xs"></i>
                         </button>
                         <div id="accounttransactionDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-list mr-2"></i>View Transactions
                             </a>
                             <a href="mod/leasing/trans_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-chart-line mr-2"></i>Print Analysis
                             </a>
                             <a href="mod/account/post_trans.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-home mr-2"></i>Kclamp/Coldroom/Container Rent
                             </a>
                             <a href="mod/account/post_trans_sc.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-tools mr-2"></i>Kclamp/Coldroom/Container Service Charge
                             </a>
                             <a href="mod/account/payments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-credit-card mr-2"></i>Payments
                             </a>
                             <a href="mod/account/journal_entry.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-book mr-2"></i>Journal Entry
                             </a>
                         </div>
                     </div>

                     <div class="relative dropdown">
                         <button onclick="toggleDropdown('reportDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-file-invoice-dollar text-fuchsia-300"></i>
                             <span class="font-semibold text-sm"> Finance </span>
                             <i class="fas fa-chevron-down text-xs"></i>
                         </button>
                         <div id="reportDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/account/ledgers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-book-open mr-2"></i>General Ledgers
                             </a> 
                             <?php 
                             if ($_SESSION['department'] == "Accounts" && ($_SESSION['level'] == "fc" || $_SESSION['level'] == "senior accountant")) { ?>
                             
                                 <a href="mod/account/trial_balance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-balance-scale mr-2"></i>Trial Balance
                                 </a>
                                 <a href="mod/account/profit_loss.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-chart-line mr-2"></i>Income Statement
                                 </a>
                                 <a href="mod/account/balance_sheet.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-file-invoice-dollar mr-2"></i>Financial Position
                                 </a>
                             <?php } ?>
                             

                         </div>
                     </div>

                     <div class="relative">
                         <button onclick="toggleDropdown('accountDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-chart-pie text-fuchsia-300"></i>Chart
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="accountDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/account/acct_chart.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                 <i class="fas fa-chart-pie mr-2"></i>Account Chart
                             </a>
                         </div>
                     </div>
                     
                     <?php if ($_SESSION['level'] != "dgm") { ?>

                         <div class="relative">
                             <button onclick="toggleDropdown('customerDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                                 <i class="fas fa-users text-fuchsia-300"></i>Shop
                                 <i class="fas fa-chevron-down ml-1 text-sm"></i>
                             </button>
                             <div id="customerDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                                 <a href="mod/leasing/manage_customer.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-user-cog mr-2"></i>Customers' Information
                                 </a>
                             </div>
                         </div>
                     <?php }
                 } ?>


                 <?php
                 /*****************************************************
                 Leasing Department Exclusive Navigation begins here 
                 *****************************************************/
                 if ($_SESSION['department'] == "Wealth Creation") { ?> 
                     <div class="relative">
                         <button onclick="toggleDropdown('wealthDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-file-invoice-dollar text-fuchsia-300"></i>
                             <span class="font-semibold text-sm"> Wealth Department</span>
                             <i class="fas fa-chevron-down text-xs"></i>
                         </button>
                         <div id="wealthDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/leasing/officers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" oncontextmenu="return false;">
                                 <i class="fas fa-user-tie mr-2"></i>Officers
                             </a>
                         </div>
                     </div>

                     <div class="relative">
                         <button onclick="toggleDropdown('collectionDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fas fa-coins text-fuchsia-300"></i>
                             <span class="font-semibold text-sm"> Collections</span>
                             <i class="fas fa-chevron-down text-xs"></i>
                         </button>
                         
                         <div id="collectionDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" oncontextmenu="return false;">
                                 <i class="fas fa-list mr-2"></i>View Transactions
                             </a>
                             <a href="mod/leasing/trans_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" oncontextmenu="return false;">
                                 <i class="fas fa-chart-line mr-2"></i>Print Analysis
                             </a>
                             <a href="mod/account/payments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" oncontextmenu="return false;">
                                 <i class="fas fa-credit-card mr-2"></i>Payments
                             </a>
                             <a href="mod/leasing/post_trans.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" oncontextmenu="return false;">
                                 <i class="fas fa-home mr-2"></i>Kclamp/Coldroom/Container Rent
                             </a>
                             <a href="mod/leasing/post_trans_sc.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" oncontextmenu="return false;">
                                 <i class="fas fa-tools mr-2"></i>Kclamp/Coldroom/Container Service Charge
                             </a>
                         </div>
                     </div>

                     <div class="relative">
                             <button onclick="toggleDropdown('customerDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                                 <i class="fas fa-users text-fuchsia-300"></i>Customers
                                 <i class="fas fa-chevron-down ml-1 text-sm"></i>
                             </button>
                             <div id="customerDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                                 <a href="mod/leasing/manage_customer.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-user-cog mr-2"></i>Manage Customers
                                 </a>
                             </div>
                         </div>
                 <?php } 
                 
                 /*****************************************************
                 CE's Office Department Navigation
                 *****************************************************/
                 if ($_SESSION['department'] == "CE's Office") { ?>
                     <div class="relative">
                         <button onclick="toggleDropdown('ceofficeDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-store text-fuchsia-300"></i>Shop Management
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="ceofficeDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/leasing/vacant_shops.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-home mr-2"></i>Vacant Shops
                             </a>
                             <a href="mod/leasing/manage_customer.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-users mr-2"></i>Manage ALL Customers
                             </a>
                             <a href="mod/leasing/lease_application.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-clipboard-list mr-2"></i>Shop Allocation Dashboard
                             </a>
                         </div>
                     </div>
                 <?php } ?>

                 
                 <?php
                 /*****************************************************
                 Audit/Inspection Department Navigation
                 *****************************************************/
                 if ($_SESSION['department'] == "Audit/Inspections") { ?>
                     <div class="relative">
                         <button onclick="toggleDropdown('auditDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-calculator text-fuchsia-300"></i>Account Dept
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="auditDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-list mr-2"></i>View Transactions
                             </a>
                             <a href="mod/leasing/trans_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-chart-line mr-2"></i>Print Analysis
                             </a>
                             <a href="mod/account/ledgers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-book-open mr-2"></i>General Ledgers
                             </a> 

                             <?php
                             if ($_SESSION['department'] == "Audit/Inspections" && $_SESSION['level'] == "Head, Audit & Inspection") { ?>
                                 <a href="mod/account/trial_balance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-balance-scale mr-2"></i>Trial Balance
                                 </a>
                                 <a href="mod/account/profit_loss.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-chart-line mr-2"></i>Income Statement
                                 </a>
                                 <a href="mod/account/balance_sheet.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                     <i class="fas fa-file-invoice-dollar mr-2"></i>Financial Position
                                 </a>
                             <?php } ?>
                         </div>
                     </div>

                     <div class="relative dropdown">
                         <button onclick="toggleDropdown('auditcustomerDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-users mr-2 text-fuchsia-300"></i>Customers
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="auditcustomerDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="mod/leasing/manage_customer.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-user-cog mr-2"></i>Manage Customers
                             </a>
                         </div>
                     </div>
                 <?php } ?>

                 <?php
                 /*****************************************************
                 DGM Exclusive Navigation begins here    
                 *****************************************************/
                 if ($_SESSION['level'] == "dgm") { ?>
                     <div class="relative">
                         <button onclick="toggleDropdown('dgmexDropdown')" class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white">
                             <i class="fas fa-calculator mr-2 text-fuchsia-300"></i>Account Dept
                             <i class="fas fa-chevron-down ml-1 text-sm"></i>
                         </button>
                         <div id="dgmexDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                             <a href="account_view_transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-list mr-2"></i>View Transactions
                             </a>
                             <a href="mod/leasing/trans_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-chart-line mr-2"></i>Print Analysis
                             </a>
                             <a href="mod/account/ledgers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-book-open mr-2"></i>General Ledgers
                             </a>
                             <a href="mod/account/trial_balance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-balance-scale mr-2"></i>Trial Balance
                             </a>
                             <a href="mod/account/profit_loss.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-chart-line mr-2"></i>Income Statement
                             </a>
                             <a href="mod/account/balance_sheet.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                 <i class="fas fa-file-invoice-dollar mr-2"></i>Financial Position
                             </a>
                         </div>
                     </div>
                 <?php } ?>


                 <div class="text-right">
                     <div class="text-sm font-medium text-white"><?= htmlspecialchars($_SESSION['first_name'] ." ". $_SESSION['last_name']); ?></div>
                     <div class="text-xs text-fuchsia-300"><?= htmlspecialchars($_SESSION['department']) ?></div>
                 </div>
                 <div class="relative">
                     <button class="flex items-center gap-2 bg-fuchsia-600 hover:bg-fuchsia-500 px-3 py-2 rounded-lg transition-colors text-white" onclick="toggleDropdown('userDropdown')">
                         <div class="w-8 h-8 bg-fuchsia-300 rounded-full flex items-center justify-center text-fuchsia-800 text-sm font-semibold">
                             <?= strtoupper($_SESSION['first_name'][0]) ?>
                         </div>
                         <i class="fas fa-chevron-down text-xs"></i>
                     </button>
                     <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                         <a href="./../access.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                             <i class="fas fa-tachometer-alt mr-2"></i> Access page
                         </a>
                         <div class="border-t my-1"></div>
                         <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                             <i class="fas fa-sign-out-alt mr-2"></i> Logout
                         </a>
                     </div>
                 </div>

             </div>
         </div>
     </div>
</header>