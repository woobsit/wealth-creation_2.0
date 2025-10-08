<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/Transaction.php';
require __DIR__.'/../app/helpers/session_helper.php';
// Check if user is already logged in
requireLogin();
$transaction =  new Transaction($databaseObj);
$stats = $transaction->getTransactionStats();
$pendingTransactions = [];
$pendingTransactions = $transaction->getPendingTransactionsForAccountApproval();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome -<?php echo $_SESSION['first_name']; ?> | WEALTH CREATION ERP </title> 
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
                            <i class="fas fa-coins text-2xl text-primary-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Today's Total Collections
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['today']['total']) ? formatCurrency($stats['today']['total']) : 0; ?></p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-primary-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-primary-700 hover:text-primary-800 transition-colors">
                        View full report <i class="fas fa-arrow-right ml-1 text-xs"></i>
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
                                    This week
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo isset($stats['week']['total']) ? formatCurrency($stats['week']['total']) : 0; ?></p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-success-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-success-700 hover:text-success-800 transition-colors">
                        view report <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div class="stats-card bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-warning-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-warning-100 p-3 rounded-full">
                            <i class="fas fa-exclamation-circle text-2xl text-warning-700"></i>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Pending Transactions
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    <?php echo count($pendingTransactions); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-warning-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-warning-700 hover:text-warning-800 transition-colors">
                        Review pending transaction <i class="fas fa-arrow-right ml-1 text-xs"></i>
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
                                    Vacant Units
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900 mt-1">
                                    3
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-danger-50 px-5 py-2 text-xs">
                    <a href="#" class="font-medium text-danger-700 hover:text-danger-800 transition-colors">
                        View vacant list <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- <div class="bg-white shadow-xl rounded-xl p-6 lg:p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-clock mr-2 text-primary-600"></i> Recent System Activity
            </h2>
            <p class="text-sm text-gray-500 mb-6">A list of the most recent actions taken across the system. (DataTables Enabled)</p>
            
            <div class="overflow-x-auto">
                <table id="activityTable" class="min-w-full divide-y divide-gray-200 table-modern">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Time
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Action
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Details
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10:30 AM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Accountant A</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Posted Transaction
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Rent for Shop B-205 (₦750,000)</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">09:15 AM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CEO</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved Lease
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">New Lease for Coldroom C-02</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yesterday, 4:45 PM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Leasing Officer B</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Customer Update
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Updated contact info for Mr. Uche</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 days ago</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Head of Audit</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Generated Report
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Trial Balance for Q3 2025</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 days ago</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Accountant C</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Posted Transaction
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Service Charge for Container 1</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 days ago</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Leasing Officer A</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Customer Registered
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">New customer: Mrs. Nkechi</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 text-right">
                <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 btn-modern">
                    View All Activity Log
                    <i class="fas fa-chevron-right ml-2 text-xs"></i>
                </a>
            </div>
        </div> -->
    </main>

    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> WEALTH CREATION ERP. All rights reserved. Developed by Woobs Resources Ltd.
            </div>
        </div>
    </footer>

<script>
    // **JAVASCRIPT FOR DROPDOWN FUNCTIONALITY**
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            // Close all other dropdowns (to prevent multiple open menus)
            document.querySelectorAll('.relative > div.dropdown-menu').forEach(otherDropdown => {
                if (otherDropdown.id !== id) {
                    otherDropdown.classList.add('hidden');
                }
            });

            // Toggle the clicked one
            dropdown.classList.toggle('hidden');
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        // Check if the click is outside of any element that triggers or is a dropdown
        if (!event.target.closest('.relative button') && !event.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.relative > div.dropdown-menu').forEach(dropdown => {
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