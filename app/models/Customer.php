<?php
class Customer {
    private $db;
    
    public function __construct($databaseObj) {
        $this->db = $databaseObj; 
    }
    
    
    public function getAllCustomers($status = 'Active', $limit = null, $offset = 0) {
        $query = "SELECT * FROM customers WHERE facility_status = :status ORDER BY shop_no ASC";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $this->db->query($query)
                ->bind(':status', $status);
                
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT)
                    ->bind(':offset', $offset, PDO::PARAM_INT);
        }
        
        return $this->db->resultSet();
    }
    
    public function getCustomerById($id) {
        $this->db->query("SELECT * FROM customers WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    public function getCustomersByStaff($staff_id, $status = 'Active') {
        $this->db->query("SELECT * FROM customers WHERE staff_id = :staff_id AND facility_status = :status ORDER BY shop_no ASC");
        $this->db->bind(':staff_id', $staff_id);
        $this->db->bind(':status', $status);
        return $this->db->resultSet();
    }
    
    public function getCustomersByBlock($block_name, $status = 'Active') {
        $this->db->query("SELECT * FROM customers WHERE shop_block = :block_name AND facility_status = :status ORDER BY shop_no ASC");
        $this->db->bind(':block_name', $block_name);
        $this->db->bind(':status', $status);
        return $this->db->resultSet();
    }
    
    public function updateCustomer($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $query = "UPDATE customers SET " . implode(', ', $fields) . " WHERE id = :id";
        $values[':id'] = $id;
        
        $this->db->query($query);
        
        foreach ($values as $param => $value) {
            $this->db->bind($param, $value);
        }
        
        return $this->db->execute();
    }
    
    public function searchCustomers($searchTerm) {
        $searchTerm = "%$searchTerm%";
        $this->db->query("SELECT * FROM customers WHERE 
                         customer_name LIKE :search OR 
                         shop_no LIKE :search OR 
                         phone_no LIKE :search 
                         ORDER BY shop_no ASC");
        $this->db->bind(':search', $searchTerm);
        return $this->db->resultSet();
    }
    
    public function getCustomerStats() {
        $stats = [];
        
        // Active customers
        $this->db->query("SELECT COUNT(*) as count FROM customers WHERE facility_status = 'Active'");
        $stats['active'] = $this->db->single()['count'];
        
        // Inactive customers
        $this->db->query("SELECT COUNT(*) as count FROM customers WHERE facility_status = 'Inactive'");
        $stats['inactive'] = $this->db->single()['count'];
        
        // Vacant shops
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacant WHERE facility_status = 'Vacant'");
        $stats['vacant'] = $this->db->single()['count'];
        
        return $stats;
    }
    
    public function getShopBlocks() {
        $this->db->query("SELECT * FROM shop_blocks ORDER BY block_name ASC");
        return $this->db->resultSet();
    }
    
    public function getShopsByBlock($block_id) {
        $this->db->query("SELECT sb.block_name, COUNT(c.id) as shop_count 
                         FROM shop_blocks sb 
                         LEFT JOIN customers c ON c.shop_block = sb.block_name 
                         WHERE sb.block_id = :block_id AND c.facility_status = 'Active'
                         GROUP BY sb.block_name");
        $this->db->bind(':block_id', $block_id);
        return $this->db->single();
    }
    
    public function getVacantShops($searchTerm = '', $staffId = 0, $blockId = 0) {
        $query = "SELECT vs.*, sb.block_name, ft.facility_type 
                 FROM shops_vacant vs
                 LEFT JOIN shop_blocks sb ON vs.block_id = sb.block_id
                 LEFT JOIN shops_facility_type ft ON vs.facility_type_id = ft.facility_id
                 WHERE vs.facility_status = 'Vacant'";
        
        $params = [];
        
        if ($searchTerm) {
            $query .= " AND (vs.shop_no LIKE :search OR vs.shop_size LIKE :search)";
            $params[':search'] = "%$searchTerm%";
        }
        
        if ($staffId > 0) {
            $query .= " AND vs.staff_id = :staff_id";
            $params[':staff_id'] = $staffId;
        }
        
        if ($blockId > 0) {
            $query .= " AND vs.block_id = :block_id";
            $params[':block_id'] = $blockId;
        }
        
        $query .= " ORDER BY vs.date_declared_vacant DESC";
        
        $this->db->query($query);
        
        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }
        
        return $this->db->resultSet();
    }
    
    public function getVacantShopsStats() {
        $stats = [];
        
        // Total vacant
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacant WHERE facility_status = 'Vacant'");
        $stats['total'] = $this->db->single()['count'];
        
        // Keys available
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacant WHERE facility_status = 'Vacant' AND key_status = 'Available'");
        $stats['keys_available'] = $this->db->single()['count'];
        
        // This month
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacant WHERE facility_status = 'Vacant' AND MONTH(date_declared_vacant) = MONTH(NOW()) AND YEAR(date_declared_vacant) = YEAR(NOW())");
        $stats['this_month'] = $this->db->single()['count'];
        
        // Pending approval
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacancy_authorization WHERE third_level_approval = ''");
        $stats['pending'] = $this->db->single()['count'];
        
        return $stats;
    }
    
    public function getVacantShopsByBlock($blockId) {
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacant WHERE facility_status = 'Vacant' AND block_id = :block_id");
        $this->db->bind(':block_id', $blockId);
        $result = $this->db->single();
        return $result['count'];
    }

    public function getRentExpiryStats() {
        $stats = [];

        // Count expiring this month
        $this->db->query("
            SELECT COUNT(*) AS count 
            FROM customers 
            WHERE lease_end_date != '0000-00-00'
            AND MONTH(lease_end_date) = MONTH(CURDATE())
            AND YEAR(lease_end_date) = YEAR(CURDATE())
        ");
        $stats['expiring_count'] = $this->db->single()['count'];

        // Sum expected rent for those expiring
        $this->db->query("
            SELECT SUM(expected_rent) AS total_rent
            FROM customers 
            WHERE lease_end_date != '0000-00-00'
            AND MONTH(lease_end_date) = MONTH(CURDATE())
            AND YEAR(lease_end_date) = YEAR(CURDATE())
        ");
        $total = $this->db->single()['total_rent'];
        $stats['expected_rent'] = isset($total) ? $total : 0;

        return $stats;
    }


    public function getLeaseApplications($status = 'all', $searchTerm = '') {
        $query = "SELECT * FROM customers_new_registration WHERE 1=1";
        $params = [];
        
        if ($status !== 'all') {
            switch ($status) {
                case 'pending':
                    $query .= " AND record_creation_status = 'Pending'";
                    break;
                case 'approved':
                    $query .= " AND record_creation_status = 'Approved'";
                    break;
                case 'declined':
                    $query .= " AND record_creation_status = 'Declined'";
                    break;
                case 'payment_pending':
                    $query .= " AND record_creation_status = 'Approved' AND (rent_paid = '' OR service_charge_paid = '')";
                    break;
            }
        }
        
        if ($searchTerm) {
            $query .= " AND (customer_name LIKE :search OR shop_no LIKE :search OR phone_no LIKE :search)";
            $params[':search'] = "%$searchTerm%";
        }
        
        $query .= " ORDER BY posting_time DESC";
        
        $this->db->query($query);
        
        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }
        
        return $this->db->resultSet();
    }
    
    public function getLeaseApplicationStats() {
        $stats = [];
        
        // Total applications
        $this->db->query("SELECT COUNT(*) as count FROM customers_new_registration");
        $stats['total'] = $this->db->single()['count'];
        
        // Pending
        $this->db->query("SELECT COUNT(*) as count FROM customers_new_registration WHERE record_creation_status = 'Pending'");
        $stats['pending'] = $this->db->single()['count'];
        
        // Approved
        $this->db->query("SELECT COUNT(*) as count FROM customers_new_registration WHERE record_creation_status = 'Approved'");
        $stats['approved'] = $this->db->single()['count'];
        
        // Declined
        $this->db->query("SELECT COUNT(*) as count FROM customers_new_registration WHERE record_creation_status = 'Declined'");
        $stats['declined'] = $this->db->single()['count'];
        
        // Payment pending
        $this->db->query("SELECT COUNT(*) as count FROM customers_new_registration WHERE record_creation_status = 'Approved' AND (rent_paid = '' OR service_charge_paid = '')");
        $stats['payment_pending'] = $this->db->single()['count'];
        
        return $stats;
    }
    
    public function getShopAnalysisStats() {
        $stats = [];
        
        // Total shops
        $this->db->query("SELECT COUNT(*) as count FROM customers");
        $totalShops = $this->db->single()['count'];
        
        // Vacant shops
        $this->db->query("SELECT COUNT(*) as count FROM shops_vacant WHERE facility_status = 'Vacant'");
        $vacantShops = $this->db->single()['count'];
        
        $stats['total'] = $totalShops + $vacantShops;
        $stats['occupied'] = $totalShops;
        $stats['vacant'] = $vacantShops;
        
        return $stats;
    }
    
    public function getBlockAnalysis() {
        $this->db->query("SELECT sb.block_id, sb.block_name, sb.facility_id, ft.facility_type,
                         COUNT(c.id) as occupied_shops,
                         COUNT(vs.id) as vacant_shops,
                         (COUNT(c.id) + COUNT(vs.id)) as total_shops
                         FROM shop_blocks sb
                         LEFT JOIN shops_facility_type ft ON sb.facility_id = ft.facility_id
                         LEFT JOIN customers c ON c.shop_block = sb.block_name AND c.facility_status = 'Active'
                         LEFT JOIN shops_vacant vs ON vs.block_id = sb.block_id AND vs.facility_status = 'Vacant'
                         GROUP BY sb.block_id, sb.block_name, sb.facility_id, ft.facility_type
                         ORDER BY sb.block_name ASC");
        
        return $this->db->resultSet();
    }
    
    public function getOfficerPerformance() {
        $this->db->query("SELECT s.user_id, s.full_name, s.department,
                         COUNT(c.id) as total_shops,
                         COUNT(CASE WHEN c.facility_status = 'Active' THEN 1 END) as active_shops,
                         COUNT(CASE WHEN c.facility_status = 'Inactive' THEN 1 END) as inactive_shops
                         FROM staffs s
                         LEFT JOIN customers c ON s.user_id = c.staff_id
                         WHERE s.level = 'leasing officer'
                         GROUP BY s.user_id, s.full_name, s.department
                         ORDER BY s.full_name ASC");
        
        return $this->db->resultSet();
    }
    
    public function getPowerConsumptionData($month, $year, $blockId = 0) {
        $query = "SELECT pc.*, c.customer_name, c.shop_no, c.shop_size, c.shop_block 
                 FROM customers_power_consumption pc
                 JOIN customers c ON pc.shop_id = c.id
                 WHERE pc.month = :month AND pc.year = :year";
        
        if ($blockId > 0) {
            $query .= " AND c.block_id = :block_id";
        }
        
        $query .= " ORDER BY c.shop_no ASC";
        
        $this->db->query($query);
        $this->db->bind(':month', $month);
        $this->db->bind(':year', $year);
        
        if ($blockId > 0) {
            $this->db->bind(':block_id', $blockId);
        }
        
        return $this->db->resultSet();
    }
    
    public function getPowerConsumptionStats($month, $year) {
        $this->db->query("SELECT 
                         SUM(consumption) as total_kwh,
                         SUM(amount) as total_amount,
                         AVG(rate) as avg_rate,
                         COUNT(DISTINCT shop_id) as active_meters
                         FROM customers_power_consumption 
                         WHERE month = :month AND year = :year");
        
        $this->db->bind(':month', $month);
        $this->db->bind(':year', $year);
        
        $result = $this->db->single();
        return $result ?: [
            'total_kwh' => 0,
            'total_amount' => 0,
            'avg_rate' => 0,
            'active_meters' => 0
        ];
    }
}
?>