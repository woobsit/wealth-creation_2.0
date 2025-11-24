<?php
require __DIR__.'/../app/config/config_woobs.php';
require __DIR__.'/../app/helpers/session_helper.php';
require_once 'include/functions.php';
require __DIR__.'/../app/models/Customer.php';
require __DIR__.'/../app/models/User.php';

// Check if user is logged in
requireLogin();
// Get the user id
$userId = getLoggedInUserId();
// Get current user
$staff = new User($databaseObj);
$customer = new Customer($databaseObj);
$currentUser = $staff->getUserById($userId);

$pageTitle = "Customer Management";

// Handle search and filters
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'active';
$staffId = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$blockId = isset($_GET['block_id']) ? (int)$_GET['block_id'] : 0;

// Get customers based on filters
if ($searchTerm) {
    $customers = $customer->searchCustomers($searchTerm);
} elseif ($staffId) {
    $customers = $customer->getCustomersByStaff($staffId, $status);
} elseif ($blockId) {
    $blockData = $customer->getShopsByBlock($blockId);
    $customers = $customer->getCustomersByBlock($blockData['block_name'], $status);
} else {
    $customers = $customer->getAllCustomers($status, 50);
}

// Get additional data
$blocks = $customer->getShopBlocks();
$stats = $customer->getCustomerStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome -<?php echo $_SESSION['first_name']; ?> | WOOBS ERP </title> 
    <meta http-equiv="Content-Type" name="description" content="Wealth Creation ERP Management System; text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Woobs Resources Ltd">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" /> 
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Custom styles from your original request, adapted for the static context */
        .dropdown-menu {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .dropdown-menu a {
            padding: 0.5rem 1rem;
            color: #374151;
            text-decoration: none;
            display: block;
        }
        .dropdown-menu a:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        /* Removed .navbar-nav .dropdown:hover .dropdown-menu as we rely purely on JS toggle */
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .btn-modern {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .table-modern {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .table-modern thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table-modern thead th {
            color: white !important; /* Force white text on header */
            border-bottom: none !important;
        }
        .table-modern tbody tr:hover {
            background-color: #f8fafc;
        }
        /* Custom DataTables adjustments to fit theme */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #2563eb !important;
            color: white !important;
            border: 1px solid #2563eb;
            border-radius: 0.25rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 0.25rem;
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
        }

        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hover-scale {
            transition: transform 0.2s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .card-shadow:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div id="body" class="min-h-screen"> 
    
    <?php include('include/header.php'); ?>
    
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customer Management</h1>
                    <p class="text-gray-600 mt-1">Manage and monitor all customer accounts</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="add_customer.php" class="btn-primary text-white px-4 py-2 rounded-lg hover-scale transition-all duration-200">
                        <i class="fas fa-plus mr-2"></i>Add Customer
                    </a>
                    <button onclick="exportData()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 card-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Customers</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6 card-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-user-times text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Inactive Customers</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['inactive']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6 card-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-home text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Vacant Shops</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['vacant']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                           placeholder="Customer name, shop no, phone..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inActive" <?php echo $status === 'inActive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div>
                    <label for="block_id" class="block text-sm font-medium text-gray-700 mb-2">Shop Block</label>
                    <select id="block_id" name="block_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Blocks</option>
                        <?php foreach ($blocks as $block): ?>
                        <option value="<?php echo $block['block_id']; ?>" <?php echo $blockId == $block['block_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($block['block_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full btn-primary text-white px-4 py-2 rounded-lg hover-scale transition-all duration-200">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <?php echo ucfirst($status); ?> Customers 
                    <span class="text-sm font-normal text-gray-500">(<?php echo count($customers); ?> found)</span>
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lease Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Amounts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($customers as $cust): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <?php if ($cust['passport']): ?>
                                        <img src="../assets/images/passports/<?php echo $cust['passport']; ?>" 
                                             alt="Customer" 
                                             class="h-12 w-12 rounded-full object-cover">
                                        <?php else: ?>
                                        <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cust['customer_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($cust['phone_no']); ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Shop <?php echo htmlspecialchars($cust['shop_no']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($cust['shop_size']); ?> - <?php echo htmlspecialchars($cust['shop_block']); ?></div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($cust['lease_tenure']); ?></div>
                                <div class="text-sm text-gray-500">
                                    <?php echo formatDate($cust['lease_start_date'], 'd/m/Y'); ?> - 
                                    <?php echo formatDate($cust['lease_end_date'], 'd/m/Y'); ?>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">Rent: <?php echo formatCurrency($cust['expected_rent']); ?></div>
                                <div class="text-sm text-gray-500">S/C: <?php echo formatCurrency($cust['expected_service_charge']); ?></div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($cust['facility_status'] === 'Active'): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                                <?php else: ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    InActive
                                </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="customer_details.php?cdetails_id=<?php echo $cust['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_customer.php?cdetails_id=<?php echo $cust['id']; ?>" 
                                       class="text-yellow-600 hover:text-yellow-900" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="payment_history.php?customer_id=<?php echo $cust['id']; ?>" 
                                       class="text-green-600 hover:text-green-900" 
                                       title="Payment History">
                                        <i class="fas fa-money-bill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($customers)): ?>
            <div class="text-center py-12">
                <i class="fas fa-users text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                <p class="text-gray-500 mb-4">Try adjusting your search or filter criteria.</p>
                <a href="customers.php" class="btn-primary text-white px-4 py-2 rounded-lg">
                    Clear Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

 <footer class="border-t mt-12 shadow-inner" style="background: linear-gradient(145deg, #0284c7, #0ea5e9); border-color: #0284c7;">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="text-center text-sm text-white">
            &copy; <?php echo date('Y'); ?> WOOBS ERP. All rights reserved. Developed by Woobs Resources Ltd.
        </div>
    </div>
</footer>

<script>
    // Amount confirmation validation in cash remittance.
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

    </div>
</body>
</html>