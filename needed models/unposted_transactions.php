
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'helpers/session_helper.php';
require_once 'models/User.php';
require_once 'models/UnpostedTransaction.php';
require_once 'models/Remittance.php';
require_once 'models/Account.php';

// Check if user is logged in and has proper role
requireLogin();
$userId = getLoggedInUserId();
hasDepartment('Wealth Creation');

// Initialize objects
$unpostedModel = new UnpostedTransaction();
$remittanceModel = new Remittance();
$accountModel = new Account();
$user = new User;

// Get current user information
$currentUser = $user->getUserById($userId);
// Get Current User department
$userDepartment = $user->getDepartmentByUserIdstring($userId);
// Get income line accounts
$incomeLines = $accountModel->getIncomeLineAccounts();

// Get current time in Lagos timezone
$current_time = new DateTime('now', new DateTimeZone('Africa/Lagos'));
$cutoff_time = new DateTime('18:30', new DateTimeZone('Africa/Lagos'));

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
    $reason = sanitize($_POST['reason']);
    
    // Additional details
    $shop_id = sanitize(isset($_POST['shop_id']) ? $_POST['shop_id'] : '');
    $shop_no = sanitize(isset($_POST['shop_no']) ? $_POST['shop_no'] : '');
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
    
    if (empty($reason)) {
        $errors[] = "Reason for unposted transaction is required";
    }
    
    // Verify remittance exists and belongs to this officer
    $remittance = $remittanceModel->getRemittanceByRemitId($remit_id);
    if (!$remittance || $remittance['remitting_officer_id'] != $_SESSION['user_id']) {
        $errors[] = "Invalid remittance ID";
    }
    
    // If no errors, process the unposted transaction
    if (empty($errors)) {
        $unpostedData = [
            'remit_id' => $remit_id,
            'receipt_no' => $receipt_no,
            'customer_name' => $customer_name,
            'date_of_payment' => $date_of_payment,
            'amount_paid' => $amount_paid,
            'income_line' => $income_line,
            'shop_id' => $shop_id,
            'shop_no' => $shop_no,
            'transaction_desc' => $transaction_desc,
            'posting_officer_id' => $_SESSION['user_id'],
            'posting_officer_name' => $_SESSION['user_name'],
            'reason' => $reason
        ];
        
        if ($unpostedModel->addUnpostedTransaction($unpostedData)) {
            $success_msg = "Unposted transaction recorded successfully!";
        } else {
            $error_msg = "Error recording unposted transaction. Please try again.";
        }
    } else {
        $error_msg = implode('<br>', $errors);
    }
}

// Get unposted transactions for this officer
$unpostedTransactions = $unpostedModel->getUnpostedTransactionsByOfficer($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unposted Transactions - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'include/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'include/header.php'; ?>
            
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
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Record Unposted Transaction</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="remit_id" class="form-label">Remittance ID</label>
                                        <input type="text" name="remit_id" id="remit_id" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="receipt_no" class="form-label">Receipt Number</label>
                                        <input type="text" name="receipt_no" id="receipt_no" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="date_of_payment" class="form-label">Date of Payment</label>
                                        <input type="date" name="date_of_payment" id="date_of_payment" class="form-control datepicker" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount_paid" class="form-label">Amount Paid</label>
                                        <input type="number" name="amount_paid" id="amount_paid" class="form-control" step="0.01" min="0" required>
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
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shop_id" class="form-label">Shop ID (if applicable)</label>
                                        <input type="text" name="shop_id" id="shop_id" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shop_no" class="form-label">Shop Number (if applicable)</label>
                                        <input type="text" name="shop_no" id="shop_no" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="transaction_desc" class="form-label">Transaction Description</label>
                                <textarea name="transaction_desc" id="transaction_desc" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason" class="form-label">Reason for Unposted Transaction</label>
                                <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Record Unposted Transaction
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Pending Unposted Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Remit ID</th>
                                        <th>Receipt No</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Income Line</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($unpostedTransactions)): ?>
                                        <?php foreach($unpostedTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo $transaction['remit_id']; ?></td>
                                                <td><?php echo $transaction['receipt_no']; ?></td>
                                                <td><?php echo formatDate($transaction['date_of_payment']); ?></td>
                                                <td><?php echo $transaction['customer_name']; ?></td>
                                                <td><?php echo formatCurrency($transaction['amount_paid']); ?></td>
                                                <td><?php echo $transaction['income_line']; ?></td>
                                                <td><?php echo $transaction['reason']; ?></td>
                                                <td>
                                                    <?php if($transaction['payment_status'] == 'pending'): ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Reposted</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No unposted transactions found</td>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
