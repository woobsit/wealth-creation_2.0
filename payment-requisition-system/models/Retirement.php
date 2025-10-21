<?php
class Retirement {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        try {
            $this->db->query('INSERT INTO retirements (requisition_id, amount_retired, amount_returned, description, posted_by) 
                             VALUES (:requisition_id, :amount_retired, :amount_returned, :description, :posted_by)');
            
            $this->db->bind(':requisition_id', $data['requisition_id']);
            $this->db->bind(':amount_retired', $data['amount_retired']);
            $this->db->bind(':amount_returned', $data['amount_returned']);
            $this->db->bind(':description', $data['description']);
            $this->db->bind(':posted_by', $data['posted_by']);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'Retirement added successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to add retirement'];
        } catch (Exception $e) {
            error_log("Create retirement error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add retirement'];
        }
    }
    
    public function getByRequisitionId($requisitionId) {
        try {
            $this->db->query('SELECT r.*, u.full_name as posted_by_name 
                             FROM retirements r 
                             LEFT JOIN users u ON r.posted_by = u.id 
                             WHERE r.requisition_id = :requisition_id 
                             ORDER BY r.created_at DESC');
            
            $this->db->bind(':requisition_id', $requisitionId);
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get retirements error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalRetired($requisitionId) {
        try {
            $this->db->query('SELECT 
                                SUM(amount_retired) as total_retired,
                                SUM(amount_returned) as total_returned
                             FROM retirements 
                             WHERE requisition_id = :requisition_id');
            
            $this->db->bind(':requisition_id', $requisitionId);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get total retired error: " . $e->getMessage());
            return ['total_retired' => 0, 'total_returned' => 0];
        }
    }
}
?>