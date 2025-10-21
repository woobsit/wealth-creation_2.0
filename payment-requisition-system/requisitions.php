<?php
require_once 'config/config.php';
requireLogin();

$requisition = new Requisition();
$message = '';
$messageType = '';

// Handle filters
$filters = array(
    'user_id' => $_SESSION['user_id'],
    'status'  => isset($_GET['status']) ? $_GET['status'] : '',
    'search'  => isset($_GET['search']) ? $_GET['search'] : ''
);

$requisitions = $requisition->getAll($filters);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - My Requisitions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8 pt-24">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">My Requisitions</h1>
                                <p class="text-gray-600 mt-1">Manage and track your payment requisitions</p>
                            </div>
                            <a href="create-requisition.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                New Requisition
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="flex-1 min-w-64">
                                <input type="text" name="search" placeholder="Search requisitions..." 
                                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Status</option>
                                    <option value="draft" <?php echo $filters['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                        </form>
                    </div>

                    <!-- Requisitions Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($requisitions)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-file-text text-4xl mb-4 text-gray-300"></i>
                                            <p>No requisitions found</p>
                                            <a href="create-requisition.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                                Create your first requisition
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requisitions as $req): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                                <a href="requisition-details.php?id=<?php echo $req['id']; ?>" class="hover:text-blue-800">
                                                    <?php echo htmlspecialchars($req['reference_number']); ?>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="max-w-xs truncate">
                                                    <?php echo htmlspecialchars($req['title']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatCurrency($req['amount'], $req['currency']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($req['department']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $priorityColors = [
                                                    'urgent' => 'bg-red-100 text-red-800',
                                                    'high' => 'bg-orange-100 text-orange-800',
                                                    'medium' => 'bg-blue-100 text-blue-800',
                                                    'low' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $priorityClass = isset($priorityColors[$req['priority']]) ? $priorityColors[$req['priority']] : 'bg-gray-100 text-gray-800';
                                                //$priorityClass = $priorityColors[$req['priority']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $priorityClass; ?>">
                                                    <?php echo ucfirst($req['priority']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusColors = [
                                                    'approved' => 'bg-green-100 text-green-800',
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'draft' => 'bg-gray-100 text-gray-800',
                                                    'completed' => 'bg-blue-100 text-blue-800'
                                                ];
                                                $statusClass = isset($statusColors[$req['status']]) ? $statusColors[$req['status']] : 'bg-gray-100 text-gray-800';
                                                //$statusClass = $statusColors[$req['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($req['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($req['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="requisition-details.php?id=<?php echo $req['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($req['status'] === 'draft'): ?>
                                                        <a href="edit-requisition.php?id=<?php echo $req['id']; ?>" 
                                                           class="text-green-600 hover:text-green-900" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>