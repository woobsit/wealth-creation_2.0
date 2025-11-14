<?php
class PowerConsumption {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllPowerConsumers() {
        $this->db->query("SELECT power_id, shop_no, customer_name, current_month, print_status 
                         FROM customers_power_consumption 
                         WHERE shop_no != '' 
                         ORDER BY shop_no ASC");
        return $this->db->resultSet();
    }
    
    public function getPowerConsumptionByPeriod($period) {//facility_type, shop_block,shop_size, occupancy_category,block_unit, facility_status, no_of_users, date_of_reading
        $this->db->query("SELECT power_id, shop_no, customer_name, tariff, current_month, 
                         previous_reading, present_reading, previous_outstanding, total_paid, meter_no  
                         FROM customers_power_consumption 
                         WHERE current_month = :period 
                         ORDER BY shop_no ASC");
        $this->db->bind(':period', $period);
        return $this->db->resultSet();
    }
    
    public function getPowerConsumptionById($id) {
        $this->db->query("SELECT * FROM customers_power_consumption WHERE power_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    public function getPowerConsumptionHistory($shopNo, $limit = 6) {
        $this->db->query("SELECT * FROM customers_power_consumption_history 
                         WHERE shop_no = :shop_no 
                         AND update_status = 'Updated' 
                         AND current_month != 'October 2019'
                         ORDER BY date_of_reading DESC 
                         LIMIT :limit");
        $this->db->bind(':shop_no', $shopNo);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
    
    public function getPowerPayments($shopId) {
        $this->db->query("SELECT * FROM arena_account_general_transaction_new 
                         WHERE shop_id = :shop_id 
                         AND payment_category = 'Power Consumption' 
                         AND approval_status = 'Approved'
                         ORDER BY date_of_payment DESC");
        $this->db->bind(':shop_id', $shopId);
        return $this->db->resultSet();
    }
    
    public function getPendingPowerPayments($shopId) {
        $this->db->query("SELECT * FROM arena_account_general_transaction_new 
                         WHERE shop_id = :shop_id 
                         AND payment_category = 'Power Consumption' 
                         AND (approval_status = 'Pending' OR leasing_post_status = 'Pending')
                         ORDER BY date_of_payment DESC");
        $this->db->bind(':shop_id', $shopId);
        return $this->db->resultSet();
    }
    
    public function getTotalPowerPayments($shopId) {
        $this->db->query("SELECT SUM(amount_paid) as total_paid 
                         FROM arena_account_general_transaction_new 
                         WHERE shop_id = :shop_id 
                         AND payment_category = 'Power Consumption' 
                         AND approval_status = 'Approved'");
        $this->db->bind(':shop_id', $shopId);
        $result = $this->db->single();
        return $result ? $result['total_paid'] : 0;
    }
    
    public function getPowerConsumptionStats($period) {
        $this->db->query("SELECT 
                         COUNT(*) as total_customers,
                         SUM(ABS(present_reading - previous_reading)) as total_consumption,
                         SUM(previous_outstanding) as total_outstanding,
                         AVG(tariff) as avg_tariff
                         FROM customers_power_consumption 
                         WHERE current_month = :period");
        $this->db->bind(':period', $period);
        $result = $this->db->single();
        return $result ?: [
            'total_customers' => 0,
            'total_consumption' => 0,
            'total_outstanding' => 0,
            'avg_tariff' => 0
        ];
    }
    
    public function getPowerApprovalStats() {
        $stats = [];
        
        // Pending approvals
        $this->db->query("SELECT COUNT(*) as count FROM customers_power_consumption_history 
                         WHERE update_status = 'Pending' AND approval_status = ''");
        $stats['pending'] = $this->db->single()['count'];
        
        // Approved records
        $this->db->query("SELECT COUNT(*) as count FROM customers_power_consumption 
                         WHERE print_status = 'Ready'");
        $stats['ready_to_print'] = $this->db->single()['count'];
        
        // Declined records
        $this->db->query("SELECT COUNT(*) as count FROM customers_power_consumption_history 
                         WHERE update_status = 'Declined'");
        $stats['declined'] = $this->db->single()['count'];
        
        return $stats;
    }
    
    public function getReadyToPrintBills() {
        $this->db->query("SELECT power_id, shop_no, customer_name 
                         FROM customers_power_consumption 
                         WHERE print_status = 'Ready' 
                         ORDER BY shop_no ASC");
        return $this->db->resultSet();
    }
    
    public function updatePrintDate($powerId) {
        $this->db->query("UPDATE customers_power_consumption 
                         SET print_date = NOW() 
                         WHERE power_id = :power_id");
        $this->db->bind(':power_id', $powerId);
        return $this->db->execute();
    }
    
    public function addPowerReading($data) {
        $this->db->query("INSERT INTO customers_power_consumption 
                         (shop_id, month, year, previous_reading, current_reading, consumption, rate, amount, status, created_by, created_at) 
                         VALUES (:shop_id, :month, :year, :previous_reading, :current_reading, :consumption, :rate, :amount, :status, :created_by, NOW())");
        
        $this->db->bind(':shop_id', $data['shop_id']);
        $this->db->bind(':month', $data['month']);
        $this->db->bind(':year', $data['year']);
        $this->db->bind(':previous_reading', $data['previous_reading']);
        $this->db->bind(':current_reading', $data['current_reading']);
        $this->db->bind(':consumption', $data['consumption']);
        $this->db->bind(':rate', $data['rate']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':created_by', $data['created_by']);
        
        return $this->db->execute();
    }
    
    public function updatePowerReading($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $query = "UPDATE customers_power_consumption SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $values[':id'] = $id;
        
        $this->db->query($query);
        
        foreach ($values as $param => $value) {
            $this->db->bind($param, $value);
        }
        
        return $this->db->execute();
    }
    
    public function getPowerReadingById($id) {
        $this->db->query("SELECT pc.*, c.customer_name, c.shop_no, c.shop_size 
                         FROM customers_power_consumption pc
                         JOIN customers c ON pc.shop_id = c.id
                         WHERE pc.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    public function getMonthlyBilling($month, $year) {
        $this->db->query("SELECT c.shop_no, c.customer_name, pc.consumption, pc.amount, pc.status
                         FROM customers_power_consumption pc
                         JOIN customers c ON pc.shop_id = c.id
                         WHERE pc.month = :month AND pc.year = :year
                         ORDER BY c.shop_no ASC");
        
        $this->db->bind(':month', $month);
        $this->db->bind(':year', $year);
        
        return $this->db->resultSet();
    }
}
?>