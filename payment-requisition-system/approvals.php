<?php
require_once 'config/config.php';
requireLogin();

$requisition = new Requisition();
$message = '';
$messageType = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $requisitionId = $_POST['requisition_id'];
    $approvalLevel = $_POST['approval_level'];
    $comments = sanitizeInput(isset($_POST['comments']) ? $_POST['comments'] : '');
    
    if ($action === 'approve') {
        $result = $requisition->approve($requisitionId, $approvalLevel, $comments);
    } else {
        $result = $requisition->reject($requisitionId, $approvalLevel, $comments);
    }
    
    if ($result['success']) {
        $message = 'Requisition ' . $action . 'd successfully!';
        $messageType = 'success';
    } else {
        $message = isset($result['message']) ? $result['message'] : 'Failed to process requisition';
        $messageType = 'error';
    }
}

// Get pending approvals for current user
$pendingApprovals = $requisition->getPendingApprovals($_SESSION['user_id'], $_SESSION['level']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Pending Approvals</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Pending Approvals</h1>
                        <p class="text-gray-600 mt-1">Review and approve payment requisitions</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mx-6 mt-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($pendingApprovals)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-check-circle text-4xl mb-4 text-gray-300"></i>
                                            <p>No pending approvals</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingApprovals as $req): ?>
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
                                                ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $priorityClass; ?>">
                                                    <?php echo ucfirst($req['priority']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($req['created_by_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($req['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button onclick="showApprovalModal(<?php echo $req['id']; ?>, <?php echo $req['current_approval_level']; ?>, 'approve')" 
                                                            class="text-green-600 hover:text-green-900" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button onclick="showApprovalModal(<?php echo $req['id']; ?>, <?php echo $req['current_approval_level']; ?>, 'reject')" 
                                                            class="text-red-600 hover:text-red-900" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <a href="requisition-details.php?id=<?php echo $req['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
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

    <!-- Approval Modal -->
    <div id="approvalModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4"></h3>
                <form method="POST">
                    <input type="hidden" id="modalRequisitionId" name="requisition_id">
                    <input type="hidden" id="modalApprovalLevel" name="approval_level">
                    <input type="hidden" id="modalAction" name="action">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                        <textarea name="comments" rows="4" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter your comments..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideApprovalModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" id="modalSubmitBtn"
                                class="px-4 py-2 rounded-lg text-white">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        function showApprovalModal(requisitionId, approvalLevel, action) {
            document.getElementById('modalRequisitionId').value = requisitionId;
            document.getElementById('modalApprovalLevel').value = approvalLevel;
            document.getElementById('modalAction').value = action;
            
            const modal = document.getElementById('approvalModal');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('modalSubmitBtn');
            
            if (action === 'approve') {
                title.textContent = 'Approve Requisition';
                submitBtn.textContent = 'Approve';
                submitBtn.className = 'px-4 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700';
            } else {
                title.textContent = 'Reject Requisition';
                submitBtn.textContent = 'Reject';
                submitBtn.className = 'px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700';
            }
            
            modal.classList.remove('hidden');
        }
        
        function hideApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
        }
    </script>
</body>
</html>