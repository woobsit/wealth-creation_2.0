<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Remittance.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
$userId = getLoggedInUserId();
hasDepartment('Wealth Creation');

// Initialize objects
$db = new Database();
$user = new User();
$remittanceModel = new Remittance();
$transactionModel = new Transaction();
$accountModel = new Account();

// Get Current User department
$userDepartment = $user->getDepartmentByUserIdstring($userId);

// Get remit_id from URL if provided
$remit_id = isset($_GET['remit_id']) ? sanitize($_GET['remit_id']) : '';
$remittance = null;

if (!empty($remit_id)) {
    $remittance = $remittanceModel->getRemittanceByRemitId($remit_id);
    
    // If remittance not found or not belonging to the current user, redirect
    if (!$remittance || $remittance['remitting_officer_id'] != $_SESSION['user_id']) {
        redirect('index.php');
    }
}
// Get current user information
$currentUser = $user->getUserById($userId);
// Get all remittances for this officer
$myRemittances = $remittanceModel->getRemittancesByOfficer($_SESSION['user_id']);

// Get income line accounts
$incomeLines = $accountModel->getIncomeLineAccounts();

// Initialize variables
$success_msg = $error_msg = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $remit_id = sanitize($_POST['remit_id']);
    $receipt_no = sanitize($_POST['receipt_no']);
    $customer_name = sanitize(isset($_POST['customer_name']) ? $_POST['customer_name'] : '');
    $date_of_payment = sanitize($_POST['date_of_payment']);
    $amount_paid = floatval(sanitize($_POST['amount_paid']));
    $income_line = sanitize($_POST['income_line']);
    $payment_type = sanitize($_POST['payment_type']);

    // Additional details based on income line
    $shop_id = sanitize(isset($_POST['shop_id']) ? $_POST['shop_id'] : '');
    $shop_no = sanitize(isset($_POST['shop_no']) ? $_POST['shop_no'] : '');
    $shop_size = sanitize(isset($_POST['shop_size']) ? $_POST['shop_size'] : '');
    $start_date = sanitize(isset($_POST['start_date']) ? $_POST['start_date'] : '');
    $end_date = sanitize(isset($_POST['end_date']) ? $_POST['end_date'] : '');
    $no_of_tickets = intval(sanitize(isset($_POST['no_of_tickets']) ? $_POST['no_of_tickets'] : 0));
    $plate_no = sanitize(isset($_POST['plate_no']) ? $_POST['plate_no'] : '');
    $transaction_desc = sanitize(isset($_POST['transaction_desc']) ? $_POST['transaction_desc'] : '');

    
    // Validation
    $errors = [];
    
    if (empty($remit_id)) {
        $errors[] = "Remittance ID is required";
    }
    
    if (empty($receipt_no)) {
        $errors[] = "Receipt number is required";
    }
    
    if (empty($date_of_payment)) {
        $errors[] = "Date of payment is required";
    }
    
    if ($amount_paid <= 0) {
        $errors[] = "Amount must be greater than zero";
    }
    
    if (empty($income_line)) {
        $errors[] = "Income line is required";
    }
    
    // Verify remittance exists and belongs to this officer
    $remittance = $remittanceModel->getRemittanceByRemitId($remit_id);
    if (!$remittance || $remittance['remitting_officer_id'] != $_SESSION['user_id']) {
        $errors[] = "Invalid remittance ID";
    }
    
    // If no errors, process the transaction
    if (empty($errors)) {
        // Get the account codes for debit and credit
        $debit_account = 'TILL-001'; // Account Till (default)
        
        // Get the account code for the selected income line
        $credit_account = '';
        foreach ($incomeLines as $line) {
            if ($line['acct_alias'] == $income_line) {
                $credit_account = $line['acct_code'];
                break;
            }
        }
        
        if (empty($credit_account)) {
            $errors[] = "Invalid income line selected";
        } else {
            // Prepare transaction data
            $transactionData = [
                'remit_id' => $remit_id,
                'receipt_no' => $receipt_no,
                'customer_name' => $customer_name,
                'date_of_payment' => $date_of_payment,
                'amount_paid' => $amount_paid,
                'income_line' => $income_line,
                'payment_type' => $payment_type,
                'debit_account' => $debit_account,
                'credit_account' => $credit_account,
                'posting_officer_id' => $_SESSION['user_id'],
                'posting_officer_name' => $_SESSION['user_name'],
                'shop_id' => $shop_id,
                'shop_no' => $shop_no,
                'shop_size' => $shop_size,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'no_of_tickets' => $no_of_tickets,
                'plate_no' => $plate_no,
                'transaction_desc' => $transaction_desc
            ];
            
            // Add the transaction
            $result = $transactionModel->addTransaction($transactionData);
            
            if ($result) {
                $success_msg = "Transaction posted successfully!";
                
                // If remittance is fully posted, show additional message
                if ($remittanceModel->isRemittanceFullyPosted($remit_id)) {
                    $success_msg .= " All receipts for this remittance have been posted.";
                }
            } else {
                $error_msg = "Error posting transaction. Please try again.";
            }
        }
    } else {
        $error_msg = implode('<br>', $errors);
    }
}

