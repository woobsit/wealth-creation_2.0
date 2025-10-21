<?php
require_once 'config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters long';
        $messageType = 'error';
    } else {
        // Verify current password
        if (hash('sha256', $currentPassword) === $user['password']) {
            // Update password
            $userModel = new User();
            $result = $userModel->update($user['id'], ['password' => $newPassword]);
            
            if ($result['success']) {
                $message = 'Password updated successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to update password';
                $messageType = 'error';
            }
        } else {
            $message = 'Current password is incorrect';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Profile Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8 pt-24">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
                        <p class="text-gray-600 mt-1">Manage your account settings and preferences</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mx-6 mt-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Profile Information -->
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                        <p class="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                        <p class="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                        <p class="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                                            <?php echo htmlspecialchars($user['department'] ?? 'Not assigned'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">User Level</label>
                                        <p class="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                                            Level <?php echo $user['level']; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Roles</label>
                                        <p class="text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                                            <?php echo htmlspecialchars($user['has_roles']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>
                                <form method="POST" class="space-y-4">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Current Password *
                                        </label>
                                        <input type="password" id="current_password" name="current_password" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            New Password *
                                        </label>
                                        <input type="password" id="new_password" name="new_password" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm New Password *
                                        </label>
                                        <input type="password" id="confirm_password" name="confirm_password" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <button type="submit" 
                                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>