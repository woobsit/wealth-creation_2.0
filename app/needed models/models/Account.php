<?php
// require_once 'config/Database.php';

class Account {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Get all accounts
    public function getAccounts() {
        // Prepare query
        $this->db->query('SELECT * FROM accounts ORDER BY acct_id ASC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get all income line accounts
    public function getIncomeLineAccounts() {
        // Prepare query
        $this->db->query('SELECT * FROM accounts WHERE income_line = "Yes" AND active = "Yes" ORDER BY acct_id ASC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get account by ID
    public function getAccountById($id) {
        // Prepare query
        $this->db->query('SELECT * FROM accounts WHERE acct_id = :id');
        
        // Bind value
        $this->db->bind(':id', $id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get account by code
    public function getAccountByCode($code) {
        // Prepare query
        $this->db->query('SELECT * FROM accounts WHERE acct_code = :code');
        
        // Bind value
        $this->db->bind(':code', $code);
        
        // Get single record
        return $this->db->single();
    }
    
    // Add new account
    public function addAccount($data) {
        // Prepare query
        $this->db->query('INSERT INTO accounts (gl_code, acct_code, acct_type, acct_class, acct_class_type, acct_desc, acct_alias, acct_table_name, balance_sheet_report, profit_loss_report, negative_acct, active, page_visibility, audit_position, income_line) 
        VALUES (:gl_code, :acct_code, :acct_type, :acct_class, :acct_class_type, :acct_desc, :acct_alias, :acct_table_name, :balance_sheet_report, :profit_loss_report, :negative_acct, :active, :page_visibility, :audit_position, :income_line)');
        
        // Bind values
        $this->db->bind(':gl_code', $data['gl_code']);
        $this->db->bind(':acct_code', $data['acct_code']);
        $this->db->bind(':acct_type', $data['acct_type']);
        $this->db->bind(':acct_class', $data['acct_class']);
        $this->db->bind(':acct_class_type', $data['acct_class_type']);
        $this->db->bind(':acct_desc', $data['acct_desc']);
        $this->db->bind(':acct_alias', $data['acct_alias']);
        $this->db->bind(':acct_table_name', $data['acct_table_name']);
        $this->db->bind(':balance_sheet_report', $data['balance_sheet_report']);
        $this->db->bind(':profit_loss_report', $data['profit_loss_report']);
        $this->db->bind(':negative_acct', $data['negative_acct']);
        $this->db->bind(':active', $data['active']);
        $this->db->bind(':page_visibility', $data['page_visibility']);
        $this->db->bind(':audit_position', $data['audit_position']);
        $this->db->bind(':income_line', $data['income_line']);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    // Update account
    public function updateAccount($data) {
        // Prepare query
        $this->db->query('UPDATE accounts SET 
            gl_code = :gl_code, 
            acct_code = :acct_code, 
            acct_type = :acct_type, 
            acct_class = :acct_class, 
            acct_class_type = :acct_class_type, 
            acct_desc = :acct_desc, 
            acct_alias = :acct_alias, 
            acct_table_name = :acct_table_name, 
            balance_sheet_report = :balance_sheet_report, 
            profit_loss_report = :profit_loss_report, 
            negative_acct = :negative_acct, 
            active = :active, 
            page_visibility = :page_visibility, 
            audit_position = :audit_position, 
            income_line = :income_line 
        WHERE acct_id = :acct_id');
        
        // Bind values
        $this->db->bind(':acct_id', $data['acct_id']);
        $this->db->bind(':gl_code', $data['gl_code']);
        $this->db->bind(':acct_code', $data['acct_code']);
        $this->db->bind(':acct_type', $data['acct_type']);
        $this->db->bind(':acct_class', $data['acct_class']);
        $this->db->bind(':acct_class_type', $data['acct_class_type']);
        $this->db->bind(':acct_desc', $data['acct_desc']);
        $this->db->bind(':acct_alias', $data['acct_alias']);
        $this->db->bind(':acct_table_name', $data['acct_table_name']);
        $this->db->bind(':balance_sheet_report', $data['balance_sheet_report']);
        $this->db->bind(':profit_loss_report', $data['profit_loss_report']);
        $this->db->bind(':negative_acct', $data['negative_acct']);
        $this->db->bind(':active', $data['active']);
        $this->db->bind(':page_visibility', $data['page_visibility']);
        $this->db->bind(':audit_position', $data['audit_position']);
        $this->db->bind(':income_line', $data['income_line']);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
?>
