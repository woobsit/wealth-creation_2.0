<?php
require_once 'BaseModel.php';

class TrialBalanceModel extends BaseModel {

    public function getTrialBalance($fromDate = null, $toDate = null) {
        $sql = "
            SELECT 
                a.acct_id,
                a.acct_code,
                a.acct_alias,
                a.acct_desc,
                a.acct_type,
                a.acct_class,
                SUM(CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_debit,
                SUM(CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END) AS total_credit
            FROM accounts a
            LEFT JOIN account_general_transaction_new ag
                ON (ag.debit_account = a.acct_id OR ag.credit_account = a.acct_id)
                AND ag.approval_status='Approved'
        ";

        $params = array();
        
        if ($fromDate) {
            $sql .= " AND ag.date_of_payment >= :fromDate";
            $params[':fromDate'] = $fromDate;
        }
        
        if ($toDate) {
            $sql .= " AND ag.date_of_payment <= :toDate";
            $params[':toDate'] = $toDate;
        }

        $sql .= " GROUP BY a.acct_id, a.acct_code, a.acct_alias, a.acct_desc, a.acct_type, a.acct_class
            ORDER BY a.acct_code";

        $st = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $st->bindValue($key, $value);
        }
        
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

