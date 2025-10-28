<?php
// require_once 'Database.php';
class PaymentProcessor {
    private $db;
    
    // public function __construct() {
    //     $this->db = new Database();
    // }
    public function __construct($databaseObj) {
        $this->db = $databaseObj;
    }
    
    /**
     * Gat the account income line
     */
    public function getIncomeLineAccounts() {
        $query = "SELECT * 
                    FROM accounts 
                    WHERE page_visibility = 'General' AND active = 'Yes' 
                    ORDER BY acct_desc ASC";
        $this->db->query($query);
        return $this->db->resultSet();
    }
    /**
     * Check if receipt number already exists
     */
    // public function checkReceiptExists($receipt_no) {
    //     $this->db->query("SELECT posting_officer_name, date_of_payment FROM account_general_transaction_new WHERE receipt_no = :receipt_no");
    //     $this->db->bind(':receipt_no', $receipt_no);
    //     return $this->db->single();
    // }
    
    /**
     * Get staff information
     */
    // public function getStaffInfo($staff_id, $staff_type = 'wc') {
    //     if ($staff_type === 'wc') {
    //         $this->db->query("SELECT full_name FROM staffs WHERE user_id = :staff_id");
    //     } else {
    //         $this->db->query("SELECT full_name FROM staffs_others WHERE id = :staff_id");
    //     }
    //     $this->db->bind(':staff_id', $staff_id);
    //     return $this->db->single();
    // }
    
    /**
     * Get account information
     */
    // public function getAccountInfo($account_identifier, $by_alias = false) {
    //     if ($by_alias) {
    //         $this->db->query("SELECT acct_id, acct_table_name, acct_desc FROM accounts WHERE acct_alias = :identifier");
    //     } else {
    //         $this->db->query("SELECT acct_id, acct_table_name, acct_desc FROM accounts WHERE acct_id = :identifier");
    //     }
    //     $this->db->bind(':identifier', $account_identifier);
    //     return $this->db->single();
    // }
    
    /**
     * Get remittance balance for Wealth Creation staff
     */
    // public function getRemittanceBalance($posting_officer_id, $current_date) {
    //     // Get amount posted today
    //     $this->db->query("
    //         SELECT COALESCE(SUM(amount_paid), 0) as amount_posted 
    //         FROM account_general_transaction_new 
    //         WHERE posting_officer_id = :officer_id 
    //         AND payment_category = 'Other Collection' 
    //         AND date_of_payment = :current_date
    //     ");
    //     $this->db->bind(':officer_id', $posting_officer_id);
    //     $this->db->bind(':current_date', $current_date);
    //     $posted = $this->db->single();
        
    //     // Get amount remitted today
    //     $this->db->query("
    //         SELECT COALESCE(SUM(amount_paid), 0) as amount_remitted, remit_id, date
    //         FROM cash_remittance 
    //         WHERE remitting_officer_id = :officer_id 
    //         AND category = 'Other Collection' 
    //         AND date = :current_date
    //     ");
    //     $this->db->bind(':officer_id', $posting_officer_id);
    //     $this->db->bind(':current_date', $current_date);
    //     $remitted = $this->db->single();
        
    //     return [
    //         'amount_posted' => $posted['amount_posted'],
    //         'amount_remitted' => $remitted['amount_remitted'],
    //         'unposted' => $remitted['amount_remitted'] - $posted['amount_posted'],
    //         'remit_id' => isset($remitted['remit_id']) ? $remitted['remit_id'] : '',
    //         'date' => isset($remitted['date']) ? $remitted['date'] : ''
    //     ];
    // }

