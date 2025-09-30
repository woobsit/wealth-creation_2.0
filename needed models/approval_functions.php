<?php
ob_start();
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/PaymentProcessor.php';
require_once 'helpers/session_helper.php';
require_once 'models/TransactionManager.php';

// Check if user is logged in
requireLogin();
$userId = getLoggedInUserId();

$db = new Database();
$user = new User();

$currentUser = $user->getUserById($userId);
//$department = $user->getDepartmentByUserIdstring($userId);
$staff = $user->getUserStaffDetail($userId);
$manager = new TransactionManager();

// Set content type for JSON response
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // FC Approve Function
    if (isset($_POST['approve'])) {
        $transaction_id = $_POST['approve'];
        $result = $manager->approveTransaction($transaction_id, $staff['user_id'], $staff['full_name'], $staff['department']);
        
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'status' => $result['success'] ? 'success' : 'error'
        ];
    }
    
    // FC Decline Function
    elseif (isset($_POST['decline'])) {
        $transaction_id = $_POST['decline'];
        $reason = $_POST['reason'] ?? '';
        $result = $manager->declineTransaction($transaction_id, $staff['user_id'], $staff['full_name'], $staff['department'], $reason);
        
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'status' => $result['success'] ? 'success' : 'error'
        ];
    }
    
    // Audit Approve (Verify) Function
    elseif (isset($_POST['verify'])) {
        $transaction_id = $_POST['verify'];
        
        // For audit department, we use the approve function but it updates verification_status
        $staff_audit = $staff;
        $staff_audit['department'] = 'Audit/Inspections';
        
        $result = $manager->approveTransaction($transaction_id, $staff['user_id'], $staff['full_name'], 'Audit/Inspections');
        
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'status' => $result['success'] ? 'success' : 'error'
        ];
    }
    
    // Audit Decline Function
    elseif (isset($_POST['audit_decline'])) {
        $transaction_id = $_POST['audit_decline'];
        $reason = $_POST['reason'] ?? '';
        
        $result = $manager->declineTransaction($transaction_id, $staff['user_id'], $staff['full_name'], 'Audit/Inspections', $reason);
        
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'status' => $result['success'] ? 'success' : 'error'
        ];
    }
    
    // Flag Transaction Function
    elseif (isset($_POST['flag'])) {
        $transaction_id = $_POST['flag'];
        $flag_reason = $_POST['flag_reason'] ?? '';
        
        try {
            $this->db->beginTransaction();
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET verification_status = 'Flagged',
                    flag_status = 'Flagged',
                    verifying_auditor_id = :auditor_id,
                    verifying_auditor_name = :auditor_name,
                    verification_time = NOW(),
                    flag_reason = :flag_reason
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':auditor_id', $staff['user_id']);
            $this->db->bind(':auditor_name', $staff['full_name']);
            $this->db->bind(':flag_reason', $flag_reason);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            $response = [
                'success' => true,
                'message' => 'Transaction flagged successfully',
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            $response = [
                'success' => false,
                'message' => 'Error flagging transaction: ' . $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
    
    // Bulk Operations
    elseif (isset($_POST['bulk_action'])) {
        $action = $_POST['bulk_action'];
        $transaction_ids = json_decode($_POST['transaction_ids'], true);
        
        if ($action === 'approve') {
            $result = $manager->bulkApproveTransactions($transaction_ids, $staff['user_id'], $staff['full_name'], $staff['department']);
        } elseif ($action === 'decline') {
            // Implement bulk decline if needed
            $result = ['success' => false, 'message' => 'Bulk decline not implemented'];
        } else {
            $result = ['success' => false, 'message' => 'Invalid bulk action'];
        }
        
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'status' => $result['success'] ? 'success' : 'error'
        ];
    }
}

echo json_encode($response);
?>