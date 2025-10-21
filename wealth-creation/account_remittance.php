<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/Transaction.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/OfficerPerformanceAnalyzer.php';
require __DIR__.'/../app/models/OfficerTargetManager.php';
require __DIR__.'/../app/models/PaymentProcessor.php';
require __DIR__.'/../app/models/Remittance.php';
require __DIR__.'/../app/models/FileCacheWealthCreation.php';
require __DIR__.'/../app/models/FileCacheAccount.php';

// Check if user is already logged in
requireLogin();
$user_id = $_SESSION['user_id'];
$transaction =  new Transaction($databaseObj);
$remittancemanager = new Remittance($databaseObj);

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['btn_post'])) {
        // Validate amounts match
        if ($_POST['amount_paid'] !== $_POST['confirm_amount_paid']) {
            $error = 'Amount and confirmation amount do not match!';
        } else {
            $date_parts = explode('/', $_POST['date_of_payment']);
            $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            
            $remittance_data = [
                'officer_id' => $_POST['officer'],
                'officer_name' => $_POST['officer_name'],
                'date' => $formatted_date,
                'amount_paid' => preg_replace('/[,]/', '', $_POST['amount_paid']),
                'no_of_receipts' => $_POST['no_of_receipts'],
                'category' => $_POST['category'],
                'posting_officer_id' => $_POST['posting_officer_id'],
                'posting_officer_name' => $_POST['posting_officer_name']
            ];
            
            $result = $remittancemanager->processRemittance($remittance_data);
            
            if ($result['success']) {
                $message = $result['message'];
                // Redirect to prevent resubmission
                header('Location: account_remittance.php?success=1');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
// Handle deletion
if (isset($_GET['delete_id'])) {
    if ($manager->deleteRemittance($_GET['delete_id'])) {
        $message = 'Remittance deleted successfully!';
    } else {
        $error = 'Error deleting remittance!';
    }
}

// Get current date for display
$current_date = isset($_GET['d1']) ? $_GET['d1'] : date('Y-m-d');
if (isset($_GET['d1'])) {
    $date_parts = explode('/', $_GET['d1']);
    if (count($date_parts) === 3) {
        $current_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
    }
}

$officers = $remittancemanager->getWealthCreationOfficers();
$remittances = $remittancemanager->getRemittancesByDate($current_date);
$summary = $remittancemanager->getRemittanceSummary($current_date);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Remittance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
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
    

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0">
                        <h2 class="text-2xl font-bold text-gray-900">Account Remittance Dashboard</h2>
                        <p class="text-gray-600">Manage cash remittances from collection officers</p>
                        <p><?php include ('countdown_script.php'); ?></p>
                    </div>
                    
                    <!-- Date Filter Form -->
                    <form method="GET" class="flex flex-col sm:flex-row gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Remittance</label>
                            <input type="date" name="d1" 
                                   value="<?php echo $current_date; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                View Remittance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline">Cash remittance successful!</span>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cash Remittance Form -->
            <?php if ($_SESSION['department'] === 'Accounts'): ?>
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-red-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Cash Remittance</h3>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="posting_officer_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <input type="hidden" name="posting_officer_name" value="<?php echo $_SESSION['first_name']." ".$_SESSION['last_name']; ?>">

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Payment</label>
                            <input type="text" name="date_of_payment" 
                                   value="<?php echo date('d/m/Y'); ?>" 
                                   readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50">
                        </div>

                        <!-- Officer select -->
                        <select name="officer" id="officerSelect" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select...</option>
                            <?php foreach ($officers as $officer): ?>
                                <option value="<?php echo $officer['user_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($officer['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($officer['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Hidden field to hold the officer name -->
                        <input type="hidden" name="officer_name" id="officerName">
                        <!-- Script to fix officer_name goes below -->
                        <script>
                            document.getElementById('officerSelect').addEventListener('change', function() {
                                var selected = this.options[this.selectedIndex];
                                document.getElementById('officerName').value = selected.getAttribute('data-name') || '';
                            });
                        </script>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Remitted</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₦</span>
                                <input type="password" name="amount_paid" id="amount_paid" required
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Confirm Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Amount Remitted</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₦</span>
                                <input type="text" name="confirm_amount_paid" id="confirm_amount_paid" required
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <span id="message" class="text-sm mt-1"></span>
                        </div>

                        <!-- Number of Receipts -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No of Receipts</label>
                            <input type="number" name="no_of_receipts" required min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select...</option>
                                <option value="Rent">Rent Collection</option>
                                <option value="Service Charge">Service Charge Collection</option>
                                <option value="Other Collection">Other Collection</option>
                            </select>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-3">
                            <button type="submit" name="btn_post"
                                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Post Remittance
                            </button>
                            <button type="reset"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Remittance Summary -->
            <div class="<?php echo $_SESSION['department'] === 'Accounts' ? 'lg:col-span-2' : 'lg:col-span-3'; ?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <?php foreach ($summary as $category => $data): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <?php echo $category; ?> Remittance
                        </h3>
                        <div class="space-y-2">
                            <?php 
                            $total_remitted = 0;
                            $total_posted = 0;
                            foreach ($data['remitted'] as $index => $officer): 
                                $total_remitted += $officer['amount_remitted'];
                                $posted_amount = isset($data['posted'][$index]['amount_posted']) ? $data['posted'][$index]['amount_posted'] : 0;
                                $total_posted += $posted_amount;
                            ?>
                            <div class="flex justify-between items-center text-sm">
                                <span class="font-medium"><?php echo $officer['officer_name']; ?></span>
                                <div class="text-right">
                                    <div class="text-green-600">₦<?php echo number_format($officer['amount_remitted']); ?></div>
                                    <div class="text-blue-600">₦<?php echo number_format($posted_amount); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="border-t pt-2 flex justify-between items-center font-bold">
                                <span>Total</span>
                                <div class="text-right">
                                    <div class="text-green-600">₦<?php echo number_format($total_remitted); ?></div>
                                    <div class="text-blue-600">₦<?php echo number_format($total_posted); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Remittances Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <?php echo date('d/m/Y', strtotime($current_date)); ?> Remittances
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($remittances)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No remittances found for this date.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($remittances as $remittance): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($_SESSION['department'] === 'Accounts'): ?>
                                        <?php if ($remittancemanager->canDeleteRemittance($remittance['id'], $remittance['remitting_officer_id'], $remittance['date'], $remittance['category'])): ?>
                                        <button onclick="confirmDelete(<?php echo $remittance['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('H:i:s', strtotime($remittance['remitting_time'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $remittance['officer_name']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $remittance['category'] === 'Rent' ? 'bg-blue-100 text-blue-800' : 
                                                  ($remittance['category'] === 'Service Charge' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'); ?>">
                                        <?php echo $remittance['category']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <div class="font-bold">₦<?php echo number_format($remittance['amount_paid']); ?></div>
                                    <div class="text-xs text-gray-500">(<?php echo $remittance['no_of_receipts']; ?> receipts)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    Posted by: <?php echo $remittance['posting_officer_name']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Amount confirmation validation
        document.getElementById('amount_paid').addEventListener('input', validateAmounts);
        document.getElementById('confirm_amount_paid').addEventListener('input', validateAmounts);

        function validateAmounts() {
            const amount = document.getElementById('amount_paid').value;
            const confirmAmount = document.getElementById('confirm_amount_paid').value;
            const message = document.getElementById('message');

            if (amount && confirmAmount) {
                if (amount === confirmAmount) {
                    message.textContent = 'Confirmed!';
                    message.className = 'text-sm mt-1 text-green-600';
                } else {
                    message.textContent = 'Amount mismatch!';
                    message.className = 'text-sm mt-1 text-red-600';
                }
            } else {
                message.textContent = '';
            }
        }

        // Delete confirmation
        function confirmDelete(id) {
            if (confirm('Are you sure you want to COMPLETELY DELETE this remittance?')) {
                window.location.href = 'account_remittance.php?delete_id=' + id;
            }
        }

        // **JAVASCRIPT FOR DROPDOWN FUNCTIONALITY**
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            if (dropdown) {
                
                // 1. Close all currently visible dropdowns
                // We now select ALL elements with the new 'dropdown-menu' class.
                document.querySelectorAll('.dropdown-menu').forEach(otherDropdown => {
                    if (otherDropdown.id !== id) {
                        otherDropdown.classList.add('hidden');
                    }
                });

                // 2. Toggle the clicked one's visibility
                dropdown.classList.toggle('hidden');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            // We update the selector here too, to check if the click is outside 
            // any button or any element with the 'dropdown-menu' class.
            if (!event.target.closest('.relative button') && !event.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });

    // **JAVASCRIPT FOR DATATABLES**
    $(document).ready(function() {
        $('#activityTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "pageLength": 5, // Show 5 entries by default
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "Filter records:",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No matching records found"
            },
            // Add Bootstrap styling classes (often needed for DataTables styling integration)
            "dom": 'lfrtip' 
        });
        
        // This is a common fix to make DataTables pagination buttons and search/length dropdowns visible 
        // when using a theme like Tailwind.
        $('.dataTables_wrapper').addClass('mt-4');
        $('.dataTables_length').addClass('mb-2');
        $('.dataTables_filter').addClass('mb-2');
    });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
     <footer class="bg-fuchsia-700 border-t border-fuchsia-800 mt-12 shadow-inner">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="text-center text-sm text-fuchsia-200">
            &copy; <?php echo date('Y'); ?> WEALTH CREATION ERP. All rights reserved. Developed by Woobs Resources Ltd.
        </div>
    </div>
    </footer>
</body>
</html>