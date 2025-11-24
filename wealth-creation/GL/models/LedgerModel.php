<?php
require_once 'BaseModel.php';

class LedgerModel extends BaseModel {

    public function getGeneralLedger($offset, $perPage, $acct_id = null, $fromDate = null, $toDate = null) {
        $sql = "
        SELECT 
            a.acct_id,
            a.acct_code,
            a.acct_alias,
            a.acct_desc,
            ag.remit_id AS txn_id,
            ag.date_of_payment,
            ag.transaction_desc,
            ag.receipt_no,
            CASE WHEN ag.debit_account = a.acct_id THEN ag.amount_paid ELSE 0 END AS debit,
            CASE WHEN ag.credit_account = a.acct_id THEN ag.amount_paid ELSE 0 END AS credit
        FROM accounts a
        LEFT JOIN account_general_transaction_new ag
            ON (ag.debit_account = a.acct_id OR ag.credit_account = a.acct_id)
            AND ag.approval_status = 'Approved'
        WHERE ag.remit_id IS NOT NULL
        ";

        if ($acct_id) {
            $sql .= " AND (ag.debit_account = :acct_id OR ag.credit_account = :acct_id)";
        }

        if ($fromDate) {
            $sql .= " AND ag.date_of_payment >= :fromDate";
        }

        if ($toDate) {
            $sql .= " AND ag.date_of_payment <= :toDate";
        }

        $sql .= " ORDER BY ag.date_of_payment DESC, ag.remit_id DESC LIMIT :limit OFFSET :offset";

        $st = $this->db->prepare($sql);
        $st->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        if ($acct_id) {
            $st->bindValue(':acct_id', (int)$acct_id, PDO::PARAM_INT);
        }
        if ($fromDate) {
            $st->bindValue(':fromDate', $fromDate);
        }
        if ($toDate) {
            $st->bindValue(':toDate', $toDate);
        }

        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countGeneralLedger($acct_id = null, $fromDate = null, $toDate = null) {
        $sql = "SELECT COUNT(DISTINCT ag.remit_id) FROM account_general_transaction_new ag WHERE ag.approval_status='Approved'";

        if ($acct_id) {
            $sql .= " AND (ag.debit_account = :acct_id OR ag.credit_account = :acct_id)";
        }

        if ($fromDate) {
            $sql .= " AND ag.date_of_payment >= :fromDate";
        }

        if ($toDate) {
            $sql .= " AND ag.date_of_payment <= :toDate";
        }

        $st = $this->db->prepare($sql);

        if ($acct_id) {
            $st->bindValue(':acct_id', (int)$acct_id, PDO::PARAM_INT);
        }
        if ($fromDate) {
            $st->bindValue(':fromDate', $fromDate);
        }
        if ($toDate) {
            $st->bindValue(':toDate', $toDate);
        }

        $st->execute();
        return $st->fetchColumn();
    }

    public function getLedgerForAccount($acct_id, $fromDate = null, $toDate = null) {
        $sql = "
            SELECT 
                ag.remit_id AS id,
                ag.date_of_payment,
                ag.transaction_desc,
                ag.receipt_no,
                CASE WHEN ag.debit_account = ? THEN ag.amount_paid ELSE 0 END AS debit,
                CASE WHEN ag.credit_account = ? THEN ag.amount_paid ELSE 0 END AS credit
            FROM account_general_transaction_new ag
            WHERE ag.approval_status='Approved'
            AND (ag.debit_account = ? OR ag.credit_account = ?)
        ";

        if ($fromDate) {
            $sql .= " AND ag.date_of_payment >= ?";
        }
        if ($toDate) {
            $sql .= " AND ag.date_of_payment <= ?";
        }

        $sql .= " ORDER BY ag.date_of_payment DESC, ag.remit_id DESC";

        $st = $this->db->prepare($sql);
        $params = array($acct_id, $acct_id, $acct_id, $acct_id);
        
        if ($fromDate) {
            $params[] = $fromDate;
        }
        if ($toDate) {
            $params[] = $toDate;
        }

        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
