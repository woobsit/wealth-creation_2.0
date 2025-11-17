<?php
require __DIR__.'/../app/config/config_woobs.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/Customer.php';
// require __DIR__.'/../app/models/PowerConsumption.php';

// Check if user is already logged in
requireLogin();
$user_id = $_SESSION['user_id'];
$customer = new Customer($databaseObj);
// $powerConsumption = new PowerConsumption($databaseObj);
// Get statistics
$customerStats = $customer->getCustomerStats($databaseObj);
$rentExpiryStats = $customer->getRentExpiryStats($databaseObj); 
// // Get power consumption data for selected period
// $powerData = $powerConsumption->getPowerConsumptionByPeriod(date('m'));
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

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="mb-8 border-b pb-4">
            <h1 class="text-3xl font-extrabold text-gray-900">
                <i class="fas fa-sun text-yellow-500 mr-2"></i> Dashboard Overview. 
            </h1> 
            
            <p class="mt-1 text-lg text-gray-500">
                Welcome! <?php echo $_SESSION['first_name'] ." ". $_SESSION['last_name']; ?> Your Dashboard is particular to your department's activities.
            </p>
        </div>

        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-10">
            
            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-primary-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-primary-100 p-3 rounded-full">
                            <i class="fas fa-users text-2xl text-primary-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Active Customers
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <p class="text-2xl font-bold text-gray-900"> <?php echo number_format($customerStats['active']); ?> 
                                    <span class="text-sm text-gray-500 font-normal">Shop(s)</span>
                                    </p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-primary-50 px-5 py-2 text-xs">
                    <a href="customers.php" class="font-medium text-primary-700 hover:text-primary-800 transition-colors">
                        View all active customers → <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-success-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-success-100 p-3 rounded-full">
                            <i class="fas fa-calendar-day text-2xl text-success-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Inactive Customers
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <p class="text-2xl font-bold text-gray-900">
                                        <?php echo number_format($customerStats['inactive']); ?>
                                        <span class="text-sm text-gray-500 font-normal">Shop(s)</span>
                                    </p>
                                    
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-success-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-success-700 hover:text-success-800 transition-colors">
                        View inactive customers → <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-warning-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-warning-100 p-3 rounded-full">
                            <i class="fas fa-home text-2xl text-warning-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Vacant Shops
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <?php echo number_format($customerStats['vacant']); ?>
                                    <span class="text-sm text-gray-500 font-normal">Shop(s)</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-warning-50 px-5 py-2 text-xs">
                    <a href="leasing/vacant_shops.php" class="font-medium text-warning-700 hover:text-warning-800 transition-colors">
                        View vacant shops → <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-danger-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-danger-100 p-3 rounded-full">
                            <i class="fas fa-home text-2xl text-danger-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Expiring (<?php echo date('F'); ?>)
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <?php echo number_format($rentExpiryStats['expiring_count']); ?> 
                                    <span class="text-sm text-gray-500 font-normal">Shop(s)</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-danger-50 px-5 py-2 text-xs">
                    <a href="leasing/vacant_shops.php" class="font-medium text-danger-700 hover:text-danger-800 transition-colors">
                     View shop analysis → <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Shortcuts -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                <i class="fas fa-bolt text-yellow-600 mr-2"></i>Quick Shortcuts
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="mpr_analysis_rent.php" class="text-center p-4 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors duration-200">
                    <i class="fas fa-home text-2xl text-blue-600 mb-2 block"></i>
                    <span class="text-sm font-medium text-blue-800">MPR Dashboard</span>
                </a>

                <a href="leasing/power_consumption.php" class="text-center p-4 bg-green-100 rounded-lg hover:bg-green-200 transition-colors duration-200">
                    <i class="fas fa-bolt text-2xl text-green-600 mb-2 block"></i>
                    <span class="text-sm font-medium text-green-800">Power Consumption</span>
                </a>
                
                <a href="mod/account/account_dashboard.php" class="text-center p-4 bg-red-100 rounded-lg hover:bg-red-200 transition-colors duration-200">
                    <i class="fas fa-money-bill text-2xl text-red-600 mb-2 block"></i>
                    <span class="text-sm font-medium text-red-800">Account Remittance</span>
                </a>

                <a href="leasing/shop_analysis.php" class="text-center p-4 bg-purple-100 rounded-lg hover:bg-purple-200 transition-colors duration-200">
                    <i class="fas fa-building text-2xl text-purple-600 mb-2 block"></i>
                    <span class="text-sm font-medium text-purple-800">Shop Analysis</span>
                </a>
            </div>
        </div>

    </main>

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