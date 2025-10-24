
<?php
require_once '../config/config.php';
require_once '../config/Database.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Instantiate DB & connect
$database = new Database();
$db = $database->conn;

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate data
if(!$data || !$data->shop_no || !$data->present_reading || !$data->date_of_reading || !$data->current_month) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // First, get current record to move to history
    $stmt = $db->prepare("SELECT * FROM customers_power_consumption WHERE shop_no = :shop_no AND current_month = :current_month");
    $stmt->bindParam(':shop_no', $data->shop_no);
    $stmt->bindParam(':current_month', $data->current_month);
    $stmt->execute();
    
    $current_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$current_record) {
        throw new Exception("No existing record found for this shop number");
    }
    
    // Insert into history table
    $stmt = $db->prepare("INSERT INTO customers_power_consumption_history (
                            pid, power_id, shop_id, shop_no, customer_name, old_shop_no,
                            old_customer_name, no_of_users, meter_no, meter_model, tariff,
                            current_month, previous_reading, present_reading, date_of_reading,
                            consumption, cost, outstanding_balance, total_payable, total_paid,
                            balance, type_of_payment, staff_id, staff_name, 
                            updating_officer_id, updating_officer_name, update_timestamp,
                            update_status, billing_category, vat_on_cost
                          ) VALUES (
                            :id, :power_id, :shop_id, :shop_no, :customer_name, :old_shop_no,
                            :old_customer_name, :no_of_users, :meter_no, :meter_model, :tariff,
                            :current_month, :previous_reading, :present_reading, :date_of_reading,
                            :consumption, :cost, :previous_outstanding, :total_payable, :total_paid,
                            :balance, :type_of_payment, :staff_id, :staff_name,
                            :updating_officer_id, :updating_officer_name, NOW(),
                            'Completed', :billing_category, :vat_on_cost
                          )");
    
    // Bind params from current record and new data
    $stmt->bindParam(':id', $current_record['id']);
    $stmt->bindParam(':power_id', $current_record['power_id']);
    $stmt->bindParam(':shop_id', $current_record['shop_id']);
    $stmt->bindParam(':shop_no', $current_record['shop_no']);
    $stmt->bindParam(':customer_name', $current_record['customer_name']);
    $stmt->bindParam(':old_shop_no', $current_record['old_shop_no']);
    $stmt->bindParam(':old_customer_name', $current_record['old_customer_name']);
    $stmt->bindParam(':no_of_users', $current_record['no_of_users']);
    $stmt->bindParam(':meter_no', $current_record['meter_no']);
    $stmt->bindParam(':meter_model', $current_record['meter_model']);
    $stmt->bindParam(':tariff', $current_record['tariff']);
    $stmt->bindParam(':current_month', $current_record['current_month']);
    $stmt->bindParam(':previous_reading', $current_record['previous_reading']);
    $stmt->bindParam(':present_reading', $data->present_reading);
    $stmt->bindParam(':date_of_reading', $data->date_of_reading);
    $stmt->bindParam(':consumption', $data->consumption);
    $stmt->bindParam(':cost', $data->cost);
    $stmt->bindParam(':previous_outstanding', $current_record['previous_outstanding']);
    $stmt->bindParam(':total_payable', $data->total_payable);
    $stmt->bindParam(':total_paid', $current_record['total_paid']);
    $stmt->bindParam(':balance', $data->balance);
    $stmt->bindParam(':type_of_payment', $current_record['type_of_payment']);
    $stmt->bindParam(':staff_id', $data->staff_id);
    $stmt->bindParam(':staff_name', $data->staff_name);
    $stmt->bindParam(':updating_officer_id', $data->updating_officer_id);
    $stmt->bindParam(':updating_officer_name', $data->updating_officer_name);
    $stmt->bindParam(':billing_category', $current_record['billing_category']);
    $stmt->bindParam(':vat_on_cost', $data->vat_on_cost);
    
    $stmt->execute();
    
    // Update the current record with new readings
    $stmt = $db->prepare("UPDATE customers_power_consumption SET
                            previous_reading = :previous_reading,
                            present_reading = :present_reading,
                            consumption = :consumption,
                            cost = :cost,
                            total_payable = :total_payable,
                            balance = :balance,
                            date_of_reading = :date_of_reading,
                            update_timestamp = NOW(),
                            staff_id = :staff_id,
                            staff_name = :staff_name,
                            updating_officer_id = :updating_officer_id,
                            updating_officer_name = :updating_officer_name,
                            vat_on_cost = :vat_on_cost
                          WHERE id = :id");
    
    // Bind params for update
    $stmt->bindParam(':previous_reading', $current_record['previous_reading']);
    $stmt->bindParam(':present_reading', $data->present_reading);
    $stmt->bindParam(':consumption', $data->consumption);
    $stmt->bindParam(':cost', $data->cost);
    $stmt->bindParam(':total_payable', $data->total_payable);
    $stmt->bindParam(':balance', $data->balance);
    $stmt->bindParam(':date_of_reading', $data->date_of_reading);
    $stmt->bindParam(':staff_id', $data->staff_id);
    $stmt->bindParam(':staff_name', $data->staff_name);
    $stmt->bindParam(':updating_officer_id', $data->updating_officer_id);
    $stmt->bindParam(':updating_officer_name', $data->updating_officer_name);
    $stmt->bindParam(':vat_on_cost', $data->vat_on_cost);
    $stmt->bindParam(':id', $current_record['id']);
    
    $stmt->execute();
    
    // Commit the transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Meter reading saved successfully',
        'data' => [
            'id' => $current_record['id'],
            'shop_no' => $current_record['shop_no'],
            'customer_name' => $current_record['customer_name'],
            'current_month' => $current_record['current_month'],
            'present_reading' => $data->present_reading
        ]
    ]);
    
} catch(Exception $e) {
    // Roll back the transaction if something failed
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
