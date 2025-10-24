<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/TransactionManager.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/PaymentProcessor.php';

// Check if user is already logged in
requireLogin();
$userId = $_SESSION['user_id'];
$db = $databaseObj;
$user = new User($databaseObj);
$staff = $user->getUserStaffDetail($userId);;
$manager = new TransactionManager($databaseObj);
$stats = $manager->getDashboardStats();

// Check permissions
if (!$manager->checkUserPermissions($staff['user_id'], 'acct_view_record')) {
    header('Location: unauthorized.php');
    exit;
}
// Handle pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$per_page = 20;

// Handle filtering
$date_from = null;
$date_to = null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;

if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
    $date_from = $_GET['date_from'];
    $date_to = $_GET['date_to'];
}

// Handle search
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$transactions = [];
$total_count = 0;

if ($search_term) {
    $transactions = $manager->searchTransactions($search_term, $page, $per_page);
    // For search, we'll use a simplified count
    $total_count = count($transactions);
} else {
    $transactions = $manager->getTransactions($page, $per_page, $date_from, $date_to, $status_filter);
    $total_count = $manager->getTransactionCount($date_from, $date_to, $status_filter);
}

$total_pages = ceil($total_count / $per_page);
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($_POST['action']) {
        case 'approve':
            $transaction_id = $_POST['transaction_id'];
            $response = $manager->approveTransaction($transaction_id, $staff['user_id'], $staff['full_name'], $staff['department']);
            break;
            
        case 'decline':
            $transaction_id = $_POST['transaction_id'];
            $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
            $response = $manager->declineTransaction($transaction_id, $staff['user_id'], $staff['full_name'], $staff['department'], $reason);
            break;
            
        case 'bulk_approve':
            $transaction_ids = isset($_POST['transaction_ids'])
            ? (is_array($_POST['transaction_ids'])
                ? $_POST['transaction_ids']
                : explode(',', $_POST['transaction_ids']))
            : [];

            if (!empty($transaction_ids)) {
                $response = $manager->bulkApproveTransactions($transaction_ids, $staff['user_id'], $staff['full_name'], $staff['department']);
            } else {
                $response = ['success' => false, 'message' => 'No transactions selected'];
            }
            break;
            
        case 'delete':
            $transaction_id = $_POST['transaction_id'];
            $response = $manager->deleteTransaction($transaction_id, $staff['user_id']);
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Transaction Approvals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#059669',
                        accent: '#dc2626',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <?php include('include/header.php'); ?>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Stats -->
        <!-- <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending Posts</p>
                        <p class="text-2xl font-bold text-gray-900"><?php //echo number_format($stats['pending_posts']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">FC Approvals</p>
                        <p class="text-2xl font-bold text-gray-900"><?php //echo number_format($stats['pending_fc_approvals']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Audit Verifications</p>
                        <p class="text-2xl font-bold text-gray-900"><?php //echo number_format($stats['pending_audit_verifications']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Declined</p>
                        <p class="text-2xl font-bold text-gray-900"><?php //echo number_format($stats['declined_transactions']); ?></p>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Date Range Filter -->
                <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                               class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>"
                               class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Filter Range
                        </button>
                        <a href="view_transactions.php" class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Clear
                        </a>
                    </div>
                </form>

                <!-- Search -->
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" 
                           placeholder="Search transactions..."
                           class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Status Filter Tabs -->
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="view_transactions.php" 
                   class="px-4 py-2 rounded-md text-sm font-medium <?php echo !$status_filter ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:text-gray-700'; ?>">
                    All Transactions
                </a>
                <a href="view_transactions.php?status=pending" 
                   class="px-4 py-2 rounded-md text-sm font-medium <?php echo $status_filter === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'text-gray-500 hover:text-gray-700'; ?>">
                    FC Pending
                </a>
                <a href="view_transactions.php?status=approved" 
                   class="px-4 py-2 rounded-md text-sm font-medium <?php echo $status_filter === 'approved' ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Approved
                </a>
                <a href="view_transactions.php?status=declined" 
                   class="px-4 py-2 rounded-md text-sm font-medium <?php echo $status_filter === 'declined' ? 'bg-red-100 text-red-700' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Declined
                </a>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm font-medium text-gray-700">Select All</span>
                    </label>
                    <span id="selectedCount" class="text-sm text-gray-500">0 selected</span>
                </div>
                
                <div class="flex gap-2">
                    <button id="bulkApprove" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Bulk Approve
                    </button>
                    <button id="bulkDecline" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Bulk Decline
                    </button>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Transactions 
                    <?php if ($date_from && $date_to): ?>
                        (<?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>)
                    <?php endif; ?>
                    <?php if ($search_term): ?>
                        - Search: "<?php echo htmlspecialchars($search_term); ?>"
                    <?php endif; ?>
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAllHeader" class="rounded border-gray-300 text-blue-600">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Review</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">FC</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Audit</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Authorization</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                                No transactions found.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $index => $transaction): ?>
                            <tr class="hover:bg-gray-50 transaction-row" data-id="<?php echo $transaction['id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($transaction['approval_status'] === 'Pending' && ($staff['department'] === 'Accounts' || $staff['department'] === 'FC')) || 
                                              ($transaction['verification_status'] === 'Pending' && $staff['department'] === 'Audit/Inspections')): ?>
                                    <input type="checkbox" class="transaction-checkbox rounded border-gray-300 text-blue-600" 
                                           value="<?php echo $transaction['id']; ?>">
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex space-x-2">
                                        <!-- Details Button -->
                                        <button onclick="viewDetails('<?php echo $transaction['id']; ?>')" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        
                                        <!-- Edit Button -->
                                        <?php if ($transaction['approval_status'] !== 'Approved' && 
                                                  ($transaction['posting_officer_id'] === $staff['user_id'] || $staff['level'] === 'ce')): ?>
                                        <button onclick="editTransaction('<?php echo $transaction['id']; ?>')" 
                                                class="text-yellow-600 hover:text-yellow-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Delete Button -->
                                        <?php if (($transaction['date_of_payment'] === date('Y-m-d') && $transaction['posting_officer_id'] === $staff['user_id']) || 
                                                  $staff['level'] === 'ce'): ?>
                                        <button onclick="deleteTransaction('<?php echo $transaction['id']; ?>')" 
                                                class="text-red-600 hover:text-red-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($transaction['date_of_payment'])); ?>
                                </td>
                                
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs">
                                        <?php if ($transaction['plate_no']): ?>
                                            <span class="font-medium text-blue-600"><?php echo strtoupper($transaction['plate_no']); ?></span> - 
                                        <?php endif; ?>
                                        <?php echo ucwords(strtolower($transaction['transaction_desc'])); ?>
                                        
                                        <?php if ($transaction['shop_no']): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Shop: <span class="font-medium"><?php echo $transaction['shop_no']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <span class="font-bold">₦<?php echo number_format($transaction['amount_paid']); ?></span>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $transaction['receipt_no']; ?>
                                </td>
                                
                                <!-- Review Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($transaction['leasing_post_status'] === 'Approved'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                    <?php elseif ($transaction['leasing_post_status'] === 'Declined'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>
                                    <?php elseif ($transaction['leasing_post_status'] === 'Pending'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- FC Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($transaction['approval_status'] === 'Approved'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                    <?php elseif ($transaction['approval_status'] === 'Declined'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>
                                    <?php elseif ($transaction['approval_status'] === 'Pending'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Audit Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($transaction['verification_status'] === 'Verified'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Verified</span>
                                    <?php elseif ($transaction['verification_status'] === 'Declined'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>
                                    <?php elseif ($transaction['verification_status'] === 'Flagged'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Flagged</span>
                                    <?php elseif ($transaction['approval_status'] === 'Approved'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Authorization Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-2">
                                        <?php if ($staff['department'] === 'Accounts' || $staff['department'] === 'fc'): ?>
                                            <?php if ($transaction['approval_status'] === 'Pending'): ?>
                                                <button onclick="approveTransaction('<?php echo $transaction['id']; ?>')" 
                                                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                                    Approve
                                                </button>
                                                <button onclick="declineTransaction('<?php echo $transaction['id']; ?>')" 
                                                        class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                                    Decline
                                                </button>
                                            <?php elseif ($transaction['approval_status'] === 'Declined'): ?>
                                                <button onclick="approveTransaction('<?php echo $transaction['id']; ?>')" 
                                                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                                    Re-approve
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($staff['department'] === 'Audit/Inspections'): ?>
                                            <?php if ($transaction['approval_status'] === 'Approved' && $transaction['verification_status'] === 'Pending'): ?>
                                                <button onclick="verifyTransaction('<?php echo $transaction['id']; ?>')" 
                                                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                                    Verify
                                                </button>
                                                <button onclick="flagTransaction('<?php echo $transaction['id']; ?>')" 
                                                        class="px-3 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                                    Flag
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Transaction Details Row -->
                            <tr class="bg-gray-50">
                                <td colspan="2"></td>
                                <td colspan="4" class="px-6 py-2 text-xs text-gray-600">
                                    <div class="flex flex-wrap gap-4">
                                        <span><strong>Debit:</strong> <?php echo $transaction['debit_account_desc']; ?></span>
                                        <span><strong>Credit:</strong> <?php echo $transaction['credit_account_desc']; ?></span>
                                    </div>
                                </td>
                                <td colspan="4" class="px-6 py-2 text-xs text-gray-600 text-right">
                                    Posted by: <strong><?php echo $transaction['posting_officer_full_name']; ?></strong>
                                    <?php if ($transaction['approving_acct_officer_name']): ?>
                                        | Approved by: <strong><?php echo $transaction['approving_acct_officer_name']; ?></strong>
                                    <?php endif; ?>
                                    <?php if ($transaction['verifying_auditor_name']): ?>
                                        | Verified by: <strong><?php echo $transaction['verifying_auditor_name']; ?></strong>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo (($page - 1) * $per_page) + 1; ?></span> 
                            to <span class="font-medium"><?php echo min($page * $per_page, $total_count); ?></span> 
                            of <span class="font-medium"><?php echo number_format($total_count); ?></span> results
                        </p>
                    </div>
                    
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
    <!-- Transaction Details Modal -->
     <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Transaction Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="modalContent" class="space-y-4">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
     </div>

      <script>
        // Checkbox handling
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkButtons();
        });

        document.getElementById('selectAllHeader').addEventListener('change', function() {
            document.getElementById('selectAll').checked = this.checked;
            document.getElementById('selectAll').dispatchEvent(new Event('change'));
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('transaction-checkbox')) {
                updateBulkButtons();
            }
        });

        function updateBulkButtons() {
            const selectedCheckboxes = document.querySelectorAll('.transaction-checkbox:checked');
            const count = selectedCheckboxes.length;
            
            document.getElementById('selectedCount').textContent = count + ' selected';
            document.getElementById('bulkApprove').disabled = count === 0;
            document.getElementById('bulkDecline').disabled = count === 0;
        }

        // Bulk actions
        document.getElementById('bulkApprove').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .map(cb => cb.value);
                
            //console.log("Selected IDs for bulk approval:", selectedIds);

            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Bulk Approve Transactions',
                text: `Are you sure you want to approve ${selectedIds.length} transactions?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, approve all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('bulk_approve', selectedIds);
                }
            });
        });

        document.getElementById('bulkDecline').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) return;
            
            Swal.fire({
                title: 'Bulk Decline Transactions',
                text: `Are you sure you want to decline ${selectedIds.length} transactions?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, decline all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkAction('bulk_decline', selectedIds);
                }
            });
        });

        // function performBulkAction(action, transactionIds) {
        //     // console.log("Sending bulk action:", action);
        //     // console.log("Transaction IDs being sent:", transactionIds);
            
        //     const formData = new FormData();
        //     formData.append('action', action);
        //     formData.append('transaction_ids', JSON.stringify(transactionIds));

        //     fetch('account_view_transactions.php', {
        //         method: 'POST',
        //         body: formData
        //     })
        //     .then(response => response.json())
        //     .then(data => {
        //         if (data.success) {
        //             Swal.fire('Success!', data.message, 'success').then(() => {
        //                 window.location.reload();
        //             });
        //         } else {
        //             Swal.fire('Error!', data.message, 'error');
        //         }
        //     })
        //     .catch(error => {
        //         Swal.fire('Error!', 'Something went wrong', 'error');
        //     });
        // }
        // function performBulkAction(action, transactionIds) {
        //     //console.log("Performing action:", action, "with IDs:", transactionIds); // 🔍 Debug

        //     fetch('account_view_transactions.php', {
        //         method: 'POST',
        //         headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        //         body: new URLSearchParams({
        //             action: action,
        //             'transaction_ids[]': transactionIds // ✅ ensure array posts properly
        //         })
        //     })
        //     .then(res => res.text()) // use .text() first to debug raw response
        //     .then(data => {
        //         //console.log("Raw response:", data); // 🔍 Debug server output
        //         try {
        //             const json = JSON.parse(data);
        //             //console.log("Parsed JSON:", json);
        //         } catch (e) {
        //             //console.error("Invalid JSON:", e);
        //         }
        //     });
        // }
        function performBulkAction(action, selectedIds) {
            if (!selectedIds.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Transactions Selected',
                    text: 'Please select at least one transaction before performing this action.',
                });
                return;
            }

            const formData = new FormData();
            formData.append('action', action);
            selectedIds.forEach(id => formData.append('transaction_ids[]', id));

            // console.log('Sending bulk action:', action);
            // console.log('Transaction IDs being sent:', selectedIds);

            fetch('account_view_transactions.php', {
                method: 'POST',
                body: formData,
            })
            .then(res => res.text())
            .then(raw => {
                //console.log('Raw response:', raw);

                let data;
                try {
                    data = JSON.parse(raw);
                } catch (err) {
                    //console.error('JSON parse error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Unexpected Error',
                        text: 'Invalid server response received. Please check console for details.',
                    });
                    return;
                }

                //console.log('Parsed response:', data);

                // Prepare SweetAlert content
                const summaryHtml = `
                    <div style="text-align:left; font-size:15px;">
                        <p><strong>✔️ Successful:</strong> ${data.count || 0}</p>
                        <p><strong>❌ Failed:</strong> ${data.failed_count || 0}</p>
                        ${
                            data.failed_ids?.length
                                ? `<details style="margin-top:8px;">
                                    <summary>View Failed Transaction IDs</summary>
                                    <ul style="margin-top:6px; padding-left:20px;">
                                        ${data.failed_ids.map(id => `<li>${id}</li>`).join('')}
                                    </ul>
                                </details>`
                                : ''
                        }
                    </div>
                `;

                // Show alert and refresh only after confirmation
                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Bulk Approval Complete' : 'Bulk Approval Failed',
                    html: summaryHtml,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#16a34a',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Refresh page or reload table cleanly
                        location.reload(); // or call your table reload method if using DataTables
                    }
                });

            })
            .catch(err => {
                //console.error('Fetch error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to connect to the server. Please try again.',
                });
            });
        }




        // Individual actions
        function approveTransaction(transactionId) {
            Swal.fire({
                title: 'Approve Transaction',
                text: 'This record will hit the Financials!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('approve', transactionId);
                }
            });
        }

        function declineTransaction(transactionId) {
            Swal.fire({
                title: 'Decline Transaction',
                text: 'This record will NOT hit the Financials!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, decline!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('decline', transactionId);
                }
            });
        }

        function verifyTransaction(transactionId) {
            Swal.fire({
                title: 'Verify Transaction',
                text: 'This record will be verified!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, verify!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('approve', transactionId);
                }
            });
        }

        function deleteTransaction(transactionId) {
            Swal.fire({
                title: 'Delete Transaction',
                text: 'This will completely delete the transaction!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction('delete', transactionId);
                }
            });
        }

        function performAction(action, transactionId, reason = '') {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('transaction_id', transactionId);
            if (reason) formData.append('reason', reason);

            fetch('account_view_transactions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Something went wrong', 'error');
            });
        }

        function viewDetails(transactionId) {
            // Load transaction details via AJAX
            fetch(`transaction_details.php?id=${transactionId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                    document.getElementById('detailsModal').classList.remove('hidden');
                })
                .catch(error => {
                    Swal.fire('Error!', 'Could not load transaction details', 'error');
                });
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function editTransaction(transactionId) {
            window.location.href = `edit_transaction.php?id=${transactionId}`;
        }

        // Auto-refresh every 10 minutes
        setInterval(() => {
            window.location.reload();
        }, 600000);

    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[1, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7] }
                ]
            });
        });

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

        // Refresh page
        function refreshPage() {
            window.location.reload();
        }

        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(alert => {
                if (alert.textContent.includes('successfully') || alert.textContent.includes('Error')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
     
</body>
</html>