    /**
     * Get till Balance for officers
     */
    public function totalTillBalance($officerId) {
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
    
    /**
     * Process car park payment
     */
    // public function processCarParkPayment($data) {
    //     $this->db->beginTransaction();
        
    //     try {
    //         $txref = time() . mt_rand(0, 9);
    //         $now = date('Y-m-d H:i:s');
            
    //         // Insert main transaction
    //         $this->db->query("
    //             INSERT INTO account_general_transaction_new (
    //                 id, date_of_payment, ticket_category, transaction_desc, receipt_no, 
    //                 amount_paid, remitting_id, remitting_staff, posting_officer_id, 
    //                 posting_officer_name, posting_time, leasing_post_status, approval_status, 
    //                 verification_status, debit_account, credit_account, payment_category, 
    //                 no_of_tickets, remit_id, income_line
    //             ) VALUES (
    //                 :txref, :date_payment, :ticket_category, :trans_desc, :receipt_no,
    //                 :amount_paid, :remitting_id, :remitting_staff, :posting_officer_id,
    //                 :posting_officer_name, :posting_time, :leasing_status, :approval_status,
    //                 :verification_status, :debit_account, :credit_account, :payment_category,
    //                 :no_tickets, :remit_id, :income_line
    //             )
    //         ");
            
    //         $this->db->bind(':txref', $txref);
    //         $this->db->bind(':date_payment', $data['date_of_payment']);
    //         $this->db->bind(':ticket_category', $data['ticket_category']);
    //         $this->db->bind(':trans_desc', $data['transaction_desc']);
    //         $this->db->bind(':receipt_no', $data['receipt_no']);
    //         $this->db->bind(':amount_paid', $data['amount_paid']);
    //         $this->db->bind(':remitting_id', $data['remitting_id']);
    //         $this->db->bind(':remitting_staff', $data['remitting_staff']);
    //         $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
    //         $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
    //         $this->db->bind(':posting_time', $now);
    //         $this->db->bind(':leasing_status', $data['leasing_post_status']);
    //         $this->db->bind(':approval_status', $data['approval_status']);
    //         $this->db->bind(':verification_status', $data['verification_status']);
    //         $this->db->bind(':debit_account', $data['debit_account']);
    //         $this->db->bind(':credit_account', $data['credit_account']);
    //         $this->db->bind(':payment_category', 'Other Collection');
    //         $this->db->bind(':no_tickets', $data['no_of_tickets']);
    //         $this->db->bind(':remit_id', $data['remit_id']);
    //         $this->db->bind(':income_line', $data['income_line']);
            
    //         $this->db->execute();
            
    //         // Insert debit entry
    //         $this->db->query("
    //             INSERT INTO {$data['db_debit_table']} (
    //                 id, acct_id, date, receipt_no, trans_desc, debit_amount, balance, approval_status
    //             ) VALUES (
    //                 :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, :balance, :approval_status
    //             )
    //         ");
            
    //         $this->db->bind(':txref', $txref);
    //         $this->db->bind(':acct_id', $data['debit_account']);
    //         $this->db->bind(':date', $data['date_of_payment']);
    //         $this->db->bind(':receipt_no', $data['receipt_no']);
    //         $this->db->bind(':trans_desc', $data['transaction_desc']);
    //         $this->db->bind(':amount', $data['amount_paid']);
    //         $this->db->bind(':balance', '');
    //         $this->db->bind(':approval_status', $data['approval_status']);
            
    //         $this->db->execute();
            
    //         // Insert credit entry
    //         $this->db->query("
    //             INSERT INTO {$data['db_credit_table']} (
    //                 id, acct_id, date, receipt_no, trans_desc, credit_amount, balance, approval_status
    //             ) VALUES (
    //                 :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, :balance, :approval_status
    //             )
    //         ");
            
    //         $this->db->bind(':txref', $txref);
    //         $this->db->bind(':acct_id', $data['credit_account']);
    //         $this->db->bind(':date', $data['date_of_payment']);
    //         $this->db->bind(':receipt_no', $data['receipt_no']);
    //         $this->db->bind(':trans_desc', $data['transaction_desc']);
    //         $this->db->bind(':amount', $data['amount_paid']);
    //         $this->db->bind(':balance', '');
    //         $this->db->bind(':approval_status', $data['approval_status']);
            
    //         $this->db->execute();
            
    //         $this->db->endTransaction();
    //         return ['success' => true, 'message' => 'Payment successfully posted for approval!'];
            
    //     } catch (Exception $e) {
    //         $this->db->cancelTransaction();
    //         return ['success' => false, 'message' => 'Error occurred while posting: ' . $e->getMessage()];
    //     }
    // }
    
    /**
     * Get staff list for dropdown
     */
    // public function getStaffList($department = null) {
    //     if ($department) {
    //         $this->db->query("SELECT user_id, full_name FROM staffs WHERE department = :department ORDER BY full_name ASC");
    //         $this->db->bind(':department', $department);
    //     } else {
    //         $this->db->query("SELECT user_id, full_name FROM staffs ORDER BY full_name ASC");
    //     }
        
    //     return $this->db->resultSet();
    // }
    
    /**
     * Get other staff list
     */
    // public function getOtherStaffList() {
    //     $this->db->query("SELECT id, full_name, department FROM staffs_others ORDER BY full_name ASC");
    //     return $this->db->resultSet();
    // }

    /**
     * Check if receipt number already exists
     */
    public function checkReceiptExists($receipt_no) {
        $this->db->query("SELECT posting_officer_name, date_of_payment FROM account_general_transaction_new WHERE receipt_no = :receipt_no");
        $this->db->bind(':receipt_no', $receipt_no);
        return $this->db->single();
    }
    
    /**
     * Get staff information
     */
    public function getStaffInfo($staff_id, $staff_type = 'wc') {
        if ($staff_type === 'wc') {
            $this->db->query("SELECT full_name FROM staffs WHERE user_id = :staff_id");
        } else {
            $this->db->query("SELECT full_name FROM staffs_others WHERE id = :staff_id");
        }
        $this->db->bind(':staff_id', $staff_id);
        return $this->db->single();
    }
    
    /**
     * Get account information
     */
    public function getAccountInfo($account_identifier, $by_alias = true) {
        // Debug check
        //  echo "<pre>DEBUG: account_identifier = " . htmlspecialchars($account_identifier) . 
        //  " | by_alias = " . ($by_alias ? 'true' : 'false') . "</pre>";
        if ($by_alias) {
            $this->db->query("SELECT acct_id, acct_table_name, acct_desc FROM accounts WHERE acct_id = :identifier");
        } else {
            $this->db->query("SELECT acct_id, acct_table_name, acct_desc FROM accounts WHERE acct_id = :identifier");
        }
        $this->db->bind(':identifier', $account_identifier);
        return $this->db->single();
    }
    
    /**
     * Get remittance balance for Wealth Creation staff
     */
    public function getRemittanceBalance($posting_officer_id, $current_date) {
        // Get amount posted today
        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as amount_posted 
            FROM account_general_transaction_new 
            WHERE posting_officer_id = :officer_id 
            AND payment_category = 'Other Collection' 
            AND date_of_payment = :current_date
        ");
        $this->db->bind(':officer_id', $posting_officer_id);
        $this->db->bind(':current_date', $current_date);
        $posted = $this->db->single();
        
        // Get amount remitted today
        $this->db->query("
            SELECT COALESCE(SUM(amount_paid), 0) as amount_remitted, remit_id, date
            FROM cash_remittance 
            WHERE remitting_officer_id = :officer_id 
            AND category = 'Other Collection' 
            AND date = :current_date
        ");
        $this->db->bind(':officer_id', $posting_officer_id);
        $this->db->bind(':current_date', $current_date);
        $remitted = $this->db->single();
        
        return [
            'amount_posted' => $posted['amount_posted'],
            'amount_remitted' => $remitted['amount_remitted'],
            'unposted' => $remitted['amount_remitted'] - $posted['amount_posted'],
            'remit_id' => isset($remitted['remit_id']) ? $remitted['remit_id'] : '',
            'date' => isset($remitted['date']) ? $remitted['date'] : ''
        ];
    }
    
    /**
     * Process car park payment
     */
    public function processCarParkPayment($data) {
        $this->db->beginTransaction();
        
        try {
            $txref = time() . mt_rand(0, 9);
            $now = date('Y-m-d H:i:s');
            
            // Insert main transaction
            $this->db->query("
                INSERT INTO account_general_transaction_new (
                    id, date_of_payment, ticket_category, transaction_desc, receipt_no, 
                    amount_paid, remitting_id, remitting_staff, posting_officer_id, 
                    posting_officer_name, posting_time, leasing_post_status, approval_status, 
                    verification_status, debit_account, credit_account, payment_category, 
                    no_of_tickets, remit_id, income_line
                ) VALUES (
                    :txref, :date_payment, :ticket_category, :trans_desc, :receipt_no,
                    :amount_paid, :remitting_id, :remitting_staff, :posting_officer_id,
                    :posting_officer_name, :posting_time, :leasing_status, :approval_status,
                    :verification_status, :debit_account, :credit_account, :payment_category,
                    :no_tickets, :remit_id, :income_line
                )
            ");
            
            $this->db->bind(':txref', $txref);
            $this->db->bind(':date_payment', $data['date_of_payment']);
            $this->db->bind(':ticket_category', $data['ticket_category']);
            $this->db->bind(':trans_desc', $data['transaction_desc']);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':amount_paid', $data['amount_paid']);
            $this->db->bind(':remitting_id', $data['remitting_id']);
            $this->db->bind(':remitting_staff', $data['remitting_staff']);
            $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
            $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
            $this->db->bind(':posting_time', $now);
            $this->db->bind(':leasing_status', $data['leasing_post_status']);
            $this->db->bind(':approval_status', $data['approval_status']);
            $this->db->bind(':verification_status', $data['verification_status']);
            $this->db->bind(':debit_account', $data['debit_account']);
            $this->db->bind(':credit_account', $data['credit_account']);
            $this->db->bind(':payment_category', 'Other Collection');
            $this->db->bind(':no_tickets', $data['no_of_tickets']);
            $this->db->bind(':remit_id', $data['remit_id']);
            $this->db->bind(':income_line', $data['income_line']);
            
            $this->db->execute();
            
            // Insert debit entry
            $this->db->query("
                INSERT INTO {$data['db_debit_table']} (
                    id, acct_id, date, receipt_no, trans_desc, debit_amount, balance, approval_status
                ) VALUES (
                    :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, :balance, :approval_status
                )
            ");
            
            $this->db->bind(':txref', $txref);
            $this->db->bind(':acct_id', $data['debit_account']);
            $this->db->bind(':date', $data['date_of_payment']);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':trans_desc', $data['transaction_desc']);
            $this->db->bind(':amount', $data['amount_paid']);
            $this->db->bind(':balance', '');
            $this->db->bind(':approval_status', $data['approval_status']);
            
            $this->db->execute();
            
            // Insert credit entry
            $this->db->query("
                INSERT INTO {$data['db_credit_table']} (
                    id, acct_id, date, receipt_no, trans_desc, credit_amount, balance, approval_status
                ) VALUES (
                    :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, :balance, :approval_status
                )
            ");
            
            $this->db->bind(':txref', $txref);
            $this->db->bind(':acct_id', $data['credit_account']);
            $this->db->bind(':date', $data['date_of_payment']);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':trans_desc', $data['transaction_desc']);
            $this->db->bind(':amount', $data['amount_paid']);
            $this->db->bind(':balance', '');
            $this->db->bind(':approval_status', $data['approval_status']);
            
            $this->db->execute();
            
            $this->db->endTransaction();
            return ['success' => true, 'message' => 'Payment successfully posted for approval!'];
            
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error occurred while posting: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get staff list for dropdown
     */
    public function getStaffList($department = null) {
        if ($department) {
            $this->db->query("SELECT user_id, full_name FROM staffs WHERE department = :department ORDER BY full_name ASC");
            $this->db->bind(':department', $department);
        } else {
            $this->db->query("SELECT user_id, full_name FROM staffs ORDER BY full_name ASC");
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get other staff list
     */
    public function getOtherStaffList() {
        $this->db->query("SELECT id, full_name, department FROM staffs_others ORDER BY full_name ASC");
        return $this->db->resultSet();
    }

    /**
     * Validate posting data
     */
    public function validatePosting($data) {
        $errors = [];

        if (empty($data['date_of_payment'])) {
            $errors[] = 'Date of payment is required';
        }

        if (empty($data['receipt_no']) || !preg_match('/^\d{7}$/', $data['receipt_no'])) {
            $errors[] = 'Receipt number is required or invalid characters';
        } else {
            $existing = $this->checkReceiptExists($data['receipt_no']);
            if ($existing) {
                $errors[] = "Receipt No: {$data['receipt_no']} already exists (used by {$existing['posting_officer_name']} on {$existing['date_of_payment']})";
            }
        }

    if (empty($data['remitting_staff'])) {
            $errors[] = 'The remitting staff name is required';
        }

        if (empty($data['amount_paid']) || !is_numeric($data['amount_paid']) || $data['amount_paid'] <= 0) {
            $errors[] = 'Amount must be a valid number and greater than 0.';
        }
//var_dump($data['income_line']);
//var_dump($data['income_line_type']);

//exit();
        if($data['income_line_type'] !== "car_sticker" AND $data['income_line_type'] !== "car_park" AND $data['income_line_type'] !== "loading"){
        if (empty($data['transaction_desc'])) {
            $errors[] = 'Transaction description is required';
        }
        }

        if (empty($data['debit_account'])) {
            $errors[] = 'Debit account is required';
        }

        if (empty($data['credit_account'])) {
            $errors[] = 'Credit account (income line) is required';
        }



        if ($data['posting_officer_dept'] == 'Wealth Creation' && !empty($data['remit_id'])) {
            $balance = $this->getRemittanceBalance($data['posting_officer_id'], $data['current_date']);

            if ($data['amount_paid'] > $balance['unposted']) {
                $errors[] = "Amount (₦" . number_format($data['amount_paid'], 2) . ") exceeds remittance balance (₦" . number_format($balance['unposted'], 2) . ")";
            }

            if (!empty($data['amt_remitted']) && $data['amt_remitted'] > $balance['unposted']) {
                $errors[] = "WARNING: Remittance balance mismatch. Expected ₦" . number_format($balance['unposted'], 2) . " but got ₦" . number_format($data['amt_remitted'], 2);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Process general payment posting
     */
    public function processPayment($data) {
        try {
            $txref = time() . mt_rand(0, 9);
            $now = date('Y-m-d H:i:s');

            list($tid, $tim, $tiy) = explode("/", $data['date_of_payment']);
            $date_of_payment = "$tiy-$tim-$tid";

            $amount_paid = preg_replace('/[,]/', '', $data['amount_paid']);

            $remitting_post = $data['remitting_staff'];
            list($remitting_id, $remitting_check) = explode("-", $remitting_post);

            if ($remitting_check == "wc") {
                $staff_info = $this->getStaffInfo($remitting_id, 'wc');
            } else {
                $staff_info = $this->getStaffInfo($remitting_id, 'other');
            }
            $remitting_staff = isset($staff_info['full_name']) ? $staff_info['full_name'] : 'Unknown';

            $transaction_desc = strip_tags($data['transaction_desc']);
            $transaction_desc = htmlspecialchars($transaction_desc);

            if ($data['posting_officer_dept'] == "Accounts") {
                $leasing_post_status = "";
                $approval_status = "Pending";
                $verification_status = "Pending";
            } else {
                $leasing_post_status = "Pending";
                $approval_status = "";
                $verification_status = "";
            }

            $debit_account_info = $this->getAccountInfo($data['debit_account']);
            $credit_account_info = $this->getAccountInfo($data['credit_account']);

            if (!$debit_account_info || !$credit_account_info) {
                return ['success' => false, 'message' => 'Invalid account information selection'];
            }

            $transaction_desc = $credit_account_info['acct_desc'] . ' - ' . $transaction_desc;
            $income_line = $data['income_line'] ?: $credit_account_info['acct_desc'];

            $this->db->query("
                INSERT INTO account_general_transaction_new (
                    id, date_of_payment, transaction_desc, receipt_no, amount_paid,
                    remitting_id, remitting_staff, posting_officer_id, posting_officer_name,
                    posting_time, leasing_post_status, approval_status, verification_status,
                    debit_account, credit_account, payment_category, remit_id, income_line
                ) VALUES (
                    :txref, :date_payment, :trans_desc, :receipt_no, :amount_paid,
                    :remitting_id, :remitting_staff, :officer_id, :officer_name,
                    :posting_time, :leasing_status, :approval_status, :verification_status,
                    :debit_account, :credit_account, :payment_category, :remit_id, :income_line
                )
            ");

            $this->db->bind(':txref', $txref);
            $this->db->bind(':date_payment', $date_of_payment);
            $this->db->bind(':trans_desc', $transaction_desc);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':amount_paid', $amount_paid);
            $this->db->bind(':remitting_id', $remitting_id);
            $this->db->bind(':remitting_staff', $remitting_staff);
            $this->db->bind(':officer_id', $data['posting_officer_id']);
            $this->db->bind(':officer_name', $data['posting_officer_name']);
            $this->db->bind(':posting_time', $now);
            $this->db->bind(':leasing_status', $leasing_post_status);
            $this->db->bind(':approval_status', $approval_status);
            $this->db->bind(':verification_status', $verification_status);
            $this->db->bind(':debit_account', $debit_account_info['acct_id']);
            $this->db->bind(':credit_account', $credit_account_info['acct_id']);
            $this->db->bind(':payment_category', 'Other Collection');
            $this->db->bind(':remit_id', $data['remit_id']);
            $this->db->bind(':income_line', $income_line);

            $this->db->execute();

            if ($debit_account_info['acct_table_name']) {
                $this->db->query("
                    INSERT INTO {$debit_account_info['acct_table_name']} (
                        id, acct_id, date, receipt_no, trans_desc, debit_amount, balance, approval_status
                    ) VALUES (
                        :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, '', :approval_status
                    )
                ");

                $this->db->bind(':txref', $txref);
                $this->db->bind(':acct_id', $debit_account_info['acct_id']);
                $this->db->bind(':date', $date_of_payment);
                $this->db->bind(':receipt_no', $data['receipt_no']);
                $this->db->bind(':trans_desc', $transaction_desc);
                $this->db->bind(':amount', $amount_paid);
                $this->db->bind(':approval_status', $approval_status);

                $this->db->execute();
            }

            if ($credit_account_info['acct_table_name']) {
                $this->db->query("
                    INSERT INTO {$credit_account_info['acct_table_name']} (
                        id, acct_id, date, receipt_no, trans_desc, credit_amount, balance, approval_status
                    ) VALUES (
                        :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, '', :approval_status
                    )
                ");

                $this->db->bind(':txref', $txref);
                $this->db->bind(':acct_id', $credit_account_info['acct_id']);
                $this->db->bind(':date', $date_of_payment);
                $this->db->bind(':receipt_no', $data['receipt_no']);
                $this->db->bind(':trans_desc', $transaction_desc);
                $this->db->bind(':amount', $amount_paid);
                $this->db->bind(':approval_status', $approval_status);

                $this->db->execute();
            }

            return ['success' => true, 'message' => 'Payment successfully posted for approval!'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error posting payment: ' . $e->getMessage()];
        }
    }
     /* Process income line payment (unified for all income types)
     */
    public function processIncomeLine($data) {
        try {
            $txref = time() . mt_rand(0, 9);
            $now = date('Y-m-d H:i:s');

            $date_of_payment = $data['date_of_payment'];
            if (strpos($date_of_payment, '/') !== false) {
                list($tid, $tim, $tiy) = explode("/", $date_of_payment);
                $date_of_payment = "$tiy-$tim-$tid";
            }

            $amount_paid = preg_replace('/[,]/', '', $data['amount_paid']);

            $remitting_post = $data['remitting_staff'];
            list($remitting_id, $remitting_check) = explode("-", $remitting_post);

            if ($remitting_check == "wc") {
                $staff_info = $this->getStaffInfo($remitting_id, 'wc');
            } else {
                $staff_info = $this->getStaffInfo($remitting_id, 'other');
            }
            $remitting_staff = isset($staff_info['full_name']) ? $staff_info['full_name'] : 'Unknown';

            $transaction_desc = strip_tags($data['transaction_desc']);
            $transaction_desc = htmlspecialchars($transaction_desc);

            if ($data['posting_officer_dept'] == "Accounts") {
                $leasing_post_status = "";
                $approval_status = "Pending";
                $verification_status = "Pending";
            } else {
                $leasing_post_status = "Pending";
                $approval_status = "";
                $verification_status = "";
            }

            $debit_account_info = $this->getAccountInfo($data['debit_account']);
            $credit_account_info = $this->getAccountInfo($data['credit_account']);

            if (!$debit_account_info || !$credit_account_info) {
                return ['success' => false, 'message' => 'Invalid account selection'];
            }

            $full_transaction_desc = $credit_account_info['acct_desc'] . ' - ' . $transaction_desc;
            $income_line = $data['income_line_type'] ? $data['income_line_type']: "";

            $payment_category = 'Other Collection';
            $ticket_category = isset($data['ticket_category']) ? $data['ticket_category'] : '';
            $no_of_tickets   = isset($data['no_of_tickets']) ? $data['no_of_tickets'] : '';
            //$category        = isset($data['category']) ? $data['category'] : '';
            $plate_no        = isset($data['plate_no']) ? $data['plate_no'] : '';
            $no_of_days      = isset($data['no_of_days']) ? $data['no_of_days'] : '';
            //$quantity        = isset($data['quantity']) ? $data['quantity'] : '';
            $no_of_nights    = isset($data['no_of_nights']) ? $data['no_of_nights'] : '';
            //$type            = isset($data['type']) ? $data['type'] : '';
           // $board_name      = isset($data['board_name']) ? $data['board_name'] : '';

            $this->db->query("
                INSERT INTO account_general_transaction_new (
                    id, date_of_payment, transaction_desc, receipt_no, amount_paid,
                    remitting_id, remitting_staff, posting_officer_id, posting_officer_name,
                    posting_time, leasing_post_status, approval_status, verification_status,
                    debit_account, credit_account, payment_category, remit_id, income_line,
                    ticket_category, no_of_tickets, plate_no, no_of_days,
                     no_of_nights
                ) VALUES (
                    :txref, :date_payment, :trans_desc, :receipt_no, :amount_paid,
                    :remitting_id, :remitting_staff, :officer_id, :officer_name,
                    :posting_time, :leasing_status, :approval_status, :verification_status,
                    :debit_account, :credit_account, :payment_category, :remit_id, :income_line,
                    :ticket_category, :no_of_tickets, :plate_no, :no_of_days,
                     :no_of_nights
                )
            ");

            $this->db->bind(':txref', $txref);
            $this->db->bind(':date_payment', $date_of_payment);
            $this->db->bind(':trans_desc', $full_transaction_desc);
            $this->db->bind(':receipt_no', $data['receipt_no']);
            $this->db->bind(':amount_paid', $amount_paid);
            $this->db->bind(':remitting_id', $remitting_id);
            $this->db->bind(':remitting_staff', $remitting_staff);
            $this->db->bind(':officer_id', $data['posting_officer_id']);
            $this->db->bind(':officer_name', $data['posting_officer_name']);
            $this->db->bind(':posting_time', $now);
            $this->db->bind(':leasing_status', $leasing_post_status);
            $this->db->bind(':approval_status', $approval_status);
            $this->db->bind(':verification_status', $verification_status);
            $this->db->bind(':debit_account', $debit_account_info['acct_id']);
            $this->db->bind(':credit_account', $credit_account_info['acct_id']);
            $this->db->bind(':payment_category', $payment_category);
            $this->db->bind(':remit_id', $data['remit_id']);
            $this->db->bind(':income_line', $income_line);
            $this->db->bind(':ticket_category', $ticket_category);
            $this->db->bind(':no_of_tickets', $no_of_tickets);
            //$this->db->bind(':category', $category);
            $this->db->bind(':plate_no', $plate_no);
            $this->db->bind(':no_of_days', $no_of_days);
            //$this->db->bind(':quantity', $quantity);
            $this->db->bind(':no_of_nights', $no_of_nights);
            // $this->db->bind(':type', $type);
            // $this->db->bind(':board_name', $board_name);

            $this->db->execute();

            if ($debit_account_info['acct_table_name']) {
                $this->db->query("
                    INSERT INTO {$debit_account_info['acct_table_name']} (
                        id, acct_id, date, receipt_no, trans_desc, debit_amount, balance, approval_status
                    ) VALUES (
                        :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, '', :approval_status
                    )
                ");

                $this->db->bind(':txref', $txref);
                $this->db->bind(':acct_id', $debit_account_info['acct_id']);
                $this->db->bind(':date', $date_of_payment);
                $this->db->bind(':receipt_no', $data['receipt_no']);
                $this->db->bind(':trans_desc', $full_transaction_desc);
                $this->db->bind(':amount', $amount_paid);
                $this->db->bind(':approval_status', $approval_status);

                $this->db->execute();
            }

            if ($credit_account_info['acct_table_name']) {
                $this->db->query("
                    INSERT INTO {$credit_account_info['acct_table_name']} (
                        id, acct_id, date, receipt_no, trans_desc, credit_amount, balance, approval_status
                    ) VALUES (
                        :txref, :acct_id, :date, :receipt_no, :trans_desc, :amount, '', :approval_status
                    )
                ");

                $this->db->bind(':txref', $txref);
                $this->db->bind(':acct_id', $credit_account_info['acct_id']);
                $this->db->bind(':date', $date_of_payment);
                $this->db->bind(':receipt_no', $data['receipt_no']);
                $this->db->bind(':trans_desc', $full_transaction_desc);
                $this->db->bind(':amount', $amount_paid);
                $this->db->bind(':approval_status', $approval_status);

                $this->db->execute();
            }

            return ['success' => true, 'message' => 'Payment successfully posted for approval!'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error posting payment: ' . $e->getMessage()];
        }
    }
}
?>