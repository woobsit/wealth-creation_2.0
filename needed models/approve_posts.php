
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
$userId = getLoggedInUserId();
hasDepartment('Accounts');

// Initialize objects
$db = new Database();
$user = new User();
$transactionModel = new Transaction();

// Get current user information
$currentUser = $user->getUserById($userId);

// Process approve/reject actions
$success_msg = $error_msg = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $id = intval($_GET['id']);
    
    if ($id > 0) {
        $transaction = $transactionModel->getTransactionById($id);
        
        if ($transaction && $transaction['leasing_post_status'] === 'approved' && $transaction['approval_status'] === 'pending') {
            if ($action === 'approve') {
                // Approve the transaction
                $result = $transactionModel->approveTransaction($id, $_SESSION['user_id'], $_SESSION['user_name']);
                
                if ($result) {
                    $success_msg = "Transaction approved successfully!";
                } else {
                    $error_msg = "Error approving transaction. Please try again.";
                }
            } elseif ($action === 'reject') {
                // Reject the transaction
                $result = $transactionModel->rejectTransaction($id, 'account', $_SESSION['user_id'], $_SESSION['user_name']);
                
                if ($result) {
                    $success_msg = "Transaction rejected successfully.";
                } else {
                    $error_msg = "Error rejecting transaction. Please try again.";
                }
            }
        } else {
            $error_msg = "Invalid transaction or transaction is not in a state that can be approved/rejected.";
        }
    }
}

// Get pending transactions for account approval
$pendingTransactions = $transactionModel->getPendingTransactionsForAccountApproval();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Posts - Income ERP System</title>
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
                <a href="remittance.php" class="sidebar-menu-item">
                    <i class="fas fa-money-bill-wave"></i> Remittances
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('Wealth Creation')): ?>
                <a href="post_collection.php" class="sidebar-menu-item">
                    <i class="fas fa-receipt"></i> Post Collections
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('Accounts')): ?>
                <a href="approve_posts.php" class="sidebar-menu-item active">
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
                    <h4 class="page-title">Approve Posts</h4>
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
                <?php if(!empty($success_msg)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error_msg)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Pending Approvals -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Pending Approvals</h5>
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
                                        <th>Remit ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($pendingTransactions)): ?>
                                        <?php foreach($pendingTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo $transaction['receipt_no']; ?></td>
                                                <td><?php echo formatDate($transaction['date_of_payment']); ?></td>
                                                <td><?php echo $transaction['customer_name']; ?></td>
                                                <td><?php echo formatCurrency($transaction['amount_paid']); ?></td>
                                                <td><?php echo $transaction['income_line']; ?></td>
                                                <td><?php echo $transaction['posting_officer_name']; ?></td>
                                                <td><?php echo $transaction['remit_id']; ?></td>
                                                <td>
                                                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="approve_posts.php?action=approve&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this transaction?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="approve_posts.php?action=reject&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this transaction?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No pending transactions found</td>
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
