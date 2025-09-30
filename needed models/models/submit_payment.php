
<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../models/Account.php';
require_once '../helpers/session_helper.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $db = new Database();
    $userModel = new User();
    $accountModel = new Account();
    
    // Get user information
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Unknown';
    $userDepartment = $_SESSION['department'] ?? '';
    
    // Validate required fields
    $requiredFields = ['receipt_no', 'date_of_payment', 'amount_paid', 'payment_type', 'income_line', 'table_name'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field {$field} is required"]);
            exit();
        }
    }
    
    // Sanitize input data
    $receipt_no = sanitize($_POST['receipt_no']);
    $date_of_payment = sanitize($_POST['date_of_payment']);
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_type = sanitize($_POST['payment_type']);
    $income_line = sanitize($_POST['income_line']);
    $table_name = sanitize($_POST['table_name']);
    $description = sanitize($_POST['description'] ?? '');
    
    // Validate amount
    if ($amount_paid <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
        exit();
    }
    
    // Check if receipt number already exists
    $db->query("SELECT id FROM transactions WHERE receipt_no = :receipt_no");
    $db->bind(':receipt_no', $receipt_no);
    if ($db->single()) {
        echo json_encode(['success' => false, 'message' => 'Receipt number already exists']);
        exit();
    }
    
    // Determine debit and credit accounts
    $debit_account = 'TILL-001'; // Default for leasing officers
    $credit_account = $income_line;
    
    // For accounts officers, use their specified accounts
    if (in_array(strtolower($userDepartment), ['accounts', 'finance'])) {
        $debit_account = sanitize($_POST['debit_account'] ?? 'TILL-001');
        $credit_account = sanitize($_POST['credit_account'] ?? $income_line);
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert into main transactions table
    $db->query("INSERT INTO transactions (
        receipt_no, 
        customer_name, 
        date_of_payment, 
        amount_paid, 
        income_line, 
        payment_type, 
        debit_account, 
        credit_account, 
        posting_officer_id, 
        posting_officer_name,
        transaction_desc,
        created_at
    ) VALUES (
        :receipt_no, 
        :customer_name, 
        :date_of_payment, 
        :amount_paid, 
        :income_line, 
        :payment_type, 
        :debit_account, 
        :credit_account, 
        :posting_officer_id, 
        :posting_officer_name,
        :transaction_desc,
        NOW()
    )");
    
    $db->bind(':receipt_no', $receipt_no);
    $db->bind(':customer_name', $_POST['customer_name'] ?? '');
    $db->bind(':date_of_payment', $date_of_payment);
    $db->bind(':amount_paid', $amount_paid);
    $db->bind(':income_line', $income_line);
    $db->bind(':payment_type', $payment_type);
    $db->bind(':debit_account', $debit_account);
    $db->bind(':credit_account', $credit_account);
    $db->bind(':posting_officer_id', $userId);
    $db->bind(':posting_officer_name', $userName);
    $db->bind(':transaction_desc', $description);
    
    if (!$db->execute()) {
        throw new Exception('Failed to insert transaction');
    }
    
    $transaction_id = $db->lastInsertId();
    
    // Insert into specific income table if it exists
    if (!empty($table_name) && $table_name !== 'transactions') {
        $specific_fields = [];
        $specific_values = [];
        $bind_params = [];
        
        // Common fields for all income tables
        $specific_fields[] = 'transaction_id';
        $specific_values[] = ':transaction_id';
        $bind_params[':transaction_id'] = $transaction_id;
        
        $specific_fields[] = 'receipt_no';
        $specific_values[] = ':receipt_no';
        $bind_params[':receipt_no'] = $receipt_no;
        
        $specific_fields[] = 'amount';
        $specific_values[] = ':amount';
        $bind_params[':amount'] = $amount_paid;
        
        $specific_fields[] = 'date_paid';
        $specific_values[] = ':date_paid';
        $bind_params[':date_paid'] = $date_of_payment;
        
        // Add specific fields based on income line
        $dynamicFields = [
            'shop_id', 'shop_no', 'shop_size', 'start_date', 'end_date',
            'plate_no', 'no_of_tickets', 'location', 'trade_type', 
            'animal_type', 'no_of_animals', 'month_year'
        ];
        
        foreach ($dynamicFields as $field) {
            if (!empty($_POST[$field])) {
                $specific_fields[] = $field;
                $specific_values[] = ":{$field}";
                $bind_params[":{$field}"] = sanitize($_POST[$field]);
            }
        }
        
        $specific_fields[] = 'created_at';
        $specific_values[] = 'NOW()';
        
        // Build and execute the query
        $sql = "INSERT INTO {$table_name} (" . implode(', ', $specific_fields) . ") VALUES (" . implode(', ', $specific_values) . ")";
        $db->query($sql);
        
        foreach ($bind_params as $param => $value) {
            $db->bind($param, $value);
        }
        
        if (!$db->execute()) {
            throw new Exception('Failed to insert into specific income table');
        }
    }
    
    // Commit transaction
    $db->endTransaction();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payment submitted successfully',
        'transaction_id' => $transaction_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->cancelTransaction();
    }
    
    error_log("Payment submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing the payment'
    ]);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
