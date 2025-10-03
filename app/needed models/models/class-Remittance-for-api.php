<?php
class Remittance {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Generate a unique remittance ID
    public function generateRemitId() {
        return 'RMT-' . date('Ymd') . '-' . rand(1000, 9999);
    }
    
    // Add new remittance
    public function addRemittance($data) {
        // Prepare query
        $this->db->query('INSERT INTO cash_remittance (remit_id, date, amount_paid, no_of_receipts, category, remitting_officer_id, remitting_officer_name, posting_officer_id, posting_officer_name) 
        VALUES (:remit_id, :date, :amount_paid, :no_of_receipts, :category, :remitting_officer_id, :remitting_officer_name, :posting_officer_id, :posting_officer_name)');
        
        // Bind values
        $this->db->bind(':remit_id', $data['remit_id']);
        $this->db->bind(':date', $data['date']);
        $this->db->bind(':amount_paid', $data['amount_paid']);
        $this->db->bind(':no_of_receipts', $data['no_of_receipts']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':remitting_officer_id', $data['remitting_officer_id']);
        $this->db->bind(':remitting_officer_name', $data['remitting_officer_name']);
        $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
        $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
        
        // Execute
        if($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false; 
        }
    }
    
    // Get all remittances
    public function getRemittances() {
        // Prepare query
        $this->db->query('SELECT * FROM cash_remittance ORDER BY date DESC, remitting_time DESC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get remittance by ID
    public function getRemittanceById($id) {
        // Prepare query
        $this->db->query('SELECT * FROM cash_remittance WHERE id = :id');
        
        // Bind value
        $this->db->bind(':id', $id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get remittance by remit ID
    public function getRemittanceByRemitId($remit_id) {
        // Prepare query
        $this->db->query('SELECT * FROM cash_remittance WHERE remit_id = :remit_id');
        
        // Bind value
        $this->db->bind(':remit_id', $remit_id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get remittances by remitting officer
    public function getRemittancesByOfficer($officer_id) {
        // Prepare query
        $this->db->query('SELECT * FROM cash_remittance WHERE remitting_officer_id = :officer_id ORDER BY date DESC, remitting_time DESC');
        
        // Bind value
        $this->db->bind(':officer_id', $officer_id);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get remittances by date
    public function getRemittancesByDate($date) {
        // Prepare query
        $this->db->query('SELECT * FROM cash_remittance WHERE date = :date ORDER BY remitting_time DESC');
        
        // Bind value
        $this->db->bind(':date', $date);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get total remittance amount by date
    public function getTotalRemittanceByDate($date) {
        // Prepare query
        $this->db->query('SELECT SUM(amount_paid) as total FROM cash_remittance WHERE date = :date');
        
        // Bind value
        $this->db->bind(':date', $date);
        
        // Get single record
        $result = $this->db->single();
        
        return isset($result['total']) ? $result['total'] : 0;
    }
    
    // Check if remittance is fully posted
    public function isRemittanceFullyPosted($remit_id) {
        // Get the remittance
        $remittance = $this->getRemittanceByRemitId($remit_id);
        
        if(!$remittance) {
            return false;
        }
        
        // Get the total number of posted transactions
        $this->db->query('SELECT COUNT(*) as posted, SUM(amount_paid) as total_posted FROM account_general_transaction_new WHERE remit_id = :remit_id');
        $this->db->bind(':remit_id', $remit_id);
        $posted = $this->db->single();
        
        // Check if the number of posted transactions matches the number of receipts
        // and if the total amount posted matches the remittance amount
        if(
            $posted['posted'] == $remittance['no_of_receipts'] && 
            $posted['total_posted'] == $remittance['amount_paid']
        ) {
            return true;
        }
        
        return false;
    }
    
    // Get paginated remittances with search and sort
    public function getPaginatedRemittances($start, $length, $search, $order_column, $order_dir) {
        // Base query for total records
        $baseQuery = 'FROM cash_remittance';
        $whereClause = '';
        
        // Search condition
        if (!empty($search)) {
            $whereClause = " WHERE remit_id LIKE :search 
                            OR remitting_officer_name LIKE :search 
                            OR posting_officer_name LIKE :search 
                            OR category LIKE :search";
        }
        
        // Get total records
        $this->db->query("SELECT COUNT(*) as total " . $baseQuery . $whereClause);
        if (!empty($search)) {
            $searchParam = "%{$search}%";
            $this->db->bind(':search', $searchParam);
        }
        $totalRecords = $this->db->single()['total'];
        
        // Column mapping for ordering
        $columns = [
            0 => 'remit_id',
            1 => 'date',
            2 => 'amount_paid',
            3 => 'no_of_receipts',
            4 => 'category',
            5 => 'remitting_officer_name',
            6 => 'posting_officer_name'
        ];
        
        // Build the main query with pagination and ordering
        $mainQuery = "SELECT * " . $baseQuery . $whereClause;
        if (isset($columns[$order_column])) {
            $mainQuery .= " ORDER BY " . $columns[$order_column] . " " . $order_dir;
        }
        $mainQuery .= " LIMIT :start, :length";
        
        // Execute main query
        $this->db->query($mainQuery);
        if (!empty($search)) {
            $this->db->bind(':search', $searchParam);
        }
        $this->db->bind(':start', (int)$start, PDO::PARAM_INT);
        $this->db->bind(':length', (int)$length, PDO::PARAM_INT);
        
        $results = $this->db->resultSet();
        
        return [
            'data' => $results,
            'total' => $totalRecords
        ];
    }
}
?>