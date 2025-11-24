<?php
require_once "includes/db.php";
require_once "includes/helpers.php";
require "vendor/autoload.php";

use Dompdf\Dompdf;

$acct_id = intval($_GET['acct_id']);

// Load account info
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE acct_id=?");
$stmt->execute([$acct_id]);
$acct = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch ledger rows (same SQL as ledger page, but no LIMIT)
$sql = "
SELECT
    ag.remit_id,
    ag.date_of_payment,
    ag.transaction_descr,
    ag.receipt_no,
    CASE WHEN ag.debit_account = :acct_id THEN ag.amount_paid ELSE 0 END AS debit,
    CASE WHEN ag.credit_account = :acct_id THEN ag.amount_paid ELSE 0 END AS credit
FROM account_general_transaction_new ag
WHERE ag.approval_status='Approved'
AND (ag.debit_account = :acct_id OR ag.credit_account = :acct_id)
ORDER BY ag.date_of_payment ASC, ag.remit_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':acct_id' => $acct_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$running = 0;

ob_start();
?>

<h2>Ledger - <?= htmlspecialchars($acct['acct_alias'] ?: $acct['acct_desc']) ?></h2>
<table border="1" cellspacing="0" cellpadding="4" width="100%">
<thead>
<tr>
  <th>Date</th>
  <th>Description</th>
  <th>Receipt</th>
  <th>Debit</th>
  <th>Credit</th>
  <th>Balance</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): 
    $running += ($r['debit'] - $r['credit']);
?>
<tr>
  <td><?= $r['date_of_payment'] ?></td>
  <td><?= htmlspecialchars($r['transaction_descr']) ?></td>
  <td><?= htmlspecialchars($r['receipt_no']) ?></td>
  <td><?= $r['debit'] ? number_format($r['debit'],2) : "" ?></td>
  <td><?= $r['credit'] ? number_format($r['credit'],2) : "" ?></td>
  <td><?= number_format($running,2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php
$html = ob_get_clean();

$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->render();
$pdf->stream("ledger_{$acct_id}.pdf");
