<?php
class Reports {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getMonthlyStats($month, $year, $department = '') {
        try {
            $sql = 'SELECT 
                        COUNT(*) as count,
                        SUM(amount) as total_amount,
                        AVG(amount) as avg_amount
                    FROM requisitions 
                    WHERE MONTH(created_at) = :month 
                    AND YEAR(created_at) = :year 
                    AND status = "approved"';
            
            $params = [':month' => $month, ':year' => $year];
            
            if ($department) {
                $sql .= ' AND department = :department';
                $params[':department'] = $department;
            }
            
            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get monthly stats error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getYearlyStats($year, $department = '') {
        try {
            $sql = 'SELECT 
                        COUNT(*) as count,
                        SUM(amount) as total_amount,
                        AVG(amount) as avg_amount
                    FROM requisitions 
                    WHERE YEAR(created_at) = :year 
                    AND status = "approved"';
            
            $params = [':year' => $year];
            
            if ($department) {
                $sql .= ' AND department = :department';
                $params[':department'] = $department;
            }
            
            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get yearly stats error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRetirementStats($month, $year, $department = '') {
        try {
            $sql = 'SELECT 
                        COUNT(DISTINCT r.id) as count_with_retirement,
                        SUM(rt.amount_retired) as total_retired,
                        SUM(rt.amount_returned) as total_returned
                    FROM requisitions r
                    INNER JOIN retirements rt ON r.id = rt.requisition_id
                    WHERE MONTH(r.created_at) = :month 
                    AND YEAR(r.created_at) = :year';
            
            $params = [':month' => $month, ':year' => $year];
            
            if ($department) {
                $sql .= ' AND r.department = :department';
                $params[':department'] = $department;
            }
            
            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get retirement stats error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDepartmentBreakdown($month, $year) {
        try {
            $this->db->query('SELECT 
                                department,
                                COUNT(*) as count,
                                SUM(amount) as total_amount
                            FROM requisitions 
                            WHERE MONTH(created_at) = :month 
                            AND YEAR(created_at) = :year 
                            AND status = "approved"
                            GROUP BY department
                            ORDER BY total_amount DESC');
            
            $this->db->bind(':month', $month);
            $this->db->bind(':year', $year);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get department breakdown error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMonthlyTrend($year, $department = '') {
        try {
            $sql = 'SELECT 
                        MONTH(created_at) as month,
                        SUM(amount) as total_amount
                    FROM requisitions 
                    WHERE YEAR(created_at) = :year 
                    AND status = "approved"';
            
            $params = [':year' => $year];
            
            if ($department) {
                $sql .= ' AND department = :department';
                $params[':department'] = $department;
            }
            
            $sql .= ' GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)';
            
            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            $results = $this->db->resultSet();
            
            // Fill in missing months with 0
            $monthlyData = array_fill(0, 12, 0);
            foreach ($results as $result) {
                $monthlyData[$result['month'] - 1] = floatval($result['total_amount']);
            }
            
            return $monthlyData;
        } catch (Exception $e) {
            error_log("Get monthly trend error: " . $e->getMessage());
            return array_fill(0, 12, 0);
        }
    }
    
    public function getDetailedReport($month, $year, $department = '') {
        try {
            $sql = 'SELECT 
                        r.*,
                        u.full_name as created_by_name,
                        COALESCE(SUM(rt.amount_retired), 0) as total_retired,
                        COALESCE(SUM(rt.amount_returned), 0) as total_returned
                    FROM requisitions r
                    LEFT JOIN users u ON r.created_by = u.id
                    LEFT JOIN retirements rt ON r.id = rt.requisition_id
                    WHERE MONTH(r.created_at) = :month 
                    AND YEAR(r.created_at) = :year 
                    AND r.status = "approved"';
            
            $params = [':month' => $month, ':year' => $year];
            
            if ($department) {
                $sql .= ' AND r.department = :department';
                $params[':department'] = $department;
            }
            
            $sql .= ' GROUP BY r.id ORDER BY r.created_at DESC';
            
            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get detailed report error: " . $e->getMessage());
            return [];
        }
    }
}
?>