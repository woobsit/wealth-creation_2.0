<?php
require_once 'config/config.php';
requireLogin();

$requisition = new Requisition();
$id = $_GET['id'] ?? 0;

$req = $requisition->getById($id);
if (!$req) {
    redirect('requisitions.php');
}

// Check if user can view this requisition
if ($req['created_by'] != $_SESSION['user_id'] && $_SESSION['level'] < 3) {
    redirect('requisitions.php');
}

$approvalSteps = $requisition->getApprovalSteps($id);
$retirements = $requisition->getRetirements($id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Requisition Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8 pt-24">
            <div class="max-w-6xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($req['title']); ?></h1>
                                <p class="text-gray-600 mt-1">Reference: <?php echo htmlspecialchars($req['reference_number']); ?></p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <?php
                                $statusColors = [
                                    'approved' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'completed' => 'bg-blue-100 text-blue-800'
                                ];
                                $statusClass = $statusColors[$req['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                                <a href="requisitions.php" class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Requisition Details -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                                <p class="text-lg font-semibold text-gray-900">
                                    <?php echo formatCurrency($req['amount'], $req['currency']); ?>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($req['department']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($req['category']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <?php
                                $priorityColors = [
                                    'urgent' => 'bg-red-100 text-red-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'medium' => 'bg-blue-100 text-blue-800',
                                    'low' => 'bg-gray-100 text-gray-800'
                                ];
                                $priorityClass = $priorityColors[$req['priority']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $priorityClass; ?>">
                                    <?php echo ucfirst($req['priority']); ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Created By</label>
                                <p class="text-gray-900"><?php echo htmlspecialchars($req['created_by_name']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Created Date</label>
                                <p class="text-gray-900"><?php echo date('M j, Y g:i A', strtotime($req['created_at'])); ?></p>
                            </div>
                            <?php if ($req['due_date']): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Required By</label>
                                <p class="text-gray-900"><?php echo date('M j, Y', strtotime($req['due_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($req['description'])); ?></p>
                            </div>
                        </div>

                        <?php if ($req['justification']): ?>
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Justification</label>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($req['justification'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Approval Workflow -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Approval Workflow</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($approvalSteps as $step): ?>
                                <div class="flex items-center space-x-4 p-4 rounded-lg <?php echo $step['status'] === 'approved' ? 'bg-green-50' : ($step['status'] === 'rejected' ? 'bg-red-50' : 'bg-gray-50'); ?>">
                                    <div class="flex-shrink-0">
                                        <?php if ($step['status'] === 'approved'): ?>
                                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                                <i class="fas fa-check text-white text-sm"></i>
                                            </div>
                                        <?php elseif ($step['status'] === 'rejected'): ?>
                                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                                <i class="fas fa-times text-white text-sm"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                <i class="fas fa-clock text-gray-600 text-sm"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-medium text-gray-900">
                                                    Level <?php echo $step['approval_level']; ?>: <?php echo htmlspecialchars($step['approver_title']); ?>
                                                </h3>
                                                <?php if ($step['approver_name']): ?>
                                                    <p class="text-sm text-gray-600">
                                                        Approved by: <?php echo htmlspecialchars($step['approver_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($step['comments']): ?>
                                                    <p class="text-sm text-gray-700 mt-2">
                                                        <strong>Comments:</strong> <?php echo htmlspecialchars($step['comments']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-sm font-medium <?php echo $step['status'] === 'approved' ? 'text-green-600' : ($step['status'] === 'rejected' ? 'text-red-600' : 'text-gray-500'); ?>">
                                                    <?php echo ucfirst($step['status']); ?>
                                                </span>
                                                <?php if ($step['approved_at']): ?>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?php echo date('M j, Y g:i A', strtotime($step['approved_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Retirement Section -->
                <?php if ($req['status'] === 'approved' || $req['status'] === 'completed'): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">Retirement Details</h2>
                            <?php if (in_array($_SESSION['department'], ['Accounts', 'Audit']) && $_SESSION['level'] >= 3): ?>
                                <button onclick="showRetirementModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Add Retirement
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($retirements)): ?>
                            <p class="text-gray-500 text-center py-8">No retirement records found</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($retirements as $retirement): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Amount Retired</label>
                                                <p class="text-lg font-semibold text-green-600">
                                                    <?php echo formatCurrency($retirement['amount_retired'], $req['currency']); ?>
                                                </p>
                                            </div>
                                            <?php if ($retirement['amount_returned'] > 0): ?>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Amount Returned</label>
                                                <p class="text-lg font-semibold text-blue-600">
                                                    <?php echo formatCurrency($retirement['amount_returned'], $req['currency']); ?>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Posted By</label>
                                                <p class="text-gray-900"><?php echo htmlspecialchars($retirement['posted_by_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($retirement['created_at'])); ?></p>
                                            </div>
                                        </div>
                                        <?php if ($retirement['description']): ?>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700">Description</label>
                                            <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($retirement['description'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Retirement Modal -->
    <div id="retirementModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Retirement</h3>
                <form id="retirementForm" method="POST" action="process-retirement.php">
                    <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Retired *</label>
                        <input type="number" name="amount_retired" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Returned (if any)</label>
                        <input type="number" name="amount_returned" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter retirement details..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideRetirementModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Save Retirement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        function showRetirementModal() {
            document.getElementById('retirementModal').classList.remove('hidden');
        }
        
        function hideRetirementModal() {
            document.getElementById('retirementModal').classList.add('hidden');
        }
    </script>
</body>
</html>