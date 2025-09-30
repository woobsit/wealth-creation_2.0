<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Remittance.php'; 
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

$userId = getLoggedInUserId();
// Initialize objects
$db = new Database();
$user = new User();
$transaction = new Transaction();
$remittance = new Remittance();
$account = new Account();

// Get transaction statistics
$stats = $transaction->getTransactionStats();

// Get current user information
$currentUser = $user->getUserById($userId);

// Get today's remittances
$todayRemittances = $remittance->getRemittancesByDate(date('Y-m-d'));

// Get income line accounts
$incomeLines = $account->getIncomeLineAccounts();

// Get Current User department
$userDepartment = $user->getDepartmentByUserIdstring($userId);

// Get pending transactions based on user role
$pendingTransactions = [];

if (hasDepartment('Wealth Creation')) {
    // Get remittances for this officer
    $myRemittances = $remittance->getRemittancesByOfficer($userId);
} elseif (hasDepartment('Accounts')) {
    // Get pending transactions for account approval
    $pendingTransactions = $transaction->getPendingTransactionsForAccountApproval();
} elseif (hasDepartment('Audit/Inspections')) {
    // Get pending transactions for audit verification
    $pendingTransactions = $transaction->getPendingTransactionsForAuditVerification();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Basic styling */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.5;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .navbar {
            background-color: #1eaedb;
            padding: 10px 0;
            color: white;
        }
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .navbar-logo {
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        .navbar-logo svg {
            margin-right: 8px;
        }
        .navbar-links {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .navbar-links li {
            margin-left: 20px;
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
        }
        .dashboard-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 24px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (min-width: 640px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 16px;
        }
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .stat-card-title {
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }
        .stat-card-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .stat-card-description {
            font-size: 12px;
            color: #666;
        }
        .action-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 16px;
        }
        .action-card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 16px;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .action-button {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            text-decoration: none;
            color: #333;
            transition: background-color 0.2s;
        }
        .action-button:hover {
            background-color: #f5f5f5;
        }
        .action-button svg {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('include/sidebar.php'); ?>  
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include('include/header.php'); ?> 
            <!-- Content Body -->
            <div class="content-body">
            <h3 class="text-start"><b>Hello <?php echo $currentUser['full_name']; ?> 👋🏼</b> </h3>
            <p>Welcome to your dashboard! Your dashboard is peculiar to your <?php echo $userDepartment; ?> Department. Please always logout of your account for security reasons. </p>
            <p><?php include ('countdown_script.php'); ?></p>

                <?php if(hasDepartment('Accounts') || hasDepartment('Audit/Inspections') || hasDepartment('IT/E-Business')){ 
                    include('include/dashboard-overview.php');
                    }
                    else {
                        include('include/dashboard-leasing.php');
                    }
                 ?>

                <?php if(hasDepartment('Accounts') || hasDepartment('Audit/Inspections') || hasDepartment('IT/E-Business')){ ?>
                <div class="dashboard-grid" style="grid-template-columns: 1fr;">
                    <div class="action-card">
                        <div class="action-card-title">Quick Actions</div>
                        <div class="action-buttons">
                            <a href="power_consumption.html" class="action-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16.2 7.8l-2 6.3-6.4 2.1 2-6.3z"></path></svg>
                                Power Consumption Management
                            </a>
                            <a href="transactions.php" class="action-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                                Transactions
                            </a>
                            <a href="income_summary.html" class="action-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="6" x2="12" y2="12"></line><line x1="12" y1="18" x2="12" y2="18"></line></svg>
                                Revenue Reports
                            </a>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <?php if(hasDepartment('Accounts') || hasDepartment('Audit/Inspections') || hasDepartment('IT/E-Business')){ ?>
                <div class="dashboard-grid" style="grid-template-columns: 1fr;">
                    <div class="action-card">
                        <div class="action-card-title">Quick Actions</div>
                        <div class="action-buttons">
                            <a href="power_consumption.html" class="action-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16.2 7.8l-2 6.3-6.4 2.1 2-6.3z"></path></svg>
                                MPR Dashboard
                            </a>
                            <a href="transactions.php" class="action-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                                Monthly Collection Report
                            </a>
                            <a href="income_summary.html" class="action-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="6" x2="12" y2="12"></line><line x1="12" y1="18" x2="12" y2="18"></line></svg>
                                Revenue Reports
                            </a>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <!-- chart overview -->  
                 
                
                <!-- /Chart overview -->
                
                <!-- Recent Activity Section -->
                <?php if(hasDepartment('IT/E-Business') || hasDepartment('Accounts')): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Today's Remittances</h5>
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
                                        <th>Remitting Officer</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($todayRemittances)): ?>
                                        <?php foreach($todayRemittances as $remit): ?>
                                            <tr>
                                                <td><?php echo $remit['remit_id']; ?></td>
                                                <td><?php echo formatDate($remit['date']); ?></td>
                                                <td><?php echo formatCurrency($remit['amount_paid']); ?></td>
                                                <td><?php echo $remit['no_of_receipts']; ?></td>
                                                <td><?php echo $remit['category']; ?></td>
                                                <td><?php echo $remit['remitting_officer_name']; ?></td>
                                                <td>
                                                    <a href="view_remittance.php?id=<?php echo $remit['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No remittances recorded today</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Pending Approvals Section -->
                <?php if(hasDepartment('Accounts') || hasDepartment('Audit/Inspections') || hasDepartment('IT/E-Business')): ?>
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
                                        <th>Status</th>
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
                                                <td>
                                                    <?php if(hasDepartment('Accounts')): ?>
                                                        <span class="badge badge-warning">Awaiting Approval</span>
                                                    <?php elseif(hasDepartment('Audit/Inspections')): ?>
                                                        <span class="badge badge-info">Awaiting Verification</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
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
                <?php endif; ?>
                
                <!-- Leasing Officer Remittances -->
                <?php if(hasDepartment('Wealth Creation')): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">My Remittances</h5>
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
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($myRemittances)): ?>
                                        <?php foreach($myRemittances as $remit): ?>
                                            <?php 
                                                $isPosted = $remittance->isRemittanceFullyPosted($remit['remit_id']);
                                            ?>
                                            <tr>
                                                <td><?php echo $remit['remit_id']; ?></td>
                                                <td><?php echo formatDate($remit['date']); ?></td>
                                                <td><?php echo formatCurrency($remit['amount_paid']); ?></td>
                                                <td><?php echo $remit['no_of_receipts']; ?></td>
                                                <td>
                                                    <?php if($isPosted): ?>
                                                        <span class="badge badge-success">Fully Posted</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending Posts</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="post_collection.php?remit_id=<?php echo $remit['remit_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-receipt"></i> Post
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No remittances found</td>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
