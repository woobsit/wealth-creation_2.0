
<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../helpers/session_helper.php';

// Check if user is logged in
requireLogin();
requireAnyRole(['admin', 'manager', 'accounts', 'financial_controller']);

header('Content-Type: application/json');

try {
    $database = new Database();
    
    // Get parameters
    $income_line = isset($_GET['income_line']) ? sanitize($_GET['income_line']) : '';
    $from_date = isset($_GET['from_date']) ? sanitize($_GET['from_date']) : date('Y-m-01');
    $to_date = isset($_GET['to_date']) ? sanitize($_GET['to_date']) : date('Y-m-t');
    
    if (empty($income_line)) {
        throw new Exception('Income line is required');
    }
    
    // Get account details for this income line
    $query = "SELECT * FROM accounts WHERE acct_desc = :income_line AND income_line = TRUE LIMIT 1";
    $database->query($query);
    $database->bind(':income_line', $income_line);
    $account = $database->single();
    
    if (!$account) {
        throw new Exception('Account not found for income line: ' . $income_line);
    }
    
    // Get transactions for this income line
    $query = "SELECT 
                t.*,
                DATE_FORMAT(t.date_of_payment, '%d/%m/%Y') as formatted_date,
                DATE_FORMAT(t.date_on_receipt, '%d/%m/%Y') as formatted_receipt_date
              FROM account_general_transaction_new t
              WHERE t.income_line = :income_line 
              AND DATE(t.date_of_payment) BETWEEN :from_date AND :to_date
              ORDER BY t.date_of_payment ASC, t.posting_time ASC";
              
    $database->query($query);
    $database->bind(':income_line', $income_line);
    $database->bind(':from_date', $from_date);
    $database->bind(':to_date', $to_date);
    $transactions = $database->resultSet();
    
    // Calculate running balance
    $balance = 0;
    foreach ($transactions as &$transaction) {
        $balance += (float)$transaction['amount_paid'];
        $transaction['balance'] = $balance;
    }
    
    echo json_encode([
        'success' => true,
        'account' => $account,
        'transactions' => $transactions,
        'total_amount' => $balance,
        'period' => [
            'from' => $from_date,
            'to' => $to_date
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
