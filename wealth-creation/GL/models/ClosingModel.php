<?php
require_once __DIR__ . '/../helpers/DB.php';

class ClosingModel {

    private $db;

    public function __construct() {
        $this->db = DB::conn();
    }

    public function getFiscalPeriods() {
        $sql = "SELECT id, name FROM fiscal_periods ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function closePeriod($period) {
        try {
            // 1. Close Revenue accounts → Income Summary
            $this->db->exec("
                INSERT INTO journal_entries (entry_date, description, entry_type, status)
                VALUES (date('now'), 'Closing Revenues - Period $period', 'Reclassification', 'Posted')
            ");
            
            $journalId = $this->db->lastInsertId();

            // 2. Create opening balances for next period
            $this->db->exec("
                INSERT INTO opening_balances (acct_id, period_id, amount, side)
                SELECT
                    a.acct_id,
                    (SELECT id FROM fiscal_periods WHERE id = (SELECT MAX(id) FROM fiscal_periods) + 1 LIMIT 1),
                    ABS(SUM(CASE WHEN ag.debit_account=a.acct_id THEN ag.amount_paid ELSE -ag.amount_paid END)),
                    CASE WHEN SUM(CASE WHEN ag.debit_account=a.acct_id THEN ag.amount_paid ELSE -ag.amount_paid END) > 0 THEN 'Debit' ELSE 'Credit' END
                FROM accounts a
                LEFT JOIN account_general_transaction_new ag
                     ON (ag.debit_account=a.acct_id OR ag.credit_account=a.acct_id)
                     AND ag.approval_status='Approved'
                WHERE a.acct_type NOT IN ('Income', 'Expense')
                GROUP BY a.acct_id
            ");

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>