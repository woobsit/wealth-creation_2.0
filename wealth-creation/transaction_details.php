<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/models/User.php';
require __DIR__.'/../app/models/TransactionManager.php';
require __DIR__.'/../app/helpers/session_helper.php';
require __DIR__.'/../app/models/PaymentProcessor.php';

// Check if user is already logged in
requireLogin();
$userId = $_SESSION['user_id'];
$db = $databaseObj;
$user = new User($databaseObj);
$staff = $user->getUserStaffDetail($userId);;
$manager = new TransactionManager($databaseObj);

$transaction_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transaction_id) {
    echo '<p class="text-red-600">Transaction ID not provided.</p>';
    exit;
}

$transaction = $manager->getTransactionDetails($transaction_id);

if (!$transaction) {
    echo '<p class="text-red-600">Transaction not found.</p>';
    exit;
}

// Get payment breakdown if it's a shop transaction
$payment_breakdown = [];
if ($transaction['shop_no']) {
    $payment_breakdown = $manager->getPaymentBreakdown(
        $transaction['shop_no'], 
        $transaction['receipt_no'], 
        $transaction['payment_category']
    );
}
?>

<div class="space-y-6">
    <!-- Transaction Header -->
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-lg font-semibold text-gray-900">Transaction #<?php echo $transaction['id']; ?></h4>
                <p class="text-sm text-gray-600">
                    <?php echo date('d/m/Y H:i:s', strtotime($transaction['posting_time'])); ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($transaction['amount_paid']); ?></p>
                <p class="text-sm text-gray-600">Receipt: <?php echo $transaction['receipt_no']; ?></p>
            </div>
        </div>
    </div>

    <!-- Transaction Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h5 class="font-medium text-gray-900 mb-3">Transaction Information</h5>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Date of Payment:</dt>
                    <dd class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($transaction['date_of_payment'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Description:</dt>
                    <dd class="text-sm text-gray-900"><?php echo ucwords(strtolower($transaction['transaction_desc'])); ?></dd>
                </div>
                <?php if ($transaction['shop_no']): ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Shop No:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['shop_no']; ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($transaction['plate_no']): ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Plate No:</dt>
                    <dd class="text-sm text-gray-900"><?php echo strtoupper($transaction['plate_no']); ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($transaction['no_of_tickets']): ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">No of Tickets:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['no_of_tickets']; ?></dd>
                </div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Payment Category:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['payment_category']; ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Income Line:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['income_line']; ?></dd>
                </div>
            </dl>
        </div>

        <div>
            <h5 class="font-medium text-gray-900 mb-3">Account Information</h5>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Debit Account:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['debit_account_desc']; ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Credit Account:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['credit_account_desc']; ?></dd>
                </div>
            </dl>

            <h5 class="font-medium text-gray-900 mb-3 mt-6">Staff Information</h5>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Posted by:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['posting_officer_full_name']; ?></dd>
                </div>
                <?php if ($transaction['remitting_staff']): ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Remitting Staff:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['remitting_staff']; ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($transaction['approving_acct_officer_name']): ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Approved by:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['approving_acct_officer_name']; ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($transaction['verifying_auditor_name']): ?>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-500">Verified by:</dt>
                    <dd class="text-sm text-gray-900"><?php echo $transaction['verifying_auditor_name']; ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <!-- Payment Breakdown -->
    <?php if (!empty($payment_breakdown)): ?>
    <div>
        <h5 class="font-medium text-gray-900 mb-3">Payment Breakdown</h5>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($payment_breakdown as $payment): ?>
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-900"><?php echo $payment['payment_month']; ?></p>
                    <p class="text-lg font-bold text-blue-600">₦<?php echo number_format($payment['amount_paid']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Timeline -->
    <div>
        <h5 class="font-medium text-gray-900 mb-3">Approval Timeline</h5>
        <div class="flow-root">
            <ul class="-mb-8">
                <!-- Posted -->
                <li>
                    <div class="relative pb-8">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        Posted by <span class="font-medium text-gray-900"><?php echo $transaction['posting_officer_full_name']; ?></span>
                                        <time class="text-gray-500"><?php echo $manager->timeElapsed($transaction['posting_time']); ?></time>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Review Status -->
                <?php if ($transaction['leasing_post_status']): ?>
                <li>
                    <div class="relative pb-8">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full <?php echo $transaction['leasing_post_status'] === 'Approved' ? 'bg-green-500' : ($transaction['leasing_post_status'] === 'Declined' ? 'bg-red-500' : 'bg-yellow-500'); ?> flex items-center justify-center ring-8 ring-white">
                                    <?php if ($transaction['leasing_post_status'] === 'Approved'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    <?php elseif ($transaction['leasing_post_status'] === 'Declined'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        Review <?php echo strtolower($transaction['leasing_post_status']); ?>
                                        <?php if ($transaction['leasing_post_approving_officer_name']): ?>
                                            by <span class="font-medium text-gray-900"><?php echo $transaction['leasing_post_approving_officer_name']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($transaction['leasing_post_approval_time']): ?>
                                            <time class="text-gray-500"><?php echo $manager->timeElapsed($transaction['leasing_post_approval_time']); ?></time>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>

                <!-- FC Approval Status -->
                <?php if ($transaction['approval_status']): ?>
                <li>
                    <div class="relative pb-8">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full <?php echo $transaction['approval_status'] === 'Approved' ? 'bg-green-500' : ($transaction['approval_status'] === 'Declined' ? 'bg-red-500' : 'bg-yellow-500'); ?> flex items-center justify-center ring-8 ring-white">
                                    <?php if ($transaction['approval_status'] === 'Approved'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    <?php elseif ($transaction['approval_status'] === 'Declined'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        FC Approval <?php echo strtolower($transaction['approval_status']); ?>
                                        <?php if ($transaction['approving_acct_officer_name']): ?>
                                            by <span class="font-medium text-gray-900"><?php echo $transaction['approving_acct_officer_name']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($transaction['approval_time']): ?>
                                            <time class="text-gray-500"><?php echo $manager->timeElapsed($transaction['approval_time']); ?></time>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>

                <!-- Audit Verification Status -->
                <?php if ($transaction['verification_status']): ?>
                <li>
                    <div class="relative">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full <?php echo $transaction['verification_status'] === 'Verified' ? 'bg-green-500' : ($transaction['verification_status'] === 'Declined' ? 'bg-red-500' : ($transaction['verification_status'] === 'Flagged' ? 'bg-orange-500' : 'bg-yellow-500')); ?> flex items-center justify-center ring-8 ring-white">
                                    <?php if ($transaction['verification_status'] === 'Verified'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    <?php elseif ($transaction['verification_status'] === 'Declined'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    <?php elseif ($transaction['verification_status'] === 'Flagged'): ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        Audit <?php echo strtolower($transaction['verification_status']); ?>
                                        <?php if ($transaction['verifying_auditor_name']): ?>
                                            by <span class="font-medium text-gray-900"><?php echo $transaction['verifying_auditor_name']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($transaction['verification_time']): ?>
                                            <time class="text-gray-500"><?php echo $manager->timeElapsed($transaction['verification_time']); ?></time>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
        <button onclick="closeModal()" 
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Close
        </button>
        
        <?php if (($staff['department'] === 'Accounts' || $staff['department'] === 'FC') && $transaction['approval_status'] === 'Pending'): ?>
            <button onclick="approveTransaction('<?php echo $transaction['id']; ?>'); closeModal();" 
                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Approve
            </button>
            <button onclick="declineTransaction('<?php echo $transaction['id']; ?>'); closeModal();" 
                    class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                Decline
            </button>
        <?php endif; ?>
        
        <?php if ($staff['department'] === 'Audit/Inspections' && $transaction['approval_status'] === 'Approved' && $transaction['verification_status'] === 'Pending'): ?>
            <button onclick="verifyTransaction('<?php echo $transaction['id']; ?>'); closeModal();" 
                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Verify
            </button>
            <button onclick="flagTransaction('<?php echo $transaction['id']; ?>'); closeModal();" 
                    class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-md hover:bg-orange-700">
                Flag
            </button>
        <?php endif; ?>
    </div>
</div>