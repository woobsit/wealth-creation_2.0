<?php
class Requisition {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate reference number
            $reference = generateReference('REQ');
            
            // Insert requisition
            $this->db->query('INSERT INTO requisitions (reference_number, title, description, amount, currency, 
                             department, category, priority, status, justification, due_date, created_by, 
                             current_approval_level, final_approval_level) 
                             VALUES (:reference_number, :title, :description, :amount, :currency, :department, 
                             :category, :priority, :status, :justification, :due_date, :created_by, 
                             :current_approval_level, :final_approval_level)');
            
            $this->db->bind(':reference_number', $reference);
            $this->db->bind(':title', $data['title']);
            $this->db->bind(':description', $data['description']);
            $this->db->bind(':amount', $data['amount']);
            $this->db->bind(':currency', isset($data['currency']) ? $data['currency'] : 'NGN');
            $this->db->bind(':department', $data['department']);
            $this->db->bind(':category', $data['category']);
            $this->db->bind(':priority', isset($data['priority']) ? $data['priority'] : 'medium');
            $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'pending');
            $this->db->bind(':justification', isset($data['justification']) ? $data['justification'] : null);
            $this->db->bind(':due_date', isset($data['due_date']) ? $data['due_date'] : null);
            $this->db->bind(':created_by', $_SESSION['user_id']);
            $this->db->bind(':current_approval_level', 1);
            $this->db->bind(':final_approval_level', 3);
            
            $this->db->execute();
            $requisitionId = $this->db->lastInsertId();
            
            // Create approval steps
            $this->createApprovalSteps($requisitionId);
            
            $this->db->commit();
            
            return ['success' => true, 'id' => $requisitionId, 'reference' => $reference];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Create requisition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create requisition'];
        }
    }
    
    private function createApprovalSteps($requisitionId) {
        // Get approval levels
        $this->db->query('SELECT * FROM approval_levels ORDER BY order_sequence');
        $levels = $this->db->resultSet();
        
        foreach ($levels as $level) {
            $this->db->query('INSERT INTO approval_steps (requisition_id, approval_level, status) 
                             VALUES (:requisition_id, :approval_level, :status)');
            $this->db->bind(':requisition_id', $requisitionId);
            $this->db->bind(':approval_level', $level['level']);
            $this->db->bind(':status', 'pending');
            $this->db->execute();
        }
    }
    
    public function getAll($filters = []) {
        try {
            $sql = 'SELECT r.*, u.full_name as created_by_name FROM requisitions r 
                    LEFT JOIN users u ON r.created_by = u.id WHERE 1=1';
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $sql .= ' AND r.created_by = :user_id';
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= ' AND r.status = :status';
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['department'])) {
                $sql .= ' AND r.department = :department';
                $params[':department'] = $filters['department'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (r.title LIKE :search OR r.reference_number LIKE :search OR r.description LIKE :search)';
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $sql .= ' ORDER BY r.created_at DESC';
            
            if (!empty($filters['limit'])) {
                $sql .= ' LIMIT :limit';
                $params[':limit'] = $filters['limit'];
            }
            
            $this->db->query($sql);
            
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            error_log("Get requisitions error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $this->db->query('SELECT r.*, u.full_name as created_by_name FROM requisitions r 
                             LEFT JOIN users u ON r.created_by = u.id WHERE r.id = :id');
            $this->db->bind(':id', $id);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get requisition error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getApprovalSteps($requisitionId) {
        try {
            $this->db->query('SELECT a.*, u.full_name as approver_name, 
                             CASE 
                                WHEN a.approval_level = 1 THEN "Department Head"
                                WHEN a.approval_level = 2 THEN "Finance Manager"
                                WHEN a.approval_level = 3 THEN "Chief Executive"
                                ELSE "Approver"
                             END as approver_title
                             FROM approval_steps a 
                             LEFT JOIN users u ON a.approver_id = u.id 
                             WHERE a.requisition_id = :requisition_id 
                             ORDER BY a.approval_level');
            $this->db->bind(':requisition_id', $requisitionId);
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get approval steps error: " . $e->getMessage());
            return [];
        }
    }
    
    public function update($id, $data) {
        try {
            $this->db->query('UPDATE requisitions SET 
                             title = :title, 
                             description = :description, 
                             amount = :amount, 
                             currency = :currency,
                             department = :department, 
                             category = :category, 
                             priority = :priority, 
                             status = :status,
                             justification = :justification, 
                             due_date = :due_date,
                             updated_at = NOW()
                             WHERE id = :id');
            
            $this->db->bind(':id', $id);
            $this->db->bind(':title', $data['title']);
            $this->db->bind(':description', $data['description']);
            $this->db->bind(':amount', $data['amount']);
            $this->db->bind(':currency', $data['currency']);
            $this->db->bind(':department', $data['department']);
            $this->db->bind(':category', $data['category']);
            $this->db->bind(':priority', $data['priority']);
            $this->db->bind(':status', $data['status']);
            $this->db->bind(':justification', $data['justification']);
            $this->db->bind(':due_date', $data['due_date']);
            
            if ($this->db->execute()) {
                // Send notification if status changed to pending
                if ($data['status'] === 'pending') {
                    $notification = new Notification();
                    $notification->sendRequisitionNotification($id, 'submitted');
                }
                
                return ['success' => true, 'message' => 'Requisition updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update requisition'];
        } catch (Exception $e) {
            error_log("Update requisition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update requisition'];
        }
    }
    
    public function updateStatus($id, $status) {
        try {
            $this->db->query('UPDATE requisitions SET status = :status WHERE id = :id');
            $this->db->bind(':id', $id);
            $this->db->bind(':status', $status);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Update status error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRetirements($requisitionId) {
        try {
            $retirement = new Retirement();
            return $retirement->getByRequisitionId($requisitionId);
        } catch (Exception $e) {
            error_log("Get retirements error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingApprovals($userId, $userLevel) {
        try {
            $sql = 'SELECT r.*, u.full_name as created_by_name 
                    FROM requisitions r 
                    LEFT JOIN users u ON r.created_by = u.id 
                    WHERE r.status = "pending" 
                    AND r.current_approval_level <= :user_level
                    AND r.id IN (
                        SELECT DISTINCT a.requisition_id 
                        FROM approval_steps a 
                        WHERE a.approval_level = r.current_approval_level 
                        AND a.status = "pending"
                    )
                    ORDER BY r.created_at ASC';
            
            $this->db->query($sql);
            $this->db->bind(':user_level', $userLevel - 2); // Adjust for approval level mapping
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get pending approvals error: " . $e->getMessage());
            return [];
        }
    }
    
    public function approve($requisitionId, $approvalLevel, $comments = null) {
        try {
            $this->db->beginTransaction();
            
            // Update approval step
            $this->db->query('UPDATE approval_steps SET status = :status, approver_id = :approver_id, 
                             comments = :comments, approved_at = NOW() 
                             WHERE requisition_id = :requisition_id AND approval_level = :approval_level');
            $this->db->bind(':status', 'approved');
            $this->db->bind(':approver_id', $_SESSION['user_id']);
            $this->db->bind(':comments', $comments);
            $this->db->bind(':requisition_id', $requisitionId);
            $this->db->bind(':approval_level', $approvalLevel);
            $this->db->execute();
            
            // Check if this is the final approval
            $this->db->query('SELECT final_approval_level FROM requisitions WHERE id = :id');
            $this->db->bind(':id', $requisitionId);
            $requisition = $this->db->single();
            
            if ($approvalLevel >= $requisition['final_approval_level']) {
                // Final approval - mark as approved
                $this->db->query('UPDATE requisitions SET status = :status, current_approval_level = :level 
                                 WHERE id = :id');
                $this->db->bind(':status', 'approved');
                $this->db->bind(':level', $approvalLevel);
                $this->db->bind(':id', $requisitionId);
                $this->db->execute();
            } else {
                // Move to next approval level
                $this->db->query('UPDATE requisitions SET current_approval_level = :level WHERE id = :id');
                $this->db->bind(':level', $approvalLevel + 1);
                $this->db->bind(':id', $requisitionId);
                $this->db->execute();
            }
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Approve requisition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve requisition'];
        }
    }
    
    public function reject($requisitionId, $approvalLevel, $comments) {
        try {
            $this->db->beginTransaction();
            
            // Update approval step
            $this->db->query('UPDATE approval_steps SET status = :status, approver_id = :approver_id, 
                             comments = :comments, approved_at = NOW() 
                             WHERE requisition_id = :requisition_id AND approval_level = :approval_level');
            $this->db->bind(':status', 'rejected');
            $this->db->bind(':approver_id', $_SESSION['user_id']);
            $this->db->bind(':comments', $comments);
            $this->db->bind(':requisition_id', $requisitionId);
            $this->db->bind(':approval_level', $approvalLevel);
            $this->db->execute();
            
            // Update requisition status
            $this->db->query('UPDATE requisitions SET status = :status WHERE id = :id');
            $this->db->bind(':status', 'rejected');
            $this->db->bind(':id', $requisitionId);
            $this->db->execute();
            
            // Send notification
            $notification = new Notification();
            $notification->sendRequisitionNotification($requisitionId, 'rejected', $approvalLevel);
            
            // Send notification
            $notification = new Notification();
            $notification->sendRequisitionNotification($requisitionId, 'approved', $approvalLevel);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Reject requisition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject requisition'];
        }
    }
    
    public function getDashboardStats($userId = null) {
        try {
            $stats = [];
            
            // Total requisitions
            $sql = 'SELECT COUNT(*) as count FROM requisitions';
            if ($userId) {
                $sql .= ' WHERE created_by = :user_id';
            }
            $this->db->query($sql);
            if ($userId) {
                $this->db->bind(':user_id', $userId);
            }
            $result = $this->db->single();
            $stats['total_requisitions'] = $result['count'];
            
            // Pending approvals
            $this->db->query('SELECT COUNT(*) as count FROM requisitions WHERE status = :status');
            $this->db->bind(':status', 'pending');
            $result = $this->db->single();
            $stats['pending_approvals'] = $result['count'];
            
            // Approved today
            $this->db->query('SELECT COUNT(*) as count FROM requisitions WHERE status = :status AND DATE(updated_at) = CURDATE()');
            $this->db->bind(':status', 'approved');
            $result = $this->db->single();
            $stats['approved_today'] = $result['count'];
            
            // Total amount pending
            $this->db->query('SELECT SUM(amount) as total FROM requisitions WHERE status = :status');
            $this->db->bind(':status', 'pending');
            $result = $this->db->single();
            $stats['total_amount_pending'] = $result['total'] ? $result['total'] : 0;
            
            // My requisitions (if user specified)
            if ($userId) {
                $this->db->query('SELECT COUNT(*) as count FROM requisitions WHERE created_by = :user_id');
                $this->db->bind(':user_id', $userId);
                $result = $this->db->single();
                $stats['my_requisitions'] = $result['count'];
                
                // Rejected requisitions
                $this->db->query('SELECT COUNT(*) as count FROM requisitions WHERE created_by = :user_id AND status = :status');
                $this->db->bind(':user_id', $userId);
                $this->db->bind(':status', 'rejected');
                $result = $this->db->single();
                $stats['rejected_requisitions'] = $result['count'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
}
?>