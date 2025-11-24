<?php
require_once __DIR__ . '/../helpers/DB.php';

class PostingModel {

    private $db;

    public function __construct() {
        $this->db = DB::conn();
    }

    public function getPendingTransactions($role) {
        $statusFilter = match($role) {
            'staff' => 'Pending',
            'fc_head' => 'Staff-Approved',
            'audit' => 'FC-Approved',
            default => 'Pending'
        };

        $sql = "SELECT 
                    ag.remit_id,
                    ag.date_of_payment,
                    ag.transaction_desc,
                    ag.receipt_no,
                    da.acct_alias as debit_account,
                    ca.acct_alias as credit_account,
                    ag.amount_paid,
                    ag.approval_status,
                    ag.created_at
                FROM account_general_transaction_new ag
                LEFT JOIN accounts da ON ag.debit_account = da.acct_id
                LEFT JOIN accounts ca ON ag.credit_account = ca.acct_id
                WHERE ag.approval_status = ?
                ORDER BY ag.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$statusFilter]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionDetail($remit_id) {
        $sql = "SELECT 
                    ag.remit_id,
                    ag.date_of_payment,
                    ag.transaction_desc,
                    ag.receipt_no,
                    ag.remitting_customer,
                    da.acct_code as debit_code,
                    da.acct_alias as debit_account,
                    ca.acct_code as credit_code,
                    ca.acct_alias as credit_account,
                    ag.amount_paid,
                    ag.approval_status,
                    ag.created_at
                FROM account_general_transaction_new ag
                LEFT JOIN accounts da ON ag.debit_account = da.acct_id
                LEFT JOIN accounts ca ON ag.credit_account = ca.acct_id
                WHERE ag.remit_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$remit_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function staffApprove($remit_id) {
        try {
            $sql = "UPDATE account_general_transaction_new 
                    SET approval_status = 'Staff-Approved' 
                    WHERE remit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remit_id]);
            
            $this->logAudit($remit_id, 'Staff Review', 'Approved');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function staffReject($remit_id, $reason) {
        try {
            $sql = "UPDATE account_general_transaction_new 
                    SET approval_status = 'Rejected' 
                    WHERE remit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remit_id]);
            
            $this->logAudit($remit_id, 'Staff Review', 'Rejected', $reason);
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function fcApprove($remit_id) {
        try {
            $sql = "UPDATE account_general_transaction_new 
                    SET approval_status = 'FC-Approved' 
                    WHERE remit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remit_id]);
            
            $this->logAudit($remit_id, 'FC Approval', 'Approved');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function fcReject($remit_id, $reason) {
        try {
            $sql = "UPDATE account_general_transaction_new 
                    SET approval_status = 'Rejected' 
                    WHERE remit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remit_id]);
            
            $this->logAudit($remit_id, 'FC Approval', 'Rejected', $reason);
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function auditApprove($remit_id) {
        try {
            $sql = "UPDATE account_general_transaction_new 
                    SET approval_status = 'Approved' 
                    WHERE remit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remit_id]);
            
            $this->logAudit($remit_id, 'Audit Approval', 'Approved');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function auditReject($remit_id, $reason) {
        try {
            $sql = "UPDATE account_general_transaction_new 
                    SET approval_status = 'Rejected' 
                    WHERE remit_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remit_id]);
            
            $this->logAudit($remit_id, 'Audit Approval', 'Rejected', $reason);
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function logAudit($remit_id, $step, $action, $note = '') {
        $sql = "INSERT INTO audit_log (entity, entity_id, action, payload, user_name, created_at)
                VALUES ('transaction', ?, ?, ?, 'system', CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $remit_id,
            "$step - $action",
            json_encode(['note' => $note, 'timestamp' => date('Y-m-d H:i:s')])
        ]);
    }
}
?>