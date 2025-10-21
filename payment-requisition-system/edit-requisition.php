<?php
require_once 'config/config.php';
requireLogin();

$requisition = new Requisition();
$id = $_GET['id'] ?? 0;

$req = $requisition->getById($id);
if (!$req || $req['created_by'] != $_SESSION['user_id'] || $req['status'] !== 'draft') {
    redirect('requisitions.php');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'description' => sanitizeInput($_POST['description']),
        'amount' => floatval($_POST['amount']),
        'currency' => sanitizeInput($_POST['currency']),
        'department' => sanitizeInput($_POST['department']),
        'category' => sanitizeInput($_POST['category']),
        'priority' => sanitizeInput($_POST['priority']),
        'justification' => sanitizeInput($_POST['justification']),
        'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
        'status' => isset($_POST['save_draft']) ? 'draft' : 'pending'
    ];
    
    $result = $requisition->update($id, $data);
    
    if ($result['success']) {
        $message = 'Requisition ' . ($data['status'] === 'draft' ? 'updated' : 'submitted') . ' successfully!';
        $messageType = 'success';
        
        if ($data['status'] === 'pending') {
            redirect('requisitions.php');
        }
    } else {
        $message = $result['message'] ?? 'Failed to update requisition';
        $messageType = 'error';
    }
}

$departments = ['Finance', 'Human Resources', 'IT', 'Operations', 'Marketing', 'Legal', 'Procurement', 'Administration'];
$categories = ['Office Supplies', 'Equipment', 'Travel', 'Training', 'Maintenance', 'Software/Licenses', 'Professional Services', 'Utilities', 'Other'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Edit Requisition</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Edit Requisition</h1>
                        <p class="text-gray-600 mt-1">Update your payment requisition details</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mx-6 mt-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Requisition Title *
                                </label>
                                <input type="text" id="title" name="title" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo htmlspecialchars($req['title']); ?>">
                            </div>

                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                    Category *
                                </label>
                                <select id="category" name="category" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo $req['category'] === $category ? 'selected' : ''; ?>>
                                            <?php echo $category; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Amount and Department -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Amount *
                                </label>
                                <input type="number" id="amount" name="amount" required step="0.01" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo $req['amount']; ?>">
                            </div>

                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                    Currency
                                </label>
                                <select id="currency" name="currency"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="NGN" <?php echo $req['currency'] === 'NGN' ? 'selected' : ''; ?>>NGN (₦)</option>
                                    <option value="USD" <?php echo $req['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $req['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo $req['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                </select>
                            </div>

                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                                    Department *
                                </label>
                                <select id="department" name="department" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept; ?>" <?php echo $req['department'] === $dept ? 'selected' : ''; ?>>
                                            <?php echo $dept; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Priority and Due Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                                    Priority Level
                                </label>
                                <select id="priority" name="priority"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="low" <?php echo $req['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $req['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $req['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo $req['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>

                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Required By
                                </label>
                                <input type="date" id="due_date" name="due_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       value="<?php echo $req['due_date']; ?>">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description *
                            </label>
                            <textarea id="description" name="description" required rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($req['description']); ?></textarea>
                        </div>

                        <!-- Justification -->
                        <div>
                            <label for="justification" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Justification *
                            </label>
                            <textarea id="justification" name="justification" required rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($req['justification']); ?></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                            <button type="submit" name="submit"
                                    class="flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Submit Requisition
                            </button>
                            
                            <button type="submit" name="save_draft"
                                    class="flex items-center justify-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <i class="fas fa-save mr-2"></i>
                                Save as Draft
                            </button>

                            <a href="requisitions.php"
                               class="flex items-center justify-center px-6 py-3 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>