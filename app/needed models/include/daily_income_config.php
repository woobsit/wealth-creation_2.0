
<?php
// Daily Income Analysis Configuration and Data Processing

function getDailyIncomeData($selected_month, $selected_year, $database) {
    // Convert month name to number for queries
    $month_num = date('m', strtotime($selected_month . ' 1'));
    $start_date = $selected_year . '-' . $month_num . '-01';
    $end_date = $selected_year . '-' . $month_num . '-' . date('t', strtotime($start_date));
    $days_in_month = (int)date('t', strtotime($start_date));

    // Get all unique income lines for the selected period
    $query = "SELECT DISTINCT income_line 
              FROM account_general_transaction_new 
              WHERE income_line IS NOT NULL 
              AND income_line != '' 
              AND DATE(date_of_payment) BETWEEN :start_date AND :end_date
              ORDER BY income_line ASC";

    $database->query($query);
    $database->bind(':start_date', $start_date);
    $database->bind(':end_date', $end_date);
    $income_lines = $database->resultSet();

    // Initialize data structures
    $daily_analysis = [];
    $daily_totals = [];
    $grand_total = 0;

    // Initialize daily totals
    for ($day = 1; $day <= $days_in_month; $day++) {
        $daily_totals[$day] = 0;
    }

    // Calculate Sundays for styling
    $sundays = [];
    for ($week = 1; $week <= 5; $week++) {
        $sunday_date = date("Y-m-d", strtotime("{$week} Sunday of {$selected_month} {$selected_year}"));
        if (date('F', strtotime($sunday_date)) == $selected_month) {
            $sundays[] = (int)date('d', strtotime($sunday_date));
        }
    }

    // Process each income line
    foreach ($income_lines as $income_line_row) {
        $income_line = $income_line_row['income_line'];
        
        $line_data = [
            'income_line' => $income_line,
            'days' => [],
            'total' => 0
        ];
        
        // Get daily data for this income line
        for ($day = 1; $day <= $days_in_month; $day++) {
            $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
            $date = $selected_year . '-' . $month_num . '-' . $day_formatted;
            
            $query = "SELECT SUM(amount_paid) as total 
                      FROM account_general_transaction_new 
                      WHERE income_line = :income_line 
                      AND DATE(date_of_payment) = :date";
                      
            $database->query($query);
            $database->bind(':income_line', $income_line);
            $database->bind(':date', $date);
            $result = $database->single();
            
            $amount = $result['total'] ? (float)$result['total'] : 0;
            
            $line_data['days'][$day] = $amount;
            $line_data['total'] += $amount;
            $daily_totals[$day] += $amount;
        }
        
        $grand_total += $line_data['total'];
        $daily_analysis[] = $line_data;
    }

    // Sort by total revenue descending
    usort($daily_analysis, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    return [
        'daily_analysis' => $daily_analysis,
        'daily_totals' => $daily_totals,
        'grand_total' => $grand_total,
        'days_in_month' => $days_in_month,
        'sundays' => $sundays
    ];
}

// Helper function to check if day is Sunday
function isSunday($day, $sundays) {
    return in_array($day, $sundays);
}
?>
