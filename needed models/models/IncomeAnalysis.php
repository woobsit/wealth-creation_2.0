<?php

class IncomeAnalysis {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get daily income analysis for a specific period
     */
    public function getDailyAnalysis($month, $year) {
        try {
            // Convert month name to number
            $month_num = date('m', strtotime($month . ' 1'));
            $start_date = $year . '-' . $month_num . '-01';
            $end_date = $year . '-' . $month_num . '-' . date('t', strtotime($start_date));
            $days_in_month = (int)date('t', strtotime($start_date));
            
            // Get all unique income lines
            $income_lines = $this->getIncomeLines($start_date, $end_date);
            
            $daily_analysis = [];
            $daily_totals = array_fill(1, $days_in_month, 0);
            $grand_total = 0;
            
            foreach ($income_lines as $income_line_row) {
                $income_line = $income_line_row['income_line'];
                $line_data = [
                    'income_line' => $income_line,
                    'days' => [],
                    'total' => 0
                ];
                
                // Get daily amounts for this income line
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
                    $date = $year . '-' . $month_num . '-' . $day_formatted;
                    
                    $amount = $this->getDailyAmount($income_line, $date);
                    $line_data['days'][$day] = $amount;
                    $line_data['total'] += $amount;
                    $daily_totals[$day] += $amount;
                }
                
                $grand_total += $line_data['total'];
                $daily_analysis[] = $line_data;
            }
            
            // Sort by total revenue descending
            usort($daily_analysis, function($a, $b) {
                return $b['total'] <=> $a['total'];
            });
            
            return [
                'data' => $daily_analysis,
                'daily_totals' => $daily_totals,
                'grand_total' => $grand_total,
                'days_in_month' => $days_in_month
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error fetching daily analysis: ' . $e->getMessage());
        }
    }
    
    /**
     * Get ledger data for a specific income line
     */
    public function getLedgerData($income_line, $from_date, $to_date) {
        try {
            // Get account details for this income line
            $account = $this->getAccountByIncomeLine($income_line);
            
            if (!$account) {
                throw new Exception('Account not found for income line: ' . $income_line);
            }
            
            // Get transactions for this income line
            $query = "SELECT 
                        t.*,
                        DATE_FORMAT(t.date_of_payment, '%d/%m/%Y') as formatted_date,
                        DATE_FORMAT(t.date_on_receipt, '%d/%m/%Y') as formatted_receipt_date
                      FROM account_general_transaction_new t
                      WHERE t.income_line = :income_line 
                      AND DATE(t.date_of_payment) BETWEEN :from_date AND :to_date
                      ORDER BY t.date_of_payment ASC, t.posting_time ASC";
                      
            $this->db->query($query);
            $this->db->bind(':income_line', $income_line);
            $this->db->bind(':from_date', $from_date);
            $this->db->bind(':to_date', $to_date);
            $transactions = $this->db->resultSet();
            
            // Calculate running balance
            $balance = 0;
            foreach ($transactions as &$transaction) {
                $balance += (float)$transaction['amount_paid'];
                $transaction['balance'] = $balance;
            }
            
            return [
                'account' => $account,
                'transactions' => $transactions,
                'total_amount' => $balance
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error fetching ledger data: ' . $e->getMessage());
        }
    }
    
    /**
     * Get account details by income line
     */
    private function getAccountByIncomeLine($income_line) {
        $query = "SELECT * FROM accounts WHERE acct_desc = :income_line AND income_line = TRUE LIMIT 1";
        $this->db->query($query);
        $this->db->bind(':income_line', $income_line);
        return $this->db->single();
    }
    
    /**
     * Get all income line accounts
     */
    public function getIncomeLineAccounts() {
        $query = "SELECT acct_desc as income_line, acct_code, gl_code, acct_table_name 
                  FROM accounts 
                  WHERE income_line = TRUE AND active = TRUE 
                  ORDER BY acct_desc ASC";
        $this->db->query($query);
        return $this->db->resultSet();
    }
    
    /**
     * Get all unique income lines for a period
     */
    private function getIncomeLines($start_date, $end_date) {
        $query = "SELECT DISTINCT income_line 
                  FROM account_general_transaction_new 
                  WHERE income_line IS NOT NULL 
                  AND income_line != '' 
                  AND DATE(date_of_payment) BETWEEN :start_date AND :end_date
                  ORDER BY income_line ASC";
                  
        $this->db->query($query);
        $this->db->bind(':start_date', $start_date);
        $this->db->bind(':end_date', $end_date);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get daily amount for a specific income line and date
     */
    private function getDailyAmount($income_line, $date) {
        $query = "SELECT SUM(amount_paid) as total 
                  FROM account_general_transaction_new 
                  WHERE income_line = :income_line 
                  AND DATE(date_of_payment) = :date";
                  
        $this->db->query($query);
        $this->db->bind(':income_line', $income_line);
        $this->db->bind(':date', $date);
        
        $result = $this->db->single();
        return $result['total'] ? (float)$result['total'] : 0;
    }
    
    /**
     * Get income line performance summary
     */
    public function getPerformanceSummary($month, $year) {
        $month_num = date('m', strtotime($month . ' 1'));
        $start_date = $year . '-' . $month_num . '-01';
        $end_date = $year . '-' . $month_num . '-' . date('t', strtotime($start_date));
        
        $query = "SELECT 
                    income_line,
                    COUNT(*) as transaction_count,
                    SUM(amount_paid) as total_amount,
                    AVG(amount_paid) as avg_amount,
                    MAX(amount_paid) as max_amount,
                    MIN(amount_paid) as min_amount
                  FROM account_general_transaction_new 
                  WHERE DATE(date_of_payment) BETWEEN :start_date AND :end_date
                  AND income_line IS NOT NULL 
                  AND income_line != ''
                  GROUP BY income_line 
                  ORDER BY total_amount DESC";
                  
        $this->db->query($query);
        $this->db->bind(':start_date', $start_date);
        $this->db->bind(':end_date', $end_date);
        
        return $this->db->resultSet();
    }
    
    /**
     * Calculate Sunday dates for a given month/year
     */
    public function getSundays($month, $year) {
        $sundays = [];
        
        for ($week = 1; $week <= 5; $week++) {
            $sunday_date = date("Y-m-d", strtotime("{$week} Sunday of {$month} {$year}"));
            if (date('F', strtotime($sunday_date)) == $month) {
                $sundays[] = (int)date('d', strtotime($sunday_date));
            }
        }
        
        return $sundays;
    }
}

?>
