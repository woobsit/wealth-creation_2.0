
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Remittance.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

// Initialize objects
$db = new Database();
$user = new User();
$remittanceModel = new Remittance();
$transactionModel = new Transaction();
// Get current user information
$currentUser = $user->getUserById($userId);
// Get remittance ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If ID is not provided, redirect to remittances page
if ($id <= 0) {
    redirect('remittance.php');
}

// Get remittance details
$remittance = $remittanceModel->getRemittanceById($id);

// If remittance not found, redirect
if (!$remittance) {
    redirect('remittance.php');
}

// Get transactions related to this remittance
$transactions = $transactionModel->getTransactionsByRemitId($remittance['remit_id']);

// Calculate summary data
$totalPosted = 0;
$pendingReceipts = $remittance['no_of_receipts'];
$pendingAmount = $remittance['amount_paid'];

if (!empty($transactions)) {
    $totalPosted = count($transactions);
    $pendingReceipts = $remittance['no_of_receipts'] - $totalPosted;
    
    $postedAmount = 0;
    foreach ($transactions as $transaction) {
        $postedAmount += $transaction['amount_paid'];
    }
    
    $pendingAmount = $remittance['amount_paid'] - $postedAmount;
}

// Check if remittance is fully posted
$isFullyPosted = $remittanceModel->isRemittanceFullyPosted($remittance['remit_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Remittance - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-chart-line"></i> Income ERP
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="sidebar-menu-title">MAIN MENU</div>
                
                <a href="index.php" class="sidebar-menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <?php if(hasDepartment('IT/E-Business') || hasDepartment('Accounts')): ?>
                <a href="remittance.php" class="sidebar-menu-item active">
                    <i class="fas fa-money-bill-wave"></i> Remittances
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('leasing')): ?>
                <a href="post_collection.php" class="sidebar-menu-item">
                    <i class="fas fa-receipt"></i> Post Collections
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('Accounts')): ?>
                <a href="approve_posts.php" class="sidebar-menu-item">
                    <i class="fas fa-check-circle"></i> Approve Posts
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('Audit/Inspections')): ?>
                <a href="verify_transactions.php" class="sidebar-menu-item">
                    <i class="fas fa-clipboard-check"></i> Verify Transactions
                </a>
                <?php endif; ?>
                
                <a href="transactions.php" class="sidebar-menu-item">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
                
                <?php if(hasDepartment('IT/E-Business')): ?>
                <div class="sidebar-menu-title">ADMINISTRATION</div>
                
                <a href="accounts.php" class="sidebar-menu-item">
                    <i class="fas fa-chart-pie"></i> Chart of Accounts
                </a>
                
                <a href="users.php" class="sidebar-menu-item">
                    <i class="fas fa-users"></i> User Management
                </a>
                
                <a href="reports.php" class="sidebar-menu-item">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
                
                <a href="settings.php" class="sidebar-menu-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <?php endif; ?>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="toggle-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="page-title">View Remittance</h4>
                </div>
                
                <div class="header-right">
                    <div class="user-dropdown">
                        <button class="user-dropdown-toggle">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="name"><?php echo $currentUser['full_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="user-dropdown-menu">
                            <a href="profile.php" class="user-dropdown-item">
                                <i class="fas fa-user-circle"></i> Profile
                            </a>
                            <a href="change_password.php" class="user-dropdown-item">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <a href="logout.php" class="user-dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Body -->
            <div class="content-body">
                <!-- Remittance Details -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Remittance Details</h5>
                        <div>
                            <a href="print_remittance.php?id=<?php echo $remittance['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                            <a href="remittance.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Remittance ID:</th>
                                        <td><?php echo $remittance['remit_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?php echo formatDate($remittance['date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td><?php echo formatCurrency($remittance['amount_paid']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Number of Receipts:</th>
                                        <td><?php echo $remittance['no_of_receipts']; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Category:</th>
                                        <td><?php echo $remittance['category']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Remitting Officer:</th>
                                        <td><?php echo $remittance['remitting_officer_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Posted By:</th>
                                        <td><?php echo $remittance['posting_officer_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <?php if($isFullyPosted): ?>
                                                <span class="badge badge-success">Fully Posted</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending Posts</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Posting Summary -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-title">Total Amount</div>
                        <div class="stat-card-value"><?php echo formatCurrency($remittance['amount_paid']); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-car-title">Total Receipts</div>
                        <div class="stat-card-value"><?php echo $remittance['no_of_receipts']; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">Posted</div>
                        <div class="stat-card-value"><?php echo $totalPosted; ?> / <?php echo $remittance['no_of_receipts']; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">Pending Amount</div>
                        <div class="stat-card-value"><?php echo formatCurrency($pendingAmount); ?></div>
                    </div>
                </div>
                
                <!-- Posted Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Posted Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Income Line</th>
                                        <th>Posted By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($transactions)): ?>
                                        <?php foreach($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo $transaction['receipt_no']; ?></td>
                                                <td><?php echo formatDate($transaction['date_of_payment']); ?></td>
                                                <td><?php echo $transaction['customer_name']; ?></td>
                                                <td><?php echo formatCurrency($transaction['amount_paid']); ?></td>
                                                <td><?php echo $transaction['income_line']; ?></td>
                                                <td><?php echo $transaction['posting_officer_name']; ?></td>
                                                <td>
                                                    <?php if($transaction['leasing_post_status'] == 'pending'): ?>
                                                        <span class="badge badge-warning">Awaiting Leasing Approval</span>
                                                    <?php elseif($transaction['leasing_post_status'] == 'approved' && $transaction['approval_status'] == 'pending'): ?>
                                                        <span class="badge badge-info">Awaiting Account Approval</span>
                                                    <?php elseif($transaction['approval_status'] == 'approved' && $transaction['verification_status'] == 'pending'): ?>
                                                        <span class="badge badge-primary">Awaiting Verification</span>
                                                    <?php elseif($transaction['verification_status'] == 'verified'): ?>
                                                        <span class="badge badge-success">Verified</span>
                                                    <?php elseif($transaction['leasing_post_status'] == 'rejected' || $transaction['approval_status'] == 'rejected' || $transaction['verification_status'] == 'rejected'): ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No transactions posted yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
