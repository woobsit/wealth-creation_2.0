<?php
class Transaction {
    private $db;
    
    public function __construct($databaseObj) {
        $this->db = $databaseObj;
    }
    
    // Add new transaction
    public function addTransaction($data) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Prepare query for main transaction
            $this->db->query('INSERT INTO account_general_transaction_new (
                shop_id, customer_name, shop_no, shop_size, date_of_payment, date_on_receipt, 
                start_date, end_date, payment_type, ticket_category, transaction_desc, 
                bank_name, cheque_no, teller_no, receipt_no, amount_paid, 
                remitting_customer, remitting_id, remitting_staff, 
                posting_officer_id, posting_officer_name, 
                debit_account, credit_account, customer_status, payment_category, 
                entry_status, no_of_tickets, plate_no, no_of_nights, 
                it_status, sticker_no, no_of_days, ref_no, remit_id, income_line
            ) VALUES (
                :shop_id, :customer_name, :shop_no, :shop_size, :date_of_payment, :date_on_receipt, 
                :start_date, :end_date, :payment_type, :ticket_category, :transaction_desc, 
                :bank_name, :cheque_no, :teller_no, :receipt_no, :amount_paid, 
                :remitting_customer, :remitting_id, :remitting_staff, 
                :posting_officer_id, :posting_officer_name, 
                :debit_account, :credit_account, :customer_status, :payment_category, 
                :entry_status, :no_of_tickets, :plate_no, :no_of_nights, 
                :it_status, :sticker_no, :no_of_days, :ref_no, :remit_id, :income_line
            )');
            
            // Bind values
            $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
            $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
            $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
            $this->db->bind(':shop_size', isset($data['shop_size']) ? $data['shop_size'] : null);
            $this->db->bind(':date_of_payment', $data['date_of_payment']);
            $this->db->bind(':date_on_receipt', isset($data['date_on_receipt']) ? $data['date_on_receipt'] : null);
            $this->db->bind(':start_date', isset($data['start_date']) ? $data['start_date'] : null);
            $this->db->bind(':end_date', isset($data['end_date']) ? $data['end_date'] : null);
            $this->db->bind(':payment_type', $data['payment_type']);
            $this->db->bind(':ticket_category', isset($data['ticket_category']) ? $data['ticket_category'] : null);
            $this->db->bind(':transaction_desc', isset($data['transaction_desc']) ? $data['transaction_desc'] : null);
            $this->db->bind(':bank_name', isset($data['bank_name']) ? $data['bank_name'] : null);
            $this->db->bind(':cheque_no', isset($data['cheque_no']) ? $data['cheque_no'] : null);
            $this->db->bind(':teller_no', isset($data['teller_no']) ? $data['teller_no'] : null);
            $this->db->bind(':receipt_no', isset($data['receipt_no']) ? $data['receipt_no'] : null);
            $this->db->bind(':amount_paid', $data['amount_paid']);
            $this->db->bind(':remitting_customer', isset($data['remitting_customer']) ? $data['remitting_customer'] : null);
            $this->db->bind(':remitting_id', isset($data['remitting_id']) ? $data['remitting_id'] : null);
            $this->db->bind(':remitting_staff', isset($data['remitting_staff']) ? $data['remitting_staff'] : null);
            $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
            $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
            $this->db->bind(':debit_account', $data['debit_account']);
            $this->db->bind(':credit_account', $data['credit_account']);
            $this->db->bind(':customer_status', isset($data['customer_status']) ? $data['customer_status'] : null);
            $this->db->bind(':payment_category', isset($data['payment_category']) ? $data['payment_category'] : null);
            $this->db->bind(':entry_status', isset($data['entry_status']) ? $data['entry_status'] : null);
            $this->db->bind(':no_of_tickets', isset($data['no_of_tickets']) ? $data['no_of_tickets'] : null);
            $this->db->bind(':plate_no', isset($data['plate_no']) ? $data['plate_no'] : null);
            $this->db->bind(':no_of_nights', isset($data['no_of_nights']) ? $data['no_of_nights'] : null);
            $this->db->bind(':it_status', isset($data['it_status']) ? $data['it_status'] : null);
            $this->db->bind(':sticker_no', isset($data['sticker_no']) ? $data['sticker_no'] : null);
            $this->db->bind(':no_of_days', isset($data['no_of_days']) ? $data['no_of_days'] : null);
            $this->db->bind(':ref_no', isset($data['ref_no']) ? $data['ref_no'] : null);
            $this->db->bind(':remit_id', $data['remit_id']);
            $this->db->bind(':income_line', $data['income_line']);
            
            // Execute
            if(!$this->db->execute()) {
                $this->db->cancelTransaction();
                return false;
            }
            
            // Get the last insert ID
            $transaction_id = $this->db->lastInsertId();
            
            // Get the credit account details
            $creditAccount = $this->account->getAccountByCode($data['credit_account']);
            
