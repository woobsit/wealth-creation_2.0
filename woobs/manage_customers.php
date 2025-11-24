<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'includes/Database.php';
//require_once 'includes/Session.php';
require_once 'includes/functions.php';
require_once 'models/Customer.php';
require_once 'models/Staff.php';
require_once 'models/MPR.php';
require_once 'includes/session_helper.php';

// Check if user is logged in
requireLogin();
// Get the user id
$userId = getLoggedInUserId();
// Get current user
$staff = new Staff();
$customer = new Customer();
$mpr = new MPR();
$currentUser = $staff->getStaffById($userId);

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

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include 'includes/navbar.php'; ?>
    
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
                                        <img src="../mod/leasing/images/passports/<?php echo $cust['passport']; ?>" 
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
                                    <a href="customer_details.php?id=<?php echo $cust['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_customer.php?id=<?php echo $cust['id']; ?>" 
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

<script>
function exportData() {
    window.location.href = 'export_customers.php' + window.location.search;
}
</script>

<?php include 'includes/footer.php'; ?>