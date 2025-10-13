<?php
// require_once 'config/Database.php';

class Remittance {
    private $db;
    
    public function __construct($databaseObj) {
        $this->db = $databaseObj;
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
        $this->db->query('SELECT * FROM cash_remittance WHERE remitting_officer_id = :id AND category="Other Collection"');
        
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

    //Get total keyedin remitances for officer
    public function getTotalRemittancesForOfficer($officer_id) {
        // Prepare query
        $this->db->query('SELECT SUM(amount_paid) as amount_posted FROM account_general_transaction_new WHERE posting_officer_id = :officer_id AND leasing_post_status = "Pending"');
        
        // Bind value
        $this->db->bind(':officer_id', $officer_id);
        
        // Get result
        $result = $this->db->single();
        return isset($result['amount_posted']) ? $result['amount_posted'] : 0;
    }
    
    //Get total keyed in Cash Remitances for officer
    public function getTotalCashRemitanceForOfficer($officer_id) {
        // Prepare query
        $this->db->query('SELECT SUM(amount_paid) as amount_remitted FROM cash_remittance WHERE remitting_officer_id = :officer_id');
        
        // Bind value
        $this->db->bind(':officer_id', $officer_id);
        
        // Get result
        $result = $this->db->single();
        return isset($result['amount_remitted']) ? $result['amount_remitted'] : 0;
    }
    // Get remittances by date
    // public function getRemittancesByDate($date) {
    //     // Prepare query
    //     $this->db->query('SELECT * FROM cash_remittance WHERE date = :date ORDER BY remitting_time DESC');
        
    //     // Bind value
    //     $this->db->bind(':date', $date);
        
    //     // Get result set
    //     return $this->db->resultSet();
    // }
    
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


    /**
     * Get staff list for officers dropdown
     */
    public function getWealthCreationOfficers() {
        $this->db->query("
            SELECT user_id, full_name 
            FROM staffs 
            WHERE department = 'Wealth Creation' 
            ORDER BY full_name ASC
        ");
        return $this->db->resultSet();
    }
    
    /**
     * Get remittances for a specific date
     */
    public function getRemittancesByDate($date) {
        $this->db->query("
            SELECT cr.*, s.full_name as officer_name
            FROM cash_remittance cr
            LEFT JOIN staffs s ON cr.remitting_officer_id = s.user_id
            WHERE cr.date = :date
            ORDER BY cr.remitting_time DESC
        ");
        $this->db->bind(':date', $date);
        return $this->db->resultSet();
    }
    
    /**
     * Get remittance summary by category for a date
     */
    public function getRemittanceSummary($date) {
        $categories = ['Rent', 'Service Charge', 'Other Collection'];
        $summary = [];
        
        foreach ($categories as $category) {
            // Get remitted amounts
            $this->db->query("
                SELECT 
                    s.full_name as officer_name,
                    COALESCE(SUM(cr.amount_paid), 0) as amount_remitted,
                    COALESCE(SUM(cr.no_of_receipts), 0) as receipts_count
                FROM staffs s
                LEFT JOIN cash_remittance cr ON s.user_id = cr.remitting_officer_id 
                    AND cr.date = :date AND cr.category = :category
                WHERE s.department = 'Wealth Creation'
                GROUP BY s.user_id, s.full_name
                ORDER BY s.full_name ASC
            ");
            $this->db->bind(':date', $date);
            $this->db->bind(':category', $category);
            $remitted = $this->db->resultSet();
            
            // Get posted amounts based on category
            $posted = [];
            foreach ($remitted as $officer) {
                $posted_amount = $this->getPostedAmount($officer['officer_name'], $date, $category);
                $posted[] = [
                    'officer_name' => $officer['officer_name'],
                    'amount_posted' => $posted_amount
                ];
            }
            
            $summary[$category] = [
                'remitted' => $remitted,
                'posted' => $posted
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get posted amount for an officer by category
     */
    private function getPostedAmount($officer_name, $date, $category) {
        if ($category === 'Other Collection') {
            $this->db->query("
                SELECT COALESCE(SUM(amount_paid), 0) as amount_posted
                FROM account_general_transaction_new 
                WHERE posting_officer_name LIKE :officer_name 
                AND date_of_payment = :date 
                AND payment_category = 'Other Collection'
            ");
        } else {
            // For Rent and Service Charge, you might need different tables
            // Based on the old erp code, these seem to use different analysis tables
            return 0; // Placeholder - implement based on your specific tables
        }
        
        $this->db->bind(':officer_name', '%' . $officer_name . '%');
        $this->db->bind(':date', $date);
        $result = $this->db->single();
        
        return isset($result['amount_posted']) ? $result['amount_posted'] : 0;
    }
    
    /**
     * Process new remittance
     */
    public function processRemittance($data) {
        $this->db->beginTransaction();
        
        try {
            // Check if remittance already exists for this officer, date, and category
            $this->db->query("
                SELECT remit_id 
                FROM cash_remittance 
                WHERE remitting_officer_id = :officer_id 
                AND date = :date 
                AND category = :category
            ");
            $this->db->bind(':officer_id', $data['officer_id']);
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':category', $data['category']);
            $existing = $this->db->single();
            
            // Generate or use existing remit_id
            if ($existing) {
                $remit_id = $existing['remit_id'];
            } else {
                $remit_id = time() . mt_rand(5000, 5300);
            }
            
            // Get officer name
            $this->db->query("SELECT full_name FROM staffs WHERE user_id = :officer_id");
            $this->db->bind(':officer_id', $data['officer_id']);
            $officer = $this->db->single();
            
            // Insert remittance
            $this->db->query("
                INSERT INTO cash_remittance (
                    remit_id, date, amount_paid, no_of_receipts, category,
                    remitting_officer_id, remitting_officer_name,
                    posting_officer_id, posting_officer_name, remitting_time
                ) VALUES (
                    :remit_id, :date, :amount_paid, :no_of_receipts, :category,
                    :remitting_officer_id, :remitting_officer_name,
                    :posting_officer_id, :posting_officer_name, NOW()
                )
            ");
            
            $this->db->bind(':remit_id', $remit_id);
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':amount_paid', $data['amount_paid']);
            $this->db->bind(':no_of_receipts', $data['no_of_receipts']);
            $this->db->bind(':category', $data['category']);
            $this->db->bind(':remitting_officer_id', $data['officer_id']);
            $this->db->bind(':remitting_officer_name', $officer['full_name']);
            $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
            $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
            
            $this->db->execute();
            
            $this->db->endTransaction();
            return ['success' => true, 'message' => 'Cash remittance successful!'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error occurred while posting: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete remittance
     */
    public function deleteRemittance($remittance_id) {
        $this->db->query("DELETE FROM cash_remittance WHERE id = :id");
        $this->db->bind(':id', $remittance_id);
        return $this->db->execute();
    }
    
    /**
     * Check if remittance can be deleted
     */
    public function canDeleteRemittance($remittance_id, $officer_id, $date, $category) {
        // Check if there are posted transactions
        $this->db->query("
            SELECT COUNT(*) as count
            FROM account_general_transaction_new 
            WHERE posting_officer_id = :officer_id 
            AND date_of_payment = :date 
            AND payment_category = :category
        ");
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':date', $date);
        $this->db->bind(':category', $category);
        $result = $this->db->single();
        
        return $result['count'] == 0;
    }
}
?>
