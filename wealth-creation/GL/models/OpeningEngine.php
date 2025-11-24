<?php
class OpeningEngine extends BaseModel {

    public function createOpeningBalances($closingJournalId, $newYear) {

        // fetch closing lines
        $sql = "SELECT acct_id, debit, credit FROM journal_lines WHERE journal_id = ?";
        $st = $this->db->prepare($sql);
        $st->execute(array($closingJournalId));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // create opening JE
        $je = $this->db->prepare("
            INSERT INTO journal_entries (fiscal_year, entry_date, description) 
            VALUES (?, ?, ?)
        ");
        $je->execute(array($newYear, $newYear . "-01-01", "Opening Balances"));
        $journal_id = $this->db->lastInsertId();

        foreach ($rows as $r) {

            // Reverse the closing entries
            $this->db->prepare("
                INSERT INTO journal_lines (journal_id, acct_id, debit, credit)
                VALUES (?, ?, ?, ?)
            ")->execute(array(
                $journal_id,
                $r['acct_id'],
                $r['credit'],  // reverse
                $r['debit']    // reverse
            ));
        }

        return $journal_id;
    }
}
?>
