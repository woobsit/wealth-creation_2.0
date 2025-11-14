
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

// Get shop ID from URL
$shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : '';

if(empty($shop_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Shop ID is required'
    ]);
    exit();
}

try {
    // Get consumption history
    $stmt = $db->prepare("SELECT 
                          pid, power_id, shop_id, shop_no, customer_name, current_month,
                          previous_reading, present_reading, date_of_reading, consumption, cost
                          FROM customers_power_consumption_history 
                          WHERE shop_id = :shop_id 
                          ORDER BY date_of_reading DESC 
                          LIMIT 6");
    $stmt->bindParam(':shop_id', $shop_id);
    $stmt->execute();
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
