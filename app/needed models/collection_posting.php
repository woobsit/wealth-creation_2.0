<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Remittance.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

// Initialize objects
$db = new Database();
$user = new User();
$remittanceModel = new Remittance();
$transactionModel = new Transaction();
$accountModel = new Account();

// Get current user information
$currentUser = $user->getUserById($userId);
$userDepartment = $_SESSION['department'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = strtolower($userDepartment);

// Check user role permissions
$isAccountsOfficer = in_array($userRole, ['accounts', 'finance']);
$isLeasingOfficer = in_array($userRole, ['wealth creation', 'leasing']);

if (!$isAccountsOfficer && !$isLeasingOfficer) {
    header('Location: dashboard.php?error=insufficient_permissions');
    exit();
}

// Get income lines
$incomeLines = $accountModel->getIncomeLineAccounts();

// Get remittances for leasing officers
$totalRemitted = 0;
$totalPosted = 0;
$pendingAmount = 0;

if ($isLeasingOfficer) {
    $myRemittances = $remittanceModel->getRemittancesByOfficer($_SESSION['user_id']);
    $today = date('Y-m-d');
    
    foreach($myRemittances as $remit) {
        $remitDate = date('Y-m-d', strtotime($remit['date']));
        if ($remitDate === $today) {
            $totalRemitted += $remit['amount_paid'];
            $postedTransactions = $transactionModel->getTransactionsByRemitId($remit['remit_id']);
            foreach($postedTransactions as $trans) {
                $totalPosted += $trans['amount_paid'];
            }
        }
    }
    $pendingAmount = $totalRemitted - $totalPosted;
}

// Get staff for remitting staff dropdown
$staffQuery = "SELECT user_id, full_name FROM staffs WHERE department = 'Wealth Creation' ORDER BY full_name ASC";
$db->query($staffQuery);
$wealthCreationStaff = $db->resultSet();

$otherStaffQuery = "SELECT id, full_name, department FROM staffs_others ORDER BY full_name ASC";
$db->query($otherStaffQuery);
$otherStaff = $db->resultSet();

// Load form configurations
$formConfigs = include 'includes/collection_form_configs.php';

// Include the header and form components
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Posting - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-white text-xl font-bold flex items-center">
                        <i class="fas fa-money-bill-wave mr-2"></i>
                        Collection Posting
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                        <?= htmlspecialchars($userDepartment) ?>
                    </div>
                    <div class="text-white text-sm">
                        Welcome, <?= htmlspecialchars($userName) ?>
                    </div>
                    <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Collection Posting</h1>
            <p class="text-gray-600 text-lg">Submit payments for various income lines</p>
        </div>

        <?php if ($isLeasingOfficer && $totalRemitted > 0): ?>
        <!-- Remittance Summary for Leasing Officers -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 card-shadow">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Today's Remittance Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-money-bill text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Total Remitted</h3>
                            <p class="text-2xl font-bold text-blue-600">₦<?= number_format($totalRemitted, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Total Posted</h3>
                            <p class="text-2xl font-bold text-green-600">₦<?= number_format($totalPosted, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="bg-orange-100 p-3 rounded-full mr-4">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Pending</h3>
                            <p class="text-2xl font-bold text-orange-600">₦<?= number_format($pendingAmount, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Form Container -->
        <div class="bg-white rounded-xl shadow-lg card-shadow">
            <!-- Income Line Selection -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Select Income Line</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach($incomeLines as $line): ?>
                    <button 
                        onclick="selectIncomeLine('<?= $line['acct_alias'] ?>', '<?= $line['acct_desc'] ?>', '<?= $line['acct_table_name'] ?>')"
                        class="income-line-btn p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 text-left"
                        data-line="<?= $line['acct_alias'] ?>"
                    >
                        <div class="flex items-center">
                            <i class="fas fa-receipt text-gray-600 mr-3"></i>
                            <div>
                                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($line['acct_alias']) ?></h3>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($line['acct_desc']) ?></p>
                            </div>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dynamic Form Section -->
            <div id="paymentForm" class="p-6 hidden">
                <div id="formContent">
                    <!-- Form content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="fixed top-4 right-4 z-50"></div>

    <script>
        let selectedIncomeLine = null;
        let selectedTableName = null;
        const isAccountsOfficer = <?= $isAccountsOfficer ? 'true' : 'false' ?>;
        const isLeasingOfficer = <?= $isLeasingOfficer ? 'true' : 'false' ?>;
        const currentUserId = <?= $userId ?>;
        const currentUserName = '<?= htmlspecialchars($userName) ?>';
        const currentUserDept = '<?= htmlspecialchars($userDepartment) ?>';
        
        // Staff data for Car Loading
        const wealthCreationStaff = <?= json_encode($wealthCreationStaff) ?>;
        const otherStaff = <?= json_encode($otherStaff) ?>;
        
        // Remittance data for leasing officers
        const myRemittances = <?= $isLeasingOfficer ? json_encode($myRemittances) : '[]' ?>;
        const pendingAmount = <?= $pendingAmount ?>;
        
        // Income line configurations
        const incomeLineConfigs = <?= json_encode($formConfigs) ?>;

        function selectIncomeLine(alias, description, tableName) {
            selectedIncomeLine = alias;
            selectedTableName = tableName;
            
            // Update UI
            document.querySelectorAll('.income-line-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'bg-blue-50');
                btn.classList.add('border-gray-200');
            });
            
            event.target.closest('.income-line-btn').classList.add('border-blue-500', 'bg-blue-50');
            event.target.closest('.income-line-btn').classList.remove('border-gray-200');
            
            // Load form
            loadPaymentForm(alias, description);
        }

        function loadPaymentForm(alias, description) {
            const config = incomeLineConfigs[alias] || incomeLineConfigs['General'];
            const formContainer = document.getElementById('formContent');
            
            let formHTML = `
                <h3 class="text-xl font-bold text-gray-900 mb-6">${description} Payment Form</h3>
                <form id="paymentSubmissionForm" onsubmit="submitPayment(event)">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Receipt Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Receipt Number</label>
                            <input type="text" name="receipt_no" required maxlength="7" pattern="^\\d{7}$" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="7-digit receipt number">
                        </div>
                        
                        <!-- Date of Payment -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Payment</label>
                            <input type="date" name="date_of_payment" value="${new Date().toISOString().split('T')[0]}" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
            `;

            // Add specific fields for Car Loading
            if (alias === 'Car Loading') {
                // Transaction Description
                formHTML += `
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Description</label>
                        <input type="text" name="transaction_descr" readonly 
                               value="Car Loading Payment" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none">
                    </div>
                    
                    <!-- Number of Tickets -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Number of Tickets</label>
                        <input type="number" name="no_of_tickets" id="no_of_tickets" required min="1" max="9999"
                               onchange="calculateAmount()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter number of tickets">
                    </div>
                    
                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Remitted (₦)</label>
                        <input type="text" name="amount_paid" id="amount_paid" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none">
                    </div>
                    
                    <!-- Remitting Staff -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Remitter's Name</label>
                        <select name="remitting_staff" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Remitter...</option>
                `;
                
                // Add Wealth Creation staff
                wealthCreationStaff.forEach(staff => {
                    formHTML += `<option value="${staff.user_id}-wc">${staff.full_name}</option>`;
                });
                
                // Add other staff
                otherStaff.forEach(staff => {
                    formHTML += `<option value="${staff.id}-so">${staff.full_name} - ${staff.department}</option>`;
                });
                
                formHTML += `
                        </select>
                    </div>
                `;
                
                // Add remittance selection for leasing officers
                if (isLeasingOfficer) {
                    formHTML += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Remittances</label>
                            <select name="remit_id" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Remittance...</option>
                    `;
                    
                    // Add today's remittances
                    const today = new Date().toISOString().split('T')[0];
                    myRemittances.forEach(remit => {
                        const remitDate = new Date(remit.date).toISOString().split('T')[0];
                        if (remitDate === today) {
                            formHTML += `<option value="${remit.remit_id}">${remit.date}: Remittance - ₦${parseFloat(remit.amount_paid).toLocaleString()}</option>`;
                        }
                    });
                    
                    formHTML += `
                            </select>
                        </div>
                    `;
                }
            } else {
                // Regular amount field for other income lines
                if (config.has_fixed_price) {
                    formHTML += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                            <select name="amount_paid" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Amount</option>
                                ${config.prices.map(price => `<option value="${price}">₦${price.toLocaleString()}</option>`).join('')}
                            </select>
                        </div>
                    `;
                } else {
                    formHTML += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                            <input type="number" name="amount_paid" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    `;
                }
            }

            // Add payment type
            formHTML += `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type</label>
                    <select name="payment_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="cash">Cash</option>
                        <option value="transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="pos">POS</option>
                    </select>
                </div>
            `;

            // Add debit and credit accounts for accounts officers
            if (isAccountsOfficer) {
                formHTML += `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Debit Account</label>
                        <select name="debit_account" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="TILL-001">Account Till</option>
                            <option value="BANK-001">Bank Account</option>
                            <option value="CASH-001">Cash Account</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Credit Account</label>
                        <input type="text" name="credit_account" value="${alias}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    </div>
                `;
            }

            formHTML += `</div>`;

            // Add other fields for non-Car Loading income lines
            if (alias !== 'Car Loading' && config.fields && config.fields.length > 0) {
                formHTML += `
                    <div class="mt-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Additional Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                `;

                config.fields.forEach(field => {
                    const fieldLabel = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    
                    if (field.includes('date') || field === 'month_year') {
                        const inputType = field === 'month_year' ? 'month' : 'date';
                        formHTML += `
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">${fieldLabel}</label>
                                <input type="${inputType}" name="${field}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        `;
                    } else if (field.includes('no_of')) {
                        formHTML += `
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">${fieldLabel}</label>
                                <input type="number" name="${field}" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        `;
                    } else {
                        formHTML += `
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">${fieldLabel}</label>
                                <input type="text" name="${field}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        `;
                    }
                });

                formHTML += `</div></div>`;
            }

            // Add description field
            formHTML += `
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description/Notes</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="resetForm()" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        Reset
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-2"></i>Submit Payment
                    </button>
                </div>
                
                <!-- Hidden Fields -->
                <input type="hidden" name="posting_officer_id" value="${currentUserId}">
                <input type="hidden" name="posting_officer_name" value="${currentUserName}">
                <input type="hidden" name="income_line" value="${alias}">
                <input type="hidden" name="posting_officer_dept" value="${currentUserDept}">
                <input type="hidden" name="table_name" value="${selectedTableName}">
            </form>
            `;

            formContainer.innerHTML = formHTML;
            document.getElementById('paymentForm').classList.remove('hidden');
            document.getElementById('paymentForm').classList.add('fade-in');
        }

        // Calculate amount for Car Loading based on number of tickets
        function calculateAmount() {
            const tickets = document.getElementById('no_of_tickets')?.value;
            const amountField = document.getElementById('amount_paid');
            
            if (tickets && amountField && selectedIncomeLine === 'Car Loading') {
                // Assuming 200 per ticket for Car Loading
                const amount = parseInt(tickets) * 200;
                amountField.value = amount.toLocaleString();
            }
        }

        async function submitPayment(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const submitButton = event.target.querySelector('button[type="submit"]');
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            try {
                const response = await fetch('api/submit_payment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Payment submitted successfully!', 'success');
                    event.target.reset();
                    
                    // Update remittance summary if leasing officer
                    if (isLeasingOfficer) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    showMessage(result.message || 'Error submitting payment', 'error');
                }
            } catch (error) {
                showMessage('Network error occurred', 'error');
            } finally {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save mr-2"></i>Submit Payment';
            }
        }

        function resetForm() {
            document.getElementById('paymentSubmissionForm').reset();
            selectedIncomeLine = null;
            selectedTableName = null;
            
            document.querySelectorAll('.income-line-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'bg-blue-50');
                btn.classList.add('border-gray-200');
            });
            
            document.getElementById('paymentForm').classList.add('hidden');
        }

        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            
            messageDiv.innerHTML = `
                <div class="${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-4 fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
            
            messageContainer.appendChild(messageDiv);
            
            // Remove message after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Auto-select General if it exists
        document.addEventListener('DOMContentLoaded', function() {
            const generalBtn = document.querySelector('[data-line="General"]');
            if (generalBtn) {
                generalBtn.click();
            }
        });
    </script>
</body>
</html>