            // If this is an income line transaction, add to the specific income table
            if($creditAccount && !empty($creditAccount['acct_table_name'])) {
                $table_name = $creditAccount['acct_table_name'];
                
                // Based on the income line, insert into the appropriate table
                switch($table_name) {
                    case 'income_shop_rent':
                        $this->db->query("INSERT INTO $table_name (
                            transaction_id, shop_id, shop_no, shop_size, customer_name, 
                            rent_start_date, rent_end_date, amount
                        ) VALUES (
                            :transaction_id, :shop_id, :shop_no, :shop_size, :customer_name, 
                            :start_date, :end_date, :amount
                        )");
                        
                        $this->db->bind(':transaction_id', $transaction_id);
                        $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
                        $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
                        $this->db->bind(':shop_size', isset($data['shop_size']) ? $data['shop_size'] : null);
                        $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
                        $this->db->bind(':start_date', isset($data['start_date']) ? $data['start_date'] : null);

                        $this->db->bind(':end_date', isset($data['end_date']) ? $data['end_date'] : null);
                        $this->db->bind(':amount', $data['amount_paid']);
                        break;
                        
                        case 'income_service_charge':
                        // Extract month/year from dates
                        $month = date('F', strtotime(isset($data['start_date']) ? $data['start_date'] : date('Y-m-d')));
                        $year = date('Y', strtotime(isset($data['start_date']) ? $data['start_date'] : date('Y-m-d')));
                    
                        $this->db->query("INSERT INTO $table_name (
                            transaction_id, shop_id, shop_no, customer_name, 
                            month, year, amount
                        ) VALUES (
                            :transaction_id, :shop_id, :shop_no, :customer_name, 
                            :month, :year, :amount
                        )");
                    
                        $this->db->bind(':transaction_id', $transaction_id);
                        $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
                        $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
                        $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
                        
                        $this->db->bind(':month', $month);
                        $this->db->bind(':year', $year);
                        $this->db->bind(':amount', $data['amount_paid']);
                        break;
                        
                    default:
                        // Generic insert for other income tables
                        $this->db->query("INSERT INTO $table_name (
                            transaction_id, description, amount, date
                        ) VALUES (
                            :transaction_id, :description, :amount, :date
                        )");
                        
                        $this->db->bind(':transaction_id', $transaction_id);
                        $this->db->bind(':description', isset($data['transaction_desc']) ? $data['transaction_desc'] : '');
                        $this->db->bind(':amount', $data['amount_paid']);
                        $this->db->bind(':date', $data['date_of_payment']);
                        break;
                }
                
                // Execute the specific income table insert
                if(!$this->db->execute()) {
                    $this->db->cancelTransaction();
                    return false;
                }
            }
            
            // Commit the transaction
            $this->db->endTransaction();
            
