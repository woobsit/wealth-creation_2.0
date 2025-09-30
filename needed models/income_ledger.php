
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
requireAnyRole(['admin', 'manager', 'accounts', 'financial_controller']);

// Use session data directly
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];
$userRole = $_SESSION['user_role'];

// Initialize database
$database = new Database();

// Get parameters
$selected_income_line = isset($_GET['income_line']) ? sanitize($_GET['income_line']) : '';
$from_date = isset($_GET['from_date']) ? sanitize($_GET['from_date']) : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? sanitize($_GET['to_date']) : date('Y-m-t');

// Get all income lines for dropdown
$query = "SELECT DISTINCT acct_desc as income_line, acct_code, gl_code 
          FROM accounts 
          WHERE income_line = TRUE AND active = TRUE 
          ORDER BY acct_desc ASC";
$database->query($query);
$income_lines = $database->resultSet();

// Get account and transaction data if income line is selected
$account_data = null;
$transactions = [];
$total_amount = 0;

if (!empty($selected_income_line)) {
    // Get account details
    $query = "SELECT * FROM accounts WHERE acct_desc = :income_line AND income_line = TRUE LIMIT 1";
    $database->query($query);
    $database->bind(':income_line', $selected_income_line);
    $account_data = $database->single();
    
    if ($account_data) {
        // Get transactions
        $query = "SELECT 
                    t.*,
                    DATE_FORMAT(t.date_of_payment, '%d/%m/%Y') as formatted_date,
                    DATE_FORMAT(t.date_on_receipt, '%d/%m/%Y') as formatted_receipt_date
                  FROM account_general_transaction_new t
                  WHERE t.income_line = :income_line 
                  AND DATE(t.date_of_payment) BETWEEN :from_date AND :to_date
                  ORDER BY t.date_of_payment ASC, t.posting_time ASC";
                  
        $database->query($query);
        $database->bind(':income_line', $selected_income_line);
        $database->bind(':from_date', $from_date);
        $database->bind(':to_date', $to_date);
        $transactions = $database->resultSet();
        
        // Calculate running balance
        $balance = 0;
        foreach ($transactions as &$transaction) {
            $balance += (float)$transaction['amount_paid'];
            $transaction['balance'] = $balance;
        }
        $total_amount = $balance;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Ledger - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <style>
        .table-container {
            max-height: 70vh;
            overflow: auto;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
        }
        .balance-positive {
            color: #059669;
            font-weight: bold;
        }
        .balance-negative {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-book text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">Income ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">Income Line Ledger</h1>
                        <p class="text-sm text-gray-500">Detailed transaction ledger for income lines</p>
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
                            <a href="daily_income_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-chart-bar mr-2"></i> Daily Analysis
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

    <!-- Main Content -->
    <main class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Filter Section -->
        <div class="mb-6 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Ledger Filters</h2>
                <?php if (!empty($selected_income_line)): ?>
                <div class="flex gap-4">
                    <button id="copyBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i> Copy
                    </button>
                    <button id="excelBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i> Excel
                    </button>
                    <button id="pdfBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i> PDF
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Income Line</label>
                    <select name="income_line" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Income Line</option>
                        <?php foreach ($income_lines as $line): ?>
                            <option value="<?= htmlspecialchars($line['income_line']) ?>" 
                                    <?= ($selected_income_line == $line['income_line']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($line['income_line']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="from_date" value="<?= $from_date ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="to_date" value="<?= $to_date ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-search mr-2"></i> View Ledger
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($selected_income_line) && $account_data): ?>
        <!-- Ledger Header -->
        <div class="mb-6 bg-white rounded-lg shadow p-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <h3 class="text-lg font-semibold text-green-800">
                    <i class="fas fa-book mr-2"></i>
                    <?= htmlspecialchars($selected_income_line) ?> Ledger
                </h3>
                <p class="text-sm text-green-600 mt-1">
                    Revenue Account | Code: <?= htmlspecialchars($account_data['acct_code']) ?>/<?= htmlspecialchars($account_data['gl_code']) ?>
                </p>
            </div>
            
            <div class="text-sm text-gray-600 mb-4">
                Currently showing entries from <strong><?= date('d/m/Y', strtotime($from_date)) ?></strong> 
                to <strong><?= date('d/m/Y', strtotime($to_date)) ?></strong>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Total Transactions</div>
                    <div class="text-2xl font-bold text-blue-900"><?= count($transactions) ?></div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Total Amount</div>
                    <div class="text-2xl font-bold text-green-900"><?= formatCurrency($total_amount) ?></div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 font-medium">Average Transaction</div>
                    <div class="text-2xl font-bold text-purple-900">
                        <?= count($transactions) > 0 ? formatCurrency($total_amount / count($transactions)) : formatCurrency(0) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transaction Details</h3>
            </div>
            
            <div class="table-container">
                <table id="ledgerTable" class="min-w-full">
                    <thead class="sticky-header bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S/N</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date on Receipt</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref/Cheque No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Journal No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Description</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit Amount (₦)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Amount (₦)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance (₦)</th>
                        </tr>
                    </thead>
                    
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p>No transactions found for the selected period.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $index => $transaction): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-sm text-gray-900"><?= $index + 1 ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-900"><?= $transaction['formatted_date'] ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-900"><?= $transaction['formatted_receipt_date'] ?: '-' ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($transaction['cheque_no'] ?: $transaction['ref_no'] ?: '-') ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($transaction['id']) ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($transaction['receipt_no'] ?: '-') ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <?= htmlspecialchars($transaction['transaction_desc'] ?: $transaction['customer_name'] ?: 'Revenue Collection') ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-right text-gray-900">-</td>
                                    <td class="px-4 py-4 text-sm text-right text-gray-900"><?= number_format($transaction['amount_paid'], 2) ?></td>
                                    <td class="px-4 py-4 text-sm text-right balance-positive"><?= number_format($transaction['balance'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    
    <script>
        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick) {
                dropdown.classList.add('hidden');
            }
        });

        $(document).ready(function() {
            // Initialize DataTable with export functionality
            var table = $('#ledgerTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copyHtml5',
                        text: 'Copy Data',
                        className: 'hidden-button'
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Export to Excel',
                        title: '<?= htmlspecialchars($selected_income_line) ?> Ledger - <?= date("d/m/Y", strtotime($from_date)) ?> to <?= date("d/m/Y", strtotime($to_date)) ?>',
                        className: 'hidden-button'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: 'Export to PDF',
                        title: '<?= htmlspecialchars($selected_income_line) ?> Ledger',
                        className: 'hidden-button',
                        orientation: 'landscape',
                        pageSize: 'A4'
                    }
                ],
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                scrollX: true,
                pageLength: 25
            });
            
            // Connect custom buttons to DataTables buttons
            $('#copyBtn').on('click', function() {
                $('.buttons-copy').click();
            });
            
            $('#excelBtn').on('click', function() {
                $('.buttons-excel').click();
            });
            
            $('#pdfBtn').on('click', function() {
                $('.buttons-pdf').click();
            });
            
            // Hide DataTables buttons
            $('.hidden-button').hide();
        });
    </script>
</body>
</html>
