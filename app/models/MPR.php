<?php
class MPR {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getRentCollectionAnalysis($staff_id, $month, $year) {
        $analysis = [];
        
        // Current month collections
        $analysis['current'] = $this->getCurrentMonthCollections($staff_id, $month, $year, 'rent');
        
        // Advance collections
        $analysis['advance'] = $this->getAdvanceCollections($staff_id, $month, $year, 'rent');
        
        // Arrears collections
        $analysis['arrears'] = $this->getArrearsCollections($staff_id, $month, $year, 'rent');
        
        // Expected amount
        $analysis['expected'] = $this->getExpectedAmount($staff_id, $month, 'rent');
        
        return $analysis;
    }
    
    public function getServiceChargeAnalysis($staff_id, $month, $year) {
        $analysis = [];
        
        // Current month collections
        $analysis['current'] = $this->getCurrentMonthCollections($staff_id, $month, $year, 'sc');
        
        // Advance collections
        $analysis['advance'] = $this->getAdvanceCollections($staff_id, $month, $year, 'sc');
        
        // Arrears collections
        $analysis['arrears'] = $this->getArrearsCollections($staff_id, $month, $year, 'sc');
        
        // Expected amount
        $analysis['expected'] = $this->getExpectedAmount($staff_id, $month, 'sc');
        
        return $analysis;
    }
    
    private function getCurrentMonthCollections($staff_id, $month, $year, $type) {
        $table = ($type === 'rent') ? 'collection_analysis_rent' : 'collection_analysis_arena';
        $period = $month . ' ' . $year;
        
        $this->db->query("SELECT ca.*, c.staff_id, c.lease_start_date 
                         FROM $table ca
                         JOIN customers c ON ca.shop_no = c.shop_no
                         WHERE c.staff_id = :staff_id 
                         AND YEAR(ca.date_of_payment) = :year 
                         AND MONTH(ca.date_of_payment) = :month_num
                         AND ca.payment_month = :period
                         ORDER BY ca.date_of_payment ASC");
        
        $this->db->bind(':staff_id', $staff_id)
                ->bind(':year', $year)
                ->bind(':month_num', $this->getMonthNumber($month))
                ->bind(':period', $period);
        
        return $this->db->resultSet();
    }
    
    private function getAdvanceCollections($staff_id, $month, $year, $type) {
        $table = ($type === 'rent') ? 'collection_analysis_rent' : 'collection_analysis_arena';
        $period = $month . ' ' . $year;
        $month_num = $this->getMonthNumber($month);
        
        $this->db->query("SELECT ca.*, c.staff_id, c.lease_start_date 
                         FROM $table ca
                         JOIN customers c ON ca.shop_no = c.shop_no
                         WHERE c.staff_id = :staff_id 
                         AND ca.payment_month = :period
                         AND ((YEAR(ca.date_of_payment) = :year AND MONTH(ca.date_of_payment) < :month_num) 
                              OR YEAR(ca.date_of_payment) < :year)
                         ORDER BY ca.date_of_payment ASC");
        
        $this->db->bind(':staff_id', $staff_id)
                ->bind(':period', $period)
                ->bind(':year', $year)
                ->bind(':month_num', $month_num);
        
        return $this->db->resultSet();
    }
    
    private function getArrearsCollections($staff_id, $month, $year, $type) {
        $table = ($type === 'rent') ? 'collection_analysis_rent' : 'collection_analysis_arena';
        $period = $month . ' ' . $year;
        $month_num = $this->getMonthNumber($month);
        
        $this->db->query("SELECT ca.*, c.staff_id, c.lease_start_date 
                         FROM $table ca
                         JOIN customers c ON ca.shop_no = c.shop_no
                         WHERE c.staff_id = :staff_id 
                         AND ca.payment_month = :period
                         AND ((YEAR(ca.date_of_payment) = :year AND MONTH(ca.date_of_payment) > :month_num) 
                              OR YEAR(ca.date_of_payment) > :year)
                         ORDER BY ca.date_of_payment ASC");
        
        $this->db->bind(':staff_id', $staff_id)
                ->bind(':period', $period)
                ->bind(':year', $year)
                ->bind(':month_num', $month_num);
        
        return $this->db->resultSet();
    }
    
    private function getExpectedAmount($staff_id, $month, $type) {
        $field = ($type === 'rent') ? 'expected_rent' : 'expected_service_charge';
        $month_num = $this->getMonthNumber($month);
        
        if ($type === 'rent') {
            $this->db->query("SELECT SUM($field) as expected 
                             FROM customers 
                             WHERE staff_id = :staff_id 
                             AND MONTH(lease_start_date) = :month_num
                             AND facility_status = 'active'");
        } else {
            $this->db->query("SELECT SUM($field) as expected 
                             FROM customers 
                             WHERE staff_id = :staff_id 
                             AND facility_status = 'active'");
        }
        
        $this->db->bind(':staff_id', $staff_id);
        if ($type === 'rent') {
            $this->db->bind(':month_num', $month_num);
        }
        
        $result = $this->db->single();
        return isset($result['expected']) ? $result['expected'] : 0;
    }
    
    private function getMonthNumber($month) {
        $months = [
            'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
            'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
            'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
        ];
        
        return isset($months[$month]) ? $months[$month] : 1;
    }
    
    public function getStaffPerformance() {
        $this->db->query("SELECT s.user_id, s.full_name, s.department,
                         COUNT(c.id) as total_shops,
                         COUNT(CASE WHEN c.facility_status = 'active' THEN 1 END) as active_shops,
                         COUNT(CASE WHEN c.facility_status = 'inactive' THEN 1 END) as inactive_shops
                         FROM staffs s
                         LEFT JOIN customers c ON s.user_id = c.staff_id
                         WHERE s.level = 'leasing officer'
                         GROUP BY s.user_id, s.full_name, s.department
                         ORDER BY s.full_name ASC");
        
        return $this->db->resultSet();
    }
}
?>