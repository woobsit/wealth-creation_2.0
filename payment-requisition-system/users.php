<?php
require_once 'config/config.php';
requireLogin();

// Check if user has admin privileges
if ($_SESSION['level'] < 4 && strpos($_SESSION['department'], 'IT/E-Business') === false) {
    redirect('index.php');
}

$user = new User();
$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $data = [
            'full_name' => sanitizeInput($_POST['full_name']),
            'email' => sanitizeInput($_POST['email']),
            'password' => $_POST['password'],
            'level' => intval($_POST['level']),
            'department' => sanitizeInput($_POST['department']),
            'has_roles' => sanitizeInput($_POST['has_roles']),
            'status' => sanitizeInput($_POST['status'])
        ];
        
        $result = $user->create($data);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if ($action === 'update') {
        $userId = intval($_POST['user_id']);
        $data = [
            'full_name' => sanitizeInput($_POST['full_name']),
            'email' => sanitizeInput($_POST['email']),
            'level' => intval($_POST['level']),
            'department' => sanitizeInput($_POST['department']),
            'has_roles' => sanitizeInput($_POST['has_roles']),
            'status' => sanitizeInput($_POST['status'])
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        $result = $user->update($userId, $data);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if ($action === 'delete') {
        $userId = intval($_POST['user_id']);
        $result = $user->delete($userId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Get all users
$users = $user->getAll();
$departments = ['Finance', 'Human Resources', 'IT', 'Operations', 'Marketing', 'Legal', 'Procurement', 'Administration', 'Accounts', 'Audit'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - User Management</title>
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
                                <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                                <p class="text-gray-600 mt-1">Manage system users and permissions</p>
                            </div>
                            <button onclick="showUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Add User
                            </button>
                        </div>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $u): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($u['full_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($u['email']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars(isset($u['department']) ? $u['department'] : 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            Level <?php echo $u['level']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($u['has_roles']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($u['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                    <button onclick="deleteUser(<?php echo $u['id']; ?>)" 
                                                            class="text-red-600 hover:text-red-900" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add User</h3>
                <form id="userForm" method="POST">
                    <input type="hidden" id="modalAction" name="action" value="create">
                    <input type="hidden" id="modalUserId" name="user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" id="modalFullName" name="full_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" id="modalEmail" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" id="modalPassword" name="password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password (for updates)</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select id="modalDepartment" name="department"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">User Level</label>
                        <select id="modalUserLevel" name="level"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="1">Level 1 - User</option>
                            <option value="2">Level 2 - Senior User</option>
                            <option value="3">Level 3 - Department Head</option>
                            <option value="4">Level 4 - Manager</option>
                            <option value="5">Level 5 - Executive</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
                        <select id="modalRoles" name="has_roles"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="user">User</option>
                            <option value="user,approver">User & Approver</option>
                            <option value="user,approver,admin">User, Approver & Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="modalStatus" name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideUserModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        function showUserModal() {
            document.getElementById('modalTitle').textContent = 'Add User';
            document.getElementById('modalAction').value = 'create';
            document.getElementById('modalPassword').required = true;
            document.getElementById('userForm').reset();
            document.getElementById('userModal').classList.remove('hidden');
        }
        
        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('modalAction').value = 'update';
            document.getElementById('modalUserId').value = user.id;
            document.getElementById('modalFullName').value = user.full_name;
            document.getElementById('modalEmail').value = user.email;
            document.getElementById('modalDepartment').value = user.department || '';
            document.getElementById('modalUserLevel').value = user.level;
            document.getElementById('modalRoles').value = user.has_roles;
            document.getElementById('modalStatus').value = user.status;
            document.getElementById('modalPassword').required = false;
            document.getElementById('userModal').classList.remove('hidden');
        }
        
        function hideUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>