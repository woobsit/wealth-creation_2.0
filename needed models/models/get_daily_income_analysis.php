
<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../helpers/session_helper.php';

// Allow for development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Check if user is logged in
requireLogin();
requireAnyRole(['admin', 'manager', 'accounts', 'financial_controller']);

// Get query parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('F');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Validate parameters
if (empty($month) || empty($year)) {
    http_response_code(400);
    echo json_encode(['error' => 'Month and year are required parameters']);
    exit;
}

// Initialize database connection
$db = new Database();

try {
    // Convert month name to number for queries
    $month_num = date('m', strtotime($month . ' 1'));
    $start_date = $year . '-' . $month_num . '-01';
    $end_date = $year . '-' . $month_num . '-' . date('t', strtotime($start_date));
    
    // Get all unique income lines for the selected period
    $query = "SELECT DISTINCT income_line 
              FROM account_general_transaction_new 
              WHERE income_line IS NOT NULL 
              AND income_line != '' 
              AND DATE(date_of_payment) BETWEEN :start_date AND :end_date
              ORDER BY income_line ASC";
              
    $db->query($query);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    $income_lines = $db->resultSet();
    
    // Prepare data structure
    $daily_analysis = [];
    $daily_totals = [];
    $grand_total = 0;
    
    // Initialize daily totals array for all days of the month
    $days_in_month = (int)date('t', strtotime($start_date));
    for ($day = 1; $day <= $days_in_month; $day++) {
        $daily_totals[$day] = 0;
    }
    
    // Calculate Sundays for styling
    $first_sunday = date("Y-m-d", strtotime("first Sunday of " . $month . " " . $year));
    $second_sunday = date("Y-m-d", strtotime("second Sunday of " . $month . " " . $year));
    $third_sunday = date("Y-m-d", strtotime("third Sunday of " . $month . " " . $year));
    $fourth_sunday = date("Y-m-d", strtotime("fourth Sunday of " . $month . " " . $year));
    $fifth_sunday = date("Y-m-d", strtotime("fifth Sunday of " . $month . " " . $year));
    
    $sundays = [
        (int)date('d', strtotime($first_sunday)),
        (int)date('d', strtotime($second_sunday)),
        (int)date('d', strtotime($third_sunday)),
        (int)date('d', strtotime($fourth_sunday))
    ];
    
    if (date('F', strtotime($fifth_sunday)) == $month) {
        $sundays[] = (int)date('d', strtotime($fifth_sunday));
    }
    
    // Process each income line
    foreach ($income_lines as $income_line_row) {
        $income_line = $income_line_row['income_line'];
        
        // Initialize data structure for this income line
        $line_data = [
            'income_line' => $income_line,
            'days' => [],
            'total' => 0
        ];
        
        // Get daily collection data for this income line
        for ($day = 1; $day <= $days_in_month; $day++) {
            $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
            $date = $year . '-' . $month_num . '-' . $day_formatted;
            
            // Query for daily amount for this specific income line
            $query = "SELECT SUM(amount_paid) as total 
                      FROM account_general_transaction_new 
                      WHERE income_line = :income_line 
                      AND DATE(date_of_payment) = :date";
                      
            $db->query($query);
            $db->bind(':income_line', $income_line);
            $db->bind(':date', $date);
            $result = $db->single();
            
            $amount = $result['total'] ? (float)$result['total'] : 0;
            
            // Store amount for this day
            $line_data['days'][$day] = $amount;
            $line_data['total'] += $amount;
            
            // Add to daily totals
            $daily_totals[$day] += $amount;
        }
        
        // Add to grand total
        $grand_total += $line_data['total'];
        
        // Add this income line to analysis data
        $daily_analysis[] = $line_data;
    }
    
    // Sort income lines by total revenue (descending)
    usort($daily_analysis, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => $daily_analysis,
        'period' => [
            'month' => $month,
            'year' => $year,
            'days_in_month' => $days_in_month
        ],
        'totals' => [
            'daily' => $daily_totals,
            'grand_total' => $grand_total
        ],
        'sundays' => $sundays,
        'metadata' => [
            'total_income_lines' => count($daily_analysis),
            'date_range' => [
                'start' => $start_date,
                'end' => $end_date
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
