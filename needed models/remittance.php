<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Remittance.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
$userId = getLoggedInUserId();
function requireAnyDepartment($departments = []) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $userId = getLoggedInUserId();
    
    require_once 'config/Database.php'; // make sure Database is loaded if not already
    $db = new Database();

    // Query the department directly
    $db->query('SELECT department FROM staffs WHERE user_id = :userId LIMIT 1');
    $db->bind(':userId', $userId);
    $result = $db->single();

    $department = $result ? $result['department'] : null;

    if (!in_array($department, $departments)) {
        redirect('unauthorized.php');
    }
}

requireAnyDepartment(['IT/E-Business', 'Accounts']);

// Initialize objects
$db = new Database();
$user = new User();
$remittanceModel = new Remittance();

// Get all leasing officers
$leasingOfficers = $user->getUsersByDepartment('Wealth Creation');
// Get current user information
$currentUser = $user->getUserById($userId);
// Process form submission
$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $remitting_officer_id = sanitize($_POST['remitting_officer_id']);
    $date = sanitize($_POST['date']);
    $amount_paid = floatval(sanitize($_POST['amount_paid']));
    $no_of_receipts = intval(sanitize($_POST['no_of_receipts']));
    $category = sanitize($_POST['category']);
    
    // Basic validation
    $errors = [];
    
    if (empty($remitting_officer_id)) {
        $errors[] = "Remitting officer is required";
    }
    
    if (empty($date)) {
        $errors[] = "Date is required";
    }
    
    if ($amount_paid <= 0) {
        $errors[] = "Amount must be greater than zero";
    }
    
    if ($no_of_receipts <= 0) {
        $errors[] = "Number of receipts must be greater than zero";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    // If no errors, process the remittance
    if (empty($errors)) {
        // Get officer details
        $officer = $user->getUserById($remitting_officer_id);
        
        if ($officer) {
            // Generate a remittance ID
            $remit_id = $remittanceModel->generateRemitId();
            
            // Prepare data for insertion
            $remittanceData = [
                'remit_id' => $remit_id,
                'date' => $date,
                'amount_paid' => $amount_paid,
                'no_of_receipts' => $no_of_receipts,
                'category' => $category,
                'remitting_officer_id' => $remitting_officer_id,
                'remitting_officer_name' => $officer['full_name'],
                'posting_officer_id' => $_SESSION['user_id'],
                'posting_officer_name' => $_SESSION['user_name']
            ];
            
            // Add the remittance
            $result = $remittanceModel->addRemittance($remittanceData);
            
            if ($result) {
                $success_msg = "Remittance added successfully with ID: " . $remit_id;
            } else {
                $error_msg = "Error adding remittance. Please try again.";
            }
        } else {
            $error_msg = "Invalid remitting officer selected.";
        }
    } else {
        $error_msg = implode('<br>', $errors);
    }
}

// Get all remittances for display
$remittances = $remittanceModel->getRemittances();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Remittance Dashboard - ERP </title>
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
                    <i class="fas fa-chart-line"></i> W C ERP
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
                
                <?php if(hasDepartment('leasing_officer')): ?>
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
                    <h4 class="page-title">Cash Remittance</h4>
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
                
                <!-- New Remittance Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Account Remittance Dashboard</h5>
                        <p><?php include ('countdown_script.php'); ?></p>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation">
                            <div class="form-group">
                                <label for="remitting_officer_id" class="form-label">Remitting Officer</label>
                                <select name="remitting_officer_id" id="remitting_officer_id" class="form-select" required>
                                    <option value="">-- Select Officer --</option>
                                    <?php foreach($leasingOfficers as $officer): ?>
                                        <option value="<?php echo $officer['id']; ?>"><?php echo $officer['full_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date" class="form-label">Date</label>
                                <input type="text" name="date" id="date" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly/>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount_paid" class="form-label">Amount Paid</label>
                                <input type="number" name="amount_paid" id="amount_paid" class="form-control" step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="no_of_receipts" class="form-label">Number of Receipts</label>
                                <input type="number" name="no_of_receipts" id="no_of_receipts" class="form-control" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Shop Rent">Rent Collections</option>
                                    <option value="Service Charge">Service Charge</option>
                                    <option value="Mixed">Other Collections</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Remittance
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Remittances List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Recent Remittances</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="remittancesTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Remit ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>No. of Receipts</th>
                                        <th>Category</th>
                                        <th>Remitting Officer</th>
                                        <th>Posted By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
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
    <script>
        $('#remittancesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/get_remittances.php',
                type: 'POST',
                dataSrc: function(json) {
                    console.log('Response from server:', json); // See everything returned
                    return json.data;
                },
                error: function(xhr, error, thrown) {
                    console.error('AJAX error:', xhr.responseText); // In case your PHP throws an error
                }
            },
            pageLength: 10,
            columns: [
                { data: 'remit_id' },
                { data: 'date' },
                { data: 'amount_paid' },
                { data: 'no_of_receipts' },
                { data: 'category' },
                { data: 'remitting_officer_name' },
                { data: 'posting_officer_name' },
                { data: 'status' },
                { data: 'actions', orderable: false }
            ],
            order: [[1, 'desc']],
            responsive: true,
            language: {
                processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>'
            }
        });

        // $(document).ready(function() {
        //     $('#remittancesTable').DataTable({
        //         processing: true,
        //         serverSide: true,
        //         ajax: {
        //             url: 'api/get_remittances.php',
        //             type: 'POST'
        //         },
        //         pageLength: 10,
        //         // columns: [
        //         //     { data: 0 }, // Remit ID
        //         //     { data: 1 }, // Date
        //         //     { data: 2 }, // Amount
        //         //     { data: 3 }, // No. of Receipts
        //         //     { data: 4 }, // Category
        //         //     { data: 5 }, // Remitting Officer
        //         //     { data: 6 }, // Posted By
        //         //     { data: 7 }, // Status
        //         //     { data: 8, orderable: false } // Actions
        //         // ]
        //         columns: [
        //             { data: 'remit_id' },          // Remit ID
        //             { data: 'date' },              // Date
        //             { data: 'amount_paid' },       // Amount
        //             { data: 'no_of_receipts' },    // No. of Receipts
        //             { data: 'category' },          // Category
        //             { data: 'remitting_officer_name' }, // Remitting Officer
        //             { data: 'posting_officer_name' },   // Posted By
        //             { data: 'status' },            // Status
        //             { data: 'actions', orderable: false } // Actions
        //         ],
        //         order: [[1, 'desc']], // Order by date descending
        //         responsive: true,
        //         language: {
        //             processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>'
        //         }
        //     });
        // });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
