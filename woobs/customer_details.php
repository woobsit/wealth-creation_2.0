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

if (!isset($_GET['cdetails_id'])) {
    header("Location: customers.php");
    exit;
}

$customerId = (int)$_GET['cdetails_id'];
$customerDetail = $customer->getCustomerById($customerId);

if (!$customerDetail) {
    header("Location: customers.php");
    exit;
}

$shopId = $customerDetail["id"];
$shopNo = $customerDetail["shop_no"];
$customerName = $customerDetail["customer_name"];
$staffId = $customerDetail["staff_id"];
$leaseTenure = $customerDetail["lease_tenure"];
$leaseStartDate = $customerDetail["lease_start_date"];
$leaseEndDate = $customerDetail["lease_end_date"];
$facilityStatus = $customerDetail["facility_status"];

$startDate = formatDate($leaseStartDate, 'd/m/Y');
$endDate = formatDate($leaseEndDate, 'd/m/Y');

$today = new DateTime();
$expiry = new DateTime($leaseEndDate);
$interval = $today->diff($expiry);
$daysRemaining = (int)$interval->format('%R%a');

$pageTitle = "Shop " . $shopNo . " - Customer Details";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title> <?php echo $pageTitle  ?> | WOOBS ERP </title> 
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
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg shadow-lg p-4 mb-4 text-white">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Shop <?php echo htmlspecialchars($shopNo); ?></h1>
                    <p class="text-purple-100 mt-2">Customer Name: <span class="font-semibold"> <?php echo htmlspecialchars($customerName); ?></span></p>
                    <p class="text-sm text-purple-200 mt-1">Managed by: <span class="font-semibold"><?php echo htmlspecialchars($customerDetail['staff_name']); ?></span></p>
                </div>
                <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
                    <?php if ($facilityStatus === 'Active'): ?>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Active Shop
                    </span>
                    <?php else: ?>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Inactive Shop
                    </span>
                    <?php endif; ?>

                    <?php
                    if ($daysRemaining < 0) {
                        $daysExpired = abs($daysRemaining);
                        if ($daysExpired <= 31) {
                            echo '<span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">';
                            echo '<i class="fas fa-exclamation-triangle mr-1"></i>Expired ' . $daysExpired . ' days ago';
                        } elseif ($daysExpired <= 365) {
                            $months = floor($daysExpired / 30);
                            $days = $daysExpired % 30;
                            echo '<span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">';
                            echo '<i class="fas fa-exclamation-triangle mr-1"></i>Expired ' . $months . 'month(s), ' . $days . 'd ago';
                        } else {
                            $years = floor($daysExpired / 365);
                            $months = floor(($daysExpired % 365) / 30);
                            echo '<span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">';
                            echo '<i class="fas fa-exclamation-triangle mr-1"></i>Expired ' . $years . '-year, ' . $months . '-month ago';
                        }
                        echo '</span>';
                    } elseif ($daysRemaining <= 31) {
                        echo '<span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">';
                        echo '<i class="fas fa-clock mr-1"></i>' . $daysRemaining . ' days remaining';
                        echo '</span>';
                    } elseif ($daysRemaining <= 365) {
                        $months = floor($daysRemaining / 30);
                        $days = $daysRemaining % 30;
                        echo '<span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">';
                        echo '<i class="fas fa-calendar-check mr-1"></i>' . $months . '-month, ' . $days . 'd left';
                        echo '</span>';
                    } else {
                        $years = floor($daysRemaining / 365);
                        $months = floor(($daysRemaining % 365) / 30);
                        echo '<span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">';
                        echo '<i class="fas fa-calendar-check mr-1"></i>' . $years . '-year, ' . $months . '-month left';
                        echo '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Lease Description -->
        <!-- <div class="bg-white rounded-lg shadow-lg p-6 mb-8"> -->
            <p class="text-gray-700 leading-relaxed mb-3">
                This is a <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($leaseTenure); ?></span> lease shop
                owned by <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($customerName); ?></span>.
                <?php if (!empty($customerDetail["off_takers_name"])): ?>
                    This property is subleased to <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($customerDetail["off_takers_name"]); ?></span>.
                <?php endif; ?>
                Current tenancy runs from <span class="font-semibold text-blue-600"><?php echo $startDate; ?></span>
                to expire on <span class="font-semibold text-blue-600"><?php echo $endDate; ?></span> managed by <span class="font-semibold"><?php echo htmlspecialchars($customerDetail['staff_name']); ?></span>.
            </p>
        <!-- </div> -->

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-lg p-4 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="flex flex-wrap gap-3">
                <a href="customers.php" class="btn-primary text-white px-4 py-2 rounded-lg hover-scale transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Customers
                </a>

                <a href="edit_customer.php?edit_id=<?php echo $shopId; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    <i class="fas fa-edit mr-2"></i>Edit Record
                </a>

                <a href="shop_history.php?history_id=<?php echo $shopId; ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors duration-200">
                    <i class="fas fa-history mr-2"></i>View History
                </a>

                <?php if ($daysRemaining < 0 || $daysRemaining <= 90): ?>
                <a href="lease_renewal.php?renewal_id=<?php echo $shopId; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    <i class="fas fa-sync-alt mr-2"></i>Renew Lease
                </a>
                <?php endif; ?>

                <?php if ($customerDetail['balance_verification_status'] !== 'Verified'): ?>
                <a href="verification_request_processing.php?vrequest_id=<?php echo $shopId; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                    <i class="fas fa-check-double mr-2"></i>Request Verification
                </a>
                <?php endif; ?>
            </div>
        </div>


        <!-- Main Content Wrapper -->
        <div class="space-y-8">
            <!-- Customer facility information -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Customer Card -->
                    <div class="bg-white rounded-xl shadow-sm border">
                        <div class="bg-blue-50 px-4 py-3 border-b">
                            <h3 class="text-base font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-user text-blue-600 mr-2"></i> Customer Information
                            </h3>
                        </div>

                        <div class="p-4">
                            <!-- Photo -->
                            <div class="text-center mb-4">
                                <?php if (!empty($customerDetail['passport'])): ?>
                                <img src="../assets/images/passports/<?php echo htmlspecialchars($customerDetail['passport']); ?>"
                                    class="w-28 h-28 rounded-full mx-auto object-cover border-2 border-gray-200" />
                                <?php else: ?>
                                <div class="w-28 h-28 rounded-full mx-auto bg-gray-200 flex items-center justify-center border-2">
                                    <i class="fas fa-user text-gray-400 text-3xl"></i>
                                </div>
                                <?php endif; ?>
                                <a href="upload_pic.php?shop_id=<?php echo $shopId; ?>"
                                class="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                    <i class="fas fa-camera mr-1"></i>Update Photo
                                </a>

                            </div>
                            <!-- Fields -->
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-phone mr-1"></i>Phone:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($customerDetail['phone_no'] ?: 'N/A'); ?></span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-envelope mr-1"></i>Email:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($customerDetail['email'] ?: 'N/A'); ?></span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-calendar mr-1"></i>DOB:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($customerDetail['dob'] ?: 'N/A'); ?></span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-600"><i class="fas fa-map-marker-alt mr-1"></i>State:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($customerDetail['state'] ?: 'N/A'); ?></span>
                                </div>

                                <div class="pt-3 border-t">
                                    <span class="text-gray-600"><i class="fas fa-home mr-1"></i>Address:</span>
                                    <p class="font-medium mt-1"><?php echo htmlspecialchars($customerDetail['home_address'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Facility Card -->
                    <div class="bg-white rounded-xl shadow-sm border">
                        <div class="bg-green-50 px-4 py-3 border-b">
                            <h3 class="text-base font-semibold flex items-center">
                                <i class="fas fa-building text-green-600 mr-2"></i> Facility Details
                            </h3>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-gray-600">Shop Block:</span><span class="font-medium"><?php echo htmlspecialchars($customerDetail['shop_block'] ?: 'N/A'); ?></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Block Unit:</span><span class="font-medium"><?php echo htmlspecialchars($customerDetail['block_unit'] ?: 'N/A'); ?></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Space Size:</span><span class="font-medium"><?php echo htmlspecialchars($customerDetail['shop_size'] ?: 'N/A'); ?></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Facility Type:</span><span class="font-medium"><?php echo htmlspecialchars($customerDetail['facility_type'] ?: 'N/A'); ?></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Occupancy:</span><span class="font-medium"><?php echo htmlspecialchars($customerDetail['occupancy_category'] ?: 'N/A'); ?></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Key Status:</span><span class="font-medium"><?php echo htmlspecialchars($customerDetail['key_status'] ?: 'N/A'); ?></span></div>
                        </div>
                    </div>

                </div> 

                <!-- Right Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Lease Card -->
                    <div class="bg-white rounded-xl shadow-sm border">
                        <div class="bg-purple-50 px-4 py-3 border-b">
                            <h3 class="text-base font-semibold flex items-center">
                                <i class="fas fa-file-contract text-purple-600 mr-2"></i> Lease Information & Timeline
                            </h3>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <!-- Left -->
                                <div class="space-y-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Lease Tenure</span>
                                        <p class="text-lg font-semibold"><?php echo htmlspecialchars($leaseTenure); ?></p>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">Lease Start Date</span>
                                        <p class="text-lg font-semibold text-blue-600"><?php echo $startDate; ?></p>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">Lease Expiry Date</span>
                                        <p class="text-lg font-semibold text-red-600"><?php echo $endDate; ?></p>
                                    </div>
                                </div>



                            </div>
                          

                        </div>

                    </div>

                </div>

            </div>     
        </div>
        
        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Customer Information -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-user text-blue-600 mr-2"></i> Customer Information
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="text-center mb-4">
                            <?php if (!empty($customerDetail['passport'])): ?>
                            <img src="../assets/images/passports/<?php echo htmlspecialchars($customerDetail['passport']); ?>"
                                 alt="Customer Photo"
                                 class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200">
                            <?php else: ?>
                            <div class="w-32 h-32 rounded-full mx-auto bg-gray-200 flex items-center justify-center border-4 border-gray-300">
                                <i class="fas fa-user text-gray-400 text-4xl"></i>
                            </div>
                            <?php endif; ?>
                            <a href="upload_pic.php?shop_id=<?php echo $shopId; ?>" class="mt-3 inline-block text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-camera mr-1"></i>Update Photo
                            </a>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600"><i class="fas fa-phone mr-2"></i>Phone:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['phone_no'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600"><i class="fas fa-envelope mr-2"></i>Email:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['email'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600"><i class="fas fa-calendar mr-2"></i>DOB:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['dob'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600"><i class="fas fa-map-marker-alt mr-2"></i>State:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['state'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="pt-3 border-t">
                                <span class="text-sm text-gray-600"><i class="fas fa-home mr-2"></i>Address:</span>
                                <p class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($customerDetail['home_address'] ?: 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facility Details -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mt-6">
                    <div class="bg-green-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-building text-green-600 mr-2"></i>Facility Details
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Shop Block:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['shop_block'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Block Unit:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['block_unit'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Space Size:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['shop_size'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Facility Type:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['facility_type'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Occupancy:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['occupancy_category'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Key Status:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['key_status'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lease & Tenure Information -->
            <div class="lg:col-span-2">
                <!-- Lease Information Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                    <div class="bg-purple-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-file-contract text-purple-600 mr-2"></i>Lease Information & Timeline
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm text-gray-600">Lease Tenure</label>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($leaseTenure); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Start Date</label>
                                    <p class="text-lg font-semibold text-blue-600"><?php echo $startDate; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Expiry Date</label>
                                    <p class="text-lg font-semibold text-red-600"><?php echo $endDate; ?></p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm text-gray-600">Lease Status</label>
                                    <?php if ($daysRemaining >= 0): ?>
                                    <p class="text-lg font-semibold text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </p>
                                    <?php else: ?>
                                    <p class="text-lg font-semibold text-red-600">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Expired
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Verification Status</label>
                                    <?php if ($customerDetail['balance_verification_status'] === 'Verified'): ?>
                                    <p class="text-lg font-semibold text-green-600">
                                        <i class="fas fa-check-double mr-1"></i>Verified
                                    </p>
                                    <?php else: ?>
                                    <p class="text-lg font-semibold text-yellow-600">
                                        <i class="fas fa-clock mr-1"></i>Pending Verification
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Created By</label>
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['posting_officer_name'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Progress -->
                        <div class="mt-6 pt-6 border-t">
                            <label class="text-sm text-gray-600 mb-2 block">Lease Timeline</label>
                            <?php
                            $leaseStart = new DateTime($leaseStartDate);
                            $leaseEnd = new DateTime($leaseEndDate);
                            $totalDays = $leaseStart->diff($leaseEnd)->days;
                            $elapsedDays = $leaseStart->diff($today)->days;
                            $progress = min(100, max(0, ($elapsedDays / $totalDays) * 100));
                            ?>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="<?php echo $progress >= 90 ? 'bg-red-600' : ($progress >= 70 ? 'bg-yellow-500' : 'bg-green-600'); ?> h-3 rounded-full transition-all duration-300"
                                     style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-gray-600">
                                <span><?php echo $startDate; ?></span>
                                <span class="font-semibold"><?php echo round($progress); ?>% Complete</span>
                                <span><?php echo $endDate; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($customerDetail["off_takers_name"])): ?>
                <!-- Off-Taker Information -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                    <div class="bg-yellow-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-users text-yellow-600 mr-2"></i>Current Occupant (Sub-Lease)
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Name:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['off_takers_name']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Phone:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['off_takers_phone_no'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Email:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['off_takers_email'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Address:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customerDetail['off_takers_address'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Financial Analysis Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Rent Payment Analysis -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-money-bill-wave text-blue-600 mr-2"></i> Rent Payment Analysis
                    </h3>
                    <a href="payment_history.php?customer_id=<?php echo $shopId; ?>&type=rent" class="text-sm text-blue-600 hover:text-blue-800">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="p-6">
                    <?php
                    $expectedRent = (float)$customerDetail['expected_rent'];
                    $rentPaid = (float)$customerDetail['rent_paid'];
                    $rentBalance = $expectedRent - $rentPaid;
                    ?>

                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-sm text-gray-600">Expected Rent (Yearly)</span>
                            <span class="text-lg font-bold text-gray-900"><?php echo formatCurrency($expectedRent); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                            <span class="text-sm text-gray-600">Total Rent Paid</span>
                            <span class="text-lg font-bold text-green-600"><?php echo formatCurrency($rentPaid); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 <?php echo $rentBalance > 0 ? 'bg-red-50' : 'bg-gray-50'; ?> rounded">
                            <span class="text-sm text-gray-600">Balance</span>
                            <span class="text-lg font-bold <?php echo $rentBalance > 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                                <?php echo formatCurrency($rentBalance); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Payment Progress -->
                    <?php $paymentProgress = $expectedRent > 0 ? min(100, ($rentPaid / $expectedRent) * 100) : 0; ?>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Payment Progress</span>
                            <span class="font-semibold"><?php echo round($paymentProgress); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="<?php echo $paymentProgress >= 90 ? 'bg-green-600' : ($paymentProgress >= 50 ? 'bg-yellow-500' : 'bg-red-600'); ?> h-3 rounded-full"
                                 style="width: <?php echo $paymentProgress; ?>%"></div>
                        </div>
                    </div>

                    <a href="payment_history.php?customer_id=<?php echo $shopId; ?>&type=rent"
                       class="block text-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-history mr-2"></i>View Payment History
                    </a>
                </div>
            </div>

            <!-- Service Charge Analysis -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-50 px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-tools text-green-600 mr-2"></i>Service Charge Analysis
                    </h3>
                    <a href="payment_history.php?customer_id=<?php echo $shopId; ?>&type=service_charge" class="text-sm text-green-600 hover:text-green-800">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="p-6">
                    <?php
                    $expectedSC = (float)$customerDetail['expected_service_charge'];
                    $scPaid = (float)$customerDetail['service_charge_paid'];
                    $scBalance = $expectedSC - $scPaid;
                    ?>

                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-sm text-gray-600">Expected S/C (Monthly)</span>
                            <span class="text-lg font-bold text-gray-900"><?php echo formatCurrency($expectedSC); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-sm text-gray-600">Expected S/C (Yearly)</span>
                            <span class="text-lg font-bold text-gray-900"><?php echo formatCurrency($expectedSC * 12); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                            <span class="text-sm text-gray-600">Total S/C Paid</span>
                            <span class="text-lg font-bold text-green-600"><?php echo formatCurrency($scPaid); ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 <?php echo $scBalance > 0 ? 'bg-red-50' : 'bg-gray-50'; ?> rounded">
                            <span class="text-sm text-gray-600">Balance</span>
                            <span class="text-lg font-bold <?php echo $scBalance > 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                                <?php echo formatCurrency($scBalance); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Service Charge Info -->
                    <div class="p-3 bg-blue-50 rounded mb-4">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Payment Plan:</span> <?php echo htmlspecialchars($customerDetail['service_charge_schedule'] ?: 'N/A'); ?>
                        </p>
                        <p class="text-sm text-gray-700 mt-1">
                            <span class="font-semibold">SC Tenure:</span> <?php echo htmlspecialchars($customerDetail['service_charge_tenure'] ?: 'N/A'); ?>
                        </p>
                    </div>

                    <a href="payment_history.php?customer_id=<?php echo $shopId; ?>&type=service_charge"
                       class="block text-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                        <i class="fas fa-history mr-2"></i>View Payment History
                    </a>
                </div>
            </div>
        </div>

        <!-- Actions Footer -->
        <div class="mt-8 flex justify-center">
            <a href="customers.php" class="btn-primary text-white px-6 py-3 rounded-lg hover-scale transition-all duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Customer List
            </a>
        </div>
    </div>
</div>

<!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                     &copy; <?php echo date('Y'); ?> WOOBS ERP. All rights reserved. Developed by Woobs Resources Ltd.
                </div>
                <div class="text-sm text-gray-500">
                    Version 2.0 | Built with PHP & Tailwind CSS
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-auto-hide');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Add loading state to buttons
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                }
            });
        });

        // Initialize tooltips
        document.querySelectorAll('[data-tooltip]').forEach(function(element) {
            element.addEventListener('mouseenter', function() {
                // Add tooltip implementation
            });
        });
    </script>
    <script>
        function toggleDropdown(id) {
            document.querySelectorAll('.absolute').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            const dropdown = document.getElementById(id);
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function (e) {
            const buttons = ['transactionDropdown', 'reportDropdown', 'userDropdown'];
            const clickedInsideDropdown = buttons.some(id => {
                const dropdown = document.getElementById(id);
                return dropdown && dropdown.contains(e.target);
            });

            const clickedOnButton = e.target.closest('button');
            if (!clickedInsideDropdown && !clickedOnButton) {
                buttons.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
