<?php
class ClosingEngine extends BaseModel {

    public function closeFiscalYear($year, $retainedEarningsAcct) {

        // 1. Get Revenue/Expense totals from AGTN
        $sql = "
            SELECT 
                a.acct_id,
                a.acct_class_type,
                SUM(CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_debit,
                SUM(CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_credit
            FROM accounts a
            LEFT JOIN account_general_transaction_new ag
                ON ag.debit_account = a.acct_id
                OR ag.credit_account = a.acct_id
            WHERE ag.approval_status='Approved'
            AND YEAR(STR_TO_DATE(ag.date_of_payment, '%d/%m/%Y')) = ?
            AND a.acct_class_type IN ('Revenue','Expense')
            GROUP BY a.acct_id, a.acct_class_type
        ";

        $st = $this->db->prepare($sql);
        $st->execute(array($year));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // 2. Create journal entry
        $je = $this->db->prepare("
            INSERT INTO journal_entries (fiscal_year, entry_date, description) 
            VALUES (?, ?, ?)
        ");
        $je->execute(array($year, $year . "-12-31", "Year-End Closing Entry"));
        $journal_id = $this->db->lastInsertId();

        $totalIncome = 0;

        foreach ($rows as $r) {
            $amount = abs($r['total_credit'] - $r['total_debit']);

            // Revenue → DEBIT
            // Expense → CREDIT
            if ($r['acct_class_type'] == 'Revenue') {
                $this->addLine($journal_id, $r['acct_id'], $amount, 0);
                $totalIncome += $amount;
            } else {
                $this->addLine($journal_id, $r['acct_id'], 0, $amount);
                $totalIncome -= $amount;
            }
        }

        // 3. Plug retained earnings
        if ($totalIncome > 0) {
            $this->addLine($journal_id, $retainedEarningsAcct, 0, $totalIncome);
        } else {
            $this->addLine($journal_id, $retainedEarningsAcct, abs($totalIncome), 0);
        }

        return $journal_id;
    }

    private function addLine($journal_id, $acct_id, $debit, $credit) {
        $st = $this->db->prepare("
            INSERT INTO journal_lines (journal_id, acct_id, debit, credit) 
            VALUES (?, ?, ?, ?)
        ");
        $st->execute(array($journal_id, $acct_id, $debit, $credit));
    }
}
?>
