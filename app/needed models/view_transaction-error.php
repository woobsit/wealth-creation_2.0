<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Initialize objects
$db = new Database();
$user = new User();
$transactionModel = new Transaction();
$accountModel = new Account();

// Get transaction ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If ID is not provided, redirect to transactions page
if ($id <= 0) {
    redirect('transactions.php');
}

// Get transaction details
$transaction = $transactionModel->getTransactionById($id);

// If transaction not found, redirect
if (!$transaction) {
    redirect('transactions.php');
}

// Get account details
$debitAccount = $accountModel->getAccountByCode($transaction['debit_account']);
$creditAccount = $accountModel->getAccountByCode($transaction['credit_account']);

// Process actions for appropriate user roles
$success_msg = $error_msg = '';

if (isset($_GET['action'])) {
    $action = sanitize($_GET['action']);
    
    // Handle leasing officer approval
    if (hasDepartment('Accounts') && $action === 'approve_leasing' && $transaction['leasing_post_status'] === 'pending') {
        $result = $transactionModel->approveLeasingPost($id, $_SESSION['user_id'], $_SESSION['user_name']);
        
        if ($result) {
            $success_msg = "Leasing post approved successfully!";
            // Reload the transaction to get updated data
            $transaction = $transactionModel->getTransactionById($id);
        } else {
            $error_msg = "Error approving leasing post. Please try again.";
        }
    }
    
    // Handle account officer approval
    if (hasDepartment('Accounts') && $action === 'approve_account' && $transaction['leasing_post_status'] === 'approved' && $transaction['approval_status'] === 'pending') {
        $result = $transactionModel->approveTransaction($id, $_SESSION['user_id'], $_SESSION['user_name']);
        
        if ($result) {
            $success_msg = "Transaction approved successfully!";
            // Reload the transaction to get updated data
            $transaction = $transactionModel->getTransactionById($id);
        } else {
            $error_msg = "Error approving transaction. Please try again.";
        }
    }
    
    // Handle auditor verification
    if (hasDepartment('Audit/Inspections') && $action === 'verify' && $transaction['approval_status'] === 'approved' && $transaction['verification_status'] === 'pending') {
        $result = $transactionModel->verifyTransaction($id, $_SESSION['user_id'], $_SESSION['user_name']);
        
        if ($result) {
            $success_msg = "Transaction verified successfully!";
            // Reload the transaction to get updated data
            $transaction = $transactionModel->getTransactionById($id);
        } else {
            $error_msg = "Error verifying transaction. Please try again.";
        }
    }
    
    // Handle rejection at any stage
    if ($action === 'reject' && isset($_GET['stage'])) {
        $stage = sanitize($_GET['stage']);
        
        // Validate appropriate role for the stage
        $canReject = false;
        
        if ($stage === 'leasing' && hasDepartment('Accounts') && $transaction['leasing_post_status'] === 'pending') {
            $canReject = true;
        } elseif ($stage === 'account' && hasDepartment('Accounts') && $transaction['leasing_post_status'] === 'approved' && $transaction['approval_status'] === 'pending') {
            $canReject = true;
        } elseif ($stage === 'audit' && hasDepartment('Audit/Inspections') && $transaction['approval_status'] === 'approved' && $transaction['verification_status'] === 'pending') {
            $canReject = true;
        }
        
        if ($canReject) {
            $result = $transactionModel->rejectTransaction($id, $stage, $_SESSION['user_id'], $_SESSION['user_name']);
            
            if ($result) {
                $success_msg = "Transaction rejected successfully.";
                // Reload the transaction to get updated data
                $transaction = $transactionModel->getTransactionById($id);
            } else {
                $error_msg = "Error rejecting transaction. Please try again.";
            }
        } else {
            $error_msg = "You don't have permission to reject this transaction at its current stage.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transaction - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                
                <a href="transactions.php" class="sidebar-menu-item active">
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
                    <h4 class="page-title">View Transaction</h4>
                </div>
                
                <div class="header-right">
                    <div class="user-dropdown">
                        <button class="user-dropdown-toggle">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="name"><?php echo $_SESSION['user_name']; ?></span>
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
                
                <!-- Transaction Details -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Transaction Details</h5>
                        <div>
                            <a href="print_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                            <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Basic Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Receipt No:</th>
                                        <td><?php echo $transaction['receipt_no']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date of Payment:</th>
                                        <td><?php echo formatDate($transaction['date_of_payment']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td><?php echo formatCurrency($transaction['amount_paid']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Income Line:</th>
                                        <td><?php echo $transaction['income_line']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Type:</th>
                                        <td><?php echo ucfirst($transaction['payment_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Remittance ID:</th>
                                        <td>
                                            <?php if(hasDepartment('IT/E-Business') || hasDepartment('Accounts')): ?>
                                                <a href="view_remittance.php?id=<?php echo $transaction['remit_id']; ?>">
                                                    <?php echo $transaction['remit_id']; ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo $transaction['remit_id']; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Customer Name:</th>
                                        <td><?php echo isset($transaction['customer_name']) ? $transaction['customer_name'] : 'N/A'; ?></td>
                                    </tr>
                                    <?php if(!empty($transaction['shop_id']) || !empty($transaction['shop_no'])): ?>
                                    <tr>
                                        <th>Shop Information:</th>
                                        <td>
                                            <?php if(!empty($transaction['shop_id'])): ?>
                                                ID: <?php echo $transaction['shop_id']; ?><br>
                                            <?php endif; ?>
                                            <?php if(!empty($transaction['shop_no'])): ?>
                                                No: <?php echo $transaction['shop_no']; ?><br>
                                            <?php endif; ?>
                                            <?php if(!empty($transaction['shop_size'])): ?>
                                                Size: <?php echo $transaction['shop_size']; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if(!empty($transaction['start_date']) || !empty($transaction['end_date'])): ?>
                                    <tr>
                                        <th>Period:</th>
                                        <td>
                                            <?php if(!empty($transaction['start_date'])): ?>
                                                From: <?php echo formatDate($transaction['start_date']); ?><br>
                                            <?php endif; ?>
                                            <?php if(!empty($transaction['end_date'])): ?>
                                                To: <?php echo formatDate($transaction['end_date']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if(!empty($transaction['no_of_tickets'])): ?>
                                    <tr>
                                        <th>Number of Tickets:</th>
                                        <td><?php echo $transaction['no_of_tickets']; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if(!empty($transaction['plate_no'])): ?>
                                    <tr>
                                        <th>Plate Number:</th>
                                        <td><?php echo $transaction['plate_no']; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if(!empty($transaction['transaction_desc'])): ?>
                                    <tr>
                                        <th>Description:</th>
                                        <td><?php echo $transaction['transaction_desc']; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6>Accounting Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Debit Account:</th>
                                        <td>
                                            <?php echo $transaction['debit_account']; ?> - 
                                            <?php echo isset($debitAccount['acct_desc']) ? $debitAccount['acct_desc'] : ''; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Credit Account:</th>
                                        <td>
                                            <?php echo $transaction['credit_account']; ?> - 
                                            <?php echo isset($creditAccount['acct_desc']) ? $creditAccount['acct_desc'] : ''; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Processing Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Posted By:</th>
                                        <td><?php echo $transaction['posting_officer_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Posted On:</th>
                                        <td><?php echo date('d-M-Y H:i', strtotime($transaction['posting_time'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Leasing Approved By:</th>
                                        <td>
                                            <?php if($transaction['leasing_post_status'] == 'approved'): ?>
                                                <?php echo $transaction['leasing_post_approving_officer_name']; ?><br>
                                                <small><?php echo date('d-M-Y H:i', strtotime($transaction['leasing_post_approval_time'])); ?></small>
                                            <?php elseif($transaction['leasing_post_status'] == 'rejected'): ?>
                                                <span class="text-danger">Rejected by <?php echo $transaction['leasing_post_approving_officer_name']; ?></span>
                                            <?php else: ?>
                                                <span class="text-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Account Approved By:</th>
                                        <td>
                                            <?php if($transaction['approval_status'] == 'approved'): ?>
                                                <?php echo $transaction['approving_acct_officer_name']; ?><br>
                                                <small><?php echo date('d-M-Y H:i', strtotime($transaction['approval_time'])); ?></small>
                                            <?php elseif($transaction['approval_status'] == 'rejected'): ?>
                                                <span class="text-danger">Rejected by <?php echo $transaction['approving_acct_officer_name']; ?></span>
                                            <?php else: ?>
                                                <span class="text-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Verified By:</th>
                                        <td>
                                            <?php if($transaction['verification_status'] == 'verified'): ?>
                                                <?php echo $transaction['verifying_auditor_name']; ?><br>
                                                <small><?php echo date('d-M-Y H:i', strtotime($transaction['verification_time'])); ?></small>
                                            <?php elseif($transaction['verification_status'] == 'rejected'): ?>
                                                <span class="text-danger">Rejected by <?php echo $transaction['verifying_auditor_name']; ?></span>
                                            <?php else: ?>
                                                <span class="text-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if(hasDepartment('Accounts') && $transaction['leasing_post_status'] === 'pending'): ?>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>&action=approve_leasing" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this leasing post?')">
                                <i class="fas fa-check"></i> Approve Leasing Post
                            </a>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>&action=reject&stage=leasing" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this leasing post?')">
                                <i class="fas fa-times"></i> Reject Leasing Post
                            </a>
                        <?php endif; ?>
                        
                        <?php if(hasDepartment('Accounts') && $transaction['leasing_post_status'] === 'approved' && $transaction['approval_status'] === 'pending'): ?>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>&action=approve_account" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this transaction?')">
                                <i class="fas fa-check"></i> Approve Transaction
                            </a>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>&action=reject&stage=account" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this transaction?')">
                                <i class="fas fa-times"></i> Reject Transaction
                            </a>
                        <?php endif; ?>
                        
                        <?php if(hasDepartment('Audit/Inspections') && $transaction['approval_status'] === 'approved' && $transaction['verification_status'] === 'pending'): ?>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>&action=verify" class="btn btn-success" onclick="return confirm('Are you sure you want to verify this transaction?')">
                                <i class="fas fa-check"></i> Verify Transaction
                            </a>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>&action=reject&stage=audit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this transaction?')">
                                <i class="fas fa-times"></i> Reject Transaction
                            </a>
                        <?php endif; ?>
                        
                        <?php if($transaction['verification_status'] === 'verified'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> This transaction has been fully verified and approved.
                            </div>
                        <?php elseif($transaction['leasing_post_status'] === 'rejected' || $transaction['approval_status'] === 'rejected' || $transaction['verification_status'] === 'rejected'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> This transaction has been rejected.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