            return $transaction_id;
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }
    
    // Get all transactions
    // public function getTransactions() {
    //     // Prepare query
    //     $this->db->query('SELECT * FROM account_general_transaction_new ORDER BY posting_time DESC LIMIT 100');
        
    //     // Get result set
    //     return $this->db->resultSet();
    // }
    
    // Get transaction by ID
    public function getTransactionById($id) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE id = :id');
        
        // Bind value
        $this->db->bind(':id', $id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get transactions by remittance ID
    public function getTransactionsByRemitId($remit_id) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE remit_id = :remit_id ORDER BY posting_time DESC');
        
        // Bind value
        $this->db->bind(':remit_id', $remit_id);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get pending transactions for leasing approval
    public function getPendingTransactionsForLeasingApproval() {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE leasing_post_status = "pending" ORDER BY posting_time DESC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get pending transactions for account approval
    public function getPendingTransactionsForAccountApproval() {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE leasing_post_status = "approved" AND approval_status = "pending" ORDER BY posting_time DESC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get pending transactions for audit verification
    public function getPendingTransactionsForAuditVerification() {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE approval_status = "approved" AND verification_status = "pending" ORDER BY posting_time DESC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Approve leasing post
    public function approveLeasingPost($transaction_id, $officer_id, $officer_name) {
        // Prepare query
        $this->db->query('UPDATE account_general_transaction_new SET 
            leasing_post_status = "approved", 
            leasing_post_approving_officer_id = :officer_id, 
            leasing_post_approving_officer_name = :officer_name, 
            leasing_post_approval_time = NOW() 
        WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':officer_name', $officer_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Approve account transaction
    // public function approveTransaction($transaction_id, $officer_id, $officer_name) {
    //     // Prepare query
    //     $this->db->query('UPDATE account_general_transaction_new SET 
    //         approval_status = "approved", 
    //         approving_acct_officer_id = :officer_id, 
    //         approving_acct_officer_name = :officer_name, 
    //         approval_time = NOW() 
    //     WHERE id = :id');
        
    //     // Bind values
    //     $this->db->bind(':id', $transaction_id);
    //     $this->db->bind(':officer_id', $officer_id);
    //     $this->db->bind(':officer_name', $officer_name);
        
    //     // Execute
    //     return $this->db->execute();
    // }
    
    // Verify transaction by auditor
    public function verifyTransaction($transaction_id, $auditor_id, $auditor_name) {
        // Prepare query
        $this->db->query('UPDATE account_general_transaction_new SET 
            verification_status = "verified", 
            verifying_auditor_id = :auditor_id, 
            verifying_auditor_name = :auditor_name, 
            verification_time = NOW() 
        WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':auditor_id', $auditor_id);
        $this->db->bind(':auditor_name', $auditor_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Reject transaction (any stage)
    public function rejectTransaction($transaction_id, $stage, $officer_id, $officer_name) {
        switch($stage) {
            case 'leasing':
                $sql = 'UPDATE account_general_transaction_new SET 
                    leasing_post_status = "rejected", 
                    leasing_post_approving_officer_id = :officer_id, 
                    leasing_post_approving_officer_name = :officer_name, 
                    leasing_post_approval_time = NOW() 
                WHERE id = :id';
                break;
                
            case 'account':
                $sql = 'UPDATE account_general_transaction_new SET 
                    approval_status = "rejected", 
                    approving_acct_officer_id = :officer_id, 
                    approving_acct_officer_name = :officer_name, 
                    approval_time = NOW() 
                WHERE id = :id';
                break;
                
            case 'audit':
                $sql = 'UPDATE account_general_transaction_new SET 
                    verification_status = "rejected", 
                    verifying_auditor_id = :officer_id, 
                    verifying_auditor_name = :officer_name, 
                    verification_time = NOW() 
                WHERE id = :id';
                break;
                
            default:
                return false;
        }
        
        // Prepare query
        $this->db->query($sql);
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':officer_name', $officer_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Get transaction statistics for dashboard
    public function getTransactionStats() {
        $stats = [];
        
        // Today's transactions
        $this->db->query('SELECT COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE DATE(posting_time) = CURDATE() AND approval_status = "Approved"');
        $stats['today'] = $this->db->single();
        
        // This week's transactions
        $this->db->query('SELECT COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE YEARWEEK(posting_time, 1) = YEARWEEK(CURDATE(), 1) AND approval_status = "Approved"');
        $stats['week'] = $this->db->single();
        
        // This month's transactions
        $this->db->query('SELECT COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE YEAR(posting_time) = YEAR(CURDATE()) AND MONTH(posting_time) = MONTH(CURDATE()) AND approval_status = "Approved"');
        $stats['month'] = $this->db->single();
        
        // Income line breakdown (this month)
        $this->db->query('SELECT income_line, COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE YEAR(posting_time) = YEAR(CURDATE()) AND MONTH(posting_time) = MONTH(CURDATE()) AND approval_status = "Approved" GROUP BY income_line');
        $stats['income_lines'] = $this->db->resultSet();

        return $stats;
    }

    // Add unposted transaction
    public function addUnpostedTransaction($data) {
        $this->db->query('INSERT INTO unposted_transactions (
            remit_id, trans_id, shop_id, shop_no, customer_name, date_of_payment, 
            transaction_desc, amount_paid, receipt_no, category, income_line,
            posting_officer_id, posting_officer_name, reason
        ) VALUES (
            :remit_id, :trans_id, :shop_id, :shop_no, :customer_name, :date_of_payment, 
            :transaction_desc, :amount_paid, :receipt_no, :category, :income_line,
            :posting_officer_id, :posting_officer_name, :reason
        )');
        
        $this->db->bind(':remit_id', $data['remit_id']);
        $this->db->bind(':trans_id', isset($data['trans_id']) ? $data['trans_id'] : null);
        $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
        $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
        $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
        $this->db->bind(':date_of_payment', $data['date_of_payment']);
        $this->db->bind(':transaction_desc', isset($data['transaction_desc']) ? $data['transaction_desc'] : null);
        $this->db->bind(':amount_paid', $data['amount_paid']);
        $this->db->bind(':receipt_no', $data['receipt_no']);
        $this->db->bind(':category', isset($data['category']) ? $data['category'] : null);
        $this->db->bind(':income_line', $data['income_line']);
        $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
        $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
        $this->db->bind(':reason', $data['reason']);
        
        return $this->db->execute();
    }
    
    // Get unposted transactions by remittance ID
    public function getUnpostedTransactionsByRemitId($remit_id) {
        $this->db->query('SELECT * FROM unposted_transactions WHERE remit_id = :remit_id ORDER BY posting_time DESC');
        $this->db->bind(':remit_id', $remit_id);
        return $this->db->resultSet();
    }
    
    // Get unposted transactions by officer
    public function getUnpostedTransactionsByOfficer($officer_id) {
        $this->db->query('SELECT * FROM unposted_transactions WHERE posting_officer_id = :officer_id AND payment_status = "unposted" ORDER BY posting_time DESC');
        $this->db->bind(':officer_id', $officer_id);
        return $this->db->resultSet();
    }
    

    //count uposted transaction in unposted transaction table
    public function countUnpostedTransactionsByOfficer($officer_id) {
        $this->db->query(
            'SELECT COUNT(*) AS total
            FROM unposted_transactions
            WHERE posting_officer_id = :officer_id
            AND payment_status = "pending"'
        );
        $this->db->bind(':officer_id', $officer_id);

        $row = $this->db->single();      // returns ['total' => n] or false
        return $row ? (int)$row['total'] : 0;
    }

    public function getPostingSummary($officer_id, $department) {
        $summary = [
            'declined' => 0,
            'pending' => 0,
            'wrong_entries' => 0
        ];

        // Count declined posts
        if ($department === "Wealth Creation") {
            $this->db->query("SELECT COUNT(id) as declined FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND leasing_post_status = 'Declined'");
        } else {
            $this->db->query("SELECT COUNT(id) as declined FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND approval_status = 'Declined'");
        }
        $this->db->bind(':id', $officer_id);
        $declinedResult = $this->db->single();
        $summary['declined'] = $declinedResult ? $declinedResult['declined'] : 0;

        // Count pending posts
        if ($department === "Wealth Creation") {
            $this->db->query("SELECT COUNT(id) as pending FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND leasing_post_status = 'Pending'");
        } else {
            $this->db->query("SELECT COUNT(id) as pending FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND approval_status = 'Pending'");
        }
        $this->db->bind(':id', $officer_id);
        $pendingResult = $this->db->single();
        $summary['pending'] = $pendingResult ? $pendingResult['pending'] : 0;

        // Count wrong entries (non-empty IT status)
        $this->db->query("SELECT COUNT(id) as wrong FROM account_general_transaction_new 
                        WHERE posting_officer_id = :id AND it_status != ''");
        $this->db->bind(':id', $officer_id);
        $wrongResult = $this->db->single();
        $summary['wrong_entries'] = $wrongResult ? $wrongResult['wrong'] : 0;

        return $summary;
    }

    public function totalofPendingPost($officerId) {
        $sql  = 'SELECT SUM(amount_paid) AS total
                FROM account_general_transaction_new
                WHERE posting_officer_id = :oid
                AND leasing_post_status = "Pending"';

        $this->db->query($sql);
        $this->db->bind(':oid', $officerId);
        $row = $this->db->single();          // ['total'=>…] or false

        $total = $row && $row['total'] !== null
                ? (float)$row['total']
                : 0.0;

        return $total;     // string “1,234.00”
    }

    //Unposted transaction in unposted transaction table
    public function totalUnpostedAmountTodayByOfficer($officerId) {
        $today = date('Y-m-d');

        $this->db->query(
            'SELECT SUM(amount_paid) AS total
            FROM unposted_transactions
            WHERE payment_status = "pending"
            AND posting_officer_id = :oid
            AND DATE(posting_time) = :today'
        );
        $this->db->bind(':oid',  $officerId);
        $this->db->bind(':today', $today);

        $row = $this->db->single();
        return $row && $row['total'] !== null ? (float) $row['total'] : 0.0;
    }

    
    // Update transaction status to reposted
    public function markAsReposted($id) {
        $this->db->query('UPDATE unposted_transactions SET payment_status = "reposted", reposting_time = NOW() WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }


    public function totalTillWealthCreation($officerId) {
        $sql  = 'SELECT SUM(amount_paid) AS total
                FROM account_general_transaction_new
                WHERE posting_officer_id = :oid
                AND leasing_post_status IN ("Pending","Declined")';

        $this->db->query($sql);
        $this->db->bind(':oid', $officerId);
        $row = $this->db->single();          // ['total'=>…] or false

        $total = $row && $row['total'] !== null
                ? (float)$row['total']
                : 0.0;

        return $total;     // string “1,234.00”
    }

    
    public function totalTillAccounts($officerId) {
        $sql  = 'SELECT SUM(amount_paid) AS total
                FROM account_general_transaction_new
                WHERE posting_officer_id = :oid
                AND approval_status IN ("Pending","Declined")';

        $this->db->query($sql);
        $this->db->bind(':oid', $officerId);
        $row = $this->db->single();

        $total = $row && $row['total'] !== null
                ? (float)$row['total']
                : 0.0;

        return $total;
    }


    public function countDeclinedPostsByOfficer($officerId, $department) {
        if ($department === 'Wealth Creation') {
            $this->db->query('SELECT COUNT(id) AS count FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND leasing_post_status = "Declined"');
        } else {
            $this->db->query('SELECT COUNT(id) AS count FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND approval_status = "Declined"');
        }

        $this->db->bind(':id', $officerId);
        $row = $this->db->single();
        return $row ? (int)$row['count'] : 0;
    }


    public function countPendingPostsByOfficer($officerId, $department) {
        if ($department === 'Wealth Creation') {
            $this->db->query('SELECT COUNT(id) AS count FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND leasing_post_status = "Pending"');
        } else {
            $this->db->query('SELECT COUNT(id) AS count FROM account_general_transaction_new 
                            WHERE posting_officer_id = :id AND approval_status = "Pending"');
        }

        $this->db->bind(':id', $officerId);
        $row = $this->db->single();
        return $row ? (int)$row['count'] : 0;
    }


    public function countWrongEntriesFlaggedByIT($officerId) {
        $this->db->query('SELECT COUNT(id) AS count FROM account_general_transaction_new 
                        WHERE posting_officer_id = :id AND it_status != ""');
        $this->db->bind(':id', $officerId);
        $row = $this->db->single();
        return $row ? (int)$row['count'] : 0;
    }


    //Get today's remittance summary for officer in other collections 
    public function getRemittanceSummaryForToday($officer_id, $category = 'Other Collection') {
        $today = date('Y-m-d');
        //$yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));

        // Get amount posted
        $this->db->query('
            SELECT posting_officer_id, date_of_payment, SUM(amount_paid) as amount_posted 
            FROM account_general_transaction_new 
            WHERE posting_officer_id = :officer_id 
            AND payment_category = :category 
            AND DATE(date_of_payment) = :today
        ');
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':today', $today);
        $posted = $this->db->single();
        //$amount_posted = $posted['amount_posted'] ?? 0;
        $amount_posted = isset($posted['amount_posted']) ? $posted['amount_posted'] : 0;


        // Get amount remitted
        $this->db->query('
            SELECT remit_id, date, category, SUM(amount_paid) as amount_remitted 
            FROM cash_remittance 
            WHERE remitting_officer_id = :officer_id 
            AND category = :category 
            AND DATE(date) = :today
        ');
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':today', $today);
        $remitted = $this->db->single();
        //$amount_remitted = $remitted['amount_remitted'] ?? 0;
        $amount_remitted = isset($remitted['amount_remitted']) ? $remitted['amount_remitted'] : 0;

        // Calculate unposted
        $unposted = $amount_remitted - $amount_posted;

        // 4. Get unposted log data
        $this->db->query('
            SELECT remit_id, date_of_payment, SUM(amount_paid) as amount_logged 
            FROM unposted_transactions 
            WHERE posting_officer_id = :officer_id 
            AND category = :category 
            AND DATE(date_of_payment) = :today
        ');
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':today', $today);
        $logged = $this->db->single();

        //Calculate unlogged
        $unlogged = $unposted - $logged['amount_logged'];

        // Return structured data
        return [
            'remitted' => $amount_remitted,
            'posted' => $amount_posted,
            'unposted' => $unposted,
            'remit_id' => isset($remitted['remit_id']) ? $remitted['remit_id'] : null,
            'date' => isset($remitted['date']) ? $remitted['date'] : $today,
            'category' => isset($remitted['category']) ? $remitted['category'] : $category,
            'loggedAmount'    => isset($logged['amount_logged']) ? $logged['amount_logged'] : 0,
            'unlogged' => $unlogged,
            'unpostedLogDate'  => isset($logged['date_of_payment']) ? $logged['date_of_payment'] : ''
        ];
    }


    function getCorrectedRecordStatusSummary($db) {
        $summary = [];

        // Total records
        $db->query("SELECT COUNT(id) as total FROM record_update_authorization WHERE status IN ('Open', 'Approved', 'Declined')");
        $summary['total'] = $db->single()['total'];

        $db->query("SELECT COUNT(id) as total FROM record_update_authorization WHERE status IN ('Open', 'Approved', 'Declined')");
        $summary['total'] = $db->single()['total'];

        // Awaiting Audit (Approved by HOD but not yet by Audit)
        $db->query("SELECT COUNT(id) as awaiting_audit FROM record_update_authorization WHERE second_level_approval = 'Yes' AND third_level_approval = ''");
        $summary['awaiting_audit'] = $db->single()['awaiting_audit'];

        // Fully approved or declined
        $db->query("SELECT COUNT(id) as third_level_or_declined FROM record_update_authorization WHERE (second_level_approval = 'Yes' AND third_level_approval = 'Yes') OR status = 'Declined'");
        $summary['fully_approved_or_declined'] = $db->single()['third_level_or_declined'];

        // Declined by Audit
        $db->query("SELECT COUNT(id) as declined_by_audit FROM record_update_authorization WHERE status = 'Declined'");
        $summary['declined_by_audit'] = $db->single()['declined_by_audit'];

        return $summary;
    }

    //Shop renewal summarry
    function getShopRenewalStatusSummary($db) {
        $summary = [];

        // Total Records
        $db->query("SELECT COUNT(id) as total FROM shops_renewal_authorization WHERE status IN ('Open', 'Approved', 'Declined')");
        $summary['total'] = $db->single()['total'];

        // Awaiting IT Approval
        $db->query("SELECT COUNT(id) as awaiting_it FROM shops_renewal_authorization WHERE it_approval = ''");
        $summary['awaiting_it'] = $db->single()['awaiting_it'];

        // Declined by IT
        $db->query("SELECT COUNT(id) as declined_by_it FROM shops_renewal_authorization WHERE it_approval = 'No'");
        $summary['declined_by_it'] = $db->single()['declined_by_it'];

        // Awaiting HOD Approval
        $db->query("SELECT COUNT(id) as awaiting_hod FROM shops_renewal_authorization WHERE it_approval = 'Yes' AND second_level_approval = ''");
        $summary['awaiting_hod'] = $db->single()['awaiting_hod'];

        // Declined by HOD
        $db->query("SELECT COUNT(id) as declined_by_hod FROM shops_renewal_authorization WHERE second_level_approval = 'No'");
        $summary['declined_by_hod'] = $db->single()['declined_by_hod'];

        // Awaiting Audit
        $db->query("SELECT COUNT(id) as awaiting_audit FROM shops_renewal_authorization WHERE it_approval = 'Yes' AND second_level_approval = 'Yes' AND third_level_approval = ''");
        $summary['awaiting_audit'] = $db->single()['awaiting_audit'];

        // Declined by Audit
        $db->query("SELECT COUNT(id) as declined_by_audit FROM shops_renewal_authorization WHERE third_level_approval = 'No'");
        $summary['declined_by_audit'] = $db->single()['declined_by_audit'];

        return $summary;
    }

    //IT Expected Adjustment
    function getExpectedAdjustmentSummary($db) {
        $summary = [];

        // Total records with any of the statuses
        $query = "SELECT COUNT(id) as total FROM shops_expected_authorization WHERE status IN ('Open', 'Approved', 'Declined')";
        $db->query($query);
        $summary['total'] = $db->single()['total'];

        // Awaiting IT Approval
        $query = "SELECT COUNT(id) as awaiting_it FROM shops_expected_authorization WHERE it_approval = ''";
        $db->query($query);
        $summary['awaiting_it'] = $db->single()['awaiting_it'];

        // Declined by IT
        $query = "SELECT COUNT(id) as declined_by_it FROM shops_expected_authorization WHERE it_approval = 'No'";
        $db->query($query);
        $summary['declined_by_it'] = $db->single()['declined_by_it'];

        return $summary;
    }


    public function getDeclinedLeasingPostsByOfficer($officer_id) {
        $this->db->query("
            SELECT agt.*, c.id AS customer_id
            FROM account_general_transaction_new agt
            LEFT JOIN customers c ON agt.shop_no = c.shop_no
            WHERE agt.posting_officer_id = :officer_id
            AND agt.leasing_post_status = 'Declined'
            ORDER BY agt.leasing_post_approval_time DESC
        ");
        
        $this->db->bind(':officer_id', $officer_id);

        $results = $this->db->resultSet();
        
        if (!$results) {
            return []; // Or optionally throw an exception
        }

        // Process the result to format amount and extract needed fields
        $declinedPosts = [];
        foreach ($results as $post) {
            $declinedPosts[] = [
                'txref' => $post['id'],
                'shop_no' => $post['shop_no'],
                'customer_id' => $post['customer_id'],
                'amount_paid' => number_format((float)$post['amount_paid'], 0),
                'posting_time' => $post['posting_time'],
                'leasing_approval_time' => $post['leasing_post_approval_time'],
                'approval_time' => $post['approval_time'],
                'receipt_no' => $post['receipt_no'],
                'comment' => $post['comment'],
                'date_of_payment' => $post['date_of_payment']
            ];
        }

        return $declinedPosts;
    }

     public function calculateUnpostedBalance($officer_id, $current_date, $category = 'Other Collection') {
        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as amount_posted
            FROM account_general_transaction_new
            WHERE posting_officer_id = :officer_id
            AND payment_category = :category
            AND date_of_payment = :current_date
        ");
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':current_date', $current_date);
        $posted = $this->db->single();

        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as amount_remitted
            FROM cash_remittance
            WHERE remitting_officer_id = :officer_id
            AND category = :category
            AND date = :current_date
        ");
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':category', $category);
        $this->db->bind(':current_date', $current_date);
        $remitted = $this->db->single();

        return $remitted['amount_remitted'] - $posted['amount_posted'];
    }
    
    /**
     * Get transactions with pagination and filtering
     */
    public function getTransactions($page = 1, $per_page = 20, $date_from = null, $date_to = null, $status_filter = null, $staff_filter = null) {
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = [];
        $params = [];
        
        // Base condition for pending transactions
        if ($status_filter === 'pending') {
            $where_conditions[] = "leasing_post_status = 'Pending'";
        } elseif ($status_filter === 'fc_pending') {
            $where_conditions[] = "approval_status = 'Pending'";
        } elseif ($status_filter === 'audit_pending') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Pending'";
        } elseif ($status_filter === 'declined') {
            $where_conditions[] = "(approval_status = 'Declined' OR verification_status = 'Declined' OR leasing_post_status = 'Declined')";
        } elseif ($status_filter === 'approved') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Verified'";
        }
        
        // Staff filtering
        if ($staff_filter) {
            $where_conditions[] = "posting_officer_id = :staff_filter";
            $params[':staff_filter'] = $staff_filter;
        }
        
        // Date filtering
        if ($date_from && $date_to) {
            $where_conditions[] = "date_of_payment BETWEEN :date_from AND :date_to";
            $params[':date_from'] = $date_from;
            $params[':date_to'] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $this->db->query("
            SELECT t.*, 
                   da.acct_desc as debit_account_desc,
                   ca.acct_desc as credit_account_desc,
                   s.full_name as posting_officer_full_name
            FROM account_general_transaction_new t
            LEFT JOIN accounts da ON t.debit_account = da.acct_id
            LEFT JOIN accounts ca ON t.credit_account = ca.acct_id
            LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
            {$where_clause}
            ORDER BY t.date_of_payment DESC, t.posting_time DESC
            LIMIT :offset, :per_page
        ");
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':offset', $offset);
        $this->db->bind(':per_page', $per_page);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get total count for pagination
     */
    public function getTransactionCount($date_from = null, $date_to = null, $status_filter = null, $staff_filter = null) {
        $where_conditions = [];
        $params = [];
        
        if ($status_filter === 'pending') {
            $where_conditions[] = "leasing_post_status = 'Pending'";
        } elseif ($status_filter === 'fc_pending') {
            $where_conditions[] = "approval_status = 'Pending'";
        } elseif ($status_filter === 'audit_pending') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Pending'";
        } elseif ($status_filter === 'declined') {
            $where_conditions[] = "(approval_status = 'Declined' OR verification_status = 'Declined' OR leasing_post_status = 'Declined')";
        } elseif ($status_filter === 'approved') {
            $where_conditions[] = "approval_status = 'Approved' AND verification_status = 'Verified'";
        }
        
        if ($staff_filter) {
            $where_conditions[] = "posting_officer_id = :staff_filter";
            $params[':staff_filter'] = $staff_filter;
        }
        
        if ($date_from && $date_to) {
            $where_conditions[] = "date_of_payment BETWEEN :date_from AND :date_to";
            $params[':date_from'] = $date_from;
            $params[':date_to'] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $this->db->query("SELECT COUNT(*) as total FROM account_general_transaction_new {$where_clause}");
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result['total'];
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        // Pending posts
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE leasing_post_status = 'Pending'
        ");
        $pending_posts = $this->db->single();
        
        // Pending FC approvals
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE approval_status = 'Pending'
        ");
        $pending_fc = $this->db->single();
        
        // Pending audit verifications
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE approval_status = 'Approved' AND verification_status = 'Pending'
        ");
        $pending_audit = $this->db->single();
        
        // Declined transactions
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM account_general_transaction_new 
            WHERE approval_status = 'Declined' OR verification_status = 'Declined' OR leasing_post_status = 'Declined'
        ");
        $declined = $this->db->single();
        
        return [
            'pending_posts' => $pending_posts['count'],
            'pending_fc_approvals' => $pending_fc['count'],
            'pending_audit_verifications' => $pending_audit['count'],
            'declined_transactions' => $declined['count']
        ];
    }
    
    /**
     * Approve transaction
     */
    public function approveTransaction($transaction_id, $approver_id, $approver_name, $department) {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            if ($department === 'Accounts' || $department === 'FC') {
                // FC/Accounts approval
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET approval_status = 'Approved',
                        approving_acct_officer_id = :approver_id,
                        approving_acct_officer_name = :approver_name,
                        approval_time = :approval_time
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':approval_time', $now);
                $this->db->bind(':transaction_id', $transaction_id);
                
            } elseif ($department === 'Audit/Inspections') {
                // Audit verification
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET verification_status = 'Verified',
                        verifying_auditor_id = :approver_id,
                        verifying_auditor_name = :approver_name,
                        verification_time = :verification_time
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':verification_time', $now);
                $this->db->bind(':transaction_id', $transaction_id);
            }
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction approved successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error approving transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Decline transaction
     */
    public function declineTransaction($transaction_id, $approver_id, $approver_name, $department, $reason = '') {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            if ($department === 'Accounts' || $department === 'FC') {
                // FC/Accounts decline
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET approval_status = 'Declined',
                        approving_acct_officer_id = :approver_id,
                        approving_acct_officer_name = :approver_name,
                        approval_time = :approval_time,
                        decline_reason = :reason
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':approval_time', $now);
                $this->db->bind(':reason', $reason);
                $this->db->bind(':transaction_id', $transaction_id);
                
            } elseif ($department === 'Audit/Inspections') {
                // Audit decline
                $this->db->query("
                    UPDATE account_general_transaction_new 
                    SET verification_status = 'Declined',
                        verifying_auditor_id = :approver_id,
                        verifying_auditor_name = :approver_name,
                        verification_time = :verification_time,
                        decline_reason = :reason
                    WHERE id = :transaction_id
                ");
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':verification_time', $now);
                $this->db->bind(':reason', $reason);
                $this->db->bind(':transaction_id', $transaction_id);
            }
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction declined successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error declining transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Bulk approve transactions
     */
    public function bulkApproveTransactions($transaction_ids, $approver_id, $approver_name, $department) {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            $success_count = 0;
            
            foreach ($transaction_ids as $transaction_id) {
                if ($department === 'Accounts' || $department === 'FC') {
                    $this->db->query("
                        UPDATE account_general_transaction_new 
                        SET approval_status = 'Approved',
                            approving_acct_officer_id = :approver_id,
                            approving_acct_officer_name = :approver_name,
                            approval_time = :approval_time
                        WHERE id = :transaction_id
                        AND approval_status = 'Pending'
                    ");
                } elseif ($department === 'Audit/Inspections') {
                    $this->db->query("
                        UPDATE account_general_transaction_new 
                        SET verification_status = 'Verified',
                            verifying_auditor_id = :approver_id,
                            verifying_auditor_name = :approver_name,
                            verification_time = :verification_time
                        WHERE id = :transaction_id
                        AND verification_status = 'Pending'
                    ");
                }
                
                $this->db->bind(':approver_id', $approver_id);
                $this->db->bind(':approver_name', $approver_name);
                $this->db->bind(':approval_time', $now);
                $this->db->bind(':verification_time', $now);
                $this->db->bind(':transaction_id', $transaction_id);
                
                if ($this->db->execute()) {
                    $success_count++;
                }
            }
            
            $this->db->endTransaction();
            
            return [
                'success' => true, 
                'message' => "{$success_count} transactions approved successfully",
                'count' => $success_count
            ];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error in bulk approval: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get transaction details
     */
    public function getTransactionDetails($transaction_id) {
        $this->db->query("
            SELECT t.*, 
                   da.acct_desc as debit_account_desc,
                   ca.acct_desc as credit_account_desc,
                   s.full_name as posting_officer_full_name,
                   c.shop_no, c.tenant_name
            FROM account_general_transaction_new t
            LEFT JOIN accounts da ON t.debit_account = da.acct_id
            LEFT JOIN accounts ca ON t.credit_account = ca.acct_id
            LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
            LEFT JOIN customers c ON t.shop_no = c.shop_no
            WHERE t.id = :transaction_id
        ");
        
        $this->db->bind(':transaction_id', $transaction_id);
        return $this->db->single();
    }
    
    /**
     * Delete transaction (with all related entries)
     */
    public function deleteTransaction($transaction_id, $user_id) {
        $this->db->beginTransaction();
        
        try {
            // Get transaction details first
            $transaction = $this->getTransactionDetails($transaction_id);
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Check if user can delete (same day and own transaction or CE level)
            $today = date('Y-m-d');
            if ($transaction['date_of_payment'] !== $today && $transaction['posting_officer_id'] !== $user_id) {
                throw new Exception('Cannot delete this transaction');
            }
            
            // Delete from main table
            $this->db->query("DELETE FROM account_general_transaction_new WHERE id = :transaction_id");
            $this->db->bind(':transaction_id', $transaction_id);
            $this->db->execute();
            
            // Delete from collection analysis tables
            if ($transaction['payment_category'] === 'Service Charge') {
                $this->db->query("DELETE FROM collection_analysis_arena WHERE trans_id = :transaction_id");
            } else {
                $this->db->query("DELETE FROM collection_analysis WHERE trans_id = :transaction_id");
            }
            $this->db->bind(':transaction_id', $transaction_id);
            $this->db->execute();
            
            // Delete from account tables (if they exist)
            $debit_table = $this->getAccountTable($transaction['debit_account']);
            $credit_table = $this->getAccountTable($transaction['credit_account']);
            
            if ($debit_table) {
                $this->db->query("DELETE FROM {$debit_table} WHERE id = :transaction_id");
                $this->db->bind(':transaction_id', $transaction_id);
                $this->db->execute();
            }
            
            if ($credit_table) {
                $this->db->query("DELETE FROM {$credit_table} WHERE id = :transaction_id");
                $this->db->bind(':transaction_id', $transaction_id);
                $this->db->execute();
            }
            
            $this->db->endTransaction();
            return ['success' => true, 'message' => 'Transaction deleted successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error deleting transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get account table name
     */
    private function getAccountTable($account_id) {
        $this->db->query("SELECT acct_table_name FROM accounts WHERE acct_id = :account_id");
        $this->db->bind(':account_id', $account_id);
        $result = $this->db->single();
        return isset($result['acct_table_name']) ? $result['acct_table_name'] : null;
    }
    
    /**
     * Search transactions
     */
    public function searchTransactions($search_term, $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $this->db->query("
            SELECT t.*, 
                   da.acct_desc as debit_account_desc,
                   ca.acct_desc as credit_account_desc,
                   s.full_name as posting_officer_full_name
            FROM account_general_transaction_new t
            LEFT JOIN accounts da ON t.debit_account = da.acct_id
            LEFT JOIN accounts ca ON t.credit_account = ca.acct_id
            LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
            WHERE t.transaction_desc LIKE :search_term
            OR t.receipt_no LIKE :search_term
            OR t.shop_no LIKE :search_term
            OR t.plate_no LIKE :search_term
            OR s.full_name LIKE :search_term
            ORDER BY t.date_of_payment DESC
            LIMIT :offset, :per_page
        ");
        
        $this->db->bind(':search_term', '%' . $search_term . '%');
        $this->db->bind(':offset', $offset);
        $this->db->bind(':per_page', $per_page);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get staff by department
     */
    public function getStaffByDepartment($department) {
        $this->db->query("
            SELECT user_id, full_name 
            FROM staffs 
            WHERE department = :department 
            ORDER BY full_name ASC
        ");
        $this->db->bind(':department', $department);
        return $this->db->resultSet();
    }
    
    /**
     * Check user permissions
     */
    public function checkUserPermissions($user_id, $permission) {
        $this->db->query("
            SELECT {$permission} as has_permission
            FROM roles 
            WHERE user_id = :user_id
        ");
        $this->db->bind(':user_id', $user_id);
        $result = $this->db->single();
        
        return $result['has_permission'] === 'Yes';
    }
    
    /**
     * Get payment breakdown for shop transactions
     */
    public function getPaymentBreakdown($shop_id, $receipt_no, $payment_category) {
        $table = $payment_category === 'Service Charge' ? 'collection_analysis_arena' : 'collection_analysis';
        
        $this->db->query("
            SELECT payment_month, amount_paid
            FROM {$table}
            WHERE shop_id = :shop_id AND receipt_no = :receipt_no
            ORDER BY payment_month
        ");
        
        $this->db->bind(':shop_id', $shop_id);
        $this->db->bind(':receipt_no', $receipt_no);
        
        return $this->db->resultSet();
    }
    
    /**
     * Time elapsed helper function
     */
    public function timeElapsed($datetime) {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return 'N/A';
        }
        
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }
    
    /**
     * Review approve transaction (Account department)
     */
    public function reviewApproveTransaction($transaction_id, $reviewer_id, $reviewer_name) {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET leasing_post_status = 'Approved',
                    leasing_post_approving_officer_id = :reviewer_id,
                    leasing_post_approving_officer_name = :reviewer_name,
                    leasing_post_approval_time = :approval_time,
                    approval_status = 'Pending'
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':reviewer_id', $reviewer_id);
            $this->db->bind(':reviewer_name', $reviewer_name);
            $this->db->bind(':approval_time', $now);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction reviewed and moved to FC for approval'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error reviewing transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Review decline transaction (Account department)
     */
    public function reviewDeclineTransaction($transaction_id, $reviewer_id, $reviewer_name, $reason = '') {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET leasing_post_status = 'Declined',
                    leasing_post_approving_officer_id = :reviewer_id,
                    leasing_post_approving_officer_name = :reviewer_name,
                    leasing_post_approval_time = :approval_time,
                    decline_reason = :reason
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':reviewer_id', $reviewer_id);
            $this->db->bind(':reviewer_name', $reviewer_name);
            $this->db->bind(':approval_time', $now);
            $this->db->bind(':reason', $reason);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction declined during review'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error declining transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Flag transaction
     */
    public function flagTransaction($transaction_id, $auditor_id, $auditor_name, $flag_reason = '') {
        $this->db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET verification_status = 'Flagged',
                    flag_status = 'Flagged',
                    verifying_auditor_id = :auditor_id,
                    verifying_auditor_name = :auditor_name,
                    verification_time = :verification_time,
                    flag_reason = :flag_reason
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':auditor_id', $auditor_id);
            $this->db->bind(':auditor_name', $auditor_name);
            $this->db->bind(':verification_time', $now);
            $this->db->bind(':flag_reason', $flag_reason);
            $this->db->bind(':transaction_id', $transaction_id);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction flagged successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error flagging transaction: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update transaction
     */
    public function updateTransaction($data) {
        $this->db->beginTransaction();
        
        try {
            $this->db->query("
                UPDATE account_general_transaction_new 
                SET transaction_desc = :transaction_desc,
                    amount_paid = :amount_paid,
                    receipt_no = :receipt_no,
                    no_of_tickets = :no_of_tickets,
                    plate_no = :plate_no,
                    shop_no = :shop_no
                WHERE id = :transaction_id
            ");
            
            $this->db->bind(':transaction_desc', $data['transaction_desc']);
            $this->db->bind(':amount_paid', $data['amount_paid']);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':no_of_tickets', $data['no_of_tickets']);
            $this->db->bind(':plate_no', $data['plate_no']);
            $this->db->bind(':shop_no', $data['shop_no']);
            $this->db->bind(':transaction_id', $data['transaction_id']);
            
            $this->db->execute();
            $this->db->endTransaction();
            
            return ['success' => true, 'message' => 'Transaction updated successfully'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error updating transaction: ' . $e->getMessage()];
        }
    }
}
?>
