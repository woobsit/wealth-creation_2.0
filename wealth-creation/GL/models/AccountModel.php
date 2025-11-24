<?php
require_once 'BaseModel.php';

class AccountModel extends BaseModel {

    public function getAllAccounts() {
        $sql = "SELECT * FROM accounts ORDER BY acct_code ASC";
        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccount($acct_id) {
        $sql = "SELECT * FROM accounts WHERE acct_id=?";
        $st = $this->db->prepare($sql);
        $st->execute(array($acct_id));
        return $st->fetch(PDO::FETCH_ASSOC);
    }
}
?>
