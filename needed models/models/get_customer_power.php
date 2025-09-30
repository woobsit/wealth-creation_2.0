<?php
require_once '../config/config.php';
require_once '../config/Database.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

// Instantiate DB & connect
$database = new Database();
$db = $database->conn;

// Get shop number from URL
$shop_no = isset($_GET['shop_no']) ? $_GET['shop_no'] : '';

if(empty($shop_no)) {
    echo json_encode([
        'success' => false,
        'message' => 'Shop number is required'
    ]);
    exit();
}

try {
    // Get current month dynamically
    $current_month = date('F, Y', strtotime('-1 month'));
    
    $stmt = $db->prepare("SELECT 
        id, power_id, shop_id, shop_no, customer_name, 
        old_shop_no, old_customer_name, no_of_users,
        meter_no, meter_model, tariff, current_month,
        previous_outstanding, previous_reading, present_reading,
        consumption, cost, total_payable, total_paid,
        balance, date_of_reading, type_of_payment,
        billing_category, bill_status, vat_on_cost
        FROM customers_power_consumption 
        WHERE shop_no = :shop_no AND current_month = :current_month");
    
    $stmt->bindParam(':shop_no', $shop_no);
    $stmt->bindParam(':current_month', $current_month);
    $stmt->execute();
    
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($customer) {
        echo json_encode([
            'success' => true,
            'customer' => $customer
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No customer found with shop number: ' . $shop_no
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
