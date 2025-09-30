
<?php
header('Content-Type: application/json');
require_once 'config/Database.php';
require_once 'models/Account.php';

// Allow all origins for development (you may want to restrict this in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get query parameters
    $account_code = isset($_GET['account']) ? $_GET['account'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    // Validate parameters
    if (empty($account_code) || empty($start_date) || empty($end_date)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    // Initialize database
    $db = new Database();
    
    try {
        // Get the account details to determine which table to query
        $accountModel = new Account();
        $account = $accountModel->getAccountByCode($account_code);
        
        if (!$account || empty($account['acct_table_name'])) {
            throw new Exception('Invalid account or missing table name');
        }
        
        $table_name = $account['acct_table_name'];
        $acct_desc = $account['acct_desc'];
        $transactions = [];
        $total_amount = 0;
        
        // Query depends on the specific table structure
        switch($table_name) {
            case 'income_shop_rent':
                // Query for shop rent income
                $query = "SELECT 
                            t.id, 
                            t.date_of_payment as transaction_date, 
                            CONCAT('Shop Rent for ', isr.shop_no, ' (', isr.customer_name, ')') as description, 
                            t.receipt_no, 
                            t.amount_paid as amount 
                        FROM 
                            account_general_transaction_new t
                        JOIN 
                            income_shop_rent isr ON t.id = isr.transaction_id 
                        WHERE 
                            t.credit_account = :account_code
                            AND t.date_of_payment BETWEEN :start_date AND :end_date
                        ORDER BY 
                            t.date_of_payment";
                break;
                
            case 'income_service_charge':
                // Query for service charge income
                $query = "SELECT 
                            t.id, 
                            t.date_of_payment as transaction_date, 
                            CONCAT('Service Charge for ', isc.shop_no, ' (', isc.customer_name, ') - ', isc.month, ' ', isc.year) as description, 
                            t.receipt_no, 
                            t.amount_paid as amount 
                        FROM 
                            account_general_transaction_new t
                        JOIN 
                            income_service_charge isc ON t.id = isc.transaction_id 
                        WHERE 
                            t.credit_account = :account_code
                            AND t.date_of_payment BETWEEN :start_date AND :end_date
                        ORDER BY 
                            t.date_of_payment";
                break;
                
            default:
                // Generic query for other income types
                $query = "SELECT 
                            t.id, 
                            t.date_of_payment as transaction_date, 
                            t.transaction_desc as description, 
                            t.receipt_no, 
                            t.amount_paid as amount 
                        FROM 
                            account_general_transaction_new t
                        WHERE 
                            t.credit_account = :account_code
                            AND t.date_of_payment BETWEEN :start_date AND :end_date
                        ORDER BY 
                            t.date_of_payment";
        }
        
        // Prepare and execute the query
        $db->query($query);
        $db->bind(':account_code', $account_code);
        $db->bind(':start_date', $start_date);
        $db->bind(':end_date', $end_date);
        $transactions = $db->resultSet();
        
        // Calculate total amount
        foreach ($transactions as $transaction) {
            $total_amount += (float)$transaction['amount'];
        }
        
        // Return the summary data
        echo json_encode([
            'account' => [
                'code' => $account_code,
                'description' => $acct_desc,
                'table_name' => $table_name
            ],
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ],
            'total_amount' => $total_amount,
            'transactions' => $transactions
        ]);
        
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // Return error for non-GET requests
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}
?>
