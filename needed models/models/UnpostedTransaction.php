
<?php
// require_once 'config/Database.php';

class UnpostedTransaction {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
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

        //Usage:
        // $declinedPosts = $otherTransaction->getDeclinedLeasingPostsByOfficer($session_id);

        //     foreach ($declinedPosts as $post) {
        //         echo $post['shop_no'] . ' - ' . $post['amount_paid'] . '<br>';
        // }

    }

    
}
?>