// Get transactions for the current remittance (if selected)
$transactions = [];
if ($remittance) {
    $transactions = $transactionModel->getTransactionsByRemitId($remittance['remit_id']);
    
    // Calculate remaining amount and receipts
    $totalPosted = count($transactions);
    $pendingReceipts = $remittance['no_of_receipts'] - $totalPosted;
    
    $postedAmount = 0;
    foreach ($transactions as $transaction) {
        $postedAmount += $transaction['amount_paid'];
    }
    
    $pendingAmount = $remittance['amount_paid'] - $postedAmount;
}

// Get current time in Lagos timezone
$current_time = new DateTime('now', new DateTimeZone('Africa/Lagos'));
$cutoff_time = new DateTime('18:30', new DateTimeZone('Africa/Lagos'));
$is_after_cutoff = $current_time > $cutoff_time;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Collections - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
                <a href="post_collection.php" class="sidebar-menu-item active">
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
                    <h4 class="page-title">Post Collections</h4>
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
                
                <?php if($is_after_cutoff): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i> Note: It's past 6:30 PM. New transactions must be recorded as unposted.
                        <a href="unposted_transactions.php" class="btn btn-warning btn-sm ml-3">
                            <i class="fas fa-receipt"></i> Record Unposted Transaction
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Select Remittance Section -->
                <?php if(empty($remittance)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Select Remittance</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>Remit ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>No. of Receipts</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($myRemittances)): ?>
                                            <?php foreach($myRemittances as $remit): ?>
                                                <?php 
                                                    $isPosted = $remittanceModel->isRemittanceFullyPosted($remit['remit_id']);
                                                ?>
                                                <tr>
                                                    <td><?php echo $remit['remit_id']; ?></td>
                                                    <td><?php echo formatDate($remit['date']); ?></td>
                                                    <td><?php echo formatCurrency($remit['amount_paid']); ?></td>
                                                    <td><?php echo $remit['no_of_receipts']; ?></td>
                                                    <td><?php echo $remit['category']; ?></td>
                                                    <td>
                                                        <?php if($isPosted): ?>
                                                            <span class="badge badge-success">Fully Posted</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Pending Posts</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if(!$isPosted): ?>
                                                            <a href="post_collection.php?remit_id=<?php echo $remit['remit_id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-receipt"></i> Post
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="post_collection.php?remit_id=<?php echo $remit['remit_id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No remittances found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Remittance Details -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Remittance Details</h5>
                            <a href="post_collection.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
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
                                            <th>Remaining Amount:</th>
                                            <td><?php echo formatCurrency($pendingAmount); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Remaining Receipts:</th>
                                            <td><?php echo $pendingReceipts; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Category:</th>
                                            <td><?php echo $remittance['category']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td>
                                                <?php if($pendingReceipts <= 0): ?>
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
                    
                    <!-- Post Transaction Form -->
                    <?php if($pendingReceipts > 0 && !$is_after_cutoff): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Post Transaction</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation">
                                    <input type="hidden" name="remit_id" value="<?php echo $remittance['remit_id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="receipt_no" class="form-label">Receipt Number</label>
                                                <input type="text" name="receipt_no" id="receipt_no" class="form-control" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_of_payment" class="form-label">Date of Payment</label>
                                                <input type="date" name="date_of_payment" id="date_of_payment" class="form-control datepicker" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="amount_paid" class="form-label">Amount Paid</label>
                                                <input type="number" name="amount_paid" id="amount_paid" class="form-control" step="0.01" min="0" max="<?php echo $pendingAmount; ?>" required>
                                                <small class="form-text text-muted">Maximum: <?php echo formatCurrency($pendingAmount); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="payment_type" class="form-label">Payment Type</label>
                                                <select name="payment_type" id="payment_type" class="form-select" required>
                                                    <option value="cash">Cash</option>
                                                    <option value="transfer">Bank Transfer</option>
                                                    <option value="cheque">Cheque</option>
                                                    <option value="pos">POS</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="income_line" class="form-label">Income Line</label>
                                                <select name="income_line" id="income_line" class="form-select" required>
                                                    <option value="">-- Select Income Line --</option>
                                                    <?php foreach($incomeLines as $line): ?>
                                                        <option value="<?php echo $line['acct_alias']; ?>"><?php echo $line['acct_desc']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="customer_name" class="form-label">Customer Name</label>
                                                <input type="text" name="customer_name" id="customer_name" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Dynamic fields based on income line -->
                                    <div id="shopRentFields" class="dynamic-fields" style="display: none;">
                                        <h6 class="mt-3 mb-2">Shop Rent Details</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="shop_id" class="form-label">Shop ID</label>
                                                    <input type="text" name="shop_id" id="shop_id" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="shop_no" class="form-label">Shop Number</label>
                                                    <input type="text" name="shop_no" id="shop_no" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="shop_size" class="form-label">Shop Size</label>
                                                    <input type="text" name="shop_size" id="shop_size" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_date" class="form-label">Start Date</label>
                                                    <input type="date" name="start_date" id="start_date" class="form-control datepicker">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_date" class="form-label">End Date</label>
                                                    <input type="date" name="end_date" id="end_date" class="form-control datepicker">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="serviceChargeFields" class="dynamic-fields" style="display: none;">
                                        <h6 class="mt-3 mb-2">Service Charge Details</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="shop_id" class="form-label">Shop ID</label>
                                                    <input type="text" name="shop_id" id="shop_id_sc" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="shop_no" class="form-label">Shop Number</label>
                                                    <input type="text" name="shop_no" id="shop_no_sc" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="start_date" class="form-label">Month/Year</label>
                                                    <input type="month" name="start_date" id="start_date_sc" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="ticketFields" class="dynamic-fields" style="display: none;">
                                        <h6 class="mt-3 mb-2">Ticket Details</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="no_of_tickets" class="form-label">Number of Tickets</label>
                                                    <input type="number" name="no_of_tickets" id="no_of_tickets" class="form-control" min="1">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="plate_no" class="form-label">Plate Number (if applicable)</label>
                                                    <input type="text" name="plate_no" id="plate_no" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-3">
                                        <label for="transaction_desc" class="form-label">Transaction Description</label>
                                        <textarea name="transaction_desc" id="transaction_desc" class="form-control" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="form-group mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Post Transaction
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php elseif($pendingReceipts > 0): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Post Transaction</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> It's past 6:30 PM. Please use the unposted transactions page to record any remaining transactions.
                                    <a href="unposted_transactions.php" class="btn btn-primary btn-sm ml-3">
                                        <i class="fas fa-receipt"></i> Go to Unposted Transactions
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                                                    <td>
                                                        <?php if($transaction['leasing_post_status'] == 'pending'): ?>
                                                            <span class="badge badge-warning">Awaiting Approval</span>
                                                        <?php elseif($transaction['leasing_post_status'] == 'approved'): ?>
                                                            <span class="badge badge-success">Approved</span>
                                                        <?php elseif($transaction['leasing_post_status'] == 'rejected'): ?>
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
                                                <td colspan="7" class="text-center">No transactions posted yet</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // Handle dynamic fields based on income line selection
        document.getElementById('income_line').addEventListener('change', function() {
            // Hide all dynamic field sections
            document.querySelectorAll('.dynamic-fields').forEach(function(element) {
                element.style.display = 'none';
            });
            
            // Show relevant fields based on selection
            const selectedValue = this.value;
            
            if (selectedValue === 'Shop Rent') {
                document.getElementById('shopRentFields').style.display = 'block';
            } else if (selectedValue === 'Service Charge') {
                document.getElementById('serviceChargeFields').style.display = 'block';
            } else if (['Car Loading', 'Car Park', 'Hawkers', 'WheelBarrow', 'Abattoir', 'Daily Trade', 'POS Ticket'].includes(selectedValue)) {
                document.getElementById('ticketFields').style.display = 'block';
            }
        });
    </script>
</body>
</html>
