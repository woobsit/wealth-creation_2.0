<?php
// models/Journal.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

class Journal {
    protected $db;
    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Create a journal entry in DRAFT. $lines = array of ['acct_id'=>..,'debit'=>..,'credit'=>..,'narrative'=>..]
    public function createEntry($entryDate, $periodId, $description, $referenceNo, $entryType, $createdBy, $lines) {
        // validation: lines present
        if (!is_array($lines) || count($lines) < 2) {
            throw new Exception("A journal entry must have at least two lines.");
        }

        // compute sums
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $ln) {
            $totalDebit += floatval(isset($ln['debit']) ? $ln['debit'] : 0);
            $totalCredit += floatval(isset($ln['credit']) ? $ln['credit'] : 0);
        }

        // Accept creation even if not balanced if you want drafts; here we require balanced for creation to avoid bad data.
        if (round($totalDebit,2) != round($totalCredit,2)) {
            throw new Exception("Journal entry is not balanced (debits: {$totalDebit}, credits: {$totalCredit}).");
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO journal_entries (entry_date, period_id, description, reference_no, entry_type, status, created_by) VALUES (:d,:p,:desc,:ref,:type,'Draft',:cb)");
            $stmt->execute(array(':d'=>$entryDate, ':p'=>$periodId, ':desc'=>$description, ':ref'=>$referenceNo, ':type'=>$entryType, ':cb'=>$createdBy));
            $entryId = $this->db->lastInsertId();

            $lineNo = 1;
            $ins = $this->db->prepare("INSERT INTO journal_lines (journal_entry_id, line_no, acct_id, debit, credit, narrative) VALUES (:je,:ln,:acct,:d,:c,:n)");
            foreach ($lines as $ln) {
                $ins->execute(array(
                    ':je'=>$entryId,
                    ':ln'=>$lineNo,
                    ':acct'=>intval($ln['acct_id']),
                    ':d'=>floatval(isset($ln['debit']) ? $ln['debit'] : 0),
                    ':c'=>floatval(isset($ln['credit']) ? $ln['credit'] : 0),
                    ':n'=>isset($ln['narrative']) ? $ln['narrative'] : null
                ));
                $lineNo++;
            }

            audit_log($this->db, 'journal_entries', $entryId, 'create', json_encode(array('description'=>$description,'reference'=>$referenceNo)), $createdBy);

            $this->db->commit();
            return $entryId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Post (publish) an entry: sets status to Posted and records posted_by/posted_at
    public function postEntry($entryId, $postedBy) {
        // confirm balanced before posting (again)
        $stmt = $this->db->prepare("SELECT SUM(debit) AS sd, SUM(credit) AS sc FROM journal_lines WHERE journal_entry_id = :id");
        $stmt->execute(array(':id'=>$entryId));
        $row = $stmt->fetch();
        $sd = floatval($row['sd']);
        $sc = floatval($row['sc']);
        if (round($sd,2) !== round($sc,2)) {
            throw new Exception("Cannot post: journal entry lines are not balanced (debits={$sd}, credits={$sc}).");
        }

        // Post
        $this->db->beginTransaction();
        try {
            $upd = $this->db->prepare("UPDATE journal_entries SET status = 'Posted', posted_by = :pb, posted_at = NOW() WHERE id = :id");
            $upd->execute(array(':pb'=>$postedBy, ':id'=>$entryId));

            audit_log($this->db, 'journal_entries', $entryId, 'post', 'posted by ' . $postedBy, $postedBy);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Create opening balances for a period (saves opening_balances rows)
    public function createOpeningBalance($periodId, $acctId, $amount, $side, $createdBy) {
        if (!in_array($side, array('Debit','Credit'))) {
            throw new Exception("Opening balance side must be Debit or Credit.");
        }
        $stmt = $this->db->prepare("INSERT INTO opening_balances (acct_id, period_id, amount, side, created_by) VALUES (:acct,:per,:amt,:side,:cb)");
        $stmt->execute(array(':acct'=>$acctId, ':per'=>$periodId, ':amt'=>floatval($amount), ':side'=>$side, ':cb'=>$createdBy));
        $obId = $this->db->lastInsertId();
        audit_log($this->db, 'opening_balances', $obId, 'create', json_encode(array('acct'=>$acctId,'amount'=>$amount,'side'=>$side)), $createdBy);
        return $obId;
    }

    // Convert opening_balances for a period into a single OpeningBalance journal entry
    // This posts a single journal entry that balances every account opening.
    public function applyOpeningBalancesToJournal($periodId, $createdBy) {
        // Gather all opening balances for period
        $stmt = $this->db->prepare("SELECT o.*, a.normal_balance FROM opening_balances o JOIN accounts a ON a.acct_id = o.acct_id WHERE o.period_id = :p");
        $stmt->execute(array(':p'=>$periodId));
        $rows = $stmt->fetchAll();

        if (!$rows) {
            throw new Exception("No opening balances found for period " . $periodId);
        }

        // We'll create many lines: debit side positive amounts, credit side positive amounts, then a balancing equity account if needed
        $lines = array();
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($rows as $r) {
            $amt = floatval($r['amount']);
            if ($r['side'] === 'Debit') {
                $lines[] = array('acct_id'=>$r['acct_id'], 'debit'=>$amt, 'credit'=>0, 'narrative'=>'Opening balance');
                $totalDebit += $amt;
            } else {
                $lines[] = array('acct_id'=>$r['acct_id'], 'debit'=>0, 'credit'=>$amt, 'narrative'=>'Opening balance');
                $totalCredit += $amt;
            }
        }

        // If totals don't match, include a balancing line to Retained Earnings or Opening Equity account
        // Attempt to find a retained earnings acct or fallback to first equity acct
        if (round($totalDebit,2) != round($totalCredit,2)) {
            $diff = round($totalDebit - $totalCredit,2);
            // find retained earnings (acct_code '3000' or acct_type Equity)
            $stmt = $this->db->query("SELECT acct_id FROM accounts WHERE acct_type = 'Equity' LIMIT 1");
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception("No equity account found to balance opening balances. Create one first.");
            }
            $balAcct = intval($row['acct_id']);
            if ($diff > 0) {
                // totalDebit > totalCredit, add credit to balance
                $lines[] = array('acct_id'=>$balAcct, 'debit'=>0, 'credit'=>$diff, 'narrative'=>'Opening balance adjustment');
                $totalCredit += $diff;
            } else if ($diff < 0) {
                $d = abs($diff);
                $lines[] = array('acct_id'=>$balAcct, 'debit'=>$d, 'credit'=>0, 'narrative'=>'Opening balance adjustment');
                $totalDebit += $d;
            }
        }

        // Create one OpeningBalance journal entry and post it
        $entryDate = null; // choose period start
        // Fetch period start
        $stmtp = $this->db->prepare("SELECT start_date FROM fiscal_periods WHERE id = :id LIMIT 1");
        $stmtp->execute(array(':id'=>$periodId));
        $pd = $stmtp->fetch();
        $entryDate = ($pd ? $pd['start_date'] : date('Y-m-d'));

        $jeId = $this->createEntry($entryDate, $periodId, 'Opening balances for period ' . $periodId, 'OPEN-' . $periodId, 'OpeningBalance', $createdBy, $lines);

        // Post it
        $this->postEntry($jeId, $createdBy);

        audit_log($this->db, 'journal_entries', $jeId, 'apply_openings', 'Applied opening balances for period ' . $periodId, $createdBy);

        return $jeId;
    }

    // Trial balance aggregator combining opening_balances and posted journal_entries
    public function trialBalance($asOfDate = null) {
        // We'll sum posted journal_lines up to the asOfDate and also include opening_balances applied as a posted OpeningBalance entry.
        // Simpler approach: sum posted journal_lines only (since applyOpeningBalancesToJournal will create a posted OpeningBalance JE)
        $params = array();
        $whereDate = "";
        if ($asOfDate) {
            $whereDate = " AND je.entry_date <= :asof ";
            $params[':asof'] = $asOfDate;
        }

        $sql = "
            SELECT jl.acct_id,
                   SUM(jl.debit) AS total_debit,
                   SUM(jl.credit) AS total_credit
            FROM journal_lines jl
            JOIN journal_entries je ON je.id = jl.journal_entry_id
            WHERE je.status = 'Posted' " . $whereDate . "
            GROUP BY jl.acct_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Build map
        $tb = array();
        foreach ($rows as $r) {
            $aid = intval($r['acct_id']);
            $d = floatval($r['total_debit']);
            $c = floatval($r['total_credit']);
            $tb[$aid] = array('debit'=>$d, 'credit'=>$c, 'net'=>$d - $c);
        }

        return $tb;
    }
}
?>
