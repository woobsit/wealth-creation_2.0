<?php
// models/Journal.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

class PeriodClosingEngine {

    protected $db;

    public function __construct($db){
        $this->db = $db;
    }

    /**
     * Perform Year End Close:
     * 1. Calculate balances from AGTN
     * 2. Close Income & Expense accounts
     * 3. Move net profit/loss to Retained Earnings
     * 4. Generate opening entries for new year
     */
    public function closeFinancialPeriod($period_end, $next_period_start, $retained_earnings_acct) {

        $this->db->beginTransaction();
        try {

            // STEP 1 — Get all ledger balances from AGTN (Trial Balance)
            $this->db->query("
                SELECT 
                    a.acct_id,
                    a.acct_code,
                    a.acct_alias,
                    a.acct_desc,
                    a.acct_class_type,
                    SUM(CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_debit,
                    SUM(CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_credit
                FROM accounts a
                LEFT JOIN account_general_transaction_new ag
                    ON ag.debit_account = a.acct_id
                    OR ag.credit_account = a.acct_id
                WHERE ag.approval_status='Approved'
                GROUP BY a.acct_id, a.acct_code, a.acct_alias, a.acct_class_type
            ");

            $balances = $this->db->resultSet();

            $net_income = 0;

            // STEP 2 — Create closing entries
            foreach ($balances as $row) {

                $acct_id = $row['acct_id'];
                $class = $row['acct_class_type'];
                $debit = floatval($row['total_debit']);
                $credit = floatval($row['total_credit']);
                $closing_balance = $debit - $credit; // positive = Dr, negative = Cr

                // Only close income & expense
                if ($class == 'income' || $class == 'expense') {

                    if ($closing_balance == 0) continue;

                    if ($class == 'income') {
                        // Reverse = debit the income account
                        $this->postClosingEntry($period_end, $acct_id, $closing_balance, 'debit');
                        $this->postClosingEntry($period_end, $retained_earnings_acct, $closing_balance, 'credit');

                        $net_income += abs($closing_balance) * -1; // revenue increases profit
                    }

                    if ($class == 'expense') {
                        // Reverse = credit the expense account
                        $this->postClosingEntry($period_end, $acct_id, abs($closing_balance), 'credit');
                        $this->postClosingEntry($period_end, $retained_earnings_acct, abs($closing_balance), 'debit');

                        $net_income += abs($closing_balance); // expenses reduce profit
                    }
                }
            }

            // STEP 3 — Opening Entries (Balance Sheet Only)
            foreach ($balances as $row) {

                $acct_id = $row['acct_id'];
                $class = $row['acct_class_type'];

                if ($class == 'income' || $class == 'expense') {
                    continue; // income/expense do not carry forward
                }

                $debit = floatval($row['total_debit']);
                $credit = floatval($row['total_credit']);
                $closing_balance = $debit - $credit;

                if ($closing_balance == 0) continue;

                if ($closing_balance > 0) {
                    $this->postOpeningEntry($next_period_start, $acct_id, $closing_balance, 'debit');
                } else {
                    $this->postOpeningEntry($next_period_start, $acct_id, abs($closing_balance), 'credit');
                }
            }

            $this->db->endTransaction();

            return [
                'success' => true,
                'message' => 'Financial period closed successfully',
                'net_income' => $net_income
            ];

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function postClosingEntry($date, $acct_id, $amount, $type) {
        $this->db->query("
            INSERT INTO closing_entries (txn_date, account_id, amount, entry_type)
            VALUES (:d, :acct, :amt, :type)
        ");
        $this->db->bind(':d', $date);
        $this->db->bind(':acct', $acct_id);
        $this->db->bind(':amt', $amount);
        $this->db->bind(':type', $type);
        $this->db->execute();
    }

    private function postOpeningEntry($date, $acct_id, $amount, $type) {
        $this->db->query("
            INSERT INTO opening_entries (txn_date, account_id, amount, entry_type)
            VALUES (:d, :acct, :amt, :type)
        ");
        $this->db->bind(':d', $date);
        $this->db->bind(':acct', $acct_id);
        $this->db->bind(':amt', $amount);
        $this->db->bind(':type', $type);
        $this->db->execute();
    }
}
?>