<?php
require_once __DIR__ . '/../helpers/DB.php';

class FinancialStatementsModel {

    private $db;

    public function __construct() {
        $this->db = DB::conn();
    }

    // PAGE 1
    public function chartOfAccountsSummary() {
        $sql = "SELECT 
                    a.acct_id, a.acct_code, a.acct_alias, a.acct_desc, a.acct_class,
                    SUM(CASE WHEN ag.debit_account=a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_debit,
                    SUM(CASE WHEN ag.credit_account=a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_credit
                FROM accounts a
                LEFT JOIN account_general_transaction_new ag
                     ON (ag.debit_account=a.acct_id OR ag.credit_account=a.acct_id)
                     AND ag.approval_status='Approved'
                GROUP BY a.acct_id, a.acct_code, a.acct_desc, a.acct_class
                ORDER BY a.acct_code";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // PAGE 2
    public function ledgerRows($acct_id) {
        $sql = "SELECT
                    ag.date_of_payment,
                    ag.transaction_desc,
                    ag.receipt_no,
                    CASE WHEN ag.debit_account = :acct THEN ag.amount_paid ELSE 0 END AS debit,
                    CASE WHEN ag.credit_account = :acct THEN ag.amount_paid ELSE 0 END AS credit
                FROM account_general_transaction_new ag
                WHERE ag.approval_status='Approved'
                AND (ag.debit_account = :acct OR ag.credit_account = :acct)
                ORDER BY ag.date_of_payment";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['acct'=>$acct_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccountName($acct_id) {
        $stmt = $this->db->prepare("SELECT acct_desc FROM accounts WHERE acct_id=?");
        $stmt->execute([$acct_id]);
        return $stmt->fetchColumn();
    }

    // PAGE 3
    public function trialBalance() {
        return $this->chartOfAccountsSummary();
    }

    // PAGE 4 – IFRS GENERATOR
    public function generateIFRS($period) {
        return [
            "assets_html" => "<p>Generate Assets HTML...</p>",
            "liabilities_html" => "<p>Generate Liabilities HTML...</p>",
        ];
    }
}
?>