<?php
// require_once 'Database.php';

class TransactionManager {
    private $db;

    public function __construct($databaseObj) {
        $this->db = $databaseObj;
    }
    
    // public function __construct($db = null) {
    //     $this->db = $db !== null ? $db : new Database();
    // }

    /**
     * Calculate unposted balance for an officer
     */
    public function calculateUnpostedBalance($officer_id, $current_date, $category = 'Other Collection') {
        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as amount_posted
            FROM account_general_transaction_new
            WHERE posting_officer_id = :officer_id
            AND payment_category = :category
            AND date_of_payment = :current_date
        ");
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':current_date', $current_date);
        $posted = $this->db->single();

        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as amount_remitted
            FROM cash_remittance
            WHERE remitting_officer_id = :officer_id
            AND category = :category
            AND date = :current_date
        ");
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':current_date', $current_date);
        $remitted = $this->db->single();

        //return $remitted['amount_remitted'] - $posted['amount_posted'];
        return [
            'amount_posted' => $posted['amount_posted'],
            'amount_remitted' => $remitted['amount_remitted'],
            'unposted' => $remitted['amount_remitted'] - $posted['amount_posted'],
            'remit_id' => isset($remitted['remit_id']) ? $remitted['remit_id'] : '',
            'date' => isset($remitted['date']) ? $remitted['date'] : ''
        ];
    }
    
    /**
     * Get transactions with pagination and filtering
     */
    public function getTransactions($page = 1, $per_page = 20, $date_from = null, $date_to = null, $status_filter = null, $staff_filter = null) {
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = [];
        $params = [];
        
        // Base condition for pending transactions
        if ($status_filter === 'pending') {
            $where_conditions[] = "leasing_post_status = 'Pending'";
        } elseif ($status_filter === 'fc_pending') {
            $where_conditions[] = "approval_status = 'Pending'";
        } elseif ($status_filter === 'audit_pending') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Pending'";
        } elseif ($status_filter === 'declined') {
            $where_conditions[] = "(approval_status = 'Declined' OR verification_status = 'Declined' OR leasing_post_status = 'Declined')";
        } elseif ($status_filter === 'approved') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Verified'";
        }
        
        // Staff filtering
        if ($staff_filter) {
            $where_conditions[] = "posting_officer_id = :staff_filter";
            $params[':staff_filter'] = $staff_filter;
        }
        
        // Date filtering
        if ($date_from && $date_to) {
            $where_conditions[] = "date_of_payment BETWEEN :date_from AND :date_to";
            $params[':date_from'] = $date_from;
            $params[':date_to'] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $this->db->query("
            SELECT t.*, 
                   da.acct_desc as debit_account_desc,
                   ca.acct_desc as credit_account_desc,
                   s.full_name as posting_officer_full_name
            FROM account_general_transaction_new t
            LEFT JOIN accounts da ON t.debit_account = da.acct_id
            LEFT JOIN accounts ca ON t.credit_account = ca.acct_id
            LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
            {$where_clause}
            ORDER BY t.date_of_payment DESC, t.posting_time DESC
            LIMIT :offset, :per_page
        ");
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':offset', $offset);
        $this->db->bind(':per_page', $per_page);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get total count for pagination
     */
    public function getTransactionCount($date_from = null, $date_to = null, $status_filter = null, $staff_filter = null) {
        $where_conditions = [];
        $params = [];
        
        if ($status_filter === 'pending') {
            $where_conditions[] = "leasing_post_status = 'Pending'";
        } elseif ($status_filter === 'fc_pending') {
            $where_conditions[] = "approval_status = 'Pending'";
        } elseif ($status_filter === 'audit_pending') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Pending'";
        } elseif ($status_filter === 'declined') {
            $where_conditions[] = "(approval_status = 'Declined' OR verification_status = 'Declined' OR leasing_post_status = 'Declined')";
        } elseif ($status_filter === 'approved') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Verified'";
        }
        
        if ($staff_filter) {
            $where_conditions[] = "posting_officer_id = :staff_filter";
            $params[':staff_filter'] = $staff_filter;
        }
        
        if ($date_from && $date_to) {
            $where_conditions[] = "date_of_payment BETWEEN :date_from AND :date_to";
            $params[':date_from'] = $date_from;
            $params[':date_to'] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $this->db->query("SELECT COUNT(*) as total FROM account_general_transaction_new {$where_clause}");
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result['total'];
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        // Pending posts
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE leasing_post_status = 'Pending'
        ");
        $pending_posts = $this->db->single();
        
        // Pending FC approvals
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE approval_status = 'Pending'
        ");
        $pending_fc = $this->db->single();
        
        // Pending audit verifications
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE approval_status = 'Approved' AND verification_status = 'Pending'
        ");
        $pending_audit = $this->db->single();
        
        // Declined transactions
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE approval_status = 'Declined' OR verification_status = 'Declined' OR leasing_post_status = 'Declined'
        ");
        $declined = $this->db->single();
        
        return [
            'pending_posts' => $pending_posts['count'],
            'pending_fc_approvals' => $pending_fc['count'],
            'pending_audit_verifications' => $pending_audit['count'],
            'declined_transactions' => $declined['count']
        ];
    }
    
    /**
     * Approve transaction
     */
    public function approveTransaction($transaction_id, $approver_id, $approver_name, $department, $level) {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            if ($department === 'Accounts' && $level === 'fc') {
                // FC/Accounts approval
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET approval_status = 'Approved',
                        approving_acct_officer_id = :approver_id,
                        approving_acct_officer_name = :approver_name,
                        approval_time = :approval_time
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':approval_time', $now);
                $this->db->bind(':transaction_id', $transaction_id);
                
            } elseif ($department === 'Accounts' && $level != 'fc') {
                // Accounts Review approval
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET leasing_post_status = 'Approved',
                        leasing_post_approving_officer_id = :approver_id,
                        leasing_post_approving_officer_name = :approver_name,
                        leasing_post_approval_time = :leasing_approval_time
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':leasing_approval_time', $now);
                $this->db->bind(':transaction_id', $transaction_id);
                
            } elseif ($department === 'Audit/Inspections') {
                // Audit verification
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET verification_status = 'Verified',
                        verifying_auditor_id = :approver_id,
                        verifying_auditor_name = :approver_name,
                        verification_time = :verification_time
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':verification_time', $now);
                $this->db->bind(':transaction_id', $transaction_id);
            }
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction approved successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error approving transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Decline transaction
     */
    public function declineTransaction($transaction_id, $approver_id, $approver_name, $department, $level, $reason = '') {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            if ($department === 'Accounts' && $level === 'fc') {
                // FC/Accounts decline
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET approval_status = 'Declined',
                        approving_acct_officer_id = :approver_id,
                        approving_acct_officer_name = :approver_name,
                        approval_time = :approval_time,
                        comment = :reason
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':approval_time', $now);
                $this->db->bind(':reason', $reason);
                $this->db->bind(':transaction_id', $transaction_id);
                
            } elseif ($department === 'Accounts' && $level != 'fc') {
                // FC/Accounts decline
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET leasing_post_status = 'Declined',
                        leasing_post_approving_officer_id = :approver_id,
                        leasing_post_approving_officer_name = :approver_name,
                        approval_time = :approval_time,
                        comment = :reason
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':approval_time', $now);
                $this->db->bind(':reason', $reason);
                $this->db->bind(':transaction_id', $transaction_id);
                
            } elseif ($department === 'Audit/Inspections') {
                // Audit decline
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET verification_status = 'Declined',
                        verifying_auditor_id = :approver_id,
                        verifying_auditor_name = :approver_name,
                        verification_time = :verification_time,
                        decline_reason = :reason
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':verification_time', $now);
                $this->db->bind(':reason', $reason);
                $this->db->bind(':transaction_id', $transaction_id);
            }
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction declined successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error declining transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Bulk approve transactions
     */
    // public function bulkApproveTransactions($transaction_ids, $approver_id, $approver_name, $department) {
    //     $this->db->beginTransaction();
        
    //     try {
    //         $now = date('Y-m-d H:i:s');
    //         $success_count = 0;
            
    //         foreach ($transaction_ids as $transaction_id) {
    //             if ($department === 'Accounts' || $department === 'FC') {
    //                 $this->db->query("
    //                     UPDATE account_general_transaction_new 
    //                     SET approval_status = 'Approved',
    //                         approving_acct_officer_id = :approver_id,
    //                         approving_acct_officer_name = :approver_name,
    //                         approval_time = :approval_time
    //                     WHERE id = :transaction_id
    //                     AND approval_status = 'Pending'
    //                 ");
    //             } elseif ($department === 'Audit/Inspections') {
    //                 $this->db->query("
    //                     UPDATE account_general_transaction_new 
    //                     SET verification_status = 'Verified',
    //                         verifying_auditor_id = :approver_id,
    //                         verifying_auditor_name = :approver_name,
    //                         verification_time = :verification_time
    //                     WHERE id = :transaction_id
    //                     AND verification_status = 'Pending'
    //                 ");
    //             }
                
    //             $this->db->bind(':approver_id', $approver_id);
    //             $this->db->bind(':approver_name', $approver_name);
    //             $this->db->bind(':approval_time', $now);
    //             $this->db->bind(':verification_time', $now);
    //             $this->db->bind(':transaction_id', $transaction_id);
                
    //             if ($this->db->execute()) {
    //                 $success_count++;
    //             }
    //         }
            
    //         $this->db->endTransaction();
            
    //         return [
    //             'success' => true, 
    //             'message' => "{$success_count} transactions approved successfully",
    //             'count' => $success_count
    //         ];
            
    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         return ['success' => false, 'message' => 'Error in bulk approval: ' . $e->getMessage()];
    //     }
    // }
    // public function bulkApproveTransactions($transaction_ids, $approver_id, $approver_name, $department) {
    //     $this->db->beginTransaction();

    //     try {
    //         $now = date('Y-m-d H:i:s');
    //         $success_count = 0;
    //         $failed_count  = 0;

    //         $successful_ids = [];
    //         $failed_ids = [];
    //         $log_messages = [];

    //         foreach ($transaction_ids as $transaction_id) {
    //             $log_messages[] = "Processing transaction ID: {$transaction_id} by department: {$department}";

    //             if ($department === 'Accounts' || $department === 'FC') {
    //                 $this->db->query("
    //                     UPDATE account_general_transaction_new 
    //                     SET approval_status = 'Approved',
    //                         approving_acct_officer_id = :approver_id,
    //                         approving_acct_officer_name = :approver_name,
    //                         approval_time = :approval_time
    //                     WHERE id = :transaction_id
    //                     AND approval_status = 'Pending'
    //                 ");

    //                 $this->db->bind(':approver_id', $approver_id);
    //                 $this->db->bind(':approver_name', $approver_name);
    //                 $this->db->bind(':approval_time', $now);
    //                 $this->db->bind(':transaction_id', $transaction_id);
    //             } 
    //             elseif ($department === 'Audit/Inspections') {
    //                 $this->db->query("
    //                     UPDATE account_general_transaction_new 
    //                     SET verification_status = 'Verified',
    //                         verifying_auditor_id = :approver_id,
    //                         verifying_auditor_name = :approver_name,
    //                         verification_time = :verification_time
    //                     WHERE id = :transaction_id
    //                     AND verification_status = 'Pending'
    //                 ");

    //                 $this->db->bind(':approver_id', $approver_id);
    //                 $this->db->bind(':approver_name', $approver_name);
    //                 $this->db->bind(':verification_time', $now);
    //                 $this->db->bind(':transaction_id', $transaction_id);
    //             } 
    //             else {
    //                 $log_messages[] = "Unknown department '{$department}' — skipped transaction {$transaction_id}";
    //                 $failed_ids[] = $transaction_id;
    //                 $failed_count++;
    //                 continue;
    //             }

    //             if ($this->db->execute()) {
    //                 $success_count++;
    //                 $successful_ids[] = $transaction_id;
    //                 $log_messages[] = "Transaction {$transaction_id} approved successfully.";
    //             } else {
    //                 $failed_count++;
    //                 $failed_ids[] = $transaction_id;
    //                 $log_messages[] = "Failed to approve transaction {$transaction_id}.";
    //             }
    //         }

    //         $this->db->endTransaction();

    //         foreach ($log_messages as $line) {
    //             error_log($line);
    //         }

    //         // Build detailed JSON response
    //         return [
    //             'success'        => true,
    //             'message'        => "{$success_count} transactions approved successfully, {$failed_count} failed.",
    //             'count'          => $success_count,
    //             'failed_count'   => $failed_count,
    //             'successful_ids' => $successful_ids,
    //             'failed_ids'     => $failed_ids,
    //             'log'            => $log_messages
    //         ];

    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         error_log("🔥 Bulk approval error: " . $e->getMessage());

    //         return [
    //             'success' => false,
    //             'message' => 'Error in bulk approval: ' . $e->getMessage(),
    //             'successful_ids' => isset($successful_ids) ? $successful_ids : [],
    //             'failed_ids' => isset($failed_ids) ? $failed_ids : []
    //         ];
    //     }
    // }
    // public function bulkApproveTransactions($transaction_ids, $approver_id, $approver_name, $department) {
    //     $this->db->beginTransaction();

    //     try {
    //         $now = date('Y-m-d H:i:s');
    //         $success_count = 0;
    //         $failed_ids = [];
    //         $log_messages = [];

    //         foreach ($transaction_ids as $transaction_id) {
    //             $log_messages[] = "Processing ID {$transaction_id} ({$department})";

    //             if ($department === 'Accounts' || $department === 'FC') {
    //                 $sql = "
    //                     UPDATE account_general_transaction_new 
    //                     SET approval_status = 'Approved',
    //                         approving_acct_officer_id = :approver_id,
    //                         approving_acct_officer_name = :approver_name,
    //                         approval_time = :approval_time
    //                     WHERE id = :transaction_id
    //                     AND approval_status = 'Pending'
    //                 ";
    //             } elseif ($department === 'Audit/Inspections') {
    //                 $sql = "
    //                     UPDATE account_general_transaction_new 
    //                     SET verification_status = 'Verified',
    //                         verifying_auditor_id = :approver_id,
    //                         verifying_auditor_name = :approver_name,
    //                         verification_time = :verification_time
    //                     WHERE id = :transaction_id
    //                     AND verification_status = 'Pending'
    //                 ";
    //             } else {
    //                 $failed_ids[] = $transaction_id;
    //                 continue;
    //             }

    //             // Prepare & bind
    //             $this->db->query($sql);
    //             $this->db->bind(':approver_id', $approver_id);
    //             $this->db->bind(':approver_name', $approver_name);
    //             $this->db->bind(':transaction_id', (int)$transaction_id);

    //             if ($department === 'Accounts' || $department === 'FC') {
    //                 $this->db->bind(':approval_time', $now);
    //             } else {
    //                 $this->db->bind(':verification_time', $now);
    //             }

    //             // Execute
    //             $exec_result = $this->db->execute();
    //             $affected = $this->db->rowCount();

    //             // Log internal check
    //             $log_messages[] = "Transaction {$transaction_id}: exec_result=" . json_encode($exec_result) . ", affected={$affected}";

    //             if ($affected > 0) {
    //                 $success_count++;
    //             } else {
    //                 $failed_ids[] = $transaction_id;
    //             }
    //         }

    //         $this->db->endTransaction();

    //         foreach ($log_messages as $msg) {
    //             error_log($msg);
    //         }

    //         return [
    //             'success'       => true,
    //             'message'       => "{$success_count} transaction(s) approved successfully",
    //             'count'         => $success_count,
    //             'failed_count'  => count($failed_ids),
    //             'failed_ids'    => $failed_ids
    //         ];

    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         error_log("🔥 Bulk approval error: " . $e->getMessage());
    //         return ['success' => false, 'message' => 'Error in bulk approval: ' . $e->getMessage()];
    //     }
    // }
    // public function bulkApproveTransactions($transaction_ids, $approver_id, $approver_name, $department, $level) {
    //     $this->db->beginTransaction();

    //     try {
    //         $now = date('Y-m-d H:i:s');
    //         $success_count = 0;
    //         $failed_ids = [];
    //         $log_messages = [];

    //         foreach ($transaction_ids as $transaction_id) {
    //             $log_messages[] = "Processing ID {$transaction_id} ({$department})";

    //             if ($department === 'Accounts' && $level === 'fc') {
    //                 $sql = "
    //                     UPDATE account_general_transaction_new 
    //                     SET approval_status = 'Approved',
    //                         approving_acct_officer_id = :approver_id,
    //                         approving_acct_officer_name = :approver_name,
    //                         approval_time = :approval_time
    //                     WHERE id = :transaction_id
    //                 ";
    //             } elseif ($department === 'Accounts' && $level != 'fc') {
    //                 $sql = "
    //                     UPDATE account_general_transaction_new 
    //                     SET leasing_post_status = 'Approved',
    //                         leasing_post_approving_officer_id = :approver_id,
    //                         leasing_post_approving_officer_name = :approver_name,
    //                         leasing_post_approval_time = :approval_time
    //                     WHERE id = :transaction_id
    //                 ";
    //             } elseif ($department === 'Audit/Inspections') {
    //                 $sql = "
    //                     UPDATE account_general_transaction_new 
    //                     SET verification_status = 'Verified',
    //                         verifying_auditor_id = :approver_id,
    //                         verifying_auditor_name = :approver_name,
    //                         verification_time = :verification_time
    //                     WHERE id = :transaction_id
    //                 ";
    //             } else {
    //                 $failed_ids[] = $transaction_id;
    //                 continue;
    //             }

    //             // Prepare and bind parameters safely
    //             $this->db->query($sql);
    //             $this->db->bind(':approver_id', $approver_id);
    //             $this->db->bind(':approver_name', $approver_name);
    //             $this->db->bind(':transaction_id', $transaction_id, PDO::PARAM_STR);

    //             if ($department === 'Accounts' && $level === 'fc') {
    //                 $this->db->bind(':approval_time', $now);
    //             }
    //             if ($department === 'Accounts' && $level != 'fc') {
    //                 $this->db->bind(':approval_time', $now);
    //             } else {
    //                 $this->db->bind(':verification_time', $now);
    //             }

    //             // Execute
    //             $exec_result = $this->db->execute();
    //             $affected = $this->db->rowCount();

    //             // Log the outcome
    //             $log_messages[] = "Transaction {$transaction_id}: exec_result=" . json_encode($exec_result) . ", affected={$affected}";

    //             if ($affected > 0) {
    //                 $success_count++;
    //             } else {
    //                 $failed_ids[] = $transaction_id;
    //             }
    //         }

    //         $this->db->endTransaction();

    //         foreach ($log_messages as $msg) {
    //             error_log($msg);
    //         }

    //         return [
    //             'success'       => true,
    //             'message'       => "{$success_count} transaction(s) approved successfully",
    //             'count'         => $success_count,
    //             'failed_count'  => count($failed_ids),
    //             'failed_ids'    => $failed_ids
    //         ];

    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         error_log("Bulk approval error: " . $e->getMessage());
    //         return [
    //             'success' => false,
    //             'message' => 'Error in bulk approval: ' . $e->getMessage()
    //         ];
    //     }
    // }
    public function bulkApproveTransactions($transaction_ids, $approver_id, $approver_name, $department, $level) {
        $this->db->beginTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $success_count = 0;
            $failed_ids = [];

            error_log("=== BULK APPROVAL STARTED by {$approver_name} ({$department}/{$level}) at {$now} ===");
            error_log("Total transactions: " . count($transaction_ids));

            foreach ($transaction_ids as $transaction_id) {
                error_log("---- Processing transaction ID {$transaction_id} ----");

                if ($department === 'Accounts' && $level === 'fc') {
                    $sql = "UPDATE account_general_transaction_new 
                            SET approval_status='Approved',
                                approving_acct_officer_id=:approver_id,
                                approving_acct_officer_name=:approver_name,
                                approval_time=:time
                            WHERE id=:transaction_id";
                } elseif ($department === 'Accounts' && $level != 'fc') {
                    $sql = "UPDATE account_general_transaction_new 
                            SET leasing_post_status='Approved',
                                leasing_post_approving_officer_id=:approver_id,
                                leasing_post_approving_officer_name=:approver_name,
                                leasing_post_approval_time=:time
                            WHERE id=:transaction_id";
                } elseif ($department === 'Audit/Inspections') {
                    $sql = "UPDATE account_general_transaction_new 
                            SET verification_status='Verified',
                                verifying_auditor_id=:approver_id,
                                verifying_auditor_name=:approver_name,
                                verification_time=:time
                            WHERE id=:transaction_id";
                } else {
                    error_log("Unknown department/level for ID {$transaction_id}");
                    $failed_ids[] = $transaction_id;
                    continue;
                }

                $this->db->query($sql);
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':transaction_id', $transaction_id);
                $this->db->bind(':time', $now);

                $exec_result = $this->db->execute();
                $affected = $this->db->rowCount();

                error_log("Execution result for ID {$transaction_id}: result=" . json_encode($exec_result) . ", affected={$affected}");

                if ($affected > 0) {
                    $success_count++;
                } else {
                    $failed_ids[] = $transaction_id;
                    error_log("⚠️ No rows affected for ID {$transaction_id}");
                }
            }

            $this->db->endTransaction();
            error_log("=== TRANSACTION COMMITTED: {$success_count} success, " . count($failed_ids) . " failed ===");

            return [
                'success'       => true,
                'message'       => "{$success_count} transaction(s) approved successfully",
                'count'         => $success_count,
                'failed_count'  => count($failed_ids),
                'failed_ids'    => $failed_ids
            ];

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            error_log("❌ Bulk approval failed: " . $e->getMessage());
            error_log($e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error in bulk approval: ' . $e->getMessage()
            ];
        }
    }






    
    /**
     * Get transaction details
     */
    public function getTransactionDetails($transaction_id) {
        $this->db->query("
            SELECT t.*, 
                   da.acct_desc as debit_account_desc,
                   ca.acct_desc as credit_account_desc,
                   s.full_name as posting_officer_full_name,
                   c.shop_no, c.customer_name
            FROM account_general_transaction_new t
            LEFT JOIN accounts da ON t.debit_account = da.acct_id
            LEFT JOIN accounts ca ON t.credit_account = ca.acct_id
            LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
            LEFT JOIN customers c ON t.shop_no = c.shop_no
            WHERE t.id = :transaction_id
        ");
        
        $this->db->bind(':transaction_id', $transaction_id);
        return $this->db->single();
    }
    
    /**
     * Delete transaction (with all related entries)
     */
    public function deleteTransaction($transaction_id, $user_id) {
        $this->db->beginTransaction();
        
        try {
            // Get transaction details first
            $transaction = $this->getTransactionDetails($transaction_id);
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Check if user can delete (same day and own transaction or CE level)
            $today = date('Y-m-d');
            if ($transaction['date_of_payment'] !== $today && $transaction['posting_officer_id'] !== $user_id) {
                throw new Exception('Cannot delete this transaction');
            }
            
            // Delete from main table
            $this->db->query("DELETE FROM account_general_transaction_new WHERE id = :transaction_id");
            $this->db->bind(':transaction_id', $transaction_id);
            $this->db->execute();
            
            // Delete from collection analysis tables
            if ($transaction['payment_category'] === 'Service Charge') {
                $this->db->query("DELETE FROM collection_analysis_arena WHERE trans_id = :transaction_id");
            } else {
                $this->db->query("DELETE FROM collection_analysis WHERE trans_id = :transaction_id");
            }
            $this->db->bind(':transaction_id', $transaction_id);
            $this->db->execute();
            
            // Delete from account tables (if they exist)
            $debit_table = $this->getAccountTable($transaction['debit_account']);
            $credit_table = $this->getAccountTable($transaction['credit_account']);
            
            if ($debit_table) {
                $this->db->query("DELETE FROM {$debit_table} WHERE id = :transaction_id");
                $this->db->bind(':transaction_id', $transaction_id);
                $this->db->execute();
            }
            
            if ($credit_table) {
                $this->db->query("DELETE FROM {$credit_table} WHERE id = :transaction_id");
                $this->db->bind(':transaction_id', $transaction_id);
                $this->db->execute();
            }
            
            $this->db->endTransaction();
            return ['success' => true, 'message' => 'Transaction deleted successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error deleting transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get account table name
     */
    private function getAccountTable($account_id) {
        $this->db->query("SELECT acct_table_name FROM accounts WHERE acct_id = :account_id");
        $this->db->bind(':account_id', $account_id);
        $result = $this->db->single();
        return isset($result['acct_table_name']) ? $result['acct_table_name'] : null;
    }
    
    /**
     * Search transactions
     */
    public function searchTransactions($search_term, $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $this->db->query("
            SELECT t.*, 
                   da.acct_desc as debit_account_desc,
                   ca.acct_desc as credit_account_desc,
                   s.full_name as posting_officer_full_name
            FROM account_general_transaction_new t
            LEFT JOIN accounts da ON t.debit_account = da.acct_id
            LEFT JOIN accounts ca ON t.credit_account = ca.acct_id
            LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
            WHERE t.transaction_desc LIKE :search_term
            OR t.receipt_no LIKE :search_term
            OR t.shop_no LIKE :search_term
            OR t.plate_no LIKE :search_term
            OR s.full_name LIKE :search_term
            ORDER BY t.date_of_payment DESC
            LIMIT :offset, :per_page
        ");
        
        $this->db->bind(':search_term', '%' . $search_term . '%');
        $this->db->bind(':offset', $offset);
        $this->db->bind(':per_page', $per_page);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get staff by department
     */
    public function getStaffByDepartment($department) {
        $this->db->query("
            SELECT user_id, full_name 
            FROM staffs 
            WHERE department = :department 
            ORDER BY full_name ASC
        ");
        $this->db->bind(':department', $department);
        return $this->db->resultSet();
    }
    
    /**
     * Check user permissions
     */
    public function checkUserPermissions($user_id, $permission) {
        $this->db->query("
            SELECT {$permission} as has_permission
            FROM roles 
            WHERE user_id = :user_id
        ");
        $this->db->bind(':user_id', $user_id);
        $result = $this->db->single();
        
        return $result['has_permission'] === 'Yes';
    }
    
    /**
     * Get payment breakdown for shop transactions
     */
    public function getPaymentBreakdown($shop_id, $receipt_no, $payment_category) {
        $table = $payment_category === 'Service Charge' ? 'collection_analysis_arena' : 'collection_analysis';
        
        $this->db->query("
            SELECT payment_month, amount_paid
            FROM {$table}
            WHERE shop_id = :shop_id AND receipt_no = :receipt_no
            ORDER BY payment_month
        ");
        
        $this->db->bind(':shop_id', $shop_id);
        $this->db->bind(':receipt_no', $receipt_no);
        
        return $this->db->resultSet();
    }
    
    /**
     * Time elapsed helper function
     */
    public function timeElapsed($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return 'N/A';
        }
        
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }
    
    /**
     * Review approve transaction (Account department)
     */
    public function reviewApproveTransaction($transaction_id, $reviewer_id, $reviewer_name) {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET leasing_post_status = 'Approved',
                    leasing_post_approving_officer_id = :reviewer_id,
                    leasing_post_approving_officer_name = :reviewer_name,
                    leasing_post_approval_time = :approval_time,
                    approval_status = 'Pending'
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':reviewer_id', $reviewer_id);
            $this->db->bind(':reviewer_name', $reviewer_name);
            $this->db->bind(':approval_time', $now);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction reviewed and moved to FC for approval'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error reviewing transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Review decline transaction (Account department)
     */
    public function reviewDeclineTransaction($transaction_id, $reviewer_id, $reviewer_name, $reason = '') {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET leasing_post_status = 'Declined',
                    leasing_post_approving_officer_id = :reviewer_id,
                    leasing_post_approving_officer_name = :reviewer_name,
                    leasing_post_approval_time = :approval_time,
                    decline_reason = :reason
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':reviewer_id', $reviewer_id);
            $this->db->bind(':reviewer_name', $reviewer_name);
            $this->db->bind(':approval_time', $now);
            $this->db->bind(':reason', $reason);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction declined during review'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error declining transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Flag transaction
     */
    public function flaggedTransaction($transaction_id, $auditor_id, $auditor_name, $flag_reason = '') {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET verification_status = 'Flagged',
                    flag_status = 'Flagged',
                    verifying_auditor_id = :auditor_id,
                    verifying_auditor_name = :auditor_name,
                    verification_time = :verification_time,
                    comment = :flag_reason
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':auditor_id', $auditor_id);
            $this->db->bind(':auditor_name', $auditor_name);
            $this->db->bind(':verification_time', $now);
            $this->db->bind(':flag_reason', $flag_reason);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction flagged successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error flagging transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update transaction
     */
    public function updateTransaction($data) {
        $this->db->beginTransaction();
        
        try {
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET transaction_desc = :transaction_desc,
                    amount_paid = :amount_paid,
                    receipt_no = :receipt_no,
                    no_of_tickets = :no_of_tickets,
                    plate_no = :plate_no,
                    shop_no = :shop_no
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':transaction_desc', $data['transaction_desc']);
            $this->db->bind(':amount_paid', $data['amount_paid']);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':no_of_tickets', $data['no_of_tickets']);
            $this->db->bind(':plate_no', $data['plate_no']);
            $this->db->bind(':shop_no', $data['shop_no']);
            $this->db->bind(':transaction_id', $data['transaction_id']);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction updated successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error updating transaction: ' . $e->getMessage()];
        }
    }
}
?>